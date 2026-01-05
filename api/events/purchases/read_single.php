<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($ticket_id === 0) {
    echo json_encode(['error' => 'Invalid Ticket ID']);
    exit();
}

$db = (new Database())->connect();
$response = [];

// 1. Get Main Ticket Info
$ticket_query = "SELECT et.*, u.full_name as user_name, u.email as user_email, ett.name as ticket_type_name, aat.name as attendee_type_name 
                 FROM event_tickets et 
                 LEFT JOIN users u ON et.user_id = u.id
                 LEFT JOIN event_ticket_types ett ON et.ticket_type_id = ett.id
                 LEFT JOIN attending_as_types aat ON et.attending_as_id = aat.id
                 WHERE et.id = :id";
$stmt = $db->prepare($ticket_query);
$stmt->execute([':id' => $ticket_id]);
$response['ticket'] = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$response['ticket']) {
    echo json_encode(['error' => 'Ticket not found']);
    exit();
}

// 2. Get Related Payments
$payments_stmt = $db->prepare("SELECT * FROM payments WHERE payment_type = 'event_ticket' AND reference_id = :id ORDER BY transaction_date DESC");
$payments_stmt->execute([':id' => $ticket_id]);
$response['payments'] = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Get Related Invoices
// FIX: Changed 'created_at' to 'issued_at' to match your database schema.
$invoices_stmt = $db->prepare("SELECT * FROM invoices WHERE related_type = 'event_ticket' AND related_id = :id ORDER BY issued_at DESC");
$invoices_stmt->execute([':id' => $ticket_id]);
$response['invoices'] = $invoices_stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Get Related Receipts (via payments)
$receipts = [];
if (!empty($response['payments'])) {
    $payment_ids = array_column($response['payments'], 'id');
    
    if (!empty($payment_ids)) {
        $placeholders = implode(',', array_fill(0, count($payment_ids), '?'));
        
        $receipt_stmt = $db->prepare("SELECT * FROM receipts WHERE payment_id IN ($placeholders)");
        $receipt_stmt->execute($payment_ids);
        $all_receipts = $receipt_stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['receipts'] = $all_receipts;
    } else {
        $response['receipts'] = [];
    }
} else {
    $response['receipts'] = [];
}

echo json_encode($response);