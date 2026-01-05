<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit();
}

$db = (new Database())->connect();

// This query joins all the necessary tables to get the data for the placeholders.
$query = "SELECT 
            et.id as ticket_id,
            u.full_name,
            c.name as company_name,
            et.ticket_code,
            aat.name as attendee_type
          FROM 
            event_tickets et
          JOIN 
            users u ON et.user_id = u.id
          LEFT JOIN 
            companies c ON u.company_id = c.id
          LEFT JOIN 
            attending_as_types aat ON et.attending_as_id = aat.id
          WHERE 
            et.event_id = :event_id
          ORDER BY
            u.full_name ASC";

$stmt = $db->prepare($query);
$stmt->execute([':event_id' => $event_id]);
$attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'attendees' => $attendees]);