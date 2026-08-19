<?php $isEdit = !empty($company['id']); ?>
<div class="page-head">
  <div>
    <div class="eyebrow">Companies</div>
    <h1><?= $isEdit ? 'Edit Company — ' . e($company['company_id'] ?? '') : 'Add Company' ?></h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="<?= $isEdit ? '/companies/' . (int) $company['id'] : '/companies' ?>">Cancel</a>
  </div>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-err"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card card-top">
<form method="post" action="<?= $isEdit ? '/companies/' . (int) $company['id'] : '/companies' ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-grid">
    <div class="field">
      <label>Company name <span class="req">*</span></label>
      <input type="text" name="company_name" required value="<?= e($company['company_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Date added</label>
      <input type="date" name="date_added" value="<?= e($company['date_added'] ?? date('Y-m-d')) ?>">
      <span class="hint">This is the staffing organization that runs candidate payroll — clients/vendors belong under Staffing Partners.</span>
    </div>
    <div class="field full">
      <label>Address</label>
      <textarea name="address" style="min-height:64px"><?= e($company['address'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label>Primary contact name</label>
      <input type="text" name="primary_contact_name" value="<?= e($company['primary_contact_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Primary contact email</label>
      <input type="email" name="primary_contact_email" value="<?= e($company['primary_contact_email'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Primary contact phone</label>
      <input type="text" name="primary_contact_phone" value="<?= e($company['primary_contact_phone'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Attachments</label>
      <input type="file" name="attachments[]" multiple>
    </div>
    <div class="field full">
      <label>Notes</label>
      <textarea name="notes"><?= e($company['notes'] ?? '') ?></textarea>
    </div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save Changes' : 'Create Company' ?></button>
  </div>
</form>
</div>
