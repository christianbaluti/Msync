<?php
// /api/settings/update_gateway.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;
$config = $data['config'] ?? null;

if (!$id || !$config) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Gateway ID and config are required.']);
    exit;
}

// Basic JSON validation
if (json_decode($config) === null) {
     http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON config format.']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE payment_gateways SET config = ? WHERE id = ?");
    $stmt->execute([$config, $id]);
    
    echo json_encode(['success' => true, 'message' => 'Gateway updated.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>