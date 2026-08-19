<div class="page-head">
  <div>
    <div class="eyebrow">Reports</div>
    <h1>Per-Company Report</h1>
  </div>
  <div class="page-actions">
    <div class="dl-buttons">
      <a class="btn btn-secondary btn-sm" href="/export/report?format=xlsx&report=per_company">Excel</a>
      <a class="btn btn-secondary btn-sm" href="/export/report?format=csv&report=per_company">CSV</a>
      <a class="btn btn-secondary btn-sm" href="/export/report?format=pdf&report=per_company">PDF</a>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/app/views/partials/_report_tabs.php'; ?>

<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr><th>Company</th><th class="num"># Candidates</th><th class="num">Total Earnings</th><th class="num">Company Payments</th><th class="num">Candidate Payments</th><th class="num">Expenses</th><th class="num">Aggregate Net Balance</th></tr>
  </thead>
  <tbody>
  <?php if (empty($rows)): ?>
    <tr><td colspan="7"><div class="empty-state">No companies to report on.</div></td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): $net = (float) $r['net_balance']; ?>
    <tr>
      <td><strong><?= e($r['company_name']) ?></strong> <span class="muted small"><?= e($r['company_id']) ?></span></td>
      <td class="num"><?= (int) $r['candidate_count'] ?></td>
      <td class="num"><?= format_currency($r['total_earnings']) ?></td>
      <td class="num"><?= format_currency($r['total_company_payments']) ?></td>
      <td class="num"><?= format_currency($r['total_candidate_payments']) ?></td>
      <td class="num"><?= format_currency($r['total_expenses']) ?></td>
      <td class="num"><span class="amount <?= $net > 0 ? 'pos' : ($net < 0 ? 'neg' : '') ?>"><?= format_currency($net) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
