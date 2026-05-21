<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

require_admin();
$db = get_db();

$stmt = $db->query(
    'SELECT e.id, e.name, e.category, e.updated_at, COUNT(u.id) as units
     FROM equipment e
     LEFT JOIN equipment_units u ON u.equipment_id = e.id
     GROUP BY e.id
     ORDER BY e.name'
);
$items = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Equipment</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <?php $current_page = 'admin_equipment'; require_once __DIR__ . '/../includes/nav.php'; ?>
  <main>
    <header>
      <h1>Equipment</h1>
      <p>Overview of equipment types and units</p>
    </header>

    <section>
      <table class="table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Category</th><th>Units</th><th>Updated</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr>
            <td><?= (int)$it['id'] ?></td>
            <td><?= htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($it['category'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)$it['units'] ?></td>
            <td><?= htmlspecialchars($it['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
