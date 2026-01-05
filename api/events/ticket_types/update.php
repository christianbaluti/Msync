<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php'; // Assuming auth is handled

$data = json_decode(file_get_contents("php://input"));

// Added member_type_id to the check
if (empty($data->id) || !isset($data->name) || !isset($data->price) || !isset($data->member_type_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

$db = (new Database())->connect();
try {
    // Added member_type_id to the SET clause
    $query = "UPDATE event_ticket_types 
              SET name = :name, price = :price, member_type_id = :member_type_id 
              WHERE id = :id";
    $stmt = $db->prepare($query);

    // Handle optional member_type_id. If it's empty or '0', store NULL.
    $member_type_id = (!empty($data->member_type_id) && $data->member_type_id != '0') ? $data->member_type_id : null;

    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':price', $data->price);
    $stmt->bindParam(':member_type_id', $member_type_id, $member_type_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Ticket type updated successfully.']);
    } else {
        throw new Exception('Database execution failed.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}