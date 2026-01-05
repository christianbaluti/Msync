<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/mailer.php';
require_once dirname(__DIR__, 2) . '/core/PdfGenerator.php';

// --- Basic Validation ---
if (empty($_FILES['user_csv']) || $_FILES['user_csv']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'CSV file upload failed or is missing.']);
    exit();
}
if (empty($_POST['event_id']) || empty($_POST['ticket_type_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required event or ticket type fields.']);
    exit();
}

$db = (new Database())->connect();
$db->beginTransaction();

try {
    // --- Get Common Data from Form ---
    $event_id = (int)$_POST['event_id'];
    $ticket_type_id = (int)$_POST['ticket_type_id'];
    $attending_as_id = (int)$_POST['attending_as_id'];
    $is_employed_flag = isset($_POST['is_employed_csv']) && $_POST['is_employed_csv'] == '1' ? 1 : 0;
    $company_id = $is_employed_flag && !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;
    $price = (float)$_POST['price'];
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $action = $_POST['action'] ?? 'record_only';

    // --- Read and Parse CSV ---
    $csvFile = $_FILES['user_csv']['tmp_name'];
    $file_handle = fopen($csvFile, 'r');
    fgetcsv($file_handle, 1024, ','); // Skip header row

    $rows = [];
    $emails_to_check = [];
    while (($row = fgetcsv($file_handle, 1024, ',')) !== FALSE) {
        // Basic validation for row data
        if (empty($row[0]) || empty($row[1]) || !filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("CSV contains invalid data. Ensure all rows have a name and a valid email.");
        }
        $rows[] = $row;
        $emails_to_check[] = $row[1]; // Column 2 (index 1) is the email
    }
    fclose($file_handle);

    if (empty($rows)) {
        throw new Exception("The uploaded CSV file is empty or improperly formatted.");
    }

    // --- Validation Pass: Check for any existing users ---
    $placeholders = implode(',', array_fill(0, count($emails_to_check), '?'));
    $stmt = $db->prepare("SELECT email FROM users WHERE email IN ($placeholders)");
    $stmt->execute($emails_to_check);
    $existing_emails = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    if (!empty($existing_emails)) {
        throw new Exception("Upload failed. The following users already exist in the system: " . implode(', ', $existing_emails));
    }

    // --- Creation Pass: If validation is successful, create users and tickets ---
    $mailer = new Mailer();
    $pdfGenerator = new PdfGenerator();
    $event_title = $db->query("SELECT title FROM events WHERE id = " . $event_id)->fetchColumn();

    foreach ($rows as $row) {
        $user_full_name = $row[0];
        $user_email = $row[1];
        $user_phone = $row[2] ?? null;

        // 1. INSERT into 'users' table
        $raw_password = bin2hex(random_bytes(6));
        $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
        $user_query = "INSERT INTO users (full_name, email, phone, password_hash, is_employed, company_id, role) VALUES (?, ?, ?, ?, ?, ?, 'user')";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([$user_full_name, $user_email, $user_phone, $password_hash, $is_employed_flag, $company_id]);
        $user_id = $db->lastInsertId();

        // 2. Send Welcome Email
        $welcome_subject = "Your New Account & Ticket for " . $event_title;
        $welcome_body = "<p>Hello " . htmlspecialchars($user_full_name) . ",</p>" .
                        "<p>An account has been created for you to manage your ticket for the event: <strong>" . htmlspecialchars($event_title) . "</strong>.</p>" .
                        "<p>You can log in using your email address and the following temporary password:</p>" .
                        "<p><strong>Password:</strong> " . $raw_password . "</p>" .
                        "<p>We recommend you change this password after your first login.</p><p>Thank you!</p>";
        $mailer->send($user_email, $user_full_name, $welcome_subject, $welcome_body);

        // 3. INSERT into 'event_tickets' table
        $balance_due = $price - $amount_paid;
        $status = ($balance_due <= 0) ? 'bought' : 'pending';
        $ticket_code = 'EVT' . $event_id . '-TKT' . strtoupper(bin2hex(random_bytes(4)));

        $ticket_stmt = $db->prepare("INSERT INTO event_tickets (event_id, ticket_type_id, user_id, attending_as_id, status, price, balance_due, ticket_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ticket_stmt->execute([$event_id, $ticket_type_id, $user_id, $attending_as_id, $status, $price, $balance_due, $ticket_code]);
        $ticket_id = $db->lastInsertId();
        
        // 4. Handle Financials, PDFs, and Confirmation Email
        $email_body_parts = ["<p>Your ticket (Code: <b>" . htmlspecialchars($ticket_code) . "</b>) has been successfully recorded. Please find it attached.</p>"];
        $attachments = [];

        $ticket_pdf_data = ['user_name' => $user_full_name, 'event_title' => $event_title, 'ticket_code' => $ticket_code, 'price' => $price, 'status' => $status];
        $attachments[] = ['content' => $pdfGenerator->generateTicketPdf($ticket_pdf_data), 'filename' => 'Ticket-' . $ticket_code . '.pdf'];

        if ($action === 'process_payment' && $amount_paid > 0) {
            $pay_stmt = $db->prepare("INSERT INTO payments (user_id, payment_type, reference_id, amount, method, status) VALUES (?, 'event_ticket', ?, ?, ?, 'completed')");
            $pay_stmt->execute([$user_id, $ticket_id, $amount_paid, $payment_method]);
            $payment_id = $db->lastInsertId();

            $timezone = new DateTimeZone('Africa/Blantyre');
            $date = new DateTime('now', $timezone);
            $receipt_num = 'RCPT-' . $date->format('Ymd') . '-' . $payment_id;
            
            $rec_stmt = $db->prepare("INSERT INTO receipts (payment_id, receipt_number) VALUES (?, ?)");
            $rec_stmt->execute([$payment_id, $receipt_num]);

            $email_body_parts[] = "<p>Thank you for your payment of <b>MWK " . number_format($amount_paid, 2) . "</b>. Your receipt is attached.</p>";
            $receipt_pdf_data = ['receipt_num' => $receipt_num, 'event_title' => $event_title, 'user_name' => $user_full_name, 'amount_paid' => $amount_paid];
            $attachments[] = ['content' => $pdfGenerator->generateReceiptPdf($receipt_pdf_data), 'filename' => 'Receipt-' . $receipt_num . '.pdf'];
        }

        $email_body_parts[] = "<p>Your ticket status is now <b>" . ucfirst($status) . "</b>.</p>";
        
        // 5. Send Confirmation Email
        $confirm_subject = "Your Ticket Confirmation for " . htmlspecialchars($event_title);
        $final_body = "<p>Hello " . htmlspecialchars($user_full_name) . ",</p>" . implode('', $email_body_parts) . "<p>Thank you.</p>";
        $mailer->send($user_email, $user_full_name, $confirm_subject, $final_body, $attachments);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Successfully created tickets for ' . count($rows) . ' new users.']);

} catch (Exception $e) {
    $db->rollBack();
    // Use 400 for validation errors (like duplicates), 500 for other server errors
    $http_code = (strpos($e->getMessage(), 'already exist') !== false || strpos($e->getMessage(), 'invalid data') !== false) ? 400 : 500;
    http_response_code($http_code);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}