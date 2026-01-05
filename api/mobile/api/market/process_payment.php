<?php
// server/api/market/process_payment.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

try {
    $auth_data = get_auth_user();
    $user_id = $auth_data->user_id;

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['order_id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'order_id is required']);
        exit;
    }
    $order_id = (int)$input['order_id'];

    // Fetch order total
    $stmt = $pdo->prepare("SELECT id, total_amount, status FROM marketplace_orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        http_response_code(404);
        echo json_encode(['message' => 'Order not found']);
        exit;
    }

    // Prepare a hosted checkout URL that will initiate MALIPO flow
    // We return a relative path so the client can join with its base domain
    $checkout_path = '/api/malipo/checkout_marketplace.php?order_id=' . urlencode($order_id);

    echo json_encode([
        'message' => 'Payment initialized',
        'checkout_url' => $checkout_path,
        'order_id' => $order_id,
        'amount' => (float)$order['total_amount'],
        'currency' => 'MWK'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>