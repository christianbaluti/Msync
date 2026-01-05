<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
$data = json_decode(file_get_contents("php://input"), true);
if (empty($data['seat_id']) || empty($data['name'])) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'Missing required fields.']); exit;
}
$db = (new Database())->connect();
$stmt = $db->prepare("INSERT INTO election_candidates (seat_id, name, description) VALUES (?, ?, ?)");
if ($stmt->execute([$data['seat_id'], $data['name'], $data['description']])) {
    echo json_encode(['success' => true, 'message' => 'Candidate created.']);
} else {
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'Failed to create candidate.']);
}