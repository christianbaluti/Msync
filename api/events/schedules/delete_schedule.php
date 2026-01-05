<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php'; 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required.']);
    exit();
}

$db = (new Database())->connect();
$db->beginTransaction();

try {
    // Step 1: Delete related facilitator links first to avoid foreign key constraints.
    $fac_query = "DELETE FROM schedule_facilitators WHERE schedule_id = :schedule_id";
    $fac_stmt = $db->prepare($fac_query);
    $fac_stmt->bindParam(':schedule_id', $data->id, PDO::PARAM_INT);
    $fac_stmt->execute();

    // Step 2: Delete the main schedule item.
    $query = "DELETE FROM event_schedules WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);
    $stmt->execute();

    // Check if any row was actually deleted from the main table.
    if ($stmt->rowCount() > 0) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Schedule item deleted successfully.']);
    } else {
        // This case handles if the ID was invalid but didn't cause a fatal error.
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Schedule item not found.']);
    }

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    // Log the actual error for server-side debugging, but don't show it to the user.
    error_log($e->getMessage()); 
    echo json_encode(['success' => false, 'message' => 'Server Error: Could not delete the schedule item.']);
}