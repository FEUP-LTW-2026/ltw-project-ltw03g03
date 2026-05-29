<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../database/equipment.class.php';

$user = require_login();
$db = get_db();
$flash = get_flash();
$role = $user['role'];

// Gated for members only
if ($role !== 'member') {
    header('Location: dashboard.php');
    exit;
}

$selected_equipment_id = (int)($_GET['equipment_id'] ?? 0);

// Get all active equipment categories and types (with non-retired units)
$equipment_stmt = $db->query(
  "SELECT DISTINCT e.id, e.name, e.category
   FROM equipment e
   JOIN equipment_units u ON u.equipment_id = e.id
   WHERE u.status != 'retired'
   ORDER BY e.category ASC, e.name ASC"
);
$all_equipment = $equipment_stmt->fetchAll();

// Get upcoming reservations for this member
$my_reservations = EquipmentReservation::getForMember($db, $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Reserve Equipment</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/trainers.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    .reserve-grid {
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      gap: 2.5rem;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem 4rem;
    }
    @media (max-width: 900px) {
      .reserve-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
      }
    }
    .reserve-form-container {
      background: var(--color-steel);
      border: 1px solid var(--color-border);
      padding: 2rem;
      position: relative;
    }
    .reserve-form-container::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
      background: var(--grad-accent);
    }
    .reserve-form-title {
      font-family: var(--font-display);
      font-size: 1.8rem;
      letter-spacing: 0.08em;
      margin-bottom: 1.5rem;
      color: var(--color-text);
    }
    .reservation-list {
      display: flex;
      flex-direction: column;
      gap: 1px;
      background: var(--color-border);
      border: 1px solid var(--color-border);
    }
    .reservation-item {
      background: var(--color-steel);
      padding: 1.2rem 1.4rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      transition: background var(--transition);
    }
    .reservation-item:hover {
      background: #1e2026;
    }
    .reservation-item__info {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
    .reservation-item__name {
      font-family: var(--font-display);
      font-size: 1.25rem;
      letter-spacing: 0.06em;
      color: var(--color-text);
    }
    .reservation-item__unit {
      font-family: var(--font-ui);
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: var(--color-accent);
    }
    .reservation-item__time {
      font-family: var(--font-ui);
      font-size: 0.72rem;
      letter-spacing: 0.08em;
      color: var(--color-subtle);
    }
    .reservation-item__cancel {
      font-family: var(--font-ui);
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #e84040;
      border: 1px solid rgba(232, 64, 64, 0.3);
      padding: 0.4rem 0.8rem;
      background: rgba(232, 64, 64, 0.05);
      transition: all var(--transition);
      cursor: pointer;
    }
    .reservation-item__cancel:hover {
      background: #e84040;
      color: #fff;
      border-color: #e84040;
    }
  </style>
</head>
<body>

  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php 
    $current_page = 'equipment'; 
    require_once __DIR__ . '/../includes/nav.php'; 
  ?>

  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" style="margin-top:80px;padding-left:2rem;max-width:1200px;margin-left:auto;margin-right:auto;">
    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <main style="padding-top: <?= $flash ? '1rem' : '6rem' ?>;">
    <header class="page-header" style="padding-top:0;margin-bottom:2.5rem;">
      <div class="page-header__inner">
        <div class="tag">Gym Assets</div>
        <h1 class="page-header__title">Reserve<br><span>Equipment</span></h1>
        <p class="page-header__sub">Book training gear ahead of time</p>
      </div>
      <div class="page-header__deco">RESERVE</div>
    </header>

    <div class="reserve-grid">
      <!-- Form Column -->
      <section class="reserve-form-container">
        <h2 class="reserve-form-title">New Reservation</h2>
        <form method="POST" action="../actions/reserve_equipment.php" class="form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

          <div class="field">
            <label class="field__label" for="equipment_id">Equipment Type</label>
            <div class="field__wrap">
              <svg class="field__icon" viewBox="0 0 24 24" fill="none">
                <path d="M6 4v16M18 4v16M3 8h3M18 8h3M3 16h3M18 16h3M6 12h12" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              <select class="field__input field__input--select" id="equipment_id" name="equipment_id" required>
                <option value="">Choose gear...</option>
                <?php foreach ($all_equipment as $eq): ?>
                  <option value="<?= (int)$eq['id'] ?>" <?= $selected_equipment_id === (int)$eq['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($eq['name'], ENT_QUOTES, 'UTF-8') ?> (<?= ucfirst(htmlspecialchars($eq['category'], ENT_QUOTES, 'UTF-8')) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="reserved_from">Start Date & Time</label>
            <div class="field__wrap">
              <svg class="field__icon" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <path d="M3 10h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              <input class="field__input field__input--date" id="reserved_from" name="reserved_from" type="datetime-local" required>
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="reserved_to">End Date & Time</label>
            <div class="field__wrap">
              <svg class="field__icon" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <path d="M3 10h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              <input class="field__input field__input--date" id="reserved_to" name="reserved_to" type="datetime-local" required>
            </div>
          </div>

          <div style="margin-top:1rem;">
            <button type="submit" class="btn btn-primary" style="width:100%;">Confirm Reservation</button>
          </div>
        </form>
      </section>

      <!-- Bookings List Column -->
      <section>
        <header class="trainer-section-heading">
          <span class="trainer-section-heading__title">Your Reservations</span>
          <div class="trainer-section-heading__line"></div>
        </header>

        <?php if (empty($my_reservations)): ?>
          <div class="trainers-empty">
            <svg class="trainers-empty__icon" viewBox="0 0 24 24" fill="none">
              <path d="M6 4v16M18 4v16M3 8h3M18 8h3M3 16h3M18 16h3M6 12h12" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            No active reservations scheduled.
          </div>
        <?php else: ?>
          <div class="reservation-list">
            <?php foreach ($my_reservations as $res): ?>
              <?php
                $start = strtotime($res['reserved_from']);
                $end = strtotime($res['reserved_to']);
              ?>
              <article class="reservation-item">
                <div class="reservation-item__info">
                  <div class="reservation-item__name"><?= htmlspecialchars($res['equipment_name'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="reservation-item__unit"><?= htmlspecialchars($res['unit_label'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="reservation-item__time">
                    <?= date('D, d M H:i', $start) ?> &mdash; <?= date('H:i', $end) ?>
                  </div>
                </div>
                <div>
                  <a class="reservation-item__cancel" 
                     href="../actions/cancel_equipment_reservation.php?reservation_id=<?= (int)$res['id'] ?>&csrf_token=<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                     onclick="return confirm('Cancel this equipment reservation?')">
                    Cancel
                  </a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
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
</body>
</html>
