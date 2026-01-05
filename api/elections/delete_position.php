<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
$data = json_decode(file_get_contents("php://input"), true);
if (empty($data['id'])) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit;
}
$db = (new Database())->connect();
$stmt = $db->prepare("DELETE FROM election_seats WHERE id = ?");
if ($stmt->execute([$data['id']])) {
    echo json_encode(['success' => true, 'message' => 'Position deleted.']);
} else {
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'Failed to delete position.']);
}