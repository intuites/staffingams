<?php $isEdit = !empty($project['id']); ?>
<div class="page-head">
  <div>
    <div class="eyebrow">Projects</div>
    <h1><?= $isEdit ? 'Edit Project — ' . e($project['project_id'] ?? '') : 'Add Project' ?></h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="/projects">Cancel</a>
  </div>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-err"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card card-top">
<form method="post" action="<?= $isEdit ? '/projects/' . (int) $project['id'] : '/projects' ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-grid">
    <div class="field">
      <label>Candidate <span class="req">*</span></label>
      <select name="candidate_id" required>
        <option value="">— Select candidate —</option>
        <?php foreach ($candidates as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (string) $c['id'] === (string) ($project['candidate_id'] ?? '') ? 'selected' : '' ?>>
            <?= e($c['full_name']) ?> (<?= e($c['candidate_id']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Staffing partner <span class="req">*</span></label>
      <select name="staffing_partner_id" required>
        <option value="">— Select staffing partner —</option>
        <?php foreach ($partners as $sp): ?>
          <option value="<?= (int) $sp['id'] ?>" <?= (string) $sp['id'] === (string) ($project['staffing_partner_id'] ?? '') ? 'selected' : '' ?>>
            <?= e($sp['partner_name']) ?><?= $sp['partner_type'] ? ' — ' . e($sp['partner_type']) : '' ?> (<?= e($sp['partner_id']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <span class="hint">The client/vendor organization where the candidate works. <a href="/partners/create">Add one</a> if missing.</span>
    </div>
    <div class="field">
      <label>Project name <span class="req">*</span></label>
      <input type="text" name="project_name" required value="<?= e($project['project_name'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Start date <span class="req">*</span></label>
      <input type="date" name="start_date" required value="<?= e($project['start_date'] ?? '') ?>">
    </div>
    <div class="field">
      <label>End date</label>
      <input type="date" name="end_date" value="<?= e($project['end_date'] ?? '') ?>">
      <span class="hint">Leave blank for ongoing projects.</span>
    </div>
  </div>

  <fieldset class="type-section">
    <legend>Rates</legend>
    <div class="form-grid">
      <div class="field">
        <label>Rate from client ($/hr) <span class="req">*</span></label>
        <input type="number" step="0.01" min="0" name="rate_from_client" required data-rate-client value="<?= e($project['rate_from_client'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Rate informed to candidate ($/hr) <span class="req">*</span></label>
        <input type="number" step="0.01" min="0" name="rate_informed_to_candidate" required data-rate-informed value="<?= e($project['rate_informed_to_candidate'] ?? '') ?>">
      </div>
      <div class="field">
        <label>% paid to candidate <span class="req">*</span></label>
        <input type="number" step="0.0001" min="0" max="100" name="percent_paid_to_candidate" required data-rate-pct value="<?= e($project['percent_paid_to_candidate'] ?? '') ?>">
        <span class="hint">Enter a fraction (0.85) or a percentage (85) — both are accepted.</span>
      </div>
      <div class="field">
        <label>Final rate override ($/hr)</label>
        <input type="number" step="0.01" min="0" name="final_rate_override" data-rate-override value="<?= e($project['final_rate_override'] ?? '') ?>">
        <span class="hint">If set (&gt; 0), overrides the auto-calculated rate.</span>
      </div>
      <div class="field">
        <label>Auto-calculated final rate</label>
        <input type="text" readonly data-auto-rate value="<?= isset($project['auto_calculated_final_rate']) ? format_currency($project['auto_calculated_final_rate']) : '' ?>">
        <span class="hint">min(client rate, informed rate) × % paid — computed on save.</span>
      </div>
      <div class="field">
        <label>Rate paid to candidate</label>
        <input type="text" readonly data-final-rate value="<?= isset($project['rate_paid_to_candidate']) ? format_currency($project['rate_paid_to_candidate']) : '' ?>">
        <span class="hint">Override wins if set; otherwise the auto rate.</span>
      </div>
    </div>
  </fieldset>

  <div class="form-grid">
    <div class="field">
      <label>Attachments</label>
      <input type="file" name="attachments[]" multiple>
    </div>
    <div class="field full">
      <label>Notes</label>
      <textarea name="notes"><?= e($project['notes'] ?? '') ?></textarea>
    </div>
  </div>

  <?php if ($isEdit && !empty($attachments)): ?>
    <div class="sec-title"><h3 class="mb-0">Existing attachments</h3></div>
    <?php $entity = 'project'; include BASE_PATH . '/app/views/partials/_attachments.php'; ?>
  <?php endif; ?>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save Changes' : 'Create Project' ?></button>
  </div>
</form>
</div>
