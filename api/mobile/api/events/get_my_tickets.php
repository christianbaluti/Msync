<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

function log_error_file($message) {
    $logPath = __DIR__ . '/../../../../public/error.log';
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] get_my_tickets.php: $message
";
    @error_log($line, 3, $logPath);
}

try {
    $auth_data = get_auth_user();
    $user_id = $auth_data->user_id;

    $query = "SELECT et.id, et.event_id, et.ticket_type_id, et.ticket_code, et.qr_code, et.price, et.purchased_at
               FROM event_tickets et
               WHERE et.user_id = :user_id
               ORDER BY et.purchased_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id);

    if ($stmt->execute()) {
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tickets);
    } else {
        log_error_file('Failed to fetch tickets for user ' . $user_id);
        http_response_code(500);
        echo json_encode(['message' => 'Failed to fetch tickets']);
    }

} catch (Exception $e) {
    log_error_file('Unhandled exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>