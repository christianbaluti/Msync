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

$database = new Database();
$db = $database->connect();

$query = "SELECT id, name FROM roles ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($roles);