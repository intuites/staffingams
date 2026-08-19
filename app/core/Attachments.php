<?php
/**
 * Shared attachment handling: validate uploaded files, push to Supabase
 * Storage, record in the right {entity}_attachments table.
 */

class Attachments
{
    public const MAX_BYTES = 10 * 1024 * 1024; // 10 MB

    private const ALLOWED_MIME = [
        'application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'text/csv',
    ];

    /** entity => [table, fk column, bucket] */
    private const MAP = [
        'company'     => ['company_attachments', 'company_id', 'companies'],
        'candidate'   => ['candidate_attachments', 'candidate_id', 'candidates'],
        'project'     => ['project_attachments', 'project_id', 'projects'],
        'transaction' => ['transaction_attachments', 'transaction_id', 'transactions'],
    ];

    /**
     * Handle the $_FILES['attachments'] array (multiple) for an entity.
     * Returns array of error strings (empty = all good).
     */
    public static function store(string $entity, int $entityId, array $files): array
    {
        [$table, $fk, $bucket] = self::MAP[$entity];
        $errors  = [];
        $storage = new SupabaseStorage();

        $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $name = $files['name'][$i];
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "Upload failed for {$name}.";
                continue;
            }
            if ($files['size'][$i] > self::MAX_BYTES) {
                $errors[] = "{$name} exceeds the 10 MB limit.";
                continue;
            }
            $tmp  = $files['tmp_name'][$i];
            $mime = mime_content_type($tmp) ?: 'application/octet-stream';
            if (!in_array($mime, self::ALLOWED_MIME, true)) {
                $errors[] = "{$name}: file type {$mime} is not allowed.";
                continue;
            }
            $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
            $path = $entityId . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safe;

            if (!$storage->isConfigured()) {
                $errors[] = "{$name}: file storage is not configured (SUPABASE_URL / SUPABASE_SERVICE_KEY missing).";
                continue;
            }
            try {
                $storage->upload($bucket, $path, $tmp, $mime);
                Database::q(
                    "INSERT INTO {$table} ({$fk}, storage_path, original_filename, mime_type, size_bytes)
                     VALUES (:id, :p, :n, :m, :s)",
                    ['id' => $entityId, 'p' => $path, 'n' => $name, 'm' => $mime, 's' => $files['size'][$i]]
                );
            } catch (Throwable $ex) {
                $errors[] = "{$name}: storage upload failed (" . $ex->getMessage() . ").";
            }
        }
        return $errors;
    }

    public static function forEntity(string $entity, int $entityId): array
    {
        [$table, $fk] = self::MAP[$entity];
        return Database::all(
            "SELECT * FROM {$table} WHERE {$fk} = :id ORDER BY uploaded_at DESC",
            ['id' => $entityId]
        );
    }

    public static function countFor(string $entity, int $entityId): int
    {
        [$table, $fk] = self::MAP[$entity];
        return (int) Database::scalar("SELECT COUNT(*) FROM {$table} WHERE {$fk} = :id", ['id' => $entityId]);
    }

    /** Redirect to a signed URL for download. */
    public static function download(string $entity, int $attachmentId): never
    {
        [$table, , $bucket] = self::MAP[$entity];
        $row = Database::one("SELECT * FROM {$table} WHERE id = :id", ['id' => $attachmentId]);
        if (!$row) {
            http_response_code(404);
            exit('Attachment not found');
        }
        $storage = new SupabaseStorage();
        redirect($storage->signedUrl($bucket, $row['storage_path']));
    }

    public static function delete(string $entity, int $attachmentId): void
    {
        [$table, , $bucket] = self::MAP[$entity];
        $row = Database::one("SELECT * FROM {$table} WHERE id = :id", ['id' => $attachmentId]);
        if (!$row) {
            return;
        }
        try {
            (new SupabaseStorage())->delete($bucket, $row['storage_path']);
        } catch (Throwable) {
            // Best effort — still remove the DB row.
        }
        Database::q("DELETE FROM {$table} WHERE id = :id", ['id' => $attachmentId]);
    }
}
