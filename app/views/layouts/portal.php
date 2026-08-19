<?php
/**
 * Candidate portal layout — same design system, minimal nav, no admin links.
 */
$themes = ['warm', 'navy', 'violet', 'ink'];
$theme = $_GET['theme'] ?? $_COOKIE['app_theme'] ?? 'navy';
if (!in_array($theme, $themes, true)) { $theme = 'navy'; }
?><!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Candidate Portal') ?> — Staffing Accounting System</title>
<link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="utility">
  <div class="container">
    <div class="utility-left"><span>Candidate Portal — Staffing Accounting System</span></div>
    <div class="utility-right">
      <span><?= e($_SESSION['portal_name'] ?? '') ?></span>
      <span class="sep">|</span>
      <a href="/portal/logout">Sign out</a>
    </div>
  </div>
</div>

<header class="masthead">
  <div class="container">
    <a class="brand" href="/portal">
      <img src="/assets/img/logo.svg" alt="Staffing Accounting System" class="brand-logo">
      <span class="brand-name">Staffing Accounting<br><small>Candidate Portal</small></span>
    </a>
    <button class="navtoggle" type="button" data-nav-toggle>Menu ☰</button>
    <nav class="primary-nav" data-nav>
      <a href="/portal" class="<?= current_path() === '/portal' ? 'active' : '' ?>">My Dashboard</a>
      <a href="/portal/transactions" class="<?= nav_active('/portal/transactions') ? 'active' : '' ?>">My Transactions</a>
    </nav>
  </div>
</header>

<main>
  <div class="container">
    <?php include BASE_PATH . '/app/views/partials/_flash.php'; ?>
    <?= $content ?>
  </div>
</main>

<footer class="site-footer">
  <div class="container">
    <span>&copy; <?= date('Y') ?> Staffing Accounting</span>
    <span class="foot-mut">Questions about your balance? Contact your account manager.</span>
  </div>
</footer>

<script src="/assets/js/app.js"></script>
</body>
</html>
