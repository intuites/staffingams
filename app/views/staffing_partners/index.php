<div class="page-head">
  <div>
    <div class="eyebrow">Clients &amp; Vendors</div>
    <h1>Staffing Partners</h1>
    <div class="sub">The organizations where candidates are engaged on projects (clients, vendors, partners).</div>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="/partners/create">+ Add Staffing Partner</a>
  </div>
</div>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Search</label>
        <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Partner name…">
      </div>
      <div class="filter-actions">
        <button class="btn btn-gradient" type="submit">Search</button>
        <a class="btn btn-secondary" href="/partners">Reset</a>
      </div>
    </div>
  </form>
</div>

<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr><th>ID</th><th>Partner</th><th>Type</th><th>Contact Email</th><th>Phone</th><th class="num">Projects</th><th>Date Added</th><th class="num">Actions</th></tr>
  </thead>
  <tbody>
  <?php if (empty($partners)): ?>
    <tr><td colspan="8"><div class="empty-state"><div class="big">No staffing partners yet</div>Add the client and vendor organizations your candidates work at.</div></td></tr>
  <?php endif; ?>
  <?php foreach ($partners as $p): ?>
    <tr>
      <td class="nowrap"><a href="/partners/<?= (int) $p['id'] ?>"><?= e($p['partner_id']) ?></a></td>
      <td><a href="/partners/<?= (int) $p['id'] ?>"><strong><?= e($p['partner_name']) ?></strong></a></td>
      <td><?= $p['partner_type'] ? '<span class="pill pill-blue">' . e($p['partner_type']) . '</span>' : '—' ?></td>
      <td><?= e($p['primary_contact_email'] ?? '—') ?></td>
      <td class="nowrap"><?= e($p['primary_contact_phone'] ?? '—') ?></td>
      <td class="num"><?= (int) $p['project_count'] ?></td>
      <td class="nowrap"><?= format_date($p['date_added']) ?></td>
      <td>
        <div class="row-actions">
          <a class="btn btn-secondary btn-sm" href="/partners/<?= (int) $p['id'] ?>">View</a>
          <a class="btn btn-secondary btn-sm" href="/partners/<?= (int) $p['id'] ?>/edit">Edit</a>
          <button type="button" class="btn btn-danger btn-sm"
            data-confirm-action="/partners/<?= (int) $p['id'] ?>/delete"
            data-confirm-msg="Delete staffing partner <?= e($p['partner_name']) ?>? Partners with projects cannot be deleted.">Delete</button>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
