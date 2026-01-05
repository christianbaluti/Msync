<?php
// events/get_ticket_types.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// === UNIVERSAL ERROR LOGGING ===
$logPath = __DIR__ . '/../../../../public/error.log';
if (!file_exists(dirname($logPath))) {
    mkdir(dirname($logPath), 0777, true);
}

// --- Simple log helper ---
function log_error_file($message) {
    global $logPath;
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] get_ticket_types.php: $message\n";
    @error_log($line, 3, $logPath);
}

// --- Log all PHP errors ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $logPath);

// --- Custom error handler ---
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    log_error_file("PHP Error [$errno] $errstr in $errfile on line $errline");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
    exit;
});

// --- Custom exception handler ---
set_exception_handler(function ($e) {
    log_error_file("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
    exit;
});

// --- Fatal error shutdown handler ---
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        log_error_file("Fatal Error: {$error['message']} in {$error['file']} line {$error['line']}");
    }
});
// === END UNIVERSAL ERROR LOGGING ===


// === ACTUAL SCRIPT LOGIC ===
require __DIR__ . '/../../vendor/autoload.php'; 
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    log_error_file("Invalid or empty JSON body");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$auth_data = get_auth_user($input); 
$userId = $auth_data ? $auth_data->user_id : null; // Extract the user_id
$memberTypeId = null;

$eventId = isset($input['event_id']) ? intval($input['event_id']) : 0;
if ($eventId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid event ID.']);
    exit;
}

try {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT membership_type_id FROM membership_subscriptions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($subscription) {
            $memberTypeId = $subscription['membership_type_id'];
        }
    }

    $sql = "
        SELECT 
            ett.id, 
            ett.name, 
            ett.price, 
            ett.member_type_id
        FROM event_ticket_types ett
        WHERE ett.event_id = :eventId 
        AND (ett.member_type_id IS NULL";

    if ($memberTypeId) {
        $sql .= " OR ett.member_type_id = :memberTypeId";
    }
    $sql .= ")";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':eventId', $eventId, PDO::PARAM_INT);
    if ($memberTypeId) {
        $stmt->bindValue(':memberTypeId', $memberTypeId, PDO::PARAM_INT);
    }

    $stmt->execute();
    $ticketTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ticketTypes);

} catch (Exception $e) {
    log_error_file('Unhandled exception during DB query: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred.']);
}
?>
