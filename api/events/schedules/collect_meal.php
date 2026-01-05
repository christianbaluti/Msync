<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->ticket_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ticket ID is required.']);
    exit();
}

$db = (new Database())->connect();
// This query will create a record if it doesn't exist, or update it if it does.
$query = "INSERT INTO meal_cards (ticket_id, status, updated_at) 
          VALUES (:ticket_id, 'collected', NOW()) 
          ON DUPLICATE KEY UPDATE status = 'collected', updated_at = NOW()";
$stmt = $db->prepare($query);

try {
    if ($stmt->execute([':ticket_id' => $data->ticket_id])) {
        echo json_encode(['success' => true, 'message' => 'Meal marked as collected.']);
    } else {
        throw new Exception('Database execution failed.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
}