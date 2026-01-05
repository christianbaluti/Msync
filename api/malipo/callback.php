<?php
// /api/malipo/callback.php
// Handles the Instant Payment Notification (IPN) from MALIPO

// Set up logging
$log_file = dirname(__DIR__, 2) . '/public/error.log'; // Ensure '/logs' directory exists and is writable
ini_set('log_errors', 1);
ini_set('error_log', $log_file);

// Log headers for debugging
$headers = getallheaders();
error_log("=== MALIPO CALLBACK START ===");
error_log("Headers: " . json_encode($headers));

// 1. Get Raw POST Data
$raw_post = file_get_contents('php://input');
error_log("Raw Body: " . $raw_post);

if (empty($raw_post)) {
    error_log("Callback received empty body.");
    http_response_code(400); // Bad Request
    exit;
}

// 2. Load Core Files
try {
    require_once dirname(__DIR__) . '/core/initialize.php';
    require_once dirname(__DIR__) . '/core/mailer.php';
    require_once dirname(__DIR__) . '/core/PdfGenerator.php';
} catch (Exception $e) {
    error_log("Failed to load core dependencies: " . $e->getMessage());
    http_response_code(500); // Server Error
    exit;
}

// 3. Decode JSON Data
$data = json_decode($raw_post, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Failed to decode JSON: " . json_last_error_msg());
    http_response_code(400);
    exit;
}

// 4. Extract Key Information
$status = $data['status'] ?? null;
$order_id = $data['merchant_txn_id'] ?? null;        // This is our generated_order_id
$gateway_ref = $data['transaction_id'] ?? null;      // This is Malipo's transaction ID
$customer_ref = $data['customer_reference'] ?? null; // Malipo's customer-facing ref

// 5. Validation
if (empty($status) || empty($order_id) || empty($gateway_ref)) {
    error_log("Missing required fields. Status: $status, OrderID: $order_id, GatewayRef: $gateway_ref");
    http_response_code(400);
    exit;
}

error_log("Processing Order ID: $order_id, Status: $status, Gateway Ref: $gateway_ref");

