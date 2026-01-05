<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
$data = json_decode(file_get_contents("php://input"), true);
if (empty($data['schedule_id']) || !isset($data['settings'])) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'Invalid input.']); exit;
}
$db = (new Database())->connect();
$stmt = $db->prepare("UPDATE event_schedules SET settings = ? WHERE id = ?");
if ($stmt->execute([json_encode($data['settings']), $data['schedule_id']])) {
    echo json_encode(['success' => true, 'message' => 'Settings saved.']);
} else {
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'Failed to save settings.']);
}