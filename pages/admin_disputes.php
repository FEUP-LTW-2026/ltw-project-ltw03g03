<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$role = $admin['role'];
$db = get_db();

$stmt = $db->query(
    'SELECT d.id, d.reporter_id, d.subject, d.status, d.created_at,
            u.first_name, u.last_name
     FROM disputes d
     LEFT JOIN users u ON u.id = d.reporter_id
     ORDER BY d.created_at DESC'
);
$disputes = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Disputes</title>
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

  <?php $current_page = 'admin_disputes'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="admin-layout">
    <header class="admin-header">
      <div>
        <div class="admin-header__sub">Admin Console</div>
        <h1 class="admin-header__title">Disputes</h1>
      </div>
      <div class="admin-header__sub"><?= count($disputes) ?> reports</div>
    </header>

    <section class="admin-section">
      <div class="admin-section__heading">
        <span class="admin-section__title">User Reports</span>
        <span class="admin-section__line"></span>
      </div>

      <?php if (empty($disputes)): ?>
        <div class="admin-empty">No disputes found.</div>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Subject</th>
                <th>Reporter</th>
                <th>Status</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($disputes as $d): ?>
              <?php $reporter = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')) ?: 'User #' . $d['reporter_id']; ?>
              <tr>
                <td><?= (int)$d['id'] ?></td>
                <td><?= htmlspecialchars($d['subject'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($reporter, ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge badge--<?= htmlspecialchars($d['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(str_replace('_', ' ', $d['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td class="muted"><?= htmlspecialchars(date('d M Y', strtotime($d['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
