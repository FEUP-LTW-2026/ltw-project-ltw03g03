<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = get_db();
    
    // Get all active trainers and their profiles
    $stmt = $db->query("
        SELECT 
            u.id as trainer_id,
            u.first_name,
            u.last_name,
            tp.bio,
            tp.specialty,
            tp.certifications,
            tp.years_experience
        FROM users u
        JOIN trainer_profiles tp ON tp.user_id = u.id
        WHERE u.role = 'trainer' AND u.is_active = 1
        ORDER BY u.first_name ASC
    ");
    
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $trainers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An internal server error occurred.'
    ]);
}
