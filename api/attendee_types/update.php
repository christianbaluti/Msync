<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';


$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || !isset($data->name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

$db = (new Database())->connect();
try {
    $query = "UPDATE attending_as_types SET name = :name, description = :description WHERE id = :id";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':description', $data->description);
    $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendee type updated.']);
    } else {
        throw new Exception('Database execution failed.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}