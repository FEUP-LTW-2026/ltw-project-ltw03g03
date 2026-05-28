<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';


$trainer = require_trainer();
$role = $trainer['role'];
$db = get_db();
$uid = current_user_id();
$flash = get_flash();

$stmt = $db->prepare(
    'SELECT id, name, type, duration_min, capacity
     FROM classes
     WHERE trainer_id = ? AND is_active = 1
     ORDER BY name'
);
$stmt->execute([$uid]);
$assigned_classes = $stmt->fetchAll();

// Get group class sessions
$stmt = $db->prepare(
    'SELECT cs.id, cs.scheduled_at, cs.status, c.name, c.type, c.duration_min, c.capacity,
            (SELECT COUNT(*) FROM enrollments e WHERE e.session_id = cs.id AND e.status = "enrolled") AS enrolled
     FROM class_sessions cs
     JOIN classes c ON c.id = cs.class_id
     WHERE c.trainer_id = ?
       AND cs.scheduled_at >= datetime("now", "-1 day")
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
     WHERE pt.trainer_id = ?
       AND pt.scheduled_at >= datetime("now", "-1 day")
     ORDER BY pt.scheduled_at ASC'
);
$stmt->execute([$uid]);
$pt_sessions = $stmt->fetchAll();

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
        'status' => $s['status'],
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

    <?php if ($flash): ?>
      <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <section class="trainer-workspace__manager">
      <form class="trainer-schedule-form" method="post" action="../actions/trainer_schedule_action.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="create_class_session">
        <div class="trainer-schedule-form__body">
          <div class="field">
            <label class="field__label" for="class_id">Assigned Class</label>
            <select class="field__input" id="class_id" name="class_id" required <?= empty($assigned_classes) ? 'disabled' : '' ?>>
              <?php if (empty($assigned_classes)): ?>
                <option value="">No active classes assigned</option>
              <?php else: ?>
                <option value="">Choose class</option>
                <?php foreach ($assigned_classes as $class): ?>
                  <option value="<?= (int)$class['id'] ?>">
                    <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?>
                    (<?= (int)$class['duration_min'] ?> min)
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
          <div class="field">
            <label class="field__label" for="scheduled_at">Date and Time</label>
            <input class="field__input" id="scheduled_at" name="scheduled_at" type="datetime-local" required>
          </div>
          <button class="btn btn-primary" type="submit" <?= empty($assigned_classes) ? 'disabled' : '' ?>>Add Session</button>
        </div>
        <?php if (empty($assigned_classes)): ?>
          <p class="trainer-schedule-form__empty">
            Ask an admin to assign active classes to this trainer before adding sessions.
          </p>
        <?php endif; ?>
      </form>
    </section>

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
                      <p><?= (int)$s['enrolled'] ?> / <?= (int)$s['capacity'] ?> enrolled &middot; <?= htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="trainer-session-card__actions">
                      <a class="trainer-session-card__action" href="my_roster.php?session_id=<?= (int)$s['id'] ?>">Roster</a>
                      <?php if ($s['status'] === 'scheduled'): ?>
                        <?php if (strtotime($s['scheduled_at']) <= time()): ?>
                        <form method="post" action="../actions/trainer_schedule_action.php">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                          <input type="hidden" name="action" value="complete_class_session">
                          <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                          <button class="trainer-session-card__action" type="submit">Done</button>
                        </form>
                        <?php endif; ?>
                        <form method="post" action="../actions/trainer_schedule_action.php">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                          <input type="hidden" name="action" value="cancel_class_session">
                          <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                          <button class="trainer-session-card__action trainer-session-card__action--danger" type="submit">Cancel</button>
                        </form>
                      <?php endif; ?>
                    </div>
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
                      <p><?= htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8') ?> client session</p>
                    </div>
                    <div class="trainer-session-card__actions">
                      <?php
                        $pt_actions = [];
                        if ($s['status'] === 'pending') {
                            $pt_actions = ['confirmed' => 'Confirm', 'cancelled' => 'Cancel'];
                        } elseif ($s['status'] === 'confirmed') {
                            $pt_actions = ['cancelled' => 'Cancel'];
                            if (strtotime($s['scheduled_at']) <= time()) {
                                $pt_actions = ['completed' => 'Done'] + $pt_actions;
                            }
                        }
                      ?>
                      <?php foreach ($pt_actions as $status => $label): ?>
                        <form method="post" action="../actions/trainer_schedule_action.php">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                          <input type="hidden" name="action" value="update_pt_status">
                          <input type="hidden" name="booking_id" value="<?= (int)$s['id'] ?>">
                          <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                          <button class="trainer-session-card__action <?= $status === 'cancelled' ? 'trainer-session-card__action--danger' : 'trainer-session-card__action--pt' ?>" type="submit"><?= $label ?></button>
                        </form>
                      <?php endforeach; ?>
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
