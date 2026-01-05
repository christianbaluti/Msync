<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->design_name) || !isset($data->front_json)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

try {
    $query = "INSERT INTO card_designs (design_name, front_json, back_json) VALUES (:name, :front, :back)";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':name', $data->design_name);
    $stmt->bindParam(':front', $data->front_json);
    $stmt->bindParam(':back', $data->back_json);

    if ($stmt->execute()) {
        $lastId = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Design saved successfully.', 'id' => $lastId]);
    } else {
        throw new Exception("Database error: Could not save design.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}