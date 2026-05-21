<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$trainer = require_trainer();
$db = get_db();
$uid = current_user_id();

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
</head>
<body>
  <?php $current_page = 'my_roster'; require_once __DIR__ . '/../includes/nav.php'; ?>
  <main>
    <header>
      <h1>My Roster</h1>
      <p>Enrolled members for upcoming sessions</p>
    </header>

    <section>
      <?php if (empty($by_session)): ?>
        <p>No roster entries found.</p>
      <?php else: ?>
        <?php foreach ($by_session as $sid => $data): ?>
          <article>
            <h2><?= htmlspecialchars($data['meta']['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><time datetime="<?= htmlspecialchars($data['meta']['scheduled_at']) ?>"><?= date('D, d M Y H:i', strtotime($data['meta']['scheduled_at'])) ?></time></p>
            <?php if (empty($data['members'])): ?>
              <p>No members enrolled yet.</p>
            <?php else: ?>
              <ul>
                <?php foreach ($data['members'] as $m): ?>
                  <li><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name'], ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
