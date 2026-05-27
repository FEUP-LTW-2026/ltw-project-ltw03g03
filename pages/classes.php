<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

function ensure_demo_weekly_schedule(PDO $db): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    $trainers = [
        'ronniec' => ['email' => 'ronnie@w8gym.com', 'first' => 'Ronnie', 'last' => 'Coleman', 'phone' => '555-1001', 'photo' => 'images/ronnie.jpg', 'specialty' => 'strength', 'bio' => '8 time Mr. Olympia, now focused on strength training.', 'certs' => 'Strength coaching, powerlifting technique, contest prep', 'years' => 8],
        'sportacus' => ['email' => 'sportacus@w8gym.com', 'first' => 'Sportacus', 'last' => '', 'phone' => '555-1002', 'photo' => 'images/sportacus.jpg', 'specialty' => 'cardio', 'bio' => 'Aerobics world champion focused on performance and conditioning.', 'certs' => 'HIIT coaching, mobility, athletic conditioning', 'years' => 10],
        'popeye' => ['email' => 'popeye@w8gym.com', 'first' => 'Popeye', 'last' => '', 'phone' => '555-1003', 'photo' => 'images/popeye.jpg', 'specialty' => 'crossfit', 'bio' => 'Specialist in movement quality and injury prevention.', 'certs' => 'CrossFit L2, Olympic lifting, movement screening', 'years' => 6],
        'oogway' => ['email' => 'oogway@w8gym.com', 'first' => 'Master', 'last' => 'Oogway', 'phone' => '555-1004', 'photo' => 'images/oogway.jpg', 'specialty' => 'yoga', 'bio' => 'Yoga instructor focused on recovery, breathwork and relaxation.', 'certs' => 'RYT-200, recovery coaching, breathwork', 'years' => 12],
    ];

    $db->beginTransaction();
    try {
        foreach (['sarahj' => 'ronniec', 'mikec' => 'sportacus', 'emmar' => 'popeye', 'davidw' => 'oogway'] as $legacy => $target) {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$legacy]);
            $legacy_id = $stmt->fetchColumn();
            $target_stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
            $target_stmt->execute([$target]);
            if ($legacy_id && !$target_stmt->fetchColumn()) {
                $t = $trainers[$target];
                $db->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, photo_path = ?, role = "trainer", is_active = 1 WHERE username = ?')
                   ->execute([$t['first'], $t['last'], $t['email'], $t['phone'], $t['photo'], $legacy]);
                $db->prepare('UPDATE users SET username = ? WHERE username = ? AND NOT EXISTS (SELECT 1 FROM users WHERE username = ?)')
                   ->execute([$target, $legacy, $target]);
            }
        }

        $trainer_ids = [];
        foreach ($trainers as $username => $t) {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $t['email']]);
            $id = $stmt->fetchColumn();
            if (!$id) {
                $db->prepare('INSERT INTO users (username, email, password_hash, first_name, last_name, phone, photo_path, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, "trainer", 1)')
                   ->execute([$username, $t['email'], $hash, $t['first'], $t['last'], $t['phone'], $t['photo']]);
                $id = (int)$db->lastInsertId();
            } else {
                $id = (int)$id;
                $db->prepare('UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ?, photo_path = ?, role = "trainer", is_active = 1 WHERE id = ?')
                   ->execute([$username, $t['email'], $t['first'], $t['last'], $t['phone'], $t['photo'], $id]);
            }

            $trainer_ids[$username] = $id;
            $profile = $db->prepare('SELECT user_id FROM trainer_profiles WHERE user_id = ?');
            $profile->execute([$id]);
            if ($profile->fetchColumn()) {
                $db->prepare('UPDATE trainer_profiles SET bio = ?, specialty = ?, certifications = ?, years_experience = ? WHERE user_id = ?')
                   ->execute([$t['bio'], $t['specialty'], $t['certs'], $t['years'], $id]);
            } else {
                $db->prepare('INSERT INTO trainer_profiles (user_id, bio, specialty, certifications, years_experience) VALUES (?, ?, ?, ?, ?)')
                   ->execute([$id, $t['bio'], $t['specialty'], $t['certs'], $t['years']]);
            }
        }

        $classes = [
            ['Strength Foundations', 'powerlifting', 'beginner', 'Learn strong squat, hinge, press, and bracing fundamentals.', 60, 18, 'ronniec'],
            ['Powerlifting Technique', 'powerlifting', 'intermediate', 'Build efficient squat, bench, and deadlift patterns.', 60, 16, 'ronniec'],
            ['Advanced Powerlifting', 'powerlifting', 'advanced', 'Heavy strength work for experienced lifters chasing bigger totals.', 75, 14, 'ronniec'],
            ['HIIT Starter', 'hiit', 'beginner', 'Low-complexity intervals for building confidence and conditioning.', 45, 20, 'sportacus'],
            ['HIIT Burn', 'hiit', 'intermediate', 'Fast intervals, athletic circuits, and controlled intensity.', 45, 20, 'sportacus'],
            ['Athletic Conditioning', 'hiit', 'advanced', 'Demanding speed, agility, and endurance blocks.', 50, 18, 'sportacus'],
            ['CrossFit Foundations', 'crossfit', 'beginner', 'Learn functional movements and safe scaling options.', 60, 16, 'popeye'],
            ['MetCon Engine', 'crossfit', 'intermediate', 'Mixed modal conditioning with barbell, bodyweight, and cardio work.', 60, 18, 'popeye'],
            ['CrossFit Competition', 'crossfit', 'advanced', 'High-skill workouts for athletes comfortable with intensity.', 60, 14, 'popeye'],
            ['Gentle Yoga', 'yoga', 'beginner', 'A calm practice for mobility, balance, and breath.', 60, 22, 'oogway'],
            ['Power Yoga Flow', 'yoga', 'intermediate', 'A stronger vinyasa flow that builds heat and control.', 60, 20, 'oogway'],
            ['Mobility Yoga', 'yoga', 'advanced', 'Deep mobility work for experienced movers and heavy training recovery.', 60, 18, 'oogway'],
        ];

        $class_ids = [];
        foreach ($classes as [$name, $type, $level, $description, $duration, $capacity, $trainer_username]) {
            $stmt = $db->prepare('SELECT id FROM classes WHERE name = ? LIMIT 1');
            $stmt->execute([$name]);
            $class_id = $stmt->fetchColumn();
            if (!$class_id) {
                $db->prepare('INSERT INTO classes (name, type, level, description, duration_min, capacity, trainer_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)')
                   ->execute([$name, $type, $level, $description, $duration, $capacity, $trainer_ids[$trainer_username]]);
                $class_id = (int)$db->lastInsertId();
            } else {
                $class_id = (int)$class_id;
                $db->prepare('UPDATE classes SET type = ?, level = ?, description = ?, duration_min = ?, capacity = ?, trainer_id = ?, is_active = 1 WHERE id = ?')
                   ->execute([$type, $level, $description, $duration, $capacity, $trainer_ids[$trainer_username], $class_id]);
            }
            $class_ids[$name] = $class_id;
        }

        foreach (['yoga' => 'oogway', 'hiit' => 'sportacus', 'crossfit' => 'popeye', 'powerlifting' => 'ronniec'] as $type => $trainer_username) {
            $db->prepare('UPDATE classes SET trainer_id = ? WHERE type = ? AND is_active = 1')
               ->execute([$trainer_ids[$trainer_username], $type]);
        }

        $week_start = strtotime('monday next week');
        $day_offsets = ['mon' => 0, 'tue' => 1, 'wed' => 2, 'thu' => 3, 'fri' => 4, 'sat' => 5];
        $plan = [
            ['mon', '09:00', 'Strength Foundations'], ['mon', '11:00', 'HIIT Starter'], ['mon', '15:00', 'CrossFit Foundations'], ['mon', '18:00', 'Gentle Yoga'],
            ['tue', '09:00', 'Power Yoga Flow'], ['tue', '11:00', 'Powerlifting Technique'], ['tue', '15:00', 'HIIT Burn'], ['tue', '18:00', 'MetCon Engine'],
            ['wed', '09:00', 'CrossFit Foundations'], ['wed', '11:00', 'Mobility Yoga'], ['wed', '15:00', 'Advanced Powerlifting'], ['wed', '18:00', 'Athletic Conditioning'],
            ['thu', '09:00', 'HIIT Starter'], ['thu', '11:00', 'MetCon Engine'], ['thu', '15:00', 'Gentle Yoga'], ['thu', '18:00', 'Strength Foundations'],
            ['fri', '09:00', 'Powerlifting Technique'], ['fri', '11:00', 'HIIT Burn'], ['fri', '15:00', 'Power Yoga Flow'], ['fri', '18:00', 'CrossFit Competition'],
            ['sat', '09:00', 'Mobility Yoga'], ['sat', '11:00', 'Advanced Powerlifting'], ['sat', '15:00', 'CrossFit Foundations'], ['sat', '18:00', 'Athletic Conditioning'],
        ];
        $planned = [];

        foreach ($plan as [$day, $time, $class_name]) {
            $scheduled_at = date('Y-m-d ' . $time . ':00', strtotime('+' . $day_offsets[$day] . ' days', $week_start));
            $class_id = $class_ids[$class_name];
            $planned[] = $class_id . '|' . $scheduled_at;
            $stmt = $db->prepare('SELECT id FROM class_sessions WHERE class_id = ? AND scheduled_at = ? LIMIT 1');
            $stmt->execute([$class_id, $scheduled_at]);
            if ($stmt->fetchColumn()) {
                $db->prepare('UPDATE class_sessions SET status = "scheduled" WHERE class_id = ? AND scheduled_at = ?')->execute([$class_id, $scheduled_at]);
            } else {
                $db->prepare('INSERT INTO class_sessions (class_id, scheduled_at, status) VALUES (?, ?, "scheduled")')->execute([$class_id, $scheduled_at]);
            }
        }

        $managed_class_ids = array_values($class_ids);
        if ($managed_class_ids) {
            $placeholders = implode(',', array_fill(0, count($managed_class_ids), '?'));
            $start = date('Y-m-d 00:00:00', $week_start);
            $end = date('Y-m-d 00:00:00', strtotime('+7 days', $week_start));
            $stmt = $db->prepare("SELECT id, class_id, scheduled_at FROM class_sessions WHERE class_id IN ($placeholders) AND scheduled_at >= ? AND scheduled_at < ? AND status = 'scheduled'");
            $stmt->execute(array_merge($managed_class_ids, [$start, $end]));
            foreach ($stmt->fetchAll() as $session) {
                if (!in_array($session['class_id'] . '|' . $session['scheduled_at'], $planned, true)) {
                    $db->prepare('UPDATE class_sessions SET status = "cancelled" WHERE id = ?')->execute([$session['id']]);
                }
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
    }
}

