<?php $coid = (int) $company['id']; ?>
<div class="page-head">
  <div>
    <div class="eyebrow"><?= e($company['company_id']) ?> · Payroll Organization</div>
    <h1><?= e($company['company_name']) ?></h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="/companies/<?= $coid ?>/edit">Edit</a>
    <button type="button" class="btn btn-danger"
      data-confirm-action="/companies/<?= $coid ?>/delete"
      data-confirm-msg="Delete company <?= e($company['company_name']) ?>? Companies with candidates cannot be deleted.">Delete</button>
    <a class="btn btn-primary" href="/candidates/create?company=<?= $coid ?>">+ Add Candidate to this Company</a>
  </div>
</div>

<div class="kpi-grid">
  <?php
  $net = (float) ($aggregates['net_balance'] ?? 0);
  $cards = [
      ['Candidates', number_format(count($candidates)), '', '', null],
      ['Total Earnings', format_currency($aggregates['total_earnings'] ?? 0), 'kpi-earnings', '', null],
      ['Company Payments', format_currency($aggregates['total_company_payments'] ?? 0), 'kpi-company', '', null],
      ['Candidate Payments', format_currency($aggregates['total_candidate_payments'] ?? 0), 'kpi-candidate', '', null],
      ['Expenses', format_currency($aggregates['total_expenses'] ?? 0), 'kpi-expense', '', null],
      ['Net Balance', format_currency($net), '', $net > 0 ? 'pos' : ($net < 0 ? 'neg' : ''), null],
  ];
  foreach ($cards as [$kpi_label, $kpi_value, $kpi_class, $kpi_tone, $kpi_href]) {
      include BASE_PATH . '/app/views/partials/_kpi_card.php';
  }
  ?>
</div>

<div class="detail-grid">
  <div class="card card-top">
    <h3>Company Profile</h3>
    <dl class="dl">
      <?php if ($company['address']): ?><dt>Address</dt><dd><?= nl2br(e($company['address'])) ?></dd><?php endif; ?>
      <?php if ($company['primary_contact_name']): ?><dt>Contact</dt><dd><?= e($company['primary_contact_name']) ?></dd><?php endif; ?>
      <?php if ($company['primary_contact_email']): ?><dt>Email</dt><dd><?= e($company['primary_contact_email']) ?></dd><?php endif; ?>
      <?php if ($company['primary_contact_phone']): ?><dt>Phone</dt><dd><?= e($company['primary_contact_phone']) ?></dd><?php endif; ?>
      <dt>Date added</dt><dd><?= format_date($company['date_added']) ?></dd>
      <?php if ($company['notes']): ?><dt>Notes</dt><dd><?= nl2br(e($company['notes'])) ?></dd><?php endif; ?>
    </dl>
    <div class="sec-title"><h3 class="mb-0">Attachments</h3></div>
    <?php $entity = 'company'; include BASE_PATH . '/app/views/partials/_attachments.php'; ?>
  </div>

  <div>
    <div class="sec-title" style="margin-top:0">
      <h2>Candidates at <?= e($company['company_name']) ?></h2>
    </div>
    <?php $showContact = false; $sort = 'name'; include BASE_PATH . '/app/views/partials/_candidates_table.php'; ?>
  </div>
</div>
