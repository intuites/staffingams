<div class="page-head">
  <div>
    <div class="eyebrow">Overview</div>
    <h1>Company Dashboard<?= $company ? ' — ' . e($company['company_name']) : '' ?></h1>
    <div class="sub"><?= $company
        ? 'Summary of all candidates on ' . e($company['company_name']) . '\'s payroll (' . e($company['company_id']) . ')'
        : 'Firm-wide totals across all payroll companies. Pick a company to scope the numbers' ?>
      <?= ($periodLabel ?? 'All time') !== 'All time' ? ' · <strong>' . e($periodLabel) . '</strong>' : '' ?>.</div>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="/candidate-dashboard<?= $company ? '?company=' . (int) $company['id'] : '' ?>">Candidate Dashboard</a>
    <a class="btn btn-primary" href="/candidates/create<?= $company ? '?company=' . (int) $company['id'] : '' ?>">+ Add Candidate</a>
  </div>
</div>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Company</label>
        <select name="company" data-autosubmit>
          <option value="">All companies (firm-wide)</option>
          <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['id'] ?>" <?= (string) $co['id'] === (string) ($filters['company_id'] ?? '') ? 'selected' : '' ?>><?= e($co['company_name']) ?> (<?= e($co['company_id']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Employment status</label>
        <select name="status" data-autosubmit>
          <option value="">All statuses</option>
          <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === ($filters['employment_status'] ?? '') ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php include BASE_PATH . '/app/views/partials/_period_filter.php'; ?>
      <div class="filter-actions">
        <a class="btn btn-secondary" href="/">Reset</a>
      </div>
    </div>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
  </form>
</div>

<div class="kpi-grid">
  <?php
  $cards = [
      ['Active Candidates', number_format($kpis['active_candidates']), '', '', '/candidates?status=Active' . ($company ? '&company=' . (int) $company['id'] : '')],
      ['Total Earnings', format_currency($kpis['total_earnings']), 'kpi-earnings', '', null],
      ['Company Payments', format_currency($kpis['total_company_payments']), 'kpi-company', '', null],
      ['Candidate Payments', format_currency($kpis['total_candidate_payments']), 'kpi-candidate', '', null],
      ['Total Expenses', format_currency($kpis['total_expenses']), 'kpi-expense', '', null],
      ['Net Company Position', format_currency($kpis['net_company_position']),
        '', $kpis['net_company_position'] >= 0 ? 'pos' : 'neg', null],
  ];
  foreach ($cards as [$kpi_label, $kpi_value, $kpi_class, $kpi_tone, $kpi_href]) {
      include BASE_PATH . '/app/views/partials/_kpi_card.php';
  }
  ?>
</div>

<div class="sec-title" style="margin-top:0">
  <h2>Candidates<?= $company ? ' at ' . e($company['company_name']) : '' ?> — balances<?= ($periodLabel ?? 'All time') !== 'All time' ? ' (' . e($periodLabel) . ')' : '' ?></h2>
</div>
<?php $linkToDashboard = true; include BASE_PATH . '/app/views/partials/_candidates_table.php'; ?>
