<?php
// includes/nav.php
// Expected variables: $role (string), $current_page (string), $base_path (string)
$current_page = $current_page ?? '';
$role = $role ?? 'member';
$base_path = $base_path ?? '../';

$is_active = function($page) use ($current_page) {
    return $current_page === $page ? 'nav-link--active' : '';
};
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
    <a href="<?= $base_path ?>pages/dashboard.php" class="<?= $is_active('dashboard') ?>">Dashboard</a>
    <a href="<?= $base_path ?>pages/classes.php" class="<?= $is_active('classes') ?>">Classes</a>
    <?php if ($role === 'member'): ?>
      <a href="<?= $base_path ?>pages/equipment.php" class="<?= $is_active('equipment') ?>">Equipment</a>
      <a href="<?= $base_path ?>pages/trainers.php" class="<?= $is_active('trainers') ?>">Trainers</a>
    <?php elseif ($role === 'trainer'): ?>
      <a href="<?= $base_path ?>pages/my_schedule.php" class="<?= $is_active('my_schedule') ?>">My Schedule</a>
      <a href="<?= $base_path ?>pages/my_roster.php" class="<?= $is_active('my_roster') ?>">Roster</a>
    <?php elseif ($role === 'admin'): ?>
      <a href="<?= $base_path ?>pages/admin_users.php" class="<?= $is_active('admin_users') ?>">Users</a>
      <a href="<?= $base_path ?>pages/admin_classes.php" class="<?= $is_active('admin_classes') ?>">Classes</a>
      <a href="<?= $base_path ?>pages/admin_equipment.php" class="<?= $is_active('admin_equipment') ?>">Equipment</a>
      <a href="<?= $base_path ?>pages/admin_disputes.php" class="<?= $is_active('admin_disputes') ?>">Disputes</a>
    <?php endif; ?>
    <a href="<?= $base_path ?>pages/profile.php" class="<?= $is_active('profile') ?>">Profile</a>
  </div>
  <a href="<?= $base_path ?>actions/do_logout.php" class="nav-cta">Sign Out</a>
  <button class="nav-mobile-btn" id="nav-toggle" aria-label="Toggle menu">
    <span></span>
    <span></span>
    <span></span>
  </button>
</nav>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    const toggle = document.getElementById('nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (toggle && navLinks) {
      toggle.addEventListener('click', () => {
        navLinks.classList.toggle('nav-links--open');
        toggle.classList.toggle('nav-mobile-btn--open');
      });
    }
  });
</script>
