<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';

$user = current_user();
$role = $user ? $user['role'] : null;
$planIdsByName = [];

if ($role === 'member') {
    $db = get_db();
    $stmt = $db->query('SELECT id, name FROM membership_plans WHERE is_active = 1');
    foreach ($stmt->fetchAll() as $plan) {
        $planIdsByName[$plan['name']] = (int)$plan['id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 — Forge Your Limits</title>

  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/home.css">

  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
</head>

<body>

  <?php 
    $base_path = '';
    $current_page = 'index';
    require_once __DIR__ . '/includes/nav.php'; 
  ?>
  
  <main>

  <!-- Hero -->
  <section class="hero">
    <div class="hero-eyebrow">Forge Your Limits</div>

    <h1>
      LIFT<br><span>HARDER.</span><br>
      LIVE<br>STRONGER.
    </h1>

    <p>
      W8 is where serious athletes and everyday grinders come to push past what they thought possible.
    </p>

    <div class="hero-actions">
      <?php if ($user): ?>
        <a href="pages/classes.php" class="btn btn-primary">Book a Class</a>
        <a href="pages/dashboard.php" class="btn btn-ghost">Go to Dashboard</a>
      <?php else: ?>
        <a href="pages/register.php" class="btn btn-primary">Start Today</a>
        <a href="pages/classes.php" class="btn btn-ghost">See Classes</a>
      <?php endif; ?>
    </div>

    <div class="hero-scroll">
      <div class="hero-scroll-line"></div>
      <span>Scroll</span>
    </div>
  </section>

  <!-- Stats -->
  <section class="stats-bar" id="about">
    <article class="stat"><span class="stat-n">2,400+</span><span class="stat-l">Active Members</span></article>
    <article class="stat"><span class="stat-n">48</span><span class="stat-l">Weekly Classes</span></article>
    <article class="stat"><span class="stat-n">12</span><span class="stat-l">Elite Trainers</span></article>
    <article class="stat"><span class="stat-n">8yr</span><span class="stat-l">In The Game</span></article>
  </section>

  <!-- About -->
  <section>
  <div class="inner">
    <div class="about-grid">

      <div class="about-visual">
        <div class="about-img">
          <img src="images/W8.jpg" alt="W8 Gym">
        </div>

        <div class="about-badge">
          Since<br>2017
          <small>Est. W8 Gym</small>
        </div>
      </div>

      <section class="about-text">
        <div class="tag">Who We Are</div>

        <h2 class="title">
          Built For<br>Those Who<br>Show Up
        </h2>

        <p>
          W8 was born from a single belief — that a great gym should feel like home for anyone willing to put in the work. No judgment. No fluff. Just iron, sweat, and results.
        </p>

        <p>
          We've built a community of athletes, beginners, and everyone in between, all training under the same roof with world-class equipment and expert coaching.
        </p>

        <div class="perks">
          <span class="perk">Olympic Free Weights</span>
          <span class="perk">High Quality Machines</span>
          <span class="perk">Cardio Zone</span>
          <span class="perk">Recovery Room</span>
          <span class="perk">Nutrition Bar</span>
          <span class="perk">Private Coaching</span>
          <span class="perk">Open 7 Days</span>
        </div>

        <?php if (!$user): ?>
          <a href="pages/register.php" class="btn btn-primary">Get Membership</a>
        <?php else: ?>
          <a href="pages/profile.php" class="btn btn-primary">My Profile</a>
        <?php endif; ?>
      </section>

    </div>
  </div>
</section>

  <!-- Classes -->
  <section class="classes-bg" id="classes">
    <div class="inner">
      <div class="tag">What We Offer</div>
      <h2 class="title">Classes & Training</h2>

      <div class="classes-grid">

        <article class="class-card">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M6 4v16M18 4v16M3 8h3M18 8h3M3 16h3M18 16h3M6 12h12" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          <h3>Powerlifting</h3>
          <p>Master the squat, bench, and deadlift under expert supervision. Build raw strength that lasts.</p>
          <div class="class-meta">60 min &nbsp; All levels</div>
        </article>

        <article class="class-card">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
            <path d="M12 8v4l3 3" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          <h3>HIIT</h3>
          <p>High-intensity intervals designed to torch calories and build conditioning fast.</p>
          <div class="class-meta">45 min &nbsp; Intermediate</div>
        </article>

        <article class="class-card">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 3c-1.5 3-4 5-4 8a4 4 0 008 0c0-3-2.5-5-4-8z" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          <h3>CrossFit</h3>
          <p>Functional movements at high intensity. Build endurance and toughness.</p>
          <div class="class-meta">50 min &nbsp; Advanced</div>
        </article>

        <article class="class-card">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 18l4-8 4 5 3-3 5 6" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          <h3>Yoga</h3>
          <p>Recovery, flexibility, and mental focus. Balance your training.</p>
          <div class="class-meta">60 min &nbsp; All levels</div>
        </article>

      </div>

      <div style="text-align: center; margin-top: 3rem;">
        <a href="pages/classes.php" class="btn btn-ghost">View Full Schedule</a>
      </div>
    </div>
  </section>

  <!-- Trainers -->
  <section id="trainers">
    <div class="inner">
      <div class="tag">The Team</div>
      <h2 class="title">Meet Your Coaches</h2>

      <div class="trainers-grid">

      <article class="trainer">
        <div class="trainer-photo">
         <img src="images/ronnie.jpg" alt="Ronnie Coleman">
        </div>
       <div class="trainer-info">
        <h3>Ronnie Coleman</h3>
        <div class="trainer-role">Head Coach</div>
        <p>8 time Mr.Olympia, now focused on strength training.</p>
       </div>
      </article>

      <article class="trainer">
        <div class="trainer-photo">
         <img src="images/sportacus.jpg" alt="Sportacus">
        </div>
       <div class="trainer-info">
        <h3>Sportacus</h3>
        <div class="trainer-role">HIIT & Conditioning</div>
        <p>Aerobics world champion focused on performance and conditioning.</p>
       </div>
      </article>

      <article class="trainer">
        <div class="trainer-photo">
         <img src="images/popeye.jpg" alt="Popeye">
        </div>
       <div class="trainer-info">
        <h3>Popeye</h3>
        <div class="trainer-role">CrossFit</div>
        <p>Specialist in movement quality and injury prevention.</p>
       </div>
      </article>

      <article class="trainer">
        <div class="trainer-photo">
         <img src="images/oogway.jpg" alt="Oogway">
        </div>
       <div class="trainer-info">
        <h3>Master Oogway</h3>
        <div class="trainer-role">Yoga & Recovery</div>
        <p>Yoga instructor focused on recovery, breathwork and relaxation.</p>
       </div>
      </article>

      </div>

      <div style="text-align: center; margin-top: 3rem;">
        <a href="pages/trainers.php" class="btn btn-ghost">View All Trainers</a>
      </div>
    </div>
  </section>

  <!-- Pricing -->
  <section class="pricing-bg" id="pricing">
    <div class="inner">
      <div class="tag">Membership</div>
      <h2 class="title">Simple Pricing</h2>

      <div class="pricing-grid">

        <article class="price-card">
          <h3>Starter</h3>
          <div class="price-amount">
            <span class="price-cur">€</span>
            <span class="price-num">29</span>
            <span class="price-per">/ month</span>
          </div>
          <ul>
            <li>Gym floor access</li>
            <li>Cardio zone</li>
            <li>Locker room</li>
            <li>Off-peak hours</li>
          </ul>
          <?php if ($role === 'member' && isset($planIdsByName['Starter'])): ?>
            <form method="post" action="actions/subscribe.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="plan_id" value="<?= (int)$planIdsByName['Starter'] ?>">
              <button type="submit" class="price-btn">Get Started</button>
            </form>
          <?php else: ?>
            <a href="<?= $user ? 'pages/dashboard.php' : 'pages/register.php' ?>" class="price-btn">Get Started</a>
          <?php endif; ?>
        </article>

        <article class="price-card featured">
          <div class="price-badge">Most Popular</div>
          <h3>Pro</h3>
          <div class="price-amount">
            <span class="price-cur">€</span>
            <span class="price-num">42</span>
            <span class="price-per">/ month</span>
          </div>
          <ul>
            <li>Full gym access</li>
            <li>Unlimited classes</li>
            <li>Recovery room</li>
            <li>Nutrition discount</li>
            <li>1 PT session</li>
          </ul>
          <?php if ($role === 'member' && isset($planIdsByName['Pro'])): ?>
            <form method="post" action="actions/subscribe.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="plan_id" value="<?= (int)$planIdsByName['Pro'] ?>">
              <button type="submit" class="price-btn">Join Pro</button>
            </form>
          <?php else: ?>
            <a href="<?= $user ? 'pages/dashboard.php' : 'pages/register.php' ?>" class="price-btn">Join Pro</a>
          <?php endif; ?>
        </article>

        <article class="price-card">
          <h3>Elite</h3>
          <div class="price-amount">
            <span class="price-cur">€</span>
            <span class="price-num">79</span>
            <span class="price-per">/ month</span>
          </div>
          <ul>
            <li>Everything in Pro</li>
            <li>Unlimited PT</li>
            <li>Custom program</li>
            <li>Priority booking</li>
            <li>Guest passes</li>
          </ul>
          <?php if ($role === 'member' && isset($planIdsByName['Elite'])): ?>
            <form method="post" action="actions/subscribe.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="plan_id" value="<?= (int)$planIdsByName['Elite'] ?>">
              <button type="submit" class="price-btn">Go Elite</button>
            </form>
          <?php else: ?>
            <a href="<?= $user ? 'pages/dashboard.php' : 'pages/register.php' ?>" class="price-btn">Go Elite</a>
          <?php endif; ?>
        </article>

      </div>
    </div>
  </section>

  <!-- Hours -->
  <section id="hours">
  <div class="inner">
    <div class="tag">Find Us</div>
    <h2 class="title">Hours & Location</h2>

    <div class="hours-grid">

      <table class="hours-table">
        <tr><td>Monday</td><td>06:00 – 23:00</td></tr>
        <tr><td>Tuesday</td><td>06:00 – 23:00</td></tr>
        <tr><td>Wednesday</td><td>06:00 – 23:00</td></tr>
        <tr><td>Thursday</td><td>06:00 – 23:00</td></tr>
        <tr><td>Friday</td><td>06:00 – 23:00</td></tr>
        <tr><td>Saturday</td><td>08:00 – 20:00</td></tr>
        <tr><td>Sunday</td><td>09:00 – 18:00</td></tr>
      </table>

      <address class="location">
        <div class="loc-item">
          <svg viewBox="0 0 20 20" fill="none">
            <path d="M10 2a6 6 0 016 6c0 4-6 10-6 10S4 12 4 8a6 6 0 016-6z" stroke="currentColor"/>
          </svg>
          <div>
            <div class="loc-label">Address</div>
            <div class="loc-val">67 Pumping Iron St, Barbell Bay, CA</div>
          </div>
        </div>

        <div class="loc-item">
          <svg viewBox="0 0 20 20" fill="none">
            <path d="M3 5a1 1 0 011-1h2.5l1 3L6 8.5a11 11 0 004.5 4.5L12 11.5l3 1V15a1 1 0 01-1 1z" stroke="currentColor"/>
          </svg>
          <div>
            <div class="loc-label">Phone</div>
            <div class="loc-val">+351 252 000 000</div>
          </div>
        </div>

        <div class="loc-item">
          <svg viewBox="0 0 20 20" fill="none">
            <path d="M2.5 5.5A1.5 1.5 0 014 4h12a1.5 1.5 0 011.5 1.5v9A1.5 1.5 0 0116 16H4a1.5 1.5 0 01-1.5-1.5z" stroke="currentColor"/>
          </svg>
          <div>
            <div class="loc-label">Email</div>
            <div class="loc-val">hello@w8gym.pt</div>
          </div>
        </div>

        <!-- Map Image -->
        <div class="map-ph">
          <img src="images/map.jpg" alt="Gym Location Map">
        </div>

      </address>

    </div>
  </div>
</section>

  <!-- CTA -->
  <section class="cta-band">
    <h2>READY TO START?</h2>
    <p>First week is on us. No contracts.</p>
    <?php if ($user): ?>
      <a href="pages/classes.php">Book Your Free Class</a>
    <?php else: ?>
      <a href="pages/register.php">Claim Free Week</a>
    <?php endif; ?>
  </section>

  </main>

  <!-- Footer -->
  <footer>
    <div class="footer-inner">

      <div class="footer-top">
        <div class="footer-brand">
          <svg viewBox="0 0 40 40" fill="none">
            <rect x="2" y="16" width="8" height="8" fill="currentColor"/>
            <rect x="30" y="16" width="8" height="8" fill="currentColor"/>
            <rect x="10" y="10" width="4" height="20" fill="currentColor"/>
            <rect x="26" y="10" width="4" height="20" fill="currentColor"/>
            <rect x="14" y="18" width="12" height="4" fill="currentColor"/>
          </svg>
          <span>W8</span>
        </div>

        <div class="footer-cols">
          <div class="footer-col">
            <h3>Gym</h3>
            <a href="#about">About</a>
            <a href="pages/classes.php">Classes</a>
            <a href="pages/trainers.php">Trainers</a>
            <a href="#pricing">Pricing</a>
          </div>

          <div class="footer-col">
            <h3>Info</h3>
            <a href="#hours">Hours</a>
            <a href="#hours">Location</a>
            <a href="#">FAQ</a>
          </div>

          <div class="footer-col">
            <h3>Account</h3>
            <?php if ($user): ?>
              <a href="pages/dashboard.php">Dashboard</a>
              <a href="pages/profile.php">Profile</a>
              <form method="post" action="actions/do_logout.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
                <button type="submit" class="nav-link-btn">Sign Out</button>
              </form>
            <?php else: ?>
              <a href="pages/sign_in.php">Sign In</a>
              <a href="pages/register.php">Register</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <span>© 2026 W8 Gym</span>
        <span>Barbell Bay</span>
      </div>

    </div>
  </footer>

</body>
</html>
