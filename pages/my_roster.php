<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$trainer = require_trainer();
$role = $trainer['role'];
$db = get_db();
$uid = current_user_id();
$selected_session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

$stmt = $db->prepare(
    'SELECT cs.id AS session_id, cs.scheduled_at, c.name, u.id as member_id, u.first_name, u.last_name
     FROM class_sessions cs
     JOIN classes c ON c.id = cs.class_id
     LEFT JOIN enrollments e ON e.session_id = cs.id AND e.status = "enrolled"
     LEFT JOIN users u ON u.id = e.member_id
     WHERE c.trainer_id = ? AND cs.scheduled_at >= datetime("now")
     ORDER BY cs.scheduled_at ASC'
);
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();

$by_session = [];
foreach ($rows as $r) {
    $by_session[$r['session_id']]['meta'] = ['scheduled_at' => $r['scheduled_at'], 'name' => $r['name']];
    if ($r['member_id']) {
        $by_session[$r['session_id']]['members'][] = $r;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Roster</title>
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

  <?php $current_page = 'my_roster'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="trainers-layout trainer-workspace">
    <header class="trainer-workspace__header">
      <div>
        <div class="tag">Trainer Area</div>
        <h1 class="title">My Roster</h1>
      </div>
      <a href="my_schedule.php" class="btn btn-primary">My Schedule</a>
    </header>

    <section class="trainer-roster-grid">
      <?php if (empty($by_session)): ?>
        <div class="trainers-empty">No roster entries found.</div>
      <?php else: ?>
        <?php foreach ($by_session as $sid => $data): ?>
          <?php if ($selected_session_id && $selected_session_id !== (int)$sid) continue; ?>
          <article class="trainer-roster-card">
            <header class="trainer-roster-card__header">
              <div>
                <div class="trainer-session-card__type">Session</div>
                <h2><?= htmlspecialchars($data['meta']['name'], ENT_QUOTES, 'UTF-8') ?></h2>
              </div>
              <time datetime="<?= htmlspecialchars($data['meta']['scheduled_at'], ENT_QUOTES, 'UTF-8') ?>"><?= date('D, d M H:i', strtotime($data['meta']['scheduled_at'])) ?></time>
            </header>

            <?php if (empty($data['members'])): ?>
              <div class="trainer-roster-card__empty">No members enrolled yet.</div>
            <?php else: ?>
              <div class="trainer-member-list">
                <?php foreach ($data['members'] as $m): ?>
                  <div class="trainer-member">
                    <span><?= htmlspecialchars(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1), ENT_QUOTES, 'UTF-8') ?></span>
                    <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
