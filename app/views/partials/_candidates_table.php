<?php
/**
 * Candidate balance table. Vars: $candidates (rows from Candidate::withBalances),
 * $showContact (bool — include email/phone columns), $sort, plus current query args.
 */
$showContact = $showContact ?? false;
// When set (Company Dashboard), row clicks open the Candidate Dashboard
// for that candidate instead of the plain detail page.
$linkToDashboard = $linkToDashboard ?? false;
$rowHref = function (array $c) use ($linkToDashboard): string {
    return $linkToDashboard
        ? '/candidate-dashboard?company=' . (int) $c['company_id'] . '&candidate=' . (int) $c['candidate_id']
        : '/candidates/' . (int) $c['candidate_id'];
};
$qs = $_GET;
$qs['sort'] = ($sort ?? '') === 'balance_desc' ? 'balance_asc' : 'balance_desc';
$sortUrl = current_path() . '?' . http_build_query($qs);
?>
<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <?php if ($showContact): ?><th>Email</th><th>Phone</th><?php endif; ?>
      <th>Company</th>
      <th>Status</th>
      <th class="num"><a href="<?= e($sortUrl) ?>">Current Balance <?= ($sort ?? '') === 'balance_asc' ? '↑' : '↓' ?></a></th>
      <th>Position</th>
      <th class="num">Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($candidates)): ?>
    <tr><td colspan="<?= $showContact ? 9 : 7 ?>">
      <div class="empty-state"><div class="big">No candidates found</div>Adjust the filters or add a candidate.</div>
    </td></tr>
  <?php endif; ?>
  <?php foreach ($candidates as $c): $bal = (float) $c['current_balance']; ?>
    <tr>
      <td class="nowrap"><a href="<?= e($rowHref($c)) ?>"><?= e($c['candidate_code']) ?></a></td>
      <td><a href="<?= e($rowHref($c)) ?>"><strong><?= e($c['full_name']) ?></strong></a></td>
      <?php if ($showContact): ?>
        <td><?= e($c['email']) ?></td>
        <td class="nowrap"><?= e($c['phone']) ?></td>
      <?php endif; ?>
      <td><a href="/companies/<?= (int) $c['company_id'] ?>"><?= e($c['company_name'] ?? '') ?></a></td>
      <td><span class="pill <?= $c['employment_status'] === 'Active' ? 'pill-green' : ($c['employment_status'] === 'Terminated' ? 'pill-red' : 'pill-grey') ?>"><?= e($c['employment_status']) ?></span></td>
      <td class="num"><span class="amount <?= $bal > 0 ? 'pos' : ($bal < 0 ? 'neg' : '') ?>"><?= format_currency($bal) ?></span></td>
      <td class="small nowrap"><?= e($c['status']) ?></td>
      <td>
        <div class="row-actions">
          <a class="btn btn-secondary btn-sm" href="<?= e($rowHref($c)) ?>"><?= $linkToDashboard ? 'Dashboard' : 'View' ?></a>
          <a class="btn btn-secondary btn-sm" href="/candidates/<?= (int) $c['candidate_id'] ?>/edit">Edit</a>
          <button type="button" class="btn btn-danger btn-sm"
            data-confirm-action="/candidates/<?= (int) $c['candidate_id'] ?>/delete"
            data-confirm-msg="Delete candidate <?= e($c['full_name']) ?>? Candidates with transactions or projects cannot be deleted.">Delete</button>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