ensure_demo_weekly_schedule($db);

// ── Helper functions for CSRF-protected URLs ───────────────────
function enroll_url($session_id) {
    return "../actions/enroll.php?session_id={$session_id}&csrf_token=" . $_SESSION['csrf_token'];
}

function cancel_url($session_id) {
    return "../actions/cancel_enrollment.php?session_id={$session_id}&csrf_token=" . $_SESSION['csrf_token'];
}

function selected_values(string $param, array $allowed): array {
    $raw = $_GET[$param] ?? '';
    if (is_array($raw)) {
        $parts = $raw;
    } else {
        $parts = explode(',', (string)$raw);
    }

    $values = [];
    foreach ($parts as $value) {
        $value = trim((string)$value);
        if ($value !== '' && $value !== 'all' && in_array($value, $allowed, true)) {
            $values[] = $value;
        }
    }

    return array_values(array_unique($values));
}

function selected_int_values(string $param, array $allowed): array {
    $raw = $_GET[$param] ?? '';
    if (is_array($raw)) {
        $parts = $raw;
    } else {
        $parts = explode(',', (string)$raw);
    }

    $values = [];
    foreach ($parts as $value) {
        $value = (int)$value;
        if ($value > 0 && in_array($value, $allowed, true)) {
            $values[] = $value;
        }
    }

    return array_values(array_unique($values));
}

