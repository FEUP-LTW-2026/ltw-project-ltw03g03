<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user  = require_login();
$db    = get_db();
$flash = get_flash();
$role  = $user['role'];

// Only members can subscribe to plans
if ($role !== 'member') {
    set_flash('error', 'Only members can manage membership plans.');
    header('Location: dashboard.php');
    exit;
}

// Fetch active plans
$stmt = $db->query("SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY price_monthly ASC");
$plans = $stmt->fetchAll();

// Fetch current plan
$stmt = $db->prepare("SELECT plan_id FROM member_profiles WHERE user_id = ?");
$stmt->execute([$user['id']]);
$current_plan_id = $stmt->fetchColumn();
$current_plan_id = $current_plan_id !== false && $current_plan_id !== null ? (int)$current_plan_id : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Membership Plans</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/home.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    .pricing-bg {
        padding-top: 120px;
        min-height: calc(100vh - 100px);
    }
  </style>
</head>
<body>

  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php 
    $current_page = 'plans';
    require_once __DIR__ . '/../includes/nav.php'; 
  ?>

  <!-- Flash -->
  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" style="margin-top:80px;padding-left:2rem;">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <main>
    <section class="pricing-bg" id="pricing">
      <div class="inner">
        <div class="tag">Membership</div>
        <h2 class="title">Choose Your Plan</h2>

        <div class="pricing-grid">
          <?php foreach ($plans as $plan): ?>
          <?php 
             $features = json_decode($plan['features'], true) ?? [];
             $is_current = ($current_plan_id === (int)$plan['id']);
             $is_popular = ($plan['name'] === 'Pro'); // Just mimicking the homepage logic
          ?>
          <article class="price-card <?= $is_popular ? 'featured' : '' ?>">
            <?php if ($is_popular): ?>
              <div class="price-badge">Most Popular</div>
            <?php endif; ?>
            
            <h3><?= htmlspecialchars($plan['name']) ?></h3>
            <div class="price-amount">
              <span class="price-cur">€</span>
              <span class="price-num"><?= (int)$plan['price_monthly'] ?></span>
              <span class="price-per">/ month</span>
            </div>
            
            <?php if (!empty($plan['description'])): ?>
              <p style="color:var(--subtle);font-size:0.9rem;margin-bottom:1.5rem;text-align:center;">
                <?= htmlspecialchars($plan['description']) ?>
              </p>
            <?php endif; ?>

            <ul>
              <?php foreach ($features as $f): ?>
                <li><?= htmlspecialchars($f) ?></li>
              <?php endforeach; ?>
            </ul>
            
            <form action="../actions/subscribe.php" method="POST" style="margin-top:auto;">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
              <?php if ($is_current): ?>
                <button type="button" class="price-btn" style="background:var(--surface-3);color:var(--text);cursor:default;">Current Plan</button>
              <?php else: ?>
                <button type="submit" class="price-btn">Subscribe</button>
              <?php endif; ?>
            </form>
          </article>
          <?php endforeach; ?>
        </div>
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

</body>
</html>
