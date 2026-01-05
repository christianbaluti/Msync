<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$data = json_decode(file_get_contents("php://input"));

$action = $data->action ?? null;
$ticket_ids = $data->ticket_ids ?? [];

if (empty($action) || empty($ticket_ids) || !is_array($ticket_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit();
}

$db = (new Database())->connect();
$count = 0;
$message = '';

try {
    $db->beginTransaction();

    if ($action === 'activate') {
        $query = "INSERT INTO meal_cards (ticket_id, status) VALUES (:ticket_id, 'about_to_collect') 
                  ON DUPLICATE KEY UPDATE status = IF(status = 'inactive', 'about_to_collect', status)";
        $stmt = $db->prepare($query);
        foreach ($ticket_ids as $tid) {
            $stmt->execute([':ticket_id' => $tid]);
            $count += $stmt->rowCount() > 0 ? 1 : 0;
        }
        $message = "$count card(s) activated successfully.";
    
    } elseif ($action === 'collect') {
        $placeholders = implode(',', array_fill(0, count($ticket_ids), '?'));
        $query = "UPDATE meal_cards SET status = 'collected' WHERE ticket_id IN ($placeholders) AND status = 'about_to_collect'";
        $stmt = $db->prepare($query);
        $stmt->execute($ticket_ids);
        $count = $stmt->rowCount();
        $message = "$count card(s) marked as collected.";

    } else {
        throw new Exception("Invalid action specified.");
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
}