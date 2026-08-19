<div class="page-head">
  <div>
    <div class="eyebrow">Engagements</div>
    <h1>Projects</h1>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="/projects/create">+ Add Project</a>
  </div>
</div>

<div class="filter-bar">
  <form method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Candidate</label>
        <select name="candidate" data-autosubmit>
          <option value="">All candidates</option>
          <?php foreach ($candidates as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string) $c['id'] === (string) ($filters['candidate_id'] ?? '') ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Staffing partner</label>
        <select name="partner" data-autosubmit>
          <option value="">All partners</option>
          <?php foreach ($partners as $sp): ?>
            <option value="<?= (int) $sp['id'] ?>" <?= (string) $sp['id'] === (string) ($filters['staffing_partner_id'] ?? '') ? 'selected' : '' ?>><?= e($sp['partner_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Start date from</label>
        <input type="date" name="from_date" value="<?= e($filters['from_date'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Start date to</label>
        <input type="date" name="to_date" value="<?= e($filters['to_date'] ?? '') ?>">
      </div>
      <div class="filter-actions">
        <button class="btn btn-gradient" type="submit">Apply</button>
        <a class="btn btn-secondary" href="/projects">Reset</a>
      </div>
    </div>
  </form>
</div>

<div class="table-wrap">
<table class="jp-table">
  <thead>
    <tr><th>ID</th><th>Candidate</th><th>Project</th><th>Staffing Partner</th><th>Start</th><th>End</th><th class="num">Rate Paid to Candidate</th><th class="num">Actions</th></tr>
  </thead>
  <tbody>
  <?php if (empty($projects)): ?>
    <tr><td colspan="8"><div class="empty-state"><div class="big">No projects found</div>Adjust the filters or add a project.</div></td></tr>
  <?php endif; ?>
  <?php foreach ($projects as $p): ?>
    <tr>
      <td class="nowrap"><?= e($p['project_id']) ?></td>
      <td><a href="/candidates/<?= (int) $p['candidate_id'] ?>"><?= e($p['candidate_name']) ?></a></td>
      <td><strong><?= e($p['project_name']) ?></strong></td>
      <td><?= $p['partner_name'] ? '<a href="/partners/' . (int) $p['partner_pk'] . '">' . e($p['partner_name']) . '</a>' : '—' ?></td>
      <td class="nowrap"><?= format_date($p['start_date']) ?></td>
      <td class="nowrap"><?= $p['end_date'] ? format_date($p['end_date']) : '—' ?></td>
      <td class="num"><?= format_currency($p['rate_paid_to_candidate']) ?>/hr</td>
      <td>
        <div class="row-actions">
          <a class="btn btn-secondary btn-sm" href="/projects/<?= (int) $p['id'] ?>/edit">Edit</a>
          <button type="button" class="btn btn-danger btn-sm"
            data-confirm-action="/projects/<?= (int) $p['id'] ?>/delete"
            data-confirm-msg="Delete project <?= e($p['project_name']) ?>? Projects with transactions cannot be deleted.">Delete</button>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
