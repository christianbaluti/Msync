<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Design ID is required.']);
    exit();
}

try {
    $db = (new Database())->connect();
    $query = "SELECT * FROM card_designs WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $design = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($design) {
        echo json_encode(['success' => true, 'data' => $design]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Design not found.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}