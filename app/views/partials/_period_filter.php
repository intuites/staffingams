<?php
/**
 * Period filter fields (select + custom date inputs).
 * Expects: $period (current key), $customFrom, $customTo.
 * Custom date inputs are shown only when 'custom' is selected.
 */
$isCustom = ($period ?? 'all') === 'custom';
?>
<div class="field">
  <label>Period</label>
  <select name="period" data-autosubmit data-period>
    <?php foreach (period_options() as $k => $label): ?>
      <option value="<?= e($k) ?>" <?= $k === ($period ?? 'all') ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="field" data-custom-date <?= $isCustom ? '' : 'style="display:none"' ?>>
  <label>From</label>
  <input type="date" name="from_date" data-autosubmit value="<?= e($customFrom ?? '') ?>">
</div>
<div class="field" data-custom-date <?= $isCustom ? '' : 'style="display:none"' ?>>
  <label>To</label>
  <input type="date" name="to_date" data-autosubmit value="<?= e($customTo ?? '') ?>">
</div>
