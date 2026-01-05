<?php
// /api/settings/update_app_update.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Update ID is required.']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE update_release SET platform = ?, version_name = ?, version_code = ?, release_notes = ?, is_force_update = ? WHERE id = ?");
    $stmt->execute([
        $data['platform'],
        $data['version_name'],
        $data['version_code'],
        $data['release_notes'] ?? null,
        $data['is_force_update'],
        $id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'App version updated.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>