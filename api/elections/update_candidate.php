<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
$data = json_decode(file_get_contents("php://input"), true);
if (empty($data['id']) || empty($data['name'])) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'Missing required fields.']); exit;
}
$db = (new Database())->connect();
$stmt = $db->prepare("UPDATE election_candidates SET name = ?, description = ? WHERE id = ?");
if ($stmt->execute([$data['name'], $data['description'], $data['id']])) {
    echo json_encode(['success' => true, 'message' => 'Candidate updated.']);
} else {
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'Failed to update candidate.']);
}