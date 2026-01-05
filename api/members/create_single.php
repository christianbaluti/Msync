<?php
// /api/members/create_single.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// Load Mailer and PDF Generator
require_once dirname(__DIR__) . '/core/mailer.php';
require_once dirname(__DIR__) . '/core/PdfGenerator.php';

$data = $_POST;
$db = (new Database())->connect();
$db->beginTransaction();

try {
    // --- 1. VALIDATION ---
    $user_type = $data['user_type'] ?? 'existing';
    $user_id = !empty($data['user_id']) ? (int)$data['user_id'] : null;
    $amount_paid = isset($data['amount_paid']) ? (float)$data['amount_paid'] : 0.0;
    if (empty($data['membership_type_id']) || empty($data['start_date'])) {
        throw new Exception("Membership Type and Start Date are required.");
    }

    // --- 2. HANDLE USER (Create or Use Existing) ---
    $user_email = '';
    $user_name = '';

    if ($user_type === 'new') {
        if (empty($data['full_name']) || empty($data['email']) || empty($data['phone'])) {
            throw new Exception("Full name, email, and phone are required for a new user.");
        }
        $stmt_check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->execute([$data['email']]);
        if ($stmt_check->fetch()) {
            throw new Exception("A user with this email already exists. Please use the 'Existing User' option.");
        }
        
        $user_name = htmlspecialchars(strip_tags(trim($data['full_name'])));
        $user_email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        if (!$user_email) throw new Exception("Invalid email format for new user.");

        $new_password = bin2hex(random_bytes(4)); // 8-char random password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $user_query = "INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)";
        $stmt_user = $db->prepare($user_query);
        $stmt_user->execute([$user_name, $user_email, $data['phone'], $password_hash]);
        $user_id = $db->lastInsertId();

        // Email new password to the user
        $mailer = new Mailer();
        $subject = "Your New MemberSync Account";
        $body = "Welcome, {$user_name}!<br><br>An account has been created for you. Your temporary password is: <b>{$new_password}</b><br><br>Please log in and change your password at your earliest convenience.";
        $mailer->send($user_email, $user_name, $subject, $body);

    } elseif ($user_id) {
        $stmt_user = $db->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if (!$user) throw new Exception("Selected existing user not found.");
        $user_name = $user['full_name'];
        $user_email = $user['email'];
    } else {
        throw new Exception("No user was selected or created.");
    }

    // --- NEW: Check for existing active/pending membership ---
    $stmt_sub_check = $db->prepare("SELECT id FROM membership_subscriptions WHERE user_id = ? AND status IN ('active', 'pending')");
    $stmt_sub_check->execute([$user_id]);
    if ($stmt_sub_check->fetch()) {
        throw new Exception("This user already has an active or pending membership. Please edit the existing one instead of creating a duplicate.");
    }
    // --- END NEW CHECK ---
    
    // --- 3. MEMBERSHIP & PAYMENT LOGIC ---
    $stmt_type = $db->prepare("SELECT fee, renewal_month, name as type_name FROM membership_types WHERE id = ?");
    $stmt_type->execute([$data['membership_type_id']]);
    $type = $stmt_type->fetch(PDO::FETCH_ASSOC);
    if (!$type) throw new Exception("Invalid membership type.");
    $membership_fee = (float)$type['fee'];

    $balance_due = $membership_fee - $amount_paid;
    $status = ($balance_due <= 0) ? 'active' : 'pending';

    $start_date = new DateTime($data['start_date']);
    $end_date = (clone $start_date)->modify('+' . $type['renewal_month'] . ' months');
    $card_number = 'MS-' . $user_id . '-' . time();
    $invoice_id = null;
    $attachments = [];
    $pdfGenerator = new PdfGenerator();

    // --- 4. HANDLE INVOICE ---
    $issue_invoice = isset($data['issue_invoice']) && $data['issue_invoice'] == 'on';
    if ($issue_invoice) {
        $due_date = (new DateTime())->modify('+30 days');
        $inv_query = "INSERT INTO invoices (user_id, related_type, total_amount, paid_amount, balance_due, status, due_date)
                      VALUES (?, 'membership', ?, ?, ?, ?, ?)";
        $stmt_inv = $db->prepare($inv_query);
        $stmt_inv->execute([$user_id, $membership_fee, $amount_paid, $balance_due, ($balance_due <= 0 ? 'paid' : 'unpaid'), $due_date->format('Y-m-d')]);
        $invoice_id = $db->lastInsertId();
    }
    
    // --- 5. CREATE SUBSCRIPTION (Now with invoice_id) ---
    $sub_query = "INSERT INTO membership_subscriptions (user_id, membership_type_id, start_date, end_date, membership_card_number, status, balance_due, invoice_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_sub = $db->prepare($sub_query);
    $stmt_sub->execute([$user_id, $data['membership_type_id'], $start_date->format('Y-m-d'), $end_date->format('Y-m-d'), $card_number, $status, $balance_due, $invoice_id]);
    $subscription_id = $db->lastInsertId();

    if ($invoice_id) {
        $db->prepare("UPDATE invoices SET related_id = ? WHERE id = ?")->execute([$subscription_id, $invoice_id]);
    }

    // --- 6. HANDLE PAYMENT, RECEIPT & EMAILS ---
    $issue_receipt = isset($data['issue_receipt']) && $data['issue_receipt'] == 'on';

    if ($amount_paid > 0) {
        $meta = ['proof_of_payment' => null];
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(__DIR__, 2) . '/uploads/proofs/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'proof_' . $subscription_id . '_' . uniqid() . '_' . basename($_FILES['payment_proof']['name']);
            $proof_of_payment_path = $upload_dir . $filename;
            if(move_uploaded_file($_FILES['payment_proof']['tmp_name'], $proof_of_payment_path)) {
                $meta['proof_of_payment'] = '/uploads/proofs/' . $filename;
            }
        }

        $pay_query = "INSERT INTO payments (user_id, payment_type, reference_id, amount, method, status, meta) VALUES (?, 'membership', ?, ?, ?, 'completed', ?)";
        $stmt_pay = $db->prepare($pay_query);
        $stmt_pay->execute([$user_id, $subscription_id, $amount_paid, $data['payment_method'], json_encode($meta)]);
        $payment_id = $db->lastInsertId();

        if ($issue_receipt) {
            $receipt_num = 'RCPT-MEM-' . date('Ymd') . '-' . $payment_id;
            $rec_query = "INSERT INTO receipts (payment_id, receipt_number) VALUES (?, ?)";
            $stmt_rec = $db->prepare($rec_query);
            $stmt_rec->execute([$payment_id, $receipt_num]);
            
            $receipt_data = ['receipt_num' => $receipt_num, 'user_name' => $user_name, 'amount_paid' => $amount_paid, 'membership_type' => $type['type_name']];
            $receipt_pdf_content = $pdfGenerator->generateMembershipReceiptPdf($receipt_data); 
            $attachments[] = ['content' => $receipt_pdf_content, 'filename' => $receipt_num . '.pdf'];
        }
    }
    
    if ($issue_invoice && $invoice_id) {
        $invoice_data = [
            'invoice_id' => $invoice_id, 
            'user_name' => $user_name, 
            'membership_type' => $type['type_name'],
            'issue_date' => date('Y-m-d'),
            'due_date' => $due_date->format('Y-m-d'),
            'total_amount' => $membership_fee,
            'paid_amount' => $amount_paid,
            'balance_due' => $balance_due
        ];
        $invoice_pdf_content = $pdfGenerator->generateMembershipInvoicePdf($invoice_data);
        $attachments[] = ['content' => $invoice_pdf_content, 'filename' => 'Invoice-'.$invoice_id.'.pdf'];
    }

    if (!empty($attachments)) {
        $mailer = new Mailer();
        $mailer->send($user_email, $user_name, "Your Membership Documents", "Please find your membership documents attached.", $attachments);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Member subscription created successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}