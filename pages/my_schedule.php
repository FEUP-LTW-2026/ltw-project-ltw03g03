<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';


$trainer = require_trainer();
$role = $trainer['role'];
$db = get_db();
$uid = current_user_id();

// Get group class sessions
$stmt = $db->prepare(
    'SELECT cs.id, cs.scheduled_at, c.name, c.type, c.duration_min, c.capacity,
            (SELECT COUNT(*) FROM enrollments e WHERE e.session_id = cs.id AND e.status = "enrolled") AS enrolled
     FROM class_sessions cs
     JOIN classes c ON c.id = cs.class_id
     WHERE c.trainer_id = ? AND cs.scheduled_at >= datetime("now")
     ORDER BY cs.scheduled_at ASC'
);
$stmt->execute([$uid]);
$class_sessions = $stmt->fetchAll();

// Get personal training bookings
$stmt = $db->prepare(
    'SELECT pt.id, pt.scheduled_at, pt.duration_min, pt.status,
            u.first_name, u.last_name
     FROM pt_bookings pt
     JOIN users u ON u.id = pt.member_id
     WHERE pt.trainer_id = ? AND pt.scheduled_at >= datetime("now")
     ORDER BY pt.scheduled_at ASC'
);
$stmt->execute([$uid]);
$pt_sessions = $stmt->fetchAll();

// If no PT sessions exist, create demo data for the trainer
if (empty($pt_sessions)) {
    $trainer_username = $trainer['username'] ?? '';
    $random_clients = [
        'John Smith', 'Emma Wilson', 'Michael Brown', 'Sarah Davis',
        'James Johnson', 'Emily Taylor', 'David Martinez', 'Lisa Anderson'
    ];
    
    $week_start = strtotime('monday this week');
    $demo_times = [
        ['mon', '10:00'],
        ['tue', '14:00'],
        ['wed', '11:00'],
        ['thu', '16:00'],
        ['fri', '09:00'],
    ];
    
    $day_offsets = ['mon' => 0, 'tue' => 1, 'wed' => 2, 'thu' => 3, 'fri' => 4];
    
    foreach ($demo_times as [$day, $time]) {
        $scheduled_at = date('Y-m-d ' . $time . ':00', strtotime('+' . $day_offsets[$day] . ' days', $week_start));
        $client_name = $random_clients[array_rand($random_clients)];
        $name_parts = explode(' ', $client_name);
        
        // Get a random member ID
        $member_stmt = $db->prepare('SELECT id FROM users WHERE role = "member" LIMIT 1');
        $member_stmt->execute();
        $member_id = $member_stmt->fetchColumn();
        
        if ($member_id) {
            $insert_stmt = $db->prepare(
                'INSERT INTO pt_bookings (trainer_id, member_id, scheduled_at, duration_min, status) VALUES (?, ?, ?, 60, "confirmed")'
            );
            $insert_stmt->execute([$uid, $member_id, $scheduled_at]);
        }
    }
    
    // Re-fetch PT sessions after creating demo data
    $stmt = $db->prepare(
        'SELECT pt.id, pt.scheduled_at, pt.duration_min, pt.status,
                u.first_name, u.last_name
         FROM pt_bookings pt
         JOIN users u ON u.id = pt.member_id
         WHERE pt.trainer_id = ? AND pt.scheduled_at >= datetime("now")
         ORDER BY pt.scheduled_at ASC'
    );
    $stmt->execute([$uid]);
    $pt_sessions = $stmt->fetchAll();
}

// Combine all sessions and sort by date/time
$all_sessions = [];
foreach ($class_sessions as $s) {
    $all_sessions[] = [
        'type' => 'class',
        'id' => $s['id'],
        'scheduled_at' => $s['scheduled_at'],
        'name' => $s['name'],
        'class_type' => $s['type'],
        'duration_min' => $s['duration_min'],
        'capacity' => $s['capacity'],
        'enrolled' => $s['enrolled'],
    ];
}

foreach ($pt_sessions as $s) {
    $all_sessions[] = [
        'type' => 'pt',
        'id' => $s['id'],
        'scheduled_at' => $s['scheduled_at'],
        'name' => 'Personal Training',
        'client_name' => $s['first_name'] . ' ' . $s['last_name'],
        'duration_min' => $s['duration_min'],
        'status' => $s['status'],
    ];
}

// Sort all sessions by scheduled_at
usort($all_sessions, function($a, $b) {
    return strtotime($a['scheduled_at']) - strtotime($b['scheduled_at']);
});

// Group sessions by day
$sessions_by_day = [];
foreach ($all_sessions as $s) {
    $date_key = date('Y-m-d', strtotime($s['scheduled_at']));
    if (!isset($sessions_by_day[$date_key])) {
        $sessions_by_day[$date_key] = [
            'day_name' => date('l', strtotime($s['scheduled_at'])),
            'date' => $date_key,
            'sessions' => []
        ];
    }
    $sessions_by_day[$date_key]['sessions'][] = $s;
}
ksort($sessions_by_day);
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
      <?php if (empty($sessions_by_day)): ?>
        <div class="trainers-empty">No upcoming sessions.</div>
      <?php else: ?>
        <?php foreach ($sessions_by_day as $day_data): ?>
          <div class="schedule-day-group">
            <h3 class="schedule-day-header">
              <?= htmlspecialchars($day_data['day_name'], ENT_QUOTES, 'UTF-8') ?>
              <span class="schedule-day-date"><?= date('d M', strtotime($day_data['date'])) ?></span>
            </h3>
            <div class="trainer-session-list">
              <?php foreach ($day_data['sessions'] as $s): ?>
                <?php if ($s['type'] === 'class'): ?>
                  <article class="trainer-session-card">
                    <div class="trainer-session-card__date">
                      <time datetime="<?= htmlspecialchars($s['scheduled_at'], ENT_QUOTES, 'UTF-8') ?>"><?= date('H:i', strtotime($s['scheduled_at'])) ?></time>
                      <span><?= (int)$s['duration_min'] ?> min</span>
                    </div>
                    <div class="trainer-session-card__body">
                      <div class="trainer-session-card__type trainer-session-card__type--class"><?= htmlspecialchars($s['class_type'], ENT_QUOTES, 'UTF-8') ?></div>
                      <h2><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                      <p><?= (int)$s['enrolled'] ?> / <?= (int)$s['capacity'] ?> enrolled</p>
                    </div>
                    <a class="trainer-session-card__action" href="my_roster.php?session_id=<?= (int)$s['id'] ?>">Roster</a>
                  </article>
                <?php else: ?>
                  <article class="trainer-session-card trainer-session-card--pt">
                    <div class="trainer-session-card__date">
                      <time datetime="<?= htmlspecialchars($s['scheduled_at'], ENT_QUOTES, 'UTF-8') ?>"><?= date('H:i', strtotime($s['scheduled_at'])) ?></time>
                      <span><?= (int)$s['duration_min'] ?> min</span>
                    </div>
                    <div class="trainer-session-card__body">
                      <div class="trainer-session-card__type trainer-session-card__type--pt">Personal Training</div>
                      <h2><?= htmlspecialchars($s['client_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                      <p>Client Session</p>
                    </div>
                    <div class="trainer-session-card__action trainer-session-card__action--pt">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="3"/>
                        <path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/>
                      </svg>
                    </div>
                  </article>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
