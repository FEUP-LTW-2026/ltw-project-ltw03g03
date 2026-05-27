<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

// Fetch fresh user data
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$u = $stmt->fetch();

// Fetch role-specific profile
$trainer_profile = null;
$member_plan     = null;

if ($role === 'trainer') {
    $stmt = $db->prepare('SELECT * FROM trainer_profiles WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $trainer_profile = $stmt->fetch();
}

if ($role === 'member') {
    $stmt = $db->prepare(
        'SELECT mp.name, mp.price_monthly, mp.features, mem.plan_start, mem.plan_end
         FROM member_profiles mem
         LEFT JOIN membership_plans mp ON mp.id = mem.plan_id
         WHERE mem.user_id = ?'
    );
    $stmt->execute([$user['id']]);
    $member_plan = $stmt->fetch();
}

// Classes attended (for reviews)
$attended = [];
if ($role === 'member') {
    $stmt = $db->prepare(
        'SELECT cs.id as session_id, c.name, cs.scheduled_at,
                u.first_name, u.last_name,
                cr.rating, cr.comment
         FROM enrollments e
         JOIN class_sessions cs ON cs.id = e.session_id
         JOIN classes c ON c.id = cs.class_id
         LEFT JOIN users u ON u.id = c.trainer_id
         LEFT JOIN class_reviews cr ON cr.session_id = cs.id AND cr.member_id = e.member_id
         WHERE e.member_id = ? AND e.status = "attended"
         ORDER BY cs.scheduled_at DESC LIMIT 5'
    );
    $stmt->execute([$user['id']]);
    $attended = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — My Profile</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/profile.css">

</head>
<body>

  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php 
    $current_page = 'profile';
    require_once __DIR__ . '/../includes/nav.php'; 
  ?>

  <main class="page-wrap">

    <!-- ── Sidebar ── -->
    <aside class="profile-sidebar">

      <aside class="avatar-card">
        <div class="avatar-wrap">
          <div class="avatar-img">
            <?php if ($u['photo_path']): ?>
              <img src="../<?= htmlspecialchars($u['photo_path']) ?>" alt="Profile photo">
            <?php else: ?>
              <?= strtoupper(substr($u['first_name'],0,1) . substr($u['last_name'],0,1)) ?>
            <?php endif; ?>
          </div>
          <button class="avatar-edit-btn" type="button" onclick="document.getElementById('photo-input').click()" title="Change photo">
            <svg viewBox="0 0 20 20" fill="none"><path d="M4 13.5V16h2.5l7-7-2.5-2.5-7 7zM15.7 6.3a1 1 0 000-1.4l-1.6-1.6a1 1 0 00-1.4 0l-1.2 1.2 3 3 1.2-1.2z" fill="currentColor"/></svg>
          </button>
        </div>
        <div class="avatar-name"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></div>
        <div class="avatar-role"><?= ucfirst($u['role']) ?></div>
        <div class="avatar-email"><?= htmlspecialchars($u['email']) ?></div>
      </aside>

      <?php if ($role === 'member' && $member_plan): ?>
      <aside class="sidebar-card">
        <div class="sidebar-card__title">Membership</div>
        <div class="sidebar-stat">
          <span>Plan</span>
          <span class="sidebar-stat__val"><?= htmlspecialchars($member_plan['name'] ?? '—') ?></span>
        </div>
        <div class="sidebar-stat">
          <span>Price</span>
          <span class="sidebar-stat__val">€<?= number_format($member_plan['price_monthly'] ?? 0, 2) ?>/mo</span>
        </div>
        <?php if ($member_plan['plan_end']): ?>
        <div class="sidebar-stat">
          <span>Renews</span>
          <span class="sidebar-stat__val"><?= date('d M Y', strtotime($member_plan['plan_end'])) ?></span>
        </div>
        <?php endif; ?>
      </aside>
      <?php endif; ?>

      <?php if ($role === 'trainer' && $trainer_profile): ?>
      <aside class="sidebar-card">
        <div class="sidebar-card__title">Trainer Info</div>
        <div class="sidebar-stat">
          <span>Specialty</span>
          <span class="sidebar-stat__val" style="font-size:.7rem;"><?= htmlspecialchars($trainer_profile['specialty'] ?? '—') ?></span>
        </div>
        <div class="sidebar-stat">
          <span>Experience</span>
          <span class="sidebar-stat__val"><?= $trainer_profile['years_experience'] ?? 0 ?> yrs</span>
        </div>
      </aside>
      <?php endif; ?>

      <aside class="sidebar-card">
        <div class="sidebar-card__title">Account Info</div>
        <div class="sidebar-stat">
          <span>Username</span>
          <span class="sidebar-stat__val">@<?= htmlspecialchars($u['username']) ?></span>
        </div>
        <div class="sidebar-stat">
          <span>Member Since</span>
          <span class="sidebar-stat__val"><?= date('M Y', strtotime($u['created_at'])) ?></span>
        </div>
        <?php if ($u['phone']): ?>
        <div class="sidebar-stat">
          <span>Phone</span>
          <span class="sidebar-stat__val"><?= htmlspecialchars($u['phone']) ?></span>
        </div>
        <?php endif; ?>
      </aside>

    </aside>

    <!-- ── Main ── -->
    <div class="profile-main">

      <?php if ($flash): ?>
      <div class="flash flash--<?= htmlspecialchars($flash['type']) ?> flash--page">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
      <?php endif; ?>

      <!-- Edit profile form -->
      <section class="profile-section">
        <header class="profile-section__header">
          <div class="profile-section__title">Edit Profile</div>
        </header>
        <div class="profile-section__body">
          <form class="form" method="post" action="../actions/update_profile.php" enctype="multipart/form-data" novalidate>

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- Hidden photo input triggered by avatar button -->
            <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/png,image/webp"
                   onchange="this.form.submit()">

            <div class="info-grid">
              <div class="field">
                <label class="field__label" for="first_name">First Name</label>
                <div class="field__wrap">
                  <svg class="field__icon" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M3 17c0-3.314 3.134-6 7-6s7 2.686 7 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                  <input class="field__input" type="text" id="first_name" name="first_name"
                         value="<?= htmlspecialchars($u['first_name']) ?>" required>
                </div>
              </div>
              <div class="field">
                <label class="field__label" for="last_name">Last Name</label>
                <div class="field__wrap">
                  <svg class="field__icon" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M3 17c0-3.314 3.134-6 7-6s7 2.686 7 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                  <input class="field__input" type="text" id="last_name" name="last_name"
                         value="<?= htmlspecialchars($u['last_name']) ?>" required>
                </div>
              </div>
            </div>

            <div class="info-grid">
              <div class="field">
                <label class="field__label" for="username">Username</label>
                <div class="field__wrap">
                  <svg class="field__icon" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M10 7v3l2 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                  <input class="field__input" type="text" id="username" name="username"
                         value="<?= htmlspecialchars($u['username']) ?>" required>
                </div>
              </div>
              <div class="field">
                <label class="field__label" for="phone">Phone</label>
                <div class="field__wrap">
                  <svg class="field__icon" viewBox="0 0 20 20" fill="none"><path d="M4 4h3l1.5 3.5-2 1.2a9 9 0 004.8 4.8l1.2-2L16 13v3a1 1 0 01-1 1z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <input class="field__input" type="tel" id="phone" name="phone"
                         value="<?= htmlspecialchars($u['phone'] ?? '') ?>">
                </div>
              </div>
            </div>

            <?php if ($role === 'trainer'): ?>
            <hr class="form-divider">
            <p class="form-section-label">Trainer Details</p>

            <div class="field">
              <label class="field__label" for="bio">Bio</label>
              <div class="field__wrap">
                <textarea class="field__input" id="bio" name="bio" rows="3"
                          style="padding-left:1rem;resize:vertical;"
                          placeholder="Tell members about yourself..."><?= htmlspecialchars($trainer_profile['bio'] ?? '') ?></textarea>
              </div>
            </div>

            <div class="info-grid">
              <div class="field">
                <label class="field__label" for="specialty">Specialty</label>
                <div class="field__wrap field__wrap--select">
                  <svg class="field__icon" viewBox="0 0 20 20" fill="none"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <select class="field__input field__input--select" id="specialty" name="specialty">
                    <?php
                    $specialties = [
                      'strength'     => 'Strength & Conditioning',
                      'cardio'       => 'Cardio & Endurance',
                      'yoga'         => 'Yoga & Flexibility',
                      'crossfit'     => 'CrossFit',
                      'pilates'      => 'Pilates',
                      'martial_arts' => 'Martial Arts',
                      'nutrition'    => 'Nutrition & Wellness',
                    ];
                    foreach ($specialties as $val => $label):
                      $sel = ($trainer_profile['specialty'] ?? '') === $val ? 'selected' : '';
                    ?>
                    <option value="<?= $val ?>" <?= $sel ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="field">
                <label class="field__label" for="years_experience">Years Experience</label>
                <div class="field__wrap">
                  <svg class="field__icon" viewBox="0 0 20 20" fill="none"><rect x="3" y="4" width="14" height="13" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M3 8h14" stroke="currentColor" stroke-width="1.4"/></svg>
                  <input class="field__input" type="number" id="years_experience" name="years_experience" min="0" max="60"
                         value="<?= (int)($trainer_profile['years_experience'] ?? 0) ?>">
                </div>
              </div>
            </div>

            <div class="field">
              <label class="field__label" for="certifications">Certifications</label>
              <div class="field__wrap">
                <textarea class="field__input" id="certifications" name="certifications" rows="2"
                          style="padding-left:1rem;resize:vertical;"
                          placeholder="e.g. ISSA CPT, CrossFit Level 2..."><?= htmlspecialchars($trainer_profile['certifications'] ?? '') ?></textarea>
              </div>
            </div>
            <?php endif; ?>

            <hr class="form-divider">
            <p class="form-section-label">Change Password <span style="font-size:.6rem;color:var(--subtle);text-transform:none;letter-spacing:0;">(leave blank to keep current)</span></p>

            <div class="info-grid">
              <div class="field">
                <label class="field__label" for="new_password">New Password</label>
                <div class="field__wrap">
                  <svg class="field__icon" viewBox="0 0 20 20" fill="none"><rect x="4" y="9" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M7 9V6.5a3 3 0 016 0V9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="10" cy="13" r="1.2" fill="currentColor"/></svg>
                  <input class="field__input" type="password" id="new_password" name="new_password"
                         placeholder="min. 8 characters" autocomplete="new-password">
                </div>
              </div>
              <div class="field">
                <label class="field__label" for="confirm_password">Confirm New Password</label>
                <div class="field__wrap">
                  <svg class="field__icon" viewBox="0 0 20 20" fill="none"><rect x="4" y="9" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M7 9V6.5a3 3 0 016 0V9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="10" cy="13" r="1.2" fill="currentColor"/></svg>
                  <input class="field__input" type="password" id="confirm_password" name="confirm_password"
                         placeholder="repeat new password" autocomplete="new-password">
                </div>
              </div>
            </div>

            <button class="btn btn-primary btn-block" type="submit">
              Save Changes
              <svg class="btn-submit__arrow" viewBox="0 0 20 20" fill="none"><path d="M4 10h12M12 6l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

          </form>
        </div>
      </section>

      <!-- Classes attended + reviews (members only) -->
      <?php if ($role === 'member' && $attended): ?>
      <section class="profile-section">
        <header class="profile-section__header">
          <div class="profile-section__title">Classes Attended</div>
        </header>
        <div class="profile-section__body">
          <div class="attended-list">
            <?php foreach ($attended as $a): ?>
            <article class="attended-item">
              <div class="attended-item__info">
                <div class="attended-item__name"><?= htmlspecialchars($a['name']) ?></div>
                <div class="attended-item__meta">
                  <?= date('d M Y', strtotime($a['scheduled_at'])) ?>
                  · <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?>
                </div>
              </div>
              <?php if ($a['rating']): ?>
                <div class="stars">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <svg class="star <?= $i <= $a['rating'] ? '' : 'star--empty' ?>" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M10 1l2.39 4.84L18 6.91l-4 3.9.94 5.5L10 13.77l-4.94 2.54L6 10.81 2 6.91l5.61-.07z"/>
                    </svg>
                  <?php endfor; ?>
                </div>
              <?php else: ?>
                <a href="classes.php?review=<?= $a['session_id'] ?>"
                   style="font-family:var(--fu);font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:var(--accent);">
                  Leave Review
                </a>
              <?php endif; ?>
            </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
      <?php endif; ?>

    </div>
  </main>

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
    toggle.addEventListener('click', () => {
      navLinks.classList.toggle('nav-links--open');
      toggle.classList.toggle('nav-mobile-btn--open');
    });
  </script>          

</body>
</html>
