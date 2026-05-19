<?php
declare(strict_types=1);

class Equipment {
    public int     $id;
    public string  $name;
    public string  $category;
    public int     $totalUnits;
    public ?string $notes;

    // ── Get all equipment with live availability ──────────────────
    public static function getAll(PDO $db): array {
        $stmt = $db->query(
            "SELECT e.*,
                    COUNT(eu.id) AS total_units,
                    SUM(CASE WHEN eu.status = 'available' THEN 1 ELSE 0 END) AS available_units,
                    SUM(CASE WHEN eu.status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_units
             FROM equipment e
             LEFT JOIN equipment_units eu ON eu.equipment_id = e.id
             GROUP BY e.id
             ORDER BY e.category, e.name"
        );
        return $stmt->fetchAll();
    }

    // ── Get a single equipment type with its units ────────────────
    public static function getById(PDO $db, int $id): ?array {
        $stmt = $db->prepare('SELECT * FROM equipment WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ── Get all units for an equipment type ───────────────────────
    public static function getUnits(PDO $db, int $equipmentId): array {
        $stmt = $db->prepare(
            'SELECT * FROM equipment_units WHERE equipment_id = ? ORDER BY unit_label'
        );
        $stmt->execute([$equipmentId]);
        return $stmt->fetchAll();
    }

    // ── Get available units for a time slot ───────────────────────
    // A unit is available if it is status=available AND has no active
    // reservation overlapping the requested window.
    public static function getAvailableUnits(PDO $db, int $equipmentId, string $from, string $to): array {
        $stmt = $db->prepare(
            "SELECT eu.* FROM equipment_units eu
             WHERE eu.equipment_id = ?
               AND eu.status = 'available'
               AND eu.id NOT IN (
                 SELECT unit_id FROM equipment_reservations
                 WHERE status = 'active'
                   AND reserved_from < ?
                   AND reserved_to   > ?
               )
             ORDER BY eu.unit_label"
        );
        $stmt->execute([$equipmentId, $to, $from]);
        return $stmt->fetchAll();
    }

    // ── Create a new equipment type ───────────────────────────────
    public static function create(PDO $db, array $data): int {
        $db->prepare(
            'INSERT INTO equipment (name, category, notes) VALUES (?, ?, ?)'
        )->execute([
            $data['name'],
            $data['category'] ?? 'other',
            $data['notes'] ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    // ── Update an equipment type ──────────────────────────────────
    public static function update(PDO $db, int $id, array $data): void {
        $db->prepare(
            'UPDATE equipment SET name=?, category=?, notes=? WHERE id=?'
        )->execute([$data['name'], $data['category'], $data['notes'] ?? null, $id]);
    }

    // ── Add a unit to an equipment type ──────────────────────────
    public static function addUnit(PDO $db, int $equipmentId, string $label): int {
        $db->prepare(
            'INSERT INTO equipment_units (equipment_id, unit_label) VALUES (?, ?)'
        )->execute([$equipmentId, $label]);
        return (int) $db->lastInsertId();
    }

    // ── Update a unit's status ────────────────────────────────────
    public static function setUnitStatus(PDO $db, int $unitId, string $status): void {
        $db->prepare('UPDATE equipment_units SET status=? WHERE id=?')
           ->execute([$status, $unitId]);
    }
}


class EquipmentReservation {
    public int    $id;
    public int    $unitId;
    public int    $memberId;
    public string $reservedFrom;
    public string $reservedTo;
    public string $status;

    // ── Reserve a specific unit ───────────────────────────────────
    public static function create(PDO $db, int $memberId, int $unitId, string $from, string $to): void {
        $db->prepare(
            "INSERT INTO equipment_reservations (unit_id, member_id, reserved_from, reserved_to, status)
             VALUES (?, ?, ?, ?, 'active')"
        )->execute([$unitId, $memberId, $from, $to]);
    }

    // ── Cancel a reservation ──────────────────────────────────────
    public static function cancel(PDO $db, int $reservationId, int $memberId): void {
        $db->prepare(
            "UPDATE equipment_reservations SET status = 'cancelled'
             WHERE id = ? AND member_id = ?"
        )->execute([$reservationId, $memberId]);
    }

    // ── Get upcoming reservations for a member ────────────────────
    public static function getForMember(PDO $db, int $memberId): array {
        $stmt = $db->prepare(
            "SELECT er.*, eu.unit_label, e.name AS equipment_name, e.category
             FROM equipment_reservations er
             JOIN equipment_units eu ON eu.id = er.unit_id
             JOIN equipment e ON e.id = eu.equipment_id
             WHERE er.member_id = ? AND er.status = 'active' AND er.reserved_to >= datetime('now')
             ORDER BY er.reserved_from ASC"
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    // ── Get all active reservations for a unit (admin view) ───────
    public static function getForUnit(PDO $db, int $unitId): array {
        $stmt = $db->prepare(
            "SELECT er.*, u.first_name, u.last_name, u.email
             FROM equipment_reservations er
             JOIN users u ON u.id = er.member_id
             WHERE er.unit_id = ? AND er.status = 'active'
             ORDER BY er.reserved_from ASC"
        );
        $stmt->execute([$unitId]);
        return $stmt->fetchAll();
    }
}
