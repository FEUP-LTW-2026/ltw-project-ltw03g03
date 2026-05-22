<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

// ── Validate ?id is an active trainer ────────────────────────
$trainer_id = (int)($_GET['id'] ?? 0);

if ($trainer_id <= 0) {
    set_flash('error', 'Trainer not found.');
    header('Location: trainers.php');
    exit;
}

$stmt = $db->prepare(
    "SELECT u.id, u.first_name, u.last_name, u.photo_path, u.email,
            tp.bio, tp.specialty, tp.certifications, tp.years_experience
     FROM users u
     LEFT JOIN trainer_profiles tp ON tp.user_id = u.id
     WHERE u.id = ? AND u.role = 'trainer' AND u.is_active = 1
     LIMIT 1"
);
$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    set_flash('error', 'Trainer not found or no longer active.');
    header('Location: trainers.php');
    exit;
}

// ── Query active classes with avg rating and review count ─────
// Joins class_reviews through class_sessions to aggregate per class.
$stmt = $db->prepare(
    "SELECT c.id, c.name, c.type, c.level, c.duration_min, c.capacity, c.description,
            ROUND(AVG(cr.rating), 1)  AS avg_rating,
            COUNT(cr.id)              AS review_count
     FROM classes c
     LEFT JOIN class_sessions cs ON cs.class_id = c.id
     LEFT JOIN class_reviews cr  ON cr.session_id = cs.id
     WHERE c.trainer_id = ? AND c.is_active = 1
     GROUP BY c.id
     ORDER BY c.name ASC"
);
$stmt->execute([$trainer_id]);
$classes = $stmt->fetchAll();

// ── Query available PT slots (is_booked=0, start_time in future) ─
$stmt = $db->prepare(
    "SELECT id, start_time, end_time
     FROM pt_availability
     WHERE trainer_id = ?
       AND is_booked = 0
       AND start_time > datetime('now')
     ORDER BY start_time ASC"
);
$stmt->execute([$trainer_id]);
$pt_slots = $stmt->fetchAll();

// ── Helpers ───────────────────────────────────────────────────
$level_classes = [
    'beginner'     => 'trainer-class-card__level--beginner',
    'intermediate' => 'trainer-class-card__level--intermediate',
    'advanced'     => 'trainer-class-card__level--advanced',
];

/**
 * Render filled/half/empty stars for a given numeric rating (0–5).
 * Returns an HTML string.
 */
