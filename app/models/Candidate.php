<?php

class Candidate
{
    public static function nextCode(): string
    {
        $max = (int) Database::scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(candidate_id FROM 6) AS INTEGER)), 0) FROM candidates"
        );
        return sprintf('CAND-%04d', $max + 1);
    }

    /**
     * Candidates joined with company name + balance rollups, optionally
     * restricted to a transaction-date window.
     * Filters: company_id, employment_status, search (name/email), from_date, to_date.
     */
    public static function withBalances(array $filters = [], string $sort = 'balance_desc'): array
    {
        $joinDate = '';
        $params = [];
        if (!empty($filters['from_date'])) {
            $joinDate .= ' AND t.transaction_date >= :fd';
            $params['fd'] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $joinDate .= ' AND t.transaction_date <= :td';
            $params['td'] = $filters['to_date'];
        }
        $sql = "SELECT c.id AS candidate_id, c.candidate_id AS candidate_code,
                    c.first_name || ' ' || c.last_name AS full_name,
                    c.email, c.phone, c.company_id, c.employment_status, c.date_registered, c.notes,
                    comp.company_name, comp.id AS comp_id,
                    COALESCE(SUM(CASE WHEN t.type = 'Earnings' THEN t.effective_amount END), 0) AS total_earnings,
                    COALESCE(SUM(CASE WHEN t.type = 'Company Payment' THEN t.effective_amount END), 0) AS total_company_payments,
                    COALESCE(SUM(CASE WHEN t.type = 'Candidate Payment' THEN t.effective_amount END), 0) AS total_candidate_payments,
                    COALESCE(SUM(CASE WHEN t.type = 'Expense' THEN t.effective_amount END), 0) AS total_expenses,
                    COALESCE(SUM(t.signed_amount), 0) AS current_balance,
                    CASE
                        WHEN COALESCE(SUM(t.signed_amount), 0) > 0 THEN 'Company owes candidate'
                        WHEN COALESCE(SUM(t.signed_amount), 0) < 0 THEN 'Candidate owes company'
                        ELSE 'Settled'
                    END AS status
                FROM candidates c
                JOIN companies comp ON comp.id = c.company_id
                LEFT JOIN transactions t ON t.candidate_id = c.id AND t.status IN ('approved', 'locked'){$joinDate}
                WHERE 1=1";
        if (!empty($filters['company_id'])) {
            $sql .= ' AND c.company_id = :cid';
            $params['cid'] = $filters['company_id'];
        }
        if (!empty($filters['employment_status'])) {
            $sql .= ' AND c.employment_status = :es';
            $params['es'] = $filters['employment_status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (c.first_name || ' ' || c.last_name ILIKE :q OR c.email ILIKE :q)";
            $params['q'] = '%' . $filters['search'] . '%';
        }
        $sql .= ' GROUP BY c.id, comp.id, comp.company_name';
        $sql .= match ($sort) {
            'balance_asc' => ' ORDER BY current_balance ASC',
            'name'        => ' ORDER BY full_name ASC',
            default       => ' ORDER BY current_balance DESC',
        };
        return Database::all($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT c.*, comp.company_name FROM candidates c
             JOIN companies comp ON comp.id = c.company_id
             WHERE c.id = :id',
            ['id' => $id]
        );
    }

    /** Balance rollup row from the view for one candidate (all time). */
    public static function balances(int $id): ?array
    {
        return Database::one('SELECT * FROM v_candidate_balances WHERE candidate_id = :id', ['id' => $id]);
    }

    /**
     * Balance rollup for one candidate restricted to a date window and/or
     * a single project. Same keys as the view row.
     */
    public static function balancesFor(int $id, ?string $from = null, ?string $to = null, ?int $projectId = null): array
    {
        $where = " WHERE candidate_id = :c AND status IN ('approved', 'locked')";
        $params = ['c' => $id];
        if ($from)      { $where .= ' AND transaction_date >= :fd'; $params['fd'] = $from; }
        if ($to)        { $where .= ' AND transaction_date <= :td'; $params['td'] = $to; }
        if ($projectId) { $where .= ' AND project_id = :p';         $params['p'] = $projectId; }
        $row = Database::one(
            "SELECT
                COALESCE(SUM(CASE WHEN type = 'Earnings' THEN effective_amount END), 0) AS total_earnings,
                COALESCE(SUM(CASE WHEN type = 'Company Payment' THEN effective_amount END), 0) AS total_company_payments,
                COALESCE(SUM(CASE WHEN type = 'Candidate Payment' THEN effective_amount END), 0) AS total_candidate_payments,
                COALESCE(SUM(CASE WHEN type = 'Expense' THEN effective_amount END), 0) AS total_expenses,
                COALESCE(SUM(signed_amount), 0) AS current_balance
             FROM transactions" . $where,
            $params
        ) ?: [];
        $bal = (float) ($row['current_balance'] ?? 0);
        $row['status'] = $bal > 0 ? 'Company owes candidate' : ($bal < 0 ? 'Candidate owes company' : 'Settled');
        return $row;
    }

    public static function options(?int $companyId = null): array
    {
        $sql = 'SELECT id, candidate_id, first_name || \' \' || last_name AS full_name FROM candidates';
        $params = [];
        if ($companyId) {
            $sql .= ' WHERE company_id = :c';
            $params['c'] = $companyId;
        }
        $sql .= ' ORDER BY first_name, last_name';
        return Database::all($sql, $params);
    }

    public static function countByCompany(int $companyId): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM candidates WHERE company_id = :c', ['c' => $companyId]);
    }

    public static function create(array $d): int
    {
        Database::begin();
        try {
            $code = self::nextCode();
            Database::q(
                'INSERT INTO candidates (candidate_id, first_name, last_name, email, phone,
                    company_id, employment_status, date_registered, notes)
                 VALUES (:code, :fn, :ln, :em, :ph, :cid, :es, :dr, :notes)',
                [
                    'code' => $code, 'fn' => $d['first_name'], 'ln' => $d['last_name'],
                    'em' => strtolower($d['email']), 'ph' => $d['phone'], 'cid' => $d['company_id'],
                    'es' => $d['employment_status'], 'dr' => $d['date_registered'] ?: date('Y-m-d'),
                    'notes' => $d['notes'],
                ]
            );
            $id = (int) Database::scalar('SELECT id FROM candidates WHERE candidate_id = :c', ['c' => $code]);
            Database::commit();
            return $id;
        } catch (Throwable $ex) {
            Database::rollBack();
            throw $ex;
        }
    }

    /** Enable/disable portal access and optionally set a new portal password. */
    public static function setPortalAccess(int $id, bool $enabled, ?string $newPassword = null): void
    {
        Database::q(
            'UPDATE candidates SET portal_enabled = :en, updated_at = NOW() WHERE id = :id',
            ['en' => $enabled ? 'true' : 'false', 'id' => $id]
        );
        if ($newPassword !== null && $newPassword !== '') {
            Database::q(
                'UPDATE candidates SET portal_password_hash = :h WHERE id = :id',
                ['h' => password_hash($newPassword, PASSWORD_BCRYPT), 'id' => $id]
            );
        }
    }

    public static function update(int $id, array $d): void
    {
        Database::q(
            'UPDATE candidates SET first_name = :fn, last_name = :ln, email = :em, phone = :ph,
                company_id = :cid, employment_status = :es, date_registered = :dr, notes = :notes,
                updated_at = NOW()
             WHERE id = :id',
            [
                'fn' => $d['first_name'], 'ln' => $d['last_name'], 'em' => strtolower($d['email']),
                'ph' => $d['phone'], 'cid' => $d['company_id'], 'es' => $d['employment_status'],
                'dr' => $d['date_registered'] ?: date('Y-m-d'), 'notes' => $d['notes'], 'id' => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::q('DELETE FROM candidates WHERE id = :id', ['id' => $id]);
    }
}
