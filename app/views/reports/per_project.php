<?php
$exportQs = http_build_query(array_filter(['report' => 'per_project', 'from_date' => $from, 'to_date' => $to]));
$sumE = array_sum(array_map(fn($r) => (float) $r['total_earnings'], $rows));
$sumP = array_sum(array_map(fn($r) => (float) $r['total_company_payments'], $rows));
$sumN = array_sum(array_map(fn($r) => (float) $r['net'], $rows));
?>
<div class="page-head">
  <div>
    <div class="eyebrow">Reports</div>
    <h1>Per-Project Report</h1>
  </div>
  <div class="page-actions">
    <div class="dl-buttons">
      <a class="btn btn-secondary btn-sm" href="/export/report?format=xlsx&<?= $exportQs ?>">Excel</a>
      <a class="btn btn-secondary btn-sm" href="/export/report?format=csv&<?= $exportQs ?>">CSV</a>
      <a class="btn btn-secondary btn-sm" href="/export/report?format=pdf&<?= $exportQs ?>">PDF</a>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/app/views/partials/_report_tabs.php'; ?>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Transactions from</label>
        <input type="date" name="from_date" value="<?= e($from ?? '') ?>">
      </div>
      <div class="field">
        <label>Transactions to</label>
        <input type="date" name="to_date" value="<?= e($to ?? '') ?>">
      </div>
      <div class="filter-actions">
        <button class="btn btn-gradient" type="submit">Apply</button>
        <a class="btn btn-secondary" href="/reports/per-project">Reset</a>
      </div>
    </div>
  </form>
</div>

<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr><th>Project ID</th><th>Project</th><th>Staffing Partner</th><th>Candidate</th><th class="num">Total Earnings</th><th class="num">Company Payments</th><th class="num">Net (Earnings − Payments)</th></tr>
  </thead>
  <tbody>
  <?php if (empty($rows)): ?>
    <tr><td colspan="7"><div class="empty-state">No projects to report on.</div></td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): $net = (float) $r['net']; ?>
    <tr>
      <td class="nowrap"><?= e($r['project_id']) ?></td>
      <td><strong><?= e($r['project_name']) ?></strong></td>
      <td><?= !empty($r['partner_name']) ? e($r['partner_name']) : '—' ?></td>
      <td><?= e($r['candidate_name']) ?></td>
      <td class="num"><?= format_currency($r['total_earnings']) ?></td>
      <td class="num"><?= format_currency($r['total_company_payments']) ?></td>
      <td class="num"><span class="amount <?= $net > 0 ? 'pos' : ($net < 0 ? 'neg' : '') ?>"><?= format_currency($net) ?></span></td>
    </tr>
  <?php endforeach; ?>
  <?php if ($rows): ?>
    <tr class="total-row">
      <td colspan="4">TOTAL</td>
      <td class="num"><?= format_currency($sumE) ?></td>
      <td class="num"><?= format_currency($sumP) ?></td>
      <td class="num"><?= format_currency($sumN) ?></td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
