<?php
// /api/settings/update_general.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No data provided.']);
    exit;
}

$db->beginTransaction();
try {
    $stmt = $db->prepare("UPDATE system_settings SET setting_value = :value WHERE setting_key = :key");
    
    foreach ($data as $key => $value) {
        $stmt->execute(['value' => $value, 'key' => $key]);
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Settings updated successfully.']);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>