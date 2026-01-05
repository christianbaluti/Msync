<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

// A user needs to be able to at least create or update users to see the roles list
if (!has_permission('users_create') && !has_permission('users_update')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT id, name FROM roles ORDER BY name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- FIX: Wrap your successful result in the expected format ---
    echo json_encode([
        'success' => true,
        'data'    => $roles
    ]);

} catch (PDOException $e) {
    // Add error handling for database failures
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>