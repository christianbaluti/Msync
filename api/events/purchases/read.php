<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if ($event_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit();
}

$db = (new Database())->connect();

// Add pagination and filtering as needed (similar to Users module)
$query = "
    SELECT 
        et.id,
        et.status,
        et.price,
        et.balance_due,
        et.ticket_code,
        et.purchased_at,
        u.full_name as user_name,
        u.email as user_email,
        c.name as company_name,
        ett.name as ticket_type_name,
        aat.name as attendee_type_name
    FROM event_tickets et
    LEFT JOIN users u ON et.user_id = u.id
    LEFT JOIN companies c ON et.company_id = c.id
    LEFT JOIN event_ticket_types ett ON et.ticket_type_id = ett.id
    LEFT JOIN attending_as_types aat ON et.attending_as_id = aat.id
    WHERE et.event_id = :event_id
    ORDER BY et.purchased_at DESC
";

$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $event_id);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));