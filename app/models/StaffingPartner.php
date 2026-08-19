<?php

/**
 * Staffing partner — the client/vendor/partner organization where a candidate
 * works on a project. Distinct from Company (the staffing organization that
 * runs the candidate's payroll).
 */
class StaffingPartner
{
    public const TYPES = ['Client', 'Vendor', 'Partner', 'Other'];

    public static function nextCode(): string
    {
        $max = (int) Database::scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(partner_id FROM 6) AS INTEGER)), 0) FROM staffing_partners"
        );
        return sprintf('PART-%04d', $max + 1);
    }

    public static function all(?string $search = null): array
    {
        $sql = 'SELECT sp.*,
                    (SELECT COUNT(*) FROM projects p WHERE p.staffing_partner_id = sp.id) AS project_count
                FROM staffing_partners sp';
        $params = [];
        if ($search) {
            $sql .= ' WHERE sp.partner_name ILIKE :s';
            $params['s'] = "%{$search}%";
        }
        $sql .= ' ORDER BY sp.partner_name';
        return Database::all($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM staffing_partners WHERE id = :id', ['id' => $id]);
    }

    public static function options(): array
    {
        return Database::all('SELECT id, partner_id, partner_name, partner_type FROM staffing_partners ORDER BY partner_name');
    }

    public static function create(array $d): int
    {
        Database::begin();
        try {
            $code = self::nextCode();
            Database::q(
                'INSERT INTO staffing_partners (partner_id, partner_name, partner_type, address,
                    primary_contact_name, primary_contact_email, primary_contact_phone, date_added, notes)
                 VALUES (:code, :name, :type, :addr, :cn, :ce, :cp, :da, :notes)',
                [
                    'code' => $code, 'name' => $d['partner_name'], 'type' => $d['partner_type'],
                    'addr' => $d['address'], 'cn' => $d['primary_contact_name'],
                    'ce' => $d['primary_contact_email'], 'cp' => $d['primary_contact_phone'],
                    'da' => $d['date_added'] ?: date('Y-m-d'), 'notes' => $d['notes'],
                ]
            );
            $id = (int) Database::scalar('SELECT id FROM staffing_partners WHERE partner_id = :c', ['c' => $code]);
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
            'UPDATE staffing_partners SET partner_name = :name, partner_type = :type, address = :addr,
                primary_contact_name = :cn, primary_contact_email = :ce, primary_contact_phone = :cp,
                date_added = :da, notes = :notes, updated_at = NOW()
             WHERE id = :id',
            [
                'name' => $d['partner_name'], 'type' => $d['partner_type'], 'addr' => $d['address'],
                'cn' => $d['primary_contact_name'], 'ce' => $d['primary_contact_email'],
                'cp' => $d['primary_contact_phone'], 'da' => $d['date_added'] ?: date('Y-m-d'),
                'notes' => $d['notes'], 'id' => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::q('DELETE FROM staffing_partners WHERE id = :id', ['id' => $id]);
    }

    public static function countProjects(int $id): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM projects WHERE staffing_partner_id = :p', ['p' => $id]);
    }

    /** Projects at this partner, with candidate + payroll company. */
    public static function projects(int $id): array
    {
        return Database::all(
            'SELECT p.*, c.first_name || \' \' || c.last_name AS candidate_name, c.id AS cand_id,
                    comp.company_name, comp.id AS comp_id
             FROM projects p
             JOIN candidates c ON c.id = p.candidate_id
             JOIN companies comp ON comp.id = c.company_id
             WHERE p.staffing_partner_id = :id
             ORDER BY p.start_date DESC',
            ['id' => $id]
        );
    }

    /** Financial aggregates across all this partner's projects. */
    public static function aggregates(int $id): array
    {
        return Database::one(
            "SELECT
                COALESCE(SUM(CASE WHEN t.type = 'Earnings' THEN t.effective_amount END), 0) AS total_earnings,
                COALESCE(SUM(CASE WHEN t.type = 'Company Payment' THEN t.effective_amount END), 0) AS total_company_payments,
                COUNT(DISTINCT p.id) AS project_count,
                COUNT(DISTINCT p.candidate_id) AS candidate_count
             FROM projects p
             LEFT JOIN transactions t ON t.project_id = p.id AND t.status IN ('approved', 'locked')
             WHERE p.staffing_partner_id = :id",
            ['id' => $id]
        ) ?? [];
    }
}
