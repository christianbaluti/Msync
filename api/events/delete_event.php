<?php
// Set headers for JSON response and allowed methods
header('Access-Control-Allow-Origin: *'); // Adjust for production
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, DELETE'); // Allow POST or DELETE
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once dirname(__DIR__) . '/core/initialize.php';

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Get raw posted data (expecting JSON)
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (!$data || !isset($data->id) || !is_numeric($data->id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid Event ID provided.']);
    exit();
}

$event_id = intval($data->id);
$image_filename = null; // Variable to store image filename if found

try {
    $db->beginTransaction();

    // 1. Get the image filename BEFORE deleting the event record
    $query_select = "SELECT main_image FROM events WHERE id = :id";
    $stmt_select = $db->prepare($query_select);
    $stmt_select->bindParam(':id', $event_id);
    $stmt_select->execute();
    if ($row = $stmt_select->fetch(PDO::FETCH_ASSOC)) {
        $image_filename = $row['main_image'];
    }

    // 2. Delete the event record
    $query_delete = "DELETE FROM events WHERE id = :id";
    $stmt_delete = $db->prepare($query_delete);
    $stmt_delete->bindParam(':id', $event_id);

    if ($stmt_delete->execute()) {
        if ($stmt_delete->rowCount() > 0) {
            // 3. If deletion successful AND an image exists, delete the image file
            if ($image_filename) {
                $image_path = dirname(__DIR__, 2) . '/public/uploads/events/' . $image_filename;
                if (file_exists($image_path)) {
                    // Attempt to delete, ignore error if file not found or unwritable (DB record is gone anyway)
                    @unlink($image_path); 
                }
            }
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully.']);
        } else {
            // No rows affected - Event ID might not exist
            $db->rollBack();
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => 'Event not found.']);
        }
    } else {
        // Deletion failed at DB level
        throw new PDOException("Database execution failed.");
    }

} catch (PDOException $e) {
    $db->rollBack(); // Rollback transaction on error
    // Log error: error_log("Database error deleting event {$event_id}: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database error during deletion.']);
} catch (Exception $e) { // Catch other potential exceptions
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>