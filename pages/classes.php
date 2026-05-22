<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

// ── Helper functions for CSRF-protected URLs ───────────────────
function enroll_url($session_id) {
    return "../actions/enroll.php?session_id={$session_id}&csrf_token=" . $_SESSION['csrf_token'];
}

function cancel_url($session_id) {
    return "../actions/cancel_enrollment.php?session_id={$session_id}&csrf_token=" . $_SESSION['csrf_token'];
}

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
$stmt->execute(array_merge($params, [$user['id'], $user['id']]));
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

    <section class="sched-controls">
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
    </section>

    <section class="sched-layout">
      <div class="inner" style="padding-top:3rem;padding-bottom:6rem;">
        <?php if (empty($by_day)): ?>
          <p style="color:var(--subtle);font-size:1rem;text-align:center;padding:4rem 0;">
            No classes match your filters. <a href="classes.php">Clear all filters →</a>
          </p>
        <?php endif; ?>

        <?php foreach ($by_day as $date => $day_sessions): ?>
        <section class="day-block">
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
                  <?= htmlspecialchars($s['trainer_first'] . ' ' . $s['trainer_last']) ?>
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
      <form action="../actions/do_review.php" method="POST">
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
  <script>
    function updateStars(val) {
      for(let i=1;i<=5;i++) {
        document.getElementById('star-'+i).style.color = i<=val ? '#c0a030' : 'var(--surface-3)';
      }
    }
  </script>
  <?php endif; ?>

  <footer>
    <div class="footer-inner">
      <div class="footer-bottom">
        <span>&copy; 2026 W8 Gym</span>
        <span>Barbell Bay</span>
      </div>
    </div>
  </footer>

  <script>
    const toggle = document.getElementById('nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (toggle) {
      toggle.addEventListener('click', () => {
        navLinks.classList.toggle('nav-links--open');
        toggle.classList.toggle('nav-mobile-btn--open');
      });
    }
  </script>

</body>
</html>
