<?php
// server/api/market/create_order.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

$auth_data = get_auth_user();
$user_id = $auth_data->user_id;

// Read body once after auth; supports both legacy array body and new { auth_token, items: [] }
$input = json_decode(file_get_contents("php://input"), true);
$cart_items = [];
if (is_array($input)) {
    // Detect associative array
    $is_assoc = array_keys($input) !== range(0, count($input) - 1);
    if ($is_assoc && isset($input['items']) && is_array($input['items'])) {
        $cart_items = $input['items'];
    } else {
        // Backward-compatible: treat root array as items
        $cart_items = $input;
    }
}

if (empty($cart_items)) {
    http_response_code(400);
    echo json_encode(['message' => 'Cart is empty.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Calculate total and fetch prices from DB to prevent tampering
    $total_amount = 0;
    $ids = array_column($cart_items, 'variant_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt_prices = $pdo->prepare("SELECT id, price FROM product_variants WHERE id IN ($placeholders)");
    $stmt_prices->execute($ids);
    $prices = $stmt_prices->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($cart_items as &$item) {
        if (!isset($prices[$item['variant_id']])) {
            throw new Exception('Invalid variant specified.');
        }
        $item['unit_price'] = (float)$prices[$item['variant_id']];
        $total_amount += $item['unit_price'] * (int)$item['quantity'];
    }
    unset($item);

    // 2. Create the main order
    $stmt_order = $pdo->prepare("INSERT INTO marketplace_orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
    $stmt_order->execute([$user_id, $total_amount]);
    $order_id = (int)$pdo->lastInsertId();

    // 3. Insert order items
    $stmt_items = $pdo->prepare("INSERT INTO marketplace_order_items (order_id, variant_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt_items->execute([
            $order_id,
            (int)$item['variant_id'],
            (int)$item['quantity'],
            (float)$item['unit_price'],
            (float)$item['unit_price'] * (int)$item['quantity']
        ]);
    }
    
    // 4. Create an associated invoice
    $stmt_inv = $pdo->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, balance_due, status) VALUES (?, 'marketplace_order', ?, ?, ?, 'unpaid')");
    $stmt_inv->execute([$user_id, $order_id, $total_amount, $total_amount]);

    $pdo->commit();

    echo json_encode(['message' => 'Order created successfully!', 'order_id' => $order_id, 'total_amount' => $total_amount, 'currency' => 'MWK']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>