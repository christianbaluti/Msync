<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

if (!has_permission('users_read')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID not provided.']);
    exit();
}

$database = new Database();
$db = $database->connect();

// Get user's main details
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

// === FIX 1: Change the query to alias role_id as 'id' ===
$role_query = "SELECT role_id AS id FROM user_roles WHERE user_id = :id";
$role_stmt = $db->prepare($role_query);
$role_stmt->bindParam(':id', $id);
$role_stmt->execute();

// === FIX 2: Fetch as an associative array (to get [{ "id": 1 }, ...]) ===
$user['admin_roles'] = $role_stmt->fetchAll(PDO::FETCH_ASSOC);

// === FIX 3: Wrap the final response in the expected format ===
echo json_encode([
    'success' => true,
    'data' => $user
]);