<?php

class CompaniesController
{
    public function index(): void
    {
        Auth::requireLogin();
        render('companies/index', [
            'title'     => 'Companies',
            'companies' => Company::all(query('q')),
            'search'    => query('q'),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        render('companies/form', [
            'title'   => 'Add Company',
            'company' => null,
            'errors'  => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verify();
        [$data, $errors] = $this->validated();
        if ($errors) {
            render('companies/form', ['title' => 'Add Company', 'company' => $data, 'errors' => $errors]);
            return;
        }
        try {
            $id = Company::create($data);
        } catch (PDOException $ex) {
            $errors[] = str_contains($ex->getMessage(), 'company_name')
                ? 'A company with that name already exists.'
                : 'Could not save the company: ' . $ex->getMessage();
            render('companies/form', ['title' => 'Add Company', 'company' => $data, 'errors' => $errors]);
            return;
        }
        $upErrors = Attachments::store('company', $id, $_FILES['attachments'] ?? []);
        foreach ($upErrors as $e) { flash('error', $e); }
        flash('success', 'Company created.');
        redirect('/companies/' . $id);
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $company = Company::find((int) $id) or $this->notFound();
        render('companies/show', [
            'title'       => $company['company_name'],
            'company'     => $company,
            'aggregates'  => Company::aggregates((int) $id),
            'candidates'  => Candidate::withBalances(['company_id' => (int) $id], 'name'),
            'attachments' => Attachments::forEntity('company', (int) $id),
        ]);
    }

    public function edit(string $id): void
    {
        Auth::requireLogin();
        $company = Company::find((int) $id) or $this->notFound();
        render('companies/form', ['title' => 'Edit Company', 'company' => $company, 'errors' => []]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $company = Company::find((int) $id) or $this->notFound();
        [$data, $errors] = $this->validated();
        if ($errors) {
            $data['id'] = $company['id'];
            $data['company_id'] = $company['company_id'];
            render('companies/form', ['title' => 'Edit Company', 'company' => $data, 'errors' => $errors]);
            return;
        }
        Company::update((int) $id, $data);
        $upErrors = Attachments::store('company', (int) $id, $_FILES['attachments'] ?? []);
        foreach ($upErrors as $e) { flash('error', $e); }
        flash('success', 'Company updated.');
        redirect('/companies/' . $id);
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $count = Candidate::countByCompany((int) $id);
        if ($count > 0) {
            flash('error', "Cannot delete: this company has {$count} candidate" . ($count === 1 ? '' : 's') . ". Delete or reassign them first.");
            redirect('/companies');
        }
        Company::delete((int) $id);
        flash('success', 'Company deleted.');
        redirect('/companies');
    }

    /** AJAX: candidates of a company (for cascading filters). */
    public function candidatesJson(string $id): void
    {
        Auth::requireLogin();
        $rows = Candidate::options((int) $id);
        json_response(array_map(
            fn($r) => ['id' => (int) $r['id'], 'label' => $r['full_name'] . ' (' . $r['candidate_id'] . ')'],
            $rows
        ));
    }

    private function validated(): array
    {
        $data = [
            'company_name'          => post('company_name'),
            'address'               => post('address'),
            'primary_contact_name'  => post('primary_contact_name'),
            'primary_contact_email' => post('primary_contact_email'),
            'primary_contact_phone' => post('primary_contact_phone'),
            'company_type'          => post('company_type'),
            'date_added'            => post('date_added'),
            'notes'                 => post('notes'),
        ];
        $errors = [];
        if (!$data['company_name']) {
            $errors[] = 'Company name is required.';
        }
        if ($data['primary_contact_email'] && !filter_var($data['primary_contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Primary contact email is not a valid email address.';
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
