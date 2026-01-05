<?php
// /api/members/designs/update.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents('php://input'));
if (empty($data->id) || empty($data->design_name)) {
    echo json_encode(['success' => false, 'message' => 'Design ID and Name are required.']);
    exit;
}

$db = (new Database())->connect();
$query = "UPDATE card_designs SET design_name = :name, front_json = :front, back_json = :back WHERE id = :id";
$stmt = $db->prepare($query);

$stmt->bindValue(':id', $data->id);
$stmt->bindValue(':name', $data->design_name);
$stmt->bindValue(':front', $data->front_json);
$stmt->bindValue(':back', $data->back_json);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Design updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update design.']);
}