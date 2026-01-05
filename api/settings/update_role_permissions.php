<?php
// /api/settings/update_role_permissions.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"), true);
$role_id = $data['role_id'] ?? null;
$permissions = $data['permissions'] ?? [];

if (!$role_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Role ID is required.']);
    exit;
}

$db->beginTransaction();
try {
    // 1. Delete all existing permissions for this role
    $stmt_delete = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt_delete->execute([$role_id]);

    // 2. Insert the new permissions
    if (!empty($permissions)) {
        $stmt_insert = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($permissions as $perm_id) {
            $stmt_insert->execute([$role_id, $perm_id]);
        }
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Role permissions updated.']);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>