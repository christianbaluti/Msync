<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if ($event_id === 0) {
    http_response_code(400);
    echo json_encode([]); // Return empty array if no event ID
    exit();
}


$db = (new Database())->connect();

// We only need id, name, and price for the dropdown.
$query = "SELECT id, name, price FROM event_ticket_types WHERE event_id = :event_id ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $event_id);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));