function add_in_filter(array &$where, array &$params, string $column, array $values): void {
    if (!$values) return;
    $where[] = $column . ' IN (' . implode(',', array_fill(0, count($values), '?')) . ')';
    foreach ($values as $value) {
        $params[] = $value;
    }
}

$valid_types = ['powerlifting', 'hiit', 'crossfit', 'yoga'];
$valid_levels = ['beginner', 'intermediate', 'advanced'];
$valid_days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
$filter_types = selected_values('type', $valid_types);
$filter_levels = selected_values('level', $valid_levels);
$filter_days = selected_values('day', $valid_days);

// ── Fetch all active trainers for the filter bar ──────────────
$trainers = $db->query(
    'SELECT u.id, u.first_name, u.last_name FROM users u
     JOIN classes c ON c.trainer_id = u.id
     WHERE u.role = "trainer" AND u.is_active = 1 AND c.is_active = 1
     GROUP BY u.id
     ORDER BY CASE u.username
                WHEN "ronniec" THEN 1
                WHEN "sportacus" THEN 2
                WHEN "popeye" THEN 3
                WHEN "oogway" THEN 4
                ELSE 5
              END,
              u.first_name'
)->fetchAll();
$valid_trainer_ids = array_map(static fn($t) => (int)$t['id'], $trainers);
$filter_trainers = selected_int_values('trainer', $valid_trainer_ids);

