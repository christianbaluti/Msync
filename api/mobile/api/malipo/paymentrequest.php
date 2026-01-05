<?php
// /api/malipo/paymentrequest.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

function log_error_file($message) {
    $logPath = __DIR__ . '/../../../../public/error.log';
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] paymentrequest.php: $message\n";
    @error_log($line, 3, $logPath);
}
try {
    $auth_data = get_auth_user();
    $user_id = $auth_data->user_id;

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        log_error_file('Invalid JSON body');
        http_response_code(400);
        echo json_encode(['message' => 'Invalid JSON body']);
        exit;
    }

    // Core payment fields
    $merchantTrxId = $input['merchantTrxId'] ?? null;
    $customerPhone = $input['customerPhone'] ?? null;
    $bankId = isset($input['bankId']) ? (int)$input['bankId'] : null;
    $amount = isset($input['amount']) ? (float)$input['amount'] : null;
    $currency = $input['currency'] ?? 'MWK';
    $description = $input['description'] ?? '';

    // Context fields
    $paymentType = $input['paymentType'] ?? null; // 'membership', 'event_ticket', 'marketplace_order'
    $referenceId = $input['referenceId'] ?? null; // membership_type_id, event_ticket_type_id, or order_id
    $eventId = $input['eventId'] ?? null;         // Only for event_ticket

    if (!$merchantTrxId || !$customerPhone || !$bankId || !$amount || !$paymentType || !$referenceId) {
        log_error_file('Missing required fields (merchantTrxId/customerPhone/bankId/amount/paymentType/referenceId)');
        http_response_code(422);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }
    
    // Sanitize phone
    $customerPhone = preg_replace('/\D+/', '', $customerPhone);
    if (strlen($customerPhone) !== 12) {
        log_error_file('Invalid phone format: ' . $customerPhone);
        http_response_code(422);
        echo json_encode(['message' => 'Phone must be 12 digits, e.g. 265XXXXXXXXX']);
        exit;
    }

    $pdo->beginTransaction();

    $invoice_id = null;
    $related_id = null; // This will be the ID of the subscription or ticket

    if ($paymentType === 'membership') {
        $membership_type_id = (int)$referenceId;
        
        // 1. Get Membership Type Details
        $stmtT = $pdo->prepare("SELECT id, fee, renewal_month, name FROM membership_types WHERE id = ? LIMIT 1");
        $stmtT->execute([$membership_type_id]);
        $typeRow = $stmtT->fetch(PDO::FETCH_ASSOC);
        if (!$typeRow) {
            throw new Exception('Membership type not found');
        }
        
        // Use the fee from the database as the canonical amount, overriding passed amount
        $amount = (float)$typeRow['fee'];
        $renewal_month = (int)($typeRow['renewal_month'] ?? 1);
        $today = new DateTime();
        $start_date = $today->format('Y-m-d');
        // Assuming renewal_month is duration in months
        $end_date = (clone $today)->modify('+' . max(1, $renewal_month) . ' months')->format('Y-m-d');

        // 2. Create new PENDING subscription
        $card_number = 'MS-' . $user_id . '-' . time();
        $stmtSub = $pdo->prepare("INSERT INTO membership_subscriptions (user_id, membership_type_id, start_date, end_date, membership_card_number, status, balance_due, invoice_id) VALUES (?, ?, ?, ?, ?, 'pending', ?, NULL)");
        $stmtSub->execute([$user_id, $membership_type_id, $start_date, $end_date, $card_number, $amount]);
        $subscription_id = (int)$pdo->lastInsertId();
        $related_id = $subscription_id;

        // 3. Create invoice linked to this subscription
        $stmtInv = $pdo->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, paid_amount, balance_due, status, due_date) VALUES (?, 'membership', ?, ?, 0, ?, 'unpaid', ?)");
        $due_date = (new DateTime())->modify('+30 days')->format('Y-m-d');
        $stmtInv->execute([$user_id, $subscription_id, $amount, $amount, $due_date]);
        $invoice_id = (int)$pdo->lastInsertId();

        // 4. Link invoice back to subscription
        $pdo->prepare("UPDATE membership_subscriptions SET invoice_id = ? WHERE id = ?")->execute([$invoice_id, $subscription_id]);

    } elseif ($paymentType === 'event_ticket') {
        $event_ticket_type_id = (int)$referenceId;
        $event_id = (int)$eventId;

        if (!$event_id) {
             throw new Exception('Event ID is required for ticket purchase');
        }

        // 1. Get Ticket Type Details
        $stmtT = $pdo->prepare("SELECT id, price FROM event_ticket_types WHERE id = ? AND event_id = ? LIMIT 1");
        $stmtT->execute([$event_ticket_type_id, $event_id]);
        $typeRow = $stmtT->fetch(PDO::FETCH_ASSOC);
        if (!$typeRow) {
            throw new Exception('Event ticket type not found');
        }
        
        // Use the fee from the database as the canonical amount
        $amount = (float)$typeRow['price'];

        // 2. Create new PENDING ticket
        $ticketCode = 'EVT' . $event_id . '-TKT' . strtoupper(uniqid());
        $qrCode = $merchantTrxId; // Use merchantTrxId as unique QR data for now
        
        $stmtTicket = $pdo->prepare("INSERT INTO event_tickets (ticket_type_id, event_id, user_id, price, balance_due, status, ticket_code, qr_code, purchased_at) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())");
        $stmtTicket->execute([$event_ticket_type_id, $event_id, $user_id, $amount, $amount, $ticketCode, $qrCode]);
        $ticket_id = (int)$pdo->lastInsertId();
        $related_id = $ticket_id;
        
        // 3. Create invoice linked to this ticket
        $stmtInv = $pdo->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, paid_amount, balance_due, status, due_date) VALUES (?, 'event_ticket', ?, ?, 0, ?, 'unpaid', ?)");
        $due_date = (new DateTime())->modify('+30 days')->format('Y-m-d');
        $stmtInv->execute([$user_id, $ticket_id, $amount, $amount, $due_date]);
        $invoice_id = (int)$pdo->lastInsertId();

    } elseif ($paymentType === 'marketplace_order') {
        // ... (Marketplace logic remains unchanged) ...
        $order_id = (int)$referenceId;
        $related_id = $order_id;
        $stmt = $pdo->prepare("SELECT i.id FROM invoices i WHERE i.related_type = 'marketplace_order' AND i.related_id = ? AND i.user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($inv) {
            $invoice_id = (int)$inv['id'];
            $pdo->prepare("UPDATE invoices SET status = 'pending' WHERE id = ?")->execute([$invoice_id]);
            $pdo->prepare("UPDATE marketplace_orders SET status = 'pending' WHERE id = ?")->execute([$order_id]);
        } else {
            $stmtInv = $pdo->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, paid_amount, balance_due, status) VALUES (?, 'marketplace_order', ?, ?, 0, ?, 'unpaid')");
            $stmtInv->execute([$user_id, $order_id, $amount, $amount]);
            $invoice_id = (int)$pdo->lastInsertId();
        }
    } else {
        throw new Exception('Invalid payment type');
    }
    
    // --- Gateway Resolution ---
    $preferredName = ($paymentType === 'marketplace_order') ? 'MALIPO_MARKETPLACE'
                    : (($paymentType === 'event_ticket') ? 'MALIPO_EVENTS' : 'MALIPO_MEMBERSHIPS');
    $stmtCfg = $pdo->prepare("SELECT id, name, config FROM payment_gateways WHERE name = ? LIMIT 1");
    $stmtCfg->execute([$preferredName]);
    $gateway = $stmtCfg->fetch(PDO::FETCH_ASSOC);
    if (!$gateway) {
        $stmtCfg2 = $pdo->prepare("SELECT id, name, config FROM payment_gateways WHERE name LIKE 'MALIPO_%' ORDER BY id ASC LIMIT 1");
        $stmtCfg2->execute();
        $gateway = $stmtCfg2->fetch(PDO::FETCH_ASSOC);
    }
    if (!$gateway) {
        throw new Exception('Payment gateway configuration missing');
    }
    $gateway_id = (int)$gateway['id'];
    
    // 5. Insert the PENDING payment record
    $meta = json_encode([
        'source' => 'malipo',
        'merchantTrxId' => $merchantTrxId,
        'invoiceRef' => $invoice_id ? ('INV-' . (int)$invoice_id) : null,
        'description' => $description,
    ]);
    $stmtPay = $pdo->prepare("INSERT INTO payments (user_id, company_id, gateway_id, payment_type, reference_id, amount, method, status, meta, gateway_transaction_id) VALUES (?, NULL, ?, ?, ?, ?, 'gateway', 'pending', ?, ?)");
    $stmtPay->execute([$user_id, $gateway_id, $paymentType, $related_id, $amount, $meta, $merchantTrxId]);


    // --- Call MALIPO API ---
    $cfg = json_decode($gateway['config'] ?? '{}', true);
    $clean = function($v){ return is_string($v) ? trim(str_replace('`','',$v)) : $v; };
    $apiBase = $clean($cfg['base_url'] ?? '');
    $apiKey  = $clean($cfg['api_key'] ?? '');
    $appId   = $clean($cfg['app_id'] ?? '');
    if (!$apiBase || !$apiKey || !$appId) {
        throw new Exception('Incomplete MALIPO configuration');
    }

    try {
        $client = new \GuzzleHttp\Client(['timeout' => 10, 'http_errors' => false]);
        $url = rtrim($apiBase, '/') . '/paymentrequest';
        $payload = [
            'merchantTrxId' => $merchantTrxId,
            'customerPhone' => $customerPhone,
            'bankId' => $bankId,
            'amount' => $amount, // Use the amount determined by the backend
        ];
        $response = $client->request('POST', $url, [
            'headers' => [
                'x-api-key' => $apiKey,
                'x-app-id'  => $appId,
                'Accept'    => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $status = $response->getStatusCode();
        $body = (string)$response->getBody();
        
        if ($status !== 200 && $status !== 201) {
            throw new Exception('MALIPO paymentrequest failed with HTTP ' . $status . ' Body: ' . $body);
        }

        // All good, commit transaction
        $pdo->commit();

        $respJson = json_decode($body, true);
        echo json_encode([
            'success' => true,
            'status' => 'pending',
            'merchantTrxId' => $merchantTrxId,
            'order_id' => ($paymentType === 'marketplace_order') ? $related_id : null,
            'invoice_ref' => $invoice_id ? ('INV-' . $invoice_id) : null,
            'gateway' => $respJson,
            'message' => 'USSD request initiated. Approve on your phone.'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback DB changes if API call fails
        log_error_file('MALIPO request error: ' . $e->getMessage());
        http_response_code(502);
        echo json_encode(['message' => 'Gateway error: ' . $e->getMessage()]);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error_file('Unhandled exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>