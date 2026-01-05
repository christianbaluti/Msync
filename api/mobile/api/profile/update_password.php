<?php
// api/profile/update_password.php

// ===============================================================
// ERROR LOGGING SETUP (Copied from your update.php)
// ===============================================================
$logPath = __DIR__ . '/../../../../public/error.log';

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logPath);

if (!file_exists($logPath)) {
    @mkdir(dirname($logPath), 0755, true);
    @touch($logPath);
    @chmod($logPath, 0644);
}

function log_entry($level, $message, $file = null, $line = null) {
    global $logPath;
    $time = date('Y-m-d H:i:s');
    $extra = $file ? " in $file:$line" : "";
    error_log("[$time] $level: $message$extra\n", 3, $logPath);
}

set_error_handler(function($severity, $message, $file, $line) {
    $map = [
        E_ERROR=>'E_ERROR', E_WARNING=>'E_WARNING', E_PARSE=>'E_PARSE',
        E_NOTICE=>'E_NOTICE', E_CORE_ERROR=>'E_CORE_ERROR',
        E_CORE_WARNING=>'E_CORE_WARNING', E_COMPILE_ERROR=>'E_COMPILE_ERROR',
        E_COMPILE_WARNING=>'E_COMPILE_WARNING', E_USER_ERROR=>'E_USER_ERROR',
        E_USER_WARNING=>'E_USER_WARNING', E_USER_NOTICE=>'E_USER_NOTICE',
        E_STRICT=>'E_STRICT', E_RECOVERABLE_ERROR=>'E_RECOVERABLE_ERROR',
        E_DEPRECATED=>'E_DEPRECATED', E_USER_DEPRECATED=>'E_USER_DEPRECATED'
    ];
    $level = $map[$severity] ?? "E_UNKNOWN($severity)";
    log_entry($level, $message, $file, $line);
    return false;
});

set_exception_handler(function(Throwable $e) {
    log_entry(
        'UNCAUGHT_EXCEPTION',
        $e->getMessage() . "\nStack:\n" . $e->getTraceAsString(),
        $e->getFile(),
        $e.getLine()
    );
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error'=>true, 'message'=>'Internal Server Error']);
    exit;
});

register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        log_entry('FATAL_ERROR', $e['message'], $e['file'], $e['line']);
    }
});
// ===============================================================
// API CODE STARTS HERE
// ===============================================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

// Authenticate user and get user ID
$auth_data = get_auth_user();
$user_id = $auth_data->user_id;
$data = json_decode(file_get_contents("php://input"));

// --- Password Change Logic ---
if (empty($data->old_password) || empty($data->new_password)) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Old and new passwords are required.']);
    exit;
}

try {
    // 1. Get the user's current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404); // Not Found
        echo json_encode(['message' => 'User not found.']);
        exit;
    }

    // 2. Verify the old password
    if (!password_verify($data->old_password, $user['password_hash'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Incorrect old password.']);
        exit;
    }

    // 3. Hash the new password
    $new_password_hash = password_hash($data->new_password, PASSWORD_BCRYPT);

    // 4. Update the database with the new hash
    $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $update_stmt->execute([$new_password_hash, $user_id]);

    echo json_encode(['message' => 'Password updated successfully!']);

} catch (Exception $e) {
    log_entry(
        'PASSWORD_UPDATE_EXCEPTION',
        $e->getMessage() . "\nStack:\n" . $e->getTraceAsString(),
        $e->getFile(),
        $e.getLine()
    );

    http_response_code(500);
    echo json_encode(['error'=>true, 'message'=>'An error occurred while updating the password.']);
}
?>