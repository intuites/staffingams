<?php

/**
 * Candidate-initiated review request on one of their own transactions.
 * requires_super mirrors whether the transaction was locked at request time —
 * locked transactions can only be edited by a super admin, so those requests
 * route to the super admin queue; the rest go to the admin queue.
 */
class ReviewRequest
{
    public static function create(int $transactionId, int $candidateId, string $comment, bool $requiresSuper): int
    {
        Database::q(
            'INSERT INTO transaction_review_requests (transaction_id, candidate_id, comment, requires_super)
             VALUES (:t, :c, :m, :s)',
            ['t' => $transactionId, 'c' => $candidateId, 'm' => $comment, 's' => $requiresSuper ? 'true' : 'false']
        );
        return (int) Database::scalar(
            'SELECT id FROM transaction_review_requests WHERE transaction_id = :t ORDER BY id DESC LIMIT 1',
            ['t' => $transactionId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            "SELECT r.*, t.transaction_id AS txn_code, t.status AS txn_status, t.type, t.effective_amount,
                    t.transaction_date, t.amount_notes, t.candidate_id AS txn_candidate_id,
                    c.first_name || ' ' || c.last_name AS candidate_name, p.project_name
             FROM transaction_review_requests r
             JOIN transactions t ON t.id = r.transaction_id
             JOIN candidates c ON c.id = r.candidate_id
             LEFT JOIN projects p ON p.id = t.project_id
             WHERE r.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Open requests visible to the current role: super admins see everything,
     * regular admins only requests that don't require super (unlocked txns).
     */
    public static function open(bool $forSuper): array
    {
        $sql = "SELECT r.*, t.transaction_id AS txn_code, t.status AS txn_status, t.type, t.effective_amount,
                       t.transaction_date, c.first_name || ' ' || c.last_name AS candidate_name, p.project_name
                FROM transaction_review_requests r
                JOIN transactions t ON t.id = r.transaction_id
                JOIN candidates c ON c.id = r.candidate_id
                LEFT JOIN projects p ON p.id = t.project_id
                WHERE r.status = 'open'";
        if (!$forSuper) {
            $sql .= ' AND r.requires_super = FALSE';
        }
        $sql .= ' ORDER BY r.created_at ASC';
        return Database::all($sql);
    }

    public static function openCount(bool $forSuper): int
    {
        $sql = "SELECT COUNT(*) FROM transaction_review_requests WHERE status = 'open'";
        if (!$forSuper) {
            $sql .= ' AND requires_super = FALSE';
        }
        return (int) Database::scalar($sql);
    }

    /** Transaction ids of this candidate's OPEN requests (to show state in the portal). */
    public static function openTxnIdsForCandidate(int $candidateId): array
    {
        return array_map('intval', array_column(
            Database::all(
                "SELECT transaction_id FROM transaction_review_requests WHERE candidate_id = :c AND status = 'open'",
                ['c' => $candidateId]
            ),
            'transaction_id'
        ));
    }

    /** This candidate's requests with outcomes (portal history). */
    public static function forCandidate(int $candidateId): array
    {
        return Database::all(
            "SELECT r.*, t.transaction_id AS txn_code
             FROM transaction_review_requests r
             JOIN transactions t ON t.id = r.transaction_id
             WHERE r.candidate_id = :c
             ORDER BY r.created_at DESC",
            ['c' => $candidateId]
        );
    }

    public static function resolve(int $id, int $adminId, ?string $response): void
    {
        Database::q(
            "UPDATE transaction_review_requests
             SET status = 'resolved', resolved_by = :a, resolved_at = NOW(), admin_response = :r
             WHERE id = :id AND status = 'open'",
            ['a' => $adminId, 'r' => $response, 'id' => $id]
        );
    }
}
