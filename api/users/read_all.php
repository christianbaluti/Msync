<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

try {
    $database = new Database();
    $db = $database->connect();

    // Simple query to get all users, ordered by name for the dropdown
    $stmt = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name");
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $users]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not fetch users: ' . $e->getMessage()]);
}