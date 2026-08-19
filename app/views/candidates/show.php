<?php $cid = (int) $candidate['id']; ?>
<div class="page-head">
  <div>
    <div class="eyebrow"><?= e($candidate['candidate_id']) ?></div>
    <h1><?= e($candidate['first_name'] . ' ' . $candidate['last_name']) ?></h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="/candidate-dashboard?company=<?= (int) $candidate['company_id'] ?>&candidate=<?= $cid ?>">Open in Dashboard</a>
    <a class="btn btn-secondary" href="/candidates/<?= $cid ?>/edit">Edit</a>
    <button type="button" class="btn btn-danger"
      data-confirm-action="/candidates/<?= $cid ?>/delete"
      data-confirm-msg="Delete candidate <?= e($candidate['first_name'] . ' ' . $candidate['last_name']) ?>? Candidates with transactions or projects cannot be deleted.">Delete</button>
    <a class="btn btn-primary" href="/transactions/create?candidate=<?= $cid ?>">+ Add Transaction</a>
  </div>
</div>

<?php include BASE_PATH . '/app/views/partials/_candidate_body.php'; ?>
