<?php
/**
 * Full candidate financial body — balance banner, KPI cards, profile,
 * per-project financial summary, projects, recent transactions.
 * Expects: $candidate, $balances, $projects, $projectSummary, $transactions, $attachments.
 * Used by candidates/show.php and dashboard/candidate.php.
 */
$bal = (float) ($balances['current_balance'] ?? 0);
$cid = (int) $candidate['id'];
$drillQs = $drillQs ?? '';           // &from_date=..&to_date=..&project_id=.. (type-card drills)
$dateQs  = $dateQs ?? '';            // &from_date=..&to_date=..              (per-project drills)
$periodLabel = $periodLabel ?? 'All time';
$scopeNote = $periodLabel !== 'All time' ? ' · ' . $periodLabel : '';
?>
<!-- Balance summary -->
<div class="balance-banner">
  <div>
    <div class="l"><?= $periodLabel !== 'All time' ? 'Balance for period' : 'Current Balance' ?> — <?= e($candidate['first_name'] . ' ' . $candidate['last_name']) ?> (<?= e($candidate['candidate_id']) ?>)<?= e($scopeNote) ?></div>
    <div class="n <?= $bal > 0 ? 'pos' : ($bal < 0 ? 'neg' : '') ?>"><?= format_currency($bal) ?></div>
  </div>
  <div class="status"><?= e($balances['status'] ?? 'Settled') ?></div>
</div>

<div class="kpi-grid kpi-grid-4">
  <?php
  $cards = [
      ['Total Earnings', format_currency($balances['total_earnings'] ?? 0), 'kpi-earnings', '', "/candidates/{$cid}/transactions?type=" . urlencode('Earnings') . $drillQs],
      ['Company Payments', format_currency($balances['total_company_payments'] ?? 0), 'kpi-company', '', "/candidates/{$cid}/transactions?type=" . urlencode('Company Payment') . $drillQs],
      ['Candidate Payments', format_currency($balances['total_candidate_payments'] ?? 0), 'kpi-candidate', '', "/candidates/{$cid}/transactions?type=" . urlencode('Candidate Payment') . $drillQs],
      ['Total Expenses', format_currency($balances['total_expenses'] ?? 0), 'kpi-expense', '', "/candidates/{$cid}/transactions?type=" . urlencode('Expense') . $drillQs],
  ];
  foreach ($cards as [$kpi_label, $kpi_value, $kpi_class, $kpi_tone, $kpi_href]) {
      $kpi_sub = 'View transactions →';
      include BASE_PATH . '/app/views/partials/_kpi_card.php';
  }
  ?>
</div>

