<div class="page-head">
  <div>
    <div class="eyebrow">Reports</div>
    <h1>Per-Employment-Status Report</h1>
  </div>
  <div class="page-actions">
    <div class="dl-buttons">
      <a class="btn btn-secondary btn-sm" href="/export/report?format=xlsx&report=per_status">Excel</a>
      <a class="btn btn-secondary btn-sm" href="/export/report?format=csv&report=per_status">CSV</a>
      <a class="btn btn-secondary btn-sm" href="/export/report?format=pdf&report=per_status">PDF</a>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/app/views/partials/_report_tabs.php'; ?>

<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr><th>Employment Status</th><th class="num"># Candidates</th><th class="num">Aggregate Current Balance</th></tr>
  </thead>
  <tbody>
  <?php if (empty($rows)): ?>
    <tr><td colspan="3"><div class="empty-state">No candidates to report on.</div></td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): $bal = (float) $r['aggregate_balance']; ?>
    <tr>
      <td><span class="pill <?= $r['employment_status'] === 'Active' ? 'pill-green' : 'pill-grey' ?>"><?= e($r['employment_status']) ?></span></td>
      <td class="num"><?= (int) $r['candidate_count'] ?></td>
      <td class="num"><span class="amount <?= $bal > 0 ? 'pos' : ($bal < 0 ? 'neg' : '') ?>"><?= format_currency($bal) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
