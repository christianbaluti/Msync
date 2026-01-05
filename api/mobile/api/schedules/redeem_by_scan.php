<?php
// api/schedules/redeem_by_scan.php

// --- Headers ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// --- Error Reporting (Enable during development) ---
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log'); // Optional: specify log file

// --- Main Logic ---
try {
    // Check HTTP Method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST method required.']);
        exit;
    }

    // --- Includes ---
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../db_connection.php'; // Ensure $pdo is defined
    require_once __DIR__ . '/../auth_middleware_for_all.php'; // Ensure get_auth_user is defined

    // --- Input Processing ---
    $raw_post_data = file_get_contents("php://input");
    if ($raw_post_data === false) {
        throw new Exception("Failed to read input stream.");
    }
    $request_data = json_decode($raw_post_data);

    if (json_last_error() !== JSON_ERROR_NONE || !is_object($request_data)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input.',
            'error_details' => ['type' => 'JSONDecodeError', 'json_error' => json_last_error_msg()]
        ]);
        exit;
    }

    // --- Authentication ---
    $auth_data = get_auth_user($request_data); // Auth middleware handles its errors/exit
    $user_id = $auth_data->user_id;

    // --- Input Validation ---
    $event_id = $request_data->event_id ?? null;
    $schedule_id = $request_data->schedule_id ?? null; // ID of the specific meal schedule from QR

    if (!$event_id || !is_numeric($event_id) || !$schedule_id || !is_numeric($schedule_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Numeric Event ID and Schedule ID from QR code are required.',
            'error_details' => ['type' => 'InputValidationError', 'fields' => ['event_id', 'schedule_id']]
            ]);
        exit;
    }
    $event_id = (int)$event_id;
    $schedule_id = (int)$schedule_id;


    // --- Database Operations ---

    // 1. Find the user's valid ticket for this event.
    $stmt_ticket = $pdo->prepare("SELECT id FROM event_tickets WHERE event_id = :event_id AND user_id = :user_id AND status = 'bought' LIMIT 1");
    $stmt_ticket->execute(['event_id' => $event_id, 'user_id' => $user_id]);
    $ticket = $stmt_ticket->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Redemption failed: You do not have a valid ticket for this event.']);
        exit;
    }
    $ticket_id = $ticket['id'];

    // 2. Find the specific meal card for this ticket AND schedule, and check its status.
    // ✅ CHANGED: Added schedule_id to the WHERE clause
    $stmt_card = $pdo->prepare("SELECT id, status FROM meal_cards WHERE ticket_id = :ticket_id AND schedule_id = :schedule_id LIMIT 1");
    $stmt_card->execute(['ticket_id' => $ticket_id, 'schedule_id' => $schedule_id]);
    $meal_card = $stmt_card->fetch(PDO::FETCH_ASSOC);

    if (!$meal_card) {
        // Card might not exist if meal_card_status wasn't called first, or schedule ID from QR is wrong
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Redemption failed: Meal card not found for your ticket and this specific schedule.']);
        exit;
    }

    if ($meal_card['status'] === 'collected') {
        http_response_code(409); // Conflict - Already redeemed
         echo json_encode(['success' => false, 'message' => 'This meal card has already been redeemed.']);
         exit;
    }

     // Allow 'inactive' or 'about_to_collect' potentially, depending on desired strictness.
     // Sticking to 'about_to_collect' as per original logic.
    if ($meal_card['status'] !== 'about_to_collect') {
        http_response_code(409); // Conflict - Not ready
        echo json_encode(['success' => false, 'message' => 'This meal card is not ready for collection yet. Status: ' . $meal_card['status']]);
        exit;
    }


    // 3. Update the status to 'collected'
    $stmt_update = $pdo->prepare("UPDATE meal_cards SET status = 'collected' WHERE id = :meal_card_id");
    $updated = $stmt_update->execute(['meal_card_id' => $meal_card['id']]);

    if ($updated) {
        echo json_encode(['success' => true, 'message' => 'Meal redeemed successfully! Enjoy.']);
    } else {
        // Throw exception if update fails
         throw new Exception("Failed to update meal card status in database.");
    }


// --- Catch Blocks ---
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database Error in redeem_by_scan.php: " . $e->getMessage() . " | SQL State: " . $e->getCode() . " | Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Database error during redemption.',
        'error_details' => [
            'type' => 'PDOException',
            'code' => $e->getCode(),
            'file' => basename($e->getFile()), // Show only filename
            'line' => $e->getLine(),
            'pdo_message' => $e->getMessage()
        ]
    ]);
    exit;
} catch (Exception $e) {
    $httpCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    http_response_code($httpCode);
    error_log("General Error in redeem_by_scan.php: " . $e->getMessage() . " | Code: " . $e->getCode() . " | Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during redemption: ' . $e->getMessage(),
        'error_details' => [
            'type' => get_class($e),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()), // Show only filename
            'line' => $e->getLine(),
            'exception_message' => $e->getMessage()
        ]
    ]);
    exit;
}
?>