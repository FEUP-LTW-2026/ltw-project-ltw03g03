<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$trainer = require_trainer();
$role = $trainer['role'];
$db = get_db();
$uid = current_user_id();

$stmt = $db->prepare(
    'SELECT cs.id, cs.scheduled_at, c.name, c.type, c.duration_min, c.capacity,
            COUNT(e.id) AS enrolled
     FROM class_sessions cs
     JOIN classes c ON c.id = cs.class_id
     LEFT JOIN enrollments e ON e.session_id = cs.id AND e.status = "enrolled"
     WHERE c.trainer_id = ? AND cs.scheduled_at >= datetime("now")
     GROUP BY cs.id
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
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php $current_page = 'my_schedule'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="trainers-layout trainer-workspace">
    <header class="trainer-workspace__header">
      <div>
        <div class="tag">Trainer Area</div>
        <h1 class="title">My Schedule</h1>
      </div>
      <a href="my_roster.php" class="btn btn-primary">View Roster</a>
    </header>

    <section class="trainer-workspace__panel">
      <?php if (empty($sessions)): ?>
        <div class="trainers-empty">No upcoming sessions.</div>
      <?php else: ?>
        <div class="trainer-session-list">
          <?php foreach ($sessions as $s): ?>
            <article class="trainer-session-card">
              <div class="trainer-session-card__date">
                <time datetime="<?= htmlspecialchars($s['scheduled_at'], ENT_QUOTES, 'UTF-8') ?>"><?= date('D, d M', strtotime($s['scheduled_at'])) ?></time>
                <span><?= date('H:i', strtotime($s['scheduled_at'])) ?></span>
              </div>
              <div class="trainer-session-card__body">
                <div class="trainer-session-card__type"><?= htmlspecialchars($s['type'], ENT_QUOTES, 'UTF-8') ?></div>
                <h2><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= (int)$s['duration_min'] ?> min &middot; <?= (int)$s['enrolled'] ?> / <?= (int)$s['capacity'] ?> enrolled</p>
              </div>
              <a class="trainer-session-card__action" href="my_roster.php?session_id=<?= (int)$s['id'] ?>">Roster</a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
