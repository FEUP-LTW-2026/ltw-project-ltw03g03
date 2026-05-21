<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

require_admin();
$db = get_db();

$stmt = $db->query(
    'SELECT c.id, c.name, c.type, c.level, c.capacity, c.trainer_id, c.is_active FROM classes c ORDER BY c.name'
);
$classes = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Classes</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <?php $current_page = 'admin_classes'; require_once __DIR__ . '/../includes/nav.php'; ?>
  <main>
    <header>
      <h1>Classes</h1>
      <p>Manage class types and assignments</p>
    </header>

    <section>
      <table class="table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Type</th><th>Level</th><th>Capacity</th><th>Trainer</th><th>Active</th></tr>
        </thead>
        <tbody>
          <?php foreach ($classes as $c): ?>
          <tr>
            <td><?= (int)$c['id'] ?></td>
            <td><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($c['type'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($c['level'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)$c['capacity'] ?></td>
            <td><?= $c['trainer_id'] ? (int)$c['trainer_id'] : '-' ?></td>
            <td><?= $c['is_active'] ? 'Yes' : 'No' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
