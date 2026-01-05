<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

// 1. Permission Check
if (!has_permission('events_delete')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to delete events.']);
    exit();
}

// 2. Get Input Data
$data = json_decode(file_get_contents("php://input"));

// 3. Validation
if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit();
}

// 4. Database Operation
$db = (new Database())->connect();
try {
    // Note: ON DELETE CASCADE constraints in your DB would also remove related schedules, tickets, etc.
    // If you don't have those constraints, you would need to delete them manually here.
    $query = "DELETE FROM events WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Event not found or already deleted.']);
        }
    } else {
        throw new Exception('Failed to execute delete statement.');
    }
} catch (Exception $e) {
    http_response_code(500);
    // This could fail if there are related records and no ON DELETE constraint.
    echo json_encode(['success' => false, 'message' => 'Server Error. This event might have related data (like tickets or schedules) that prevents its deletion.']);
}