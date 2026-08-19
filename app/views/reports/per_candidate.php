<?php
$scopeNote = ($periodLabel ?? 'All time') !== 'All time' ? ' · ' . $periodLabel : ' · All time';
$dateQs = http_build_query(array_filter(['from_date' => $from, 'to_date' => $to]));
?>
<div class="page-head">
  <div>
    <div class="eyebrow">Reports</div>
    <h1>Per-Candidate Report<?= !empty($candidate) ? ' — ' . e($candidate['first_name'] . ' ' . $candidate['last_name']) : '' ?></h1>
    <div class="sub"><?= !empty($candidate)
        ? 'Full statement for ' . e($candidate['candidate_id']) . e($scopeNote) . '.'
        : 'Pick a candidate for their full statement, or view the all-candidates overview below.' ?></div>
  </div>
  <div class="page-actions">
    <div class="dl-buttons">
      <?php if (!empty($candidate)):
          $stQs = http_build_query(array_filter(['candidate' => $candidateId, 'from_date' => $from, 'to_date' => $to])); ?>
        <a class="btn btn-secondary btn-sm" href="/export/statement?format=xlsx&<?= $stQs ?>">Excel Statement</a>
        <a class="btn btn-secondary btn-sm" href="/export/statement?format=csv&<?= $stQs ?>">CSV</a>
        <a class="btn btn-secondary btn-sm" href="/export/statement?format=pdf&<?= $stQs ?>">PDF Statement</a>
      <?php else:
          $exQs = http_build_query(array_filter(['report' => 'per_candidate', 'from_date' => $from, 'to_date' => $to])); ?>
        <a class="btn btn-secondary btn-sm" href="/export/report?format=xlsx&<?= $exQs ?>">Excel</a>
        <a class="btn btn-secondary btn-sm" href="/export/report?format=csv&<?= $exQs ?>">CSV</a>
        <a class="btn btn-secondary btn-sm" href="/export/report?format=pdf&<?= $exQs ?>">PDF</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/app/views/partials/_report_tabs.php'; ?>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Candidate</label>
        <select name="candidate" data-autosubmit>
          <option value="">— All candidates (overview) —</option>
          <?php foreach ($candList as $c): ?>
            <option value="<?= (int) $c['candidate_id'] ?>" <?= (string) $c['candidate_id'] === (string) ($candidateId ?? '') ? 'selected' : '' ?>>
              <?= e($c['full_name']) ?> (<?= e($c['candidate_code']) ?> · <?= e($c['company_name']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php include BASE_PATH . '/app/views/partials/_period_filter.php'; ?>
      <div class="filter-actions">
        <a class="btn btn-secondary" href="/reports/per-candidate">Reset</a>
      </div>
    </div>
  </form>
</div>

<?php if (!empty($candidate)): $bal = (float) ($balances['current_balance'] ?? 0); ?>

  <!-- ===== Candidate statement ===== -->
  <div class="balance-banner">
    <div>
      <div class="l"><?= ($periodLabel ?? 'All time') !== 'All time' ? 'Balance for period' : 'Current Balance' ?> — <?= e($candidate['first_name'] . ' ' . $candidate['last_name']) ?> (<?= e($candidate['candidate_id']) ?>)<?= e($scopeNote) ?></div>
      <div class="n <?= $bal > 0 ? 'pos' : ($bal < 0 ? 'neg' : '') ?>"><?= format_currency($bal) ?></div>
    </div>
    <div class="status"><?= e($balances['status'] ?? 'Settled') ?></div>
  </div>

  <div class="kpi-grid kpi-grid-4">
    <?php
    $cards = [
        ['Earnings', format_currency($balances['total_earnings'] ?? 0), 'kpi-earnings', '', "/candidates/{$candidateId}/transactions?type=Earnings" . ($dateQs ? '&' . $dateQs : '')],
        ['Company Payments', format_currency($balances['total_company_payments'] ?? 0), 'kpi-company', '', "/candidates/{$candidateId}/transactions?type=" . urlencode('Company Payment') . ($dateQs ? '&' . $dateQs : '')],
        ['Candidate Payments', format_currency($balances['total_candidate_payments'] ?? 0), 'kpi-candidate', '', "/candidates/{$candidateId}/transactions?type=" . urlencode('Candidate Payment') . ($dateQs ? '&' . $dateQs : '')],
        ['Expenses', format_currency($balances['total_expenses'] ?? 0), 'kpi-expense', '', "/candidates/{$candidateId}/transactions?type=Expense" . ($dateQs ? '&' . $dateQs : '')],
    ];
    foreach ($cards as [$kpi_label, $kpi_value, $kpi_class, $kpi_tone, $kpi_href]) {
        include BASE_PATH . '/app/views/partials/_kpi_card.php';
    }
    ?>
  </div>

  <div class="sec-title">
    <h2>Summary by Project<?= e($scopeNote) ?></h2>
  </div>
  <div class="table-wrap">
    <table class="jp-table">
      <thead><tr><th>Project</th><th>Staffing Partner</th><th class="num">Earnings</th><th class="num">Company Payments</th><th class="num">Candidate Payments</th><th class="num">Expenses</th><th class="num">Balance</th></tr></thead>
      <tbody>
      <?php if (empty($projectSummary['projects']) && empty($projectSummary['unassigned'])): ?>
        <tr><td colspan="7"><div class="empty-state">No activity in this period.</div></td></tr>
      <?php endif; ?>
      <?php foreach ($projectSummary['projects'] as $ps): $b = (float) $ps['balance']; ?>
        <tr>
          <td><strong><?= e($ps['project_name']) ?></strong> <span class="muted small"><?= e($ps['project_id']) ?></span></td>
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
          <td class="num"><?= format_currency($bal) ?></td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="sec-title">
    <h2>All Transactions<?= e($scopeNote) ?></h2>
  </div>
  <?php include BASE_PATH . '/app/views/partials/_txn_table.php'; ?>

<?php else: ?>

  <!-- ===== All-candidates overview ===== -->
  <?php
  $sumE = array_sum(array_map(fn($r) => (float) $r['total_earnings'], $rows));
  $sumCP = array_sum(array_map(fn($r) => (float) $r['total_company_payments'], $rows));
  $sumCA = array_sum(array_map(fn($r) => (float) $r['total_candidate_payments'], $rows));
  $sumX = array_sum(array_map(fn($r) => (float) $r['total_expenses'], $rows));
  $sumB = array_sum(array_map(fn($r) => (float) $r['current_balance'], $rows));
  ?>
  <div class="table-wrap">
  <table class="jp-table">
    <thead>
      <tr><th>ID</th><th>Candidate</th><th>Company</th><th>Status</th><th class="num">Earnings</th><th class="num">Company Payments</th><th class="num">Candidate Payments</th><th class="num">Expenses</th><th class="num">Current Balance</th><th>Position</th></tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="10"><div class="empty-state">No candidates to report on.</div></td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): $bal = (float) $r['current_balance'];
        $rowQs = http_build_query(array_filter(['candidate' => $r['candidate_id'], 'period' => $period !== 'all' ? $period : null, 'from_date' => $customFrom, 'to_date' => $customTo])); ?>
      <tr>
        <td class="nowrap"><a href="/reports/per-candidate?<?= $rowQs ?>"><?= e($r['candidate_code']) ?></a></td>
        <td><a href="/reports/per-candidate?<?= $rowQs ?>"><strong><?= e($r['full_name']) ?></strong></a></td>
        <td><?= e($r['company_name']) ?></td>
        <td><span class="pill <?= $r['employment_status'] === 'Active' ? 'pill-green' : 'pill-grey' ?>"><?= e($r['employment_status']) ?></span></td>
        <td class="num"><?= format_currency($r['total_earnings']) ?></td>
        <td class="num"><?= format_currency($r['total_company_payments']) ?></td>
        <td class="num"><?= format_currency($r['total_candidate_payments']) ?></td>
        <td class="num"><?= format_currency($r['total_expenses']) ?></td>
        <td class="num"><span class="amount <?= $bal > 0 ? 'pos' : ($bal < 0 ? 'neg' : '') ?>"><?= format_currency($bal) ?></span></td>
        <td class="small nowrap"><?= e($r['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($rows): ?>
      <tr class="total-row">
        <td colspan="4">TOTAL (<?= count($rows) ?> candidates)</td>
        <td class="num"><?= format_currency($sumE) ?></td>
        <td class="num"><?= format_currency($sumCP) ?></td>
        <td class="num"><?= format_currency($sumCA) ?></td>
        <td class="num"><?= format_currency($sumX) ?></td>
        <td class="num"><?= format_currency($sumB) ?></td>
        <td></td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>

<?php endif; ?>
