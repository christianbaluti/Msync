<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

$db = (new Database())->connect();

// The query uses a LEFT JOIN and a subquery to count attendees for the specific event
$query = "
    SELECT 
        aat.*,
        (SELECT COUNT(*) 
         FROM event_tickets et 
         WHERE et.attending_as_id = aat.id AND et.event_id = :event_id) as attendee_count
    FROM 
        attending_as_types aat
    ORDER BY 
        aat.name ASC
";

$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));