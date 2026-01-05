<?php
// server/api/events/create_pending_ticket.php
// Creates pending event ticket, invoice, and payment records BEFORE Malipo payment

// ---------------------------------------------------------------------
// Logging Setup
// ---------------------------------------------------------------------
$logPath = __DIR__ . '/../../../../public/error.log';

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logPath);

function log_entry($level, $message) {
    global $logPath;
    $time = date('Y-m-d H:i:s');
    $entry = "[$time] create_pending_ticket.php $level: $message\n";
    @error_log($entry, 3, $logPath);
}

// ---------------------------------------------------------------------
// CORS + Headers
// ---------------------------------------------------------------------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware_for_all.php';

try {
    // ---------------------------------------------------------------------
    // Parse Input
    // ---------------------------------------------------------------------
    $rawInput = file_get_contents('php://input');
    $dataObj = json_decode($rawInput);
    $data = json_decode($rawInput, true);
    
    // ---------------------------------------------------------------------
    // Authenticate user
    // ---------------------------------------------------------------------
    $auth_data = get_auth_user($dataObj);
    $userId = $auth_data->user_id;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => "Unauthorized"]);
        exit;
    }
    
    // ---------------------------------------------------------------------
    // Validate required fields
    // ---------------------------------------------------------------------
    $ticketTypeId = $data['ticket_type_id'] ?? null;
    $eventId = $data['event_id'] ?? null;
    $orderId = $data['order_id'] ?? null;
    
    if (!$ticketTypeId || !$eventId || !$orderId) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Missing required fields (ticket_type_id, event_id, order_id)']);
        exit;
    }
    
    log_entry("INFO", "Creating pending ticket: user=$userId event=$eventId ticketType=$ticketTypeId order=$orderId");
    
    // ---------------------------------------------------------------------
    // Get Ticket Type Details
    // ---------------------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT id, name, price, event_id 
        FROM event_ticket_types 
        WHERE id = ? AND event_id = ?
    ");
    $stmt->execute([$ticketTypeId, $eventId]);
    $ticketType = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticketType) {
        http_response_code(404);
        echo json_encode(['error' => true, 'message' => 'Ticket type not found for this event']);
        exit;
    }
    
    $price = (float)$ticketType['price'];
    $ticketTypeName = $ticketType['name'];
    
    // ---------------------------------------------------------------------
    // Check for Existing Pending Ticket (prevent duplicates)
    // ---------------------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT id FROM event_tickets 
        WHERE user_id = ? AND event_id = ? AND ticket_type_id = ? AND status = 'pending'
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$userId, $eventId, $ticketTypeId]);
    $existingTicket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingTicket) {
        log_entry("INFO", "Found existing pending ticket: " . $existingTicket['id']);
        echo json_encode([
            'error' => false,
            'message' => 'Pending ticket already exists',
            'ticket_id' => (int)$existingTicket['id'],
        ]);
        exit;
    }
    
    // ---------------------------------------------------------------------
    // Create Records in Transaction
    // ---------------------------------------------------------------------
    $pdo->beginTransaction();
    
    try {
        // 1. Create pending event_ticket
        // Match admin format: EVT{event_id}-TKT{random_hex}
        $ticketCode = 'EVT' . $eventId . '-TKT' . strtoupper(bin2hex(random_bytes(4)));
        
        $stmt = $pdo->prepare("
            INSERT INTO event_tickets 
                (ticket_type_id, event_id, user_id, status, price, balance_due, ticket_code, purchased_at)
            VALUES 
                (?, ?, ?, 'pending', ?, ?, ?, NOW())
        ");
        $stmt->execute([$ticketTypeId, $eventId, $userId, $price, $price, $ticketCode]);
        $ticketId = $pdo->lastInsertId();
        
        log_entry("INFO", "Created pending ticket ID: $ticketId with code: $ticketCode");
        
        // 2. Create pending invoice
        $stmt = $pdo->prepare("
            INSERT INTO invoices 
                (user_id, related_type, related_id, total_amount, paid_amount, balance_due, status, issued_at, meta)
            VALUES 
                (?, 'event_ticket', ?, ?, 0.00, ?, 'unpaid', NOW(), ?)
        ");
        $invoiceMeta = json_encode(['order_id' => $orderId, 'ticket_type' => $ticketTypeName]);
        $stmt->execute([$userId, $ticketId, $price, $price, $invoiceMeta]);
        $invoiceId = $pdo->lastInsertId();
        
        log_entry("INFO", "Created pending invoice ID: $invoiceId");
        
        // 3. Create pending payment record
        $stmt = $pdo->prepare("
            INSERT INTO payments 
                (user_id, payment_type, reference_id, amount, method, status, gateway_transaction_id, transaction_date)
            VALUES 
                (?, 'event_ticket', ?, ?, 'gateway', 'pending', ?, NOW())
        ");
        $stmt->execute([$userId, $ticketId, $price, $orderId]);
        $paymentId = $pdo->lastInsertId();
        
        log_entry("INFO", "Created pending payment ID: $paymentId with gateway_transaction_id: $orderId");
        
        $pdo->commit();
        
        echo json_encode([
            'error' => false,
            'message' => 'Pending ticket created successfully',
            'ticket_id' => (int)$ticketId,
            'invoice_id' => (int)$invoiceId,
            'payment_id' => (int)$paymentId,
            'ticket_code' => $ticketCode,
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    log_entry("ERROR", "Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Failed to create pending ticket: ' . $e->getMessage()]);
}