<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['user']['id']) && !isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $db = get_db();
    
    // Get all scheduled sessions in the future, including class info and trainer
    $stmt = $db->query("
        SELECT 
            cs.id as session_id,
            cs.scheduled_at,
            c.id as class_id,
            c.name as class_name,
            c.type,
            c.level,
            c.duration_min,
            c.capacity,
            u.id as trainer_id,
            u.first_name as trainer_first_name,
            u.last_name as trainer_last_name
        FROM class_sessions cs
        JOIN classes c ON c.id = cs.class_id
        LEFT JOIN users u ON u.id = c.trainer_id
        WHERE cs.status = 'scheduled'
          AND cs.scheduled_at > datetime('now')
          AND c.is_active = 1
        ORDER BY cs.scheduled_at ASC
        LIMIT 100
    ");
    
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $sessions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An internal server error occurred.'
    ]);
}
