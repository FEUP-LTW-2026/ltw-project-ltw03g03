<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('member');
$db = get_db();
$flash = get_flash();
$role = $user['role'];

$stmt = $db->prepare(
    'SELECT cs.id as session_id,
            cs.scheduled_at,
            c.name,
            c.type,
            c.duration_min,
            u.first_name as trainer_first,
            u.last_name as trainer_last,
            cr.rating,
            cr.comment,
            cr.created_at as reviewed_at
     FROM enrollments e
     JOIN class_sessions cs ON cs.id = e.session_id
     JOIN classes c ON c.id = cs.class_id
     LEFT JOIN users u ON u.id = c.trainer_id
     LEFT JOIN class_reviews cr ON cr.session_id = cs.id AND cr.member_id = e.member_id
     WHERE e.member_id = ?
       AND e.status = "attended"
     ORDER BY cs.scheduled_at DESC'
);
$stmt->execute([$user['id']]);
$attended = $stmt->fetchAll();

function review_stars(?int $rating): string {
    $html = '<div class="stars" aria-label="' . (int)$rating . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        $class = $rating && $i <= $rating ? 'star' : 'star star--empty';
        $html .= '<svg class="' . $class . '" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
              . '<path d="M10 1l2.39 4.84L18 6.91l-4 3.9.94 5.5L10 13.77l-4.94 2.54L6 10.81 2 6.91l5.61-.07z"/>'
              . '</svg>';
    }
    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>W8 - My Reviews</title>
  <link rel="stylesheet" href="../css/base.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/profile.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@300;400;500;600;700&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
  <div class="bg-fixed bg-grid"></div>
  <div class="bg-fixed bg-diagonal"></div>
  <span class="corner corner--tl"></span>
  <span class="corner corner--br"></span>

  <?php
    $current_page = 'my_reviews';
    require_once __DIR__ . '/../includes/nav.php';
  ?>

  <?php if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> flash--page">
    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <main class="page-wrap" style="grid-template-columns:1fr;max-width:960px;">
    <section class="profile-section">
      <header class="profile-section__header">
        <div>
          <div class="tag">Member Area</div>
          <div class="profile-section__title">Class Reviews</div>
        </div>
      </header>
      <div class="profile-section__body">
        <?php if (!$attended): ?>
          <div class="attended-empty">
            No attended classes ready for review.
          </div>
        <?php else: ?>
          <div class="attended-list">
            <?php foreach ($attended as $class): ?>
            <article class="attended-item attended-item--review">
              <div class="attended-item__info">
                <div class="attended-item__name"><?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="attended-item__meta">
                  <?= date('d M Y, H:i', strtotime($class['scheduled_at'])) ?>
                  &middot;
                  <?= htmlspecialchars(trim($class['trainer_first'] . ' ' . $class['trainer_last']), ENT_QUOTES, 'UTF-8') ?>
                  &middot;
                  <?= (int)$class['duration_min'] ?> min
                </div>

                <?php if ($class['rating']): ?>
                  <div class="review-current">
                    <?= review_stars((int)$class['rating']) ?>
                    <?php if (!empty($class['comment'])): ?>
                      <p><?= htmlspecialchars($class['comment'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <form class="review-form" action="../actions/submit_review.php" method="POST">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="session_id" value="<?= (int)$class['session_id'] ?>">
                  <input type="hidden" name="redirect" value="../pages/my_reviews.php">

                  <label class="field__label">Rating</label>
                  <div class="review-rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <label>
                      <input type="radio" name="rating" value="<?= $i ?>" <?= (int)$class['rating'] === $i ? 'checked' : '' ?> required>
                      <span><?= $i ?></span>
                    </label>
                    <?php endfor; ?>
                  </div>

                  <label class="field__label" for="comment-<?= (int)$class['session_id'] ?>">Comment</label>
                  <textarea
                    id="comment-<?= (int)$class['session_id'] ?>"
                    name="comment"
                    class="field__input review-form__comment"
                    rows="3"
                    placeholder="How was the class?"><?= htmlspecialchars($class['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

                  <button type="submit" class="btn btn-primary">
                    <?= $class['rating'] ? 'Update Review' : 'Submit Review' ?>
                  </button>
                </form>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>
