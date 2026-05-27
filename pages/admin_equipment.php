<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$role = $admin['role'];
$db = get_db();

$stmt = $db->query(
    'SELECT MIN(e.id) AS id, e.name, e.category, MAX(e.updated_at) AS updated_at,
            COUNT(u.id) AS units,
            SUM(CASE WHEN u.status = "available" THEN 1 ELSE 0 END) AS available_units,
            SUM(CASE WHEN u.status = "maintenance" THEN 1 ELSE 0 END) AS maintenance_units
     FROM equipment e
     LEFT JOIN equipment_units u ON u.equipment_id = e.id AND u.status != "retired"
     GROUP BY lower(trim(e.name)), e.category
     ORDER BY e.category, e.name'
);
$items = $stmt->fetchAll();

$flash = get_flash();
$editEquipment = null;
$equipmentUnits = [];

if (isset($_GET['edit_equipment'])) {
    $editId = (int) $_GET['edit_equipment'];
    if ($editId > 0) {
        $editStmt = $db->prepare('SELECT * FROM equipment WHERE id = ?');
        $editStmt->execute([$editId]);
        $editEquipment = $editStmt->fetch();

        if ($editEquipment) {
            $unitStmt = $db->prepare('SELECT * FROM equipment_units WHERE equipment_id = ? ORDER BY id');
            $unitStmt->execute([$editId]);
            $equipmentUnits = $unitStmt->fetchAll();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Equipment</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php $current_page = 'admin_equipment'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="admin-layout">
    <header class="admin-header">
      <div>
        <div class="admin-header__sub">Admin Console</div>
        <h1 class="admin-header__title">Equipment</h1>
      </div>
      <div class="admin-header__sub"><?= count($items) ?> grouped equipment types</div>
    </header>

    <section class="admin-section">
      <div class="admin-section__heading">
        <span class="admin-section__title">Equipment Inventory</span>
        <span class="admin-section__line"></span>
      </div>

      <?php if ($flash): ?>
      <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
      <?php endif; ?>

      <div class="admin-section__forms">
        <div class="admin-panel admin-panel--split">
          <div class="admin-panel__block">
            <h2 class="admin-panel__title"><?= $editEquipment ? 'Edit Equipment Type' : 'Add Equipment Type' ?></h2>
            <form class="form" method="post" action="../actions/admin_save_equipment.php" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($editEquipment): ?>
                <input type="hidden" name="equipment_id" value="<?= (int)$editEquipment['id'] ?>">
              <?php endif; ?>

              <div class="field">
                <label class="field__label" for="equipment-name">Name</label>
                <input class="field__input" id="equipment-name" name="name" type="text" value="<?= htmlspecialchars($editEquipment['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
              </div>

              <div class="field">
                <label class="field__label" for="equipment-category">Category</label>
                <select class="field__input" id="equipment-category" name="category" required>
                  <?php foreach (['cardio','weights','machines','other'] as $categoryOption): ?>
                    <option value="<?= $categoryOption ?>" <?= isset($editEquipment['category']) && $editEquipment['category'] === $categoryOption ? 'selected' : '' ?>><?= ucfirst($categoryOption) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="field">
                <label class="field__label" for="equipment-notes">Notes</label>
                <textarea class="field__input" id="equipment-notes" name="notes"><?= htmlspecialchars($editEquipment['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
              </div>

              <button class="btn btn-primary" type="submit"><?= $editEquipment ? 'Save Equipment' : 'Add Equipment' ?></button>
            </form>
          </div>

          <?php if ($editEquipment): ?>
          <div class="admin-panel__block">
            <h2 class="admin-panel__title">Manage Units</h2>
            <form class="form" method="post" action="../actions/admin_equipment_unit.php" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="equipment_id" value="<?= (int)$editEquipment['id'] ?>">
              <div class="field">
                <label class="field__label" for="unit-label">Unit Label</label>
                <input class="field__input" id="unit-label" name="unit_label" type="text" placeholder="Treadmill #1">
              </div>
              <div class="field">
                <label class="field__label" for="unit-status">Status</label>
                <select class="field__input" id="unit-status" name="status" required>
                  <?php foreach (['available','maintenance','retired'] as $statusOption): ?>
                    <option value="<?= $statusOption ?>"><?= ucfirst($statusOption) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn btn-primary" type="submit" name="action" value="add">Add Unit</button>
            </form>

            <?php if ($equipmentUnits): ?>
            <div class="admin-table-wrap" style="margin-top:1.5rem;">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Unit ID</th>
                    <th>Label</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($equipmentUnits as $unit): ?>
                  <tr>
                    <td><?= (int)$unit['id'] ?></td>
                    <td><?= htmlspecialchars($unit['unit_label'] ?? 'Unit ' . $unit['id'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(ucfirst($unit['status']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <form method="post" action="../actions/admin_equipment_unit.php" style="display:inline; margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="unit_id" value="<?= (int)$unit['id'] ?>">
                        <input type="hidden" name="equipment_id" value="<?= (int)$editEquipment['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-ghost" type="submit">Remove</button>
                      </form>
                      <form method="post" action="../actions/admin_equipment_unit.php" style="display:inline; margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="unit_id" value="<?= (int)$unit['id'] ?>">
                        <input type="hidden" name="equipment_id" value="<?= (int)$editEquipment['id'] ?>">
                        <input type="hidden" name="unit_label" value="<?= htmlspecialchars($unit['unit_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <select class="field__input" name="status" style="width:auto; display:inline-block; margin-right:.5rem;">
                          <?php foreach (['available','maintenance','retired'] as $statusOption): ?>
                            <option value="<?= $statusOption ?>" <?= $unit['status'] === $statusOption ? 'selected' : '' ?>><?= ucfirst($statusOption) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button class="btn btn-secondary" type="submit" name="action" value="update">Update</button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Type ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Units</th>
              <th>Available</th>
              <th>Maintenance</th>
              <th>Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
              <td><?= (int)$it['id'] ?></td>
              <td><?= htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge badge--scheduled"><?= htmlspecialchars($it['category'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td class="num"><?= (int)$it['units'] ?></td>
              <td class="num"><?= (int)$it['available_units'] ?></td>
              <td class="num"><?= (int)$it['maintenance_units'] ?></td>
              <td class="muted"><?= htmlspecialchars(date('d M Y', strtotime($it['updated_at'])), ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <a class="btn btn-secondary" href="?edit_equipment=<?= (int)$it['id'] ?>">Edit</a>
                <form method="post" action="../actions/admin_delete_equipment.php" style="display:inline; margin:0;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="equipment_id" value="<?= (int)$it['id'] ?>">
                  <button class="btn btn-ghost" type="submit">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
