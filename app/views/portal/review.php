<div class="page-head">
  <div>
    <div class="eyebrow"><?= e($txn['transaction_id']) ?></div>
    <h1>Request a Review</h1>
    <div class="sub">Spotted a discrepancy? Describe it below and our team will look into this transaction.</div>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="/portal/transactions">← Back to My Transactions</a>
  </div>
</div>

<div class="grid-2">
  <div class="card card-top">
    <h3>Transaction being questioned</h3>
    <dl class="dl">
      <dt>ID</dt><dd><?= e($txn['transaction_id']) ?></dd>
      <dt>Date</dt><dd><?= format_date($txn['transaction_date']) ?></dd>
      <dt>Type</dt><dd><?= e($txn['type']) ?></dd>
      <dt>Amount</dt><dd><strong><?= format_currency($txn['effective_amount']) ?></strong> (<?= e($txn['direction']) ?>)</dd>
      <?php if ($txn['project_name']): ?><dt>Project</dt><dd><?= e($txn['project_name']) ?></dd><?php endif; ?>
      <?php if ($txn['amount_notes']): ?><dt>Notes</dt><dd><?= e($txn['amount_notes']) ?></dd><?php endif; ?>
    </dl>
  </div>

  <div class="card card-top">
    <h3>Your comment</h3>
    <form method="post" action="/portal/review/<?= (int) $txn['id'] ?>">
      <?= Csrf::field() ?>
      <div class="field">
        <label>Describe the discrepancy <span class="req">*</span></label>
        <textarea name="comment" required style="min-height:140px" placeholder="e.g. I worked 88 hours this period, but this shows 80…"></textarea>
        <span class="hint">Be specific — dates, hours, and amounts help us resolve it faster.</span>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg">Send Review Request</button>
      </div>
    </form>
  </div>
</div>
