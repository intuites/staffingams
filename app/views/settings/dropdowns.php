<div class="page-head">
  <div>
    <div class="eyebrow">Settings</div>
    <h1>Dropdown Options</h1>
    <div class="sub">Add, reorder or deactivate the options offered in forms. Deactivated options stay on historical records.</div>
  </div>
</div>

<div class="grid-2">
<?php foreach ($categories as $cat => $label): ?>
  <div class="card card-top">
    <h3><?= e($label) ?></h3>
    <div class="table-wrap" style="box-shadow:none;border-radius:var(--r-md)">
      <table class="jp-table">
        <thead><tr><th>Value</th><th class="num">Order</th><th>Status</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($grouped[$cat] ?? [] as $opt): ?>
          <tr>
            <td><?= e($opt['value']) ?></td>
            <td class="num" style="width:110px">
              <form method="post" action="/settings/dropdowns/<?= (int) $opt['id'] ?>/update" style="display:flex;gap:6px;justify-content:flex-end">
                <?= Csrf::field() ?>
                <input type="number" name="display_order" value="<?= (int) $opt['display_order'] ?>" style="width:64px;padding:4px 8px;border:1px solid var(--ink-200);border-radius:4px">
                <button class="btn btn-secondary btn-sm" type="submit" title="Save order">✓</button>
              </form>
            </td>
            <td><span class="pill <?= $opt['is_active'] && $opt['is_active'] !== 'f' ? 'pill-green' : 'pill-grey' ?>"><?= $opt['is_active'] && $opt['is_active'] !== 'f' ? 'Active' : 'Inactive' ?></span></td>
            <td>
              <form method="post" action="/settings/dropdowns/<?= (int) $opt['id'] ?>/toggle" class="row-actions">
                <?= Csrf::field() ?>
                <button class="btn btn-secondary btn-sm" type="submit"><?= $opt['is_active'] && $opt['is_active'] !== 'f' ? 'Deactivate' : 'Activate' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <form method="post" action="/settings/dropdowns" style="display:flex;gap:8px;margin-top:var(--s4)">
      <?= Csrf::field() ?>
      <input type="hidden" name="category" value="<?= e($cat) ?>">
      <input type="text" name="value" placeholder="New option…" required style="flex:1;padding:9px 12px;border:1px solid var(--ink-200);border-radius:4px">
      <button class="btn btn-gradient btn-sm" type="submit">+ Add</button>
    </form>
  </div>
<?php endforeach; ?>
</div>

<div class="note note-blue" style="margin-top:var(--s5)">
  <strong>Method received</strong> (Candidate Payments) is a fixed list per specification: Check, Bank Transfer / ACH, Wire Transfer, Zelle, PayPal, Cash, Other.
</div>
