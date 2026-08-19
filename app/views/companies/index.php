<div class="page-head">
  <div>
    <div class="eyebrow">Payroll Organizations</div>
    <h1>Companies</h1>
    <div class="sub">The staffing organizations that run candidate payroll (employer of record). Clients and vendors live under <a href="/partners">Staffing Partners</a>.</div>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="/companies/create">+ Add Company</a>
  </div>
</div>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Search</label>
        <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Company name…">
      </div>
      <div class="filter-actions">
        <button class="btn btn-gradient" type="submit">Search</button>
        <a class="btn btn-secondary" href="/companies">Reset</a>
      </div>
    </div>
  </form>
</div>

<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr><th>ID</th><th>Company</th><th>Contact</th><th>Contact Email</th><th>Phone</th><th>Date Added</th><th class="num">Actions</th></tr>
  </thead>
  <tbody>
  <?php if (empty($companies)): ?>
    <tr><td colspan="7"><div class="empty-state"><div class="big">No companies found</div>Add your first company to get started.</div></td></tr>
  <?php endif; ?>
  <?php foreach ($companies as $c): ?>
    <tr>
      <td class="nowrap"><a href="/companies/<?= (int) $c['id'] ?>"><?= e($c['company_id']) ?></a></td>
      <td><a href="/companies/<?= (int) $c['id'] ?>"><strong><?= e($c['company_name']) ?></strong></a></td>
      <td><?= e($c['primary_contact_name'] ?? '—') ?></td>
      <td><?= e($c['primary_contact_email'] ?? '—') ?></td>
      <td class="nowrap"><?= e($c['primary_contact_phone'] ?? '—') ?></td>
      <td class="nowrap"><?= format_date($c['date_added']) ?></td>
      <td>
        <div class="row-actions">
          <a class="btn btn-secondary btn-sm" href="/companies/<?= (int) $c['id'] ?>">View</a>
          <a class="btn btn-secondary btn-sm" href="/companies/<?= (int) $c['id'] ?>/edit">Edit</a>
          <button type="button" class="btn btn-danger btn-sm"
            data-confirm-action="/companies/<?= (int) $c['id'] ?>/delete"
            data-confirm-msg="Delete company <?= e($c['company_name']) ?>? Companies with candidates cannot be deleted.">Delete</button>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
