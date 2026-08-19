<?php

/**
 * Candidate self-service portal. Every data query is scoped to
 * PortalAuth::candidateId() — the session identity — never to a URL parameter.
 * Internal-only figures (client rate, % split, override) are never selected.
 */
class PortalController
{
    public function showLogin(): void
    {
        if (PortalAuth::check()) {
            redirect('/portal');
        }
        render('portal/login', ['title' => 'Candidate Sign in'], 'auth');
    }

    public function login(): void
    {
        Csrf::verify();
        if (!Turnstile::verify()) {
            flash('error', 'Human verification failed. Please try again.');
            redirect('/portal/login');
        }
        if (PortalAuth::tooManyAttempts()) {
            flash('error', 'Too many failed attempts. Please wait 15 minutes and try again.');
            redirect('/portal/login');
        }
        if (PortalAuth::attempt(post('email') ?? '', $_POST['password'] ?? '')) {
            redirect('/portal');
        }
        flash('error', 'Invalid email or password, or portal access is not enabled for your account.');
        redirect('/portal/login');
    }

    public function logout(): void
    {
        PortalAuth::logout();
        redirect('/portal/login');
    }

    /** Portal home — balance, per-project summary, recent transactions. */
    public function dashboard(): void
    {
        PortalAuth::requireLogin();
        $cid = PortalAuth::candidateId();
        $period = query('period') ?? 'all';
        [$from, $to, $periodLabel] = resolve_period($period, query('from_date'), query('to_date'));
        $projectId = query('project') ? (int) query('project') : null;

        render('portal/dashboard', [
            'title'          => 'My Dashboard',
            'candidate'      => Candidate::find($cid),
            'balances'       => Candidate::balancesFor($cid, $from, $to, $projectId),
            'projectSummary' => Project::financialSummaryByCandidate($cid, $from, $to, $projectId),
            'projects'       => self::safeProjects($cid),
            'transactions'   => Transaction::filtered([
                'candidate_id' => $cid, 'from_date' => $from, 'to_date' => $to,
                'project_id' => $projectId, 'limit' => 8, 'statuses' => ['approved', 'locked'],
            ]),
            'period'         => $period,
            'periodLabel'    => $periodLabel,
            'customFrom'     => query('from_date'),
            'customTo'       => query('to_date'),
            'projectId'      => $projectId,
        ], 'portal');
    }

    /** Full transaction ledger with filters + totals. */
    public function transactions(): void
    {
        PortalAuth::requireLogin();
        $cid = PortalAuth::candidateId();
        $period = query('period') ?? 'all';
        [$from, $to, $periodLabel] = resolve_period($period, query('from_date'), query('to_date'));
        $filters = [
            'candidate_id' => $cid,
            'type'         => query('type'),
            'project_id'   => query('project') ? (int) query('project') : null,
            'from_date'    => $from,
            'to_date'      => $to,
            'statuses'     => ['approved', 'locked'],
        ];
        render('portal/transactions', [
            'title'        => 'My Transactions',
            'candidate'    => Candidate::find($cid),
            'transactions' => Transaction::filtered($filters),
            'total'        => Transaction::filteredTotal($filters),
            'projects'     => self::safeProjects($cid),
            'openReviewTxnIds' => ReviewRequest::openTxnIdsForCandidate($cid),
            'myReviews'    => ReviewRequest::forCandidate($cid),
            'filters'      => $filters,
            'period'       => $period,
            'periodLabel'  => $periodLabel,
            'customFrom'   => query('from_date'),
            'customTo'     => query('to_date'),
        ], 'portal');
    }

    /** Review-request form for one of the candidate's OWN transactions. */
    public function reviewForm(string $id): void
    {
        PortalAuth::requireLogin();
        $cid = PortalAuth::candidateId();
        $txn = Database::one(
            'SELECT t.*, p.project_name FROM transactions t
             LEFT JOIN projects p ON p.id = t.project_id
             WHERE t.id = :id AND t.candidate_id = :c',
            ['id' => (int) $id, 'c' => $cid]
        );
        if (!$txn) {
            http_response_code(404);
            exit('Not found');
        }
        render('portal/review', [
            'title' => 'Request Review — ' . $txn['transaction_id'],
            'txn'   => $txn,
            'candidate' => Candidate::find($cid),
        ], 'portal');
    }

    public function reviewSubmit(string $id): void
    {
        PortalAuth::requireLogin();
        Csrf::verify();
        $cid = PortalAuth::candidateId();
        $txn = Database::one(
            'SELECT * FROM transactions WHERE id = :id AND candidate_id = :c',
            ['id' => (int) $id, 'c' => $cid]
        );
        if (!$txn) {
            http_response_code(404);
            exit('Not found');
        }
        $comment = post('comment');
        if (!$comment) {
            flash('error', 'Please describe the discrepancy you see.');
            redirect('/portal/review/' . (int) $id);
        }
        if (in_array((int) $id, ReviewRequest::openTxnIdsForCandidate($cid), true)) {
            flash('error', 'A review request for this transaction is already open.');
            redirect('/portal/transactions');
        }
        $locked = $txn['status'] === 'locked';
        ReviewRequest::create((int) $id, $cid, $comment, $locked);
        $candidate = Candidate::find($cid);
        // Locked transactions can only be edited by a super admin → route there.
        Notification::queue($locked ? 'super_admin' : 'admin', 'review_request', sprintf(
            '<strong>%s</strong> flagged %s (%s, %s)%s — “%s”',
            e($candidate['first_name'] . ' ' . $candidate['last_name']), e($txn['transaction_id']),
            e($txn['type']), format_currency($txn['effective_amount']),
            $locked ? ' <strong>[locked — super admin]</strong>' : '',
            e(mb_strimwidth($comment, 0, 160, '…'))
        ));
        flash('success', 'Review request sent. Our team will look into it and get back to you.');
        redirect('/portal/transactions');
    }

    /** CSV export of the candidate's own (filtered) transactions. */
    public function exportCsv(): void
    {
        PortalAuth::requireLogin();
        $cid = PortalAuth::candidateId();
        [$from, $to] = resolve_period(query('period') ?? 'all', query('from_date'), query('to_date'));
        $filters = [
            'candidate_id' => $cid,
            'type'         => query('type'),
            'project_id'   => query('project') ? (int) query('project') : null,
            'from_date'    => $from,
            'to_date'      => $to,
            'statuses'     => ['approved', 'locked'],
        ];
        $rows = Transaction::filtered($filters);
        $total = Transaction::filteredTotal($filters);

        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="my_transactions_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Transaction ID', 'Type', 'Direction', 'Amount', 'Amount Notes', 'Project', 'Description'], ',', '"', '\\');
        foreach ($rows as $t) {
            fputcsv($out, [
                format_date($t['transaction_date']), $t['transaction_id'], $t['type'], $t['direction'],
                (float) $t['effective_amount'], $t['amount_notes'], $t['project_name'], $t['description_notes'],
            ], ',', '"', '\\');
        }
        fputcsv($out, ['TOTAL', '', '', '', $total, '', '', ''], ',', '"', '\\');
        fclose($out);
        exit;
    }

    /** Projects with only candidate-safe columns (no client rate / % split). */
    private static function safeProjects(int $cid): array
    {
        return Database::all(
            'SELECT p.id, p.project_id, p.project_name, p.start_date, p.end_date,
                    p.rate_paid_to_candidate, sp.partner_name
             FROM projects p
             LEFT JOIN staffing_partners sp ON sp.id = p.staffing_partner_id
             WHERE p.candidate_id = :c
             ORDER BY p.start_date DESC',
            ['c' => $cid]
        );
    }
}
