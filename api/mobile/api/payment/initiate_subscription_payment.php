<?php
// api/mobile/api/payment/initiate_subscription_payment.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// ✅ Corrected paths
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';
require __DIR__ . '/../config.php';

$auth_data = get_auth_user();
$user_id = $auth_data->user_id;
$data = json_decode(file_get_contents("php://input"));

// ... (validation code is fine) ...
if (empty($data->membership_type_id) || empty($data->bank_id) || empty($data->phone_number)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required payment details.']);
    exit;
}
$membership_type_id = (int)$data->membership_type_id;
$bank_id = (int)$data->bank_id;
$phone_number = $data->phone_number;
$subscription_id = null;
$merchantTrxId = null;

try {
    // 1. Get membership type details (fee)
    $stmt_type = $pdo->prepare("SELECT fee FROM membership_types WHERE id = ?");
    $stmt_type->execute([$membership_type_id]);
    $type = $stmt_type->fetch(PDO::FETCH_ASSOC);
    if (!$type) { throw new Exception("Invalid Membership Type."); }
    $amount = (float)$type['fee'];

    // 2. Generate a unique merchant transaction ID (with 'r')
    $merchantTrxId = 'MEM-' . $user_id . '-' . $membership_type_id . '-' . time();

    // 3. Database Transaction
    try {
        $pdo->beginTransaction();

        // 3a. Create 'pending' subscription
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+1 year'));
        $stmt_sub = $pdo->prepare("INSERT INTO membership_subscriptions (user_id, membership_type_id, start_date, end_date, status, balance_due) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt_sub->execute([$user_id, $membership_type_id, $start_date, $end_date, $amount]);
        $subscription_id = $pdo->lastInsertId();

        // 3b. Create 'unpaid' invoice
        $stmt_inv = $pdo->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, balance_due, status) VALUES (?, 'membership', ?, ?, ?, 'unpaid')");
        $stmt_inv->execute([$user_id, $subscription_id, $amount, $amount]);
        $invoice_id = $pdo->lastInsertId();
        
        // 3c. Link invoice to subscription
        $stmt_sub_update = $pdo->prepare("UPDATE membership_subscriptions SET invoice_id = ? WHERE id = ?");
        $stmt_sub_update->execute([$invoice_id, $subscription_id]);

        // 3d. Create 'pending' payment record, storing our merchantTrxId
        $stmt_pay = $pdo->prepare("INSERT INTO payments (user_id, gateway_id, payment_type, reference_id, amount, method, status, gateway_transaction_id) VALUES (?, 1, 'membership', ?, ?, 'gateway', 'pending', ?)");
        $stmt_pay->execute([$user_id, $subscription_id, $amount, $merchantTrxId]);
        
        $pdo->commit();
    } catch (PDOException $db_e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw new Exception("Database error: " . $db_e->getMessage());
    }

    // 4. Call MALIPO API
    $client = new GuzzleHttp\Client();
    $headers = [ 'x-api-key' => MALIPO_API_KEY, 'x-app-id' => MALIPO_APP_ID, 'Content-Type' => 'application/json' ];
    $body = json_encode([ 'merchantTrxId' => $merchantTrxId, 'customerPhone' => $phone_number, 'bankId' => $bank_id, 'amount' => $amount ]);
    $response = $client->request('POST', MALIPO_API_BASE_URL . '/paymentrequest', [ 'headers' => $headers, 'body' => $body ]);
    $responseBody = json_decode($response->getBody()->getContents());

    if ($response->getStatusCode() == 200 && $responseBody->message) {
        echo json_encode([ 'success' => true, 'message' => 'Payment initiated. Please check your phone.', 'merchantTrxId' => $merchantTrxId ]);
    } else {
        throw new Exception($responseBody->message ?? 'Failed to initiate payment with gateway.');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }

    if ($subscription_id !== null && $merchantTrxId !== null) {
        try {
            $meta_error = json_encode(['error' => 'Initiation failed: ' . $e->getMessage()]);
            $stmt_fail = $pdo->prepare("UPDATE payments SET status = 'failed', meta = ? WHERE gateway_transaction_id = ?");
            $stmt_fail->execute([$meta_error, $merchantTrxId]);
        } catch (PDOException $comp_e) {
            // Log this compensating transaction error
        }
    }
    
    http_response_code(500);
    echo json_encode([ 'success' => false, 'message' => 'An error occurred: ' . $e->getMessage() ]);
}
?>