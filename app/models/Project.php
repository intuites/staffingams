<?php

class Project
{
    public static function nextCode(): string
    {
        $max = (int) Database::scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(project_id FROM 6) AS INTEGER)), 0) FROM projects"
        );
        return sprintf('PROJ-%04d', $max + 1);
    }

    /**
     * Rate computation per spec 6.1:
     *   auto = min(rate_from_client, rate_informed_to_candidate) * percent_paid
     *   final = override when set and > 0, else auto
     * Returns [$auto, $final].
     */
    public static function computeRates(float $rateFromClient, float $rateInformed, float $percent, ?float $override): array
    {
        $auto = round(min($rateFromClient, $rateInformed) * $percent, 2);
        $final = ($override !== null && $override > 0) ? $override : $auto;
        return [$auto, $final];
    }

    public static function all(array $filters = []): array
    {
        $sql = 'SELECT p.*, c.first_name || \' \' || c.last_name AS candidate_name, c.candidate_id AS candidate_code,
                       sp.partner_name, sp.id AS partner_pk
                FROM projects p
                JOIN candidates c ON c.id = p.candidate_id
                LEFT JOIN staffing_partners sp ON sp.id = p.staffing_partner_id
                WHERE 1=1';
        $params = [];
        if (!empty($filters['candidate_id'])) {
            $sql .= ' AND p.candidate_id = :cand';
            $params['cand'] = $filters['candidate_id'];
        }
        if (!empty($filters['staffing_partner_id'])) {
            $sql .= ' AND p.staffing_partner_id = :sp';
            $params['sp'] = $filters['staffing_partner_id'];
        }
        if (!empty($filters['from_date'])) {
            $sql .= ' AND p.start_date >= :fd';
            $params['fd'] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $sql .= ' AND p.start_date <= :td';
            $params['td'] = $filters['to_date'];
        }
        $sql .= ' ORDER BY p.start_date DESC, p.id DESC';
        return Database::all($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT p.*, c.first_name || \' \' || c.last_name AS candidate_name, sp.partner_name
             FROM projects p
             JOIN candidates c ON c.id = p.candidate_id
             LEFT JOIN staffing_partners sp ON sp.id = p.staffing_partner_id
             WHERE p.id = :id',
            ['id' => $id]
        );
    }

    public static function byCandidate(int $candidateId): array
    {
        return Database::all(
            'SELECT p.*, sp.partner_name FROM projects p
             LEFT JOIN staffing_partners sp ON sp.id = p.staffing_partner_id
             WHERE p.candidate_id = :c ORDER BY p.start_date DESC',
            ['c' => $candidateId]
        );
    }

    public static function countByCandidate(int $candidateId): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM projects WHERE candidate_id = :c', ['c' => $candidateId]);
    }

    public static function create(array $d): int
    {
        [$auto, $final] = self::computeRates(
            (float) $d['rate_from_client'],
            (float) $d['rate_informed_to_candidate'],
            (float) $d['percent_paid_to_candidate'],
            $d['final_rate_override'] !== null ? (float) $d['final_rate_override'] : null
        );
        Database::begin();
        try {
            $code = self::nextCode();
            Database::q(
                'INSERT INTO projects (project_id, candidate_id, staffing_partner_id, project_name, start_date, end_date,
                    rate_from_client, rate_informed_to_candidate, percent_paid_to_candidate,
                    auto_calculated_final_rate, final_rate_override, rate_paid_to_candidate, notes)
                 VALUES (:code, :cand, :sp, :name, :sd, :ed, :rc, :ri, :pct, :auto, :ovr, :final, :notes)',
                [
                    'code' => $code, 'cand' => $d['candidate_id'],
                    'sp' => $d['staffing_partner_id'] ?: null, 'name' => $d['project_name'],
                    'sd' => $d['start_date'], 'ed' => $d['end_date'],
                    'rc' => $d['rate_from_client'], 'ri' => $d['rate_informed_to_candidate'],
                    'pct' => $d['percent_paid_to_candidate'], 'auto' => $auto,
                    'ovr' => $d['final_rate_override'], 'final' => $final, 'notes' => $d['notes'],
                ]
            );
            $id = (int) Database::scalar('SELECT id FROM projects WHERE project_id = :c', ['c' => $code]);
            Database::commit();
            return $id;
        } catch (Throwable $ex) {
            Database::rollBack();
            throw $ex;
        }
    }

    public static function update(int $id, array $d): void
    {
        [$auto, $final] = self::computeRates(
            (float) $d['rate_from_client'],
            (float) $d['rate_informed_to_candidate'],
            (float) $d['percent_paid_to_candidate'],
            $d['final_rate_override'] !== null ? (float) $d['final_rate_override'] : null
        );
        Database::q(
            'UPDATE projects SET candidate_id = :cand, staffing_partner_id = :sp, project_name = :name,
                start_date = :sd, end_date = :ed,
                rate_from_client = :rc, rate_informed_to_candidate = :ri, percent_paid_to_candidate = :pct,
                auto_calculated_final_rate = :auto, final_rate_override = :ovr, rate_paid_to_candidate = :final,
                notes = :notes, updated_at = NOW()
             WHERE id = :id',
            [
                'cand' => $d['candidate_id'], 'sp' => $d['staffing_partner_id'] ?: null,
                'name' => $d['project_name'],
                'sd' => $d['start_date'], 'ed' => $d['end_date'],
                'rc' => $d['rate_from_client'], 'ri' => $d['rate_informed_to_candidate'],
                'pct' => $d['percent_paid_to_candidate'], 'auto' => $auto,
                'ovr' => $d['final_rate_override'], 'final' => $final, 'notes' => $d['notes'], 'id' => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::q('DELETE FROM projects WHERE id = :id', ['id' => $id]);
    }

    /**
     * Financial summary per project for one candidate: earnings, payments,
     * expenses and signed balance per project, plus a row for transactions
     * not linked to any project. ['projects' => rows, 'unassigned' => row|null]
     */
    public static function financialSummaryByCandidate(int $candidateId, ?string $from = null, ?string $to = null, ?int $projectId = null): array
    {
        $joinDate = '';
        $params = ['c' => $candidateId];
        if ($from) { $joinDate .= ' AND t.transaction_date >= :fd'; $params['fd'] = $from; }
        if ($to)   { $joinDate .= ' AND t.transaction_date <= :td'; $params['td'] = $to; }
        $projWhere = '';
        if ($projectId) {
            $projWhere = ' AND p.id = :pid';
            $params['pid'] = $projectId;
        }
        $projects = Database::all(
            "SELECT p.id, p.project_id, p.project_name, sp.partner_name,
                    COALESCE(SUM(CASE WHEN t.type = 'Earnings' THEN t.effective_amount END), 0) AS earnings,
                    COALESCE(SUM(CASE WHEN t.type = 'Company Payment' THEN t.effective_amount END), 0) AS company_payments,
                    COALESCE(SUM(CASE WHEN t.type = 'Candidate Payment' THEN t.effective_amount END), 0) AS candidate_payments,
                    COALESCE(SUM(CASE WHEN t.type = 'Expense' THEN t.effective_amount END), 0) AS expenses,
                    COALESCE(SUM(t.signed_amount), 0) AS balance,
                    COUNT(t.id) AS txn_count
             FROM projects p
             LEFT JOIN staffing_partners sp ON sp.id = p.staffing_partner_id
             LEFT JOIN transactions t ON t.project_id = p.id AND t.status IN ('approved', 'locked'){$joinDate}
             WHERE p.candidate_id = :c{$projWhere}
             GROUP BY p.id, p.project_id, p.project_name, sp.partner_name
             ORDER BY p.start_date DESC, p.id DESC",
            $params
        );
        // "Not linked to a project" row — hidden when a specific project is selected.
        $unassigned = null;
        if (!$projectId) {
            $uParams = ['c' => $candidateId];
            $uWhere = " WHERE candidate_id = :c AND project_id IS NULL AND status IN ('approved', 'locked')";
            if ($from) { $uWhere .= ' AND transaction_date >= :fd'; $uParams['fd'] = $from; }
            if ($to)   { $uWhere .= ' AND transaction_date <= :td'; $uParams['td'] = $to; }
            $unassigned = Database::one(
                "SELECT
                        COALESCE(SUM(CASE WHEN type = 'Earnings' THEN effective_amount END), 0) AS earnings,
                        COALESCE(SUM(CASE WHEN type = 'Company Payment' THEN effective_amount END), 0) AS company_payments,
                        COALESCE(SUM(CASE WHEN type = 'Candidate Payment' THEN effective_amount END), 0) AS candidate_payments,
                        COALESCE(SUM(CASE WHEN type = 'Expense' THEN effective_amount END), 0) AS expenses,
                        COALESCE(SUM(signed_amount), 0) AS balance,
                        COUNT(id) AS txn_count
                 FROM transactions" . $uWhere,
                $uParams
            );
            if ($unassigned && (int) $unassigned['txn_count'] === 0) {
                $unassigned = null;
            }
        }
        return ['projects' => $projects, 'unassigned' => $unassigned];
    }

    public static function countTransactions(int $id): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM transactions WHERE project_id = :p', ['p' => $id]);
    }
}
