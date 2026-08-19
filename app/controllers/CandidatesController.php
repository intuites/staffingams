<?php

class CandidatesController
{
    public function index(): void
    {
        Auth::requireLogin();
        $filters = [
            'company_id'        => query('company'),
            'employment_status' => query('status'),
            'search'            => query('q'),
        ];
        $sort = query('sort') ?? 'balance_desc';
        render('candidates/index', [
            'title'      => 'Candidates',
            'candidates' => Candidate::withBalances($filters, $sort),
            'companies'  => Company::options(),
            'statuses'   => dropdown('employment_status'),
            'filters'    => $filters,
            'sort'       => $sort,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        render('candidates/form', [
            'title'     => 'Add Candidate',
            'candidate' => ['company_id' => query('company')],
            'companies' => Company::options(),
            'statuses'  => dropdown('employment_status'),
            'errors'    => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verify();
        [$data, $errors] = $this->validated();
        if ($errors) {
            render('candidates/form', [
                'title' => 'Add Candidate', 'candidate' => $data,
                'companies' => Company::options(), 'statuses' => dropdown('employment_status'),
                'errors' => $errors,
            ]);
            return;
        }
        try {
            $id = Candidate::create($data);
            if (post('portal_enabled') !== null || post('portal_password') !== null) {
                Candidate::setPortalAccess($id, post('portal_enabled') !== null, post('portal_password'));
            }
        } catch (PDOException $ex) {
            $errors[] = str_contains($ex->getMessage(), 'email')
                ? 'A candidate with that email already exists.'
                : 'Could not save the candidate: ' . $ex->getMessage();
            render('candidates/form', [
                'title' => 'Add Candidate', 'candidate' => $data,
                'companies' => Company::options(), 'statuses' => dropdown('employment_status'),
                'errors' => $errors,
            ]);
            return;
        }
        $upErrors = Attachments::store('candidate', $id, $_FILES['attachments'] ?? []);
        foreach ($upErrors as $e) { flash('error', $e); }
        flash('success', 'Candidate created.');
        redirect('/candidates/' . $id);
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $candidate = Candidate::find((int) $id) or $this->notFound();
        render('candidates/show', [
            'title'        => $candidate['first_name'] . ' ' . $candidate['last_name'],
            'candidate'    => $candidate,
            'balances'     => Candidate::balances((int) $id),
            'projects'     => Project::byCandidate((int) $id),
            'projectSummary' => Project::financialSummaryByCandidate((int) $id),
            'transactions' => Transaction::filtered(['candidate_id' => (int) $id, 'limit' => 10]),
            'attachments'  => Attachments::forEntity('candidate', (int) $id),
        ]);
    }

    /** Per-type transaction list with filters + totals. */
    public function transactions(string $id): void
    {
        Auth::requireLogin();
        $candidate = Candidate::find((int) $id) or $this->notFound();
        $filters = [
            'candidate_id' => (int) $id,
            'type'         => query('type'),
            'project_id'   => query('project_id'),
            'from_date'    => query('from_date'),
            'to_date'      => query('to_date'),
        ];
        render('candidates/transactions', [
            'title'        => 'Transactions — ' . $candidate['first_name'] . ' ' . $candidate['last_name'],
            'candidate'    => $candidate,
            'transactions' => Transaction::filtered($filters),
            'total'        => Transaction::filteredTotal($filters),
            'projects'     => Project::byCandidate((int) $id),
            'filters'      => $filters,
        ]);
    }

    public function edit(string $id): void
    {
        Auth::requireLogin();
        $candidate = Candidate::find((int) $id) or $this->notFound();
        render('candidates/form', [
            'title' => 'Edit Candidate', 'candidate' => $candidate,
            'companies' => Company::options(), 'statuses' => dropdown('employment_status'),
            'errors' => [],
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $candidate = Candidate::find((int) $id) or $this->notFound();
        [$data, $errors] = $this->validated();
        if ($errors) {
            $data['id'] = $candidate['id'];
            $data['candidate_id'] = $candidate['candidate_id'];
            render('candidates/form', [
                'title' => 'Edit Candidate', 'candidate' => $data,
                'companies' => Company::options(), 'statuses' => dropdown('employment_status'),
                'errors' => $errors,
            ]);
            return;
        }
        Candidate::update((int) $id, $data);
        Candidate::setPortalAccess((int) $id, post('portal_enabled') !== null, post('portal_password'));
        $upErrors = Attachments::store('candidate', (int) $id, $_FILES['attachments'] ?? []);
        foreach ($upErrors as $e) { flash('error', $e); }
        flash('success', 'Candidate updated.');
        redirect('/candidates/' . $id);
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $txn = Transaction::countByCandidate((int) $id);
        $proj = Project::countByCandidate((int) $id);
        if ($txn > 0 || $proj > 0) {
            flash('error', "Cannot delete: this candidate has {$txn} transaction" . ($txn === 1 ? '' : 's')
                . " and {$proj} project" . ($proj === 1 ? '' : 's') . ". Delete those first.");
            redirect('/candidates');
        }
        Candidate::delete((int) $id);
        flash('success', 'Candidate deleted.');
        redirect('/candidates');
    }

    /** AJAX: this candidate's projects (for transaction form). */
    public function projectsJson(string $id): void
    {
        Auth::requireLogin();
        json_response(array_map(
            fn($p) => ['id' => (int) $p['id'], 'label' => $p['project_name'] . ' (' . $p['project_id'] . ')'],
            Project::byCandidate((int) $id)
        ));
    }

    /** AJAX: distinct transaction types this candidate has records in. */
    public function typesJson(string $id): void
    {
        Auth::requireLogin();
        json_response(array_map(
            fn($t) => ['id' => $t, 'label' => $t],
            Transaction::typesForCandidate((int) $id)
        ));
    }

    private function validated(): array
    {
        $data = [
            'first_name'        => post('first_name'),
            'last_name'         => post('last_name'),
            'email'             => post('email'),
            'phone'             => post('phone'),
            'company_id'        => post('company_id'),
            'employment_status' => post('employment_status'),
            'date_registered'   => post('date_registered'),
            'notes'             => post('notes'),
        ];
        $errors = [];
        foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email', 'phone' => 'Phone', 'company_id' => 'Company', 'employment_status' => 'Employment status'] as $k => $label) {
            if (!$data[$k]) {
                $errors[] = "$label is required.";
            }
        }
        if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email is not a valid email address.';
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
