<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php'; // Adjust path
require_once dirname(__DIR__, 2) . '/core/auth.php'; // Adjust path

$data = json_decode(file_get_contents("php://input"));

// We can delete by ID or event_id. Using event_id is fine if we assume 1-to-1.
// Using the 'id' (primary key) is safer if it's provided.
if (empty($data->id) && empty($data->event_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Stream ID or Event ID is required.']);
    exit();
}

$db = (new Database())->connect();

try {
    if (!empty($data->id)) {
        $query = "DELETE FROM event_youtube_streams WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);
    } else {
        // Fallback to event_id if id is not passed
        $query = "DELETE FROM event_youtube_streams WHERE event_id = :event_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':event_id', $data->event_id, PDO::PARAM_INT);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Stream configuration removed successfully.']);
    } else {
        throw new Exception('Database execution failed.');
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while removing the configuration.']);
}