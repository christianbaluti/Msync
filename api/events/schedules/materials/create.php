<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 3) . '/core/initialize.php';
require_once dirname(__DIR__, 3) . '/core/auth.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->schedule_id) || empty($data->title) || empty($data->type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

$db = (new Database())->connect();
$query = "INSERT INTO training_materials (schedule_id, title, description, type, url) 
          VALUES (:schedule_id, :title, :description, :type, :url)";
$stmt = $db->prepare($query);

// Use bindValue for safety, especially with potentially null values
$stmt->bindValue(':schedule_id', $data->schedule_id);
$stmt->bindValue(':title', $data->title);
$stmt->bindValue(':description', $data->description ?? null);
$stmt->bindValue(':type', $data->type);
$stmt->bindValue(':url', $data->url ?? null);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Training material added successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to add material.']);
}