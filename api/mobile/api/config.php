<?php
// api/mobile/config.php

/**
 * Malipo Payment Gateway Configuration
 * This file dynamically fetches credentials from the 'payment_gateways' table.
 */

// We need the database connection to fetch the config
// This path assumes db_connection.php is in the same /api/mobile/ directory
require_once __DIR__ . '/db_connection.php'; 

try {
    // 1. Find the MALIPO gateway config
    $stmt = $pdo->prepare("SELECT config FROM payment_gateways WHERE name = 'MALIPO' LIMIT 1");
    $stmt->execute();
    $gateway = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gateway) {
        throw new Exception("MALIPO payment gateway not configured in database.");
    }

    // 2. Decode the JSON config
    // We use 'true' to get an associative array
    $config = json_decode($gateway['config'], true); 

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode MALIPO config JSON from database.");
    }

    // 3. Define the constants from the fetched values
    define('MALIPO_API_KEY', $config['api_key'] ?? null);
    define('MALIPO_APP_ID', $config['app_id'] ?? $config['merchid'] ?? null); // Use app_id, fallback to merchid
    define('MALIPO_API_BASE_URL', $config['base_url'] ?? 'https://app.malipo.mw/api/v1'); // Fallback just in case

    // 4. Check if essential keys were found
    if (!MALIPO_API_KEY || !MALIPO_APP_ID) {
         throw new Exception("Missing 'api_key' or 'app_id' in MALIPO config JSON.");
    }

} catch (Exception $e) {
    // If this fails, the whole payment system can't work.
    // We must stop execution and report the error.
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        'success' => false,
        'message' => 'Critical Error: Could not load payment gateway configuration. ' . $e->getMessage()
    ]);
    exit;
}

?>