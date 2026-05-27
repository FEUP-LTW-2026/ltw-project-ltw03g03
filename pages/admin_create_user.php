<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Create User</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const roleSelect = document.getElementById('new-role');
      const specialtyWrapper = document.getElementById('new-specialty-wrapper');
      
      if (roleSelect && specialtyWrapper) {
        roleSelect.addEventListener('change', function() {
          specialtyWrapper.style.display = this.value === 'trainer' ? 'block' : 'none';
        });
      }
    });
  </script>
</head>
<body>
  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php $current_page = 'admin_users'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="admin-layout">
    <header class="admin-header">
      <div>
        <div class="admin-header__sub">Admin Console</div>
        <h1 class="admin-header__title">Create User Account</h1>
      </div>
      <a href="admin_users.php" class="btn btn-ghost">← Back to Users</a>
    </header>

    <?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <section class="admin-section">
      <div class="admin-panel">
        <div class="admin-panel__block">
          <form class="form" method="post" action="../actions/do_register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form__row">
              <div class="field">
                <label class="field__label" for="new-firstname">First Name</label>
                <input class="field__input" id="new-firstname" name="firstname" type="text" required>
              </div>
              <div class="field">
                <label class="field__label" for="new-lastname">Last Name</label>
                <input class="field__input" id="new-lastname" name="lastname" type="text" required>
              </div>
            </div>

            <div class="form__row">
              <div class="field">
                <label class="field__label" for="new-email">Email</label>
                <input class="field__input" id="new-email" name="email" type="email" required>
              </div>
              <div class="field">
                <label class="field__label" for="new-phone">Phone</label>
                <input class="field__input" id="new-phone" name="phone" type="tel">
              </div>
            </div>

            <div class="form__row">
              <div class="field">
                <label class="field__label" for="new-password">Password</label>
                <input class="field__input" id="new-password" name="password" type="password" required>
              </div>
              <div class="field">
                <label class="field__label" for="new-password-confirm">Confirm Password</label>
                <input class="field__input" id="new-password-confirm" name="password_confirm" type="password" required>
              </div>
            </div>

            <div class="field">
              <label class="field__label" for="new-role">Role</label>
              <select class="field__input" id="new-role" name="role" required>
                <option value="member">Member</option>
                <option value="trainer">Trainer</option>
                <option value="admin">Admin</option>
              </select>
            </div>

            <div class="form__row">
              <div class="field">
                <label class="field__label" for="new-dob">Date of Birth</label>
                <input class="field__input" id="new-dob" name="dob" type="date">
              </div>
              <div class="field" id="new-specialty-wrapper" style="display: none;">
                <label class="field__label" for="new-specialty">Trainer Specialty</label>
                <select class="field__input" id="new-specialty" name="specialty">
                  <option value="" selected>None</option>
                  <option value="strength">Strength & Conditioning</option>
                  <option value="cardio">Cardio & Endurance</option>
                  <option value="yoga">Yoga & Flexibility</option>
                  <option value="crossfit">CrossFit</option>
                  <option value="pilates">Pilates</option>
                  <option value="martial_arts">Martial Arts</option>
                  <option value="nutrition">Nutrition & Wellness</option>
                </select>
              </div>
            </div>

            <div class="form__row">
              <button class="btn btn-primary" type="submit">Create User</button>
              <a href="admin_users.php" class="btn btn-ghost">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
