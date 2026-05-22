<?php
require_once __DIR__ . '/../config/session.php';

// Already logged in → redirect to dashboard
if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Sign In</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/auth.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>

  <div class="bg-layer bg-overlay"></div>
  <div class="bg-layer bg-grid"></div>
  <div class="bg-layer bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <div class="stat-pill stat-pill--left">
    <span class="stat-num">2,400+</span>
    <span class="stat-label">Active Members</span>
  </div>
  <div class="stat-pill stat-pill--right">
    <span class="stat-num">48</span>
    <span class="stat-label">Weekly Classes</span>
  </div>

  <main class="auth-shell">

    <div class="brand">
      <a href="../index.php" class="brand__icon" style="text-decoration:none;">
        <svg viewBox="0 0 40 40" fill="none" width="54" height="54">
          <rect x="2" y="16" width="8" height="8" rx="1" fill="currentColor"/>
          <rect x="30" y="16" width="8" height="8" rx="1" fill="currentColor"/>
          <rect x="10" y="10" width="4" height="20" rx="1" fill="currentColor"/>
          <rect x="26" y="10" width="4" height="20" rx="1" fill="currentColor"/>
          <rect x="14" y="18" width="12" height="4" rx="1" fill="currentColor"/>
        </svg>
      </a>
      <div class="brand__text">
        <span class="brand__name">W8</span>
      </div>
    </div>

    <?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <section class="auth-card">

      <div class="tabs" role="tablist">
        <a class="tab tab--active" href="sign_in.php">Sign In</a>
        <a class="tab" href="register.php">Register</a>
        <span class="tab-indicator tab-indicator--left"></span>
      </div>

      <section class="panel panel--active" id="panel-login" role="tabpanel">
        <form class="form" method="post" action="../actions/do_login.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">


          <div class="field">
            <label class="field__label" for="login-email">Email / Username</label>
            <div class="field__wrap">
              <svg class="field__icon" viewBox="0 0 20 20" fill="none">
                <path d="M2.5 5.5A1.5 1.5 0 014 4h12a1.5 1.5 0 011.5 1.5v9A1.5 1.5 0 0116 16H4a1.5 1.5 0 01-1.5-1.5v-9z" stroke="currentColor" stroke-width="1.4"/>
                <path d="M2.5 6l7.5 5 7.5-5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
              <input class="field__input" type="text" id="login-email" name="email"
                     placeholder="you@example.com" autocomplete="email" required>
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="login-password">Password</label>
            <div class="field__wrap">
              <svg class="field__icon" viewBox="0 0 20 20" fill="none">
                <rect x="4" y="9" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                <path d="M7 9V6.5a3 3 0 016 0V9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                <circle cx="10" cy="13" r="1.2" fill="currentColor"/>
              </svg>
              <input class="field__input" type="password" id="login-password" name="password"
                     placeholder="••••••••" autocomplete="current-password" required>
            </div>
            <a href="forgot_password.php" class="field__link field__link--end">Forgot password?</a>
          </div>

          <div class="field field--inline">
            <label class="checkbox">
              <input type="checkbox" id="remember" name="remember">
              <span class="checkbox__box"></span>
              <span class="checkbox__label">Keep me signed in</span>
            </label>
          </div>

          <button class="btn btn-primary btn-block" type="submit">
            <span class="btn-submit__text">Continue</span>
            <svg class="btn-submit__arrow" viewBox="0 0 20 20" fill="none">
              <path d="M4 10h12M12 6l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>

        </form>
      </section>
    </section>

    <footer class="auth-footer">&copy; W8 — All rights reserved</footer>
  </main>

</body>
</html>
