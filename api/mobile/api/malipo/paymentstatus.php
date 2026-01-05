<?php
// server/api/malipo/paymentstatus.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

/**
 * Log to ../../../../../public/error.log
 */
function log_error_file($msg) {
    $logPath = __DIR__ . '/../../../public/error.log';
    $ts = date('Y-m-d H:i:s');
    @error_log("[$ts] paymentstatus.php: $msg\n", 3, $logPath);
}

try {

    /* ---------------------------
        AUTHENTICATION
    ----------------------------*/
    $auth = get_auth_user();
    if (!$auth || empty($auth->user_id)) {
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => 'Unauthorized']);
        exit;
    }
    $userId = (int)$auth->user_id;


    /* ---------------------------
        PARSE INPUT
    ----------------------------*/
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        log_error_file("Invalid JSON body");
        http_response_code(400);
        echo json_encode(['status' => 'failed', 'message' => 'Invalid JSON']);
        exit;
    }

    $merchantTrxId = $input['merchantTrxId'] ?? null;
    $orderId = isset($input['order_id']) ? (int)$input['order_id'] : null;


    /* ===========================================================
        1. MARKETPLACE PAYMENT STATUS (order_id provided)
       =========================================================== */
    if ($orderId) {

        $stmt = $pdo->prepare("
            SELECT id, status 
            FROM invoices 
            WHERE related_type = 'marketplace_order'
              AND related_id = ?
              AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId, $userId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            log_error_file("Marketplace invoice not found for order_id $orderId user $userId");
            http_response_code(404);
            echo json_encode(['status' => 'failed', 'message' => 'Invoice not found']);
            exit;
        }

        $mapped = match (strtolower($invoice['status'])) {
            'paid'   => 'success',
            'failed' => 'failed',
            default  => 'pending',
        };

        echo json_encode([
            'status' => $mapped,
            'order_id' => $orderId,
            'invoice_ref' => 'INV-' . (int)$invoice['id'],
        ]);
        exit;
    }



    /* ===========================================================
        2. MEMBERSHIP / EVENT PAYMENT STATUS (no order_id)
       =========================================================== */

    // Find latest invoice for membership or event
    $stmt = $pdo->prepare("
        SELECT id, status
        FROM invoices
        WHERE user_id = ?
          AND related_type IN ('membership', 'event_ticket')
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        // No invoice yet = still pending
        echo json_encode(['status' => 'pending']);
        exit;
    }

    $mapped = match (strtolower($invoice['status'])) {
        'paid'   => 'success',
        'failed' => 'failed',
        default  => 'pending',
    };

    echo json_encode([
        'status' => $mapped,
        'invoice_ref' => 'INV-' . (int)$invoice['id'],
    ]);


} catch (Exception $e) {

    log_error_file("Unhandled exception: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'failed',
        'message' => 'Internal error: ' . $e->getMessage(),
    ]);
}
?>
