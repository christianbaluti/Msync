<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->schedule_id) || !isset($data->facilitator_ids) || !is_array($data->facilitator_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input. schedule_id and facilitator_ids array are required.']);
    exit();
}

$db = (new Database())->connect();

try {
    // Start a transaction for this multi-step operation
    $db->beginTransaction();

    // 1. Delete all existing facilitators for this schedule
    $delete_stmt = $db->prepare("DELETE FROM schedule_facilitators WHERE schedule_id = :schedule_id");
    $delete_stmt->execute([':schedule_id' => $data->schedule_id]);

    // 2. Insert the new list of facilitators
    if (!empty($data->facilitator_ids)) {
        $insert_query = "INSERT INTO schedule_facilitators (schedule_id, user_id) VALUES (:schedule_id, :user_id)";
        $insert_stmt = $db->prepare($insert_query);
        
        foreach ($data->facilitator_ids as $user_id) {
            $insert_stmt->execute([
                ':schedule_id' => $data->schedule_id,
                ':user_id' => $user_id
            ]);
        }
    }

    // If everything was successful, commit the changes to the database
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Facilitators updated successfully.']);

} catch (Exception $e) {
    // If any step fails, roll back all changes
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating facilitators: ' . $e->getMessage()]);
}