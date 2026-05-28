<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user = require_login();
$db = get_db();
$flash = get_flash();
$role = $user['role'];

$trainer_id = (int)($_GET['trainer_id'] ?? 0);
$trainer = null;
$slots = [];
$my_bookings = [];
$back_href = 'trainers.php';

if ($role === 'member') {
    $stmt = $db->prepare(
        'SELECT pb.id, pb.scheduled_at, pb.duration_min, pb.status,
                u.first_name, u.last_name
         FROM pt_bookings pb
         JOIN users u ON u.id = pb.trainer_id
         WHERE pb.member_id = ?
           AND pb.scheduled_at >= datetime("now", "-1 day")
           AND pb.status != "cancelled"
         ORDER BY pb.scheduled_at ASC'
    );
    $stmt->execute([$user['id']]);
    $my_bookings = $stmt->fetchAll();
}

if ($trainer_id > 0) {
    $stmt = $db->prepare('SELECT users.id, first_name, last_name, photo_path, specialty FROM users LEFT JOIN trainer_profiles ON users.id = trainer_profiles.user_id WHERE users.id = ? AND role = "trainer"');
    $stmt->execute([$trainer_id]);
    $trainer = $stmt->fetch();

    if ($trainer) {
        $back_href = 'trainer_profile.php?id=' . $trainer['id'];
        $stmt2 = $db->prepare(
            'SELECT pa.id, pa.start_time, pa.end_time, pa.is_booked,
                    u.first_name, u.last_name
             FROM pt_availability pa
             JOIN users u ON u.id = pa.trainer_id
             WHERE pa.trainer_id = ?
               AND pa.is_booked = 0
               AND pa.start_time > datetime("now")
             ORDER BY pa.start_time ASC'
        );
        $stmt2->execute([$trainer_id]);
        $slots = $stmt2->fetchAll();
    }
} else {
    $stmt = $db->query(
        'SELECT pa.id, pa.start_time, pa.end_time, pa.is_booked,
                u.first_name, u.last_name
         FROM pt_availability pa
         JOIN users u ON u.id = pa.trainer_id
         WHERE pa.is_booked = 0
           AND pa.start_time > datetime("now")
           AND u.role = "trainer"
           AND u.is_active = 1
         ORDER BY pa.start_time ASC'
    );
    $slots = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>W8 - PT Bookings</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/trainers.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <script src="../js/pt_bookings.js" defer></script>
</head>
<body>

  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php $current_page = 'trainers'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" style="margin-top:80px;padding-left:2rem;">
    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <main>
    <header class="page-header">
      <div class="page-header__inner">
        <div class="tag">One-on-One</div>
        <h1 class="page-header__title">Book a<br><span>Session</span></h1>
        <?php if ($trainer): ?>
          <p class="page-header__sub">Available slots for <?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
          <p class="page-header__sub">Available PT slots from all trainers.</p>
        <?php endif; ?>
      </div>
      <div class="page-header__deco">BOOKING</div>
    </header>

    <section class="trainers-layout" style="padding-top: 2rem; max-width: 800px; margin: 0 auto;">
      <?php if ($my_bookings): ?>
        <section style="margin-bottom:2rem;">
          <header class="trainer-section-heading">
            <span class="trainer-section-heading__title">Your PT Sessions</span>
            <div class="trainer-section-heading__line"></div>
          </header>
          <div class="pt-bookings-list">
            <?php foreach ($my_bookings as $booking): ?>
              <article class="pt-booking-item">
                <div class="pt-booking-item__info">
                  <div class="pt-booking-item__trainer">
                    <?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <div class="pt-booking-item__time">
                    <?= date('D, d M H:i', strtotime($booking['scheduled_at'])) ?>
                    &middot;
                    <?= (int)$booking['duration_min'] ?> min
                  </div>
                </div>
                <span class="pt-booking-item__status pt-booking-item__status--<?= htmlspecialchars($booking['status'], ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars($booking['status'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if (in_array($booking['status'], ['pending', 'confirmed'], true)): ?>
                  <a
                    class="trainer-session-card__action trainer-session-card__action--danger"
                    href="../actions/cancel_pt_booking.php?booking_id=<?= (int)$booking['id'] ?>&csrf_token=<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                    onclick="return confirm('Cancel this PT session?')">
                    Cancel
                  </a>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($trainer_id > 0 && !$trainer): ?>
        <div class="trainers-empty">
          Trainer not found. <a href="trainers.php" style="color: var(--accent);">Return to Trainers</a>
        </div>
      <?php else: ?>
        <div style="margin-bottom: 2rem;">
          <a href="<?= htmlspecialchars($back_href, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">Back</a>
        </div>

        <?php if (empty($slots)): ?>
          <div class="trainers-empty">
            <svg class="trainers-empty__icon" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
              <path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            No available PT slots right now.
          </div>
        <?php else: ?>
          <form method="POST" action="../actions/book_pt.php" class="booking-form">
            <div class="booking-form__header">
              <span class="booking-form__title">Select a Session</span>
            </div>

            <div class="booking-form__body">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

              <div class="pt-slots">
                <?php foreach ($slots as $s): ?>
                  <?php
                    $start_ts = strtotime($s['start_time']);
                    $end_ts = strtotime($s['end_time']);
                    $duration = round(($end_ts - $start_ts) / 60);
                    $is_booked = $s['is_booked'] == 1;
                  ?>
                  <label class="pt-slot <?= $is_booked ? 'pt-slot--booked' : '' ?>">
                    <input type="radio" name="slot_id" value="<?= $s['id'] ?>" class="booking-form__slot-input" <?= $is_booked ? 'disabled' : '' ?> required>
                    <div class="pt-slot__trainer"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="pt-slot__date"><?= date('D, d M Y', $start_ts) ?></div>
                    <div class="pt-slot__time"><?= date('H:i', $start_ts) ?></div>
                    <div class="pt-slot__duration"><?= date('H:i', $end_ts) ?> &middot; <?= $duration ?> min</div>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="booking-form__submit" style="padding: 0 1.4rem 1.4rem;">
              <?php if ($role === 'member'): ?>
                <button type="submit" class="btn btn-primary" disabled>Confirm Booking</button>
              <?php else: ?>
                <button class="btn btn-secondary" disabled>Members Only</button>
              <?php endif; ?>
            </div>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </main>

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
