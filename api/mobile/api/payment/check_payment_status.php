<?php
// api/mobile/api/payment/check_payment_status.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

// Secure this endpoint
$auth_data = get_auth_user();
$user_id = $auth_data->user_id;

if (empty($_GET['trx_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'failed', 'message' => 'Transaction ID is required.']);
    exit;
}

$merchantTrxId = $_GET['trx_id'];

try {
    // Find the payment by the merchantTrxId we created
    // We also check user_id to ensure a user can only check their own transaction
    $stmt = $pdo->prepare(
        "SELECT status FROM payments WHERE gateway_transaction_id = ? AND user_id = ?"
    );
    $stmt->execute([$merchantTrxId, $user_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        // Return the current status (e.g., 'pending', 'completed', 'failed')
        echo json_encode(['status' => $payment['status']]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'failed', 'message' => 'Transaction not found.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'failed', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>