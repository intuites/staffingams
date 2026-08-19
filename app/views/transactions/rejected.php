<div class="page-head">
  <div>
    <div class="eyebrow">Review/Approve</div>
    <h1>Rejected Transactions</h1>
    <div class="sub">Sent back by the super admin for correction. Click a transaction to open and edit it — saving your changes resubmits it as <strong>Pending</strong> for approval.</div>
  </div>
</div>

<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr><th>Rejected</th><th>By</th><th>Transaction</th><th>Candidate</th><th>Type</th><th class="num">Amount</th><th>Rejection Reason</th><th class="num">Actions</th></tr>
  </thead>
  <tbody>
  <?php if (empty($rejected)): ?>
    <tr><td colspan="8"><div class="empty-state"><div class="big">Nothing rejected 🎉</div>No transactions are waiting for correction.</div></td></tr>
  <?php endif; ?>
  <?php foreach ($rejected as $t): ?>
    <tr>
      <td class="nowrap small"><?= format_date($t['rejected_at']) ?></td>
      <td class="small"><?= e($t['rejected_by_name'] ?? '—') ?></td>
      <td class="nowrap small">
        <a href="/transactions/<?= (int) $t['id'] ?>/edit" title="Open and correct this transaction"><strong><?= e($t['transaction_id']) ?></strong></a>
        <br><span class="muted"><?= format_date($t['transaction_date']) ?></span>
      </td>
      <td><a href="/candidates/<?= (int) $t['candidate_id'] ?>"><?= e($t['candidate_name']) ?></a></td>
      <td><span class="pill <?= match ($t['type']) {
            'Earnings' => 'pill-blue', 'Company Payment' => 'pill-purple',
            'Candidate Payment' => 'pill-teal', default => 'pill-coral' } ?>"><?= e($t['type']) ?></span></td>
      <td class="num"><span class="amount <?= $t['direction'] === '+' ? 'pos' : 'neg' ?>"><?= format_currency($t['effective_amount']) ?></span></td>
      <td class="small" style="max-width:320px"><?= $t['rejection_reason'] ? nl2br(e($t['rejection_reason'])) : '<span class="muted">—</span>' ?></td>
      <td>
        <div class="row-actions">
          <a class="btn btn-primary btn-sm" href="/transactions/<?= (int) $t['id'] ?>/edit">Correct &amp; Resubmit</a>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
