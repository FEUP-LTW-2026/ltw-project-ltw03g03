<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

// Fetch user notifications
$stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Notifications</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    .notif-bg {
        padding-top: 120px;
        min-height: calc(100vh - 100px);
        max-width: 800px;
        margin: 0 auto;
    }
    .notif-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 2rem;
    }
    .notif-item {
        background: var(--surface-2);
        border: 1px solid var(--surface-3);
        border-radius: 8px;
        padding: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        position: relative;
        overflow: hidden;
    }
    .notif-item--unread {
        border-color: var(--accent);
        background: rgba(232, 64, 64, 0.05);
    }
    .notif-item--unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--accent);
    }
    .notif-icon {
        background: var(--surface-3);
        padding: 0.75rem;
        border-radius: 50%;
        color: var(--text);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .notif-item--unread .notif-icon {
        background: var(--accent);
        color: #fff;
    }
    .notif-content {
        flex: 1;
    }
    .notif-message {
        font-family: var(--font-body, var(--fb));
        font-size: 1rem;
        color: var(--text);
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }
    .notif-time {
        font-family: var(--font-ui, var(--fu));
        font-size: 0.8rem;
        color: var(--subtle);
        letter-spacing: .05em;
    }
    .notif-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 2rem;
        border-bottom: 1px solid var(--surface-3);
        padding-bottom: 1rem;
    }
  </style>
</head>
<body>

  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php 
    $current_page = 'notifications';
    require_once __DIR__ . '/../includes/nav.php'; 
  ?>

  <!-- Flash -->
  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" style="margin-top:80px;padding-left:2rem;">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <main>
    <section class="notif-bg">
      <div class="notif-header">
        <div>
          <h2 class="title" style="margin:0;font-size:2.5rem;">Notifications</h2>
        </div>
        <div>
          <form action="../actions/read_notifications.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit" class="btn btn-ghost" style="padding:0.5rem 1rem;font-size:0.8rem;">Mark all as read</button>
          </form>
        </div>
      </div>

      <?php if (empty($notifications)): ?>
        <p style="color:var(--subtle);text-align:center;padding:4rem 0;">You have no notifications yet.</p>
      <?php else: ?>
        <div class="notif-list">
          <?php foreach ($notifications as $n): ?>
            <article class="notif-item <?= $n['is_read'] ? '' : 'notif-item--unread' ?>">
              <div class="notif-icon">
                <?php if ($n['type'] === 'class_reminder' || $n['type'] === 'waitlist_update'): ?>
                  <svg viewBox="0 0 24 24" fill="none" style="width:20px;height:20px;"><path d="M12 8v4l3 3M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                <?php else: ?>
                  <svg viewBox="0 0 24 24" fill="none" style="width:20px;height:20px;"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                <?php endif; ?>
              </div>
              <div class="notif-content">
                <div class="notif-message"><?= htmlspecialchars($n['message']) ?></div>
                <div class="notif-time"><?= date('D, d M Y, H:i', strtotime($n['created_at'])) ?></div>
              </div>
            </article>
          <?php endforeach; ?>
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
