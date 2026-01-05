<?php
// /api/settings/create_app_update.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"), true);

try {
    $stmt = $db->prepare("INSERT INTO update_release (platform, version_name, version_code, release_notes, is_force_update) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['platform'],
        $data['version_name'],
        $data['version_code'],
        $data['release_notes'] ?? null,
        $data['is_force_update']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'New app version created.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>