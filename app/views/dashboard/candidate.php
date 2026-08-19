<div class="page-head">
  <div>
    <div class="eyebrow">360° View</div>
    <h1>Candidate Dashboard</h1>
    <div class="sub">Pick a company, then a candidate — overall balance, per-project summary, and drill-downs into every transaction.</div>
  </div>
  <div class="page-actions">
    <?php if (!empty($candidate)): ?>
      <a class="btn btn-secondary" href="/candidates/<?= (int) $candidate['id'] ?>/edit">Edit Candidate</a>
      <a class="btn btn-primary" href="/transactions/create?candidate=<?= (int) $candidate['id'] ?>">+ Add Transaction</a>
    <?php endif; ?>
  </div>
</div>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Company (payroll)</label>
        <select name="company" data-autosubmit>
          <option value="">— Select company —</option>
          <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['id'] ?>" <?= (string) $co['id'] === (string) $companyId ? 'selected' : '' ?>><?= e($co['company_name']) ?> (<?= e($co['company_id']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Candidate</label>
        <select name="candidate" data-autosubmit <?= $companyId ? '' : 'disabled' ?>>
          <option value="">— Select candidate —</option>
          <?php foreach ($candList as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= !empty($candidate) && (string) $c['id'] === (string) $candidate['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?> (<?= e($c['candidate_id']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (!empty($candidate)): ?>
      <div class="field">
        <label>Project</label>
        <select name="project" data-autosubmit>
          <option value="">All projects</option>
          <?php foreach ($projList ?? [] as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === (string) ($projectId ?? '') ? 'selected' : '' ?>><?= e($p['project_name']) ?> (<?= e($p['project_id']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <?php include BASE_PATH . '/app/views/partials/_period_filter.php'; ?>
      <div class="filter-actions">
        <a class="btn btn-secondary" href="/candidate-dashboard">Reset</a>
      </div>
    </div>
  </form>
</div>

<?php if (empty($candidate)): ?>
  <div class="card">
    <div class="empty-state">
      <div class="big">Select a company and candidate above to open their dashboard.</div>
      You'll get the overall balance, a financial summary for each of their projects, and one-click drill-downs into the underlying transactions.
    </div>
  </div>
<?php else: ?>
  <?php include BASE_PATH . '/app/views/partials/_candidate_body.php'; ?>
<?php endif; ?>
