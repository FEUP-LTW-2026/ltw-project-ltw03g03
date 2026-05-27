<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$role = $admin['role'];
$db = get_db();

$stmt = $db->query(
    'SELECT id, username, first_name, last_name, email, phone, role, is_active, created_at
     FROM users
     ORDER BY created_at DESC'
);
$users = $stmt->fetchAll();

$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Users</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
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
        <h1 class="admin-header__title">Users</h1>
      </div>
      <div class="admin-header__sub"><?= count($users) ?> registered accounts</div>
    </header>

    <section class="admin-section">
      <div class="admin-section__heading">
        <span class="admin-section__title">Registered Users</span>
        <span class="admin-section__line"></span>
        <a href="admin_create_user.php" class="btn btn-primary">+ Create User</a>
      </div>

      <?php if ($flash): ?>
      <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
      <?php endif; ?>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Active</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge badge--role-<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><span class="badge <?= $u['is_active'] ? 'badge--active' : 'badge--inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
              <td class="muted"><?= htmlspecialchars(date('d M Y', strtotime($u['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="admin-table__actions">
                <a class="btn btn-secondary" href="admin_edit_user.php?id=<?= (int)$u['id'] ?>">Edit</a>
                <?php if ((int)$u['id'] !== (int)$admin['id']): ?>
                <form method="post" action="../actions/admin_toggle_user.php" style="display:inline; margin:0;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <button class="btn btn-ghost" type="submit"><?= $u['is_active'] ? 'Deactivate' : 'Reactivate' ?></button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
