<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->ticket_ids) || !is_array($data->ticket_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'An array of ticket_ids is required.']);
    exit();
}

$db = (new Database())->connect();

// MODIFIED QUERY: The ON DUPLICATE KEY UPDATE now includes a condition.
// It will ONLY update the status if the current status is 'inactive'.
// This prevents re-activating an already 'about_to_collect' or 'collected' card.
$query = "INSERT INTO meal_cards (ticket_id, status, updated_at) 
          VALUES (:ticket_id, 'about_to_collect', NOW()) 
          ON DUPLICATE KEY UPDATE 
            status = IF(status = 'inactive', 'about_to_collect', status), 
            updated_at = NOW()";
            
$stmt = $db->prepare($query);

try {
    $db->beginTransaction();
    foreach ($data->ticket_ids as $ticket_id) {
        $stmt->execute([':ticket_id' => $ticket_id]);
    }
    $db->commit();
    echo json_encode(['success' => true, 'message' => count($data->ticket_ids) . ' meal card(s) processed for activation.']);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
}