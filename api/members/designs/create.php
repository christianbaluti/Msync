<?php
// /api/members/designs/create.php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$data = json_decode(file_get_contents('php://input'));
if (empty($data->design_name)) {
    echo json_encode(['success' => false, 'message' => 'Design Name is required.']);
    exit;
}

$db = (new Database())->connect();
$query = "INSERT INTO card_designs (design_name, front_json, back_json) VALUES (:name, :front, :back)";
$stmt = $db->prepare($query);

$stmt->bindValue(':name', $data->design_name);
$stmt->bindValue(':front', $data->front_json);
$stmt->bindValue(':back', $data->back_json);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'message' => 'Design saved successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save design.']);
}