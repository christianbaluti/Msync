<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/mailer.php';
require_once dirname(__DIR__, 2) . '/core/PdfGenerator.php';

// Get Input Data & Validate
$data = json_decode(file_get_contents("php://input"));
if (empty($data->event_id) || empty($data->ticket_type_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

$db = (new Database())->connect();
$db->beginTransaction();

try {
    $user_id = $data->user_id ?? null;

    // Step 1: Create a new user if necessary
    if ($data->user_mode === 'new' && !empty($data->user_email)) {
        $check_stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $check_stmt->execute([':email' => $data->user_email]);
        if ($check_stmt->fetch()) {
            throw new Exception('A user with this email already exists.');
        }

        $company_id = (!empty($data->is_employed) && $data->is_employed == '1' && !empty($data->company_id)) ? $data->company_id : null;
        $is_employed_flag = (!empty($data->is_employed) && $data->is_employed == '1') ? 1 : 0;
        
        $raw_password = bin2hex(random_bytes(6));
        $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);

        $user_query = "INSERT INTO users (full_name, email, phone, password_hash, is_employed, company_id, role) 
                       VALUES (:name, :email, :phone, :pass, :is_employed, :company_id, 'user')";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([
            ':name' => $data->user_name,
            ':email' => $data->user_email,
            ':phone' => $data->user_phone,
            ':pass' => $password_hash,
            ':is_employed' => $is_employed_flag,
            ':company_id' => $company_id
        ]);
        $user_id = $db->lastInsertId();

        // Send the separate "Welcome" email (no attachments needed here)
        $mailer = new Mailer();
        $event_name_for_welcome = $db->query("SELECT title FROM events WHERE id = " . (int)$data->event_id)->fetchColumn();
        $subject = "Your New Account & Ticket for " . $event_name_for_welcome;
        $body = "<p>Hello " . htmlspecialchars($data->user_name) . ",</p>" .
                "<p>An account has been created for you to manage your ticket for the event: <strong>" . htmlspecialchars($event_name_for_welcome) . "</strong>.</p>" .
                "<p>You can log in using your email address and the following temporary password:</p>" .
                "<p><strong>Password:</strong> " . $raw_password . "</p>" .
                "<p>We recommend you change this password after your first login.</p><p>Thank you!</p>";
        $mailer->send($data->user_email, $data->user_name, $subject, $body);
    }

    if (!$user_id) throw new Exception('A user must be selected or created.');

    $check_ticket_stmt = $db->prepare("SELECT id FROM event_tickets WHERE event_id = :event_id AND user_id = :user_id");
    $check_ticket_stmt->execute([':event_id' => $data->event_id, ':user_id' => $user_id]);
    if ($check_ticket_stmt->fetch()) {
        throw new Exception('This user already has a ticket for this event.');
    }

    $details_stmt = $db->prepare(
        "SELECT u.full_name, u.email, e.title as event_title
         FROM users u, events e
         WHERE u.id = :user_id AND e.id = :event_id"
    );
    $details_stmt->execute([':user_id' => $user_id, ':event_id' => $data->event_id]);
    $details = $details_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Step 2: Create the Event Ticket
    $price = (float)$data->price;
    $amount_paid = (float)($data->amount_paid ?? 0);
    $balance_due = $price - $amount_paid;
    $status = ($balance_due <= 0) ? 'bought' : 'pending';
    $ticket_code = 'EVT' . $data->event_id . '-TKT' . strtoupper(bin2hex(random_bytes(4)));

    $ticket_stmt = $db->prepare("INSERT INTO event_tickets (event_id, ticket_type_id, user_id, attending_as_id, status, price, balance_due, ticket_code) VALUES (:event_id, :ticket_type_id, :user_id, :attending_as_id, :status, :price, :balance_due, :ticket_code)");
    $ticket_stmt->execute([':event_id' => $data->event_id, ':ticket_type_id' => $data->ticket_type_id, ':user_id' => $user_id, ':attending_as_id' => $data->attending_as_id, ':status' => $status, ':price' => $price, ':balance_due' => $balance_due, ':ticket_code' => $ticket_code]);
    $ticket_id = $db->lastInsertId();

    // Step 3: Handle Financials, PDF Generation, and Email
    $email_body_parts = [];
    $attachments = [];
    $pdfGenerator = new PdfGenerator();

    $email_body_parts[] = "<p>Your ticket (Code: <b>" . htmlspecialchars($ticket_code) . "</b>) for the event has been successfully recorded. Please find it attached.</p>";

    // Always generate and attach the ticket PDF
    $ticket_pdf_data = [
        'user_name' => $details['full_name'],
        'event_title' => $details['event_title'],
        'ticket_code' => $ticket_code,
        'price' => $price,
        'status' => $status
    ];
    $ticket_pdf_content = $pdfGenerator->generateTicketPdf($ticket_pdf_data);
    $attachments[] = ['content' => $ticket_pdf_content, 'filename' => 'Ticket-' . $ticket_code . '.pdf'];

    if ($data->action === 'process_payment' && $amount_paid > 0) {
        $pay_stmt = $db->prepare("INSERT INTO payments (user_id, payment_type, reference_id, amount, method, status) VALUES (?, 'event_ticket', ?, ?, ?, 'completed')");
        $pay_stmt->execute([$user_id, $ticket_id, $amount_paid, $data->payment_method]);
        $payment_id = $db->lastInsertId();

        $timezone = new DateTimeZone('Africa/Blantyre');
        $date = new DateTime('now', $timezone);
        $receipt_num = 'RCPT-' . $date->format('Ymd') . '-' . $payment_id;
        
        $rec_stmt = $db->prepare("INSERT INTO receipts (payment_id, receipt_number) VALUES (?, ?)");
        $rec_stmt->execute([$payment_id, $receipt_num]);
        
        $email_body_parts[] = "<p>Thank you for your payment of <b>MWK " . number_format($amount_paid, 2) . "</b>. Your receipt is attached.</p>";

        $receipt_pdf_data = ['receipt_num' => $receipt_num, 'event_title' => $details['event_title'], 'user_name' => $details['full_name'], 'amount_paid' => $amount_paid];
        $receipt_pdf_content = $pdfGenerator->generateReceiptPdf($receipt_pdf_data);
        $attachments[] = ['content' => $receipt_pdf_content, 'filename' => 'Receipt-' . $receipt_num . '.pdf'];

    } elseif ($data->action === 'issue_invoice') {
        $inv_stmt = $db->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, paid_amount, balance_due, status) VALUES (?, 'event_ticket', ?, ?, ?, ?, 'unpaid')");
        $inv_stmt->execute([$user_id, $ticket_id, $price, $amount_paid, $balance_due]);
        $invoice_id = $db->lastInsertId();

        $email_body_parts[] = "<p>An invoice (#" . $invoice_id . ") for the outstanding balance of <b>MWK " . number_format($balance_due, 2) . "</b> has been issued and is attached.</p>";

        $invoice_pdf_data = ['invoice_id' => $invoice_id, 'user_name' => $details['full_name'], 'event_title' => $details['event_title'], 'total_amount' => $price, 'paid_amount' => $amount_paid, 'balance_due' => $balance_due];
        $invoice_pdf_content = $pdfGenerator->generateInvoicePdf($invoice_pdf_data);
        $attachments[] = ['content' => $invoice_pdf_content, 'filename' => 'Invoice-' . $invoice_id . '.pdf'];
    }
    
    $email_body_parts[] = "<p>Your ticket status is now <b>" . ucfirst($status) . "</b>.</p>";

    // Step 4: Send the ticket confirmation email with attachments
    $mailer = new Mailer();
    $subject = "Your Ticket Confirmation for " . htmlspecialchars($details['event_title']);
    
    $email_header = "<p>Hello " . htmlspecialchars($details['full_name']) . ",</p>";
    $email_footer = "<p>We look forward to seeing you at the event.</p><p>Thank you.</p>";
    
    $final_body = $email_header . implode('', $email_body_parts) . $email_footer;
    $mailer->send($details['email'], $details['full_name'], $subject, $final_body, $attachments);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Ticket purchase processed successfully. A confirmation email has been sent.']);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}