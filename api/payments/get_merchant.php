<?php
// ===================== ERROR LOGGING SETUP =====================

// Path to error log file
$errorLogPath = dirname(__DIR__, 2) . '/error.log';

// Report ALL errors
error_reporting(E_ALL);

// Do NOT display errors to users (important for APIs)
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Enable logging
ini_set('log_errors', '1');
ini_set('error_log', $errorLogPath);

// Catch PHP errors (warnings, notices, etc.)
set_error_handler(function ($severity, $message, $file, $line) {
    error_log("[PHP ERROR] $message in $file on line $line");
    return false; // Allow normal PHP handling too
});

// Catch uncaught exceptions
set_exception_handler(function ($exception) {
    error_log("[UNCAUGHT EXCEPTION] " . $exception->getMessage() .
        " in " . $exception->getFile() .
        " on line " . $exception->getLine()
    );

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
    exit;
});

// Catch fatal errors (parse errors, memory errors, etc.)
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        error_log("[FATAL ERROR] {$error['message']} in {$error['file']} on line {$error['line']}");
    }
});

// ===================== END ERROR LOGGING SETUP =====================

// api/payments/get_merchant.php

// Set headers for CORS and JSON output
header('Access-Control-Allow-Origin: *'); // Allow requests from any origin (adjust in production)
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET'); // This endpoint uses GET
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Adjust the path according to your project structure to include your core files
require_once dirname(__DIR__) . '/core/initialize.php'; // Adjusted path

$gateway_name = 'MALIPO'; // The specific gateway we are looking for

try {
    // Instantiate DB & connect (This is the new connection style)
    $db = (new Database())->connect();

    // Prepare SQL query to fetch the config for the specified gateway
    $query = "SELECT config FROM payment_gateways WHERE name = :name LIMIT 1";
    $stmt = $db->prepare($query);

    // Bind the gateway name parameter
    $stmt->bindParam(':name', $gateway_name);

    // Execute the query
    $stmt->execute();

    // Check if a row was found
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $config_json = $row['config'];

        // Decode the JSON configuration
        $config_data = json_decode($config_json, true); // Decode as associative array

        // Check if JSON decoding was successful and if 'merchid' exists
        if (json_last_error() === JSON_ERROR_NONE && isset($config_data['merchid'])) {
            $merchant_id = $config_data['merchid'];

            // Return the merchant ID successfully
            echo json_encode(['success' => true, 'merchantId' => $merchant_id]);
            exit();

        } else {
            // Handle JSON decode error or missing 'merchid' key
            http_response_code(500); // Internal Server Error
            $error_message = json_last_error() !== JSON_ERROR_NONE
                ? 'Failed to parse gateway configuration JSON.'
                : 'Merchant ID (merchid) not found in MALIPO configuration.';
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
    } else {
        // Handle case where the 'MALIPO' gateway configuration is not found
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'MALIPO payment gateway configuration not found in the database.']);
        exit();
    }

} catch (PDOException $e) {
    // Handle potential database errors (this will catch connection or query failures)
    http_response_code(500); // Internal Server Error
    // Log the detailed error for debugging purposes (don't expose details to the client in production)
    error_log("Database Error in get_merchant.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving gateway information.']);
    exit();
} catch (Exception $e) {
    // Handle other unexpected errors (e.g., if Database class is not found)
     http_response_code(500);
     error_log("General Error in get_merchant.php: " . $e->getMessage());
     echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
     exit();
}

?>