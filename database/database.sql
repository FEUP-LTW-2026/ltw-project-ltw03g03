--  W8 GYM — Database Schema
--  SQLite 3  (compatible with PHP PDO + SQLite)


PRAGMA journal_mode = WAL;       -- better concurrency for web apps
PRAGMA foreign_keys = ON;        -- enforce FK constraints in SQLite

-- ── Users (base table for all roles) ────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  username       TEXT    NOT NULL UNIQUE,
  email          TEXT    NOT NULL UNIQUE,
  password_hash  TEXT    NOT NULL,
  first_name     TEXT    NOT NULL,
  last_name      TEXT    NOT NULL,
  phone          TEXT    DEFAULT NULL,
  date_of_birth  TEXT    DEFAULT NULL,   -- ISO-8601: YYYY-MM-DD
  photo_path     TEXT    DEFAULT NULL,   -- stored relative path
  role           TEXT    NOT NULL DEFAULT 'member'
                         CHECK (role IN ('member','trainer','admin')),
  is_active      INTEGER NOT NULL DEFAULT 1,
  created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at     TEXT    NOT NULL DEFAULT (datetime('now'))
  -- NOTE: update updated_at via UPDATE trigger or in application code
);

CREATE INDEX IF NOT EXISTS idx_users_role  ON users (role);
CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);

