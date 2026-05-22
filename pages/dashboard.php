<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

// ── Fetch role-specific data ───────────────────────────────────
$upcoming_sessions = [];
$enrolled_count    = 0;
$trainer_classes   = [];
$roster_count      = 0;
$admin_stats       = [];

if ($role === 'member') {
    // Next 5 enrolled sessions
    $stmt = $db->prepare(
        "SELECT cs.id, cs.scheduled_at, c.name, c.type, c.duration_min,
                u.first_name, u.last_name, e.status
         FROM enrollments e
         JOIN class_sessions cs ON cs.id = e.session_id
         JOIN classes c ON c.id = cs.class_id
         LEFT JOIN users u ON u.id = c.trainer_id
         WHERE e.member_id = ? AND e.status = 'enrolled' AND cs.scheduled_at >= datetime('now')
         ORDER BY cs.scheduled_at ASC LIMIT 5"
    );
    $stmt->execute([$user['id']]);
    $upcoming_sessions = $stmt->fetchAll();

    // Total enrollments
    $stmt = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE member_id = ? AND status = "enrolled"');
    $stmt->execute([$user['id']]);
    $enrolled_count = $stmt->fetchColumn();

    // Current plan
    $stmt = $db->prepare(
        'SELECT mp.name, mp.price_monthly, mem.plan_end
         FROM member_profiles mem
         LEFT JOIN membership_plans mp ON mp.id = mem.plan_id
         WHERE mem.user_id = ?'
    );
    $stmt->execute([$user['id']]);
    $member_plan = $stmt->fetch();
}

