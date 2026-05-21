<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

require_admin();
$db = get_db();

$stmt = $db->query('SELECT id, reporter_id, subject, status, created_at FROM disputes ORDER BY created_at DESC');
$disputes = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Disputes</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <?php $current_page = 'admin_disputes'; require_once __DIR__ . '/../includes/nav.php'; ?>
  <main>
    <header>
      <h1>Disputes</h1>
      <p>User reports and admin actions</p>
    </header>

    <section>
      <?php if (empty($disputes)): ?>
        <p>No disputes found.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($disputes as $d): ?>
            <li>
              <strong>#<?= (int)$d['id'] ?></strong>
              <?= htmlspecialchars($d['subject'], ENT_QUOTES, 'UTF-8') ?> —
              <?= htmlspecialchars($d['status'], ENT_QUOTES, 'UTF-8') ?>
              <span style="color:var(--color-muted);"><?= htmlspecialchars($d['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
