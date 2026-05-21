<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$db = get_db();

$stmt = $db->query(
    'SELECT id, username, first_name, last_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC'
);
$users = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Users</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <?php $current_page = 'admin_users'; require_once __DIR__ . '/../includes/nav.php'; ?>
  <main>
    <header>
      <h1>Users</h1>
      <p>Manage registered users</p>
    </header>

    <section>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Active</th>
            <th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
            <td><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
