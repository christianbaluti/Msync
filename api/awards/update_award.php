<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

$award_id = $data->id ?? null;
$title = $data->title ?? null;
$for_entity = $data->for_entity ?? null;
$description = $data->description ?? '';

if (!$award_id || !$title || !in_array($for_entity, ['user', 'company'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: id, title, or for_entity.']);
    exit;
}

try {
    $db = (new Database())->connect();
    $stmt = $db->prepare("
        UPDATE awards SET title = :title, description = :description, for_entity = :for_entity 
        WHERE id = :id
    ");
    
    $stmt->bindParam(':id', $award_id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':for_entity', $for_entity);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Award updated successfully.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Award not found or no changes made.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update award.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}