<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user = require_login();
$db = get_db();

$trainer_id = (int)($_GET['trainer_id'] ?? 0);

if ($trainer_id <= 0) {
    // show generic info or prompt
    $trainer = null;
} else {
    $stmt = $db->prepare('SELECT id, first_name, last_name FROM users WHERE id = ? AND role = "trainer"');
    $stmt->execute([$trainer_id]);
    $trainer = $stmt->fetch();

    $slots = [];
    if ($trainer) {
        $stmt2 = $db->prepare('SELECT id, start_time, end_time, is_booked FROM pt_availability WHERE trainer_id = ? AND start_time > datetime("now") ORDER BY start_time ASC');
        $stmt2->execute([$trainer_id]);
        $slots = $stmt2->fetchAll();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>PT Bookings</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
</head>
<body>
  <?php $current_page = 'pt_bookings'; require_once __DIR__ . '/../includes/nav.php'; ?>
  <main>
    <header>
      <h1>Personal Training</h1>
      <?php if ($trainer): ?>
        <p>Available slots for <?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php else: ?>
        <p>Select a trainer to view available slots.</p>
      <?php endif; ?>
    </header>

    <section>
      <?php if (empty($slots)): ?>
        <p>No available PT slots right now.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($slots as $s): ?>
            <li>
              <time datetime="<?= htmlspecialchars($s['start_time']) ?>"><?= date('D, d M Y H:i', strtotime($s['start_time'])) ?></time>
              — <?= $s['is_booked'] ? 'Booked' : 'Available' ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
