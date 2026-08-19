<?php

class TransactionsController
{
    /** Global transactions with cascading company → candidate → type filters. */
    public function index(): void
    {
        Auth::requireLogin();
        $companyId   = query('company');
        $candidateId = query('candidate');
        $type        = query('type');

        $filters = [
            'candidate_id' => $candidateId,
            'type'         => $type,
            'from_date'    => query('from_date'),
            'to_date'      => query('to_date'),
        ];

        $transactions = $candidateId ? Transaction::filtered($filters) : [];
        $total        = $candidateId ? Transaction::filteredTotal($filters) : 0.0;

        render('transactions/index', [
            'title'          => 'Transactions',
            'transactions'   => $transactions,
            'total'          => $total,
            'companies'      => Company::options(),
            'candidates'     => $companyId ? Candidate::options((int) $companyId) : [],
            'types'          => $candidateId ? Transaction::typesForCandidate((int) $candidateId) : [],
            'companyId'      => $companyId,
            'candidateId'    => $candidateId,
            'filters'        => $filters,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        $candidateId = query('candidate');
        render('transactions/form', [
            'title'       => 'Add Transaction',
            'txn'         => ['candidate_id' => $candidateId, 'type' => query('type') ?? 'Earnings'],
            'candidates'  => Candidate::options(),
            'projects'    => $candidateId ? Project::byCandidate((int) $candidateId) : [],
            'errors'      => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verify();
        [$data, $errors] = $this->validated();
        if ($errors) {
            render('transactions/form', [
                'title' => 'Add Transaction', 'txn' => $data,
                'candidates' => Candidate::options(),
                'projects' => !empty($data['candidate_id']) ? Project::byCandidate((int) $data['candidate_id']) : [],
                'errors' => $errors,
            ]);
            return;
        }
        $data['_status'] = Auth::isSuper() ? 'approved' : 'pending';
        $data['_actor'] = Auth::user()['id'];
        $id = Transaction::create($data);
        $upErrors = Attachments::store('transaction', $id, $_FILES['attachments'] ?? []);
        foreach ($upErrors as $e) { flash('error', $e); }
        if (!Auth::isSuper()) {
            $txn = Transaction::find($id);
            Notification::queue('super_admin', 'txn_pending', sprintf(
                '<strong>%s</strong>: %s of <strong>%s</strong> for %s (dated %s) entered by %s',
                e($txn['transaction_id']), e($txn['type']), format_currency($txn['effective_amount']),
                e($txn['candidate_name']), format_date($txn['transaction_date']), e(Auth::user()['name'])
            ));
        }
        flash('success', Auth::isSuper()
            ? 'Transaction recorded and approved.'
            : 'Transaction recorded — pending super admin approval. It will not affect balances until approved.');
        redirect('/candidates/' . $data['candidate_id'] . '/transactions');
    }

    public function edit(string $id): void
    {
        Auth::requireLogin();
        $txn = Transaction::find((int) $id) or $this->notFound();
        if ($txn['status'] === 'locked' && !Auth::isSuper()) {
            flash('error', 'Transaction ' . $txn['transaction_id'] . ' is locked — only a super admin can edit it.');
            redirect('/candidates/' . $txn['candidate_id'] . '/transactions');
        }
        render('transactions/form', [
            'title'      => 'Edit Transaction ' . $txn['transaction_id'],
            'txn'        => $txn,
            'candidates' => Candidate::options(),
            'projects'   => Project::byCandidate((int) $txn['candidate_id']),
            'errors'     => [],
            'attachments' => Attachments::forEntity('transaction', (int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $txn = Transaction::find((int) $id) or $this->notFound();
        if ($txn['status'] === 'locked' && !Auth::isSuper()) {
            flash('error', 'Transaction ' . $txn['transaction_id'] . ' is locked — only a super admin can edit it.');
            redirect('/candidates/' . $txn['candidate_id'] . '/transactions');
        }
        [$data, $errors] = $this->validated();
        if ($errors) {
            $data['id'] = $txn['id'];
            $data['transaction_id'] = $txn['transaction_id'];
            render('transactions/form', [
                'title' => 'Edit Transaction ' . $txn['transaction_id'], 'txn' => $data,
                'candidates' => Candidate::options(),
                'projects' => !empty($data['candidate_id']) ? Project::byCandidate((int) $data['candidate_id']) : [],
                'errors' => $errors,
            ]);
            return;
        }
        // Super admin edits keep a locked txn locked; admin edits go back to pending.
        $data['_status'] = Auth::isSuper() ? ($txn['status'] === 'locked' ? 'locked' : 'approved') : 'pending';
        Transaction::update((int) $id, $data);
        $upErrors = Attachments::store('transaction', (int) $id, $_FILES['attachments'] ?? []);
        foreach ($upErrors as $e) { flash('error', $e); }
        if (!Auth::isSuper()) {
            $t2 = Transaction::find((int) $id);
            Notification::queue('super_admin', 'txn_edited', sprintf(
                '<strong>%s</strong>: %s now <strong>%s</strong> for %s, edited by %s — back to pending',
                e($t2['transaction_id']), e($t2['type']), format_currency($t2['effective_amount']),
                e($t2['candidate_name']), e(Auth::user()['name'])
            ));
        }
        flash('success', Auth::isSuper()
            ? 'Transaction updated.'
            : 'Transaction updated — back to pending until a super admin re-approves it.');
        redirect('/candidates/' . $data['candidate_id'] . '/transactions');
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $txn = Transaction::find((int) $id) or $this->notFound();
        if ($txn['status'] === 'locked' && !Auth::isSuper()) {
            flash('error', 'Transaction ' . $txn['transaction_id'] . ' is locked — only a super admin can delete it.');
            redirect('/candidates/' . $txn['candidate_id'] . '/transactions');
        }
        Transaction::delete((int) $id);
        flash('success', 'Transaction ' . $txn['transaction_id'] . ' deleted.');
        redirect('/candidates/' . $txn['candidate_id'] . '/transactions');
    }

    /** Approvals queue — super admin only. */
    public function approvals(): void
    {
        Auth::requireSuper();
        render('transactions/approvals', [
            'title'   => 'Pending Approvals',
            'pending' => Transaction::pending(),
        ]);
    }

    public function approve(string $id): void
    {
        Auth::requireSuper();
        Csrf::verify();
        $txn = Transaction::find((int) $id) or $this->notFound();
        Transaction::approve((int) $id, Auth::user()['id']);
        flash('success', 'Transaction ' . $txn['transaction_id'] . ' approved — it now counts toward balances.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/approvals');
    }

    public function rejectTxn(string $id): void
    {
        Auth::requireSuper();
        Csrf::verify();
        $txn = Transaction::find((int) $id) or $this->notFound();
        $reason = post('rejection_reason');
        Transaction::reject((int) $id, Auth::user()['id'], $reason);
        Notification::queue('admin', 'txn_rejected', sprintf(
            '<strong>%s</strong>: %s of <strong>%s</strong> for %s rejected by %s%s',
            e($txn['transaction_id']), e($txn['type']), format_currency($txn['effective_amount']),
            e($txn['candidate_name']), e(Auth::user()['name']),
            $reason ? ' — “' . e($reason) . '”' : ''
        ));
        flash('success', 'Transaction ' . $txn['transaction_id'] . ' rejected — sent back to the admins for correction.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/approvals');
    }

    /** Rejected transactions awaiting correction (visible to both roles). */
    public function rejected(): void
    {
        Auth::requireLogin();
        render('transactions/rejected', [
            'title'    => 'Rejected Transactions',
            'rejected' => Transaction::rejected(),
        ]);
    }

    public function lockTxn(string $id): void
    {
        Auth::requireSuper();
        Csrf::verify();
        $txn = Transaction::find((int) $id) or $this->notFound();
        Transaction::lock((int) $id, Auth::user()['id']);
        flash('success', 'Transaction ' . $txn['transaction_id'] . ' locked — it is now final and cannot be edited.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/approvals');
    }

    public function unlockTxn(string $id): void
    {
        Auth::requireSuper();
        Csrf::verify();
        $txn = Transaction::find((int) $id) or $this->notFound();
        Transaction::unlock((int) $id);
        flash('success', 'Transaction ' . $txn['transaction_id'] . ' unlocked — back to approved (editable).');
        redirect($_SERVER['HTTP_REFERER'] ?? '/approvals');
    }

    /** Candidate review-comment queue. Admins see unlocked requests; super admins see all. */
    public function reviews(): void
    {
        Auth::requireLogin();
        render('transactions/reviews', [
            'title'   => 'Review Candidate Comments',
            'reviews' => ReviewRequest::open(Auth::isSuper()),
        ]);
    }

    public function resolveReview(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $req = ReviewRequest::find((int) $id) or $this->notFound();
        $requiresSuper = $req['requires_super'] && $req['requires_super'] !== 'f';
        if ($requiresSuper && !Auth::isSuper()) {
            flash('error', 'That review concerns a locked transaction — only a super admin can resolve it.');
            redirect('/reviews');
        }
        ReviewRequest::resolve((int) $id, Auth::user()['id'], post('admin_response'));
        flash('success', 'Review request for ' . $req['txn_code'] . ' marked as resolved.');
        redirect('/reviews');
    }

    /**
     * Validation with per-type required fields (spec 10.10).
     */
    private function validated(): array
    {
        $type = post('type');
        $data = [
            'candidate_id'      => post('candidate_id'),
            'type'              => $type,
            'transaction_date'  => post('transaction_date'),
            'project_id'        => post('project_id'),
            'amount_notes'      => post('amount_notes'),
            'description_notes' => post('description_notes'),
            // Earnings
            'period_start_date' => post('period_start_date'),
            'period_end_date'   => post('period_end_date'),
            'hours_worked'      => post_num('hours_worked'),
            'rate_applied'      => post_num('rate_applied'),
            'amount_override'   => post_num('amount_override'),
            // Company Payment
            'payment_method'    => post('payment_method'),
            'reference_number'  => post('reference_number'),
            'period_covered'    => post('period_covered'),
            'payment_amount'    => post_num('payment_amount'),
            // Expense
            'expense_type'              => post('expense_type'),
            'paid_to_vendor'            => post('paid_to_vendor'),
            'reimbursable_by_candidate' => post('reimbursable_by_candidate') !== null,
            'expense_amount'            => post_num('expense_amount'),
            // Candidate Payment
            'reason_for_payment'        => post('reason_for_payment'),
            'method_received'           => post('method_received'),
            'reference'                 => post('reference'),
            'candidate_payment_amount'  => post_num('candidate_payment_amount'),
        ];

        $errors = [];
        if (!$data['candidate_id']) $errors[] = 'Candidate is required.';
        if (!in_array($type, Transaction::TYPES, true)) {
            $errors[] = 'Transaction type is required.';
            return [$data, $errors];
        }

        switch ($type) {
            case 'Earnings':
                if (!$data['project_id'])        $errors[] = 'Project is required for Earnings.';
                if (!$data['period_start_date']) $errors[] = 'Period start date is required for Earnings.';
                if (!$data['period_end_date'])   $errors[] = 'Period end date is required for Earnings.';
                if ($data['period_start_date'] && $data['period_end_date'] && $data['period_end_date'] < $data['period_start_date']) {
                    $errors[] = 'Period end date cannot be before the period start date.';
                }
                $hasCalc = $data['hours_worked'] !== null && $data['rate_applied'] !== null;
                $hasOverride = $data['amount_override'] !== null && $data['amount_override'] > 0;
                if (!$hasCalc && !$hasOverride) {
                    $errors[] = 'Enter hours worked and rate applied, or an amount override.';
                }
                break;
            case 'Company Payment':
                if (!$data['payment_method']) $errors[] = 'Payment method is required for a Company Payment.';
                if ($data['payment_amount'] === null || $data['payment_amount'] <= 0) $errors[] = 'Payment amount must be greater than zero.';
                if (!$data['transaction_date']) $errors[] = 'Transaction date is required.';
                break;
            case 'Expense':
                if (!$data['expense_type']) $errors[] = 'Expense type is required for an Expense.';
                if ($data['expense_amount'] === null || $data['expense_amount'] <= 0) $errors[] = 'Expense amount must be greater than zero.';
                if (!$data['transaction_date']) $errors[] = 'Transaction date is required.';
                $data['project_id'] = null; // Expenses carry no project
                break;
            case 'Candidate Payment':
                if (!$data['reason_for_payment']) $errors[] = 'Reason for payment is required for a Candidate Payment.';
                if (!$data['method_received'])    $errors[] = 'Method received is required for a Candidate Payment.';
                if ($data['candidate_payment_amount'] === null || $data['candidate_payment_amount'] <= 0) $errors[] = 'Amount received must be greater than zero.';
                if (!$data['transaction_date']) $errors[] = 'Transaction date is required.';
                break;
        }

        // Project (when set) must belong to the candidate.
        if ($data['project_id'] && $data['candidate_id']) {
            $owner = Database::scalar('SELECT candidate_id FROM projects WHERE id = :p', ['p' => $data['project_id']]);
            if ((int) $owner !== (int) $data['candidate_id']) {
                $errors[] = 'The selected project does not belong to the selected candidate.';
            }
        }

        return [$data, $errors];
    }

    private function notFound(): never
    {
        http_response_code(404);
        render('errors/404', ['title' => 'Not Found']);
        exit;
    }
}
