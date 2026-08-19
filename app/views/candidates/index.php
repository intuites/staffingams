<div class="page-head">
  <div>
    <div class="eyebrow">People</div>
    <h1>Candidates</h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="/candidates/create">+ Add Candidate</a>
  </div>
</div>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Search</label>
        <input type="text" name="q" value="<?= e($filters['search'] ?? '') ?>" placeholder="Name or email…">
      </div>
      <div class="field">
        <label>Company</label>
        <select name="company" data-autosubmit>
          <option value="">All companies</option>
          <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['id'] ?>" <?= (string) $co['id'] === (string) ($filters['company_id'] ?? '') ? 'selected' : '' ?>><?= e($co['company_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Employment status</label>
        <select name="status" data-autosubmit>
          <option value="">All statuses</option>
          <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === ($filters['employment_status'] ?? '') ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-actions">
        <button class="btn btn-gradient" type="submit">Search</button>
        <a class="btn btn-secondary" href="/candidates">Reset</a>
      </div>
    </div>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
  </form>
</div>

<?php $showContact = true; include BASE_PATH . '/app/views/partials/_candidates_table.php'; ?>
