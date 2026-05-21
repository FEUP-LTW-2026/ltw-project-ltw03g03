<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Only logged-in users can view equipment (or as per your auth rules)
    if (!isset($_SESSION['user']['id']) && !isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $db = get_db();

    // Whitelist filters
    $allowed_statuses    = ['available', 'in_use', 'maintenance'];
    $filter_status       = $_GET['status']   ?? '';
    $filter_category     = $_GET['category'] ?? '';

    if (!in_array($filter_status, $allowed_statuses, true)) {
        $filter_status = '';
    }
    $filter_category = trim($filter_category);

    $params = [];
    $where_clauses = ["1=1"];

    if ($filter_category !== '') {
        $where_clauses[] = 'e.category = ?';
        $params[] = $filter_category;
    }

    $sql = "
        SELECT
            e.id, e.name, e.category, e.updated_at,
            COUNT(u.id) as quantity,
            SUM(CASE WHEN u.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_qty,
            SUM(CASE WHEN u.status = 'available' AND res.id IS NOT NULL THEN 1 ELSE 0 END) as in_use_qty,
            SUM(CASE WHEN u.status = 'available' AND res.id IS NULL THEN 1 ELSE 0 END) as available_qty
        FROM equipment e
        LEFT JOIN equipment_units u ON u.equipment_id = e.id AND u.status != 'retired'
        LEFT JOIN equipment_reservations res ON res.unit_id = u.id
            AND res.status = 'active'
            AND res.reserved_from <= datetime('now')
            AND res.reserved_to >= datetime('now')
        WHERE " . implode(' AND ', $where_clauses) . "
        GROUP BY e.id
        ORDER BY e.category ASC, e.name ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $all_equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter by overall status
    $filtered_equipment = [];
    $total_available = 0;
    $total_non_retired = 0;

    foreach ($all_equipment as &$item) {
        $item['quantity'] = (int)$item['quantity'];
        $item['available_qty'] = (int)$item['available_qty'];
        $item['in_use_qty'] = (int)$item['in_use_qty'];
        $item['maintenance_qty'] = (int)$item['maintenance_qty'];

        $total_non_retired += $item['quantity'];
        $total_available += $item['available_qty'];

        if ($item['available_qty'] > 0) {
            $item['overall_status'] = 'available';
        } elseif ($item['in_use_qty'] > 0) {
            $item['overall_status'] = 'in_use';
        } elseif ($item['maintenance_qty'] > 0) {
            $item['overall_status'] = 'maintenance';
        } else {
            $item['overall_status'] = 'maintenance'; // default fallback
        }

        if ($filter_status !== '' && $item['overall_status'] !== $filter_status) {
            continue; // Skip this item
        }

        $filtered_equipment[] = $item;
    }

    echo json_encode([
        'equipment' => $filtered_equipment,
        'summary' => [
            'total_available' => $total_available,
            'total_non_retired' => $total_non_retired
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
