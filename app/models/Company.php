<?php

class Company
{
    /** Generate the next COMP-#### id. Caller should hold a transaction. */
    public static function nextCode(): string
    {
        $max = (int) Database::scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(company_id FROM 6) AS INTEGER)), 0) FROM companies"
        );
        return sprintf('COMP-%04d', $max + 1);
    }

    public static function all(?string $search = null): array
    {
        $sql = 'SELECT * FROM companies';
        $params = [];
        if ($search) {
            $sql .= ' WHERE company_name ILIKE :s';
            $params['s'] = "%{$search}%";
        }
        $sql .= ' ORDER BY company_name';
        return Database::all($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM companies WHERE id = :id', ['id' => $id]);
    }

    public static function options(): array
    {
        return Database::all('SELECT id, company_id, company_name FROM companies ORDER BY company_name');
    }

    public static function create(array $d): int
    {
        Database::begin();
        try {
            $code = self::nextCode();
            Database::q(
                'INSERT INTO companies (company_id, company_name, address, primary_contact_name,
                    primary_contact_email, primary_contact_phone, company_type, date_added, notes)
                 VALUES (:code, :name, :addr, :cn, :ce, :cp, :type, :da, :notes)',
                [
                    'code' => $code, 'name' => $d['company_name'], 'addr' => $d['address'],
                    'cn' => $d['primary_contact_name'], 'ce' => $d['primary_contact_email'],
                    'cp' => $d['primary_contact_phone'], 'type' => $d['company_type'],
                    'da' => $d['date_added'] ?: date('Y-m-d'), 'notes' => $d['notes'],
                ]
            );
            $id = (int) Database::scalar('SELECT id FROM companies WHERE company_id = :c', ['c' => $code]);
            Database::commit();
            return $id;
        } catch (Throwable $ex) {
            Database::rollBack();
            throw $ex;
        }
    }

    public static function update(int $id, array $d): void
    {
        Database::q(
            'UPDATE companies SET company_name = :name, address = :addr, primary_contact_name = :cn,
                primary_contact_email = :ce, primary_contact_phone = :cp, company_type = :type,
                date_added = :da, notes = :notes, updated_at = NOW()
             WHERE id = :id',
            [
                'name' => $d['company_name'], 'addr' => $d['address'],
                'cn' => $d['primary_contact_name'], 'ce' => $d['primary_contact_email'],
                'cp' => $d['primary_contact_phone'], 'type' => $d['company_type'],
                'da' => $d['date_added'] ?: date('Y-m-d'), 'notes' => $d['notes'], 'id' => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::q('DELETE FROM companies WHERE id = :id', ['id' => $id]);
    }

    /** Aggregate transaction totals across all candidates of this company. */
    public static function aggregates(int $id): array
    {
        return Database::one(
            "SELECT
                COALESCE(SUM(CASE WHEN t.type = 'Earnings' THEN t.effective_amount END), 0) AS total_earnings,
                COALESCE(SUM(CASE WHEN t.type = 'Company Payment' THEN t.effective_amount END), 0) AS total_company_payments,
                COALESCE(SUM(CASE WHEN t.type = 'Candidate Payment' THEN t.effective_amount END), 0) AS total_candidate_payments,
                COALESCE(SUM(CASE WHEN t.type = 'Expense' THEN t.effective_amount END), 0) AS total_expenses,
                COALESCE(SUM(t.signed_amount), 0) AS net_balance
             FROM candidates c
             LEFT JOIN transactions t ON t.candidate_id = c.id AND t.status IN ('approved', 'locked')
             WHERE c.company_id = :id",
            ['id' => $id]
        ) ?? [];
    }
}
