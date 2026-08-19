<?php $isEdit = !empty($partner['id']); ?>
<div class="page-head">
  <div>
    <div class="eyebrow">Staffing Partners</div>
    <h1><?= $isEdit ? 'Edit Staffing Partner — ' . e($partner['partner_id'] ?? '') : 'Add Staffing Partner' ?></h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="<?= $isEdit ? '/partners/' . (int) $partner['id'] : '/partners' ?>">Cancel</a>
  </div>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-err"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card card-top">
<form method="post" action="<?= $isEdit ? '/partners/' . (int) $partner['id'] : '/partners' ?>">
  <?= Csrf::field() ?>
  <div class="form-grid">
    <div class="field">
      <label>Partner name <span class="req">*</span></label>
      <input type="text" name="partner_name" required value="<?= e($partner['partner_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Partner type</label>
      <select name="partner_type">
        <option value="">— Select type —</option>
        <?php foreach (StaffingPartner::TYPES as $t): ?>
          <option value="<?= $t ?>" <?= $t === ($partner['partner_type'] ?? '') ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field full">
      <label>Address</label>
      <textarea name="address" style="min-height:64px"><?= e($partner['address'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label>Primary contact name</label>
      <input type="text" name="primary_contact_name" value="<?= e($partner['primary_contact_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Primary contact email</label>
      <input type="email" name="primary_contact_email" value="<?= e($partner['primary_contact_email'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Primary contact phone</label>
      <input type="text" name="primary_contact_phone" value="<?= e($partner['primary_contact_phone'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Date added</label>
      <input type="date" name="date_added" value="<?= e($partner['date_added'] ?? date('Y-m-d')) ?>">
    </div>
    <div class="field full">
      <label>Notes</label>
      <textarea name="notes"><?= e($partner['notes'] ?? '') ?></textarea>
    </div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save Changes' : 'Create Staffing Partner' ?></button>
  </div>
</form>
</div>
