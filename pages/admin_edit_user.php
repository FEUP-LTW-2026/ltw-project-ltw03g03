<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$db = get_db();

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    set_flash('error', 'Invalid user ID.');
    header('Location: admin_users.php');
    exit;
}

$stmt = $db->prepare('SELECT id, username, email, first_name, last_name, phone, role, is_active FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'User not found.');
    header('Location: admin_users.php');
    exit;
}

$specialty = null;
if ($user['role'] === 'trainer') {
    $specStmt = $db->prepare('SELECT specialty FROM trainer_profiles WHERE user_id = ?');
    $specStmt->execute([$userId]);
    $trainerProfile = $specStmt->fetch();
    if ($trainerProfile) {
        $specialty = $trainerProfile['specialty'];
    }
}

$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Edit User</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const roleSelect = document.getElementById('edit-role');
      const specialtyWrapper = document.getElementById('edit-specialty-wrapper');
      
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
        <h1 class="admin-header__title">Edit Account: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8') ?></h1>
      </div>
      <a href="admin_users.php" class="btn btn-ghost">← Back to Users</a>
    </header>

    <?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <section class="admin-section">
      <div class="admin-panel admin-panel--split">
        <div class="admin-panel__block">
          <h2 class="admin-panel__title">Account Information</h2>
          <form class="form" method="post" action="../actions/admin_update_user.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

            <div class="form__row">
              <div class="field">
                <label class="field__label" for="edit-firstname">First Name</label>
                <input class="field__input" id="edit-firstname" name="first_name" type="text" value="<?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
              </div>
              <div class="field">
                <label class="field__label" for="edit-lastname">Last Name</label>
                <input class="field__input" id="edit-lastname" name="last_name" type="text" value="<?= htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8') ?>" required>
              </div>
            </div>

            <div class="form__row">
              <div class="field">
                <label class="field__label" for="edit-email">Email</label>
                <input class="field__input" id="edit-email" name="email" type="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" required>
              </div>
              <div class="field">
                <label class="field__label" for="edit-phone">Phone</label>
                <input class="field__input" id="edit-phone" name="phone" type="tel" value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>

            <div class="field">
              <label class="field__label" for="edit-role">Role</label>
              <select class="field__input" id="edit-role" name="role" required>
                <option value="member" <?= $user['role'] === 'member' ? 'selected' : '' ?>>Member</option>
                <option value="trainer" <?= $user['role'] === 'trainer' ? 'selected' : '' ?>>Trainer</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
              </select>
            </div>

            <div class="field" id="edit-specialty-wrapper" style="display: <?= $user['role'] === 'trainer' ? 'block' : 'none' ?>;">
              <label class="field__label" for="edit-specialty">Trainer Specialty</label>
              <select class="field__input" id="edit-specialty" name="specialty">
                <option value="" <?= empty($specialty) ? 'selected' : '' ?>>None</option>
                <option value="strength" <?= $specialty === 'strength' ? 'selected' : '' ?>>Strength & Conditioning</option>
                <option value="cardio" <?= $specialty === 'cardio' ? 'selected' : '' ?>>Cardio & Endurance</option>
                <option value="yoga" <?= $specialty === 'yoga' ? 'selected' : '' ?>>Yoga & Flexibility</option>
                <option value="crossfit" <?= $specialty === 'crossfit' ? 'selected' : '' ?>>CrossFit</option>
                <option value="pilates" <?= $specialty === 'pilates' ? 'selected' : '' ?>>Pilates</option>
                <option value="martial_arts" <?= $specialty === 'martial_arts' ? 'selected' : '' ?>>Martial Arts</option>
                <option value="nutrition" <?= $specialty === 'nutrition' ? 'selected' : '' ?>>Nutrition & Wellness</option>
              </select>
            </div>

            <div class="form__row">
              <button class="btn btn-primary" type="submit">Save Changes</button>
              <a href="admin_users.php" class="btn btn-ghost">Cancel</a>
            </div>
          </form>
        </div>

        <div class="admin-panel__block">
          <h2 class="admin-panel__title">Account Status</h2>
          
          <div style="margin-bottom: 1.5rem;">
            <p class="form-section-label">Status</p>
            <p><?= $user['is_active'] ? '<span class="badge badge--active">Active</span>' : '<span class="badge badge--inactive">Inactive</span>' ?></p>
          </div>

          <div style="margin-bottom: 1.5rem;">
            <p class="form-section-label">User ID</p>
            <p><?= (int)$user['id'] ?></p>
          </div>

          <div style="margin-bottom: 1.5rem;">
            <p class="form-section-label">Username</p>
            <p><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>

          <?php if ((int)$user['id'] !== (int)$admin['id']): ?>
          <hr style="margin: 1.5rem 0;">
          
          <form method="post" action="../actions/admin_toggle_user.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
            <button class="btn btn-ghost" type="submit">
              <?= $user['is_active'] ? 'Deactivate Account' : 'Reactivate Account' ?>
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
