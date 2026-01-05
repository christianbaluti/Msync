<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

// Find the most recent event
$event_query = "SELECT id, title, description, start_datetime, location FROM events ORDER BY created_at DESC LIMIT 1";
$event_stmt = $db->query($event_query);
$event = $event_stmt->fetch(PDO::FETCH_ASSOC);

if ($event) {
    // Find its schedule
    $schedule_query = "SELECT id, title, type, start_datetime, end_datetime FROM event_schedules WHERE event_id = ? ORDER BY start_datetime ASC";
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->execute([$event['id']]);
    $schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

    $event['schedules'] = $schedules;

    echo json_encode(['success' => true, 'data' => $event]);
} else {
    echo json_encode(['success' => false, 'message' => 'No events found.']);
}