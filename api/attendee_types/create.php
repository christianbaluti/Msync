<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Attendee type name is required.']);
    exit();
}

$db = (new Database())->connect();
$query = "INSERT INTO attending_as_types (name, description) VALUES (:name, :description)";
$stmt = $db->prepare($query);

$stmt->bindParam(':name', $data->name);
$stmt->bindParam(':description', $data->description);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Attendee Type created successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create attendee type.']);
}