<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ticket ID is required.']);
    exit();
}

$db = (new Database())->connect();
$db->beginTransaction();

try {
    $ticket_id = $data->id;

    // To maintain financial integrity, we don't delete payments/invoices,
    // but a real-world app might mark them as 'cancelled' or 'void'.
    // For simplicity here, we will just delete the ticket itself.
    // A check could be added to prevent deleting tickets with payments.
    
    $stmt = $db->prepare("DELETE FROM event_tickets WHERE id = :id");
    $stmt->bindParam(':id', $ticket_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Ticket deleted successfully.']);
        } else {
            throw new Exception('Ticket not found or already deleted.');
        }
    } else {
        throw new Exception('Database execution failed.');
    }
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}