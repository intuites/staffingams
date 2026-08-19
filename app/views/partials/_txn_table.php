<?php
/**
 * Transaction table. Vars: $transactions, optional $total (renders sticky total
 * rows top+bottom), optional $showCandidate (bool).
 * Status workflow: pending (excluded from balances) → approved → locked (immutable).
 * Super admins see Approve / Lock / Unlock actions.
 */
$showCandidate = $showCandidate ?? false;
$isSuper = Auth::isSuper();
$cols = 9 + ($showCandidate ? 1 : 0);
$attachCounts = [];
foreach ($transactions as $t) {
    $attachCounts[$t['id']] = Attachments::countFor('transaction', (int) $t['id']);
}
$statusPill = function (string $s): string {
    return match ($s) {
        'pending'  => '<span class="pill pill-gold" title="Awaiting super admin approval — not counted in balances">Pending</span>',
        'locked'   => '<span class="pill pill-grey" title="Final — cannot be edited">🔒 Locked</span>',
        'rejected' => '<span class="pill pill-red" title="Rejected by super admin — edit to resubmit">Rejected</span>',
        default    => '<span class="pill pill-green">Approved</span>',
    };
};
?>
<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr>
      <th>Date</th>
      <th>ID</th>
      <?php if ($showCandidate): ?><th>Candidate</th><?php endif; ?>
      <th>Type</th>
      <th>Dir</th>
      <th class="num">Final Amount</th>
      <th>Status</th>
      <th>Amount Notes</th>
      <th>Project</th>
      <th>Description</th>
      <th class="num">Files / Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (isset($total) && !empty($transactions)): ?>
    <tr class="total-row top">
      <td colspan="<?= $cols - 5 ?>">TOTAL (<?= count($transactions) ?> transactions; pending excluded)</td>
      <td class="num"><?= format_currency($total) ?></td>
      <td colspan="5"></td>
    </tr>
  <?php endif; ?>
  <?php if (empty($transactions)): ?>
    <tr><td colspan="<?= $cols + 1 ?>">
      <div class="empty-state"><div class="big">No transactions</div>Nothing matches the current filters.</div>
    </td></tr>
  <?php endif; ?>
  <?php foreach ($transactions as $t): $locked = $t['status'] === 'locked'; ?>
    <tr>
      <td class="nowrap"><?= format_date($t['transaction_date']) ?></td>
      <td class="nowrap small"><?= e($t['transaction_id']) ?></td>
      <?php if ($showCandidate): ?><td><a href="/candidates/<?= (int) $t['candidate_id'] ?>"><?= e($t['candidate_name']) ?></a></td><?php endif; ?>
      <td><span class="pill <?= match ($t['type']) {
            'Earnings' => 'pill-blue', 'Company Payment' => 'pill-purple',
            'Candidate Payment' => 'pill-teal', default => 'pill-coral' } ?>"><?= e($t['type']) ?></span></td>
      <td><span class="dir-badge <?= $t['direction'] === '+' ? 'plus' : 'minus' ?>"><?= e($t['direction']) ?></span></td>
      <td class="num"><span class="amount <?= $t['direction'] === '+' ? 'pos' : 'neg' ?>"><?= format_currency($t['effective_amount']) ?></span></td>
      <td><?= $statusPill($t['status']) ?></td>
      <td class="small" title="<?= e($t['amount_notes']) ?>"><?= e(mb_strimwidth((string) $t['amount_notes'], 0, 40, '…')) ?></td>
      <td class="small"><?= e($t['project_name'] ?? '—') ?></td>
      <td class="small" title="<?= e($t['description_notes']) ?>"><?= e(mb_strimwidth((string) $t['description_notes'], 0, 40, '…')) ?></td>
      <td>
        <div class="row-actions">
          <?php if ($attachCounts[$t['id']] > 0): ?><span class="pill pill-grey" title="Attachments">📎 <?= $attachCounts[$t['id']] ?></span><?php endif; ?>
          <?php if ($isSuper && in_array($t['status'], ['pending', 'rejected'], true)): ?>
            <form method="post" action="/transactions/<?= (int) $t['id'] ?>/approve"><?= Csrf::field() ?><button class="btn btn-primary btn-sm" type="submit">Approve</button></form>
          <?php endif; ?>
          <?php if ($isSuper && $t['status'] === 'approved'): ?>
            <form method="post" action="/transactions/<?= (int) $t['id'] ?>/lock"><?= Csrf::field() ?><button class="btn btn-secondary btn-sm" type="submit" title="Make final — no further edits">Lock</button></form>
          <?php endif; ?>
          <?php if ($isSuper && $locked): ?>
            <form method="post" action="/transactions/<?= (int) $t['id'] ?>/unlock"><?= Csrf::field() ?><button class="btn btn-secondary btn-sm" type="submit">Unlock</button></form>
          <?php endif; ?>
          <?php if (!$locked): ?>
            <a class="btn btn-secondary btn-sm" href="/transactions/<?= (int) $t['id'] ?>/edit">Edit</a>
            <button type="button" class="btn btn-danger btn-sm"
              data-confirm-action="/transactions/<?= (int) $t['id'] ?>/delete"
              data-confirm-msg="Delete transaction <?= e($t['transaction_id']) ?> (<?= e($t['type']) ?>, <?= format_currency($t['effective_amount']) ?>)?">Delete</button>
          <?php endif; ?>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (isset($total) && !empty($transactions)): ?>
    <tr class="total-row">
      <td colspan="<?= $cols - 5 ?>">TOTAL (<?= count($transactions) ?> transactions; pending excluded)</td>
      <td class="num"><?= format_currency($total) ?></td>
      <td colspan="5"></td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
