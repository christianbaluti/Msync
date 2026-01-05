<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/mailer.php';
require_once dirname(__DIR__, 2) . '/core/PdfGenerator.php';

$data = json_decode(file_get_contents("php://input"));
// Basic validation
if (empty($data->event_id) || empty($data->ticket_type_id) || empty($data->user_ids) || !is_array($data->user_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

$db = (new Database())->connect();
$db->beginTransaction();

try {
    $mailer = new Mailer();
    $pdfGenerator = new PdfGenerator();
    $processed_count = 0;
    $skipped_users = [];

    // Get event details once
    $event_title = $db->query("SELECT title FROM events WHERE id = " . (int)$data->event_id)->fetchColumn();
    
    foreach ($data->user_ids as $user_id) {
        // 1. Check if user already has a ticket
        $check_stmt = $db->prepare("SELECT u.full_name FROM event_tickets et JOIN users u ON et.user_id = u.id WHERE et.event_id = ? AND et.user_id = ?");
        $check_stmt->execute([$data->event_id, $user_id]);
        if ($existing_ticket = $check_stmt->fetch(PDO::FETCH_ASSOC)) {
            $skipped_users[] = $existing_ticket['full_name'];
            continue; // Skip to the next user
        }

        // 2. Get user details for email
        $user_stmt = $db->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) continue;

        // 3. Create ticket
        $price = (float)$data->price;
        $amount_paid = (float)($data->amount_paid ?? 0);
        $balance_due = $price - $amount_paid;
        $status = ($balance_due <= 0) ? 'bought' : 'pending';
        $ticket_code = 'EVT' . $data->event_id . '-TKT' . strtoupper(bin2hex(random_bytes(4)));

        $ticket_stmt = $db->prepare("INSERT INTO event_tickets (event_id, ticket_type_id, user_id, attending_as_id, status, price, balance_due, ticket_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ticket_stmt->execute([$data->event_id, $data->ticket_type_id, $user_id, $data->attending_as_id, $status, $price, $balance_due, $ticket_code]);
        $ticket_id = $db->lastInsertId();

        // 4. Handle financials, PDF generation, and Email
        $email_body_parts = ["<p>A ticket (Code: <b>" . htmlspecialchars($ticket_code) . "</b>) has been purchased for you. Please find it attached.</p>"];
        $attachments = [];
        
        $ticket_pdf_data = ['user_name' => $user['full_name'], 'event_title' => $event_title, 'ticket_code' => $ticket_code, 'price' => $price, 'status' => $status];
        $attachments[] = ['content' => $pdfGenerator->generateTicketPdf($ticket_pdf_data), 'filename' => 'Ticket-' . $ticket_code . '.pdf'];

        // --- START of completed logic ---
        if ($amount_paid > 0) {
            $pay_stmt = $db->prepare("INSERT INTO payments (user_id, payment_type, reference_id, amount, method, status) VALUES (?, 'event_ticket', ?, ?, ?, 'completed')");
            $pay_stmt->execute([$user_id, $ticket_id, $amount_paid, $data->payment_method]);
            $payment_id = $db->lastInsertId();

            $timezone = new DateTimeZone('Africa/Blantyre');
            $date = new DateTime('now', $timezone);
            $receipt_num = 'RCPT-' . $date->format('Ymd') . '-' . $payment_id;

            $rec_stmt = $db->prepare("INSERT INTO receipts (payment_id, receipt_number) VALUES (?, ?)");
            $rec_stmt->execute([$payment_id, $receipt_num]);

            $email_body_parts[] = "<p>A payment of <b>MWK " . number_format($amount_paid, 2) . "</b> has been recorded. Your receipt is attached.</p>";

            $receipt_pdf_data = [
                'receipt_num' => $receipt_num,
                'event_title' => $event_title,
                'user_name' => $user['full_name'],
                'amount_paid' => $amount_paid
            ];
            $receipt_pdf_content = $pdfGenerator->generateReceiptPdf($receipt_pdf_data);
            $attachments[] = ['content' => $receipt_pdf_content, 'filename' => 'Receipt-' . $receipt_num . '.pdf'];
        }
        // --- END of completed logic ---
        
        $email_body_parts[] = "<p>Your ticket status is now <b>" . ucfirst($status) . "</b>.</p>";

        // 5. Send email
        $subject = "Your Ticket Confirmation for " . htmlspecialchars($event_title);
        $final_body = "<p>Hello " . htmlspecialchars($user['full_name']) . ",</p>" . implode('', $email_body_parts) . "<p>Thank you.</p>";
        $mailer->send($user['email'], $user['full_name'], $subject, $final_body, $attachments);

        $processed_count++;
    }

    $db->commit();
    $message = "Successfully created tickets for {$processed_count} users.";
    if (!empty($skipped_users)) {
        $message .= " Skipped " . count($skipped_users) . " users who already had tickets: " . implode(', ', $skipped_users);
    }
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}