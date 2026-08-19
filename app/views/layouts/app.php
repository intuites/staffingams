<?php
/**
 * Master layout. Expects: $content (string), optional $title, optional $js.
 * Theme follows ?theme=<name> (cookie-persisted) — warm | navy | violet | ink.
 */
$themes = ['warm', 'navy', 'violet', 'ink'];
$theme = $_GET['theme'] ?? $_COOKIE['app_theme'] ?? 'navy';
if (!in_array($theme, $themes, true)) { $theme = 'navy'; }
if (($_GET['theme'] ?? null) && !headers_sent()) {
    setcookie('app_theme', $theme, time() + 86400 * 365, '/');
}
$admin = Auth::user();
?><!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Staffing Accounting') ?> — Staffing Accounting System</title>
<link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="utility">
  <div class="container">
    <div class="utility-left">
      <span>Staffing Accounting Management System</span>
    </div>
    <div class="utility-right">
      <div class="theme-switch" title="Theme">
        <?php foreach ($themes as $t): ?>
          <a class="th-<?= $t ?> <?= $t === $theme ? 'active' : '' ?>" href="?theme=<?= $t ?>" title="<?= ucfirst($t) ?> theme"></a>
        <?php endforeach; ?>
      </div>
      <span class="sep">|</span>
      <span><?= e($admin['name'] ?? '') ?> <?= ($admin['role'] ?? '') === 'super_admin' ? '· Super Admin' : '· Admin' ?></span>
      <span class="sep">|</span>
      <a href="/logout">Sign out</a>
    </div>
  </div>
</div>

<header class="masthead">
  <div class="container">
    <a class="brand" href="/">
      <img src="/assets/img/logo.svg" alt="Staffing Accounting System" class="brand-logo">
      <span class="brand-name">Staffing Accounting<br><small>System</small></span>
    </a>
    <button class="navtoggle" type="button" data-nav-toggle>Menu ☰</button>
    <nav class="primary-nav" data-nav>
      <?php
        $isSuper = Auth::isSuper();
        $pendingN = $isSuper ? Transaction::pendingCount() : 0;
        $reviewN = ReviewRequest::openCount($isSuper);
        $rejectedN = Transaction::rejectedCount();
        $raCount = $pendingN + $reviewN + $rejectedN;
        $dashActive = current_path() === '/' || nav_active('/candidate-dashboard');
        $raActive = nav_active('/approvals') || nav_active('/reviews') || nav_active('/rejected');
      ?>
      <div class="navdrop <?= $dashActive ? 'active-parent' : '' ?>">
        <button type="button" class="navtrigger <?= $dashActive ? 'active' : '' ?>" data-drop>Dashboards ▾</button>
        <div class="navdrop-menu">
          <a href="/" class="<?= current_path() === '/' ? 'active' : '' ?>">Company Dashboard</a>
          <a href="/candidate-dashboard" class="<?= nav_active('/candidate-dashboard') ? 'active' : '' ?>">Candidate Dashboard</a>
        </div>
      </div>
      <a href="/candidates" class="<?= nav_active('/candidates') ? 'active' : '' ?>">Candidates</a>
      <a href="/companies" class="<?= nav_active('/companies') ? 'active' : '' ?>">Companies</a>
      <a href="/partners" class="<?= nav_active('/partners') ? 'active' : '' ?>">Partners</a>
      <a href="/projects" class="<?= nav_active('/projects') ? 'active' : '' ?>">Projects</a>
      <a href="/transactions" class="<?= nav_active('/transactions') ? 'active' : '' ?>">Transactions</a>
      <div class="navdrop <?= $raActive ? 'active-parent' : '' ?>">
        <button type="button" class="navtrigger <?= $raActive ? 'active' : '' ?>" data-drop>Review/Approve<?= $raCount > 0 ? ' (' . $raCount . ')' : '' ?> ▾</button>
        <div class="navdrop-menu">
          <?php if ($isSuper): ?>
            <a href="/approvals" class="<?= nav_active('/approvals') ? 'active' : '' ?>">Approve Admin Transactions<?= $pendingN > 0 ? ' (' . $pendingN . ')' : '' ?></a>
          <?php endif; ?>
          <a href="/reviews" class="<?= nav_active('/reviews') ? 'active' : '' ?>">Review Candidate Comments<?= $reviewN > 0 ? ' (' . $reviewN . ')' : '' ?></a>
          <a href="/rejected" class="<?= nav_active('/rejected') ? 'active' : '' ?>">Rejected Transactions<?= $rejectedN > 0 ? ' (' . $rejectedN . ')' : '' ?></a>
        </div>
      </div>
      <a href="/reports" class="<?= nav_active('/reports') ? 'active' : '' ?>">Reports</a>
      <a href="/settings/dropdowns" class="<?= nav_active('/settings') ? 'active' : '' ?>">Settings</a>
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
    <span class="foot-mut">Internal admin system — authorized users only</span>
  </div>
</footer>

<?php include BASE_PATH . '/app/views/partials/_modal.php'; ?>
<script src="/assets/js/app.js"></script>
<?php if (!empty($js)): ?><script><?= $js ?></script><?php endif; ?>
</body>
</html>
