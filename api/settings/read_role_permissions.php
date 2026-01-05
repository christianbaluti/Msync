<?php
// /api/settings/read_role_permissions.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$role_id = $_GET['role_id'] ?? null;

if (!$role_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Role ID is required.']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $permissions]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>