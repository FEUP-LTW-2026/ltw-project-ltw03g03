<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

// ── Query active trainers with their profiles ─────────────────
$stmt = $db->query(
    'SELECT u.id,
            u.first_name,
            u.last_name,
            u.photo_path,
            tp.specialty,
            tp.years_experience
     FROM users u
     JOIN trainer_profiles tp ON tp.user_id = u.id
     WHERE u.role = \'trainer\'
       AND u.is_active = 1
     ORDER BY CASE u.username
                WHEN \'ronniec\' THEN 1
                WHEN \'sportacus\' THEN 2
                WHEN \'popeye\' THEN 3
                WHEN \'oogway\' THEN 4
                WHEN \'sarahj\' THEN 1
                WHEN \'mikec\' THEN 2
                WHEN \'emmar\' THEN 3
                WHEN \'davidw\' THEN 4
                ELSE 5
              END,
              u.first_name ASC,
              u.last_name ASC'
);
$trainers = $stmt->fetchAll();

function trainer_specialty_label(?string $specialty): string {
    $labels = [
        'strength' => 'Head Coach',
        'cardio'   => 'HIIT & Conditioning',
        'crossfit' => 'CrossFit',
        'yoga'     => 'Yoga & Recovery',
    ];

    return $labels[$specialty] ?? ucfirst(str_replace('_', ' ', (string)$specialty));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Trainers</title>
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

  <?php 
    $current_page = 'trainers';
    require_once __DIR__ . '/../includes/nav.php'; 
  ?>

  <!-- Flash -->
  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" style="margin-top:80px;padding-left:2rem;">
    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <main>
    <!-- Page Header -->
    <header class="page-header">
      <div class="page-header__inner">
        <div class="tag">Team</div>
        <h1 class="page-header__title">Our<br><span>Trainers</span></h1>
        <p class="page-header__sub">Meet the experts behind your progress</p>
      </div>
      <div class="page-header__deco">TRAINERS</div>
    </header>

    <!-- Live API Stats Bar (populated by js/trainers.js via api/trainers.php) -->
    <div id="trainers-api-stats" data-api-url="../api/trainers.php" style="max-width:1100px;margin:0 auto;padding:0 2rem;"></div>

    <!-- Trainers Grid -->
    <section class="trainers-layout">

    <?php if (empty($trainers)): ?>
      <div class="trainers-empty">
        <svg class="trainers-empty__icon" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        No trainers available at the moment.
      </div>
    <?php else: ?>
      <div class="trainers-grid">
        <?php foreach ($trainers as $t): ?>
        <article class="trainer-card" data-trainer-id="<?= (int)$t['id'] ?>">

          <!-- Photo or default avatar -->
          <div class="trainer-card__photo">
            <?php if (!empty($t['photo_path'])): ?>
              <img
                src="../<?= htmlspecialchars($t['photo_path'], ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name'], ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
              <div class="trainer-avatar--default">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.2"/>
                  <path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
              </div>
            <?php endif; ?>
          </div>

          <!-- Card body -->
          <section class="trainer-card__body">
            <?php if (!empty($t['specialty'])): ?>
              <div class="trainer-card__specialty">
                <?= htmlspecialchars(trainer_specialty_label($t['specialty']), ENT_QUOTES, 'UTF-8') ?>
              </div>
            <?php endif; ?>
            <div class="trainer-card__name">
              <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="trainer-card__experience" data-trainer-exp>
              <strong><?= (int)$t['years_experience'] ?></strong>
              yr<?= (int)$t['years_experience'] !== 1 ? 's' : '' ?> experience
            </div>
          </section>

          <!-- Card footer -->
          <div class="trainer-card__footer">
            <a
              href="pt_bookings.php?trainer_id=<?= (int)$t['id'] ?>"
              class="trainer-card__book">
              Book a Session
            </a>
            <a
              href="trainer_profile.php?id=<?= (int)$t['id'] ?>"
              class="trainer-card__profile"
              title="View profile">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>
          </div>

        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    </section>
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

  <script src="../js/trainers.js"></script>
</body>
</html>
