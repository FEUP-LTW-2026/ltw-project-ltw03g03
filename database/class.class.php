<?php
declare(strict_types=1);

class GymClass {
    public int     $id;
    public string  $name;
    public string  $type;
    public string  $level;
    public ?string $description;
    public int     $durationMin;
    public int     $capacity;
    public ?int    $trainerId;
    public int     $isActive;

    // ── Fetch all active classes ──────────────────────────────────
    public static function getAll(PDO $db): array {
        $stmt = $db->query(
            'SELECT c.*, u.first_name as trainer_first, u.last_name as trainer_last
             FROM classes c
             LEFT JOIN users u ON u.id = c.trainer_id
             WHERE c.is_active = 1
             ORDER BY c.name'
        );
        return $stmt->fetchAll();
    }

    // ── Fetch a single class ──────────────────────────────────────
    public static function getById(PDO $db, int $id): ?array {
        $stmt = $db->prepare(
            'SELECT c.*, u.first_name as trainer_first, u.last_name as trainer_last
             FROM classes c
             LEFT JOIN users u ON u.id = c.trainer_id
             WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ── Get classes assigned to a specific trainer ────────────────
    public static function getByTrainer(PDO $db, int $trainerId): array {
        $stmt = $db->prepare(
            'SELECT * FROM classes WHERE trainer_id = ? AND is_active = 1 ORDER BY name'
        );
        $stmt->execute([$trainerId]);
        return $stmt->fetchAll();
    }

    // ── Create a new class ────────────────────────────────────────
    public static function create(PDO $db, array $data): int {
        $db->prepare(
            'INSERT INTO classes (name, type, level, description, duration_min, capacity, trainer_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['name'],
            $data['type'],
            $data['level']       ?? 'all',
            $data['description'] ?? null,
            (int)($data['duration_min'] ?? 60),
            (int)($data['capacity']     ?? 20),
            $data['trainer_id']  ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    // ── Update an existing class ──────────────────────────────────
    public static function update(PDO $db, int $id, array $data): void {
        $db->prepare(
            'UPDATE classes SET name=?, type=?, level=?, description=?,
             duration_min=?, capacity=?, trainer_id=? WHERE id=?'
        )->execute([
            $data['name'],
            $data['type'],
            $data['level']       ?? 'all',
            $data['description'] ?? null,
            (int)($data['duration_min'] ?? 60),
            (int)($data['capacity']     ?? 20),
            $data['trainer_id']  ?? null,
            $id,
        ]);
    }

    // ── Soft delete ───────────────────────────────────────────────
    public static function deactivate(PDO $db, int $id): void {
        $db->prepare('UPDATE classes SET is_active = 0 WHERE id = ?')->execute([$id]);
    }
}


class ClassSession {
    public int    $id;
    public int    $classId;
    public string $scheduledAt;
    public string $status;

    // ── Fetch upcoming sessions with filters ──────────────────────
    public static function getUpcoming(PDO $db, array $filters = [], int $memberId = 0): array {
        $where  = ["cs.scheduled_at >= datetime('now')", "cs.status = 'scheduled'", 'c.is_active = 1'];
        $params = [];

        if (!empty($filters['type'])) {
            $where[]  = 'c.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['level'])) {
            $where[]  = 'c.level = ?';
            $params[] = $filters['level'];
        }
        if (!empty($filters['trainer_id'])) {
            $where[]  = 'c.trainer_id = ?';
            $params[] = (int) $filters['trainer_id'];
        }
        if (!empty($filters['day'])) {
            // SQLite strftime: 0=Sun,1=Mon,...,6=Sat
            $dayMap = ['sun'=>0,'mon'=>1,'tue'=>2,'wed'=>3,'thu'=>4,'fri'=>5,'sat'=>6];
            if (isset($dayMap[$filters['day']])) {
                $where[]  = "CAST(strftime('%w', cs.scheduled_at) AS INTEGER) = ?";
                $params[] = $dayMap[$filters['day']];
            }
        }

        // Subqueries for enrollment status of the requesting member
        $memberEnrolled  = $memberId ? "MAX(CASE WHEN e.member_id = $memberId AND e.status = 'enrolled' THEN 1 ELSE 0 END)" : '0';
        $memberWaitlisted = $memberId ? "MAX(CASE WHEN e.member_id = $memberId AND e.status = 'waitlist' THEN 1 ELSE 0 END)" : '0';

        $sql = "
            SELECT cs.id          AS session_id,
                   cs.scheduled_at,
                   cs.status,
                   c.id           AS class_id,
                   c.name,
                   c.type,
                   c.level,
                   c.description,
                   c.duration_min,
                   c.capacity,
                   u.id           AS trainer_id,
                   u.first_name   AS trainer_first,
                   u.last_name    AS trainer_last,
                   u.photo_path   AS trainer_photo,
                   COUNT(CASE WHEN e.status = 'enrolled' THEN 1 END) AS enrolled_count,
                   $memberEnrolled   AS i_am_enrolled,
                   $memberWaitlisted AS i_am_waitlisted
            FROM class_sessions cs
            JOIN classes c ON c.id = cs.class_id
            LEFT JOIN users u ON u.id = c.trainer_id
            LEFT JOIN enrollments e ON e.session_id = cs.id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY cs.id
            ORDER BY cs.scheduled_at ASC
            LIMIT 300
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Fetch sessions for a specific trainer ─────────────────────
    public static function getForTrainer(PDO $db, int $trainerId): array {
        $stmt = $db->prepare(
            "SELECT cs.*, c.name, c.type, c.capacity,
                    COUNT(CASE WHEN e.status = 'enrolled' THEN 1 END) AS enrolled_count
             FROM class_sessions cs
             JOIN classes c ON c.id = cs.class_id
             LEFT JOIN enrollments e ON e.session_id = cs.id
             WHERE c.trainer_id = ? AND cs.scheduled_at >= datetime('now')
             GROUP BY cs.id
             ORDER BY cs.scheduled_at ASC"
        );
        $stmt->execute([$trainerId]);
        return $stmt->fetchAll();
    }

    // ── Get a single session with class info ──────────────────────
    public static function getById(PDO $db, int $sessionId): ?array {
        $stmt = $db->prepare(
            'SELECT cs.*, c.name, c.type, c.level, c.capacity, c.duration_min,
                    u.first_name as trainer_first, u.last_name as trainer_last
             FROM class_sessions cs
             JOIN classes c ON c.id = cs.class_id
             LEFT JOIN users u ON u.id = c.trainer_id
             WHERE cs.id = ?'
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetch() ?: null;
    }

    // ── Schedule a new session ────────────────────────────────────
    public static function create(PDO $db, int $classId, string $scheduledAt): int {
        $db->prepare(
            'INSERT INTO class_sessions (class_id, scheduled_at) VALUES (?, ?)'
        )->execute([$classId, $scheduledAt]);
        return (int) $db->lastInsertId();
    }

    // ── Cancel a session ──────────────────────────────────────────
    public static function cancel(PDO $db, int $sessionId): void {
        $db->prepare("UPDATE class_sessions SET status = 'cancelled' WHERE id = ?")
           ->execute([$sessionId]);
    }

    // ── Mark a session as completed ───────────────────────────────
    public static function complete(PDO $db, int $sessionId): void {
        $db->prepare("UPDATE class_sessions SET status = 'completed' WHERE id = ?")
           ->execute([$sessionId]);
        // Mark all enrolled members as attended
        $db->prepare(
            "UPDATE enrollments SET status = 'attended' WHERE session_id = ? AND status = 'enrolled'"
        )->execute([$sessionId]);
    }
}