-- ── Membership Plans ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS membership_plans (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  name           TEXT    NOT NULL,               -- Starter, Pro, Elite
  price_monthly  REAL    NOT NULL,
  description    TEXT    DEFAULT NULL,
  features       TEXT    DEFAULT NULL,           -- JSON array stored as TEXT
  is_active      INTEGER NOT NULL DEFAULT 1,
  created_at     TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ── Member Profiles ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS member_profiles (
  user_id        INTEGER PRIMARY KEY,
  plan_id        INTEGER DEFAULT NULL,
  plan_start     TEXT    DEFAULT NULL,           -- YYYY-MM-DD
  plan_end       TEXT    DEFAULT NULL,           -- YYYY-MM-DD
  -- fitness_goals TEXT removed: use the structured fitness_goals table
  FOREIGN KEY (user_id) REFERENCES users(id)            ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE SET NULL
);

-- ── Trainer Profiles ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS trainer_profiles (
  user_id          INTEGER PRIMARY KEY,
  bio              TEXT    DEFAULT NULL,
  specialty        TEXT    DEFAULT NULL
                           CHECK (specialty IN (
                             'strength','cardio','yoga',
                             'crossfit','pilates','martial_arts','nutrition'
                           ) OR specialty IS NULL),
  certifications   TEXT    DEFAULT NULL,
  years_experience INTEGER DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Equipment ────────────────────────────────────────────────────
-- Stores the equipment *type* (e.g. "Treadmill, 8 units").
-- Availability is computed from equipment_units + reservations,
-- not from a single status flag (which cannot reflect partial availability).
CREATE TABLE IF NOT EXISTS equipment (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  name         TEXT    NOT NULL,
  category     TEXT    NOT NULL DEFAULT 'other'
                       CHECK (category IN ('cardio','weights','machines','other')),
  notes        TEXT    DEFAULT NULL,
  added_at     TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ── Equipment Units ───────────────────────────────────────────────
-- One row per physical machine/item.
-- This allows tracking per-unit status (maintenance, retired, etc.)
-- and computing real-time availability via active reservations.
CREATE TABLE IF NOT EXISTS equipment_units (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  equipment_id  INTEGER NOT NULL,
  unit_label    TEXT    DEFAULT NULL,   -- e.g. "Treadmill #3"
  status        TEXT    NOT NULL DEFAULT 'available'
                        CHECK (status IN ('available','maintenance','retired')),
  notes         TEXT    DEFAULT NULL,
  FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_equip_units_equipment ON equipment_units (equipment_id);
CREATE INDEX IF NOT EXISTS idx_equip_units_status    ON equipment_units (status);

-- ── Classes ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS classes (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  name          TEXT    NOT NULL,
  type          TEXT    NOT NULL
                        CHECK (type IN ('powerlifting','hiit','crossfit','yoga','other')),
  level         TEXT    NOT NULL DEFAULT 'all'
                        CHECK (level IN ('all','beginner','intermediate','advanced')),
  description   TEXT    DEFAULT NULL,
  duration_min  INTEGER NOT NULL DEFAULT 60,
  capacity      INTEGER NOT NULL DEFAULT 20,
  trainer_id    INTEGER DEFAULT NULL,
  is_active     INTEGER NOT NULL DEFAULT 1,
  created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_classes_type    ON classes (type);
CREATE INDEX IF NOT EXISTS idx_classes_level   ON classes (level);
CREATE INDEX IF NOT EXISTS idx_classes_trainer ON classes (trainer_id);

-- ── Class Sessions (scheduled occurrences of a class) ────────────
CREATE TABLE IF NOT EXISTS class_sessions (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  class_id      INTEGER NOT NULL,
  scheduled_at  TEXT    NOT NULL,   -- ISO-8601: YYYY-MM-DD HH:MM:SS
  status        TEXT    NOT NULL DEFAULT 'scheduled'
                        CHECK (status IN ('scheduled','cancelled','completed')),
  created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_sessions_class     ON class_sessions (class_id);
CREATE INDEX IF NOT EXISTS idx_sessions_scheduled ON class_sessions (scheduled_at);

-- ── Enrollments ──────────────────────────────────────────────────
-- waitlist_position: NULL = not on waitlist; 1 = first in queue, etc.
CREATE TABLE IF NOT EXISTS enrollments (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  session_id        INTEGER NOT NULL,
  member_id         INTEGER NOT NULL,
  enrolled_at       TEXT    NOT NULL DEFAULT (datetime('now')),
  status            TEXT    NOT NULL DEFAULT 'enrolled'
                            CHECK (status IN ('enrolled','cancelled','waitlist','attended')),
  waitlist_position INTEGER DEFAULT NULL,   -- NULL unless status = 'waitlist'
  UNIQUE (session_id, member_id),
  FOREIGN KEY (session_id) REFERENCES class_sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id)  REFERENCES users(id)          ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_enroll_member  ON enrollments (member_id);
CREATE INDEX IF NOT EXISTS idx_enroll_session ON enrollments (session_id);

-- ── Class Reviews ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS class_reviews (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  session_id INTEGER NOT NULL,
  member_id  INTEGER NOT NULL,
  rating     INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment    TEXT    DEFAULT NULL,
  created_at TEXT    NOT NULL DEFAULT (datetime('now')),
  UNIQUE (session_id, member_id),
  FOREIGN KEY (session_id) REFERENCES class_sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id)  REFERENCES users(id)          ON DELETE CASCADE
);

-- ── Equipment Reservations ───────────────────────────────────────
-- References a specific unit, not just the equipment type.
-- Overlap prevention must be enforced in PHP before INSERT.
CREATE TABLE IF NOT EXISTS equipment_reservations (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  unit_id       INTEGER NOT NULL,   -- specific physical unit
  member_id     INTEGER NOT NULL,
  reserved_from TEXT    NOT NULL,   -- ISO-8601
  reserved_to   TEXT    NOT NULL,   -- ISO-8601
  status        TEXT    NOT NULL DEFAULT 'active'
                        CHECK (status IN ('active','cancelled','completed')),
  created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (unit_id)   REFERENCES equipment_units(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES users(id)           ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_equip_res_unit   ON equipment_reservations (unit_id);
CREATE INDEX IF NOT EXISTS idx_equip_res_member ON equipment_reservations (member_id);

-- ── PT (Personal Training) Bookings ─────────────────────────────
CREATE TABLE IF NOT EXISTS pt_bookings (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  trainer_id   INTEGER NOT NULL,
  member_id    INTEGER NOT NULL,
  scheduled_at TEXT    NOT NULL,
  duration_min INTEGER NOT NULL DEFAULT 60,
  status       TEXT    NOT NULL DEFAULT 'pending'
                       CHECK (status IN ('pending','confirmed','cancelled','completed')),
  notes        TEXT    DEFAULT NULL,
  created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id)  REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_pt_trainer   ON pt_bookings (trainer_id);
CREATE INDEX IF NOT EXISTS idx_pt_member    ON pt_bookings (member_id);
CREATE INDEX IF NOT EXISTS idx_pt_scheduled ON pt_bookings (scheduled_at);

-- ── PT Availability ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pt_availability (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  trainer_id    INTEGER NOT NULL,
  start_time    TEXT    NOT NULL,   -- ISO-8601: YYYY-MM-DD HH:MM:SS
  end_time      TEXT    NOT NULL,   -- ISO-8601: YYYY-MM-DD HH:MM:SS
  is_booked     INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_pt_avail_trainer ON pt_availability (trainer_id);
CREATE INDEX IF NOT EXISTS idx_pt_avail_start   ON pt_availability (start_time);

-- ── Workout Logs (Member Progress Tracking) ──────────────────────
CREATE TABLE IF NOT EXISTS workout_logs (
  id        INTEGER PRIMARY KEY AUTOINCREMENT,
  member_id INTEGER NOT NULL,
  logged_at TEXT    NOT NULL DEFAULT (datetime('now')),
  notes     TEXT    DEFAULT NULL,
  FOREIGN KEY (member_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_wlog_member ON workout_logs (member_id);

CREATE TABLE IF NOT EXISTS workout_exercises (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  log_id        INTEGER NOT NULL,
  exercise_name TEXT    NOT NULL,
  sets          INTEGER DEFAULT NULL,
  reps          INTEGER DEFAULT NULL,   -- SMALLINT range; INTEGER covers it
  weight_kg     REAL    DEFAULT NULL,
  duration_sec  INTEGER DEFAULT NULL,
  FOREIGN KEY (log_id) REFERENCES workout_logs(id) ON DELETE CASCADE
);

-- ── Fitness Goals ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS fitness_goals (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  member_id   INTEGER NOT NULL,
  title       TEXT    NOT NULL,
  target_date TEXT    DEFAULT NULL,   -- YYYY-MM-DD
  achieved    INTEGER NOT NULL DEFAULT 0,
  created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (member_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Notifications ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER NOT NULL,
  type       TEXT    NOT NULL,   -- 'class_reminder','waitlist_update', etc.
  message    TEXT    NOT NULL,
  is_read    INTEGER NOT NULL DEFAULT 0,
  created_at TEXT    NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_notif_user_unread ON notifications (user_id, is_read);

-- ── Disputes / Reports ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS disputes (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  reporter_id  INTEGER NOT NULL,
  resolved_by  INTEGER DEFAULT NULL,   -- admin user_id who resolved it
  subject      TEXT    NOT NULL,
  description  TEXT    NOT NULL,
  status       TEXT    NOT NULL DEFAULT 'open'
                       CHECK (status IN ('open','in_review','resolved','closed')),
  admin_reply  TEXT    DEFAULT NULL,
  created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at   TEXT    NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ── Remember-me Tokens ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS remember_tokens (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER NOT NULL,
  token_hash TEXT    NOT NULL,
  expires_at TEXT    NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_remember_token ON remember_tokens (token_hash);


-- ═══════════════════════════════════════════════════════════════
--  TRIGGERS
--  SQLite has no ON UPDATE CURRENT_TIMESTAMP column option.
--  These triggers maintain updated_at automatically so application
--  code doesn't have to remember to set it on every UPDATE.
-- ═══════════════════════════════════════════════════════════════

CREATE TRIGGER IF NOT EXISTS trg_users_updated
  AFTER UPDATE ON users
  FOR EACH ROW
  BEGIN
    UPDATE users SET updated_at = datetime('now') WHERE id = OLD.id;
  END;

CREATE TRIGGER IF NOT EXISTS trg_classes_updated
  AFTER UPDATE ON classes
  FOR EACH ROW
  BEGIN
    UPDATE classes SET updated_at = datetime('now') WHERE id = OLD.id;
  END;

CREATE TRIGGER IF NOT EXISTS trg_equipment_updated
  AFTER UPDATE ON equipment
  FOR EACH ROW
  BEGIN
    UPDATE equipment SET updated_at = datetime('now') WHERE id = OLD.id;
  END;

CREATE TRIGGER IF NOT EXISTS trg_disputes_updated
  AFTER UPDATE ON disputes
  FOR EACH ROW
  BEGIN
    UPDATE disputes SET updated_at = datetime('now') WHERE id = OLD.id;
  END;


-- ═══════════════════════════════════════════════════════════════
--  SEED DATA
--  Includes lookup data, demo accounts, schedules, reservations,
--  notifications, and profile rows for a complete local database.
-- ═══════════════════════════════════════════════════════════════

-- Membership Plans (match the pricing section in home.html)
INSERT INTO membership_plans (name, price_monthly, description, features) VALUES
  ('Starter', 29.00,
   'Off-peak access to gym floor and cardio zone.',
   '["Gym floor access","Cardio zone","Locker room","Off-peak hours"]'),
  ('Pro', 42.00,
   'Full access plus unlimited classes and recovery room.',
   '["Full gym access","Unlimited classes","Recovery room","Nutrition discount","1 PT session/month"]'),
  ('Elite', 79.00,
   'Everything in Pro plus unlimited PT and priority booking.',
   '["Everything in Pro","Unlimited PT sessions","Custom program","Priority booking","Guest passes"]');

-- Equipment types
INSERT INTO equipment (name, category) VALUES
  ('Treadmill',         'cardio'),
  ('Stationary Bike',   'cardio'),
  ('Rowing Machine',    'cardio'),
  ('Barbell (Olympic)', 'weights'),
  ('Dumbbell Set',      'weights'),
  ('Weight Bench',      'machines'),
  ('Squat Rack',        'machines'),
  ('Leg Press Machine', 'machines'),
  ('Cable Machine',     'machines'),
  ('Pull-up Bar',       'other');

-- Equipment units: generate one row per physical unit per type.
-- Treadmill x8
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Treadmill #1'  FROM equipment WHERE name = 'Treadmill';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Treadmill #2'  FROM equipment WHERE name = 'Treadmill';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Treadmill #3'  FROM equipment WHERE name = 'Treadmill';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Treadmill #4'  FROM equipment WHERE name = 'Treadmill';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Treadmill #5'  FROM equipment WHERE name = 'Treadmill';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Treadmill #6'  FROM equipment WHERE name = 'Treadmill';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Treadmill #7'  FROM equipment WHERE name = 'Treadmill';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Treadmill #8'  FROM equipment WHERE name = 'Treadmill';
-- Stationary Bike x6
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bike #1' FROM equipment WHERE name = 'Stationary Bike';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bike #2' FROM equipment WHERE name = 'Stationary Bike';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bike #3' FROM equipment WHERE name = 'Stationary Bike';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bike #4' FROM equipment WHERE name = 'Stationary Bike';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bike #5' FROM equipment WHERE name = 'Stationary Bike';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bike #6' FROM equipment WHERE name = 'Stationary Bike';
-- Rowing Machine x4
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Rower #1' FROM equipment WHERE name = 'Rowing Machine';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Rower #2' FROM equipment WHERE name = 'Rowing Machine';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Rower #3' FROM equipment WHERE name = 'Rowing Machine';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Rower #4' FROM equipment WHERE name = 'Rowing Machine';
-- Squat Rack x4
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Squat Rack #1' FROM equipment WHERE name = 'Squat Rack';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Squat Rack #2' FROM equipment WHERE name = 'Squat Rack';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Squat Rack #3' FROM equipment WHERE name = 'Squat Rack';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Squat Rack #4' FROM equipment WHERE name = 'Squat Rack';
-- Leg Press x2
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Leg Press #1' FROM equipment WHERE name = 'Leg Press Machine';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Leg Press #2' FROM equipment WHERE name = 'Leg Press Machine';
-- Cable Machine x3
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Cable Machine #1' FROM equipment WHERE name = 'Cable Machine';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Cable Machine #2' FROM equipment WHERE name = 'Cable Machine';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Cable Machine #3' FROM equipment WHERE name = 'Cable Machine';
-- Weight Bench x6
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bench #1' FROM equipment WHERE name = 'Weight Bench';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bench #2' FROM equipment WHERE name = 'Weight Bench';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bench #3' FROM equipment WHERE name = 'Weight Bench';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bench #4' FROM equipment WHERE name = 'Weight Bench';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bench #5' FROM equipment WHERE name = 'Weight Bench';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Bench #6' FROM equipment WHERE name = 'Weight Bench';
-- Pull-up Bar x4
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Pull-up Bar #1' FROM equipment WHERE name = 'Pull-up Bar';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Pull-up Bar #2' FROM equipment WHERE name = 'Pull-up Bar';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Pull-up Bar #3' FROM equipment WHERE name = 'Pull-up Bar';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Pull-up Bar #4' FROM equipment WHERE name = 'Pull-up Bar';
-- Barbells and Dumbbells: not individually tracked (shared/racked equipment)
-- They are represented in equipment but do not need per-unit reservation rows.
-- Their availability is shown as always available unless admin marks otherwise.
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Olympic Barbell Set' FROM equipment WHERE name = 'Barbell (Olympic)';
INSERT INTO equipment_units (equipment_id, unit_label) SELECT id, 'Dumbbell Rack'       FROM equipment WHERE name = 'Dumbbell Set';


-- Demo users and activity.
-- README credentials:
--   admin / p4s5w0rd
--   member / 1234
--   trainer / 1234

-- Admin account.
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role, is_active) VALUES
('admin', 'admin@w8gym.com', '$2y$10$8miVM1XqfCSXEs.rrcJYhOyv7I.ljZuvLvtcc3pzykrCE20VtPxlu', 'System', 'Administrator', '555-0001', 'admin', 1);

-- Trainer accounts. Password for all trainer rows is 1234.
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, photo_path, role, is_active) VALUES
('trainer', 'trainer@w8gym.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Demo', 'Trainer', '555-1000', NULL, 'trainer', 1),
('ronniec', 'ronnie@w8gym.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Ronnie', 'Coleman', '555-1001', 'images/ronnie.jpg', 'trainer', 1),
('oogway', 'oogway@w8gym.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Master', 'Oogway', '555-1002', 'images/oogway.jpg', 'trainer', 1),
('sportacus', 'sportacus@w8gym.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Sportacus', '', '555-1003', 'images/sportacus.jpg', 'trainer', 1),
('popeye', 'popeye@w8gym.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Popeye', '', '555-1004', 'images/popeye.jpg', 'trainer', 1);

-- Member accounts. Password for all member rows is 1234.
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role, is_active) VALUES
('member', 'member@w8gym.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Demo', 'Member', '555-2000', 'member', 1),
('aliceb', 'alice@example.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Alice', 'Brown', '555-2001', 'member', 1),
('bobl', 'bob@example.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Bob', 'Lee', '555-2002', 'member', 1),
('carolm', 'carol@example.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Carol', 'Martinez', '555-2003', 'member', 1),
('davidb', 'david.b@example.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'David', 'Brown', '555-2004', 'member', 1),
('emilyc', 'emily@example.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Emily', 'Clark', '555-2005', 'member', 1),
('frankw', 'frank@example.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Frank', 'Wright', '555-2006', 'member', 1),
('graceh', 'grace@example.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Grace', 'Harris', '555-2007', 'member', 1),
('henryk', 'henry@example.com', '$2y$10$ZDPovMGy8o9l3juTXe8rzepd.6pvI2Vf8qYegvoK8vMHnCZTXrJk6', 'Henry', 'Kim', '555-2008', 'member', 1);

INSERT INTO trainer_profiles (user_id, bio, specialty, certifications, years_experience) VALUES
((SELECT id FROM users WHERE username = 'trainer'), 'Demo trainer account for testing trainer features.', 'strength', 'Demo certification', 5),
((SELECT id FROM users WHERE username = 'ronniec'), '8 time Mr. Olympia, now focused on strength training.', 'strength', 'Strength coaching, powerlifting technique, contest prep', 8),
((SELECT id FROM users WHERE username = 'sportacus'), 'Aerobics world champion focused on performance and conditioning.', 'cardio', 'HIIT coaching, mobility, athletic conditioning', 10),
((SELECT id FROM users WHERE username = 'popeye'), 'Specialist in movement quality and injury prevention.', 'crossfit', 'CrossFit L2, Olympic lifting, movement screening', 6),
((SELECT id FROM users WHERE username = 'oogway'), 'Yoga instructor focused on recovery, breathwork and relaxation.', 'yoga', 'RYT-200, recovery coaching, breathwork', 12);

INSERT INTO member_profiles (user_id, plan_id, plan_start, plan_end) VALUES
((SELECT id FROM users WHERE username = 'member'), (SELECT id FROM membership_plans WHERE name = 'Starter'), date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'aliceb'), (SELECT id FROM membership_plans WHERE name = 'Pro'), date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'bobl'), (SELECT id FROM membership_plans WHERE name = 'Starter'), date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'carolm'), (SELECT id FROM membership_plans WHERE name = 'Elite'), date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'davidb'), (SELECT id FROM membership_plans WHERE name = 'Pro'), date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'emilyc'), (SELECT id FROM membership_plans WHERE name = 'Starter'), date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'frankw'), (SELECT id FROM membership_plans WHERE name = 'Pro'), date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'graceh'), (SELECT id FROM membership_plans WHERE name = 'Elite'), date('now'), date('now', '+30 days')),
((SELECT id FROM users WHERE username = 'henryk'), (SELECT id FROM membership_plans WHERE name = 'Starter'), date('now'), date('now', '+30 days'));

INSERT INTO classes (name, type, level, description, duration_min, capacity, trainer_id) VALUES
('Power Yoga Flow', 'yoga', 'intermediate', 'Dynamic vinyasa flow building heat and flexibility', 60, 25, (SELECT id FROM users WHERE username = 'oogway')),
('Gentle Morning Yoga', 'yoga', 'beginner', 'Slow-paced practice focusing on alignment and relaxation', 60, 20, (SELECT id FROM users WHERE username = 'oogway')),
('HIIT Burn', 'hiit', 'advanced', 'High-intensity interval training for maximum calorie burn', 45, 20, (SELECT id FROM users WHERE username = 'sportacus')),
('CrossFit Foundations', 'crossfit', 'beginner', 'Learn proper form for Olympic lifts and CrossFit basics', 60, 15, (SELECT id FROM users WHERE username = 'popeye')),
('Strength Training 101', 'powerlifting', 'beginner', 'Master squat, bench, and deadlift technique', 60, 20, (SELECT id FROM users WHERE username = 'ronniec')),
('Advanced Powerlifting', 'powerlifting', 'advanced', 'For experienced lifters looking to increase 1RMs', 90, 15, (SELECT id FROM users WHERE username = 'ronniec')),
('Pilates Core', 'yoga', 'intermediate', 'Mat Pilates focusing on core strength and stability', 50, 20, (SELECT id FROM users WHERE username = 'oogway')),
('MetCon', 'crossfit', 'intermediate', 'Metabolic conditioning workout with varied movements', 60, 18, (SELECT id FROM users WHERE username = 'popeye'));

INSERT INTO class_sessions (class_id, scheduled_at) VALUES
((SELECT id FROM classes WHERE name = 'Power Yoga Flow'), datetime('now', '+1 day', '09:00:00')),
((SELECT id FROM classes WHERE name = 'Power Yoga Flow'), datetime('now', '+3 days', '09:00:00')),
((SELECT id FROM classes WHERE name = 'Power Yoga Flow'), datetime('now', '+5 days', '09:00:00')),
((SELECT id FROM classes WHERE name = 'Gentle Morning Yoga'), datetime('now', '+2 days', '08:00:00')),
((SELECT id FROM classes WHERE name = 'Gentle Morning Yoga'), datetime('now', '+4 days', '08:00:00')),
((SELECT id FROM classes WHERE name = 'Pilates Core'), datetime('now', '+1 day', '17:30:00')),
((SELECT id FROM classes WHERE name = 'Pilates Core'), datetime('now', '+4 days', '17:30:00')),
((SELECT id FROM classes WHERE name = 'HIIT Burn'), datetime('now', '+1 day', '18:00:00')),
((SELECT id FROM classes WHERE name = 'HIIT Burn'), datetime('now', '+3 days', '18:00:00')),
((SELECT id FROM classes WHERE name = 'HIIT Burn'), datetime('now', '+5 days', '18:00:00')),
((SELECT id FROM classes WHERE name = 'CrossFit Foundations'), datetime('now', '+2 days', '19:00:00')),
((SELECT id FROM classes WHERE name = 'CrossFit Foundations'), datetime('now', '+4 days', '19:00:00')),
((SELECT id FROM classes WHERE name = 'MetCon'), datetime('now', '+2 days', '07:00:00')),
((SELECT id FROM classes WHERE name = 'MetCon'), datetime('now', '+4 days', '07:00:00')),
((SELECT id FROM classes WHERE name = 'Strength Training 101'), datetime('now', '+1 day', '17:00:00')),
((SELECT id FROM classes WHERE name = 'Strength Training 101'), datetime('now', '+3 days', '17:00:00')),
((SELECT id FROM classes WHERE name = 'Advanced Powerlifting'), datetime('now', '+2 days', '18:30:00')),
((SELECT id FROM classes WHERE name = 'Advanced Powerlifting'), datetime('now', '+5 days', '18:30:00'));

INSERT INTO enrollments (session_id, member_id, status) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 7), (SELECT id FROM users WHERE username = 'bobl'), 'enrolled'),
((SELECT id FROM class_sessions LIMIT 1 OFFSET 14), (SELECT id FROM users WHERE username = 'carolm'), 'enrolled'),
((SELECT id FROM class_sessions LIMIT 1 OFFSET 1), (SELECT id FROM users WHERE username = 'davidb'), 'enrolled'),
((SELECT id FROM class_sessions LIMIT 1 OFFSET 5), (SELECT id FROM users WHERE username = 'emilyc'), 'enrolled'),
((SELECT id FROM class_sessions LIMIT 1 OFFSET 12), (SELECT id FROM users WHERE username = 'frankw'), 'enrolled'),
((SELECT id FROM class_sessions LIMIT 1 OFFSET 16), (SELECT id FROM users WHERE username = 'graceh'), 'enrolled');

INSERT INTO enrollments (session_id, member_id, status, waitlist_position) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 7), (SELECT id FROM users WHERE username = 'henryk'), 'waitlist', 1);

INSERT INTO class_reviews (session_id, member_id, rating, comment) VALUES
((SELECT id FROM class_sessions LIMIT 1 OFFSET 7), (SELECT id FROM users WHERE username = 'bobl'), 4, 'Great workout, really intense!'),
((SELECT id FROM class_sessions LIMIT 1 OFFSET 14), (SELECT id FROM users WHERE username = 'carolm'), 5, 'Ronnie helped me fix my deadlift form perfectly.');

INSERT INTO notifications (user_id, type, message, is_read) VALUES
((SELECT id FROM users WHERE username = 'henryk'), 'waitlist_update', 'You are on the waitlist for HIIT Burn. Current position: 1', 0),
((SELECT id FROM users WHERE username = 'bobl'), 'class_reminder', 'Reminder: HIIT Burn tomorrow at 6:00 PM', 0);

INSERT INTO equipment_reservations (unit_id, member_id, reserved_from, reserved_to, status) VALUES
((SELECT eu.id FROM equipment_units eu JOIN equipment e ON e.id = eu.equipment_id WHERE e.name = 'Treadmill' LIMIT 1),
 (SELECT id FROM users WHERE username = 'aliceb'),
 datetime('now', '+1 day', '10:00:00'),
 datetime('now', '+1 day', '11:00:00'),
 'active');

INSERT INTO pt_availability (trainer_id, start_time, end_time, is_booked) VALUES
((SELECT id FROM users WHERE username = 'trainer'), datetime('now', '+1 day', '09:00:00'), datetime('now', '+1 day', '10:00:00'), 0),
((SELECT id FROM users WHERE username = 'ronniec'), datetime('now', '+1 day', '10:00:00'), datetime('now', '+1 day', '11:00:00'), 0),
((SELECT id FROM users WHERE username = 'ronniec'), datetime('now', '+1 day', '11:00:00'), datetime('now', '+1 day', '12:00:00'), 0),
((SELECT id FROM users WHERE username = 'oogway'), datetime('now', '+2 days', '14:00:00'), datetime('now', '+2 days', '15:00:00'), 0),
((SELECT id FROM users WHERE username = 'popeye'), datetime('now', '+3 days', '09:00:00'), datetime('now', '+3 days', '10:00:00'), 0);
