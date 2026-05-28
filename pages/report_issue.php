<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Report an Issue</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    .report-bg {
        padding-top: 120px;
        min-height: calc(100vh - 100px);
        max-width: 600px;
        margin: 0 auto;
    }
  </style>
</head>
<body>

  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php 
    $current_page = 'report_issue';
    require_once __DIR__ . '/../includes/nav.php'; 
  ?>

  <!-- Flash -->
  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" style="margin-top:80px;padding-left:2rem;">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <main>
    <section class="report-bg">
      <h2 class="title" style="margin-bottom:0.5rem;">Report an Issue</h2>
      <p style="color:var(--subtle);margin-bottom:2rem;">Have a problem with equipment, classes, or something else? Let us know.</p>

      <form action="../actions/submit_dispute.php" method="POST" style="background:var(--surface-2);padding:2rem;border-radius:8px;border:1px solid var(--surface-3);">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group" style="margin-bottom:1.5rem;">
          <label class="form-label" for="subject">Subject</label>
          <input type="text" name="subject" id="subject" class="form-input" placeholder="e.g. Broken Treadmill #3" required>
        </div>

        <div class="form-group" style="margin-bottom:2rem;">
          <label class="form-label" for="description">Description</label>
          <textarea name="description" id="description" class="form-input" rows="5" placeholder="Please provide details about the issue..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">Submit Report</button>
      </form>
      
      <!-- List past disputes for this user -->
      <?php
        $stmt = $db->prepare("SELECT subject, status, created_at, admin_reply FROM disputes WHERE reporter_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$user['id']]);
        $my_disputes = $stmt->fetchAll();
      ?>
      <?php if ($my_disputes): ?>
      <div style="margin-top:4rem;">
        <h3 style="font-family:var(--fh);font-size:1.5rem;margin-bottom:1rem;color:var(--accent);">Your Recent Reports</h3>
        <div style="display:flex;flex-direction:column;gap:1rem;">
          <?php foreach ($my_disputes as $d): ?>
            <div style="background:var(--surface-2);padding:1rem;border-radius:6px;border:1px solid var(--surface-3);">
              <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
                <span style="font-weight:600;"><?= htmlspecialchars($d['subject']) ?></span>
                <span class="badge badge--<?= $d['status'] === 'resolved' ? 'completed' : ($d['status'] === 'open' ? 'cancelled' : 'in_review') ?>">
                  <?= str_replace('_', ' ', $d['status']) ?>
                </span>
              </div>
              <div style="font-size:0.8rem;color:var(--subtle);margin-bottom:0.5rem;">Reported on <?= date('d M Y', strtotime($d['created_at'])) ?></div>
              <?php if (!empty($d['admin_reply'])): ?>
                <div style="background:rgba(255,255,255,0.05);padding:0.75rem;border-radius:4px;font-size:0.9rem;border-left:3px solid var(--accent);">
                  <strong style="color:var(--accent);">Admin Reply:</strong> <?= nl2br(htmlspecialchars($d['admin_reply'])) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </section>
  </main>

  <footer style="margin-top:auto;">
    <div class="footer-inner">
      <div class="footer-bottom">
        <span>&copy; 2026 W8 Gym</span>
        <span>Barbell Bay</span>
      </div>
    </div>
  </footer>

</body>
</html>
