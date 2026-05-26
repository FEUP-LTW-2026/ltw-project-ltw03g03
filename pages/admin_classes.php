<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$role = $admin['role'];
$db = get_db();

$stmt = $db->query(
    'SELECT c.id, c.name, c.type, c.level, c.capacity, c.trainer_id, c.is_active,
            u.first_name, u.last_name,
            COUNT(cs.id) AS upcoming_sessions
     FROM classes c
     LEFT JOIN users u ON u.id = c.trainer_id
     LEFT JOIN class_sessions cs ON cs.class_id = c.id
        AND cs.scheduled_at >= datetime("now")
        AND cs.status = "scheduled"
     GROUP BY c.id
     ORDER BY c.name'
);
$classes = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Classes</title>
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

  <?php $current_page = 'admin_classes'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="admin-layout">
    <header class="admin-header">
      <div>
        <div class="admin-header__sub">Admin Console</div>
        <h1 class="admin-header__title">Classes</h1>
      </div>
      <div class="admin-header__sub"><?= count($classes) ?> class types</div>
    </header>

    <section class="admin-section">
      <div class="admin-section__heading">
        <span class="admin-section__title">Class Catalog</span>
        <span class="admin-section__line"></span>
      </div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Type</th>
              <th>Level</th>
              <th>Capacity</th>
              <th>Trainer</th>
              <th>Upcoming</th>
              <th>Active</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($classes as $c): ?>
            <tr>
              <td><?= (int)$c['id'] ?></td>
              <td><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge badge--scheduled"><?= htmlspecialchars($c['type'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td class="muted"><?= htmlspecialchars($c['level'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="num"><?= (int)$c['capacity'] ?></td>
              <td><?= $c['trainer_id'] ? htmlspecialchars($c['first_name'] . ' ' . $c['last_name'], ENT_QUOTES, 'UTF-8') : '<span class="muted">Unassigned</span>' ?></td>
              <td class="num"><?= (int)$c['upcoming_sessions'] ?></td>
              <td><span class="badge <?= $c['is_active'] ? 'badge--active' : 'badge--inactive' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
