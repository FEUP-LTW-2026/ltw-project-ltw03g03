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

    <section class="equipment-layout" style="padding-top:1.5rem;" id="equipment-container">
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

  <script>
    document.addEventListener("DOMContentLoaded", () => {
        const container = document.getElementById("equipment-container");
        const statusFilter = <?= json_encode($filter_status) ?>;
        const categoryFilter = <?= json_encode($filter_category) ?>;

        function fetchEquipment() {
            let url = '../api/equipment.php?';
            if (statusFilter) url += `status=${encodeURIComponent(statusFilter)}&`;
            if (categoryFilter) url += `category=${encodeURIComponent(categoryFilter)}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    renderSummary(data.summary);
                    renderEquipment(data.equipment);
                })
                .catch(err => console.error("Error fetching equipment:", err));
        }

        function renderSummary(summary) {
            document.getElementById("summary-available").textContent = summary.total_available;
            document.getElementById("summary-total").textContent = summary.total_non_retired;
            document.getElementById("summary-unavailable").textContent = summary.total_non_retired - summary.total_available;
        }

        function renderEquipment(items) {
            if (!items || items.length === 0) {
                container.innerHTML = `
                  <div class="equipment-empty">
                    <svg class="equipment-empty__icon" viewBox="0 0 40 40" fill="none">
                      <rect x="4" y="12" width="32" height="20" rx="2" stroke="currentColor" stroke-width="1.8"/>
                      <path d="M4 18h32" stroke="currentColor" stroke-width="1.8"/>
                      <circle cx="12" cy="25" r="2" fill="currentColor" opacity=".4"/>
                      <circle cx="20" cy="25" r="2" fill="currentColor" opacity=".4"/>
                      <circle cx="28" cy="25" r="2" fill="currentColor" opacity=".4"/>
                    </svg>
                    No equipment found.
                  </div>
                `;
                return;
            }

            const byCategory = {};
            items.forEach(item => {
                if (!byCategory[item.category]) byCategory[item.category] = [];
                byCategory[item.category].push(item);
            });

            let html = '';
            for (const [cat, eqItems] of Object.entries(byCategory)) {
                html += `
                  <header class="equipment-section-heading">
                    <span class="equipment-section-heading__title">${escapeHtml(cat)}</span>
                    <div class="equipment-section-heading__line"></div>
                    <span style="font-family:var(--font-ui);font-size:.65rem;letter-spacing:.14em;color:var(--color-muted);white-space:nowrap;">
                      ${eqItems.length} item${eqItems.length !== 1 ? 's' : ''}
                    </span>
                  </header>
                  <div class="equipment-grid" style="margin-bottom:2rem;">
                `;

                eqItems.forEach(eq => {
                    let cssClass = 'status--maintenance';
                    let label = 'Maintenance';
                    if (eq.overall_status === 'available') {
                        cssClass = 'status--available'; label = 'Available';
                    } else if (eq.overall_status === 'in_use') {
                        cssClass = 'status--in-use'; label = 'In Use';
                    }

                    const dateStr = eq.updated_at ? eq.updated_at.split(' ')[0] : '';
                    
                    html += `
                      <article class="equipment-card">
                        <div class="equipment-card__body">
                          <div class="equipment-card__category">${escapeHtml(eq.category)}</div>
                          <div class="equipment-card__name">${escapeHtml(eq.name)}</div>
                          <div class="equipment-card__quantity">Qty: <strong>${eq.quantity}</strong></div>
                        </div>
                        <div class="equipment-card__footer">
                          <span class="status-badge ${cssClass}">${label}</span>
                          <span style="font-family:var(--font-ui);font-size:.6rem;letter-spacing:.08em;color:var(--color-muted);">
                            ${escapeHtml(dateStr)}
                          </span>
                        </div>
                      </article>
                    `;
                });
                html += `</div>`;
            }
            container.innerHTML = html;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        fetchEquipment();
        setInterval(fetchEquipment, 15000); // Poll every 15 seconds
    });
  </script>
</body>
</html>
