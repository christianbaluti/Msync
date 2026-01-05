<?php
// File: /api/events/get_attendees.php

header('Content-Type: application/json');
// UPDATED PATH:
require_once dirname(__DIR__) . '/core/database.php';

$db = (new Database())->connect();
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$schedule_id = filter_input(INPUT_GET, 'schedule_id', FILTER_VALIDATE_INT);

if (!$event_id || !$schedule_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID and Schedule ID are required.']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT
            u.full_name as user_name,
            u.email as user_email,
            u.is_employed,
            c.name as company_name,
            et.id as ticket_id,
            et.ticket_code,
            IFNULL(mc.status, 'inactive') as meal_card_status
        FROM event_tickets et
        JOIN users u ON et.user_id = u.id
        LEFT JOIN companies c ON u.company_id = c.id
        LEFT JOIN meal_cards mc ON et.id = mc.ticket_id AND mc.schedule_id = :schedule_id
        WHERE et.event_id = :event_id AND et.status IN ('bought', 'verified')
        ORDER BY u.full_name
    ");
    $stmt->execute([':schedule_id' => $schedule_id, ':event_id' => $event_id]);
    $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'attendees' => $attendees]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}