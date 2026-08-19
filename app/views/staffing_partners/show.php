<?php $pid = (int) $partner['id']; ?>
<div class="page-head">
  <div>
    <div class="eyebrow"><?= e($partner['partner_id']) ?><?= $partner['partner_type'] ? ' · ' . e($partner['partner_type']) : '' ?></div>
    <h1><?= e($partner['partner_name']) ?></h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="/partners/<?= $pid ?>/edit">Edit</a>
    <button type="button" class="btn btn-danger"
      data-confirm-action="/partners/<?= $pid ?>/delete"
      data-confirm-msg="Delete staffing partner <?= e($partner['partner_name']) ?>? Partners with projects cannot be deleted.">Delete</button>
    <a class="btn btn-primary" href="/projects/create?partner=<?= $pid ?>">+ Add Project at this Partner</a>
  </div>
</div>

<div class="kpi-grid kpi-grid-4">
  <?php
  $cards = [
      ['Projects', number_format($aggregates['project_count'] ?? 0), '', '', null],
      ['Candidates Engaged', number_format($aggregates['candidate_count'] ?? 0), '', '', null],
      ['Total Earnings (via projects)', format_currency($aggregates['total_earnings'] ?? 0), 'kpi-earnings', '', null],
      ['Company Payments (via projects)', format_currency($aggregates['total_company_payments'] ?? 0), 'kpi-company', '', null],
  ];
  foreach ($cards as [$kpi_label, $kpi_value, $kpi_class, $kpi_tone, $kpi_href]) {
      include BASE_PATH . '/app/views/partials/_kpi_card.php';
  }
  ?>
</div>

<div class="detail-grid">
  <div class="card card-top">
    <h3>Partner Profile</h3>
    <dl class="dl">
      <?php if ($partner['address']): ?><dt>Address</dt><dd><?= nl2br(e($partner['address'])) ?></dd><?php endif; ?>
      <?php if ($partner['primary_contact_name']): ?><dt>Contact</dt><dd><?= e($partner['primary_contact_name']) ?></dd><?php endif; ?>
      <?php if ($partner['primary_contact_email']): ?><dt>Email</dt><dd><?= e($partner['primary_contact_email']) ?></dd><?php endif; ?>
      <?php if ($partner['primary_contact_phone']): ?><dt>Phone</dt><dd><?= e($partner['primary_contact_phone']) ?></dd><?php endif; ?>
      <dt>Date added</dt><dd><?= format_date($partner['date_added']) ?></dd>
      <?php if ($partner['notes']): ?><dt>Notes</dt><dd><?= nl2br(e($partner['notes'])) ?></dd><?php endif; ?>
    </dl>
  </div>

  <div>
    <div class="sec-title" style="margin-top:0">
      <h2>Projects at <?= e($partner['partner_name']) ?></h2>
    </div>
    <div class="table-wrap">
      <table class="jp-table">
        <thead><tr><th>ID</th><th>Project</th><th>Candidate</th><th>Payroll Company</th><th>Start</th><th>End</th><th class="num">Rate Paid</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php if (empty($projects)): ?>
          <tr><td colspan="8"><div class="empty-state">No projects at this partner yet.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($projects as $p): ?>
          <tr>
            <td class="nowrap"><?= e($p['project_id']) ?></td>
            <td><strong><?= e($p['project_name']) ?></strong></td>
            <td><a href="/candidates/<?= (int) $p['cand_id'] ?>"><?= e($p['candidate_name']) ?></a></td>
            <td><a href="/companies/<?= (int) $p['comp_id'] ?>"><?= e($p['company_name']) ?></a></td>
            <td class="nowrap"><?= format_date($p['start_date']) ?></td>
            <td class="nowrap"><?= $p['end_date'] ? format_date($p['end_date']) : '—' ?></td>
            <td class="num"><?= format_currency($p['rate_paid_to_candidate']) ?>/hr</td>
            <td>
              <div class="row-actions">
                <a class="btn btn-secondary btn-sm" href="/projects/<?= (int) $p['id'] ?>/edit">Edit</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
