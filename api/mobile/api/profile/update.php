<?php
// api/profile/update.php

// ===============================================================
// ERROR LOGGING SETUP (same engine as first script)
// ===============================================================
$logPath = __DIR__ . '/../../../../public/error.log';

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logPath);

// Create the log file if missing
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
        $e->getLine()
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
// ORIGINAL API CODE STARTS HERE
// ===============================================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

$auth_data = get_auth_user();
$user_id = $auth_data->user_id;
$data = json_decode(file_get_contents("php://input"));

try {

    $update_fields = [];
    $params = [];

    if (!empty($data->full_name)) { $update_fields[] = "full_name = ?"; $params[] = $data->full_name; }
    if (!empty($data->phone)) { $update_fields[] = "phone = ?"; $params[] = $data->phone; }
    if (!empty($data->gender)) { $update_fields[] = "gender = ?"; $params[] = $data->gender; }
    if (isset($data->is_employed)) { 
        $update_fields[] = "is_employed = ?";
        $params[] = $data->is_employed;
    }

    if (!empty($data->is_employed)) {

        if (!empty($data->company_id)) {
            $update_fields[] = "company_id = ?";
            $params[] = $data->company_id;
        } 
        else if (!empty($data->company_name)) {
            $stmt_company = $pdo->prepare("SELECT id FROM companies WHERE name = ?");
            $stmt_company->execute([$data->company_name]);
            $company = $stmt_company->fetch();

            if ($company) {
                $company_id_to_use = $company['id'];
            } else {
                $stmt_create = $pdo->prepare("INSERT INTO companies (name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
                $stmt_create->execute([$data->company_name, uniqid().'@temp.com', uniqid(), 'dummy']);
                $company_id_to_use = $pdo->lastInsertId();
            }

            $update_fields[] = "company_id = ?";
            $params[] = $company_id_to_use;
        }

        if (isset($data->position)) {
            $update_fields[] = "position = ?";
            $params[] = $data->position;
        }

    } else {

        $update_fields[] = "company_id = NULL";

        if (isset($data->position)) { 
            $update_fields[] = "position = ?";
            $params[] = $data->position;
        }
    }

    if (empty($update_fields)) {
        echo json_encode(['message' => 'No fields to update.']);
        exit;
    }

    $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['message' => 'Profile updated successfully!']);

} catch (Exception $e) {

    // Log DETAILS but return safe JSON response
    log_entry(
        'PROFILE_UPDATE_EXCEPTION',
        $e->getMessage() . "\nStack:\n" . $e->getTraceAsString(),
        $e->getFile(),
        $e->getLine()
    );

    http_response_code(500);
    echo json_encode(['error'=>true, 'message'=>'An error occurred while updating the profile']);
}
?>
