<?php
require_once __DIR__ . '/../config/session.php';

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
  <title>W8 — Register</title>
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

  <main class="auth-shell auth-shell--wide">

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

    <div class="auth-card">

      <div class="tabs" role="tablist">
        <a class="tab" href="sign_in.php">Sign In</a>
        <a class="tab tab--active" href="register.php">Register</a>
        <span class="tab-indicator tab-indicator--right"></span>
      </div>

      <div class="panel panel--active" id="panel-register" role="tabpanel">
        <form class="form" method="post" action="../actions/do_register.php" novalidate>

          <!-- Role selector -->
          <fieldset class="role-selector">
            <legend class="role-selector__legend">I am joining as</legend>
            <div class="role-selector__options">

              <label class="role-option">
                <input type="radio" name="role" value="member" checked>
                <span class="role-option__card">
                  <svg class="role-option__icon" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                  <span class="role-option__label">Member</span>
                  <span class="role-option__desc">Access classes &amp; facilities</span>
                </span>
              </label>

              <label class="role-option">
                <input type="radio" name="role" value="trainer">
                <span class="role-option__card">
                  <svg class="role-option__icon" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="11" width="4" height="4" rx="0.5" stroke="currentColor" stroke-width="1.5"/>
                    <rect x="17" y="11" width="4" height="4" rx="0.5" stroke="currentColor" stroke-width="1.5"/>
                    <rect x="7" y="7" width="2.5" height="10" rx="0.5" stroke="currentColor" stroke-width="1.5"/>
                    <rect x="14.5" y="7" width="2.5" height="10" rx="0.5" stroke="currentColor" stroke-width="1.5"/>
                    <rect x="9.5" y="11" width="5" height="2" rx="0.5" stroke="currentColor" stroke-width="1.5"/>
                  </svg>
                  <span class="role-option__label">Trainer</span>
                  <span class="role-option__desc">Teach classes &amp; personal training</span>
                </span>
              </label>

              <label class="role-option">
                <input type="radio" name="role" value="admin">
                <span class="role-option__card">
                  <svg class="role-option__icon" viewBox="0 0 24 24" fill="none">
                    <path d="M12 3l8 4v5c0 4-3.333 7.333-8 9-4.667-1.667-8-5-8-9V7l8-4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span class="role-option__label">Admin</span>
                  <span class="role-option__desc">Manage platform operations</span>
                </span>
              </label>

            </div>
          </fieldset>

          <hr class="form-divider">
          <p class="form-section-label">Personal Information</p>

          <div class="form__row">
            <div class="field">
              <label class="field__label" for="reg-firstname">First Name</label>
              <div class="field__wrap">
                <svg class="field__icon" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M3 17c0-3.314 3.134-6 7-6s7 2.686 7 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                <input class="field__input" type="text" id="reg-firstname" name="firstname" placeholder="Alex" required>
              </div>
            </div>
            <div class="field">
              <label class="field__label" for="reg-lastname">Last Name</label>
              <div class="field__wrap">
                <svg class="field__icon" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M3 17c0-3.314 3.134-6 7-6s7 2.686 7 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                <input class="field__input" type="text" id="reg-lastname" name="lastname" placeholder="Stone" required>
              </div>
            </div>
          </div>

          <div class="form__row">
            <div class="field">
              <label class="field__label" for="reg-dob">Date of Birth</label>
              <div class="field__wrap">
                <svg class="field__icon" viewBox="0 0 20 20" fill="none"><rect x="3" y="4" width="14" height="13" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M3 8h14M7 2v4M13 2v4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                <input class="field__input field__input--date" type="date" id="reg-dob" name="dob">
              </div>
            </div>
            <div class="field">
              <label class="field__label" for="reg-phone">Phone Number</label>
              <div class="field__wrap">
                <svg class="field__icon" viewBox="0 0 20 20" fill="none"><path d="M4 4h3l1.5 3.5-2 1.2a9 9 0 004.8 4.8l1.2-2L16 13v3a1 1 0 01-1 1z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <input class="field__input" type="tel" id="reg-phone" name="phone" placeholder="+351 900 000 000">
              </div>
            </div>
          </div>

          <p class="form-section-label">Account</p>

          <div class="field">
            <label class="field__label" for="reg-username">Username</label>
            <div class="field__wrap">
              <svg class="field__icon" viewBox="0 0 20 20" fill="none"><path d="M10 2a4 4 0 00-4 4c0 2.21 1.79 4 4 4s4-1.79 4-4-1.79-4-4-4zm0 10c-4 0-7 2-7 4v1h14v-1c0-2-3-4-7-4z" stroke="currentColor" stroke-width="1.4" fill="none"/></svg>
              <input class="field__input" type="text" id="reg-username" name="username" placeholder="alexstone42" autocomplete="username" required>
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="reg-email">Email</label>
            <div class="field__wrap">
              <svg class="field__icon" viewBox="0 0 20 20" fill="none"><path d="M2.5 5.5A1.5 1.5 0 014 4h12a1.5 1.5 0 011.5 1.5v9A1.5 1.5 0 0116 16H4a1.5 1.5 0 01-1.5-1.5v-9z" stroke="currentColor" stroke-width="1.4"/><path d="M2.5 6l7.5 5 7.5-5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
              <input class="field__input" type="email" id="reg-email" name="email" placeholder="you@example.com" autocomplete="email" required>
            </div>
          </div>

          <div class="form__row">
            <div class="field">
              <label class="field__label" for="reg-password">Password</label>
              <div class="field__wrap">
                <svg class="field__icon" viewBox="0 0 20 20" fill="none"><rect x="4" y="9" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M7 9V6.5a3 3 0 016 0V9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="10" cy="13" r="1.2" fill="currentColor"/></svg>
                <input class="field__input" type="password" id="reg-password" name="password" placeholder="min. 8 characters" autocomplete="new-password" required>
              </div>
            </div>
            <div class="field">
              <label class="field__label" for="reg-password-confirm">Confirm Password</label>
              <div class="field__wrap">
                <svg class="field__icon" viewBox="0 0 20 20" fill="none"><rect x="4" y="9" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M7 9V6.5a3 3 0 016 0V9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="10" cy="13" r="1.2" fill="currentColor"/></svg>
                <input class="field__input" type="password" id="reg-password-confirm" name="password_confirm" placeholder="repeat password" autocomplete="new-password" required>
              </div>
            </div>
          </div>

          <!-- Trainer-specific fields -->
          <div class="trainer-section">
            <p class="form-section-label">Trainer Details</p>
            <div class="field">
              <label class="field__label" for="reg-specialty">Specialty</label>
              <div class="field__wrap field__wrap--select">
                <svg class="field__icon" viewBox="0 0 20 20" fill="none"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <select class="field__input field__input--select" id="reg-specialty" name="specialty">
                  <option value="" disabled selected>Select a specialty</option>
                  <option value="Strength &amp; Conditioning">Strength &amp; Conditioning</option>
                  <option value="Cardio &amp; Endurance">Cardio &amp; Endurance</option>
                  <option value="Yoga &amp; Flexibility">Yoga &amp; Flexibility</option>
                  <option value="CrossFit">CrossFit</option>
                  <option value="Pilates">Pilates</option>
                  <option value="Martial Arts">Martial Arts</option>
                  <option value="Nutrition &amp; Wellness">Nutrition &amp; Wellness</option>
                </select>
              </div>
            </div>
          </div>

          <label class="checkbox checkbox--terms">
            <input type="checkbox" id="terms" name="terms" required>
            <span class="checkbox__box"></span>
            <span class="checkbox__label">
              I agree to the <a href="#" class="field__link">Terms of Service</a> and <a href="#" class="field__link">Privacy Policy</a>
            </span>
          </label>

          <button class="btn btn-primary btn-block" type="submit">
            <span class="btn-submit__text">Create Account</span>
            <svg class="btn-submit__arrow" viewBox="0 0 20 20" fill="none">
              <path d="M4 10h12M12 6l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>

        </form>
      </div>
    </div>

    <footer class="auth-footer">&copy; W8 — All rights reserved</footer>
  </main>

</body>
</html>
