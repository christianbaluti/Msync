<?php
require_once dirname(__DIR__, 2) . '/core/initialize.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

try {
    // --- CORRECTED DATABASE USAGE ---
    $database = new Database();
    $db = $database->connect();
    
    $stmt = $db->prepare("DELETE FROM merchandise WHERE id = :id");
    $stmt->bindParam(':id', $input['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Merchandise deleted.']);
    } else {
        throw new Exception('Database execution failed.');
    }
    // --- END CORRECTION ---
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting merchandise: ' . $e->getMessage()]);
}