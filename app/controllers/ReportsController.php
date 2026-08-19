<?php

class ReportsController
{
    public function index(): void
    {
        redirect('/reports/per-project');
    }

    public function perProject(): void
    {
        Auth::requireLogin();
        $from = query('from_date');
        $to   = query('to_date');
        render('reports/per_project', [
            'title' => 'Report — Per Project',
            'rows'  => self::perProjectRows($from, $to),
            'from'  => $from,
            'to'    => $to,
        ]);
    }

    /**
     * Per-candidate report. Without a candidate selected: overview of all
     * candidates. With one selected: their full statement for the chosen
     * period — summary, per-project breakdown, and every transaction.
     */
    public function perCandidate(): void
    {
        Auth::requireLogin();
        $period = query('period') ?? 'all';
        [$from, $to, $periodLabel] = resolve_period($period, query('from_date'), query('to_date'));
        $candidateId = query('candidate') ? (int) query('candidate') : null;

        $data = [
            'title'       => 'Report — Per Candidate',
            'candList'    => Candidate::withBalances([], 'name'),
            'candidateId' => $candidateId,
            'period'      => $period,
            'periodLabel' => $periodLabel,
            'customFrom'  => query('from_date'),
            'customTo'    => query('to_date'),
            'from'        => $from,
            'to'          => $to,
        ];
        if ($candidateId && ($candidate = Candidate::find($candidateId))) {
            $filters = ['candidate_id' => $candidateId, 'from_date' => $from, 'to_date' => $to];
            $data += [
                'candidate'      => $candidate,
                'balances'       => Candidate::balancesFor($candidateId, $from, $to),
                'projectSummary' => Project::financialSummaryByCandidate($candidateId, $from, $to),
                'transactions'   => Transaction::filtered($filters),
                'total'          => Transaction::filteredTotal($filters),
            ];
        } else {
            $data['rows'] = self::perCandidateRows($from, $to);
        }
        render('reports/per_candidate', $data);
    }

    public function perCompany(): void
    {
        Auth::requireLogin();
        render('reports/per_company', [
            'title' => 'Report — Per Company',
            'rows'  => self::perCompanyRows(),
        ]);
    }

    public function perStatus(): void
    {
        Auth::requireLogin();
        render('reports/per_status', [
            'title' => 'Report — Per Employment Status',
            'rows'  => self::perStatusRows(),
        ]);
    }

    public static function perProjectRows(?string $from, ?string $to): array
    {
        $where = '';
        $params = [];
        if ($from) { $where .= ' AND t.transaction_date >= :fd'; $params['fd'] = $from; }
        if ($to)   { $where .= ' AND t.transaction_date <= :td'; $params['td'] = $to; }
        return Database::all(
            "SELECT p.project_id, p.project_name,
                    sp.partner_name,
                    c.first_name || ' ' || c.last_name AS candidate_name,
                    COALESCE(SUM(CASE WHEN t.type = 'Earnings' THEN t.effective_amount END), 0) AS total_earnings,
                    COALESCE(SUM(CASE WHEN t.type = 'Company Payment' THEN t.effective_amount END), 0) AS total_company_payments,
                    COALESCE(SUM(CASE WHEN t.type = 'Earnings' THEN t.effective_amount END), 0)
                  - COALESCE(SUM(CASE WHEN t.type = 'Company Payment' THEN t.effective_amount END), 0) AS net
             FROM projects p
             JOIN candidates c ON c.id = p.candidate_id
             LEFT JOIN staffing_partners sp ON sp.id = p.staffing_partner_id
             LEFT JOIN transactions t ON t.project_id = p.id AND t.status IN ('approved', 'locked') {$where}
             GROUP BY p.id, p.project_id, p.project_name, sp.partner_name, c.first_name, c.last_name
             ORDER BY p.project_id",
            $params
        );
    }

    /** One row per candidate with full totals, optionally date-windowed. */
    public static function perCandidateRows(?string $from, ?string $to): array
    {
        return Candidate::withBalances(['from_date' => $from, 'to_date' => $to], 'name');
    }

    public static function perCompanyRows(): array
    {
        return Database::all(
            "SELECT comp.company_id, comp.company_name,
                    COUNT(DISTINCT c.id) AS candidate_count,
                    COALESCE(SUM(CASE WHEN t.type = 'Earnings' THEN t.effective_amount END), 0) AS total_earnings,
                    COALESCE(SUM(CASE WHEN t.type = 'Company Payment' THEN t.effective_amount END), 0) AS total_company_payments,
                    COALESCE(SUM(CASE WHEN t.type = 'Candidate Payment' THEN t.effective_amount END), 0) AS total_candidate_payments,
                    COALESCE(SUM(CASE WHEN t.type = 'Expense' THEN t.effective_amount END), 0) AS total_expenses,
                    COALESCE(SUM(t.signed_amount), 0) AS net_balance
             FROM companies comp
             LEFT JOIN candidates c ON c.company_id = comp.id
             LEFT JOIN transactions t ON t.candidate_id = c.id AND t.status IN ('approved', 'locked')
             GROUP BY comp.id, comp.company_id, comp.company_name
             ORDER BY comp.company_name"
        );
    }

    public static function perStatusRows(): array
    {
        return Database::all(
            "SELECT c.employment_status,
                    COUNT(DISTINCT c.id) AS candidate_count,
                    COALESCE(SUM(t.signed_amount), 0) AS aggregate_balance
             FROM candidates c
             LEFT JOIN transactions t ON t.candidate_id = c.id AND t.status IN ('approved', 'locked')
             GROUP BY c.employment_status
             ORDER BY c.employment_status"
        );
    }
}
