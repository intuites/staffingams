<?php
$bal = (float) ($balances['current_balance'] ?? 0);
$scopeNote = ($periodLabel ?? 'All time') !== 'All time' ? ' · ' . $periodLabel : '';
$qs = '';
if (!empty($filtersQs)) { $qs = $filtersQs; }
?>
<div class="page-head">
  <div>
    <div class="eyebrow"><?= e($candidate['candidate_id']) ?></div>
    <h1>Welcome, <?= e($candidate['first_name']) ?></h1>
    <div class="sub">Your balance, projects and payments<?= e($scopeNote) ?>.</div>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="/portal/transactions">All my transactions</a>
  </div>
</div>

<div class="filter-bar">
  <form method="get" action="/portal">
    <div class="filter-grid">
      <div class="field">
        <label>Project</label>
        <select name="project" data-autosubmit>
          <option value="">All projects</option>
          <?php foreach ($projects as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === (string) ($projectId ?? '') ? 'selected' : '' ?>><?= e($p['project_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php include BASE_PATH . '/app/views/partials/_period_filter.php'; ?>
      <div class="filter-actions">
        <a class="btn btn-secondary" href="/portal">Reset</a>
      </div>
    </div>
  </form>
</div>

<div class="balance-banner">
  <div>
    <div class="l"><?= ($periodLabel ?? 'All time') !== 'All time' ? 'Balance for period' : 'Current Balance' ?><?= e($scopeNote) ?></div>
    <div class="n <?= $bal > 0 ? 'pos' : ($bal < 0 ? 'neg' : '') ?>"><?= format_currency($bal) ?></div>
  </div>
  <div class="status"><?= e($balances['status'] ?? 'Settled') ?></div>
</div>

<div class="kpi-grid kpi-grid-4">
  <?php
  $cards = [
      ['My Earnings', format_currency($balances['total_earnings'] ?? 0), 'kpi-earnings', '', '/portal/transactions?type=' . urlencode('Earnings')],
      ['Payments to Me', format_currency($balances['total_company_payments'] ?? 0), 'kpi-company', '', '/portal/transactions?type=' . urlencode('Company Payment')],
      ['My Payments to Company', format_currency($balances['total_candidate_payments'] ?? 0), 'kpi-candidate', '', '/portal/transactions?type=' . urlencode('Candidate Payment')],
      ['Expenses on My Behalf', format_currency($balances['total_expenses'] ?? 0), 'kpi-expense', '', '/portal/transactions?type=' . urlencode('Expense')],
  ];
  foreach ($cards as [$kpi_label, $kpi_value, $kpi_class, $kpi_tone, $kpi_href]) {
      $kpi_sub = 'View details →';
      include BASE_PATH . '/app/views/partials/_kpi_card.php';
  }
  ?>
</div>

<div class="sec-title">
  <h2>Summary by Project<?= e($scopeNote) ?></h2>
</div>
<div class="table-wrap">
  <table class="jp-table">
    <thead><tr><th>Project</th><th>Client / Partner</th><th class="num">Earnings</th><th class="num">Payments to Me</th><th class="num">My Payments</th><th class="num">Expenses</th><th class="num">Balance</th></tr></thead>
    <tbody>
    <?php if (empty($projectSummary['projects']) && empty($projectSummary['unassigned'])): ?>
      <tr><td colspan="7"><div class="empty-state">Nothing recorded yet.</div></td></tr>
    <?php endif; ?>
    <?php foreach ($projectSummary['projects'] as $ps): $b = (float) $ps['balance']; ?>
      <tr>
        <td><a href="/portal/transactions?project=<?= (int) $ps['id'] ?>"><strong><?= e($ps['project_name']) ?></strong></a></td>
        <td><?= !empty($ps['partner_name']) ? e($ps['partner_name']) : '—' ?></td>
        <td class="num"><?= format_currency($ps['earnings']) ?></td>
        <td class="num"><?= format_currency($ps['company_payments']) ?></td>
        <td class="num"><?= format_currency($ps['candidate_payments']) ?></td>
        <td class="num"><?= format_currency($ps['expenses']) ?></td>
        <td class="num"><span class="amount <?= $b > 0 ? 'pos' : ($b < 0 ? 'neg' : '') ?>"><?= format_currency($b) ?></span></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!empty($projectSummary['unassigned'])): $u = $projectSummary['unassigned']; $b = (float) $u['balance']; ?>
      <tr>
        <td><em>Not linked to a project</em></td>
        <td>—</td>
        <td class="num"><?= format_currency($u['earnings']) ?></td>
        <td class="num"><?= format_currency($u['company_payments']) ?></td>
        <td class="num"><?= format_currency($u['candidate_payments']) ?></td>
        <td class="num"><?= format_currency($u['expenses']) ?></td>
        <td class="num"><span class="amount <?= $b > 0 ? 'pos' : ($b < 0 ? 'neg' : '') ?>"><?= format_currency($b) ?></span></td>
      </tr>
    <?php endif; ?>
    <?php if (!empty($projectSummary['projects']) || !empty($projectSummary['unassigned'])): ?>
      <tr class="total-row">
        <td colspan="2">OVERALL</td>
        <td class="num"><?= format_currency($balances['total_earnings'] ?? 0) ?></td>
        <td class="num"><?= format_currency($balances['total_company_payments'] ?? 0) ?></td>
        <td class="num"><?= format_currency($balances['total_candidate_payments'] ?? 0) ?></td>
        <td class="num"><?= format_currency($balances['total_expenses'] ?? 0) ?></td>
        <td class="num"><?= format_currency($balances['current_balance'] ?? 0) ?></td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="sec-title">
  <h2>My Projects</h2>
</div>
<div class="table-wrap">
  <table class="jp-table">
    <thead><tr><th>ID</th><th>Project</th><th>Client / Partner</th><th>Start</th><th>End</th><th class="num">My Rate</th></tr></thead>
    <tbody>
    <?php if (empty($projects)): ?>
      <tr><td colspan="6"><div class="empty-state">No projects yet.</div></td></tr>
    <?php endif; ?>
    <?php foreach ($projects as $p): ?>
      <tr>
        <td class="nowrap"><?= e($p['project_id']) ?></td>
        <td><strong><?= e($p['project_name']) ?></strong></td>
        <td><?= !empty($p['partner_name']) ? e($p['partner_name']) : '—' ?></td>
        <td class="nowrap"><?= format_date($p['start_date']) ?></td>
        <td class="nowrap"><?= $p['end_date'] ? format_date($p['end_date']) : 'Ongoing' ?></td>
        <td class="num"><?= format_currency($p['rate_paid_to_candidate']) ?>/hr</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="sec-title">
  <h2>Recent Transactions<?= e($scopeNote) ?></h2>
  <a class="btn btn-secondary btn-sm" href="/portal/transactions">View all</a>
</div>
<div class="table-wrap">
  <table class="jp-table">
    <thead><tr><th>Date</th><th>ID</th><th>Type</th><th>Dir</th><th class="num">Amount</th><th>Notes</th><th>Project</th></tr></thead>
    <tbody>
    <?php if (empty($transactions)): ?>
      <tr><td colspan="7"><div class="empty-state">No transactions in this period.</div></td></tr>
    <?php endif; ?>
    <?php foreach ($transactions as $t): ?>
      <tr>
        <td class="nowrap"><?= format_date($t['transaction_date']) ?></td>
        <td class="nowrap small"><?= e($t['transaction_id']) ?></td>
        <td><span class="pill <?= match ($t['type']) {
              'Earnings' => 'pill-blue', 'Company Payment' => 'pill-purple',
              'Candidate Payment' => 'pill-teal', default => 'pill-coral' } ?>"><?= e($t['type']) ?></span></td>
        <td><span class="dir-badge <?= $t['direction'] === '+' ? 'plus' : 'minus' ?>"><?= e($t['direction']) ?></span></td>
        <td class="num"><span class="amount <?= $t['direction'] === '+' ? 'pos' : 'neg' ?>"><?= format_currency($t['effective_amount']) ?></span></td>
        <td class="small"><?= e($t['amount_notes']) ?></td>
        <td class="small"><?= e($t['project_name'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
