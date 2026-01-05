<?php
require_once dirname(__DIR__, 2) . '/core/initialize.php';

header('Content-Type: application/json');
$event_id = $_GET['event_id'] ?? 0;

if (empty($event_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit;
}

try {
    // --- CORRECTED DATABASE USAGE ---
    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("SELECT id, name, description, total_quantity FROM merchandise WHERE event_id = :event_id ORDER BY name ASC");
    $stmt->bindParam(':event_id', $event_id);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // --- END CORRECTION ---

    echo json_encode(['success' => true, 'data' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching merchandise: ' . $e->getMessage()]);
}