// ── Build session query with filters ─────────────────────────
$week_start_ts = strtotime('monday next week');
$week_end_ts = strtotime('+7 days', $week_start_ts);
$week_start = date('Y-m-d 00:00:00', $week_start_ts);
$week_end = date('Y-m-d 00:00:00', $week_end_ts);
$where  = ['cs.scheduled_at >= ?', 'cs.scheduled_at < ?', 'cs.status = "scheduled"', 'c.is_active = 1'];
$params = [$week_start, $week_end];

add_in_filter($where, $params, 'c.type', $filter_types);
add_in_filter($where, $params, 'c.level', $filter_levels);
add_in_filter($where, $params, 'c.trainer_id', $filter_trainers);

if ($filter_days) {
    $day_map = ['mon'=>'1','tue'=>'2','wed'=>'3','thu'=>'4','fri'=>'5','sat'=>'6'];
    $day_values = [];
    foreach ($filter_days as $day) {
        $day_values[] = $day_map[$day];
    }
    add_in_filter($where, $params, "strftime('%w', cs.scheduled_at)", $day_values);
}

$sql = '
    SELECT cs.id as session_id,
           cs.scheduled_at,
           cs.status,
           c.id as class_id,
           c.name,
           c.type,
           c.level,
           c.description,
           c.duration_min,
           c.capacity,
           u.id as trainer_id,
           u.first_name as trainer_first,
           u.last_name  as trainer_last,
           u.photo_path as trainer_photo,
           COUNT(CASE WHEN e.status = "enrolled" THEN 1 END) as enrolled_count,
           MAX(CASE WHEN e.member_id = ? AND e.status = "enrolled" THEN 1 ELSE 0 END) as i_am_enrolled,
           MAX(CASE WHEN e.member_id = ? AND e.status = "waitlist" THEN 1 ELSE 0 END) as i_am_waitlisted
    FROM class_sessions cs
    JOIN classes c ON c.id = cs.class_id
    LEFT JOIN users u ON u.id = c.trainer_id
    LEFT JOIN enrollments e ON e.session_id = cs.id
    WHERE ' . implode(' AND ', $where) . '
    GROUP BY cs.id
    ORDER BY cs.scheduled_at ASC
    LIMIT 200
';

$stmt = $db->prepare($sql);
$stmt->execute(array_merge([$user['id'], $user['id']], $params));
$sessions = $stmt->fetchAll();

// ── Group sessions by date ────────────────────────────────────
$week_days = [];
foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat'] as $offset => $key) {
    $date = date('Y-m-d', strtotime('+' . $offset . ' days', $week_start_ts));
    $week_days[$key] = [
        'date' => $date,
        'label' => date('l, d F', strtotime($date)),
        'sessions' => [],
    ];
}
foreach ($sessions as $s) {
    $day_key = strtolower(date('D', strtotime($s['scheduled_at'])));
    if (isset($week_days[$day_key])) {
        $week_days[$day_key]['sessions'][] = $s;
    }
}

