<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$role = $admin['role'];
$db = get_db();

$stmt = $db->query(
    'SELECT MIN(e.id) AS id, e.name, e.category, MAX(e.updated_at) AS updated_at,
            COUNT(u.id) AS units,
            SUM(CASE WHEN u.status = "available" THEN 1 ELSE 0 END) AS available_units,
            SUM(CASE WHEN u.status = "maintenance" THEN 1 ELSE 0 END) AS maintenance_units
     FROM equipment e
     LEFT JOIN equipment_units u ON u.equipment_id = e.id AND u.status != "retired"
     GROUP BY lower(trim(e.name)), e.category
     ORDER BY e.category, e.name'
);
$items = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Equipment</title>
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

  <?php $current_page = 'admin_equipment'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="admin-layout">
    <header class="admin-header">
      <div>
        <div class="admin-header__sub">Admin Console</div>
        <h1 class="admin-header__title">Equipment</h1>
      </div>
      <div class="admin-header__sub"><?= count($items) ?> grouped equipment types</div>
    </header>

    <section class="admin-section">
      <div class="admin-section__heading">
        <span class="admin-section__title">Equipment Inventory</span>
        <span class="admin-section__line"></span>
      </div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Type ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Units</th>
              <th>Available</th>
              <th>Maintenance</th>
              <th>Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
              <td><?= (int)$it['id'] ?></td>
              <td><?= htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge badge--scheduled"><?= htmlspecialchars($it['category'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td class="num"><?= (int)$it['units'] ?></td>
              <td class="num"><?= (int)$it['available_units'] ?></td>
              <td class="num"><?= (int)$it['maintenance_units'] ?></td>
              <td class="muted"><?= htmlspecialchars(date('d M Y', strtotime($it['updated_at'])), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
