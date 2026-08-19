<?php $isEdit = !empty($candidate['id']); ?>
<div class="page-head">
  <div>
    <div class="eyebrow">Candidates</div>
    <h1><?= $isEdit ? 'Edit Candidate — ' . e($candidate['candidate_id'] ?? '') : 'Add Candidate' ?></h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="<?= $isEdit ? '/candidates/' . (int) $candidate['id'] : '/candidates' ?>">Cancel</a>
  </div>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-err"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card card-top">
<form method="post" action="<?= $isEdit ? '/candidates/' . (int) $candidate['id'] : '/candidates' ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-grid">
    <div class="field">
      <label>First name <span class="req">*</span></label>
      <input type="text" name="first_name" required value="<?= e($candidate['first_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Last name <span class="req">*</span></label>
      <input type="text" name="last_name" required value="<?= e($candidate['last_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Email <span class="req">*</span></label>
      <input type="email" name="email" required value="<?= e($candidate['email'] ?? '') ?>">
      <span class="hint">Also the candidate's future portal login identity.</span>
    </div>
    <div class="field">
      <label>Phone <span class="req">*</span></label>
      <input type="text" name="phone" required value="<?= e($candidate['phone'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Company <span class="req">*</span></label>
      <select name="company_id" required>
        <option value="">— Select company —</option>
        <?php foreach ($companies as $co): ?>
          <option value="<?= (int) $co['id'] ?>" <?= (string) $co['id'] === (string) ($candidate['company_id'] ?? '') ? 'selected' : '' ?>>
            <?= e($co['company_name']) ?> (<?= e($co['company_id']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Employment status <span class="req">*</span></label>
      <select name="employment_status" required>
        <option value="">— Select status —</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= e($s) ?>" <?= $s === ($candidate['employment_status'] ?? '') ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Date registered</label>
      <input type="date" name="date_registered" value="<?= e($candidate['date_registered'] ?? date('Y-m-d')) ?>">
    </div>
    <div class="field">
      <label>Attachments</label>
      <input type="file" name="attachments[]" multiple>
      <span class="hint">PDF, images, Word, Excel — max 10 MB each.</span>
    </div>
    <div class="field full">
      <label>Notes</label>
      <textarea name="notes"><?= e($candidate['notes'] ?? '') ?></textarea>
    </div>
  </div>

  <fieldset class="type-section">
    <legend>Candidate portal access</legend>
    <div class="form-grid">
      <div class="field field-check">
        <input type="checkbox" id="portal_enabled" name="portal_enabled" value="1"
          <?= !empty($candidate['portal_enabled']) && $candidate['portal_enabled'] !== 'f' ? 'checked' : '' ?>>
        <label for="portal_enabled">Allow this candidate to sign in at <code>/portal</code> (they see only their own data)</label>
      </div>
      <div class="field">
        <label>Set / reset portal password</label>
        <input type="text" name="portal_password" autocomplete="new-password" placeholder="Leave blank to keep current password">
        <span class="hint">Login is their email + this password. Share it with the candidate securely.</span>
      </div>
    </div>
  </fieldset>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save Changes' : 'Create Candidate' ?></button>
  </div>
</form>
</div>
