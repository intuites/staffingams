<?php
/**
 * KPI card. Vars: $kpi_label, $kpi_value (pre-formatted string), $kpi_href (optional),
 * $kpi_class (optional card modifier), $kpi_tone ('pos'|'neg'|''), $kpi_sub (optional).
 */
$tag = !empty($kpi_href) ? 'a' : 'div';
?>
<<?= $tag ?><?= !empty($kpi_href) ? ' href="' . e($kpi_href) . '"' : '' ?> class="kpi <?= e($kpi_class ?? '') ?>">
  <div class="l"><?= e($kpi_label) ?></div>
  <div class="n <?= e($kpi_tone ?? '') ?>"><?= e($kpi_value) ?></div>
  <?php if (!empty($kpi_sub)): ?><div class="s"><?= e($kpi_sub) ?></div><?php endif; ?>
</<?= $tag ?>>
