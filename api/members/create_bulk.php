<?php
// /api/members/create_bulk.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/mailer.php';
require_once dirname(__DIR__) . '/core/PdfGenerator.php';

// Data now comes from $_POST and $_FILES, not php://input
if (empty($_POST['user_ids']) || !is_array($_POST['user_ids']) || empty($_POST['membership_type_id'])) {
    echo json_encode(['success' => false, 'message' => 'User IDs and Membership Type are required.']);
    exit;
}

$db = (new Database())->connect();
$db->beginTransaction();

try {
    // --- 1. Data Sanitization & Preparation ---
    $user_ids = array_map('intval', $_POST['user_ids']);
    $membership_type_id = (int)$_POST['membership_type_id'];
    $start_date_str = $_POST['start_date'];
    $amount_paid = isset($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : 0.0;
    $payment_method = $_POST['payment_method'] ?? 'credit'; // Default to credit if not specified
    $issue_receipt = isset($_POST['issue_receipt']);
    $issue_invoice = isset($_POST['issue_invoice']);
    
    // --- 2. Handle File Upload (Proof of Payment) ---
    $payment_meta = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_proof'];
        $upload_dir = dirname(__DIR__, 3) . '/public/uploads/proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = uniqid() . '-' . basename($file['name']);
        $destination = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $payment_meta = json_encode([
                'proof_of_payment' => [
                    'filename' => $filename,
                    'original_name' => $file['name'],
                    'size' => $file['size']
                ]
            ]);
        }
    }

    // --- 3. Fetch Shared Data (Membership Type & Users) ---
    $stmt_type = $db->prepare("SELECT fee, renewal_month, name as type_name FROM membership_types WHERE id = ?");
    $stmt_type->execute([$membership_type_id]);
    $type = $stmt_type->fetch(PDO::FETCH_ASSOC);
    if (!$type) throw new Exception("Invalid membership type.");

    $placeholders = rtrim(str_repeat('?,', count($user_ids)), ',');
    $stmt_users = $db->prepare("SELECT id, full_name, email FROM users WHERE id IN ($placeholders)");
    $stmt_users->execute($user_ids);
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    
    // --- 4. Prepare Reusable SQL Statements ---
    $sub_query = "INSERT INTO membership_subscriptions (user_id, membership_type_id, start_date, end_date, membership_card_number, status, balance_due) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_sub = $db->prepare($sub_query);
    
    $payment_query = "INSERT INTO payments (user_id, payment_type, reference_id, amount, method, status, meta, transaction_date) VALUES (?, 'membership', ?, ?, ?, 'completed', ?, NOW())";
    $stmt_payment = $db->prepare($payment_query);
    
    $receipt_query = "INSERT INTO receipts (payment_id, receipt_number, issued_at) VALUES (?, ?, NOW())";
    $stmt_receipt = $db->prepare($receipt_query);
    
    $invoice_query = "INSERT INTO invoices (user_id, related_type, related_id, total_amount, paid_amount, balance_due, status, issued_at, due_date) VALUES (?, 'membership', ?, ?, ?, ?, ?, NOW(), ?)";
    $stmt_invoice = $db->prepare($invoice_query);

    // --- 5. Initialize Services & Loop Through Users ---
    $mailer = new Mailer();
    $pdf_generator = new PdfGenerator();
    $success_count = 0;

    foreach ($users as $user) {
        // a. Create Subscription
        $start_date = new DateTime($start_date_str);
        $end_date = (clone $start_date)->modify('+' . $type['renewal_month'] . ' months');
        $card_number = 'MS-' . $user['id'] . '-' . time() . $success_count;
        $balance_due = (float)$type['fee'] - $amount_paid;
        $status = ($balance_due <= 0) ? 'active' : 'pending';

        $stmt_sub->execute([$user['id'], $membership_type_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'), $card_number, $status, $balance_due]);
        $subscription_id = $db->lastInsertId();

        $payment_id = null;
        if ($amount_paid > 0) {
            // b. Create Payment Record
            $stmt_payment->execute([$user['id'], $subscription_id, $amount_paid, $payment_method, $payment_meta]);
            $payment_id = $db->lastInsertId();
        }
        
        // c. Issue Invoice if requested
        if ($issue_invoice) {
            $invoice_status = ($balance_due <= 0) ? 'paid' : (($amount_paid > 0) ? 'partially_paid' : 'unpaid');
            $due_date = (clone $start_date)->modify('+14 days');
            $stmt_invoice->execute([$user['id'], $subscription_id, $type['fee'], $amount_paid, $balance_due, $invoice_status, $due_date->format('Y-m-d')]);
            $invoice_id = $db->lastInsertId();
            
            $invoice_data = [
                'invoice_id' => $invoice_id, 'user_name' => $user['full_name'], 'membership_type' => $type['type_name'],
                'issued_at' => date('Y-m-d'), 'due_date' => $due_date->format('Y-m-d'), 'total_amount' => $type['fee'],
                'paid_amount' => $amount_paid, 'balance_due' => $balance_due
            ];
            $invoice_pdf = $pdf_generator->generateMembershipInvoicePdf($invoice_data);

            // FIX: Structure the attachment data into the expected array format.
            $attachments = [
                ['filename' => 'invoice.pdf', 'content' => $invoice_pdf]
            ];
            $mailer->send($user['email'], $user['full_name'], "Your Membership Invoice", "Please find your invoice attached.", $attachments);
        }
        
        // d. Issue Receipt if requested and payment was made
        if ($issue_receipt && $payment_id) {
            $receipt_number = 'RCPT-' . $payment_id . '-' . time();
            $stmt_receipt->execute([$payment_id, $receipt_number]);
            
            $receipt_data = [
                'receipt_num' => $receipt_number, 'membership_type' => $type['type_name'],
                'user_name' => $user['full_name'], 'amount_paid' => $amount_paid
            ];
            $receipt_pdf = $pdf_generator->generateMembershipReceiptPdf($receipt_data);
            
            // FIX: Structure the attachment data into the expected array format.
            $attachments = [
                ['filename' => 'receipt.pdf', 'content' => $receipt_pdf]
            ];
            $mailer->send($user['email'], $user['full_name'], "Your Membership Payment Receipt", "Thank you for your payment. Your receipt is attached.", $attachments);
        }
        
        $success_count++;
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => "$success_count members subscribed successfully."]);

} catch (Exception $e) {
    $db->rollBack();
    // Use 500 status code for server errors
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}