<?php

class AttachmentsController
{
    private const ENTITIES = ['company', 'candidate', 'project', 'transaction'];

    public function download(string $entity, string $id): void
    {
        Auth::requireLogin();
        if (!in_array($entity, self::ENTITIES, true)) {
            http_response_code(404);
            exit('Not found');
        }
        Attachments::download($entity, (int) $id);
    }

    public function destroy(string $entity, string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        if (!in_array($entity, self::ENTITIES, true)) {
            http_response_code(404);
            exit('Not found');
        }
        Attachments::delete($entity, (int) $id);
        flash('success', 'Attachment deleted.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
