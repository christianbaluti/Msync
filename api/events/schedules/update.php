<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

// 1. Permission Check
if (!has_permission('events_update')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to update events.']);
    exit();
}

// 2. Get Input Data
$data = json_decode(file_get_contents("php://input"));

// 3. Validation
if (empty($data->id) || empty($data->title) || empty($data->start_datetime) || empty($data->end_datetime)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID, title, start time, and end time are required.']);
    exit();
}

// 4. Database Operation
$db = (new Database())->connect();
try {
    $query = "UPDATE events 
              SET title = :title, 
                  description = :description, 
                  start_datetime = :start_datetime, 
                  end_datetime = :end_datetime, 
                  location = :location, 
                  status = :status
              WHERE id = :id";
              
    $stmt = $db->prepare($query);

    // Bind data
    $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $data->title);
    $stmt->bindParam(':description', $data->description);
    $stmt->bindParam(':start_datetime', $data->start_datetime);
    $stmt->bindParam(':end_datetime', $data->end_datetime);
    $stmt->bindParam(':location', $data->location);
    $stmt->bindParam(':status', $data->status);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event updated successfully.']);
    } else {
        throw new Exception('Failed to execute update statement.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}