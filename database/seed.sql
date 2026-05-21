-- database/seed.sql
-- Seed data for W8 Gym Management Platform
-- Run this after creating the schema

-- =====================================================
-- USERS (passwords are 'password123' for all demo users)
-- Password hash: password_hash('password123', PASSWORD_BCRYPT)
-- =====================================================

-- Admin User
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role, is_active) VALUES
('admin', 'admin@w8gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', '555-0001', 'admin', 1);

-- Trainers
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role, is_active) VALUES
('sarahj', 'sarah@w8gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', '555-1001', 'trainer', 1),
('mikec', 'mike@w8gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Chen', '555-1002', 'trainer', 1),
('emmar', 'emma@w8gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma', 'Rodriguez', '555-1003', 'trainer', 1),
('davidw', 'david@w8gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Williams', '555-1004', 'trainer', 1);

-- Members (8 active members)
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role, is_active) VALUES
('aliceb', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice', 'Brown', '555-2001', 'member', 1),
('bobl', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob', 'Lee', '555-2002', 'member', 1),
('carolm', 'carol@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carol', 'Martinez', '555-2003', 'member', 1),
('davidb', 'david.b@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Brown', '555-2004', 'member', 1),
('emilyc', 'emily@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily', 'Clark', '555-2005', 'member', 1),
('frankw', 'frank@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Frank', 'Wright', '555-2006', 'member', 1),
('graceh', 'grace@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Grace', 'Harris', '555-2007', 'member', 1),
('henryk', 'henry@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Henry', 'Kim', '555-2008', 'member', 1);

-- =====================================================
-- TRAINER PROFILES
-- =====================================================
INSERT INTO trainer_profiles (user_id, bio, specialty, certifications, years_experience) VALUES
((SELECT id FROM users WHERE username = 'sarahj'), 
 'Certified yoga instructor with 8+ years of experience. Focus on alignment, breath work, and building strength mindfully.',
 'yoga',
 'RYT-200, Yin Yoga Certified, Yoga Nidra Facilitator',
 8),
((SELECT id FROM users WHERE username = 'mikec'),
 'Former competitive powerlifter turned coach. I help members build functional strength safely.',
 'strength',
 'NASM CPT, USA Powerlifting Coach, FRC Mobility',
 10),
((SELECT id FROM users WHERE username = 'emmar'),
 'High-energy HIIT and CrossFit coach. Let''s push your limits together.',
 'cardio',
 'CrossFit L2, F45 Certified, CPR/AED',
 6),
((SELECT id FROM users WHERE username = 'davidw'),
 'Nutrition specialist and body transformation coach. I combine strength training with dietary guidance.',
 'nutrition',
 'Precision Nutrition L1, ISSA Nutritionist, Kettlebell Certified',
 12);

-- =====================================================
-- MEMBER PROFILES (assign membership plans)
-- =====================================================
-- Get plan IDs
-- Starter = 1, Pro = 2, Elite = 3

INSERT INTO member_profiles (user_id, plan_id, plan_start, plan_end) VALUES
((SELECT id FROM users WHERE username = 'aliceb'), 2, date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'bobl'), 1, date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'carolm'), 3, date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'davidb'), 2, date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'emilyc'), 1, date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'frankw'), 2, date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'graceh'), 3, date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'henryk'), 1, date('now'), date('now', '+30 days'));

-- =====================================================
-- CLASSES
-- =====================================================
INSERT INTO classes (name, type, level, description, duration_min, capacity, trainer_id) VALUES
('Power Yoga Flow', 'yoga', 'intermediate', 'Dynamic vinyasa flow building heat and flexibility', 60, 25, (SELECT id FROM users WHERE username = 'sarahj')),
('Gentle Morning Yoga', 'yoga', 'beginner', 'Slow-paced practice focusing on alignment and relaxation', 60, 20, (SELECT id FROM users WHERE username = 'sarahj')),
('HIIT Burn', 'hiit', 'advanced', 'High-intensity interval training for maximum calorie burn', 45, 20, (SELECT id FROM users WHERE username = 'emmar')),
('CrossFit Foundations', 'crossfit', 'beginner', 'Learn proper form for Olympic lifts and CrossFit basics', 60, 15, (SELECT id FROM users WHERE username = 'emmar')),
('Strength Training 101', 'powerlifting', 'beginner', 'Master squat, bench, and deadlift technique', 60, 20, (SELECT id FROM users WHERE username = 'mikec')),
('Advanced Powerlifting', 'powerlifting', 'advanced', 'For experienced lifters looking to increase 1RMs', 90, 15, (SELECT id FROM users WHERE username = 'mikec')),
('Pilates Core', 'yoga', 'intermediate', 'Mat Pilates focusing on core strength and stability', 50, 20, (SELECT id FROM users WHERE username = 'sarahj')),
('MetCon', 'crossfit', 'intermediate', 'Metabolic conditioning workout with varied movements', 60, 18, (SELECT id FROM users WHERE username = 'emmar'));

