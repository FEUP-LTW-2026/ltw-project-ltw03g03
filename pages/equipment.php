<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

// ── Whitelist-validated GET filters ──────────────────────────
$allowed_statuses    = ['available', 'in_use', 'maintenance'];
$filter_status       = $_GET['status']   ?? '';
$filter_category     = $_GET['category'] ?? '';

if (!in_array($filter_status, $allowed_statuses, true)) {
    $filter_status = '';
}
$filter_category = trim($filter_category);
if ($filter_category !== '' && strlen($filter_category) > 50) {
    $filter_category = '';
}

// ── Fetch distinct categories for the filter bar (only from non-retired units)
$categories = $db->query(
  "SELECT DISTINCT e.category FROM equipment e
   JOIN equipment_units u ON u.equipment_id = e.id
   WHERE u.status != 'retired'
   ORDER BY e.category ASC"
)->fetchAll(PDO::FETCH_COLUMN);

// ── Helper: map DB status value to CSS class ─────────────────
function status_css_class(string $status): string {
    $map = [
        'available'   => 'status--available',
        'in_use'      => 'status--in-use',
        'maintenance' => 'status--maintenance',
    ];
    return $map[$status] ?? '';
}

// ── Helper: human-readable status label ──────────────────────
function status_label(string $status): string {
    $map = [
        'available'   => 'Available',
        'in_use'      => 'In Use',
        'maintenance' => 'Maintenance',
    ];
    return $map[$status] ?? ucfirst($status);
}

// ── Helper: build filter URL preserving other params ─────────
function equipment_filter_url(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        }
    }
    return 'equipment.php' . ($params ? '?' . http_build_query($params) : '');
}

function equipment_chip_active(string $param, string $value): string {
    $current = $_GET[$param] ?? '';
    return ($current === $value) ? 'chip--active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Equipment</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/equipment.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
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

  <!-- Flash -->
  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" style="margin-top:80px;padding-left:2rem;">
    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <main>
    <header class="page-header">
      <div class="page-header__inner">
        <div class="tag">Gym Floor</div>
        <h1 class="page-header__title">Equipment<br><span>Availability</span></h1>
        <p class="page-header__sub">Check what's available before you train</p>
      </div>
      <div class="page-header__deco">GEAR</div>
    </header>

    <section class="equipment-layout">
      <!-- Summary Row -->
      <section class="equipment-summary">
        <article class="equipment-summary__item">
          <span class="equipment-summary__label">Available</span>
          <span class="equipment-summary__value equipment-summary__value--available" id="summary-available">0</span>
          <span class="equipment-summary__sub">items ready to use</span>
        </article>
        <article class="equipment-summary__item">
          <span class="equipment-summary__label">Total (Active)</span>
          <span class="equipment-summary__value" id="summary-total">0</span>
          <span class="equipment-summary__sub">non-retired items</span>
        </article>
        <article class="equipment-summary__item">
          <span class="equipment-summary__label">In Use / Maintenance</span>
          <span class="equipment-summary__value" id="summary-unavailable">0</span>
          <span class="equipment-summary__sub">currently unavailable</span>
        </article>
      </section>
    </section>

    <section class="equipment-filters">
      <div class="equipment-filters__inner">
        <div class="equipment-filter-group">
          <span class="equipment-filter-group__label">Status</span>
          <div class="equipment-filter-chips">
            <a href="<?= equipment_filter_url(['status' => '']) ?>" class="chip <?= ($filter_status === '') ? 'chip--active' : '' ?>">All</a>
            <a href="<?= equipment_filter_url(['status' => 'available']) ?>" class="chip <?= equipment_chip_active('status', 'available') ?>">Available</a>
            <a href="<?= equipment_filter_url(['status' => 'in_use']) ?>" class="chip <?= equipment_chip_active('status', 'in_use') ?>">In Use</a>
            <a href="<?= equipment_filter_url(['status' => 'maintenance']) ?>" class="chip <?= equipment_chip_active('status', 'maintenance') ?>">Maintenance</a>
          </div>
        </div>

        <?php if ($categories): ?>
        <div class="equipment-filter-group">
          <span class="equipment-filter-group__label">Category</span>
          <div class="equipment-filter-chips">
            <a href="<?= equipment_filter_url(['category' => '']) ?>" class="chip <?= ($filter_category === '') ? 'chip--active' : '' ?>">All</a>
            <?php foreach ($categories as $cat): ?>
            <a href="<?= equipment_filter_url(['category' => $cat]) ?>" class="chip <?= equipment_chip_active('category', $cat) ?>">
              <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="equipment-layout" style="padding-top:1.5rem;" id="equipment-container" data-status="<?= htmlspecialchars($filter_status) ?>" data-category="<?= htmlspecialchars($filter_category) ?>" data-user-role="<?= htmlspecialchars($role) ?>">
      <div class="equipment-empty" id="loading-spinner">
        Loading equipment...
      </div>
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

  <script src="../js/equipment.js"></script>
</body>
</html>