if ($role === 'trainer') {
    // Classes assigned to this trainer
    $stmt = $db->prepare(
        "SELECT c.id, c.name, c.type, c.level, c.duration_min, c.capacity,
                COUNT(e.id) as enrolled
         FROM classes c
         LEFT JOIN class_sessions cs ON cs.class_id = c.id AND cs.scheduled_at >= datetime('now')
         LEFT JOIN enrollments e ON e.session_id = cs.id AND e.status = 'enrolled'
         WHERE c.trainer_id = ? AND c.is_active = 1
         GROUP BY c.id ORDER BY c.name"
    );
    $stmt->execute([$user['id']]);
    $trainer_classes = $stmt->fetchAll();

    // Upcoming sessions for trainer
    $stmt = $db->prepare(
        "SELECT cs.id, cs.scheduled_at, c.name, c.type,
                COUNT(e.id) as enrolled, c.capacity
         FROM class_sessions cs
         JOIN classes c ON c.id = cs.class_id
         LEFT JOIN enrollments e ON e.session_id = cs.id AND e.status = 'enrolled'
         WHERE c.trainer_id = ? AND cs.scheduled_at >= datetime('now') AND cs.status = 'scheduled'
         GROUP BY cs.id ORDER BY cs.scheduled_at ASC LIMIT 8"
    );
    $stmt->execute([$user['id']]);
    $upcoming_sessions = $stmt->fetchAll();

    // Trainer profile
    $stmt = $db->prepare('SELECT * FROM trainer_profiles WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $trainer_profile = $stmt->fetch();
}

if ($role === 'admin') {
    $admin_stats['total_members']  = $db->query('SELECT COUNT(*) FROM users WHERE role="member" AND is_active=1')->fetchColumn();
    $admin_stats['total_trainers'] = $db->query('SELECT COUNT(*) FROM users WHERE role="trainer" AND is_active=1')->fetchColumn();
    $admin_stats['total_classes']  = $db->query('SELECT COUNT(*) FROM classes WHERE is_active=1')->fetchColumn();
    $admin_stats['open_disputes']  = $db->query('SELECT COUNT(*) FROM disputes WHERE status="open"')->fetchColumn();
    $admin_stats['total_equipment'] = $db->query('SELECT COUNT(*) FROM equipment')->fetchColumn();

    // Recent registrations
    $recent_users = $db->query(
        'SELECT id, first_name, last_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 6'
    )->fetchAll();
}

$type_colors = [
    'powerlifting' => '#f5681a',
    'hiit'         => '#e84040',
    'crossfit'     => '#c0a030',
    'yoga'         => '#42a882',
    'other'        => '#7a7f91',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Dashboard</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/dashboard.css">

</head>
<body>

  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php 
    $current_page = 'dashboard';
    require_once __DIR__ . '/../includes/nav.php'; 
  ?>

  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type']) ?> flash--page">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <main>
    <!-- Hero greeting -->
    <header class="dash-hero">
      <div class="dash-hero__greeting">Welcome back</div>
      <div class="dash-hero__name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
      <div class="dash-hero__role"><?= ucfirst($role) ?> Account</div>
    </header>

    <!-- ═══════════════════ MEMBER DASHBOARD ══════════════════════ -->
    <?php if ($role === 'member'): ?>
    <section class="dash-grid dash-grid--member">

    <article class="dash-card">
      <div class="dash-card__title">Active Enrollments</div>
      <div class="dash-card__big"><?= $enrolled_count ?></div>
      <div class="dash-card__sub">upcoming classes</div>
    </article>

    <article class="dash-card">
      <div class="dash-card__title">Membership</div>
      <?php if (!empty($member_plan['name'])): ?>
        <div class="dash-card__big"><?= htmlspecialchars($member_plan['name']) ?></div>
        <div class="dash-card__sub">€<?= number_format($member_plan['price_monthly'],2) ?> / month</div>
      <?php else: ?>
        <div class="dash-card__big" style="font-size:1.5rem;color:var(--subtle);">No Plan</div>
        <div class="dash-card__sub"><a href="../index.php#pricing">Browse plans →</a></div>
      <?php endif; ?>
    </article>

    <article class="dash-card">
      <div class="dash-card__title">Quick Actions</div>
      <div class="quick-links">
        <a href="classes.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M3 10h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <span>Browse Classes</span>
        </a>
        <a href="equipment.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><path d="M6 4v16M18 4v16M3 8h3M18 8h3M3 16h3M18 16h3M6 12h12" stroke="currentColor" stroke-width="1.5"/></svg>
          <span>Equipment</span>
        </a>
        <a href="trainers.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <span>Trainers</span>
        </a>
        <a href="workout_log.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><path d="M4 18l4-8 4 5 3-3 5 6" stroke="currentColor" stroke-width="1.5"/></svg>
          <span>Log Workout</span>
        </a>
        <a href="profile.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <span>My Profile</span>
        </a>
      </div>
    </article>

    <article class="dash-card dash-card--wide">
      <div class="dash-card__title">Upcoming Classes</div>
      <?php if ($upcoming_sessions): ?>
        <div class="session-list">
          <?php foreach ($upcoming_sessions as $s): ?>
          <article class="session-item">
            <div class="session-item__bar" style="background:<?= $type_colors[$s['type']] ?? '#7a7f91' ?>"></div>
            <div class="session-item__time"><?= date('D d M, H:i', strtotime($s['scheduled_at'])) ?></div>
            <div class="session-item__info">
              <div class="session-item__name"><?= htmlspecialchars($s['name']) ?></div>
              <div class="session-item__meta"><?= $s['duration_min'] ?> min · <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
            </div>
            <div class="session-item__action">
              <a href="../actions/cancel_enrollment.php?session_id=<?= $s['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Cancel this class?')">Cancel</a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <a href="classes.php" style="display:inline-block;margin-top:1rem;font-family:var(--fu);font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);">Browse more classes →</a>
      <?php else: ?>
        <p style="color:var(--subtle);font-size:.9rem;">You have no upcoming classes. <a href="classes.php">Browse the schedule →</a></p>
      <?php endif; ?>
    </article>

    </section>
    <?php endif; ?>

    <!-- ═══════════════════ TRAINER DASHBOARD ════════════════════ -->
    <?php if ($role === 'trainer'): ?>
    <section class="dash-grid dash-grid--trainer">

    <article class="dash-card">
      <div class="dash-card__title">Your Classes</div>
      <div class="dash-card__big"><?= count($trainer_classes) ?></div>
      <div class="dash-card__sub">active class types</div>
    </article>

    <article class="dash-card">
      <div class="dash-card__title">Specialty</div>
      <div class="dash-card__big" style="font-size:1.4rem;color:var(--text);">
        <?= htmlspecialchars($trainer_profile['specialty'] ?? 'Not set') ?>
      </div>
      <div class="dash-card__sub"><a href="profile.php">Edit profile →</a></div>
    </article>

    <article class="dash-card">
      <div class="dash-card__title">Quick Actions</div>
      <div class="quick-links">
        <a href="my_schedule.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M3 10h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <span>My Schedule</span>
        </a>
        <a href="my_roster.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M2 20c0-3.314 3.134-6 7-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 11l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span>Roster</span>
        </a>
        <a href="profile.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <span>My Profile</span>
        </a>
      </div>
    </article>

    <article class="dash-card dash-card--wide">
      <div class="dash-card__title">Upcoming Sessions</div>
      <?php if ($upcoming_sessions): ?>
        <div class="session-list">
          <?php foreach ($upcoming_sessions as $s): ?>
          <article class="session-item">
            <div class="session-item__bar" style="background:<?= $type_colors[$s['type']] ?? '#7a7f91' ?>"></div>
            <div class="session-item__time"><?= date('D d M, H:i', strtotime($s['scheduled_at'])) ?></div>
            <div class="session-item__info">
              <div class="session-item__name"><?= htmlspecialchars($s['name']) ?></div>
              <div class="session-item__meta"><?= $s['enrolled'] ?> / <?= $s['capacity'] ?> enrolled</div>
            </div>
            <div class="session-item__action">
              <a href="my_roster.php?session_id=<?= $s['id'] ?>">View Roster</a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:var(--subtle);font-size:.9rem;">No upcoming sessions scheduled.</p>
      <?php endif; ?>
    </article>

    </section>
    <?php endif; ?>

    <!-- ════════════════════ ADMIN DASHBOARD ═════════════════════ -->
    <?php if ($role === 'admin'): ?>

    <section style="max-width:1100px;margin:0 auto;padding:0 2rem 2rem;">
    <div class="stat-cards">
      <article class="stat-card">
        <div class="stat-card__n"><?= $admin_stats['total_members'] ?></div>
        <div class="stat-card__l">Active Members</div>
      </article>
      <article class="stat-card">
        <div class="stat-card__n"><?= $admin_stats['total_trainers'] ?></div>
        <div class="stat-card__l">Trainers</div>
      </article>
      <article class="stat-card">
        <div class="stat-card__n"><?= $admin_stats['total_classes'] ?></div>
        <div class="stat-card__l">Active Classes</div>
      </article>
      <article class="stat-card">
        <div class="stat-card__n"><?= $admin_stats['total_equipment'] ?></div>
        <div class="stat-card__l">Equipment Items</div>
      </article>
      <article class="stat-card">
        <div class="stat-card__n" style="<?= $admin_stats['open_disputes'] > 0 ? 'color:#e84040' : '' ?>"><?= $admin_stats['open_disputes'] ?></div>
        <div class="stat-card__l">Open Disputes</div>
      </article>
      </div>
    </section>

    <section class="dash-grid dash-grid--admin" style="grid-template-columns:1fr 1fr;">

    <article class="dash-card">
      <div class="dash-card__title">Manage</div>
      <div class="quick-links">
        <a href="admin_users.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M2 20c0-3.314 3.134-6 7-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 11v6M13 14h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <span>Users</span>
        </a>
        <a href="admin_classes.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M3 10h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <span>Classes</span>
        </a>
        <a href="admin_equipment.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><path d="M6 4v16M18 4v16M3 8h3M18 8h3M3 16h3M18 16h3M6 12h12" stroke="currentColor" stroke-width="1.5"/></svg>
          <span>Equipment</span>
        </a>
        <a href="admin_disputes.php" class="quick-link">
          <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <span>Disputes</span>
        </a>
      </div>
    </article>

    <article class="dash-card">
      <div class="dash-card__title">Recent Registrations</div>
      <div class="user-list">
        <?php foreach ($recent_users as $u): ?>
        <article class="user-row">
          <div class="user-row__avatar">
            <?php if ($u['photo_path']): ?>
              <img src="../<?= htmlspecialchars($u['photo_path']) ?>" alt="">
            <?php else: ?>
              <?= strtoupper(substr($u['first_name'],0,1) . substr($u['last_name'],0,1)) ?>
            <?php endif; ?>
          </div>
          <div class="user-row__name"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?><br>
            <span style="font-size:.65rem;color:var(--subtle)"><?= htmlspecialchars($u['email']) ?></span>
          </div>
          <span class="user-row__badge user-row__badge--<?= $u['role'] ?>"><?= $u['role'] ?></span>
          <a href="admin_users.php?edit=<?= $u['id'] ?>" style="font-family:var(--fu);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:var(--accent);">Edit</a>
        </article>
        <?php endforeach; ?>
      </div>
    </article>
    </section>
    <?php endif; ?>

  </main>

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
