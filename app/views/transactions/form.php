<?php
$isEdit = !empty($txn['id']);
$type = $txn['type'] ?? 'Earnings';
?>
<div class="page-head">
  <div>
    <div class="eyebrow">Transactions</div>
    <h1><?= $isEdit ? 'Edit Transaction — ' . e($txn['transaction_id'] ?? '') : 'Add Transaction' ?></h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="<?= !empty($txn['candidate_id']) ? '/candidates/' . (int) $txn['candidate_id'] . '/transactions' : '/transactions' ?>">Cancel</a>
  </div>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-err"><?= e($err) ?></div>
<?php endforeach; ?>

<?php if (($txn['status'] ?? '') === 'rejected'): ?>
  <div class="alert alert-err">
    <strong>Rejected by super admin<?= !empty($txn['rejected_at']) ? ' on ' . format_date($txn['rejected_at']) : '' ?>.</strong>
    <?= !empty($txn['rejection_reason']) ? 'Reason: ' . e($txn['rejection_reason']) : 'No reason was given.' ?>
    &nbsp;Correct the details below and save — it will be resubmitted as Pending for approval.
  </div>
<?php endif; ?>

<div class="card card-top">
<form method="post" action="<?= $isEdit ? '/transactions/' . (int) $txn['id'] : '/transactions' ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>

  <div class="form-grid">
    <div class="field">
      <label>Candidate <span class="req">*</span></label>
      <select name="candidate_id" required id="txn-candidate"
              data-cascade-src="/candidates/{id}/projects.json"
              data-cascade-target="#txn-project">
        <option value="">— Select candidate —</option>
        <?php foreach ($candidates as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (string) $c['id'] === (string) ($txn['candidate_id'] ?? '') ? 'selected' : '' ?>>
            <?= e($c['full_name']) ?> (<?= e($c['candidate_id']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Type <span class="req">*</span></label>
      <select name="type" required data-txn-type>
        <?php foreach (Transaction::TYPES as $t): ?>
          <option value="<?= e($t) ?>" <?= $t === $type ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Transaction date: for Earnings it syncs to period end on save -->
    <div class="field" data-types="Company Payment,Expense,Candidate Payment">
      <label>Transaction date <span class="req">*</span></label>
      <input type="date" name="transaction_date" data-req value="<?= e($txn['transaction_date'] ?? date('Y-m-d')) ?>">
    </div>

    <div class="field" data-types="Earnings,Company Payment,Candidate Payment">
      <label>Project<span class="req" data-types="Earnings"> *</span></label>
      <select name="project_id" id="txn-project" data-placeholder="— Select project —">
        <option value="">— Select project —</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === (string) ($txn['project_id'] ?? '') ? 'selected' : '' ?>>
            <?= e($p['project_name']) ?> (<?= e($p['project_id']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <span class="hint">Required for Earnings; optional for payments. Lists only this candidate's projects.</span>
    </div>
  </div>

  <!-- ===================== Earnings ===================== -->
  <fieldset class="type-section" data-types="Earnings">
    <legend>Earnings details</legend>
    <div class="form-grid">
      <div class="field">
        <label>Period start date <span class="req">*</span></label>
        <input type="date" name="period_start_date" data-req value="<?= e($txn['period_start_date'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Period end date <span class="req">*</span></label>
        <input type="date" name="period_end_date" data-req value="<?= e($txn['period_end_date'] ?? '') ?>">
        <span class="hint">The transaction date is set to this on save.</span>
      </div>
      <div class="field">
        <label>Hours worked</label>
        <input type="number" step="0.01" min="0" name="hours_worked" value="<?= e($txn['hours_worked'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Rate applied ($/hr)</label>
        <input type="number" step="0.01" min="0" name="rate_applied" value="<?= e($txn['rate_applied'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Auto-calculated amount</label>
        <input type="text" readonly data-auto-amount value="<?= isset($txn['auto_calculated_amount']) ? format_currency($txn['auto_calculated_amount']) : '$0.00' ?>">
        <span class="hint">hours × rate — computed on save.</span>
      </div>
      <div class="field">
        <label>Amount override</label>
        <input type="number" step="0.01" min="0" name="amount_override" value="<?= e($txn['amount_override'] ?? '') ?>">
        <span class="hint">If set (&gt; 0), used instead of the auto amount.</span>
      </div>
    </div>
  </fieldset>

  <!-- ===================== Company Payment ===================== -->
  <fieldset class="type-section" data-types="Company Payment">
    <legend>Company payment details</legend>
    <div class="form-grid">
      <div class="field">
        <label>Payment method <span class="req">*</span></label>
        <select name="payment_method" data-req>
          <option value="">— Select method —</option>
          <?php foreach (dropdown('payment_method') as $m): ?>
            <option value="<?= e($m) ?>" <?= $m === ($txn['payment_method'] ?? '') ? 'selected' : '' ?>><?= e($m) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Payment amount ($) <span class="req">*</span></label>
        <input type="number" step="0.01" min="0.01" name="payment_amount" data-req value="<?= e($txn['payment_amount'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Reference number</label>
        <input type="text" name="reference_number" value="<?= e($txn['reference_number'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Period covered</label>
        <input type="text" name="period_covered" placeholder="e.g. First half June 2026" value="<?= e($txn['period_covered'] ?? '') ?>">
      </div>
    </div>
  </fieldset>

  <!-- ===================== Expense ===================== -->
  <fieldset class="type-section" data-types="Expense">
    <legend>Expense details</legend>
    <div class="form-grid">
      <div class="field">
        <label>Expense type <span class="req">*</span></label>
        <select name="expense_type" data-req>
          <option value="">— Select type —</option>
          <?php foreach (dropdown('expense_type') as $x): ?>
            <option value="<?= e($x) ?>" <?= $x === ($txn['expense_type'] ?? '') ? 'selected' : '' ?>><?= e($x) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Expense amount ($) <span class="req">*</span></label>
        <input type="number" step="0.01" min="0.01" name="expense_amount" data-req value="<?= e($txn['expense_amount'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Paid to (vendor)</label>
        <input type="text" name="paid_to_vendor" placeholder="e.g. USCIS" value="<?= e($txn['paid_to_vendor'] ?? '') ?>">
      </div>
      <div class="field field-check">
        <input type="checkbox" id="reimb" name="reimbursable_by_candidate" value="1" <?= !empty($txn['reimbursable_by_candidate']) && $txn['reimbursable_by_candidate'] !== 'f' && $txn['reimbursable_by_candidate'] !== false ? 'checked' : '' ?>>
        <label for="reimb">Reimbursable by candidate</label>
      </div>
    </div>
  </fieldset>

  <!-- ===================== Candidate Payment ===================== -->
  <fieldset class="type-section" data-types="Candidate Payment">
    <legend>Candidate payment details</legend>
    <div class="form-grid">
      <div class="field">
        <label>Reason for payment <span class="req">*</span></label>
        <select name="reason_for_payment" data-req>
          <option value="">— Select reason —</option>
          <?php foreach (dropdown('reason_for_payment') as $r): ?>
            <option value="<?= e($r) ?>" <?= $r === ($txn['reason_for_payment'] ?? '') ? 'selected' : '' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Method received <span class="req">*</span></label>
        <select name="method_received" data-req>
          <option value="">— Select method —</option>
          <?php foreach (method_received_options() as $m): ?>
            <option value="<?= e($m) ?>" <?= $m === ($txn['method_received'] ?? '') ? 'selected' : '' ?>><?= e($m) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Amount received ($) <span class="req">*</span></label>
        <input type="number" step="0.01" min="0.01" name="candidate_payment_amount" data-req value="<?= e($txn['candidate_payment_amount'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Reference</label>
        <input type="text" name="reference" value="<?= e($txn['reference'] ?? '') ?>">
      </div>
    </div>
  </fieldset>

  <div class="form-grid">
    <div class="field full">
      <label>Amount notes</label>
      <textarea name="amount_notes" style="min-height:64px" placeholder="Explain how the amount was calculated…"><?= e($txn['amount_notes'] ?? '') ?></textarea>
    </div>
    <div class="field full">
      <label>Description notes</label>
      <textarea name="description_notes" style="min-height:64px"><?= e($txn['description_notes'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label>Attachments</label>
      <input type="file" name="attachments[]" multiple>
    </div>
  </div>

  <?php if ($isEdit && !empty($attachments)): ?>
    <div class="sec-title"><h3 class="mb-0">Existing attachments</h3></div>
    <?php $entity = 'transaction'; include BASE_PATH . '/app/views/partials/_attachments.php'; ?>
  <?php endif; ?>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save Changes' : 'Record Transaction' ?></button>
  </div>
</form>
</div>
