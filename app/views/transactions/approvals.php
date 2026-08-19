<div class="page-head">
  <div>
    <div class="eyebrow">Super Admin</div>
    <h1>Pending Approvals</h1>
    <div class="sub">Transactions entered by admins, awaiting your review. They do not count toward any balance until approved. Lock a transaction when it is final — locked entries cannot be edited or deleted.</div>
  </div>
</div>

<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr><th>Entered</th><th>Date</th><th>ID</th><th>Candidate</th><th>Type</th><th class="num">Amount</th><th>Amount Notes</th><th>Project</th><th class="num">Actions</th></tr>
  </thead>
  <tbody>
  <?php if (empty($pending)): ?>
    <tr><td colspan="9"><div class="empty-state"><div class="big">Nothing pending 🎉</div>All transactions have been reviewed.</div></td></tr>
  <?php endif; ?>
  <?php foreach ($pending as $t): ?>
    <tr>
      <td class="nowrap small"><?= format_date($t['created_at']) ?></td>
      <td class="nowrap"><?= format_date($t['transaction_date']) ?></td>
      <td class="nowrap small"><?= e($t['transaction_id']) ?></td>
      <td><a href="/candidates/<?= (int) $t['candidate_id'] ?>"><?= e($t['candidate_name']) ?></a></td>
      <td><span class="pill <?= match ($t['type']) {
            'Earnings' => 'pill-blue', 'Company Payment' => 'pill-purple',
            'Candidate Payment' => 'pill-teal', default => 'pill-coral' } ?>"><?= e($t['type']) ?></span></td>
      <td class="num"><span class="amount <?= $t['direction'] === '+' ? 'pos' : 'neg' ?>"><?= format_currency($t['effective_amount']) ?></span></td>
      <td class="small"><?= e($t['amount_notes']) ?></td>
      <td class="small"><?= e($t['project_name'] ?? '—') ?></td>
      <td>
        <div class="row-actions">
          <a class="btn btn-secondary btn-sm" href="/transactions/<?= (int) $t['id'] ?>/edit">Review / Edit</a>
          <form method="post" action="/transactions/<?= (int) $t['id'] ?>/approve"><?= Csrf::field() ?><button class="btn btn-primary btn-sm" type="submit">Approve</button></form>
          <form method="post" action="/transactions/<?= (int) $t['id'] ?>/lock"><?= Csrf::field() ?><button class="btn btn-gradient btn-sm" type="submit" title="Approve and make final in one step">Approve + Lock</button></form>
          <form method="post" action="/transactions/<?= (int) $t['id'] ?>/reject" style="display:flex;gap:6px">
            <?= Csrf::field() ?>
            <input type="text" name="rejection_reason" placeholder="Reason (sent to admins)" style="padding:6px 10px;border:1px solid var(--ink-200);border-radius:4px;font-size:13px;width:180px">
            <button class="btn btn-danger btn-sm" type="submit" title="Send back to the admins for correction">Reject</button>
          </form>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