-- =====================================================
-- CLASS SESSIONS (scheduled for the next 7 days)
-- =====================================================
-- Helper: Generate dates for next 7 days
-- Mon=1, Tue=2, Wed=3, Thu=4, Fri=5, Sat=6, Sun=0

-- Yoga sessions
INSERT INTO class_sessions (class_id, scheduled_at) VALUES
((SELECT id FROM classes WHERE name = 'Power Yoga Flow'), datetime('now', '+1 day', '09:00:00')),
((SELECT id FROM classes WHERE name = 'Power Yoga Flow'), datetime('now', '+3 days', '09:00:00')),
((SELECT id FROM classes WHERE name = 'Power Yoga Flow'), datetime('now', '+5 days', '09:00:00')),
((SELECT id FROM classes WHERE name = 'Gentle Morning Yoga'), datetime('now', '+2 days', '08:00:00')),
((SELECT id FROM classes WHERE name = 'Gentle Morning Yoga'), datetime('now', '+4 days', '08:00:00')),
((SELECT id FROM classes WHERE name = 'Pilates Core'), datetime('now', '+1 day', '17:30:00')),
((SELECT id FROM classes WHERE name = 'Pilates Core'), datetime('now', '+4 days', '17:30:00'));

-- HIIT/CrossFit sessions
INSERT INTO class_sessions (class_id, scheduled_at) VALUES
((SELECT id FROM classes WHERE name = 'HIIT Burn'), datetime('now', '+1 day', '18:00:00')),
((SELECT id FROM classes WHERE name = 'HIIT Burn'), datetime('now', '+3 days', '18:00:00')),
((SELECT id FROM classes WHERE name = 'HIIT Burn'), datetime('now', '+5 days', '18:00:00')),
((SELECT id FROM classes WHERE name = 'CrossFit Foundations'), datetime('now', '+2 days', '19:00:00')),
((SELECT id FROM classes WHERE name = 'CrossFit Foundations'), datetime('now', '+4 days', '19:00:00')),
((SELECT id FROM classes WHERE name = 'MetCon'), datetime('now', '+2 days', '07:00:00')),
((SELECT id FROM classes WHERE name = 'MetCon'), datetime('now', '+4 days', '07:00:00'));

-- Strength sessions
INSERT INTO class_sessions (class_id, scheduled_at) VALUES
((SELECT id FROM classes WHERE name = 'Strength Training 101'), datetime('now', '+1 day', '17:00:00')),
((SELECT id FROM classes WHERE name = 'Strength Training 101'), datetime('now', '+3 days', '17:00:00')),
((SELECT id FROM classes WHERE name = 'Advanced Powerlifting'), datetime('now', '+2 days', '18:30:00')),
((SELECT id FROM classes WHERE name = 'Advanced Powerlifting'), datetime('now', '+5 days', '18:30:00'));

-- =====================================================
-- ENROLLMENTS (mix of enrolled and waitlist)
-- =====================================================
-- Enroll members in sessions (use actual session IDs, but we'll use subqueries)

-- Alice enrolled in Power Yoga (session 1)
INSERT INTO enrollments (session_id, member_id, status) VALUES
((SELECT id FROM class_sessions LIMIT 1), (SELECT id FROM users WHERE username = 'aliceb'), 'enrolled');

-- Bob enrolled in HIIT Burn (session 8)
INSERT INTO enrollments (session_id, member_id, status) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 7), (SELECT id FROM users WHERE username = 'bobl'), 'enrolled');

