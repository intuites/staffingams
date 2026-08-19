<?php
$scopeNote = ($periodLabel ?? 'All time') !== 'All time' ? ' · ' . $periodLabel : '';
$exportQs = http_build_query(array_filter([
    'type'      => $filters['type'] ?? null,
    'project'   => $filters['project_id'] ?? null,
    'period'    => 'custom',
    'from_date' => $filters['from_date'] ?? null,
    'to_date'   => $filters['to_date'] ?? null,
]));
?>
<div class="page-head">
  <div>
    <div class="eyebrow"><?= e($candidate['candidate_id']) ?> — <?= e($candidate['first_name'] . ' ' . $candidate['last_name']) ?></div>
    <h1>My Transactions<?= e($scopeNote) ?></h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary btn-sm" href="/portal/export?<?= $exportQs ?>">Download CSV</a>
    <a class="btn btn-secondary" href="/portal">← My Dashboard</a>
  </div>
</div>

<div class="filter-bar">
  <form method="get" action="/portal/transactions">
    <div class="filter-grid">
      <div class="field">
        <label>Type</label>
        <select name="type" data-autosubmit>
          <option value="">All types</option>
          <?php foreach (Transaction::TYPES as $t): ?>
            <option value="<?= e($t) ?>" <?= $t === ($filters['type'] ?? '') ? 'selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Project</label>
        <select name="project" data-autosubmit>
          <option value="">All projects</option>
          <?php foreach ($projects as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === (string) ($filters['project_id'] ?? '') ? 'selected' : '' ?>><?= e($p['project_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php include BASE_PATH . '/app/views/partials/_period_filter.php'; ?>
      <div class="filter-actions">
        <a class="btn btn-secondary" href="/portal/transactions">Reset</a>
      </div>
    </div>
  </form>
</div>

<?php if (!empty($transactions)): ?>
<div class="totals-bar sticky-top">
  <span>TOTAL — <?= count($transactions) ?> transaction<?= count($transactions) === 1 ? '' : 's' ?><?= e($scopeNote) ?></span>
  <span><?= format_currency($total) ?></span>
</div>
<?php endif; ?>

<div class="table-wrap">
  <table class="jp-table">
    <thead><tr><th>Date</th><th>ID</th><th>Type</th><th>Dir</th><th class="num">Amount</th><th>Amount Notes</th><th>Project</th><th>Description</th><th class="num">Review</th></tr></thead>
    <tbody>
    <?php if (empty($transactions)): ?>
      <tr><td colspan="9"><div class="empty-state"><div class="big">No transactions</div>Nothing matches the current filters.</div></td></tr>
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
        <td class="small"><?= e($t['description_notes']) ?></td>
        <td class="num">
          <?php if (in_array((int) $t['id'], $openReviewTxnIds ?? [], true)): ?>
            <span class="pill pill-gold" title="Our team is reviewing this transaction">Review requested</span>
          <?php else: ?>
            <a class="btn btn-secondary btn-sm" href="/portal/review/<?= (int) $t['id'] ?>" title="Report a discrepancy on this transaction">Request review</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!empty($transactions)): ?>
      <tr class="total-row">
        <td colspan="4">TOTAL</td>
        <td class="num"><?= format_currency($total) ?></td>
        <td colspan="4"></td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if (!empty($myReviews)): ?>
<div class="sec-title">
  <h2>My Review Requests</h2>
</div>
<div class="table-wrap">
  <table class="jp-table">
    <thead><tr><th>Requested</th><th>Transaction</th><th>Your Comment</th><th>Status</th><th>Response</th></tr></thead>
    <tbody>
    <?php foreach ($myReviews as $mr): $resolved = $mr['status'] === 'resolved'; ?>
      <tr>
        <td class="nowrap small"><?= format_date($mr['created_at']) ?></td>
        <td class="nowrap small"><?= e($mr['txn_code']) ?></td>
        <td class="small" style="max-width:360px"><?= nl2br(e($mr['comment'])) ?></td>
        <td><span class="pill <?= $resolved ? 'pill-green' : 'pill-gold' ?>"><?= $resolved ? 'Resolved' : 'Under review' ?></span></td>
        <td class="small"><?= $mr['admin_response'] ? nl2br(e($mr['admin_response'])) : '—' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