$db = null;
try {
    $db = (new Database())->connect();
    $db->beginTransaction();

    // 6. Find the 'pending' payment record using our Order ID
    // We stored our 'generated_order_id' in the 'gateway_transaction_id' field
    $stmt_pay = $db->prepare("SELECT * FROM payments WHERE gateway_transaction_id = ? AND status = 'pending' LIMIT 1");
    $stmt_pay->execute([$order_id]);
    $payment = $stmt_pay->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        error_log("No matching 'pending' payment found for Order ID: $order_id. It might be completed or invalid.");
        $db->rollBack();
        http_response_code(404); // Not Found (or 200 to acknowledge)
        exit;
    }

    $payment_id = $payment['id'];
    $user_id = $payment['user_id'];
    $amount_paid = (float)$payment['amount'];
    $subscription_id = $payment['reference_id']; // This is the membership_subscription.id

    // 7. Handle based on Malipo Status
    if ($status === 'Completed') {
        error_log("Status is 'Completed'. Updating payment $payment_id.");

        // 7a. Update Payment Record
        // Store the *actual* Malipo ref now
        $update_pay_stmt = $db->prepare("UPDATE payments SET status = 'completed', gateway_transaction_id = ?, meta = ? WHERE id = ?");
        $payment_meta = json_decode($payment['meta'], true) ?: [];
        $payment_meta['malipo_customer_ref'] = $customer_ref;
        $payment_meta['malipo_order_id'] = $order_id;
        $update_pay_stmt->execute([$gateway_ref, json_encode($payment_meta), $payment_id]);

        // 7b. Update Subscription
        $stmt_sub = $db->prepare("SELECT balance_due, invoice_id FROM membership_subscriptions WHERE id = ?");
        $stmt_sub->execute([$subscription_id]);
        $subscription = $stmt_sub->fetch(PDO::FETCH_ASSOC);

        if ($subscription) {
            $new_balance_due = max(0, (float)$subscription['balance_due'] - $amount_paid);
            $new_sub_status = ($new_balance_due <= 0) ? 'active' : 'pending'; // Or 'partially_paid' if you add that status

            $db->prepare("UPDATE membership_subscriptions SET balance_due = ?, status = ? WHERE id = ?")
               ->execute([$new_balance_due, $new_sub_status, $subscription_id]);
            error_log("Updated subscription $subscription_id. New Balance: $new_balance_due, New Status: $new_sub_status");

            // 7c. Update Related Invoice (if exists)
            if ($subscription['invoice_id']) {
                $invoice_id = $subscription['invoice_id'];
                $stmt_inv = $db->prepare("SELECT total_amount, paid_amount FROM invoices WHERE id = ?");
                $stmt_inv->execute([$invoice_id]);
                $invoice = $stmt_inv->fetch(PDO::FETCH_ASSOC);

                if ($invoice) {
                    $new_paid_amount_inv = (float)$invoice['paid_amount'] + $amount_paid;
                    $new_inv_balance = (float)$invoice['total_amount'] - $new_paid_amount_inv;
                    $inv_status = ($new_inv_balance <= 0) ? 'paid' : 'partially_paid';

                    $db->prepare("UPDATE invoices SET paid_amount = ?, balance_due = ?, status = ? WHERE id = ?")
                       ->execute([$new_paid_amount_inv, $new_inv_balance, $inv_status, $invoice_id]);
                    error_log("Updated invoice $invoice_id. New Paid: $new_paid_amount_inv, New Status: $inv_status");
                }
            }
        } else {
            error_log("Warning: Payment $payment_id completed but no matching subscription $subscription_id found.");
        }

        // 7d. Generate Receipt and Send Email (as it's now confirmed)
        $user_stmt = $db->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        $type_stmt = $db->prepare("SELECT mt.name FROM membership_types mt JOIN membership_subscriptions ms ON mt.id = ms.membership_type_id WHERE ms.id = ?");
        $type_stmt->execute([$subscription_id]);
        $type_name = $type_stmt->fetchColumn() ?: 'Membership Payment';

        if ($user && $amount_paid > 0) {
            $receipt_num = 'RCPT-MEM-' . date('Ymd') . '-' . $payment_id;
            $db->prepare("INSERT INTO receipts (payment_id, receipt_number) VALUES (?, ?)")->execute([$payment_id, $receipt_num]);

            $receipt_data = [
                'receipt_num' => $receipt_num,
                'user_name' => $user['full_name'],
                'amount_paid' => $amount_paid,
                'membership_type' => $type_name,
                'payment_method' => 'MALIPO Gateway',
                'transaction_date' => date('Y-m-d H:i:s'),
                'gateway_ref' => $gateway_ref
            ];
            
            $pdfGenerator = new PdfGenerator();
            $receipt_pdf = $pdfGenerator->generateMembershipReceiptPdf($receipt_data);
            $attachments = [['content' => $receipt_pdf, 'filename' => $receipt_num . '.pdf']];
            
            $email_subject = "Your Membership Payment Receipt";
            $email_body = "Dear " . htmlspecialchars($user['full_name']) . ",\n\nWe have successfully received your payment. Your receipt is attached.";
            
            try {
                $mailer = new Mailer();
                $mailer->send($user['email'], $user['full_name'], $email_subject, nl2br(htmlspecialchars($email_body)), $attachments);
                error_log("Receipt email sent to " . $user['email']);
            } catch (Exception $mailError) {
                error_log("Mailer Error in callback.php (PaymentID $payment_id): " . $mailError->getMessage());
            }
        }

    } elseif ($status === 'Failed') {
        error_log("Status is 'Failed'. Updating payment $payment_id.");
        // Update payment to 'failed' and store the Malipo reference
        $db->prepare("UPDATE payments SET status = 'failed', gateway_transaction_id = ? WHERE id = ?")
           ->execute([$gateway_ref, $payment_id]);

        // Optionally send a "payment failed" email
        // ... (email logic) ...

    } else {
        error_log("Received unknown status: $status");
    }

    // 8. Commit Transaction
    $db->commit();
    error_log("Transaction committed for Order ID: $order_id.");

} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("CRITICAL ERROR in callback.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500); // Internal Server Error
    exit;
}

// 9. Respond
// Malipo doesn't expect a response body, but a 200 OK is standard.
http_response_code(200);
error_log("=== MALIPO CALLBACK END ===");
exit;
?>