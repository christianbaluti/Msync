<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Attendee Type ID is required.']);
    exit();
}

$db = (new Database())->connect();
try {
    // Check if any tickets reference this attendee type
    $check_query = "SELECT COUNT(*) FROM event_tickets WHERE attending_as_id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $data->id, PDO::PARAM_INT);
    $check_stmt->execute();

    if ($check_stmt->fetchColumn() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Cannot delete this type because it is currently assigned to one or more tickets.']);
        exit();
    }

    // Proceed with deletion if no tickets are using it
    $query = "DELETE FROM attending_as_types WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendee type deleted successfully.']);
    } else {
        throw new Exception('Database execution failed.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}