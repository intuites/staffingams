<?php
$exportQs = http_build_query(array_filter([
    'candidate' => $candidateId,
    'type'      => $filters['type'] ?? null,
    'from_date' => $filters['from_date'] ?? null,
    'to_date'   => $filters['to_date'] ?? null,
]));
?>
<div class="page-head">
  <div>
    <div class="eyebrow">Finance</div>
    <h1>Transactions</h1>
  </div>
  <div class="page-actions">
    <?php if ($candidateId): ?>
      <div class="dl-buttons">
        <a class="btn btn-secondary btn-sm" href="/export/transactions?format=xlsx&<?= $exportQs ?>">Excel</a>
        <a class="btn btn-secondary btn-sm" href="/export/transactions?format=csv&<?= $exportQs ?>">CSV</a>
        <a class="btn btn-secondary btn-sm" href="/export/transactions?format=pdf&<?= $exportQs ?>">PDF</a>
      </div>
    <?php endif; ?>
    <a class="btn btn-primary" href="/transactions/create<?= $candidateId ? '?candidate=' . (int) $candidateId : '' ?>">+ Add Transaction</a>
  </div>
</div>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Company</label>
        <select name="company" data-autosubmit>
          <option value="">— Select company —</option>
          <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['id'] ?>" <?= (string) $co['id'] === (string) $companyId ? 'selected' : '' ?>><?= e($co['company_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Candidate</label>
        <select name="candidate" data-autosubmit <?= $companyId ? '' : 'disabled' ?>>
          <option value="">— Select candidate —</option>
          <?php foreach ($candidates as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string) $c['id'] === (string) $candidateId ? 'selected' : '' ?>><?= e($c['full_name']) ?> (<?= e($c['candidate_id']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Type</label>
        <select name="type" data-autosubmit <?= $candidateId ? '' : 'disabled' ?>>
          <option value="">All types this candidate has</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= $t === ($filters['type'] ?? '') ? 'selected' : '' ?>><?= e($t) ?></option>
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
        <a class="btn btn-secondary" href="/transactions">Reset</a>
      </div>
    </div>
  </form>
</div>

<?php if (!$candidateId): ?>
  <div class="card">
    <div class="empty-state">
      <div class="big">Select a company and candidate above to view their transactions.</div>
      The Type filter fills in with only the transaction types that candidate actually has.
    </div>
  </div>
<?php else: ?>
  <?php include BASE_PATH . '/app/views/partials/_txn_table.php'; ?>
<?php endif; ?>
