<?php
// api/schedules/training_details.php

// --- Error Logging Setup ---
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../../public/error.log');
error_reporting(E_ALL);

// Custom error handler (for non-fatal PHP warnings, notices, etc.)
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $message = "[" . date('Y-m-d H:i:s') . "] PHP Error: $errstr in $errfile on line $errline" . PHP_EOL;
    error_log($message, 3, __DIR__ . '/../../../../public/error.log');
    return false; // Let PHP continue normal error handling
});

// Custom exception handler
set_exception_handler(function ($e) {
    $message = "[" . date('Y-m-d H:i:s') . "] Uncaught Exception: " . $e->getMessage() .
        " in " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL .
        "Trace: " . $e->getTraceAsString() . PHP_EOL;
    error_log($message, 3, __DIR__ . '/../../../../public/error.log');
    http_response_code(500);
    echo json_encode(['message' => 'Internal server error. Please check logs.']);
    exit;
});

// --- Headers ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ .  '/../../vendor/autoload.php';
require_once __DIR__ . '/../db_connection.php'; 
require_once __DIR__ .  '/../../vendor/autoload.php';
require_once __DIR__ . '/../auth_middleware_for_all.php';

// ✅ START: Read POST body ONCE
$data = json_decode(file_get_contents("php://input"));
if ($data === null) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid JSON body.']);
    exit;
}
// ✅ END: Read POST body

// ✅ CHANGED: Pass $data to auth function
get_auth_user($data);

// ✅ CHANGED: Read 'id' from $data, not $_GET
$schedule_id = $data->id ?? null;

if (!$schedule_id) {
    http_response_code(400);
    echo json_encode(['message' => 'Schedule ID (id) is required in the request body.']);
    exit;
}

try {
    // 1. Fetch the schedule's main details
    $stmt_schedule = $pdo->prepare("SELECT title, description FROM event_schedules WHERE id = ? AND type = 'training'");
    $stmt_schedule->execute([$schedule_id]);
    $schedule = $stmt_schedule->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        http_response_code(404);
        echo json_encode(['message' => 'Training schedule not found.']);
        exit;
    }

    // 2. Fetch all associated training materials
    $stmt_materials = $pdo->prepare("SELECT id, title, description, type, url FROM training_materials WHERE schedule_id = ?");
    $stmt_materials->execute([$schedule_id]);
    $materials = $stmt_materials->fetchAll(PDO::FETCH_ASSOC);

    // URL rewrite logic
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $uploads_base_url = $protocol . '://' . $_SERVER['HTTP_HOST'];

    foreach ($materials as &$material) {
        if (!empty($material['url']) && filter_var($material['url'], FILTER_VALIDATE_URL) === false) {
            $material['url'] = $uploads_base_url . '/' . $material['url'];
        }
    }
    unset($material);

    // 3. Combine and return the data
    $response = [
        'title' => $schedule['title'],
        'description' => $schedule['description'],
        'materials' => $materials
    ];

    echo json_encode($response);

} catch (Exception $e) {
    // Log and return error
    error_log("[" . date('Y-m-d H:i:s') . "] Exception: " . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../../../../public/error.log');
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred. Please check the error log.']);
}
?>
