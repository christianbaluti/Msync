<?php
// /api/malipo/config.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';

// Allowed types
$types = [
    "membership" => "MALIPO_MEMBERSHIPS",
    "event"      => "MALIPO_EVENTS",
    "marketplace"=> "MALIPO_MARKETPLACE"
];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Validate payment_type
$payment_type = $_GET['payment_type'] ?? null;

if (!$payment_type || !isset($types[$payment_type])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing or invalid payment_type. Expected: membership, event, marketplace"
    ]);
    exit;
}

// Resolve gateway name
$gateway_name = $types[$payment_type];

try {
    // Load payment gateway config
    $stmt = $pdo->prepare("SELECT config FROM payment_gateways WHERE name = ? LIMIT 1");
    $stmt->execute([$gateway_name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(["error" => "Payment gateway configuration not found"]);
        exit;
    }

    // Decode config JSON
    $cfg = json_decode($row['config'], true);

    if (!$cfg) {
        http_response_code(500);
        echo json_encode(["error" => "Invalid configuration format"]);
        exit;
    }

    // Return only required keys
    $response = [
        "app_id"    => $cfg["app_id"] ?? null,
        "api_key"   => $cfg["api_key"] ?? null,
        "base_url"  => "https://invoicing.malipo.mw/guest",
        "callback"  => $cfg["callback"] ?? null,
        "merchid"   => $cfg["merchid"] ?? null,
        "sdk"       =>  $cfg["sdk"] ?? null
    ];

    echo json_encode([
        "success" => true,
        "payment_type" => $payment_type,
        "gateway_name" => $gateway_name,
        "config" => $response
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
}

