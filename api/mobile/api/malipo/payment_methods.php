<?php
// server/api/malipo/payment_methods.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

// Authenticate via JWT in POST body using existing middleware
$auth_data = get_auth_user();
$user_id = $auth_data->user_id; // Not used for now, but available

// For now, return a static list of supported methods.
// Align IDs with client assumptions (Airtel=1, TNM Mpamba=2).
$methods = [
    [ 'id' => 1, 'name' => 'Airtel Money' ],
    [ 'id' => 2, 'name' => 'TNM Mpamba' ],
];

echo json_encode($methods);
?>