<?php
// File: /api/schedules/update_meal_card_status.php

header('Content-Type: application/json');
// UPDATED PATH:
require_once dirname(__DIR__) . '/core/database.php';

$db = (new Database())->connect();
$data = json_decode(file_get_contents('php://input'), true);

$ticket_ids = $data['ids'] ?? [];
$status = $data['status'] ?? '';
$schedule_id = $data['schedule_id'] ?? 0;

if (empty($ticket_ids) || !in_array($status, ['about_to_collect', 'collected']) || !$schedule_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO meal_cards (ticket_id, schedule_id, status, updated_at)
        VALUES (:ticket_id, :schedule_id, :status, NOW())
        ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()
    ");

    foreach ($ticket_ids as $ticket_id) {
        $stmt->execute([
            ':ticket_id' => $ticket_id,
            ':schedule_id' => $schedule_id,
            ':status' => $status
        ]);
    }

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Meal card statuses updated.']);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}