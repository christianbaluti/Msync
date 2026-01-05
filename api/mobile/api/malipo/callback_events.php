<?php
// server/api/malipo/callback_events.php
// Event Ticket-only MALIPO callback

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';

function log_error_file($message) {
    $logPath = __DIR__ . '/../../../../public/error.log';
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] callback_events.php: $message\n";
    @error_log($line, 3, $logPath);
}

try {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid payload']);
        exit;
    }

    log_error_file('Received callback payload: ' . json_encode($payload));

    $statusRaw = $payload['status'] ?? null;
    $merchantTrxId = $payload['merchant_txn_id'] ?? ($payload['merchantTrxId'] ?? ($payload['merchant_trx_id'] ?? null));
    $transactionId = $payload['transaction_id'] ?? null;
    $customerRef = $payload['customer_ref'] ?? null;
    $amount = isset($payload['amount']) ? (float)$payload['amount'] : null;

    if (!$merchantTrxId || !$statusRaw) {
        http_response_code(422);
        echo json_encode(['message' => 'Missing required fields (merchant_txn_id/status)']);
        exit;
    }

    // Locate existing pending payment
    $stmtP = $pdo->prepare("
        SELECT id, user_id, reference_id, status, amount
        FROM payments 
        WHERE payment_type = 'event_ticket' 
          AND method = 'gateway' 
          AND gateway_transaction_id = ? 
        ORDER BY id DESC LIMIT 1
    ");
    $stmtP->execute([$merchantTrxId]);
    $paymentRow = $stmtP->fetch(PDO::FETCH_ASSOC);
    
    if (!$paymentRow) {
        log_error_file('Pending event payment not found for merchant_txn_id ' . $merchantTrxId);
        http_response_code(404);
        echo json_encode(['message' => 'Pending payment not found']);
        exit;
    }

    // Duplicate callback protection
    if ($paymentRow['status'] === 'completed') {
        log_error_file('Payment already completed for merchant_txn_id ' . $merchantTrxId);
        echo json_encode(['message' => 'Payment already processed']);
        exit;
    }

    $paymentId = (int)$paymentRow['id'];
    $userId = (int)$paymentRow['user_id'];
    $ticketId = (int)$paymentRow['reference_id'];
    $originalAmount = (float)$paymentRow['amount'];

    // Find event invoice linked to this ticket
    $stmtInv = $pdo->prepare("
        SELECT id, status, total_amount 
        FROM invoices 
        WHERE related_type = 'event_ticket' 
          AND related_id = ? 
          AND user_id = ? 
        ORDER BY id DESC LIMIT 1
    ");
    $stmtInv->execute([$ticketId, $userId]);
    $invoice = $stmtInv->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        log_error_file('Invoice not found for event ticket ' . $ticketId . ' user ' . $userId);
        http_response_code(404);
        echo json_encode(['message' => 'Invoice not found']);
        exit;
    }

    $invId = (int)$invoice['id'];
    $totalAmount = (float)$invoice['total_amount'];
    $paidAmount = is_null($amount) ? $totalAmount : $amount;

    // Normalize status from Malipo
    $stRaw = strtolower(trim($statusRaw));
    $st = ($stRaw === 'completed' || $stRaw === 'success' || $stRaw === 'paid') ? 'success'
          : (($stRaw === 'failed' || $stRaw === 'cancelled') ? 'failed' : 'pending');

    $meta = json_encode([
        'source' => 'malipo',
        'merchantTrxId' => $merchantTrxId,
        'transactionId' => $transactionId,
        'customerRef' => $customerRef,
        'statusRaw' => $statusRaw,
    ]);

    log_error_file("Processing: paymentId=$paymentId ticketId=$ticketId invoiceId=$invId normalizedStatus=$st");

    if ($st === 'success') {
        $pdo->beginTransaction();
        try {
            // 1. Update invoice to paid
            $pdo->prepare("
                UPDATE invoices 
                SET status = 'paid', paid_amount = total_amount, balance_due = 0 
                WHERE id = ?
            ")->execute([$invId]);
            
            // 2. Activate event ticket
            $pdo->prepare("
                UPDATE event_tickets 
                SET status = 'bought', balance_due = 0 
                WHERE id = ?
            ")->execute([$ticketId]);
                
            // 3. Update payment row to completed
            $pdo->prepare("
                UPDATE payments 
                SET amount = ?, status = 'completed', meta = ?, gateway_transaction_id = COALESCE(?, gateway_transaction_id) 
                WHERE id = ?
            ")->execute([$paidAmount, $meta, $transactionId, $paymentId]);
            
            // 4. Create receipt (matching admin logic)
            $timezone = new DateTimeZone('Africa/Blantyre');
            $date = new DateTime('now', $timezone);
            $receiptNum = 'RCPT-' . $date->format('Ymd') . '-' . $paymentId;
            
            $receiptMeta = json_encode([
                'payment_method' => 'gateway',
                'transaction_id' => $transactionId,
                'customer_ref' => $customerRef,
            ]);
            
            $pdo->prepare("
                INSERT INTO receipts (payment_id, receipt_number, issued_at, meta)
                VALUES (?, ?, NOW(), ?)
            ")->execute([$paymentId, $receiptNum, $receiptMeta]);
            $receiptId = $pdo->lastInsertId();
                
            $pdo->commit();
            log_error_file("SUCCESS: Ticket $ticketId activated, Invoice $invId paid, Payment $paymentId completed, Receipt $receiptId created ($receiptNum)");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } elseif ($st === 'failed') {
        $pdo->beginTransaction();
        try {
            // 1. Cancel invoice
            $pdo->prepare("
                UPDATE invoices 
                SET status = 'cancelled' 
                WHERE id = ?
            ")->execute([$invId]);
            
            // 2. Deny ticket
            $pdo->prepare("
                UPDATE event_tickets 
                SET status = 'denied' 
                WHERE id = ?
            ")->execute([$ticketId]);
                
            // 3. Update payment row to failed
            $pdo->prepare("
                UPDATE payments 
                SET status = 'failed', meta = ?, gateway_transaction_id = COALESCE(?, gateway_transaction_id) 
                WHERE id = ?
            ")->execute([$meta, $transactionId, $paymentId]);
            
            $pdo->commit();
            log_error_file("FAILED: Ticket $ticketId denied, Invoice $invId cancelled, Payment $paymentId failed");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
            
    } else {
        // Still pending - just enrich meta
        $pdo->prepare("
            UPDATE payments 
            SET meta = ?, gateway_transaction_id = COALESCE(?, gateway_transaction_id) 
            WHERE id = ?
        ")->execute([$meta, $transactionId, $paymentId]);
        
        log_error_file("PENDING: Payment $paymentId still pending");
    }

    echo json_encode(['message' => 'Event ticket callback processed', 'status' => $st]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error_file('Unhandled exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}