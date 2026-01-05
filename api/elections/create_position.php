<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
$data = json_decode(file_get_contents("php://input"), true);
if (empty($data['schedule_id']) || empty($data['name']) || empty($data['nominee_type'])) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'Missing required fields.']); exit;
}
$db = (new Database())->connect();
$stmt = $db->prepare("INSERT INTO election_seats (schedule_id, name, description, nominee_type) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$data['schedule_id'], $data['name'], $data['description'], $data['nominee_type']])) {
    echo json_encode(['success' => true, 'message' => 'Position created.']);
} else {
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'Failed to create position.']);
}