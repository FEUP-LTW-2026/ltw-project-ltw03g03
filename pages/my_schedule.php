<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$trainer = require_trainer();
$db = get_db();
$uid = current_user_id();

$stmt = $db->prepare(
    'SELECT cs.id, cs.scheduled_at, c.name, c.duration_min
     FROM class_sessions cs
     JOIN classes c ON c.id = cs.class_id
     WHERE c.trainer_id = ? AND cs.scheduled_at >= datetime("now")
     ORDER BY cs.scheduled_at ASC'
);
$stmt->execute([$uid]);
$sessions = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Schedule</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/trainers.css">
</head>
<body>
  <?php $current_page = 'my_schedule'; require_once __DIR__ . '/../includes/nav.php'; ?>
  <main>
    <header>
      <h1>My Schedule</h1>
      <p>Upcoming sessions you're teaching</p>
    </header>

    <section>
      <?php if (empty($sessions)): ?>
        <p>No upcoming sessions.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($sessions as $s): ?>
            <li>
              <time datetime="<?= htmlspecialchars($s['scheduled_at']) ?>"><?= date('D, d M Y H:i', strtotime($s['scheduled_at'])) ?></time>
              — <?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)$s['duration_min'] ?> min)
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
