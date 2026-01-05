<?php
require_once dirname(__DIR__, 2) . '/core/initialize.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($input['ticket_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

try {
    // --- CORRECTED DATABASE USAGE ---
    $database = new Database();
    $db = $database->connect();

    $sql = "INSERT INTO event_attendance (ticket_id, checked_in, checked_in_at) 
            VALUES (:ticket_id, 1, NOW()) 
            ON DUPLICATE KEY UPDATE checked_in = 1, checked_in_at = NOW()";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ticket_id', $input['ticket_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendee checked in.']);
    } else {
        throw new Exception('Database execution failed.');
    }
    // --- END CORRECTION ---
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Check-in failed: ' . $e->getMessage()]);
}