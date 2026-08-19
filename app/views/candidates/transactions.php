<?php
$cid = (int) $candidate['id'];
$exportQs = http_build_query(array_filter([
    'candidate'  => $cid,
    'type'       => $filters['type'] ?? null,
    'project_id' => $filters['project_id'] ?? null,
    'from_date'  => $filters['from_date'] ?? null,
    'to_date'    => $filters['to_date'] ?? null,
]));
?>
<div class="page-head">
  <div>
    <div class="eyebrow"><a href="/candidates/<?= $cid ?>"><?= e($candidate['candidate_id']) ?> — <?= e($candidate['first_name'] . ' ' . $candidate['last_name']) ?></a></div>
    <h1><?= e($filters['type'] ?: 'All') ?> Transactions</h1>
  </div>
  <div class="page-actions">
    <div class="dl-buttons">
      <a class="btn btn-secondary btn-sm" href="/export/transactions?format=xlsx&<?= $exportQs ?>">Excel</a>
      <a class="btn btn-secondary btn-sm" href="/export/transactions?format=csv&<?= $exportQs ?>">CSV</a>
      <a class="btn btn-secondary btn-sm" href="/export/transactions?format=pdf&<?= $exportQs ?>">PDF</a>
    </div>
    <a class="btn btn-primary" href="/transactions/create?candidate=<?= $cid ?><?= $filters['type'] ? '&type=' . urlencode($filters['type']) : '' ?>">+ Add Transaction</a>
  </div>
</div>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Type</label>
        <select name="type" data-autosubmit>
          <option value="">All types</option>
          <?php foreach (Transaction::TYPES as $t): ?>
            <option value="<?= e($t) ?>" <?= $t === ($filters['type'] ?? '') ? 'selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Project</label>
        <select name="project_id" data-autosubmit>
          <option value="">All projects</option>
          <?php foreach ($projects as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === (string) ($filters['project_id'] ?? '') ? 'selected' : '' ?>><?= e($p['project_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>From</label>
        <input type="date" name="from_date" value="<?= e($filters['from_date'] ?? '') ?>">
      </div>
      <div class="field">
        <label>To</label>
        <input type="date" name="to_date" value="<?= e($filters['to_date'] ?? '') ?>">
      </div>
      <div class="filter-actions">
        <button class="btn btn-gradient" type="submit">Apply</button>
        <a class="btn btn-secondary" href="/candidates/<?= $cid ?>/transactions">Reset</a>
      </div>
    </div>
  </form>
</div>

<?php include BASE_PATH . '/app/views/partials/_txn_table.php'; ?>
