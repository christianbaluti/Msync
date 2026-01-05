<?php
// /api/members/upload_csv.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/Mailer.php';
require_once dirname(__DIR__) . '/core/PdfGenerator.php';

// --- 1. Basic Validation & File Check ---
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No CSV file was uploaded or an error occurred.']);
    exit;
}
if (empty($_POST['membership_type_id']) || empty($_POST['start_date'])) {
    echo json_encode(['success' => false, 'message' => 'Membership Type and Start Date are required.']);
    exit;
}

$db = (new Database())->connect();
$db->beginTransaction();

try {
    // --- 2. File Handling & CSV Header Validation ---
    $csv_file = $_FILES['csv_file'];
    $file_path = $csv_file['tmp_name'];
    
    $file_handle = fopen($file_path, 'r');
    if (!$file_handle) throw new Exception("Could not open the uploaded file.");

    $headers = fgetcsv($file_handle);
    $required_headers = ['full_name', 'email'];
    $header_map = [];

    foreach ($required_headers as $required) {
        $index = array_search($required, $headers);
        if ($index === false) throw new Exception("CSV file is missing the required header: '{$required}'.");
        $header_map[$required] = $index;
    }
    // Map optional headers
    $header_map['phone'] = array_search('phone', $headers);
    $header_map['position'] = array_search('position', $headers);

    // --- 3. Data Sanitization & Preparation ---
    $membership_type_id = (int)$_POST['membership_type_id'];
    $start_date_str = $_POST['start_date'];
    $amount_paid = isset($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : 0.0;
    $payment_method = $_POST['payment_method'] ?? 'credit';
    $issue_receipt = isset($_POST['issue_receipt']);
    $issue_invoice = isset($_POST['issue_invoice']);
    
    // Handle optional proof of payment upload
    $payment_meta = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_proof'];
        
        // Server's file system path for saving the file
        $upload_dir = dirname(__DIR__, 3) . '/public/uploads/proofs/';
        // Public web path for accessing the file
        $web_path_prefix = '/uploads/proofs/';

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $filename = uniqid('proof_') . '-' . basename($file['name']);
        
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
            // --- FIX START ---
            // Construct the public web path to the file.
            $web_path = $web_path_prefix . $filename;
            
            // Create the JSON string in the desired format.
            $payment_meta = json_encode(['proof_of_payment' => $web_path]);
            // --- FIX END ---
        }
    }

    // --- 4. Fetch Shared Data (Membership Type) ---
    $stmt_type = $db->prepare("SELECT fee, renewal_month, name as type_name FROM membership_types WHERE id = ?");
    $stmt_type->execute([$membership_type_id]);
    $type = $stmt_type->fetch(PDO::FETCH_ASSOC);
    if (!$type) throw new Exception("Invalid membership type selected.");

    // --- 5. Prepare Reusable SQL Statements ---
    $stmt_find_user = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_create_user = $db->prepare("INSERT INTO users (full_name, email, phone, position, password_hash) VALUES (?, ?, ?, ?, ?)");
    $stmt_sub = $db->prepare("INSERT INTO membership_subscriptions (user_id, membership_type_id, start_date, end_date, membership_card_number, status, balance_due) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_payment = $db->prepare("INSERT INTO payments (user_id, payment_type, reference_id, amount, method, status, meta, transaction_date) VALUES (?, 'membership', ?, ?, ?, 'completed', ?, NOW())");
    $stmt_receipt = $db->prepare("INSERT INTO receipts (payment_id, receipt_number, issued_at) VALUES (?, ?, NOW())");
    $stmt_invoice = $db->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, paid_amount, balance_due, status, issued_at, due_date) VALUES (?, 'membership', ?, ?, ?, ?, ?, NOW(), ?)");

    // --- 6. Initialize Services & Loop Through CSV Rows ---
    $mailer = new Mailer();
    $pdf_generator = new PdfGenerator();
    $success_count = 0;
    $skipped_count = 0;
    $row_num = 1;

    while (($row = fgetcsv($file_handle)) !== false) {
        $row_num++;
        $full_name = trim($row[$header_map['full_name']]);
        $email = trim($row[$header_map['email']]);

        if (empty($full_name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $skipped_count++;
            continue; // Skip invalid rows
        }

        // a. Find or Create User
        $stmt_find_user->execute([$email]);
        $user = $stmt_find_user->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $user_id = $user['id'];
        } else {
            $phone = ($header_map['phone'] !== false) ? trim($row[$header_map['phone']]) : null;
            $position = ($header_map['position'] !== false) ? trim($row[$header_map['position']]) : null;
            $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            
            $stmt_create_user->execute([$full_name, $email, $phone, $position, $password_hash]);
            $user_id = $db->lastInsertId();
        }

        // b. Create Subscription
        $start_date = new DateTime($start_date_str);
        $end_date = (clone $start_date)->modify('+' . $type['renewal_month'] . ' months');
        $card_number = 'MS-' . $user_id . '-' . time() . $success_count;
        $balance_due = (float)$type['fee'] - $amount_paid;
        $status = ($balance_due <= 0) ? 'active' : 'pending';

        $stmt_sub->execute([$user_id, $membership_type_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'), $card_number, $status, $balance_due]);
        $subscription_id = $db->lastInsertId();

        $payment_id = null;
        if ($amount_paid > 0) {
            $stmt_payment->execute([$user_id, $subscription_id, $amount_paid, $payment_method, $payment_meta]);
            $payment_id = $db->lastInsertId();
        }

        if ($issue_invoice) {
            $invoice_status = ($balance_due <= 0) ? 'paid' : (($amount_paid > 0) ? 'partially_paid' : 'unpaid');
            $due_date = (clone $start_date)->modify('+14 days');
            $stmt_invoice->execute([$user_id, $subscription_id, $type['fee'], $amount_paid, $balance_due, $invoice_status, $due_date->format('Y-m-d')]);
            $invoice_id = $db->lastInsertId();
            $invoice_data = ['invoice_id' => $invoice_id, 'user_name' => $full_name, 'membership_type' => $type['type_name'], 'issued_at' => date('Y-m-d'), 'due_date' => $due_date->format('Y-m-d'), 'total_amount' => $type['fee'], 'paid_amount' => $amount_paid, 'balance_due' => $balance_due];
            $invoice_pdf = $pdf_generator->generateMembershipInvoicePdf($invoice_data);
            $mailer->send($email, $full_name, "Your Membership Invoice", "Please find your invoice attached.", [['filename' => 'invoice.pdf', 'content' => $invoice_pdf]]);
        }
        
        if ($issue_receipt && $payment_id) {
            $receipt_number = 'RCPT-' . $payment_id . '-' . time();
            $stmt_receipt->execute([$payment_id, $receipt_number]);
            $receipt_data = ['receipt_num' => $receipt_number, 'membership_type' => $type['type_name'], 'user_name' => $full_name, 'amount_paid' => $amount_paid];
            $receipt_pdf = $pdf_generator->generateMembershipReceiptPdf($receipt_data);
            $mailer->send($email, $full_name, "Your Membership Payment Receipt", "Thank you for your payment. Your receipt is attached.", [['filename' => 'receipt.pdf', 'content' => $receipt_pdf]]);
        }
        
        $success_count++;
    }

    fclose($file_handle);
    $db->commit();
    
    $message = "$success_count members subscribed successfully.";
    if ($skipped_count > 0) {
        $message .= " $skipped_count rows were skipped due to missing or invalid data.";
    }
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}