<?php
// /api/marketplace/update_status.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"), true);

$order_id = $data['order_id'] ?? null;
$new_status = $data['new_status'] ?? null;

// Validation
$allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!$order_id || !$new_status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID and new status are required.']);
    exit;
}
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status provided.']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE marketplace_orders SET status = ? WHERE id = ?");
    
    if ($stmt->execute([$new_status, $order_id])) {
        // TODO: Optionally log this change to audit_logs
        // (new AuditLog($db))->log('user', $_SESSION['user_id'], 'order_status_update', 'marketplace_order', $order_id, ['new_status' => $new_status]);
        
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully.']);
    } else {
        throw new Exception("Failed to update order status in database.");
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Query Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>