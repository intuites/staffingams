<h1>Candidate Sign in</h1>
<p class="sub">See your projects, transactions and current balance.</p>
<?php include BASE_PATH . '/app/views/partials/_flash.php'; ?>
<form method="post" action="/portal/login">
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
<p class="muted small center" style="margin-top:16px">No access yet? Ask your account manager to enable portal access.<br>
<a href="/login">Admin sign in →</a></p>
