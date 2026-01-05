<?php
// File: /api/schedules/add_facilitator.php
header('Content-Type: application/json');
// UPDATED PATH:
require_once dirname(__DIR__) . '/core/database.php';

$db = (new Database())->connect();
$data = json_decode(file_get_contents('php://input'), true);
$schedule_id = $data['schedule_id'] ?? 0;
$user_id = $data['user_id'] ?? 0;

if (!$schedule_id || !$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Schedule ID and User ID are required.']);
    exit;
}

$stmt = $db->prepare("INSERT IGNORE INTO schedule_facilitators (schedule_id, user_id) VALUES (?, ?)");
$stmt->execute([$schedule_id, $user_id]);

echo json_encode(['success' => true, 'message' => 'Coordinator added.']);