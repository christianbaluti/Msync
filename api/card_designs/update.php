<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Design ID is required for update.']);
    exit();
}

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->design_name) || !isset($data->front_json)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

try {
    $query = "UPDATE card_designs SET design_name = :name, front_json = :front, back_json = :back WHERE id = :id";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':name', $data->design_name);
    $stmt->bindParam(':front', $data->front_json);
    $stmt->bindParam(':back', $data->back_json);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Design updated successfully.']);
    } else {
        throw new Exception("Database error: Could not update design.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}