// ── Handle review modal param ─────────────────────────────────
$review_session_id = (int)($_GET['review'] ?? 0);
$review_session    = null;
if ($review_session_id && $role === 'member') {
    $stmt = $db->prepare(
        'SELECT cs.id, c.name, cs.scheduled_at FROM class_sessions cs
         JOIN classes c ON c.id = cs.class_id
         JOIN enrollments e ON e.session_id = cs.id
         WHERE cs.id = ? AND e.member_id = ? AND e.status = "attended"
         LIMIT 1'
    );
    $stmt->execute([$review_session_id, $user['id']]);
    $review_session = $stmt->fetch();
}

$type_colors = [
    'powerlifting' => '#f5681a',
    'hiit'         => '#e84040',
    'crossfit'     => '#c0a030',
    'yoga'         => '#42a882',
    'other'        => '#7a7f91',
];
$level_classes = [
    'all'          => 'sched-card__level--all',
    'beginner'     => 'sched-card__level--beginner',
    'intermediate' => 'sched-card__level--intermediate',
    'advanced'     => 'sched-card__level--advanced',
];

function is_active_filter(string $param, string $value): string {
    $allowed = [
        'type' => ['powerlifting', 'hiit', 'crossfit', 'yoga'],
        'level' => ['beginner', 'intermediate', 'advanced'],
        'day' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
    ];
    $selected = selected_values($param, $allowed[$param] ?? []);
    return ($value === 'all' ? empty($selected) : in_array($value, $selected, true)) ? 'chip--active' : '';
}

function is_active_trainer_filter(int $trainer_id): string {
    global $valid_trainer_ids;
    $selected = selected_int_values('trainer', $valid_trainer_ids);
    return in_array($trainer_id, $selected, true) ? 'chip--active' : '';
}

function filter_url(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === 'all' || $v === '' || $v === '0' || $v === 0) unset($params[$k]);
    }
    return 'classes.php' . ($params ? '?' . http_build_query($params) : '');
}

function toggle_filter_url(string $param, string $value): string {
    $allowed = [
        'type' => ['powerlifting', 'hiit', 'crossfit', 'yoga'],
        'level' => ['beginner', 'intermediate', 'advanced'],
        'day' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
    ];
    $selected = selected_values($param, $allowed[$param] ?? []);
    if (in_array($value, $selected, true)) {
        $selected = array_values(array_diff($selected, [$value]));
    } else {
        $selected[] = $value;
    }
    return filter_url([$param => $selected ? implode(',', $selected) : 'all']);
}

