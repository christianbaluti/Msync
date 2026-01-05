<?php
require_once dirname(__DIR__, 2) . '/core/initialize.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($input['event_id']) || empty($input['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

try {
    // --- CORRECTED DATABASE USAGE ---
    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("INSERT INTO merchandise (event_id, name, description, total_quantity) VALUES (:event_id, :name, :description, :total_quantity)");

    $stmt->bindParam(':event_id', $input['event_id']);
    $stmt->bindParam(':name', $input['name']);
    $stmt->bindValue(':description', $input['description'] ?? null);
    $stmt->bindValue(':total_quantity', !empty($input['total_quantity']) ? (int)$input['total_quantity'] : null);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Merchandise created.']);
    } else {
        throw new Exception('Database execution failed.');
    }
    // --- END CORRECTION ---
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error creating merchandise: ' . $e->getMessage()]);
}