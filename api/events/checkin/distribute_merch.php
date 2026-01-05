<?php
require_once dirname(__DIR__, 2) . '/core/initialize.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($input['ticket_id']) || empty($input['merch_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

try {
    // --- CORRECTED DATABASE USAGE ---
    $database = new Database();
    $db = $database->connect();
    
    $sql = "INSERT IGNORE INTO merchandise_distribution (ticket_id, merch_id, distributed_at) VALUES (:ticket_id, :merch_id, NOW())";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ticket_id', $input['ticket_id']);
    $stmt->bindParam(':merch_id', $input['merch_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Merchandise distributed.']);
    } else {
        throw new Exception('Database execution failed.');
    }
    // --- END CORRECTION ---
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Distribution failed: ' . $e->getMessage()]);
}