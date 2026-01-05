<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once dirname(__DIR__) . '/core/initialize.php';

// Parse Input
$input = json_decode(file_get_contents('php://input'), true);
$uid = $input['uid'] ?? 0;
$token = $input['token'] ?? '';
$type_id = $input['membership_type_id'] ?? 0;

if (!$uid || !$token || !$type_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

$database = new Database();
$db = $database->connect();

try {
    $db->beginTransaction();

    // 1. Re-Validate Token (Security Check)
    $stmt = $db->prepare("SELECT id FROM invoice_access_tokens WHERE user_id = :uid AND token = :token AND status = 'pending' AND expires_at > NOW() LIMIT 1");
    $stmt->execute([':uid' => $uid, ':token' => $token]);
    if (!$stmt->fetch()) {
        throw new Exception("Invalid or expired session. Please refresh.");
    }

    // 2. Get Membership Details
    $stmtType = $db->prepare("SELECT fee, name, COALESCE(prefix, 'MID') AS prefix, renewal_month FROM membership_types WHERE id = ?");
    $stmtType->execute([$type_id]);
    $mType = $stmtType->fetch(PDO::FETCH_ASSOC);

    if (!$mType) throw new Exception("Invalid Membership Type selected.");

    $fee = (float)$mType['fee'];
    $prefix = $mType['prefix'];

    // 3. Generate Card Number logic (Simplified from your code)
    // ... [Insert your specific card generation logic here if strict, simplified below] ...
    $card_number = $prefix . '_' . time() . '_' . $uid; 

    // 4. Create Local Records
    // Subscription
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+' . ($mType['renewal_month'] ?? 12) . ' months'));

    $stmtSub = $db->prepare("INSERT INTO membership_subscriptions (user_id, membership_type_id, start_date, end_date, status, membership_card_number, balance_due) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $stmtSub->execute([$uid, $type_id, $startDate, $endDate, $card_number, $fee]);
    $subId = $db->lastInsertId();

    // Invoice
    $stmtInv = $db->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, balance_due, status, issued_at) VALUES (?, 'membership', ?, ?, ?, 'unpaid', NOW())");
    $stmtInv->execute([$uid, $subId, $fee, $fee]);
    $invId = $db->lastInsertId();

    // Link Invoice to Sub
    $db->prepare("UPDATE membership_subscriptions SET invoice_id = ? WHERE id = ?")->execute([$invId, $subId]);

    // 5. Construct Malipo Redirect
    // Get Gateway Config
    $stmtGw = $db->prepare("SELECT config FROM payment_gateways WHERE name LIKE 'MALIPO_%' LIMIT 1");
    $stmtGw->execute();
    $gwRow = $stmtGw->fetch(PDO::FETCH_ASSOC);
    $config = json_decode($gwRow['config'] ?? '{}', true);
    
    // We expect 'base_url', 'api_key', 'app_id' in config
    $baseUrl = rtrim($config['base_url'] ?? 'https://invoicing.malipo.mw/guest', '/');
    
    // Construct the unique transaction reference for Malipo
    $trxRef = 'INV-' . $invId . '-' . time();

    // Create Payment Record (Pending)
    $stmtPay = $db->prepare("INSERT INTO payments (user_id, payment_type, reference_id, amount, method, status, gateway_transaction_id) VALUES (?, 'membership', ?, ?, 'gateway', 'pending', ?)");
    $stmtPay->execute([$uid, $subId, $fee, $trxRef]);

    // 6. Update Token Status (Optional: Mark used now, or after payment. Keeping it valid allows retry if payment fails)
    // $db->prepare("UPDATE invoice_access_tokens SET status = 'used' WHERE user_id = ? AND token = ?")->execute([$uid, $token]);

    $db->commit();

    // 7. Return Redirect URL
    // NOTE: This URL structure depends on Malipo's Web Integration documentation. 
    // Assuming standard GET param structure based on provided config. 
    // If Malipo requires a server-to-server POST to get a link, we would do that here using Guzzle.
    
    // Assuming we redirect to a payment page with params:
    $redirectUrl = "{$baseUrl}/pay?app_id={$config['app_id']}&ref={$trxRef}&amount={$fee}&email=" . urlencode($input['email'] ?? '');

    echo json_encode([
        'success' => true,
        'redirect_url' => $redirectUrl
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}