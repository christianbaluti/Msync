<?php
// File: /api/events/get_certification_attendees.php

header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/database.php';

$db = (new Database())->connect();
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

if (!$event_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit;
}

try {
    // Fetch all ticket holders for the event who have a paid/verified ticket
    $stmt = $db->prepare("
        SELECT
            u.id as user_id,
            u.full_name as user_name,
            u.email as user_email,
            c.name as company_name,
            et.ticket_code,
            ett.name as attendee_type
        FROM event_tickets et
        JOIN users u ON et.user_id = u.id
        LEFT JOIN companies c ON u.company_id = c.id
        LEFT JOIN event_ticket_types ett ON et.ticket_type_id = ett.id
        WHERE et.event_id = :event_id 
        AND et.status IN ('bought', 'verified')
        ORDER BY u.full_name
    ");
    $stmt->execute([':event_id' => $event_id]);
    $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'attendees' => $attendees]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
