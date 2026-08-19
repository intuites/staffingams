<?php

class StaffingPartnersController
{
    public function index(): void
    {
        Auth::requireLogin();
        render('staffing_partners/index', [
            'title'    => 'Staffing Partners',
            'partners' => StaffingPartner::all(query('q')),
            'search'   => query('q'),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        render('staffing_partners/form', ['title' => 'Add Staffing Partner', 'partner' => null, 'errors' => []]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verify();
        [$data, $errors] = $this->validated();
        if ($errors) {
            render('staffing_partners/form', ['title' => 'Add Staffing Partner', 'partner' => $data, 'errors' => $errors]);
            return;
        }
        try {
            $id = StaffingPartner::create($data);
        } catch (PDOException $ex) {
            $errors[] = str_contains($ex->getMessage(), 'partner_name')
                ? 'A staffing partner with that name already exists.'
                : 'Could not save the staffing partner: ' . $ex->getMessage();
            render('staffing_partners/form', ['title' => 'Add Staffing Partner', 'partner' => $data, 'errors' => $errors]);
            return;
        }
        flash('success', 'Staffing partner created.');
        redirect('/partners/' . $id);
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $partner = StaffingPartner::find((int) $id) or $this->notFound();
        render('staffing_partners/show', [
            'title'      => $partner['partner_name'],
            'partner'    => $partner,
            'projects'   => StaffingPartner::projects((int) $id),
            'aggregates' => StaffingPartner::aggregates((int) $id),
        ]);
    }

    public function edit(string $id): void
    {
        Auth::requireLogin();
        $partner = StaffingPartner::find((int) $id) or $this->notFound();
        render('staffing_partners/form', ['title' => 'Edit Staffing Partner', 'partner' => $partner, 'errors' => []]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $partner = StaffingPartner::find((int) $id) or $this->notFound();
        [$data, $errors] = $this->validated();
        if ($errors) {
            $data['id'] = $partner['id'];
            $data['partner_id'] = $partner['partner_id'];
            render('staffing_partners/form', ['title' => 'Edit Staffing Partner', 'partner' => $data, 'errors' => $errors]);
            return;
        }
        StaffingPartner::update((int) $id, $data);
        flash('success', 'Staffing partner updated.');
        redirect('/partners/' . $id);
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $count = StaffingPartner::countProjects((int) $id);
        if ($count > 0) {
            flash('error', "Cannot delete: this staffing partner has {$count} project" . ($count === 1 ? '' : 's') . " linked to it. Reassign or delete those first.");
            redirect('/partners');
        }
        StaffingPartner::delete((int) $id);
        flash('success', 'Staffing partner deleted.');
        redirect('/partners');
    }

    private function validated(): array
    {
        $data = [
            'partner_name'          => post('partner_name'),
            'partner_type'          => post('partner_type'),
            'address'               => post('address'),
            'primary_contact_name'  => post('primary_contact_name'),
            'primary_contact_email' => post('primary_contact_email'),
            'primary_contact_phone' => post('primary_contact_phone'),
            'date_added'            => post('date_added'),
            'notes'                 => post('notes'),
        ];
        $errors = [];
        if (!$data['partner_name']) {
            $errors[] = 'Partner name is required.';
        }
        if ($data['partner_type'] && !in_array($data['partner_type'], StaffingPartner::TYPES, true)) {
            $errors[] = 'Unknown partner type.';
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
