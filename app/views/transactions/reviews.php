<div class="page-head">
  <div>
    <div class="eyebrow">Review/Approve</div>
    <h1>Review Candidate Comments</h1>
    <div class="sub">Discrepancies flagged by candidates from their portal.
      <?= Auth::isSuper()
        ? 'Requests on locked transactions are marked — only you (super admin) can edit those.'
        : 'Requests on locked transactions go to the super admin and are not shown here.' ?></div>
  </div>
</div>

<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr><th>Requested</th><th>Candidate</th><th>Transaction</th><th>Type</th><th class="num">Amount</th><th>Txn Status</th><th>Candidate's Comment</th><th class="num">Actions</th></tr>
  </thead>
  <tbody>
  <?php if (empty($reviews)): ?>
    <tr><td colspan="8"><div class="empty-state"><div class="big">No open review requests 🎉</div>Nothing flagged by candidates right now.</div></td></tr>
  <?php endif; ?>
  <?php foreach ($reviews as $r):
      $locked = $r['txn_status'] === 'locked';
      $canEdit = !$locked || Auth::isSuper();
  ?>
    <tr>
      <td class="nowrap small"><?= format_date($r['created_at']) ?></td>
      <td><a href="/candidates/<?= (int) $r['candidate_id'] ?>"><?= e($r['candidate_name']) ?></a></td>
      <td class="nowrap small">
        <?php if ($canEdit): ?>
          <a href="/transactions/<?= (int) $r['transaction_id'] ?>/edit" title="Open this transaction for editing"><strong><?= e($r['txn_code']) ?></strong></a>
        <?php else: ?>
          <?= e($r['txn_code']) ?>
        <?php endif; ?>
        <br><span class="muted"><?= format_date($r['transaction_date']) ?></span>
      </td>
      <td><span class="pill <?= match ($r['type']) {
            'Earnings' => 'pill-blue', 'Company Payment' => 'pill-purple',
            'Candidate Payment' => 'pill-teal', default => 'pill-coral' } ?>"><?= e($r['type']) ?></span></td>
      <td class="num"><?= format_currency($r['effective_amount']) ?></td>
      <td><?= $locked
            ? '<span class="pill pill-grey" title="Only a super admin can edit">🔒 Locked</span>'
            : '<span class="pill ' . ($r['txn_status'] === 'pending' ? 'pill-gold">Pending' : 'pill-green">Approved') . '</span>' ?></td>
      <td class="small" style="max-width:340px"><?= nl2br(e($r['comment'])) ?></td>
      <td>
        <div class="row-actions" style="flex-direction:column;align-items:flex-end;gap:6px">
          <?php if ($canEdit): ?>
            <a class="btn btn-secondary btn-sm" href="/transactions/<?= (int) $r['transaction_id'] ?>/edit">Open / Edit Transaction</a>
          <?php else: ?>
            <span class="muted small">Locked — super admin only</span>
          <?php endif; ?>
          <form method="post" action="/reviews/<?= (int) $r['id'] ?>/resolve" style="display:flex;gap:6px">
            <?= Csrf::field() ?>
            <input type="text" name="admin_response" placeholder="Response to candidate (optional)" style="padding:6px 10px;border:1px solid var(--ink-200);border-radius:4px;font-size:13px;width:220px">
            <button class="btn btn-primary btn-sm" type="submit">Resolve</button>
          </form>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
