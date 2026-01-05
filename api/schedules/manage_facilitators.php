<?php
/**
 * API endpoint to manage facilitators for an event schedule.
 *
 * Accepts a POST request with a JSON body containing:
 * {
 * "schedule_id": (int) The ID of the schedule to update.
 * "facilitator_ids": (array) An array of user IDs to set as facilitators.
 * }
 * An empty array for facilitator_ids will remove all facilitators.
 */

header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST is accepted.']);
    exit;
}

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'));

// Validate the input data
if (!isset($data->schedule_id) || !is_numeric($data->schedule_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'A valid schedule_id is required.']);
    exit;
}
if (!isset($data->facilitator_ids) || !is_array($data->facilitator_ids)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'facilitator_ids must be an array.']);
    exit;
}

$schedule_id = (int)$data->schedule_id;
// Sanitize the array to ensure all values are integers
$facilitator_ids = array_map('intval', $data->facilitator_ids);

$db = (new Database())->connect();

try {
    // Start a transaction to ensure atomicity
    $db->beginTransaction();

    // 1. Delete all existing facilitator records for this schedule
    $delete_stmt = $db->prepare("DELETE FROM schedule_facilitators WHERE schedule_id = ?");
    $delete_stmt->execute([$schedule_id]);

    // 2. If the new list of facilitators is not empty, insert them
    if (!empty($facilitator_ids)) {
        // Prepare the insert statement to be used in the loop
        $insert_sql = "INSERT INTO schedule_facilitators (schedule_id, user_id) VALUES (:schedule_id, :user_id)";
        $insert_stmt = $db->prepare($insert_sql);
        $insert_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);

        foreach ($facilitator_ids as $user_id) {
            // Bind the user_id and execute for each facilitator
            if ($user_id > 0) { // Basic validation
                 $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                 $insert_stmt->execute();
            }
        }
    }

    // 3. If everything was successful, commit the transaction
    $db->commit();

    // Send a success response
    echo json_encode([
        'success' => true,
        'message' => 'Facilitators have been updated successfully.'
    ]);

} catch (Exception $e) {
    // If any error occurred, roll back the transaction
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // Send an error response
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating facilitators: ' . $e->getMessage()
    ]);
}