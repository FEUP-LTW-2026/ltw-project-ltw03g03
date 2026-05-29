<?php
/**
 * Demo data seeding helpers.
 *
 * Call ensure_demo_weekly_schedule($db) once per page-load on the Classes page
 * to guarantee the four demo trainers and next-week's schedule exist in the DB.
 * The function is idempotent: it upserts rather than duplicating rows, and the
 * static $done guard prevents it running more than once per PHP process.
 */

/**
 * Ensure the four demo trainers, their classes, and next-week's sessions exist.
 *
 * @param PDO $db An open PDO connection to w8_gym.db.
 */
function ensure_demo_weekly_schedule(PDO $db): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    $trainers = [
        'ronniec'  => ['email' => 'ronnie@w8gym.com',     'first' => 'Ronnie',   'last' => 'Coleman', 'phone' => '555-1001', 'photo' => 'images/ronnie.jpg',    'specialty' => 'strength',  'bio' => '8 time Mr. Olympia, now focused on strength training.',               'certs' => 'Strength coaching, powerlifting technique, contest prep', 'years' => 8],
        'sportacus'=> ['email' => 'sportacus@w8gym.com',  'first' => 'Sportacus','last' => '',        'phone' => '555-1002', 'photo' => 'images/sportacus.jpg', 'specialty' => 'cardio',    'bio' => 'Aerobics world champion focused on performance and conditioning.',    'certs' => 'HIIT coaching, mobility, athletic conditioning',          'years' => 10],
        'popeye'   => ['email' => 'popeye@w8gym.com',     'first' => 'Popeye',   'last' => '',        'phone' => '555-1003', 'photo' => 'images/popeye.jpg',    'specialty' => 'crossfit',  'bio' => 'Specialist in movement quality and injury prevention.',               'certs' => 'CrossFit L2, Olympic lifting, movement screening',        'years' => 6],
        'oogway'   => ['email' => 'oogway@w8gym.com',     'first' => 'Master',   'last' => 'Oogway',  'phone' => '555-1004', 'photo' => 'images/oogway.jpg',    'specialty' => 'yoga',      'bio' => 'Yoga instructor focused on recovery, breathwork and relaxation.',     'certs' => 'RYT-200, recovery coaching, breathwork',                  'years' => 12],
    ];

    $db->beginTransaction();
    try {
        // ── 1. Migrate any legacy usernames to the canonical demo names ──────
        foreach (['sarahj' => 'ronniec', 'mikec' => 'sportacus', 'emmar' => 'popeye', 'davidw' => 'oogway'] as $legacy => $target) {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$legacy]);
            $legacy_id = $stmt->fetchColumn();

            $target_stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
            $target_stmt->execute([$target]);

            if ($legacy_id && !$target_stmt->fetchColumn()) {
                $t = $trainers[$target];
                $db->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, photo_path = ?, role = "trainer", is_active = 1 WHERE username = ?')
                   ->execute([$t['first'], $t['last'], $t['email'], $t['phone'], $t['photo'], $legacy]);
                $db->prepare('UPDATE users SET username = ? WHERE username = ? AND NOT EXISTS (SELECT 1 FROM users WHERE username = ?)')
                   ->execute([$target, $legacy, $target]);
            }
        }

        // ── 2. Upsert demo trainer accounts + profiles ───────────────────────
        $trainer_ids = [];
        foreach ($trainers as $username => $t) {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $t['email']]);
            $id = $stmt->fetchColumn();

            if (!$id) {
                $db->prepare('INSERT INTO users (username, email, password_hash, first_name, last_name, phone, photo_path, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, "trainer", 1)')
                   ->execute([$username, $t['email'], $hash, $t['first'], $t['last'], $t['phone'], $t['photo']]);
                $id = (int)$db->lastInsertId();
            } else {
                $id = (int)$id;
                $db->prepare('UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ?, photo_path = ?, role = "trainer", is_active = 1 WHERE id = ?')
                   ->execute([$username, $t['email'], $t['first'], $t['last'], $t['phone'], $t['photo'], $id]);
            }

            $trainer_ids[$username] = $id;

            $profile = $db->prepare('SELECT user_id FROM trainer_profiles WHERE user_id = ?');
            $profile->execute([$id]);
            if ($profile->fetchColumn()) {
                $db->prepare('UPDATE trainer_profiles SET bio = ?, specialty = ?, certifications = ?, years_experience = ? WHERE user_id = ?')
                   ->execute([$t['bio'], $t['specialty'], $t['certs'], $t['years'], $id]);
            } else {
                $db->prepare('INSERT INTO trainer_profiles (user_id, bio, specialty, certifications, years_experience) VALUES (?, ?, ?, ?, ?)')
                   ->execute([$id, $t['bio'], $t['specialty'], $t['certs'], $t['years']]);
            }
        }

        // ── 3. Upsert the 12 demo classes ────────────────────────────────────
        $classes = [
            ['Strength Foundations',   'powerlifting', 'beginner',     'Learn strong squat, hinge, press, and bracing fundamentals.',            60, 18, 'ronniec'],
            ['Powerlifting Technique', 'powerlifting', 'intermediate', 'Build efficient squat, bench, and deadlift patterns.',                   60, 16, 'ronniec'],
            ['Advanced Powerlifting',  'powerlifting', 'advanced',     'Heavy strength work for experienced lifters chasing bigger totals.',     75, 14, 'ronniec'],
            ['HIIT Starter',           'hiit',         'beginner',     'Low-complexity intervals for building confidence and conditioning.',     45, 20, 'sportacus'],
            ['HIIT Burn',              'hiit',         'intermediate', 'Fast intervals, athletic circuits, and controlled intensity.',           45, 20, 'sportacus'],
            ['Athletic Conditioning',  'hiit',         'advanced',     'Demanding speed, agility, and endurance blocks.',                       50, 18, 'sportacus'],
            ['CrossFit Foundations',   'crossfit',     'beginner',     'Learn functional movements and safe scaling options.',                  60, 16, 'popeye'],
            ['MetCon Engine',          'crossfit',     'intermediate', 'Mixed modal conditioning with barbell, bodyweight, and cardio work.',   60, 18, 'popeye'],
            ['CrossFit Competition',   'crossfit',     'advanced',     'High-skill workouts for athletes comfortable with intensity.',          60, 14, 'popeye'],
            ['Gentle Yoga',            'yoga',         'beginner',     'A calm practice for mobility, balance, and breath.',                    60, 22, 'oogway'],
            ['Power Yoga Flow',        'yoga',         'intermediate', 'A stronger vinyasa flow that builds heat and control.',                 60, 20, 'oogway'],
            ['Mobility Yoga',          'yoga',         'advanced',     'Deep mobility work for experienced movers and heavy training recovery.',60, 18, 'oogway'],
        ];

        $class_ids = [];
        foreach ($classes as [$name, $type, $level, $description, $duration, $capacity, $trainer_username]) {
            $stmt = $db->prepare('SELECT id FROM classes WHERE name = ? LIMIT 1');
            $stmt->execute([$name]);
            $class_id = $stmt->fetchColumn();

            if (!$class_id) {
                $db->prepare('INSERT INTO classes (name, type, level, description, duration_min, capacity, trainer_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)')
                   ->execute([$name, $type, $level, $description, $duration, $capacity, $trainer_ids[$trainer_username]]);
                $class_id = (int)$db->lastInsertId();
            } else {
                $class_id = (int)$class_id;
                $db->prepare('UPDATE classes SET type = ?, level = ?, description = ?, duration_min = ?, capacity = ?, trainer_id = ?, is_active = 1 WHERE id = ?')
                   ->execute([$type, $level, $description, $duration, $capacity, $trainer_ids[$trainer_username], $class_id]);
            }
            $class_ids[$name] = $class_id;
        }

        // Ensure each type is assigned the correct demo trainer
        foreach (['yoga' => 'oogway', 'hiit' => 'sportacus', 'crossfit' => 'popeye', 'powerlifting' => 'ronniec'] as $type => $trainer_username) {
            $db->prepare('UPDATE classes SET trainer_id = ? WHERE type = ? AND is_active = 1')
               ->execute([$trainer_ids[$trainer_username], $type]);
        }

        // ── 4. Upsert next-week's 24 sessions ────────────────────────────────
        $week_start  = strtotime('monday next week');
        $day_offsets = ['mon' => 0, 'tue' => 1, 'wed' => 2, 'thu' => 3, 'fri' => 4, 'sat' => 5];
        $plan = [
            ['mon', '09:00', 'Strength Foundations'],   ['mon', '11:00', 'HIIT Starter'],          ['mon', '15:00', 'CrossFit Foundations'], ['mon', '18:00', 'Gentle Yoga'],
            ['tue', '09:00', 'Power Yoga Flow'],         ['tue', '11:00', 'Powerlifting Technique'], ['tue', '15:00', 'HIIT Burn'],            ['tue', '18:00', 'MetCon Engine'],
            ['wed', '09:00', 'CrossFit Foundations'],    ['wed', '11:00', 'Mobility Yoga'],          ['wed', '15:00', 'Advanced Powerlifting'],['wed', '18:00', 'Athletic Conditioning'],
            ['thu', '09:00', 'HIIT Starter'],            ['thu', '11:00', 'MetCon Engine'],          ['thu', '15:00', 'Gentle Yoga'],          ['thu', '18:00', 'Strength Foundations'],
            ['fri', '09:00', 'Powerlifting Technique'],  ['fri', '11:00', 'HIIT Burn'],              ['fri', '15:00', 'Power Yoga Flow'],      ['fri', '18:00', 'CrossFit Competition'],
            ['sat', '09:00', 'Mobility Yoga'],           ['sat', '11:00', 'Advanced Powerlifting'],  ['sat', '15:00', 'CrossFit Foundations'], ['sat', '18:00', 'Athletic Conditioning'],
        ];
        $planned = [];

        foreach ($plan as [$day, $time, $class_name]) {
            $scheduled_at = date('Y-m-d ' . $time . ':00', strtotime('+' . $day_offsets[$day] . ' days', $week_start));
            $class_id     = $class_ids[$class_name];
            $planned[]    = $class_id . '|' . $scheduled_at;

            $stmt = $db->prepare('SELECT id FROM class_sessions WHERE class_id = ? AND scheduled_at = ? LIMIT 1');
            $stmt->execute([$class_id, $scheduled_at]);
            if ($stmt->fetchColumn()) {
                $db->prepare('UPDATE class_sessions SET status = "scheduled" WHERE class_id = ? AND scheduled_at = ?')
                   ->execute([$class_id, $scheduled_at]);
            } else {
                $db->prepare('INSERT INTO class_sessions (class_id, scheduled_at, status) VALUES (?, ?, "scheduled")')
                   ->execute([$class_id, $scheduled_at]);
            }
        }

        // Cancel any next-week sessions for these classes that are no longer in the plan
        $managed_class_ids = array_values($class_ids);
        if ($managed_class_ids) {
            $placeholders = implode(',', array_fill(0, count($managed_class_ids), '?'));
            $start = date('Y-m-d 00:00:00', $week_start);
            $end   = date('Y-m-d 00:00:00', strtotime('+7 days', $week_start));
            $stmt  = $db->prepare("SELECT id, class_id, scheduled_at FROM class_sessions WHERE class_id IN ($placeholders) AND scheduled_at >= ? AND scheduled_at < ? AND status = 'scheduled'");
            $stmt->execute(array_merge($managed_class_ids, [$start, $end]));
            foreach ($stmt->fetchAll() as $session) {
                if (!in_array($session['class_id'] . '|' . $session['scheduled_at'], $planned, true)) {
                    $db->prepare('UPDATE class_sessions SET status = "cancelled" WHERE id = ?')
                       ->execute([$session['id']]);
                }
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
    }
}
