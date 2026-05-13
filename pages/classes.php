<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

// ── Filters from GET ──────────────────────────────────────────
$filter_type    = $_GET['type']       ?? 'all';
$filter_level   = $_GET['level']      ?? 'all';
$filter_trainer = (int)($_GET['trainer'] ?? 0);
$filter_day     = $_GET['day']        ?? 'all';   // mon, tue, wed, thu, fri, sat, sun

// ── Fetch all active trainers for the filter bar ──────────────
$trainers = $db->query(
    'SELECT u.id, u.first_name, u.last_name FROM users u
     JOIN classes c ON c.trainer_id = u.id
     WHERE u.role = "trainer" AND u.is_active = 1 AND c.is_active = 1
     GROUP BY u.id ORDER BY u.first_name'
)->fetchAll();

// ── Build session query with filters ─────────────────────────
// Show sessions from today onwards, grouped by date
$where  = ['cs.scheduled_at >= datetime(\'now\')', 'cs.status = "scheduled"', 'c.is_active = 1'];
$params = [];

if ($filter_type !== 'all') {
    $where[]  = 'c.type = ?';
    $params[] = $filter_type;
}
if ($filter_level !== 'all') {
    $where[]  = 'c.level = ?';
    $params[] = $filter_level;
}
if ($filter_trainer > 0) {
    $where[]  = 'c.trainer_id = ?';
    $params[] = $filter_trainer;
}
if ($filter_day !== 'all') {
    $day_map = ['sun'=>'0','mon'=>'1','tue'=>'2','wed'=>'3','thu'=>'4','fri'=>'5','sat'=>'6'];
    if (isset($day_map[$filter_day])) {
        $where[]  = "strftime('%w', cs.scheduled_at) = ?";
        $params[] = $day_map[$filter_day];
    }
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
           MAX(CASE WHEN e.member_id = ' . (int)$user['id'] . ' AND e.status = "enrolled" THEN 1 ELSE 0 END) as i_am_enrolled,
           MAX(CASE WHEN e.member_id = ' . (int)$user['id'] . ' AND e.status = "waitlist" THEN 1 ELSE 0 END) as i_am_waitlisted
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
$stmt->execute($params);
$sessions = $stmt->fetchAll();

// ── Group sessions by date ────────────────────────────────────
$by_day = [];
foreach ($sessions as $s) {
    $day_key = date('Y-m-d', strtotime($s['scheduled_at']));
    $by_day[$day_key][] = $s;
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
    $current = $_GET[$param] ?? 'all';
    return ($current === $value || ($value === 'all' && !isset($_GET[$param]))) ? 'chip--active' : '';
}

function filter_url(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === 'all' || $v === '' || $v === '0' || $v === 0) unset($params[$k]);
    }
    return 'classes.php' . ($params ? '?' . http_build_query($params) : '');
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

  <!-- Nav -->
  <nav>
    <a href="../index.php" class="nav-brand">
      <svg viewBox="0 0 40 40" fill="none">
        <rect x="2" y="16" width="8" height="8" rx="1" fill="currentColor"/>
        <rect x="30" y="16" width="8" height="8" rx="1" fill="currentColor"/>
        <rect x="10" y="10" width="4" height="20" rx="1" fill="currentColor"/>
        <rect x="26" y="10" width="4" height="20" rx="1" fill="currentColor"/>
        <rect x="14" y="18" width="12" height="4" rx="1" fill="currentColor"/>
      </svg>
      <span>W8</span>
    </a>
    <div class="nav-links">
      <a href="dashboard.php">Dashboard</a>
      <a href="classes.php" class="nav-link--active">Classes</a>
      <?php if ($role === 'member'): ?>
        <a href="equipment.php">Equipment</a>
        <a href="trainers.php">Trainers</a>
      <?php elseif ($role === 'trainer'): ?>
        <a href="my_schedule.php">My Schedule</a>
        <a href="my_roster.php">Roster</a>
      <?php elseif ($role === 'admin'): ?>
        <a href="admin_users.php">Users</a>
        <a href="admin_classes.php">Classes</a>
        <a href="admin_equipment.php">Equipment</a>
      <?php endif; ?>
      <a href="profile.php">Profile</a>
    </div>
    <a href="../actions/logout.php" class="nav-cta">Sign Out</a>
  </nav>

  <!-- Flash -->
  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" style="margin-top:80px;padding-left:2rem;">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header__inner">
      <div class="tag">Schedule</div>
      <h1 class="page-header__title">Class<br><span>Schedule</span></h1>
      <p class="page-header__sub">Browse, filter, and book your next session</p>
    </div>
    <div class="page-header__deco">CLASSES</div>
  </div>

  <!-- Filters Bar -->
  <div class="filters-bar">
    <div class="filters-bar__inner">

      <!-- Type -->
      <div class="filter-group">
        <span class="filter-group__label">Type</span>
        <div class="filter-chips">
          <a href="<?= filter_url(['type'=>'all']) ?>" class="chip <?= is_active_filter('type','all') ?>">All</a>
          <a href="<?= filter_url(['type'=>'powerlifting']) ?>" class="chip <?= is_active_filter('type','powerlifting') ?>">Powerlifting</a>
          <a href="<?= filter_url(['type'=>'hiit']) ?>" class="chip <?= is_active_filter('type','hiit') ?>">HIIT</a>
          <a href="<?= filter_url(['type'=>'crossfit']) ?>" class="chip <?= is_active_filter('type','crossfit') ?>">CrossFit</a>
          <a href="<?= filter_url(['type'=>'yoga']) ?>" class="chip <?= is_active_filter('type','yoga') ?>">Yoga</a>
        </div>
      </div>

      <!-- Level -->
      <div class="filter-group">
        <span class="filter-group__label">Level</span>
        <div class="filter-chips">
          <a href="<?= filter_url(['level'=>'all']) ?>" class="chip <?= is_active_filter('level','all') ?>">All</a>
          <a href="<?= filter_url(['level'=>'beginner']) ?>" class="chip <?= is_active_filter('level','beginner') ?>">Beginner</a>
          <a href="<?= filter_url(['level'=>'intermediate']) ?>" class="chip <?= is_active_filter('level','intermediate') ?>">Intermediate</a>
          <a href="<?= filter_url(['level'=>'advanced']) ?>" class="chip <?= is_active_filter('level','advanced') ?>">Advanced</a>
        </div>
      </div>

      <!-- Day -->
      <div class="filter-group">
        <span class="filter-group__label">Day</span>
        <div class="filter-chips">
          <a href="<?= filter_url(['day'=>'all']) ?>" class="chip <?= is_active_filter('day','all') ?>">All</a>
          <?php foreach (['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'] as $k=>$v): ?>
          <a href="<?= filter_url(['day'=>$k]) ?>" class="chip <?= is_active_filter('day',$k) ?>"><?= $v ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Trainer -->
      <?php if ($trainers): ?>
      <div class="filter-group">
        <span class="filter-group__label">Trainer</span>
        <form method="get" action="classes.php" style="display:contents;">
          <?php foreach ($_GET as $k => $v): if ($k === 'trainer') continue; ?>
            <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
          <?php endforeach; ?>
          <select name="trainer" class="trainer-select" onchange="this.form.submit()">
            <option value="0">All Trainers</option>
            <?php foreach ($trainers as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $filter_trainer === (int)$t['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- Schedule -->
  <section class="schedule-section">
    <div class="inner" style="padding-top:3rem;padding-bottom:6rem;">
      <div class="schedule-section .inner">

        <?php if (empty($by_day)): ?>
          <p style="color:var(--subtle);font-size:1rem;text-align:center;padding:4rem 0;">
            No classes match your filters. <a href="classes.php">Clear all filters →</a>
          </p>
        <?php endif; ?>

        <?php foreach ($by_day as $date => $day_sessions): ?>
        <div class="day-block">
          <div class="day-label">
            <span class="day-label__name"><?= date('l, d F', strtotime($date)) ?></span>
            <div class="day-label__line"></div>
            <span class="day-label__count"><?= count($day_sessions) ?> class<?= count($day_sessions) != 1 ? 'es' : '' ?></span>
          </div>

          <div class="schedule-grid">
            <?php foreach ($day_sessions as $s):
              $spots_left = $s['capacity'] - $s['enrolled_count'];
              $is_full    = $spots_left <= 0;
              $low_spots  = !$is_full && $spots_left <= 3;
            ?>
            <div class="sched-card <?= $is_full ? 'sched-card--full' : '' ?>">

              <!-- Colour bar -->
              <div class="sched-card__bar sched-card__bar--<?= $s['type'] ?>"></div>

              <!-- Time -->
              <div class="sched-card__time">
                <span class="sched-card__hour"><?= date('H:i', strtotime($s['scheduled_at'])) ?></span>
                <span class="sched-card__dur"><?= $s['duration_min'] ?>min</span>
              </div>

              <!-- Body -->
              <div class="sched-card__body">
                <div class="sched-card__type"><?= ucfirst($s['type']) ?></div>
                <div class="sched-card__name"><?= htmlspecialchars($s['name']) ?></div>
                <div class="sched-card__trainer">
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                  <?= htmlspecialchars($s['trainer_first'] . ' ' . $s['trainer_last']) ?>
                </div>
              </div>

              <!-- Meta row -->
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

              <!-- Enroll button (members only) -->
              <?php if ($role === 'member'): ?>
                <?php if ($s['i_am_enrolled']): ?>
                  <a href="../actions/cancel_enrollment.php?session_id=<?= $s['session_id'] ?>"
                     class="sched-card__enroll"
                     style="color:#42a882;border-color:rgba(66,168,130,.3);"
                     onclick="return confirm('Cancel your enrollment in this class?')">
                    ✓ Enrolled — Cancel
                  </a>
                <?php elseif ($s['i_am_waitlisted']): ?>
                  <a href="../actions/cancel_enrollment.php?session_id=<?= $s['session_id'] ?>"
                     class="sched-card__enroll sched-card__enroll--waitlist"
                     onclick="return confirm('Leave the waitlist for this class?')">
                    On Waitlist — Leave
                  </a>
                <?php elseif ($is_full): ?>
                  <a href="../actions/enroll.php?session_id=<?= $s['session_id'] ?>"
                     class="sched-card__enroll sched-card__enroll--waitlist">
                    Join Waitlist
                  </a>
                <?php else: ?>
                  <a href="../actions/enroll.php?session_id=<?= $s['session_id'] ?>"
                     class="sched-card__enroll">
                    Enroll
                  </a>
                <?php endif; ?>
              <?php elseif ($role === 'trainer' || $role === 'admin'): ?>
                <a href="my_roster.php?session_id=<?= $s['session_id'] ?>" class="sched-card__enroll">
                  View Roster (<?= $s['enrolled_count'] ?>)
                </a>
              <?php endif; ?>

            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    </div>
  </section>

  <!-- ── Review Modal ── -->
  <?php if ($review_session): ?>
  <div class="modal-backdrop" id="review-modal">
    <div class="modal">
      <button class="modal__close" onclick="document.getElementById('review-modal').remove()">✕</button>
      <div class="modal__header">
        <div class="modal__title">Leave a Review</div>
        <div class="modal__sub"><?= htmlspecialchars($review_session['name']) ?> · <?= date('d M Y', strtotime($review_session['scheduled_at'])) ?></div>
      </div>
      <div class="modal__body">
        <form class="form" method="post" action="../actions/submit_review.php">
          <input type="hidden" name="session_id" value="<?= $review_session['id'] ?>">

          <div class="field">
            <label class="field__label">Your Rating</label>
            <div class="star-picker">
              <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
              <label for="star<?= $i ?>">★</label>
              <?php endfor; ?>
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="review-comment">Comment <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted)">(optional)</span></label>
            <div class="field__wrap">
              <textarea class="field__input" id="review-comment" name="comment" rows="3"
                        style="padding-left:1rem;resize:vertical;"
                        placeholder="What did you think of the class?"></textarea>
            </div>
          </div>

          <button class="btn btn-primary btn-block" type="submit">Submit Review</button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Footer -->
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
