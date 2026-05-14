<?php
declare(strict_types=1);

class Enrollment {
    public int     $id;
    public int     $sessionId;
    public int     $memberId;
    public string  $status;
    public ?int    $waitlistPosition;
    public string  $enrolledAt;

    // ── Enroll a member in a session (or add to waitlist if full) ─
    public static function enroll(PDO $db, int $memberId, int $sessionId): self {
        // Count current enrolled spots
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM enrollments WHERE session_id = ? AND status = 'enrolled'"
        );
        $stmt->execute([$sessionId]);
        $enrolledCount = (int) $stmt->fetchColumn();

        // Get class capacity
        $stmt = $db->prepare(
            'SELECT c.capacity FROM class_sessions cs
             JOIN classes c ON c.id = cs.class_id
             WHERE cs.id = ?'
        );
        $stmt->execute([$sessionId]);
        $capacity = (int) $stmt->fetchColumn();

        $isFull = $enrolledCount >= $capacity;

        if ($isFull) {
            // Get next waitlist position
            $stmt = $db->prepare(
                "SELECT COALESCE(MAX(waitlist_position), 0) + 1
                 FROM enrollments WHERE session_id = ? AND status = 'waitlist'"
            );
            $stmt->execute([$sessionId]);
            $position = (int) $stmt->fetchColumn();

            $db->prepare(
                "INSERT INTO enrollments (session_id, member_id, status, waitlist_position)
                 VALUES (?, ?, 'waitlist', ?)"
            )->execute([$sessionId, $memberId, $position]);
        } else {
            $db->prepare(
                "INSERT INTO enrollments (session_id, member_id, status)
                 VALUES (?, ?, 'enrolled')"
            )->execute([$sessionId, $memberId]);
        }

        return self::get($db, $memberId, $sessionId);
    }

    // ── Cancel enrollment and promote first waitlisted member ─────
    public static function cancel(PDO $db, int $memberId, int $sessionId): void {
        // Find the enrollment
        $stmt = $db->prepare(
            'SELECT id, status FROM enrollments WHERE session_id = ? AND member_id = ?'
        );
        $stmt->execute([$sessionId, $memberId]);
        $enrollment = $stmt->fetch();

        if (!$enrollment) return;

        $wasEnrolled = $enrollment['status'] === 'enrolled';

        // Cancel it
        $db->prepare("UPDATE enrollments SET status = 'cancelled' WHERE id = ?")
           ->execute([$enrollment['id']]);

        // If a real spot was freed, promote first on waitlist
        if ($wasEnrolled) {
            $stmt = $db->prepare(
                "SELECT id, member_id FROM enrollments
                 WHERE session_id = ? AND status = 'waitlist'
                 ORDER BY waitlist_position ASC LIMIT 1"
            );
            $stmt->execute([$sessionId]);
            $next = $stmt->fetch();

            if ($next) {
                $db->prepare(
                    "UPDATE enrollments
                     SET status = 'enrolled', waitlist_position = NULL
                     WHERE id = ?"
                )->execute([$next['id']]);

                // Recalculate positions for remaining waitlist
                self::reorderWaitlist($db, $sessionId);

                // Notify the promoted member
                $db->prepare(
                    "INSERT INTO notifications (user_id, type, message) VALUES (?, 'waitlist_update', ?)"
                )->execute([
                    $next['member_id'],
                    'Good news! A spot opened up and you have been enrolled from the waitlist.'
                ]);
            }
        }
    }

    // ── Get a single enrollment ───────────────────────────────────
    public static function get(PDO $db, int $memberId, int $sessionId): ?self {
        $stmt = $db->prepare(
            'SELECT * FROM enrollments WHERE session_id = ? AND member_id = ?'
        );
        $stmt->execute([$sessionId, $memberId]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    // ── Get all enrollments for a member ─────────────────────────
    public static function getForMember(PDO $db, int $memberId, string $status = 'enrolled'): array {
        $stmt = $db->prepare(
            'SELECT e.*, cs.scheduled_at, c.name, c.type, c.duration_min,
                    u.first_name as trainer_first, u.last_name as trainer_last
             FROM enrollments e
             JOIN class_sessions cs ON cs.id = e.session_id
             JOIN classes c ON c.id = cs.class_id
             LEFT JOIN users u ON u.id = c.trainer_id
             WHERE e.member_id = ? AND e.status = ?
             ORDER BY cs.scheduled_at ASC'
        );
        $stmt->execute([$memberId, $status]);
        return $stmt->fetchAll();
    }

    // ── Get all enrollments for a session (roster) ────────────────
    public static function getRoster(PDO $db, int $sessionId): array {
        $stmt = $db->prepare(
            "SELECT e.*, u.first_name, u.last_name, u.email, u.photo_path
             FROM enrollments e
             JOIN users u ON u.id = e.member_id
             WHERE e.session_id = ? AND e.status IN ('enrolled','waitlist')
             ORDER BY e.status DESC, e.waitlist_position ASC, e.enrolled_at ASC"
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }

    // ── Private helpers ───────────────────────────────────────────
    private static function reorderWaitlist(PDO $db, int $sessionId): void {
        $stmt = $db->prepare(
            "SELECT id FROM enrollments WHERE session_id = ? AND status = 'waitlist'
             ORDER BY waitlist_position ASC"
        );
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $i => $row) {
            $db->prepare('UPDATE enrollments SET waitlist_position = ? WHERE id = ?')
               ->execute([$i + 1, $row['id']]);
        }
    }

    private static function fromRow(array $row): self {
        $e                   = new self();
        $e->id               = (int) $row['id'];
        $e->sessionId        = (int) $row['session_id'];
        $e->memberId         = (int) $row['member_id'];
        $e->status           = $row['status'];
        $e->waitlistPosition = isset($row['waitlist_position']) ? (int)$row['waitlist_position'] : null;
        $e->enrolledAt       = $row['enrolled_at'];
        return $e;
    }
}
