<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// This script can update the status for any schedule type (voting, awards, etc.)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

$schedule_id = $data->schedule_id ?? null;
$status = $data->status ?? null;

if (!$schedule_id || !in_array($status, ['active', 'inactive'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID or status provided.']);
    exit;
}

try {
    $db = (new Database())->connect();
    $stmt = $db->prepare("UPDATE event_schedules SET status = :status WHERE id = :schedule_id");
    
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule status updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update schedule status.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}