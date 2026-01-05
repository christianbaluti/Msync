<?php
// server/api/malipo/callback_marketplace.php
// Endpoint that MALIPO can call to update payment status for an invoice/order

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';

try {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid payload']);
        exit;
    }

    // Expect fields per MALIPO docs; use placeholders here.
    $reference = $payload['reference'] ?? null; // e.g., our invoice reference
    $status = $payload['status'] ?? null; // e.g., 'success' | 'failed' | 'pending'
    $amount = isset($payload['amount']) ? (float)$payload['amount'] : null;

    if (!$reference || !$status) {
        http_response_code(422);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }

    // Find invoice by reference
    $stmt = $pdo->prepare("SELECT id, status FROM invoices WHERE CONCAT('INV-', id) = ?");
    $stmt->execute([$reference]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$invoice) {
        http_response_code(404);
        echo json_encode(['message' => 'Invoice not found']);
        exit;
    }

    // Update invoice and related order based on status
    if ($status === 'success') {
        $pdo->beginTransaction();
        // Mark invoice paid
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', balance_due = 0 WHERE id = ?");
        $stmt->execute([$invoice['id']]);
        // Mark order completed
        $stmt = $pdo->prepare("UPDATE marketplace_orders SET status = 'paid' WHERE id = (SELECT related_id FROM invoices WHERE id = ?)");
        $stmt->execute([$invoice['id']]);
        $pdo->commit();
    } elseif ($status === 'failed') {
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'failed' WHERE id = ?");
        $stmt->execute([$invoice['id']]);
        $stmt = $pdo->prepare("UPDATE marketplace_orders SET status = 'failed' WHERE id = (SELECT related_id FROM invoices WHERE id = ?)");
        $stmt->execute([$invoice['id']]);
    } else { // pending or other states
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'pending' WHERE id = ?");
        $stmt->execute([$invoice['id']]);
        $stmt = $pdo->prepare("UPDATE marketplace_orders SET status = 'pending' WHERE id = (SELECT related_id FROM invoices WHERE id = ?)");
        $stmt->execute([$invoice['id']]);
    }

    echo json_encode(['message' => 'Callback processed']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>