<?php foreach (flash_pull() as $f): ?>
  <div class="alert <?= $f['type'] === 'success' ? 'alert-ok' : 'alert-err' ?>">
    <?= e($f['message']) ?>
  </div>
<?php endforeach; ?>
