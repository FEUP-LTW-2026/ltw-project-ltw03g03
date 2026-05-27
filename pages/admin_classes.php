<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$role = $admin['role'];
$db = get_db();

$stmt = $db->query(
    'SELECT c.id, c.name, c.type, c.level, c.capacity, c.trainer_id, c.is_active,
            u.first_name, u.last_name,
            COUNT(cs.id) AS upcoming_sessions
     FROM classes c
     LEFT JOIN users u ON u.id = c.trainer_id
     LEFT JOIN class_sessions cs ON cs.class_id = c.id
        AND cs.scheduled_at >= datetime("now")
        AND cs.status = "scheduled"
     GROUP BY c.id
     ORDER BY c.name'
);
$classes = $stmt->fetchAll();

$flash = get_flash();
$editClass = null;
$trainers = $db->query(
    'SELECT id, first_name, last_name FROM users WHERE role = "trainer" AND is_active = 1 ORDER BY first_name, last_name'
)->fetchAll();

if (isset($_GET['edit_class'])) {
    $editId = (int) $_GET['edit_class'];
    if ($editId > 0) {
        $editStmt = $db->prepare('SELECT * FROM classes WHERE id = ?');
        $editStmt->execute([$editId]);
        $editClass = $editStmt->fetch();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Classes</title>
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

  <?php $current_page = 'admin_classes'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="admin-layout">
    <header class="admin-header">
      <div>
        <div class="admin-header__sub">Admin Console</div>
        <h1 class="admin-header__title">Classes</h1>
      </div>
      <div class="admin-header__sub"><?= count($classes) ?> class types</div>
    </header>

    <section class="admin-section">
      <div class="admin-section__heading">
        <span class="admin-section__title">Class Catalog</span>
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
            <h2 class="admin-panel__title"><?= $editClass ? 'Edit Class' : 'Create Class' ?></h2>
            <form class="form" method="post" action="../actions/admin_save_class.php" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($editClass): ?>
                <input type="hidden" name="class_id" value="<?= (int)$editClass['id'] ?>">
              <?php endif; ?>

              <div class="field">
                <label class="field__label" for="class-name">Class Name</label>
                <input class="field__input" id="class-name" name="name" type="text" value="<?= htmlspecialchars($editClass['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
              </div>

              <div class="form__row">
                <div class="field">
                  <label class="field__label" for="class-type">Type</label>
                  <select class="field__input" id="class-type" name="type" required>
                    <?php foreach (['powerlifting','hiit','crossfit','yoga','other'] as $typeOption): ?>
                      <option value="<?= $typeOption ?>" <?= isset($editClass['type']) && $editClass['type'] === $typeOption ? 'selected' : '' ?>><?= ucfirst($typeOption) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label class="field__label" for="class-level">Level</label>
                  <select class="field__input" id="class-level" name="level" required>
                    <?php foreach (['all','beginner','intermediate','advanced'] as $levelOption): ?>
                      <option value="<?= $levelOption ?>" <?= isset($editClass['level']) && $editClass['level'] === $levelOption ? 'selected' : '' ?>><?= ucfirst($levelOption) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="form__row">
                <div class="field">
                  <label class="field__label" for="class-capacity">Capacity</label>
                  <input class="field__input" id="class-capacity" name="capacity" type="number" min="1" value="<?= htmlspecialchars($editClass['capacity'] ?? '20', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="field">
                  <label class="field__label" for="class-trainer">Assigned Trainer</label>
                  <select class="field__input" id="class-trainer" name="trainer_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($trainers as $trainer): ?>
                      <option value="<?= (int)$trainer['id'] ?>" <?= isset($editClass['trainer_id']) && $editClass['trainer_id'] === $trainer['id'] ? 'selected' : '' ?>><?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="field">
                <label class="field__label" for="class-description">Description</label>
                <textarea class="field__input" id="class-description" name="description"><?= htmlspecialchars($editClass['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
              </div>

              <button class="btn btn-primary" type="submit"><?= $editClass ? 'Save Changes' : 'Create Class' ?></button>
            </form>
          </div>
        </div>
      </div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Type</th>
              <th>Level</th>
              <th>Capacity</th>
              <th>Trainer</th>
              <th>Upcoming</th>
              <th>Active</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($classes as $c): ?>
            <tr>
              <td><?= (int)$c['id'] ?></td>
              <td><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge badge--scheduled"><?= htmlspecialchars($c['type'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td class="muted"><?= htmlspecialchars($c['level'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="num"><?= (int)$c['capacity'] ?></td>
              <td><?= $c['trainer_id'] ? htmlspecialchars($c['first_name'] . ' ' . $c['last_name'], ENT_QUOTES, 'UTF-8') : '<span class="muted">Unassigned</span>' ?></td>
              <td class="num"><?= (int)$c['upcoming_sessions'] ?></td>
              <td><span class="badge <?= $c['is_active'] ? 'badge--active' : 'badge--inactive' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
              <td>
                <a class="btn btn-secondary" href="?edit_class=<?= (int)$c['id'] ?>">Edit</a>
                <form method="post" action="../actions/admin_toggle_class.php" style="display:inline; margin:0;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="class_id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-ghost" type="submit"><?= $c['is_active'] ? 'Deactivate' : 'Reactivate' ?></button>
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
