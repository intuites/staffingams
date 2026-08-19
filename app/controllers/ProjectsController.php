<?php

class ProjectsController
{
    public function index(): void
    {
        Auth::requireLogin();
        $filters = [
            'candidate_id'        => query('candidate'),
            'staffing_partner_id' => query('partner'),
            'from_date'           => query('from_date'),
            'to_date'             => query('to_date'),
        ];
        render('projects/index', [
            'title'      => 'Projects',
            'projects'   => Project::all($filters),
            'candidates' => Candidate::options(),
            'partners'   => StaffingPartner::options(),
            'filters'    => $filters,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        render('projects/form', [
            'title'      => 'Add Project',
            'project'    => ['candidate_id' => query('candidate'), 'staffing_partner_id' => query('partner')],
            'candidates' => Candidate::options(),
            'partners'   => StaffingPartner::options(),
            'errors'     => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verify();
        [$data, $errors] = $this->validated();
        if ($errors) {
            render('projects/form', [
                'title' => 'Add Project', 'project' => $data,
                'candidates' => Candidate::options(), 'partners' => StaffingPartner::options(), 'errors' => $errors,
            ]);
            return;
        }
        $id = Project::create($data);
        $upErrors = Attachments::store('project', $id, $_FILES['attachments'] ?? []);
        foreach ($upErrors as $e) { flash('error', $e); }
        flash('success', 'Project created.');
        redirect('/candidates/' . $data['candidate_id']);
    }

    public function edit(string $id): void
    {
        Auth::requireLogin();
        $project = Project::find((int) $id) or $this->notFound();
        render('projects/form', [
            'title' => 'Edit Project', 'project' => $project,
            'candidates' => Candidate::options(), 'partners' => StaffingPartner::options(), 'errors' => [],
            'attachments' => Attachments::forEntity('project', (int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $project = Project::find((int) $id) or $this->notFound();
        [$data, $errors] = $this->validated();
        if ($errors) {
            $data['id'] = $project['id'];
            $data['project_id'] = $project['project_id'];
            render('projects/form', [
                'title' => 'Edit Project', 'project' => $data,
                'candidates' => Candidate::options(), 'partners' => StaffingPartner::options(), 'errors' => $errors,
            ]);
            return;
        }
        Project::update((int) $id, $data);
        $upErrors = Attachments::store('project', (int) $id, $_FILES['attachments'] ?? []);
        foreach ($upErrors as $e) { flash('error', $e); }
        flash('success', 'Project updated.');
        redirect('/projects');
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $txn = Project::countTransactions((int) $id);
        if ($txn > 0) {
            flash('error', "Cannot delete: this project has {$txn} transaction" . ($txn === 1 ? '' : 's') . " linked to it. Delete those first.");
            redirect('/projects');
        }
        Project::delete((int) $id);
        flash('success', 'Project deleted.');
        redirect('/projects');
    }

    private function validated(): array
    {
        $data = [
            'candidate_id'               => post('candidate_id'),
            'staffing_partner_id'        => post('staffing_partner_id'),
            'project_name'               => post('project_name'),
            'start_date'                 => post('start_date'),
            'end_date'                   => post('end_date'),
            'rate_from_client'           => post_num('rate_from_client'),
            'rate_informed_to_candidate' => post_num('rate_informed_to_candidate'),
            'percent_paid_to_candidate'  => post_num('percent_paid_to_candidate'),
            'final_rate_override'        => post_num('final_rate_override'),
            'notes'                      => post('notes'),
        ];
        $errors = [];
        if (!$data['candidate_id']) $errors[] = 'Candidate is required.';
        if (!$data['staffing_partner_id']) $errors[] = 'Staffing partner is required.';
        if (!$data['project_name']) $errors[] = 'Project name is required.';
        if (!$data['start_date'])   $errors[] = 'Start date is required.';
        if ($data['rate_from_client'] === null || $data['rate_from_client'] < 0) $errors[] = 'Rate from client is required.';
        if ($data['rate_informed_to_candidate'] === null || $data['rate_informed_to_candidate'] < 0) $errors[] = 'Rate informed to candidate is required.';
        if ($data['percent_paid_to_candidate'] === null) {
            $errors[] = '% paid to candidate is required.';
        } else {
            // Accept either 0-1 fraction or 0-100 percentage input.
            if ($data['percent_paid_to_candidate'] > 1) {
                $data['percent_paid_to_candidate'] = $data['percent_paid_to_candidate'] / 100;
            }
            if ($data['percent_paid_to_candidate'] < 0 || $data['percent_paid_to_candidate'] > 1) {
                $errors[] = '% paid to candidate must be between 0 and 1 (or 0-100).';
            }
        }
        if ($data['end_date'] && $data['start_date'] && $data['end_date'] < $data['start_date']) {
            $errors[] = 'End date cannot be before the start date.';
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
