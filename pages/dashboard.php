<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

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
        'SELECT cs.id, cs.scheduled_at, c.name, c.type, c.duration_min,
                u.first_name, u.last_name, e.status
         FROM enrollments e
         JOIN class_sessions cs ON cs.id = e.session_id
         JOIN classes c ON c.id = cs.class_id
         LEFT JOIN users u ON u.id = c.trainer_id
         WHERE e.member_id = ? AND e.status = "enrolled" AND cs.scheduled_at >= NOW()
         ORDER BY cs.scheduled_at ASC LIMIT 5'
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
        'SELECT c.id, c.name, c.type, c.level, c.duration_min, c.capacity,
                COUNT(e.id) as enrolled
         FROM classes c
         LEFT JOIN class_sessions cs ON cs.class_id = c.id AND cs.scheduled_at >= NOW()
         LEFT JOIN enrollments e ON e.session_id = cs.id AND e.status = "enrolled"
         WHERE c.trainer_id = ? AND c.is_active = 1
         GROUP BY c.id ORDER BY c.name'
    );
    $stmt->execute([$user['id']]);
    $trainer_classes = $stmt->fetchAll();

    // Upcoming sessions for trainer
    $stmt = $db->prepare(
        'SELECT cs.id, cs.scheduled_at, c.name, c.type,
                COUNT(e.id) as enrolled, c.capacity
         FROM class_sessions cs
         JOIN classes c ON c.id = cs.class_id
         LEFT JOIN enrollments e ON e.session_id = cs.id AND e.status = "enrolled"
         WHERE c.trainer_id = ? AND cs.scheduled_at >= NOW() AND cs.status = "scheduled"
         GROUP BY cs.id ORDER BY cs.scheduled_at ASC LIMIT 8'
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
    $admin_stats['total_equipment']= $db->query('SELECT COUNT(*) FROM equipment WHERE status != "retired"')->fetchColumn();

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
  <link rel="stylesheet" href="../css/home.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    .dash-hero { padding:8rem 2rem 3rem; max-width:1100px; margin:0 auto; }
    .dash-hero__greeting { font-family:var(--fu); font-size:.8rem; letter-spacing:.22em; text-transform:uppercase; color:var(--accent); margin-bottom:.5rem; }
    .dash-hero__name { font-family:var(--fd); font-size:clamp(2.5rem,6vw,4.5rem); letter-spacing:.08em; line-height:1; }
    .dash-hero__role { font-family:var(--fu); font-size:.72rem; letter-spacing:.16em; text-transform:uppercase; color:var(--subtle); margin-top:.4rem; }

    .dash-grid { display:grid; gap:1.5rem; max-width:1100px; margin:0 auto; padding:0 2rem 6rem; }
    .dash-grid--member  { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
    .dash-grid--trainer { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
    .dash-grid--admin   { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }

    .dash-card { background:var(--steel); border:1px solid var(--border); padding:1.6rem; position:relative; }
    .dash-card__title { font-family:var(--fu); font-size:.68rem; font-weight:700; letter-spacing:.2em; text-transform:uppercase; color:var(--subtle); margin-bottom:1rem; }
    .dash-card__big { font-family:var(--fd); font-size:3rem; color:var(--accent); line-height:1; }
    .dash-card__sub { font-family:var(--fu); font-size:.72rem; letter-spacing:.1em; color:var(--subtle); margin-top:.3rem; text-transform:uppercase; }

    .dash-card--wide { grid-column: 1 / -1; }

    .session-list { display:flex; flex-direction:column; gap:.6rem; }
    .session-item { display:flex; align-items:center; gap:1rem; padding:.7rem; background:var(--dark); border:1px solid var(--border); }
    .session-item__bar { width:3px; height:40px; flex-shrink:0; border-radius:2px; }
    .session-item__time { font-family:var(--fd); font-size:1.1rem; letter-spacing:.06em; white-space:nowrap; }
    .session-item__info { flex:1; min-width:0; }
    .session-item__name { font-family:var(--fd); font-size:.95rem; letter-spacing:.08em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .session-item__meta { font-family:var(--fu); font-size:.62rem; letter-spacing:.1em; text-transform:uppercase; color:var(--subtle); margin-top:2px; }
    .session-item__action a { font-family:var(--fu); font-size:.65rem; letter-spacing:.1em; text-transform:uppercase; color:var(--accent); white-space:nowrap; }

    .stat-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:1px; background:var(--border); }
    .stat-card { background:var(--steel); padding:1.4rem; text-align:center; }
    .stat-card__n { font-family:var(--fd); font-size:2.8rem; color:var(--accent); line-height:1; }
    .stat-card__l { font-family:var(--fu); font-size:.65rem; letter-spacing:.14em; text-transform:uppercase; color:var(--subtle); margin-top:.3rem; }

    .user-list { display:flex; flex-direction:column; gap:.5rem; }
    .user-row { display:flex; align-items:center; gap:1rem; padding:.6rem; background:var(--dark); border:1px solid var(--border); }
    .user-row__avatar { width:34px; height:34px; border-radius:50%; background:var(--muted); display:flex; align-items:center; justify-content:center; font-family:var(--fd); font-size:.9rem; color:var(--text); flex-shrink:0; overflow:hidden; }
    .user-row__avatar img { width:100%; height:100%; object-fit:cover; }
    .user-row__name { flex:1; font-family:var(--fu); font-size:.82rem; letter-spacing:.06em; }
    .user-row__badge { font-family:var(--fu); font-size:.6rem; letter-spacing:.14em; text-transform:uppercase; padding:2px 7px; border:1px solid; }
    .user-row__badge--member  { color:var(--subtle); border-color:var(--border); }
    .user-row__badge--trainer { color:#c0a030; border-color:rgba(192,160,48,.4); }
    .user-row__badge--admin   { color:#e84040; border-color:rgba(232,64,64,.4); }

    .quick-links { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:.6rem; }
    .quick-link { display:flex; flex-direction:column; align-items:center; gap:.5rem; padding:1.1rem .8rem;
                  border:1px solid var(--border); background:var(--dark); text-decoration:none; color:var(--text);
                  transition:border-color var(--t), background var(--t); text-align:center; }
    .quick-link:hover { border-color:var(--accent); background:rgba(245,104,26,.05); color:var(--text); }
    .quick-link svg { width:22px; height:22px; color:var(--accent); }
    .quick-link span { font-family:var(--fu); font-size:.7rem; letter-spacing:.12em; text-transform:uppercase; }

    .flash { padding:.75rem 1.2rem; font-family:var(--fu); font-size:.82rem; letter-spacing:.06em; border-left:3px solid; margin:0 2rem 1rem; max-width:1100px; margin-left:auto; margin-right:auto; }
    .flash--success { background:rgba(66,168,130,.12); border-color:#42a882; color:#42a882; }
    .flash--error   { background:rgba(232,64,64,.12);  border-color:#e84040; color:#e84040; }
  </style>
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
      <a href="dashboard.php" class="nav-link--active">Dashboard</a>
      <a href="classes.php">Classes</a>
      <?php if ($role === 'member'): ?>
        <a href="equipment.php">Equipment</a>
        <a href="trainers.php">Trainers</a>
      <?php endif; ?>
      <?php if ($role === 'trainer'): ?>
        <a href="my_schedule.php">My Schedule</a>
        <a href="my_roster.php">Roster</a>
      <?php endif; ?>
      <?php if ($role === 'admin'): ?>
        <a href="admin_users.php">Users</a>
        <a href="admin_classes.php">Classes</a>
        <a href="admin_equipment.php">Equipment</a>
      <?php endif; ?>
      <a href="profile.php">Profile</a>
    </div>
    <a href="../actions/logout.php" class="nav-cta">Sign Out</a>
  </nav>

  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" style="margin-top:80px;">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <!-- Hero greeting -->
  <div class="dash-hero">
    <div class="dash-hero__greeting">Welcome back</div>
    <div class="dash-hero__name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
    <div class="dash-hero__role"><?= ucfirst($role) ?> Account</div>
  </div>

  <!-- ═══════════════════ MEMBER DASHBOARD ══════════════════════ -->
  <?php if ($role === 'member'): ?>
  <div class="dash-grid dash-grid--member">

    <div class="dash-card">
      <div class="dash-card__title">Active Enrollments</div>
      <div class="dash-card__big"><?= $enrolled_count ?></div>
      <div class="dash-card__sub">upcoming classes</div>
    </div>

    <div class="dash-card">
      <div class="dash-card__title">Membership</div>
      <?php if (!empty($member_plan['name'])): ?>
        <div class="dash-card__big"><?= htmlspecialchars($member_plan['name']) ?></div>
        <div class="dash-card__sub">€<?= number_format($member_plan['price_monthly'],2) ?> / month</div>
      <?php else: ?>
        <div class="dash-card__big" style="font-size:1.5rem;color:var(--subtle);">No Plan</div>
        <div class="dash-card__sub"><a href="../index.php#pricing">Browse plans →</a></div>
      <?php endif; ?>
    </div>

    <div class="dash-card">
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
    </div>

    <div class="dash-card dash-card--wide">
      <div class="dash-card__title">Upcoming Classes</div>
      <?php if ($upcoming_sessions): ?>
        <div class="session-list">
          <?php foreach ($upcoming_sessions as $s): ?>
          <div class="session-item">
            <div class="session-item__bar" style="background:<?= $type_colors[$s['type']] ?? '#7a7f91' ?>"></div>
            <div class="session-item__time"><?= date('D d M, H:i', strtotime($s['scheduled_at'])) ?></div>
            <div class="session-item__info">
              <div class="session-item__name"><?= htmlspecialchars($s['name']) ?></div>
              <div class="session-item__meta"><?= $s['duration_min'] ?> min · <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
            </div>
            <div class="session-item__action">
              <a href="cancel_enrollment.php?session_id=<?= $s['id'] ?>" onclick="return confirm('Cancel this class?')">Cancel</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <a href="classes.php" style="display:inline-block;margin-top:1rem;font-family:var(--fu);font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);">Browse more classes →</a>
      <?php else: ?>
        <p style="color:var(--subtle);font-size:.9rem;">You have no upcoming classes. <a href="classes.php">Browse the schedule →</a></p>
      <?php endif; ?>
    </div>

  </div>
  <?php endif; ?>

  <!-- ═══════════════════ TRAINER DASHBOARD ════════════════════ -->
  <?php if ($role === 'trainer'): ?>
  <div class="dash-grid dash-grid--trainer">

    <div class="dash-card">
      <div class="dash-card__title">Your Classes</div>
      <div class="dash-card__big"><?= count($trainer_classes) ?></div>
      <div class="dash-card__sub">active class types</div>
    </div>

    <div class="dash-card">
      <div class="dash-card__title">Specialty</div>
      <div class="dash-card__big" style="font-size:1.4rem;color:var(--text);">
        <?= htmlspecialchars($trainer_profile['specialty'] ?? 'Not set') ?>
      </div>
      <div class="dash-card__sub"><a href="profile.php">Edit profile →</a></div>
    </div>

    <div class="dash-card">
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
    </div>

    <div class="dash-card dash-card--wide">
      <div class="dash-card__title">Upcoming Sessions</div>
      <?php if ($upcoming_sessions): ?>
        <div class="session-list">
          <?php foreach ($upcoming_sessions as $s): ?>
          <div class="session-item">
            <div class="session-item__bar" style="background:<?= $type_colors[$s['type']] ?? '#7a7f91' ?>"></div>
            <div class="session-item__time"><?= date('D d M, H:i', strtotime($s['scheduled_at'])) ?></div>
            <div class="session-item__info">
              <div class="session-item__name"><?= htmlspecialchars($s['name']) ?></div>
              <div class="session-item__meta"><?= $s['enrolled'] ?> / <?= $s['capacity'] ?> enrolled</div>
            </div>
            <div class="session-item__action">
              <a href="my_roster.php?session_id=<?= $s['id'] ?>">View Roster</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:var(--subtle);font-size:.9rem;">No upcoming sessions scheduled.</p>
      <?php endif; ?>
    </div>

  </div>
  <?php endif; ?>

  <!-- ════════════════════ ADMIN DASHBOARD ═════════════════════ -->
  <?php if ($role === 'admin'): ?>

  <div style="max-width:1100px;margin:0 auto;padding:0 2rem 2rem;">
    <div class="stat-cards">
      <div class="stat-card">
        <div class="stat-card__n"><?= $admin_stats['total_members'] ?></div>
        <div class="stat-card__l">Active Members</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__n"><?= $admin_stats['total_trainers'] ?></div>
        <div class="stat-card__l">Trainers</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__n"><?= $admin_stats['total_classes'] ?></div>
        <div class="stat-card__l">Active Classes</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__n"><?= $admin_stats['total_equipment'] ?></div>
        <div class="stat-card__l">Equipment Items</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__n" style="<?= $admin_stats['open_disputes'] > 0 ? 'color:#e84040' : '' ?>"><?= $admin_stats['open_disputes'] ?></div>
        <div class="stat-card__l">Open Disputes</div>
      </div>
    </div>
  </div>

  <div class="dash-grid dash-grid--admin" style="grid-template-columns:1fr 1fr;">

    <div class="dash-card">
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
    </div>

    <div class="dash-card">
      <div class="dash-card__title">Recent Registrations</div>
      <div class="user-list">
        <?php foreach ($recent_users as $u): ?>
        <div class="user-row">
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
        </div>
        <?php endforeach; ?>
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
