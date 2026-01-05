<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->event_id) || !isset($data->name) || !isset($data->price)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

$db = (new Database())->connect();
try {
    $query = "INSERT INTO event_ticket_types (event_id, name, price, member_type_id) 
              VALUES (:event_id, :name, :price, :member_type_id)";
    $stmt = $db->prepare($query);

    // Handle optional member_type_id. If it's empty or '0', store NULL.
    $member_type_id = (!empty($data->member_type_id) && $data->member_type_id != '0') ? $data->member_type_id : null;

    $stmt->bindParam(':event_id', $data->event_id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':price', $data->price);
    
    // **FIXED LINE: Explicitly bind the parameter type**
    $stmt->bindParam(':member_type_id', $member_type_id, $member_type_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Ticket type created successfully.']);
    } else {
        throw new Exception('Database execution failed.');
    }
} catch (Exception $e) {
    http_response_code(500);
    // It's good practice not to expose raw database errors in production
    error_log($e->getMessage()); 
    echo json_encode(['success' => false, 'message' => 'An error occurred while creating the ticket type.']);
}