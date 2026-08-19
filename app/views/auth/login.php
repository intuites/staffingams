<h1>Sign in</h1>
<p class="sub">Staffing Accounting Management System</p>
<?php include BASE_PATH . '/app/views/partials/_flash.php'; ?>
<form method="post" action="/login">
  <?= Csrf::field() ?>
  <div class="field">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required autofocus autocomplete="username">
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">
  </div>
  <?= Turnstile::widget() ?>
  <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
</form>
