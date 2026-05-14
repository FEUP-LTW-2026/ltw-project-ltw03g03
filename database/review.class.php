<?php
declare(strict_types=1);

class Review {
    public int     $id;
    public int     $sessionId;
    public int     $memberId;
    public int     $rating;
    public ?string $comment;
    public string  $createdAt;

    // ── Insert or update a review ─────────────────────────────────
    public static function create(PDO $db, array $data): void {
        $db->prepare(
            'INSERT INTO class_reviews (session_id, member_id, rating, comment)
             VALUES (?, ?, ?, ?)
             ON CONFLICT(session_id, member_id) DO UPDATE SET
               rating  = excluded.rating,
               comment = excluded.comment'
        )->execute([
            (int) $data['session_id'],
            (int) $data['member_id'],
            (int) $data['rating'],
            $data['comment'] ?? null,
        ]);
    }

    // ── Get all reviews for a class session ───────────────────────
    public static function getForSession(PDO $db, int $sessionId): array {
        $stmt = $db->prepare(
            'SELECT r.*, u.first_name, u.last_name, u.photo_path
             FROM class_reviews r
             JOIN users u ON u.id = r.member_id
             WHERE r.session_id = ?
             ORDER BY r.created_at DESC'
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }

    // ── Get average rating for a class (across all sessions) ──────
    public static function getAverageForClass(PDO $db, int $classId): ?float {
        $stmt = $db->prepare(
            'SELECT AVG(r.rating) FROM class_reviews r
             JOIN class_sessions cs ON cs.id = r.session_id
             WHERE cs.class_id = ?'
        );
        $stmt->execute([$classId]);
        $avg = $stmt->fetchColumn();
        return $avg !== false ? round((float)$avg, 1) : null;
    }

    // ── Get average rating for a trainer (across all their classes) 
    public static function getAverageForTrainer(PDO $db, int $trainerId): ?float {
        $stmt = $db->prepare(
            'SELECT AVG(r.rating) FROM class_reviews r
             JOIN class_sessions cs ON cs.id = r.session_id
             JOIN classes c ON c.id = cs.class_id
             WHERE c.trainer_id = ?'
        );
        $stmt->execute([$trainerId]);
        $avg = $stmt->fetchColumn();
        return $avg !== false ? round((float)$avg, 1) : null;
    }

    // ── Get a member's review for a specific session ──────────────
    public static function getByMemberAndSession(PDO $db, int $memberId, int $sessionId): ?self {
        $stmt = $db->prepare(
            'SELECT * FROM class_reviews WHERE member_id = ? AND session_id = ?'
        );
        $stmt->execute([$memberId, $sessionId]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    private static function fromRow(array $row): self {
        $r            = new self();
        $r->id        = (int) $row['id'];
        $r->sessionId = (int) $row['session_id'];
        $r->memberId  = (int) $row['member_id'];
        $r->rating    = (int) $row['rating'];
        $r->comment   = $row['comment'] ?? null;
        $r->createdAt = $row['created_at'];
        return $r;
    }
}
