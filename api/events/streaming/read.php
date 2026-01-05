<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php'; // Adjust path as needed
require_once dirname(__DIR__, 2) . '/core/auth.php'; // Adjust path as needed

if (empty($_GET['event_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit();
}

$event_id = $_GET['event_id'];
$db = (new Database())->connect();

try {
    $query = "SELECT * FROM event_youtube_streams WHERE event_id = :event_id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $stream_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // fetch() returns false if no row is found, which json_encodes as 'false'.
    // Sending an empty object {} is often cleaner for JS.
    if ($stream_data === false) {
        echo json_encode((object)[]);
    } else {
        echo json_encode($stream_data);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching stream data.']);
}