function render_stars(float $rating): string {
    $html = '<div class="rating__stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $cls = 'rating__star rating__star--filled';
        } elseif ($rating >= $i - 0.5) {
            $cls = 'rating__star rating__star--half';
        } else {
            $cls = 'rating__star';
        }
        $html .= '<svg class="' . $cls . '" viewBox="0 0 20 20" fill="currentColor">'
               . '<path d="M10 1l2.39 4.84L18 6.91l-4 3.9.94 5.5L10 13.77l-4.94 2.54L6 10.81 2 6.91l5.61-.07z"/>'
               . '</svg>';
    }
    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — <?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?></title>
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
  <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" style="margin-top:80px;padding-left:2rem;">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <main class="trainer-profile">

    <!-- ── Header: photo + key info ── -->
    <header class="trainer-profile__header">

      <!-- Photo / default avatar -->
      <div class="trainer-profile__photo">
        <?php if (!empty($trainer['photo_path'])): ?>
          <img src="../<?= htmlspecialchars($trainer['photo_path']) ?>"
               alt="<?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?>">
        <?php else: ?>
          <div class="trainer-avatar--default">
            <svg viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.2"/>
              <path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
          </div>
        <?php endif; ?>
      </div>

      <!-- Info -->
      <section class="trainer-profile__info">

        <?php if (!empty($trainer['specialty'])): ?>
          <div class="trainer-profile__specialty">
            <?= htmlspecialchars($trainer['specialty']) ?>
          </div>
        <?php endif; ?>

        <h1 class="trainer-profile__name">
          <?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?>
        </h1>

        <!-- Meta: experience + certifications label -->
        <div class="trainer-profile__meta">
          <div class="trainer-profile__meta-item">
            <span class="trainer-profile__meta-label">Experience</span>
            <span class="trainer-profile__meta-value">
              <?= (int)($trainer['years_experience'] ?? 0) ?>
              <span style="font-size:1rem;letter-spacing:.04em;">yrs</span>
            </span>
          </div>
          <?php if (!empty($trainer['certifications'])): ?>
          <div class="trainer-profile__meta-item">
            <span class="trainer-profile__meta-label">Certifications</span>
            <span class="trainer-profile__meta-value--text">
              <?= htmlspecialchars($trainer['certifications']) ?>
            </span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Bio -->
        <?php if (!empty($trainer['bio'])): ?>
          <p class="trainer-profile__bio">
            <?= htmlspecialchars($trainer['bio']) ?>
          </p>
        <?php endif; ?>

        <!-- CTA: Book a Session (Requirement 13.1) -->
        <div class="trainer-profile__cta">
          <a href="pt_bookings.php?trainer_id=<?= $trainer['id'] ?>" class="btn btn-primary">
            Book a Session
          </a>
          <a href="trainers.php" class="btn btn-secondary">
            ← All Trainers
          </a>
        </div>

      </section>
    </header>

    <!-- ── Active Classes with ratings ── -->
    <section class="trainer-profile__classes">
      <header class="trainer-section-heading">
        <span class="trainer-section-heading__title">Classes</span>
        <div class="trainer-section-heading__line"></div>
      </header>

      <?php if (empty($classes)): ?>
        <div class="trainers-empty">
          <svg class="trainers-empty__icon" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
            <path d="M3 10h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          No active classes assigned to this trainer.
        </div>
      <?php else: ?>
        <div class="trainer-classes-grid">
          <?php foreach ($classes as $c): ?>
          <article class="trainer-class-card">
            <div class="trainer-class-card__type"><?= ucfirst(htmlspecialchars($c['type'])) ?></div>
            <div class="trainer-class-card__name"><?= htmlspecialchars($c['name']) ?></div>
            <?php if (!empty($c['description'])): ?>
              <p style="font-family:var(--font-body,var(--fb));font-size:.82rem;color:var(--color-subtle,var(--subtle));line-height:1.5;margin:0;">
                <?= htmlspecialchars($c['description']) ?>
              </p>
            <?php endif; ?>
            <span class="trainer-class-card__level <?= $level_classes[$c['level']] ?? '' ?>">
              <?= ucfirst(htmlspecialchars($c['level'])) ?>
            </span>
            <div class="trainer-class-card__footer">
              <!-- Rating / review count (Requirement 6.3) -->
              <?php if ((int)$c['review_count'] === 0): ?>
                <span class="rating--no-reviews">No reviews yet</span>
              <?php else: ?>
                <div class="rating">
                  <?= render_stars((float)$c['avg_rating']) ?>
                  <span class="rating__value"><?= number_format((float)$c['avg_rating'], 1) ?></span>
                  <span class="rating__count">(<?= (int)$c['review_count'] ?>)</span>
                </div>
              <?php endif; ?>
              <span style="font-family:var(--font-ui,var(--fu));font-size:.65rem;letter-spacing:.1em;color:var(--color-muted,var(--muted));">
                <?= $c['duration_min'] ?> min
              </span>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- ── Available PT Slots (Requirement 6.2, 13.2) ── -->
    <section class="trainer-profile__slots">
      <header class="trainer-section-heading">
        <span class="trainer-section-heading__title">Available PT Slots</span>
        <div class="trainer-section-heading__line"></div>
      </header>

      <?php if (empty($pt_slots)): ?>
        <div class="pt-slots-empty">No available slots at this time.</div>
      <?php else: ?>
        <div class="pt-slots">
          <?php foreach ($pt_slots as $slot): ?>
          <?php
            $start_ts  = strtotime($slot['start_time']);
            $end_ts    = strtotime($slot['end_time']);
            $duration  = round(($end_ts - $start_ts) / 60);
          ?>
          <article class="pt-slot">
            <div class="pt-slot__date"><?= date('D, d M Y', $start_ts) ?></div>
            <div class="pt-slot__time"><?= date('H:i', $start_ts) ?></div>
            <div class="pt-slot__duration">
              <?= date('H:i', $end_ts) ?> · <?= $duration ?> min
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <!-- Book a session CTA below slots -->
        <div style="margin-top:1.5rem;">
          <a href="pt_bookings.php?trainer_id=<?= $trainer['id'] ?>" class="btn btn-primary">
            Book a Session →
          </a>
        </div>
      <?php endif; ?>
    </section>

  </main><!-- /.trainer-profile -->

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
