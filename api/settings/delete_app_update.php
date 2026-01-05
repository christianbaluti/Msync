<?php
// /api/settings/delete_app_update.php
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
    $stmt = $db->prepare("DELETE FROM update_release WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'App version deleted.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>