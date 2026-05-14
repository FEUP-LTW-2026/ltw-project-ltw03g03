<?php
declare(strict_types=1);

class User {
    public int     $id;
    public string  $username;
    public string  $email;
    public string  $firstName;
    public string  $lastName;
    public ?string $phone;
    public ?string $dateOfBirth;
    public ?string $photoPath;
    public string  $role;
    public int     $isActive;
    public string  $createdAt;

    public function name(): string {
        return $this->firstName . ' ' . $this->lastName;
    }

    // ── Fetch a single user by ID ─────────────────────────────────
    public static function getUser(PDO $db, int $id): ?self {
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    // ── Fetch user and verify password (for login) ────────────────
    public static function getUserWithPassword(PDO $db, string $email, string $password): ?self {
        $stmt = $db->prepare(
            'SELECT * FROM users WHERE (email = ? OR username = ?) AND is_active = 1'
        );
        $stmt->execute([$email, $email]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password_hash'])) {
            return self::fromRow($row);
        }
        return null;
    }

    // ── Fetch all users, optionally filtered by role ──────────────
    public static function getAll(PDO $db, ?string $role = null): array {
        if ($role) {
            $stmt = $db->prepare('SELECT * FROM users WHERE role = ? ORDER BY first_name, last_name');
            $stmt->execute([$role]);
        } else {
            $stmt = $db->query('SELECT * FROM users ORDER BY first_name, last_name');
        }
        return array_map([self::class, 'fromRow'], $stmt->fetchAll());
    }

    // ── Create a new user + role profile ─────────────────────────
    public static function create(PDO $db, array $data): self {
        $hash     = password_hash($data['password'], PASSWORD_BCRYPT);
        $username = self::generateUsername($db, $data['first_name'], $data['last_name']);
        $role     = in_array($data['role'] ?? '', ['member','trainer','admin'])
                    ? $data['role'] : 'member';

        $stmt = $db->prepare(
            'INSERT INTO users
               (username, email, password_hash, first_name, last_name, phone, date_of_birth, role)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $username,
            strtolower(trim($data['email'])),
            $hash,
            trim($data['first_name']),
            trim($data['last_name']),
            $data['phone']    ?? null,
            $data['dob']      ?? null,
            $role,
        ]);
        $userId = (int) $db->lastInsertId();

        // Create role-specific profile row
        if ($role === 'member') {
            $db->prepare('INSERT INTO member_profiles (user_id) VALUES (?)')->execute([$userId]);
        }
        if ($role === 'trainer') {
            $db->prepare(
                'INSERT INTO trainer_profiles (user_id, specialty) VALUES (?, ?)'
            )->execute([$userId, $data['specialty'] ?? null]);
        }

        return self::getUser($db, $userId);
    }

    // ── Save changes to an existing user ─────────────────────────
    public function save(PDO $db): void {
        $db->prepare(
            'UPDATE users
             SET username = ?, first_name = ?, last_name = ?, phone = ?, photo_path = ?
             WHERE id = ?'
        )->execute([
            $this->username,
            $this->firstName,
            $this->lastName,
            $this->phone,
            $this->photoPath,
            $this->id,
        ]);
    }

    // ── Save password separately (keeps save() clean) ────────────
    public function savePassword(PDO $db, string $newPassword): void {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
           ->execute([$hash, $this->id]);
    }

    // ── Deactivate / reactivate ───────────────────────────────────
    public function setActive(PDO $db, bool $active): void {
        $db->prepare('UPDATE users SET is_active = ? WHERE id = ?')
           ->execute([$active ? 1 : 0, $this->id]);
    }

    // ── Promote to admin ──────────────────────────────────────────
    public function promoteToAdmin(PDO $db): void {
        $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?")
           ->execute([$this->id]);
        $this->role = 'admin';
    }

    // ── Trainer profile helpers ───────────────────────────────────
    public function getTrainerProfile(PDO $db): ?array {
        $stmt = $db->prepare('SELECT * FROM trainer_profiles WHERE user_id = ?');
        $stmt->execute([$this->id]);
        return $stmt->fetch() ?: null;
    }

    public function saveTrainerProfile(PDO $db, array $data): void {
        $db->prepare(
            'INSERT INTO trainer_profiles (user_id, bio, specialty, certifications, years_experience)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT(user_id) DO UPDATE SET
               bio              = excluded.bio,
               specialty        = excluded.specialty,
               certifications   = excluded.certifications,
               years_experience = excluded.years_experience'
        )->execute([
            $this->id,
            $data['bio']              ?? null,
            $data['specialty']        ?? null,
            $data['certifications']   ?? null,
            (int)($data['years_experience'] ?? 0),
        ]);
    }

    // ── Member plan helpers ───────────────────────────────────────
    public function getMemberPlan(PDO $db): ?array {
        $stmt = $db->prepare(
            'SELECT mp.*, mem.plan_start, mem.plan_end
             FROM member_profiles mem
             LEFT JOIN membership_plans mp ON mp.id = mem.plan_id
             WHERE mem.user_id = ?'
        );
        $stmt->execute([$this->id]);
        return $stmt->fetch() ?: null;
    }

    // ── Private helpers ───────────────────────────────────────────
    private static function fromRow(array $row): self {
        $u              = new self();
        $u->id          = (int) $row['id'];
        $u->username    = $row['username'];
        $u->email       = $row['email'];
        $u->firstName   = $row['first_name'];
        $u->lastName    = $row['last_name'];
        $u->phone       = $row['phone']         ?? null;
        $u->dateOfBirth = $row['date_of_birth'] ?? null;
        $u->photoPath   = $row['photo_path']    ?? null;
        $u->role        = $row['role'];
        $u->isActive    = (int)($row['is_active'] ?? 1);
        $u->createdAt   = $row['created_at'];
        return $u;
    }

    private static function generateUsername(PDO $db, string $first, string $last): string {
        $base   = strtolower(preg_replace('/[^a-z0-9]/i', '', $first . $last));
        $name   = $base;
        $suffix = 1;
        while (true) {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$name]);
            if (!$stmt->fetch()) break;
            $name = $base . $suffix++;
        }
        return $name;
    }
}