function toggle_trainer_url(int $trainer_id): string {
    global $valid_trainer_ids;
    $selected = selected_int_values('trainer', $valid_trainer_ids);
    if (in_array($trainer_id, $selected, true)) {
        $selected = array_values(array_diff($selected, [$trainer_id]));
    } else {
        $selected[] = $trainer_id;
    }
    return filter_url(['trainer' => $selected ? implode(',', $selected) : 0]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Class Schedule</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/classes.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">

</head>
<body>

  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php 
    $current_page = 'classes';
    require_once __DIR__ . '/../includes/nav.php'; 
  ?>

  <!-- Flash -->
  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" style="margin-top:80px;padding-left:2rem;">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <!-- Page Header -->
  <main>
    <header class="page-header">
      <div class="page-header__inner">
        <div class="tag">Schedule</div>
        <h1 class="page-header__title">Class<br><span>Schedule</span></h1>
        <p class="page-header__sub">Browse, filter, and book your next session</p>
      </div>
      <div class="page-header__deco">CLASSES</div>
    </header>

    <section class="filters-bar">
      <div class="filters-bar__inner">
        <div class="filter-group">
          <span class="filter-group__label">Type</span>
          <div class="filter-chips">
            <a href="<?= filter_url(['type'=>'all']) ?>" class="chip <?= is_active_filter('type','all') ?>">All</a>
            <a href="<?= toggle_filter_url('type','powerlifting') ?>" class="chip <?= is_active_filter('type','powerlifting') ?>">Powerlifting</a>
            <a href="<?= toggle_filter_url('type','hiit') ?>" class="chip <?= is_active_filter('type','hiit') ?>">HIIT</a>
            <a href="<?= toggle_filter_url('type','crossfit') ?>" class="chip <?= is_active_filter('type','crossfit') ?>">CrossFit</a>
            <a href="<?= toggle_filter_url('type','yoga') ?>" class="chip <?= is_active_filter('type','yoga') ?>">Yoga</a>
          </div>
        </div>

        <!-- Level -->
        <div class="filter-group">
          <span class="filter-group__label">Level</span>
          <div class="filter-chips">
            <a href="<?= filter_url(['level'=>'all']) ?>" class="chip <?= is_active_filter('level','all') ?>">All</a>
            <a href="<?= toggle_filter_url('level','beginner') ?>" class="chip <?= is_active_filter('level','beginner') ?>">Beginner</a>
            <a href="<?= toggle_filter_url('level','intermediate') ?>" class="chip <?= is_active_filter('level','intermediate') ?>">Intermediate</a>
            <a href="<?= toggle_filter_url('level','advanced') ?>" class="chip <?= is_active_filter('level','advanced') ?>">Advanced</a>
          </div>
        </div>

        <!-- Day -->
        <div class="filter-group">
          <span class="filter-group__label">Day</span>
          <div class="filter-chips">
            <a href="<?= filter_url(['day'=>'all']) ?>" class="chip <?= is_active_filter('day','all') ?>">All</a>
            <?php foreach (['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat'] as $k=>$v): ?>
            <a href="<?= toggle_filter_url('day',$k) ?>" class="chip <?= is_active_filter('day',$k) ?>"><?= $v ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Trainer -->
        <?php if ($trainers): ?>
        <div class="filter-group">
          <span class="filter-group__label">Trainer</span>
          <div class="filter-chips">
            <a href="<?= filter_url(['trainer'=>0]) ?>" class="chip <?= empty($filter_trainers) ? 'chip--active' : '' ?>">All</a>
            <?php foreach ($trainers as $t): ?>
            <a href="<?= toggle_trainer_url((int)$t['id']) ?>" class="chip <?= is_active_trainer_filter((int)$t['id']) ?>">
              <?= htmlspecialchars(trim($t['first_name'] . ' ' . $t['last_name']), ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="sched-layout">
      <div class="inner" style="padding-top:3rem;padding-bottom:6rem;">
        <?php if (empty($sessions)): ?>
          <p style="color:var(--subtle);font-size:1rem;text-align:center;padding:4rem 0;">
            No classes match your filters. <a href="classes.php">Clear all filters →</a>
          </p>
        <?php endif; ?>

        <?php foreach ($week_days as $day): ?>
        <?php $day_sessions = $day['sessions']; ?>
        <section class="day-block">
          <div class="day-label">
            <span class="day-label__name"><?= htmlspecialchars($day['label'], ENT_QUOTES, 'UTF-8') ?></span>
            <div class="day-label__line"></div>
            <span class="day-label__count"><?= count($day_sessions) ?> class<?= count($day_sessions) != 1 ? 'es' : '' ?></span>
          </div>

          <?php if (empty($day_sessions)): ?>
          <p class="day-empty">No classes match these filters on this day.</p>
          <?php else: ?>
          <div class="schedule-grid">
            <?php foreach ($day_sessions as $s):
              $spots_left = $s['capacity'] - $s['enrolled_count'];
              $is_full    = $spots_left <= 0;
              $low_spots  = !$is_full && $spots_left <= 3;
            ?>
            <article class="sched-card <?= $is_full ? 'sched-card--full' : '' ?>">
              <div class="sched-card__bar sched-card__bar--<?= $s['type'] ?>"></div>
              <div class="sched-card__time">
                <span class="sched-card__hour"><?= date('H:i', strtotime($s['scheduled_at'])) ?></span>
                <span class="sched-card__dur"><?= $s['duration_min'] ?>min</span>
              </div>
              <div class="sched-card__body">
                <div class="sched-card__type"><?= ucfirst($s['type']) ?></div>
                <div class="sched-card__name"><?= htmlspecialchars($s['name']) ?></div>
                <div class="sched-card__trainer">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                  <?= htmlspecialchars(trim($s['trainer_first'] . ' ' . $s['trainer_last']), ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>
              <div class="sched-card__meta">
                <span class="sched-card__level <?= $level_classes[$s['level']] ?? '' ?>">
                  <?= ucfirst($s['level']) ?>
                </span>
                <?php if ($is_full): ?>
                  <span class="sched-card__spots sched-card__spots--full">Full</span>
                <?php elseif ($low_spots): ?>
                  <span class="sched-card__spots sched-card__spots--low"><?= $spots_left ?> spot<?= $spots_left != 1 ? 's' : '' ?> left</span>
                <?php else: ?>
                  <span class="sched-card__spots"><?= $spots_left ?> / <?= $s['capacity'] ?> spots</span>
                <?php endif; ?>
              </div>
              <?php if ($role === 'member'): ?>
                <?php if ($s['i_am_enrolled']): ?>
                  <a href="<?= cancel_url($s['session_id']) ?>" class="sched-card__enroll" style="color:#42a882;border-color:rgba(66,168,130,.3);" onclick="return confirm('Cancel your enrollment in this class?')">✓ Enrolled — Cancel</a>
                <?php elseif ($s['i_am_waitlisted']): ?>
                  <a href="<?= cancel_url($s['session_id']) ?>" class="sched-card__enroll sched-card__enroll--waitlist" onclick="return confirm('Leave the waitlist for this class?')">On Waitlist — Leave</a>
                <?php elseif ($is_full): ?>
                  <a href="<?= enroll_url($s['session_id']) ?>" class="sched-card__enroll sched-card__enroll--waitlist">Join Waitlist</a>
                <?php else: ?>
                  <a href="<?= enroll_url($s['session_id']) ?>" class="sched-card__enroll">Enroll</a>
                <?php endif; ?>
              <?php elseif ($role === 'trainer' || $role === 'admin'): ?>
                <a href="my_roster.php?session_id=<?= $s['session_id'] ?>" class="sched-card__enroll">View Roster (<?= $s['enrolled_count'] ?>)</a>
              <?php endif; ?>
            </article>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </section>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <!-- Review Modal Overlay -->
  <?php if ($review_session): ?>
  <div class="overlay overlay--visible">
    <div class="modal">
      <h2>Review Class</h2>
      <p style="margin-bottom:1rem;color:var(--subtle);font-size:.9rem;">
        <?= htmlspecialchars($review_session['name']) ?> on <?= date('D d M', strtotime($review_session['scheduled_at'])) ?>
      </p>
      <form action="../actions/submit_review.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="session_id" value="<?= $review_session['id'] ?>">

        <label class="form-label">Rating (1-5)</label>
        <div style="display:flex;gap:.5rem;margin-bottom:1rem;justify-content:center;">
          <?php for($i=1;$i<=5;$i++): ?>
            <label style="cursor:pointer;font-size:1.5rem;">
              <input type="radio" name="rating" value="<?= $i ?>" required style="display:none;" onchange="updateStars(this.value)">
              <span class="star-icon" id="star-<?= $i ?>" style="color:var(--surface-3);">★</span>
            </label>
          <?php endfor; ?>
        </div>

        <label class="form-label">Comment (optional)</label>
        <textarea name="comment" class="form-input" rows="3" style="resize:vertical;" placeholder="How was the class?"></textarea>

        <div style="display:flex;gap:1rem;margin-top:1.5rem;">
          <a href="classes.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
          <button type="submit" class="btn btn-primary" style="flex:1;">Submit Review</button>
        </div>
      </form>
    </div>
  </div>
  <script src="../js/classes.js"></script>
  <?php endif; ?>

  <footer>
    <div class="footer-inner">
      <div class="footer-bottom">
        <span>&copy; 2026 W8 Gym</span>
        <span>Barbell Bay</span>
      </div>
    </div>
  </footer>

</body>
</html>
