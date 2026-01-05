<?php
// api/schedules/meal_card_status.php

// --- Error Reporting (for development) ---
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// --- Headers ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }


// ==================================================
// 🛡 GLOBAL ERROR AND EXCEPTION HANDLING
// ==================================================

function json_error_response($message, $code = 500, $details = []) {
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json');
    }

    echo json_encode([
        'success' => false,
        'message' => $message,
        'error_details' => $details
    ]);
    exit;
}

// --- Handle runtime PHP errors (warnings, notices, etc.) ---
set_error_handler(function($severity, $message, $file, $line) {
    // Convert PHP errors to ErrorException so they can be caught in try-catch
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// --- Handle uncaught exceptions ---
set_exception_handler(function($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " line " . $exception->getLine());
    json_error_response(
        'A server-side error occurred. Please try again later.',
        500,
        [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine(),
        ]
    );
});

// --- Handle fatal errors (shutdown function) ---
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal Error: {$error['message']} in {$error['file']} line {$error['line']}");
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'A critical server error occurred.',
            'error_details' => [
                'type' => 'FatalError',
                'file' => basename($error['file']),
                'line' => $error['line'],
                'message' => $error['message']
            ]
        ]);
        exit;
    }
});


// ==================================================
// 🧩 MAIN LOGIC
// ==================================================
try {
    // --- Method Check ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'POST method required.',
            'error_details' => ['type' => 'RequestMethodError']
        ]);
        exit;
    }

    // --- Includes ---
    
    require_once __DIR__ . '/../db_connection.php';
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../auth_middleware_for_all.php';

    // --- Input ---
    $raw_post_data = file_get_contents('php://input');
    if ($raw_post_data === false) {
        throw new Exception("Failed to read input stream.");
    }

    $request_data = json_decode($raw_post_data);
    if (json_last_error() !== JSON_ERROR_NONE || !is_object($request_data)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input in request body.',
            'error_details' => ['type' => 'JSONDecodeError', 'json_error' => json_last_error_msg()]
        ]);
        exit;
    }

    // --- Auth ---
    $auth_data = get_auth_user($request_data);
    if (!$auth_data || !isset($auth_data->user_id)) {
        throw new Exception("Authentication failed or user_id not found in token data.");
    }
    $user_id = $auth_data->user_id;

    // --- Input Validation ---
    $schedule_id = $request_data->id ?? null;
    if (!$schedule_id || !is_numeric($schedule_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Numeric Schedule ID (id) is required in the request body.',
            'error_details' => ['type' => 'InputValidationError', 'field' => 'id']
        ]);
        exit;
    }
    $schedule_id = (int)$schedule_id;

    // --- DB Operations ---
    $stmt_schedule_ticket = $pdo->prepare("
        SELECT es.event_id, es.title, et.ticket_code, et.id as ticket_id
        FROM event_schedules es
        JOIN event_tickets et ON es.event_id = et.event_id
        WHERE es.id = :schedule_id AND es.type = 'meal' AND et.user_id = :user_id AND et.status = 'bought'
        LIMIT 1
    ");
    $stmt_schedule_ticket->execute(['schedule_id' => $schedule_id, 'user_id' => $user_id]);
    $schedule_and_ticket = $stmt_schedule_ticket->fetch(PDO::FETCH_ASSOC);

    if (!$schedule_and_ticket) {
        $stmt_check_schedule = $pdo->prepare("SELECT 1 FROM event_schedules WHERE id = :schedule_id AND type = 'meal'");
        $stmt_check_schedule->execute(['schedule_id' => $schedule_id]);
        if (!$stmt_check_schedule->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Meal schedule not found or is not a meal type.']);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have a valid, bought ticket for this event.']);
        }
        exit;
    }

    $pdo->beginTransaction();

    $stmt_card = $pdo->prepare("
        SELECT id, status FROM meal_cards
        WHERE ticket_id = :ticket_id AND schedule_id = :schedule_id FOR UPDATE
    ");
    $stmt_card->execute(['ticket_id' => $schedule_and_ticket['ticket_id'], 'schedule_id' => $schedule_id]);
    $meal_card = $stmt_card->fetch(PDO::FETCH_ASSOC);

    if (!$meal_card) {
        $stmt_create = $pdo->prepare("INSERT INTO meal_cards (ticket_id, schedule_id, status) VALUES (:ticket_id, :schedule_id, 'inactive')");
        if (!$stmt_create->execute(['ticket_id' => $schedule_and_ticket['ticket_id'], 'schedule_id' => $schedule_id])) {
            throw new Exception("Failed to create meal card entry.");
        }
        $meal_card = ['id' => $pdo->lastInsertId(), 'status' => 'inactive'];
    }

    $pdo->commit();

    echo json_encode([
        'success'     => true,
        'id'          => (int)$meal_card['id'],
        'status'      => $meal_card['status'],
        'title'       => $schedule_and_ticket['title'],
        'ticket_code' => $schedule_and_ticket['ticket_code']
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Database Error: " . $e->getMessage());
    json_error_response('Database error occurred.', 500, [
        'type' => 'PDOException',
        'code' => $e->getCode(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'pdo_message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("General Error: " . $e->getMessage());
    json_error_response('An unexpected error occurred.', 500, [
        'type' => get_class($e),
        'code' => $e->getCode(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'exception_message' => $e->getMessage()
    ]);
}
?>
