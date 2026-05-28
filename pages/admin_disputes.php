<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$admin = require_admin();
$role = $admin['role'];
$db = get_db();

$stmt = $db->query(
    'SELECT d.id, d.reporter_id, d.subject, d.description, d.status, d.created_at, d.admin_reply,
            u.first_name, u.last_name
     FROM disputes d
     LEFT JOIN users u ON u.id = d.reporter_id
     ORDER BY d.created_at DESC'
);
$disputes = $stmt->fetchAll();

$selected_id = (int)($_GET['id'] ?? 0);
$selected_dispute = null;
if ($selected_id > 0) {
    foreach ($disputes as $d) {
        if ($d['id'] == $selected_id) {
            $selected_dispute = $d;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Disputes</title>
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

  <?php $current_page = 'admin_disputes'; require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="admin-layout">
    <header class="admin-header">
      <div>
        <div class="admin-header__sub">Admin Console</div>
        <h1 class="admin-header__title">Disputes</h1>
      </div>
      <div class="admin-header__sub"><?= count($disputes) ?> reports</div>
    </header>

    <!-- Flash -->
    <?php $flash = get_flash(); if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom:2rem;">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <section class="admin-section">
      <div class="admin-section__heading">
        <span class="admin-section__title">User Reports</span>
        <span class="admin-section__line"></span>
      </div>

      <?php if (empty($disputes)): ?>
        <div class="admin-empty">No disputes found.</div>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Subject</th>
                <th>Reporter</th>
                <th>Status</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($disputes as $d): ?>
              <?php $reporter = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')) ?: 'User #' . $d['reporter_id']; ?>
              <tr>
                <td><?= (int)$d['id'] ?></td>
                <td><a href="?id=<?= $d['id'] ?>" style="color:var(--text);text-decoration:none;font-weight:600;"><?= htmlspecialchars($d['subject'], ENT_QUOTES, 'UTF-8') ?></a></td>
                <td><?= htmlspecialchars($reporter, ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge badge--<?= $d['status'] === 'resolved' ? 'completed' : ($d['status'] === 'open' ? 'cancelled' : 'in_review') ?>"><?= htmlspecialchars(str_replace('_', ' ', $d['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td class="muted"><?= htmlspecialchars(date('d M Y', strtotime($d['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                <td><a href="?id=<?= $d['id'] ?>" class="btn btn-ghost" style="padding:0.25rem 0.75rem;font-size:0.75rem;">View</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($selected_dispute): ?>
    <section class="admin-section" style="margin-top:2rem;">
      <div class="admin-section__heading">
        <span class="admin-section__title">Resolve Dispute #<?= $selected_dispute['id'] ?></span>
        <span class="admin-section__line"></span>
      </div>
      <div style="background:var(--surface-2);padding:2rem;border-radius:8px;border:1px solid var(--surface-3);">
        <h3 style="margin-bottom:1rem;font-size:1.5rem;"><?= htmlspecialchars($selected_dispute['subject']) ?></h3>
        <p style="color:var(--subtle);font-size:0.9rem;margin-bottom:1.5rem;">
          Reported by <strong><?= htmlspecialchars(trim(($selected_dispute['first_name'] ?? '') . ' ' . ($selected_dispute['last_name'] ?? ''))) ?></strong> on <?= date('d M Y, H:i', strtotime($selected_dispute['created_at'])) ?>
        </p>
        
        <div style="background:var(--bg);padding:1.5rem;border-radius:6px;margin-bottom:2rem;font-family:var(--fb);line-height:1.5;">
          <?= nl2br(htmlspecialchars($selected_dispute['description'])) ?>
        </div>

        <form action="../actions/admin_update_dispute.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="dispute_id" value="<?= $selected_dispute['id'] ?>">

          <div class="form-group" style="margin-bottom:1.5rem;">
            <label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-input">
              <option value="open" <?= $selected_dispute['status'] === 'open' ? 'selected' : '' ?>>Open</option>
              <option value="in_review" <?= $selected_dispute['status'] === 'in_review' ? 'selected' : '' ?>>In Review</option>
              <option value="resolved" <?= $selected_dispute['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
              <option value="closed" <?= $selected_dispute['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:2rem;">
            <label class="form-label" for="admin_reply">Admin Reply (visible to user)</label>
            <textarea name="admin_reply" id="admin_reply" class="form-input" rows="4"><?= htmlspecialchars($selected_dispute['admin_reply'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn-primary">Update Dispute</button>
          <a href="admin_disputes.php" class="btn btn-ghost" style="margin-left:1rem;">Cancel</a>
        </form>
      </div>
    </section>
    <?php endif; ?>
  </main>
</body>
</html>