-- Carol enrolled in Strength 101 (session 15)
INSERT INTO enrollments (session_id, member_id, status) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 14), (SELECT id FROM users WHERE username = 'carolm'), 'enrolled');

-- David enrolled in Power Yoga (session 2)
INSERT INTO enrollments (session_id, member_id, status) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 1), (SELECT id FROM users WHERE username = 'davidb'), 'enrolled');

-- Emily enrolled in Pilates (session 6)
INSERT INTO enrollments (session_id, member_id, status) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 5), (SELECT id FROM users WHERE username = 'emilyc'), 'enrolled');

-- Frank enrolled in MetCon (session 13)
INSERT INTO enrollments (session_id, member_id, status) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 12), (SELECT id FROM users WHERE username = 'frankw'), 'enrolled');

-- Grace enrolled in Advanced Powerlifting (session 17)
INSERT INTO enrollments (session_id, member_id, status) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 16), (SELECT id FROM users WHERE username = 'graceh'), 'enrolled');

-- Henry on waitlist for HIIT Burn (session 8)
INSERT INTO enrollments (session_id, member_id, status, waitlist_position) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 7), (SELECT id FROM users WHERE username = 'henryk'), 'waitlist', 1);

-- =====================================================
-- REVIEWS (for past/completed sessions)
-- =====================================================
-- Note: These sessions are in the past relative to DB creation
INSERT INTO class_reviews (session_id, member_id, rating, comment) VALUES
((SELECT id FROM class_sessions LIMIT 1), (SELECT id FROM users WHERE username = 'aliceb'), 5, 'Amazing class! Sarah is so knowledgeable and patient.'),
((SELECT id FROM class_sessions LIMIT 1 OFFSET 7), (SELECT id FROM users WHERE username = 'bobl'), 4, 'Great workout, really intense!'),
((SELECT id FROM class_sessions LIMIT 1 OFFSET 14), (SELECT id FROM users WHERE username = 'carolm'), 5, 'Mike helped me fix my deadlift form perfectly.');

-- =====================================================
-- NOTIFICATIONS
-- =====================================================
INSERT INTO notifications (user_id, type, message, is_read) VALUES
((SELECT id FROM users WHERE username = 'henryk'), 'waitlist_update', 'You are on the waitlist for HIIT Burn. Current position: 1', 0),
((SELECT id FROM users WHERE username = 'aliceb'), 'class_reminder', 'Reminder: Power Yoga Flow tomorrow at 9:00 AM', 1),
((SELECT id FROM users WHERE username = 'bobl'), 'class_reminder', 'Reminder: HIIT Burn tomorrow at 6:00 PM', 0);

-- =====================================================
-- EQUIPMENT RESERVATIONS (sample)
-- =====================================================
-- A reservation for tomorrow from 10-11 AM on Treadmill #1
INSERT INTO equipment_reservations (unit_id, member_id, reserved_from, reserved_to, status) VALUES
((SELECT eu.id FROM equipment_units eu JOIN equipment e ON e.id = eu.equipment_id WHERE e.name = 'Treadmill' LIMIT 1),
 (SELECT id FROM users WHERE username = 'aliceb'),
 datetime('now', '+1 day', '10:00:00'),
 datetime('now', '+1 day', '11:00:00'),
 'active');

-- =====================================================
-- PT AVAILABILITY
-- =====================================================
INSERT INTO pt_availability (trainer_id, start_time, end_time, is_booked) VALUES
((SELECT id FROM users WHERE username = 'mikec'), datetime('now', '+1 day', '10:00:00'), datetime('now', '+1 day', '11:00:00'), 0),
((SELECT id FROM users WHERE username = 'mikec'), datetime('now', '+1 day', '11:00:00'), datetime('now', '+1 day', '12:00:00'), 0),
((SELECT id FROM users WHERE username = 'sarahj'), datetime('now', '+2 days', '14:00:00'), datetime('now', '+2 days', '15:00:00'), 0),
((SELECT id FROM users WHERE username = 'emmar'), datetime('now', '+3 days', '09:00:00'), datetime('now', '+3 days', '10:00:00'), 0);

 