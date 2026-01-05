<?php
// Set the content type to JSON for API responses
header('Content-Type: application/json');

// Include core files for initialization and authentication
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php'; 

// Decode the incoming JSON payload from the request body
$data = json_decode(file_get_contents("php://input"));

// --- Input Validation ---
// Ensure the essential fields for an update are present.
if (empty($data->id) || empty($data->title) || empty($data->type)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Missing required fields: id, title, type.']);
    exit();
}

// Establish a database connection
$db = (new Database())->connect();
// Begin a transaction to ensure all database operations succeed or fail together
$db->beginTransaction();

try {
    // --- 1. Update the main schedule record in `event_schedules` table ---
    $query = "UPDATE event_schedules SET 
                title = :title, 
                description = :description, 
                type = :type, 
                start_datetime = :start_datetime, 
                end_datetime = :end_datetime, 
                status = :status 
              WHERE id = :id";
              
    $stmt = $db->prepare($query);

    // Bind parameters from the input data to the prepared statement
    $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $data->title);
    $stmt->bindParam(':description', $data->description);
    $stmt->bindParam(':type', $data->type);
    $stmt->bindParam(':start_datetime', $data->start_datetime);
    $stmt->bindParam(':end_datetime', $data->end_datetime);
    $stmt->bindParam(':status', $data->status);
    
    $stmt->execute();

    // --- 2. Synchronize the facilitators ---
    // First, remove all existing facilitators for this schedule to handle deletions.
    $delete_fac_query = "DELETE FROM schedule_facilitators WHERE schedule_id = :schedule_id";
    $delete_fac_stmt = $db->prepare($delete_fac_query);
    $delete_fac_stmt->bindParam(':schedule_id', $data->id, PDO::PARAM_INT);
    $delete_fac_stmt->execute();

    // Second, if a new list of facilitators is provided, insert them.
    if (!empty($data->facilitators) && is_array($data->facilitators)) {
        $insert_fac_query = "INSERT INTO schedule_facilitators (schedule_id, user_id) VALUES (:schedule_id, :user_id)";
        $insert_fac_stmt = $db->prepare($insert_fac_query);
        
        foreach ($data->facilitators as $user_id) {
            $insert_fac_stmt->bindParam(':schedule_id', $data->id, PDO::PARAM_INT);
            $insert_fac_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_fac_stmt->execute();
        }
    }

    // If all queries were successful, commit the transaction
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Schedule item updated successfully.']);

} catch (Exception $e) {
    // If any error occurred, roll back the transaction to prevent partial updates
    $db->rollBack();
    http_response_code(500); // Internal Server Error
    error_log($e->getMessage()); // Log the actual error for debugging
    echo json_encode(['success' => false, 'message' => 'Server Error: Could not update the schedule item.']);
}