<div class="detail-grid">
  <!-- Profile card -->
  <div class="card card-top">
    <h3>Profile</h3>
    <dl class="dl">
      <dt>Email</dt><dd><?= e($candidate['email']) ?></dd>
      <dt>Phone</dt><dd><?= e($candidate['phone']) ?></dd>
      <dt>Payroll Co.</dt><dd><a href="/companies/<?= (int) $candidate['company_id'] ?>"><?= e($candidate['company_name']) ?></a></dd>
      <dt>Status</dt><dd><span class="pill <?= $candidate['employment_status'] === 'Active' ? 'pill-green' : 'pill-grey' ?>"><?= e($candidate['employment_status']) ?></span></dd>
      <dt>Registered</dt><dd><?= format_date($candidate['date_registered']) ?></dd>
      <dt>Portal</dt><dd><?php $pe = !empty($candidate['portal_enabled']) && $candidate['portal_enabled'] !== 'f'; ?>
        <span class="pill <?= $pe ? 'pill-green' : 'pill-grey' ?>"><?= $pe ? 'Enabled' : 'Disabled' ?></span>
        <?php if ($pe && !empty($candidate['portal_last_login_at'])): ?><span class="muted small">last sign-in <?= format_date($candidate['portal_last_login_at']) ?></span><?php endif; ?>
      </dd>
      <?php if ($candidate['notes']): ?><dt>Notes</dt><dd><?= nl2br(e($candidate['notes'])) ?></dd><?php endif; ?>
    </dl>
    <div class="sec-title"><h3 class="mb-0">Attachments</h3></div>
    <?php $entity = 'candidate'; include BASE_PATH . '/app/views/partials/_attachments.php'; ?>
  </div>

  <div>
    <!-- Financial summary by project -->
    <div class="sec-title" style="margin-top:0">
      <h2>Financial Summary by Project<?= e($scopeNote) ?></h2>
    </div>
    <div class="table-wrap">
      <table class="jp-table">
        <thead><tr><th>Project</th><th>Staffing Partner</th><th class="num">Earnings</th><th class="num">Company Payments</th><th class="num">Candidate Payments</th><th class="num">Expenses</th><th class="num">Balance</th><th class="num">Txns</th></tr></thead>
        <tbody>
        <?php if (empty($projectSummary['projects']) && empty($projectSummary['unassigned'])): ?>
          <tr><td colspan="8"><div class="empty-state">No projects or transactions yet.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($projectSummary['projects'] as $ps): $b = (float) $ps['balance']; ?>
          <tr>
            <td><a href="/candidates/<?= $cid ?>/transactions?project_id=<?= (int) $ps['id'] . $dateQs ?>"><strong><?= e($ps['project_name']) ?></strong></a> <span class="muted small"><?= e($ps['project_id']) ?></span></td>
            <td><?= !empty($ps['partner_name']) ? e($ps['partner_name']) : '—' ?></td>
            <td class="num"><?= format_currency($ps['earnings']) ?></td>
            <td class="num"><?= format_currency($ps['company_payments']) ?></td>
            <td class="num"><?= format_currency($ps['candidate_payments']) ?></td>
            <td class="num"><?= format_currency($ps['expenses']) ?></td>
            <td class="num"><span class="amount <?= $b > 0 ? 'pos' : ($b < 0 ? 'neg' : '') ?>"><?= format_currency($b) ?></span></td>
            <td class="num"><a href="/candidates/<?= $cid ?>/transactions?project_id=<?= (int) $ps['id'] . $dateQs ?>"><?= (int) $ps['txn_count'] ?></a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!empty($projectSummary['unassigned'])): $u = $projectSummary['unassigned']; $b = (float) $u['balance']; ?>
          <tr>
            <td><a href="/candidates/<?= $cid ?>/transactions"><em>Not linked to a project</em></a> <span class="muted small">expenses, general payments</span></td>
            <td>—</td>
            <td class="num"><?= format_currency($u['earnings']) ?></td>
            <td class="num"><?= format_currency($u['company_payments']) ?></td>
            <td class="num"><?= format_currency($u['candidate_payments']) ?></td>
            <td class="num"><?= format_currency($u['expenses']) ?></td>
            <td class="num"><span class="amount <?= $b > 0 ? 'pos' : ($b < 0 ? 'neg' : '') ?>"><?= format_currency($b) ?></span></td>
            <td class="num"><?= (int) $u['txn_count'] ?></td>
          </tr>
        <?php endif; ?>
        <?php if (!empty($projectSummary['projects']) || !empty($projectSummary['unassigned'])): ?>
          <tr class="total-row">
            <td colspan="2">OVERALL<?= $scopeNote ? e($scopeNote) : ' (all projects + unassigned)' ?></td>
            <td class="num"><?= format_currency($balances['total_earnings'] ?? 0) ?></td>
            <td class="num"><?= format_currency($balances['total_company_payments'] ?? 0) ?></td>
            <td class="num"><?= format_currency($balances['total_candidate_payments'] ?? 0) ?></td>
            <td class="num"><?= format_currency($balances['total_expenses'] ?? 0) ?></td>
            <td class="num"><?= format_currency($balances['current_balance'] ?? 0) ?></td>
            <td></td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Projects -->
    <div class="sec-title">
      <h2>Projects</h2>
      <a class="btn btn-gradient btn-sm" href="/projects/create?candidate=<?= $cid ?>">+ Add Project</a>
    </div>
    <div class="table-wrap">
      <table class="jp-table">
        <thead><tr><th>ID</th><th>Project</th><th>Staffing Partner</th><th>Start</th><th>End</th><th class="num">Rate Paid</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php if (empty($projects)): ?>
          <tr><td colspan="7"><div class="empty-state">No projects yet.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($projects as $p): ?>
          <tr>
            <td class="nowrap"><?= e($p['project_id']) ?></td>
            <td><strong><?= e($p['project_name']) ?></strong></td>
            <td><?= !empty($p['partner_name']) ? e($p['partner_name']) : '—' ?></td>
            <td class="nowrap"><?= format_date($p['start_date']) ?></td>
            <td class="nowrap"><?= $p['end_date'] ? format_date($p['end_date']) : '—' ?></td>
            <td class="num"><?= format_currency($p['rate_paid_to_candidate']) ?>/hr</td>
            <td>
              <div class="row-actions">
                <a class="btn btn-secondary btn-sm" href="/projects/<?= (int) $p['id'] ?>/edit">Edit</a>
                <button type="button" class="btn btn-danger btn-sm"
                  data-confirm-action="/projects/<?= (int) $p['id'] ?>/delete"
                  data-confirm-msg="Delete project <?= e($p['project_name']) ?>?">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Recent transactions -->
    <div class="sec-title">
      <h2>Recent Transactions<?= e($scopeNote) ?></h2>
      <div>
        <a class="btn btn-secondary btn-sm" href="/candidates/<?= $cid ?>/transactions<?= $drillQs ? '?' . ltrim($drillQs, '&') : '' ?>">View all</a>
        <a class="btn btn-gradient btn-sm" href="/transactions/create?candidate=<?= $cid ?>">+ Add Transaction</a>
      </div>
    </div>
    <?php include BASE_PATH . '/app/views/partials/_txn_table.php'; ?>
  </div>
</div>
