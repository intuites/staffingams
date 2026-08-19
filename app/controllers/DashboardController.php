<?php

class DashboardController
{
    /**
     * Company Dashboard (home) — overall summary of all candidates in the
     * selected payroll company, optionally restricted to a reporting period.
     */
    public function index(): void
    {
        Auth::requireLogin();
        $companies = Company::options();
        $companyId = query('company');
        $period    = query('period') ?? 'all';
        [$from, $to, $periodLabel] = resolve_period($period, query('from_date'), query('to_date'));

        $company = $companyId ? Company::find((int) $companyId) : null;
        $kpis = Transaction::kpis($company ? (int) $company['id'] : null, $from, $to);

        $filters = [
            'company_id'        => $company ? (int) $company['id'] : null,
            'employment_status' => query('status'),
            'from_date'         => $from,
            'to_date'           => $to,
        ];
        $sort = query('sort') ?? 'balance_desc';
        $candidates = Candidate::withBalances($filters, $sort);

        render('dashboard/index', [
            'title'       => 'Company Dashboard',
            'kpis'        => $kpis,
            'company'     => $company,
            'candidates'  => $candidates,
            'companies'   => $companies,
            'statuses'    => dropdown('employment_status'),
            'filters'     => $filters,
            'sort'        => $sort,
            'period'      => $period,
            'periodLabel' => $periodLabel,
            'customFrom'  => query('from_date'),
            'customTo'    => query('to_date'),
        ]);
    }

    /**
     * Candidate Dashboard — pick company → candidate, filter by period and
     * project; overall balance, per-project summary, drill-downs.
     */
    public function candidate(): void
    {
        Auth::requireLogin();
        $companies   = Company::options();
        $companyId   = query('company');
        $candidateId = query('candidate');
        $projectId   = query('project');
        $period      = query('period') ?? 'all';
        [$from, $to, $periodLabel] = resolve_period($period, query('from_date'), query('to_date'));

        $candidate = $candidateId ? Candidate::find((int) $candidateId) : null;
        if ($candidate) {
            $companyId = $candidate['company_id'];
        }

        $data = [
            'title'       => 'Candidate Dashboard',
            'companies'   => $companies,
            'companyId'   => $companyId,
            'candList'    => $companyId ? Candidate::options((int) $companyId) : [],
            'candidate'   => $candidate,
            'period'      => $period,
            'periodLabel' => $periodLabel,
            'customFrom'  => query('from_date'),
            'customTo'    => query('to_date'),
            'projectId'   => $projectId,
        ];
        if ($candidate) {
            $cid = (int) $candidate['id'];
            $pid = $projectId ? (int) $projectId : null;

            // Drill-down query-string suffix so every link keeps the filters.
            $dateQs = '';
            if ($from) { $dateQs .= '&from_date=' . urlencode($from); }
            if ($to)   { $dateQs .= '&to_date=' . urlencode($to); }
            $drillQs = $dateQs . ($pid ? '&project_id=' . $pid : '');

            $data += [
                'balances'       => Candidate::balancesFor($cid, $from, $to, $pid),
                'projects'       => Project::byCandidate($cid),
                'projectSummary' => Project::financialSummaryByCandidate($cid, $from, $to, $pid),
                'transactions'   => Transaction::filtered([
                    'candidate_id' => $cid, 'from_date' => $from, 'to_date' => $to,
                    'project_id' => $pid, 'limit' => 10,
                ]),
                'attachments'    => Attachments::forEntity('candidate', $cid),
                'drillQs'        => $drillQs,
                'dateQs'         => $dateQs,
                'projList'       => Project::byCandidate($cid),
            ];
        }
        render('dashboard/candidate', $data);
    }
}
