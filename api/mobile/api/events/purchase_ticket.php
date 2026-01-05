<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

function log_error_file($message) {
    $logPath = __DIR__ . '/../../../../public/error.log';
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] purchase_ticket.php: $message
";
    @error_log($line, 3, $logPath);
}

try {
    $auth_data = get_auth_user();
    $user_id = $auth_data->user_id;

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        log_error_file('Invalid JSON body');
        http_response_code(400);
        echo json_encode(['message' => 'Invalid JSON body']);
        exit;
    }

    $ticketTypeId = $input['ticket_type_id'] ?? null;
    $eventId = $input['event_id'] ?? null;

    if (!$ticketTypeId || !$eventId) {
        log_error_file('Missing fields ticket_type_id/event_id');
        http_response_code(422);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }

    require_once __DIR__ . '/../models/EventTicket.php';
    $eventTicket = new EventTicket($pdo);
    
    $result = $eventTicket->purchase($ticketTypeId, $eventId, $user_id);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Ticket purchased successfully', 'ticket_id' => $result]);
    } else {
        log_error_file('Failed to purchase ticket for user ' . $user_id);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to purchase ticket']);
    }

} catch (Exception $e) {
    log_error_file('Unhandled exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>