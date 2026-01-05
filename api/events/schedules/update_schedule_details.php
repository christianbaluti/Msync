<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';

$data = json_decode(file_get_contents("php://input"));

// Basic validation
if (empty($data->id) || empty($data->title) || empty($data->start_datetime) || empty($data->end_datetime)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();

    $query = "UPDATE event_schedules SET 
                title = :title, 
                start_datetime = :start_datetime, 
                end_datetime = :end_datetime, 
                status = :status, 
                description = :description 
              WHERE id = :id";
              
    $stmt = $db->prepare($query);
    
    $stmt->bindValue(':id', $data->id);
    $stmt->bindValue(':title', $data->title);
    $stmt->bindValue(':start_datetime', $data->start_datetime);
    $stmt->bindValue(':end_datetime', $data->end_datetime);
    $stmt->bindValue(':status', $data->status ?? 'inactive');
    $stmt->bindValue(':description', $data->description ?? null);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule details updated successfully.']);
    } else {
        throw new Exception('Database execution failed.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating schedule: ' . $e->getMessage()]);
}