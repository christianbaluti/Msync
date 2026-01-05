<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

$schedule_id = $data->schedule_id ?? null;
$title = $data->title ?? null;
$for_entity = $data->for_entity ?? null;
$description = $data->description ?? '';

if (!$schedule_id || !$title || !in_array($for_entity, ['user', 'company'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: schedule_id, title, or for_entity.']);
    exit;
}

try {
    $db = (new Database())->connect();
    $stmt = $db->prepare("
        INSERT INTO awards (schedule_id, title, description, for_entity) 
        VALUES (:schedule_id, :title, :description, :for_entity)
    ");
    
    $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':for_entity', $for_entity);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Award created successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create award.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}