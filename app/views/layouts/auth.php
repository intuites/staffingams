<?php
$theme = $_COOKIE['app_theme'] ?? 'navy';
if (!in_array($theme, ['warm', 'navy', 'violet', 'ink'], true)) { $theme = 'navy'; }
?><!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Sign in') ?> — Staffing Accounting System</title>
<link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <img src="/assets/img/logo.svg" alt="Staffing Accounting System" class="brand-logo" style="width:56px;height:56px">
    <?= $content ?>
  </div>
</div>
</body>
</html>
