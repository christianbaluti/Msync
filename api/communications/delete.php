<?php
// /api/communications/delete.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents('php://input'));
if (empty($data->id)) { exit; }

$db = (new Database())->connect();

// Assuming ON DELETE CASCADE is set for the foreign key in communication_logs
$stmt = $db->prepare("DELETE FROM communications WHERE id = ?");

if ($stmt->execute([$data->id])) {
    echo json_encode(['success' => true, 'message' => 'Communication deleted.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete communication.']);
}