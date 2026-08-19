<?php
/**
 * Attachment list. Vars: $attachments (rows), $entity (company|candidate|project|transaction).
 */
if (!empty($attachments)): ?>
<ul class="attach-list">
  <?php foreach ($attachments as $a): ?>
  <li>
    <span>📎</span>
    <a class="nm" href="/attachments/<?= e($entity) ?>/<?= (int) $a['id'] ?>/download"><?= e($a['original_filename']) ?></a>
    <span class="sz"><?= $a['size_bytes'] ? number_format($a['size_bytes'] / 1024, 0) . ' KB' : '' ?></span>
    <button type="button" class="btn btn-danger btn-sm"
      data-confirm-action="/attachments/<?= e($entity) ?>/<?= (int) $a['id'] ?>/delete"
      data-confirm-msg="Delete attachment <?= e($a['original_filename']) ?>?">✕</button>
  </li>
  <?php endforeach; ?>
</ul>
<?php else: ?>
<p class="muted small mb-0">No attachments.</p>
<?php endif; ?>
