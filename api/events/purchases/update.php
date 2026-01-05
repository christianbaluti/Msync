<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';
require_once dirname(__DIR__, 2) . '/core/mailer.php';
require_once dirname(__DIR__, 2) . '/core/PdfGenerator.php';

$data = json_decode(file_get_contents("php://input"));
if (empty($data->ticket_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ticket ID is required.']);
    exit();
}

$db = (new Database())->connect();
$db->beginTransaction();

try {
    $ticket_query = "SELECT et.*, u.full_name as user_name, u.email as user_email, e.title as event_title
                     FROM event_tickets et
                     LEFT JOIN users u ON et.user_id = u.id
                     LEFT JOIN events e ON et.event_id = e.id
                     WHERE et.id = :id";
    $ticket_stmt = $db->prepare($ticket_query);
    $ticket_stmt->execute([':id' => $data->ticket_id]);
    $ticket = $ticket_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) throw new Exception('Ticket not found.');

    $current_balance = (float)$ticket['balance_due'];
    $original_status = $ticket['status'];
    $amount_paid = isset($data->payment_amount) ? (float)$data->payment_amount : 0;
    $action = $data->action ?? 'save_changes';
    
    $email_body_parts = [];
    $attachments = [];
    $pdfGenerator = new PdfGenerator();

    if ($amount_paid > 0) {
        if ($amount_paid > $current_balance) throw new Exception('Payment amount cannot be greater than the balance due.');
        
        $pay_stmt = $db->prepare("INSERT INTO payments (user_id, payment_type, reference_id, amount, method, status) VALUES (?, 'event_ticket', ?, ?, ?, 'completed')");
        $pay_stmt->execute([$ticket['user_id'], $ticket['id'], $amount_paid, $data->payment_method]);
        $payment_id = $db->lastInsertId();

        $timezone = new DateTimeZone('Africa/Blantyre');
        $date = new DateTime('now', $timezone);
        $receipt_num = 'RCPT-' . $date->format('Ymd') . '-' . $payment_id;
        
        $rec_stmt = $db->prepare("INSERT INTO receipts (payment_id, receipt_number) VALUES (?, ?)");
        $rec_stmt->execute([$payment_id, $receipt_num]);

        $current_balance -= $amount_paid;
        
        $email_body_parts[] = "<p>Thank you for your payment of <b>MWK " . number_format($amount_paid, 2) . "</b>. Your receipt is attached.</p>";

        $receipt_pdf_data = ['receipt_num' => $receipt_num, 'event_title' => $ticket['event_title'], 'user_name' => $ticket['user_name'], 'amount_paid' => $amount_paid];
        $receipt_pdf_content = $pdfGenerator->generateReceiptPdf($receipt_pdf_data);
        $attachments[] = ['content' => $receipt_pdf_content, 'filename' => 'Receipt-' . $receipt_num . '.pdf'];
    }

    switch ($action) {
        case 'issue_invoice':
            if ($current_balance > 0) {
                $inv_stmt = $db->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, paid_amount, balance_due, status) VALUES (?, 'event_ticket', ?, ?, ?, ?, 'unpaid')");
                $paid_amount_so_far = (float)$ticket['price'] - $current_balance;
                $inv_stmt->execute([$ticket['user_id'], $ticket['id'], $ticket['price'], $paid_amount_so_far, $current_balance]);
                $invoice_id = $db->lastInsertId();
                
                $email_body_parts[] = "<p>An invoice (#" . $invoice_id . ") for the outstanding balance of <b>MWK " . number_format($current_balance, 2) . "</b> has been issued and is attached.</p>";

                $invoice_pdf_data = ['invoice_id' => $invoice_id, 'user_name' => $ticket['user_name'], 'event_title' => $ticket['event_title'], 'total_amount' => $ticket['price'], 'paid_amount' => $paid_amount_so_far, 'balance_due' => $current_balance];
                $invoice_pdf_content = $pdfGenerator->generateInvoicePdf($invoice_pdf_data);
                $attachments[] = ['content' => $invoice_pdf_content, 'filename' => 'Invoice-' . $invoice_id . '.pdf'];
            }
            break;
    }

    $new_status = $data->status ?? $original_status;
    if ($current_balance <= 0 && $new_status !== 'verified') {
        $new_status = 'bought';
    }
    
    if ($new_status !== $original_status) {
        $email_body_parts[] = "<p>The status of your ticket has been updated from <b>" . ucfirst($original_status) . "</b> to <b>" . ucfirst($new_status) . "</b>.</p>";
    }

    $update_ticket_stmt = $db->prepare("UPDATE event_tickets SET status = :status, balance_due = :balance_due WHERE id = :id");
    $update_ticket_stmt->execute([':status' => $new_status, ':balance_due' => $current_balance, ':id' => $data->ticket_id]);

    if (!empty($email_body_parts)) {
        $mailer = new Mailer();
        $subject = "Update on your ticket for " . htmlspecialchars($ticket['event_title']);
        
        $email_header = "<p>Hello " . htmlspecialchars($ticket['user_name']) . ",</p><p>Please see below for updates regarding your ticket for the event: <b>" . htmlspecialchars($ticket['event_title']) . "</b>.</p>";
        $email_footer = "<p>If you have any questions, please contact the event organizers.</p><p>Thank you.</p>";
        
        $final_body = $email_header . implode('', $email_body_parts) . $email_footer;

        $mailer->send($ticket['user_email'], $ticket['user_name'], $subject, $final_body, $attachments);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Ticket updated successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}