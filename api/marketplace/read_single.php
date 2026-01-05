<?php
// /api/marketplace/read_single.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required.']);
    exit;
}

try {
    // 1. Get Order Details
    $query_order = "SELECT 
                        o.*, 
                        COALESCE(u.full_name, c.name) as customer_name,
                        COALESCE(u.email, c.email) as customer_email,
                        COALESCE(u.phone, c.phone) as customer_phone
                    FROM marketplace_orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    LEFT JOIN companies c ON o.company_id = c.id
                    WHERE o.id = ?";
    
    $stmt_order = $db->prepare($query_order);
    $stmt_order->execute([$order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // 2. Get Order Items
    $query_items = "SELECT 
                        oi.quantity, oi.unit_price, oi.total_price,
                        p.name as product_name, 
                        v.name as variant_name, v.variant_sku
                    FROM marketplace_order_items oi
                    JOIN product_variants v ON oi.variant_id = v.id
                    JOIN products p ON v.product_id = p.id
                    WHERE oi.order_id = ?";
    
    $stmt_items = $db->prepare($query_items);
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 3. Return combined data
    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Query Error: ' . $e->getMessage()]);
}
?>