<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user = require_login();
$db = get_db();
$uid = current_user_id();

$stmt = $db->prepare('SELECT id, logged_at, notes FROM workout_logs WHERE member_id = ? ORDER BY logged_at DESC');
$stmt->execute([$uid]);
$logs = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Workout Log</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
</head>
<body>
  <?php $current_page = 'workout_log'; require_once __DIR__ . '/../includes/nav.php'; ?>
  <main>
    <header>
      <h1>Workout Log</h1>
      <p>Your recent workout entries</p>
    </header>

    <section>
      <?php if (empty($logs)): ?>
        <p>No workout logs yet.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($logs as $l): ?>
            <li>
              <time datetime="<?= htmlspecialchars($l['logged_at']) ?>"><?= date('D, d M Y H:i', strtotime($l['logged_at'])) ?></time>
              — <?= nl2br(htmlspecialchars($l['notes'], ENT_QUOTES, 'UTF-8')) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
