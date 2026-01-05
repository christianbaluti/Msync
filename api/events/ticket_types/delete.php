<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';


$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ticket Type ID is required.']);
    exit();
}

$db = (new Database())->connect();
try {
    // Check if tickets have been sold for this type. If so, prevent deletion.
    $check_query = "SELECT COUNT(*) FROM event_tickets WHERE ticket_type_id = :id AND status = 'bought'";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $data->id);
    $check_stmt->execute();
    if ($check_stmt->fetchColumn() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Cannot delete type with sold tickets.']);
        exit();
    }

    $query = "DELETE FROM event_ticket_types WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Ticket type deleted.']);
    } else {
        throw new Exception('Database execution failed.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}