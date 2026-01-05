<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
// You can keep your auth check if needed, I've removed it for clarity.
// require_once dirname(__DIR__, 2) . '/core/auth.php';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id === 0) {
    http_response_code(400);
    echo json_encode(['message' => 'Event ID is required.']);
    exit;
}

$db = (new Database())->connect();

// MODIFIED QUERY: Added a LEFT JOIN to get the membership type name
$query = "
    SELECT 
        ett.*,
        mt.name AS member_type_name, -- ADDED: Fetch the name from the membership_types table
        (SELECT COUNT(*) FROM event_tickets et WHERE et.ticket_type_id = ett.id AND et.status = 'bought') as tickets_sold
    FROM 
        event_ticket_types ett
    LEFT JOIN 
        membership_types mt ON ett.member_type_id = mt.id -- ADDED: The join condition
    WHERE 
        ett.event_id = :event_id
    ORDER BY 
        ett.name ASC
";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $event_id);
$stmt->execute();
$ticket_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($ticket_types);