<?php

class Transaction
{
    public const TYPES = ['Earnings', 'Company Payment', 'Candidate Payment', 'Expense'];

    public static function nextCode(): string
    {
        $max = (int) Database::scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(transaction_id FROM 5) AS INTEGER)), 0) FROM transactions"
        );
        return sprintf('TXN-%05d', $max + 1);
    }

    /**
     * Business computation per spec 6.2. Mutates and returns $d with:
     * direction, transaction_date (synced for Earnings), auto_calculated_amount,
     * effective_amount, signed_amount.
     */
    public static function compute(array $d): array
    {
        $type = $d['type'];
        $d['direction'] = in_array($type, ['Earnings', 'Candidate Payment'], true) ? '+' : '-';

        if ($type === 'Earnings' && !empty($d['period_end_date'])) {
            $d['transaction_date'] = $d['period_end_date'];
        }

        $d['auto_calculated_amount'] = round(
            (float) ($d['hours_worked'] ?? 0) * (float) ($d['rate_applied'] ?? 0), 2
        );

        $d['effective_amount'] = match ($type) {
            'Earnings' => (!empty($d['amount_override']) && (float) $d['amount_override'] > 0)
                ? (float) $d['amount_override']
                : $d['auto_calculated_amount'],
            'Company Payment'   => (float) ($d['payment_amount'] ?? 0),
            'Expense'           => (float) ($d['expense_amount'] ?? 0),
            'Candidate Payment' => (float) ($d['candidate_payment_amount'] ?? 0),
        };

        $d['signed_amount'] = $d['direction'] === '+'
            ? $d['effective_amount']
            : -1 * $d['effective_amount'];

        return $d;
    }

    private const COLS = [
        'candidate_id', 'type', 'direction', 'transaction_date', 'project_id',
        'effective_amount', 'signed_amount', 'amount_notes', 'description_notes',
        'period_start_date', 'period_end_date', 'hours_worked', 'rate_applied',
        'auto_calculated_amount', 'amount_override',
        'payment_method', 'reference_number', 'period_covered', 'payment_amount',
        'expense_type', 'paid_to_vendor', 'reimbursable_by_candidate', 'expense_amount',
        'reason_for_payment', 'method_received', 'reference', 'candidate_payment_amount',
    ];

    public static function create(array $d): int
    {
        $d = self::compute($d);
        Database::begin();
        try {
            $code = self::nextCode();
            $cols = ['transaction_id'];
            $params = ['transaction_id' => $code];
            foreach (self::COLS as $c) {
                $cols[] = $c;
                $params[$c] = self::normalize($c, $d[$c] ?? null);
            }
            // Approval workflow: super admin entries are approved immediately;
            // regular admin entries start pending and are excluded from balances.
            $cols[] = 'status';
            $params['status'] = $d['_status'] ?? 'pending';
            if ($params['status'] === 'approved') {
                $cols[] = 'approved_by';
                $params['approved_by'] = $d['_actor'] ?? null;
                $cols[] = 'approved_at';
                $params['approved_at'] = date('c');
            }
            $sql = 'INSERT INTO transactions (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')';
            Database::q($sql, $params);
            $id = (int) Database::scalar('SELECT id FROM transactions WHERE transaction_id = :c', ['c' => $code]);
            Database::commit();
            return $id;
        } catch (Throwable $ex) {
            Database::rollBack();
            throw $ex;
        }
    }

    public static function update(int $id, array $d): void
    {
        $d = self::compute($d);
        $sets = [];
        $params = ['id' => $id];
        foreach (self::COLS as $c) {
            $sets[] = "$c = :$c";
            $params[$c] = self::normalize($c, $d[$c] ?? null);
        }
        // An edit by a regular admin sends the transaction back to pending
        // review; a super admin's edit keeps/sets it approved.
        if (!empty($d['_status'])) {
            $sets[] = 'status = :status';
            $params['status'] = $d['_status'];
        }
        $sql = 'UPDATE transactions SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id';
        Database::q($sql, $params);
    }

    public static function approve(int $id, int $adminId): void
    {
        Database::q(
            "UPDATE transactions SET status = 'approved', approved_by = :a, approved_at = NOW(), updated_at = NOW()
             WHERE id = :id AND status IN ('pending', 'rejected')",
            ['a' => $adminId, 'id' => $id]
        );
    }

    public static function lock(int $id, int $adminId): void
    {
        Database::q(
            "UPDATE transactions SET status = 'locked', locked_by = :a, locked_at = NOW(),
                    approved_by = COALESCE(approved_by, :a), approved_at = COALESCE(approved_at, NOW()), updated_at = NOW()
             WHERE id = :id AND status IN ('pending','approved')",
            ['a' => $adminId, 'id' => $id]
        );
    }

    public static function reject(int $id, int $adminId, ?string $reason): void
    {
        Database::q(
            "UPDATE transactions SET status = 'rejected', rejected_by = :a, rejected_at = NOW(),
                    rejection_reason = :r, updated_at = NOW()
             WHERE id = :id AND status = 'pending'",
            ['a' => $adminId, 'r' => $reason, 'id' => $id]
        );
    }

    /** Rejected transactions awaiting an admin's correction, oldest first. */
    public static function rejected(): array
    {
        return Database::all(
            "SELECT t.*, c.first_name || ' ' || c.last_name AS candidate_name, p.project_name,
                    a.name AS rejected_by_name
             FROM transactions t
             JOIN candidates c ON c.id = t.candidate_id
             LEFT JOIN projects p ON p.id = t.project_id
             LEFT JOIN admin_users a ON a.id = t.rejected_by
             WHERE t.status = 'rejected'
             ORDER BY t.rejected_at ASC"
        );
    }

    public static function rejectedCount(): int
    {
        return (int) Database::scalar("SELECT COUNT(*) FROM transactions WHERE status = 'rejected'");
    }

    public static function unlock(int $id): void
    {
        Database::q(
            "UPDATE transactions SET status = 'approved', locked_by = NULL, locked_at = NULL, updated_at = NOW()
             WHERE id = :id AND status = 'locked'",
            ['id' => $id]
        );
    }

    /** All pending transactions, oldest first (for the Approvals queue). */
    public static function pending(): array
    {
        return Database::all(
            "SELECT t.*, c.first_name || ' ' || c.last_name AS candidate_name, p.project_name
             FROM transactions t
             JOIN candidates c ON c.id = t.candidate_id
             LEFT JOIN projects p ON p.id = t.project_id
             WHERE t.status = 'pending'
             ORDER BY t.created_at ASC"
        );
    }

    public static function pendingCount(): int
    {
        return (int) Database::scalar("SELECT COUNT(*) FROM transactions WHERE status = 'pending'");
    }

    private static function normalize(string $col, mixed $v): mixed
    {
        if ($col === 'reimbursable_by_candidate') {
            return $v ? 'true' : 'false';
        }
        return $v === '' ? null : $v;
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT t.*, c.first_name || \' \' || c.last_name AS candidate_name, c.company_id AS cand_company_id,
                    p.project_name
             FROM transactions t
             JOIN candidates c ON c.id = t.candidate_id
             LEFT JOIN projects p ON p.id = t.project_id
             WHERE t.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Filtered transaction list.
     * Filters: candidate_id, type, project_id, from_date, to_date, limit.
     */
    public static function filtered(array $f = []): array
    {
        [$where, $params] = self::whereClause($f);
        $sql = 'SELECT t.*, c.first_name || \' \' || c.last_name AS candidate_name,
                       p.project_name, p.project_id AS project_code
                FROM transactions t
                JOIN candidates c ON c.id = t.candidate_id
                LEFT JOIN projects p ON p.id = t.project_id
                ' . $where . '
                ORDER BY t.transaction_date DESC, t.id DESC';
        if (!empty($f['limit'])) {
            $sql .= ' LIMIT ' . (int) $f['limit'];
        }
        return Database::all($sql, $params);
    }

    /** SUM(effective_amount) over the same filter set — pending rows excluded. */
    public static function filteredTotal(array $f = []): float
    {
        if (empty($f['statuses'])) {
            $f['statuses'] = ['approved', 'locked'];
        }
        [$where, $params] = self::whereClause($f);
        return (float) Database::scalar(
            'SELECT COALESCE(SUM(t.effective_amount), 0) FROM transactions t ' . $where,
            $params
        );
    }

    private static function whereClause(array $f): array
    {
        $where = ' WHERE 1=1';
        $params = [];
        if (!empty($f['candidate_id'])) {
            $where .= ' AND t.candidate_id = :cand';
            $params['cand'] = $f['candidate_id'];
        }
        if (!empty($f['type'])) {
            $where .= ' AND t.type = :type';
            $params['type'] = $f['type'];
        }
        if (!empty($f['project_id'])) {
            $where .= ' AND t.project_id = :proj';
            $params['proj'] = $f['project_id'];
        }
        if (!empty($f['from_date'])) {
            $where .= ' AND t.transaction_date >= :fd';
            $params['fd'] = $f['from_date'];
        }
        if (!empty($f['to_date'])) {
            $where .= ' AND t.transaction_date <= :td';
            $params['td'] = $f['to_date'];
        }
        if (!empty($f['statuses'])) {
            $in = [];
            foreach (array_values($f['statuses']) as $i => $st) {
                $in[] = ':st' . $i;
                $params['st' . $i] = $st;
            }
            $where .= ' AND t.status IN (' . implode(', ', $in) . ')';
        }
        return [$where, $params];
    }

    public static function countByCandidate(int $candidateId): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM transactions WHERE candidate_id = :c', ['c' => $candidateId]);
    }

    /** Distinct types this candidate has records in (for cascading type filter). */
    public static function typesForCandidate(int $candidateId): array
    {
        return array_column(
            Database::all('SELECT DISTINCT type FROM transactions WHERE candidate_id = :c ORDER BY type', ['c' => $candidateId]),
            'type'
        );
    }

    public static function delete(int $id): void
    {
        Database::q('DELETE FROM transactions WHERE id = :id', ['id' => $id]);
    }

    /** Six dashboard KPIs, optionally scoped to one payroll company and/or a date window. */
    public static function kpis(?int $companyId = null, ?string $from = null, ?string $to = null): array
    {
        $conds = [];
        $params = [];
        if ($companyId) {
            $conds[] = 't.candidate_id IN (SELECT id FROM candidates WHERE company_id = :co)';
            $params['co'] = $companyId;
        }
        if ($from) { $conds[] = 't.transaction_date >= :fd'; $params['fd'] = $from; }
        if ($to)   { $conds[] = 't.transaction_date <= :td'; $params['td'] = $to; }
        $conds[] = "t.status IN ('approved', 'locked')";
        $where = ' WHERE ' . implode(' AND ', $conds);
        $row = Database::one(
            "SELECT
                COALESCE(SUM(CASE WHEN t.type = 'Earnings' THEN t.effective_amount END), 0) AS total_earnings,
                COALESCE(SUM(CASE WHEN t.type = 'Company Payment' THEN t.effective_amount END), 0) AS total_company_payments,
                COALESCE(SUM(CASE WHEN t.type = 'Candidate Payment' THEN t.effective_amount END), 0) AS total_candidate_payments,
                COALESCE(SUM(CASE WHEN t.type = 'Expense' THEN t.effective_amount END), 0) AS total_expenses
             FROM transactions t" . $where,
            $params
        );
        $active = (int) Database::scalar(
            "SELECT COUNT(*) FROM candidates WHERE employment_status = 'Active'"
            . ($companyId ? ' AND company_id = :co' : ''),
            $companyId ? ['co' => $companyId] : []
        );
        $net = ((float) $row['total_company_payments'] + (float) $row['total_expenses'])
             - ((float) $row['total_earnings'] + (float) $row['total_candidate_payments']);
        return [
            'active_candidates'        => $active,
            'total_earnings'           => (float) $row['total_earnings'],
            'total_company_payments'   => (float) $row['total_company_payments'],
            'total_candidate_payments' => (float) $row['total_candidate_payments'],
            'total_expenses'           => (float) $row['total_expenses'],
            'net_company_position'     => $net,
        ];
    }
}
