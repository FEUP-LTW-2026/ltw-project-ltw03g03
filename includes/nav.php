<?php
// includes/nav.php
// Expected variables: $role (string), $current_page (string), $base_path (string)
$current_page = $current_page ?? '';
$role = $role ?? null;
$base_path = $base_path ?? '../';

$is_active = function($page) use ($current_page) {
    return $current_page === $page ? 'nav-link--active' : '';
};

$unread_notifications_count = 0;
if ($role) {
    $nav_user_id = current_user_id();
    if ($nav_user_id) {
        $nav_db = get_db();
        $nav_stmt = $nav_db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $nav_stmt->execute([$nav_user_id]);
        $unread_notifications_count = (int) $nav_stmt->fetchColumn();
    }
}
?>
<nav>
  <a href="<?= $base_path ?>index.php" class="nav-brand">
    <svg viewBox="0 0 40 40" fill="none">
      <rect x="2" y="16" width="8" height="8" rx="1" fill="currentColor"/>
      <rect x="30" y="16" width="8" height="8" rx="1" fill="currentColor"/>
      <rect x="10" y="10" width="4" height="20" rx="1" fill="currentColor"/>
      <rect x="26" y="10" width="4" height="20" rx="1" fill="currentColor"/>
      <rect x="14" y="18" width="12" height="4" rx="1" fill="currentColor"/>
    </svg>
    <span>W8</span>
  </a>
  <div class="nav-links">
    <?php if ($role): ?>
      <a href="<?= $base_path ?>pages/dashboard.php" class="<?= $is_active('dashboard') ?>">Dashboard</a>
    <?php endif; ?>
    <?php if ($role === 'member'): ?>
      <a href="<?= $base_path ?>pages/classes.php" class="<?= $is_active('classes') ?>">Classes</a>
      <a href="<?= $base_path ?>pages/equipment.php" class="<?= $is_active('equipment') ?>">Equipment</a>
      <a href="<?= $base_path ?>pages/trainers.php" class="<?= $is_active('trainers') ?>">Trainers</a>
      <a href="<?= $base_path ?>pages/my_reviews.php" class="<?= $is_active('my_reviews') ?>">Reviews</a>
    <?php elseif ($role === 'trainer'): ?>
      <a href="<?= $base_path ?>pages/my_schedule.php" class="<?= $is_active('my_schedule') ?>">My Schedule</a>
      <a href="<?= $base_path ?>pages/my_roster.php" class="<?= $is_active('my_roster') ?>">Roster</a>
    <?php elseif ($role === 'admin'): ?>
      <a href="<?= $base_path ?>pages/admin_users.php" class="<?= $is_active('admin_users') ?>">Users</a>
      <a href="<?= $base_path ?>pages/admin_classes.php" class="<?= $is_active('admin_classes') ?>">Classes</a>
      <a href="<?= $base_path ?>pages/admin_equipment.php" class="<?= $is_active('admin_equipment') ?>">Equipment</a>
      <a href="<?= $base_path ?>pages/admin_disputes.php" class="<?= $is_active('admin_disputes') ?>">Disputes</a>
    <?php endif; ?>
    <?php if ($role): ?>
      <a href="<?= $base_path ?>pages/profile.php" class="<?= $is_active('profile') ?>">Profile</a>
      <a href="<?= $base_path ?>actions/do_logout.php" class="nav-mobile-auth" style="display:none;">Sign Out</a>
    <?php else: ?>
      <a href="<?= $base_path ?>pages/sign_in.php" class="nav-mobile-auth" style="display:none;">Sign In</a>
      <a href="<?= $base_path ?>pages/register.php" class="nav-mobile-auth" style="display:none;">Register</a>
    <?php endif; ?>
  </div>
  
  <?php if ($role): ?>
    <div class="nav-desktop-auth" style="display: flex; gap: 1.5rem; align-items: center;">
      <a href="<?= $base_path ?>pages/notifications.php" style="position:relative;color:var(--text);display:flex;align-items:center;">
        <svg viewBox="0 0 24 24" fill="none" style="width:20px;height:20px;">
          <path d="M18 15h2v2H4v-2h2v-4a6 6 0 0112 0v4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M13.73 21a2 2 0 01-3.46 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <?php if ($unread_notifications_count > 0): ?>
          <span style="position:absolute;top:-6px;right:-8px;background:var(--accent);color:#000;font-size:0.65rem;font-weight:700;padding:2px 5px;border-radius:10px;"><?= $unread_notifications_count ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= $base_path ?>actions/do_logout.php" class="nav-cta">Sign Out</a>
    </div>
  <?php else: ?>
    <div class="nav-desktop-auth" style="display: flex; gap: 1rem; align-items: center;">
      <a href="<?= $base_path ?>pages/sign_in.php" style="color: var(--subtle); font-family: var(--fu); font-size: .8rem; font-weight: 600; letter-spacing: .16em; text-transform: uppercase; text-decoration: none;">Sign In</a>
      <a href="<?= $base_path ?>pages/register.php" class="nav-cta">Register</a>
    </div>
  <?php endif; ?>

  <button class="nav-mobile-btn" id="nav-toggle" aria-label="Toggle menu">
    <span></span>
    <span></span>
    <span></span>
  </button>
</nav>

<style>
@media (max-width: 768px) {
  .nav-desktop-auth { display: none !important; }
  .nav-mobile-auth { display: block !important; }
}
</style>

<script src="<?= $base_path ?>js/nav.js"></script>
