<?php
// ---------------------------------------------------------------------
// Unified Logging Setup (matches subscription creation script)
// ---------------------------------------------------------------------
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
    $fp = $file ? " in $file:$line" : "";
    $entry = "[$time] $level: $message$fp\n";
    @error_log($entry, 3, $logPath);
}

set_error_handler(function($severity, $message, $file, $line) {
    log_entry("PHP_ERROR", $message, $file, $line);
    return false;
});

set_exception_handler(function(Throwable $e) {
    log_entry("UNCAUGHT_EXCEPTION", $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString(), $e->getFile(), $e->getLine());
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Internal Server Error']);
    exit;
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        log_entry("FATAL", $err['message'], $err['file'], $err['line']);
    }
});

// CORS + Dependencies
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';

try {
    $payload = json_decode(file_get_contents('php://input'), true);

    if (!$payload) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Invalid JSON payload']);
        exit;
    }

    // Log incoming callback for debugging
    log_entry("CALLBACK_RECEIVED", json_encode($payload));

    // TODO: Verify signature from MALIPO
    // $signature = $_SERVER['HTTP_X_MALIPO_SIGNATURE'] ?? null;
    // Verify signature here...

    $statusRaw     = $payload['status'] ?? null;
    $merchantTrxId = $payload['merchant_txn_id'] ?? $payload['merchantTrxId'] ?? $payload['merchant_trx_id'] ?? null;
    $transactionId = $payload['transaction_id'] ?? null;
    $customerRef   = $payload['customer_ref'] ?? null;
    $amount        = isset($payload['amount']) ? (float)$payload['amount'] : null;

    if (!$merchantTrxId || !$statusRaw) {
        http_response_code(422);
        echo json_encode(['error' => true, 'message' => 'Missing merchant_txn_id or status']);
        exit;
    }

    // Find PENDING payment only
    $stmt = $pdo->prepare("
        SELECT id, user_id, reference_id, status
        FROM payments 
        WHERE payment_type = 'membership'
          AND method = 'gateway'
          AND gateway_transaction_id = ?
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$merchantTrxId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        log_entry("NOT_FOUND", "No payment found for merchant_txn_id $merchantTrxId");
        http_response_code(404);
        echo json_encode(['error' => true, 'message' => 'Payment not found']);
        exit;
    }

    // Idempotency: already processed?
    if ($payment['status'] === 'completed') {
        log_entry("INFO", "Callback already processed for $merchantTrxId");
        echo json_encode(['error' => false, 'message' => 'Already processed']);
        exit;
    }

    $paymentId      = (int)$payment['id'];
    $userId         = (int)$payment['user_id'];
    $subscriptionId = (int)$payment['reference_id'];

    // Get Invoice
    $stmt = $pdo->prepare("
        SELECT id, total_amount 
        FROM invoices 
        WHERE related_type = 'membership'
          AND related_id = ?
          AND user_id = ?
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$subscriptionId, $userId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        log_entry("NOT_FOUND", "Invoice not found for subscription $subscriptionId user $userId");
        http_response_code(404);
        echo json_encode(['error' => true, 'message' => 'Invoice not found']);
        exit;
    }

    $invoiceId   = (int)$invoice['id'];
    $totalAmount = (float)$invoice['total_amount'];
    $paidAmount  = $amount ?? $totalAmount;

    // Normalize status
    $s = strtolower($statusRaw);
    $status = match(true) {
        $s === 'completed' || $s === 'success' || $s === 'paid' => 'success',
        $s === 'failed' || $s === 'cancelled' => 'failed',
        default => 'pending'
    };

    $meta = json_encode([
        'source'        => 'malipo',
        'merchantTrxId' => $merchantTrxId,
        'transactionId' => $transactionId,
        'customerRef'   => $customerRef,
        'statusRaw'     => $statusRaw,
    ]);

    // Process based on status
    $pdo->beginTransaction();
    try {
        if ($status === 'success') {
            // Validate amount (optional: reject or just log)
            if ($amount !== null && abs($amount - $totalAmount) > 0.01) {
                log_entry("AMOUNT_MISMATCH", "Expected $totalAmount, got $amount for $merchantTrxId");
            }

            $pdo->prepare("UPDATE invoices SET status='paid', balance_due=0 WHERE id=?")
                ->execute([$invoiceId]);

            $pdo->prepare("UPDATE membership_subscriptions SET status='active', balance_due=0 WHERE id=?")
                ->execute([$subscriptionId]);

            $pdo->prepare("
                UPDATE membership_subscriptions 
                SET status='expired' 
                WHERE user_id=? AND id<>? AND status='active'
            ")->execute([$userId, $subscriptionId]);

            $pdo->prepare("
                UPDATE payments 
                SET status='completed', amount=?, meta=?, gateway_transaction_id=COALESCE(?, gateway_transaction_id)
                WHERE id=?
            ")->execute([$paidAmount, $meta, $transactionId, $paymentId]);

            log_entry("SUCCESS", "Subscription $subscriptionId activated for user $userId");

        } elseif ($status === 'failed') {
            $pdo->prepare("UPDATE invoices SET status='failed' WHERE id=?")->execute([$invoiceId]);
            $pdo->prepare("UPDATE membership_subscriptions SET status='pending' WHERE id=?")->execute([$subscriptionId]);
            $pdo->prepare("
                UPDATE payments 
                SET status='failed', amount=?, meta=?, gateway_transaction_id=COALESCE(?, gateway_transaction_id)
                WHERE id=?
            ")->execute([$paidAmount, $meta, $transactionId, $paymentId]);

            log_entry("FAILED", "Payment failed for subscription $subscriptionId");

        } else {
            $pdo->prepare("UPDATE invoices SET status='pending' WHERE id=?")->execute([$invoiceId]);
            $pdo->prepare("UPDATE membership_subscriptions SET status='pending' WHERE id=?")->execute([$subscriptionId]);
            $pdo->prepare("
                UPDATE payments 
                SET meta=?, gateway_transaction_id=COALESCE(?, gateway_transaction_id)
                WHERE id=?
            ")->execute([$meta, $transactionId, $paymentId]);

            log_entry("PENDING", "Payment still pending for subscription $subscriptionId");
        }

        $pdo->commit();
        echo json_encode(['error' => false, 'message' => 'Callback processed', 'status' => $status]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    log_entry("EXCEPTION", $e->getMessage(), $e->getFile(), $e->getLine());
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Callback error']);
}