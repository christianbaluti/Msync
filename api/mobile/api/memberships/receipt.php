<?php
// api/memberships/receipt.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../vendor/autoload.php';
require '../db_connection.php';
require '../auth_middleware.php';

$auth_data = get_auth_user();
$user_id = $auth_data->user_id;

$receipt_number = $_GET['number'] ?? null;

if (!$receipt_number) {
    http_response_code(400);
    echo json_encode(['message' => 'Receipt number is required.']);
    exit;
}

try {
    $sql = "
        SELECT
            r.receipt_number,
            r.issued_at,
            p.amount,
            p.method AS payment_method,
            p.gateway_transaction_id,
            u.full_name AS user_name,
            u.email AS user_email,
            u.phone AS user_phone,
            c.name AS company_name,
            mt.name AS item_name,
            mt.description AS item_description
        FROM receipts r
        JOIN payments p ON r.payment_id = p.id
        JOIN users u ON p.user_id = u.id
        JOIN membership_subscriptions ms ON p.reference_id = ms.id AND p.payment_type = 'membership'
        JOIN membership_types mt ON ms.membership_type_id = mt.id
        LEFT JOIN companies c ON u.company_id = c.id
        WHERE r.receipt_number = ? AND p.user_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    // This query ensures the user owns the receipt they are requesting
    $stmt->execute([$receipt_number, $user_id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receipt) {
        http_response_code(404);
        echo json_encode(['message' => 'Receipt not found or you do not have permission to view it.']);
        exit;
    }

    echo json_encode($receipt);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>