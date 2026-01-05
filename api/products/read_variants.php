<?php
// /api/products/read_variants.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$product_id = $_GET['product_id'] ?? null;

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
    exit;
}

try {
    $query = "SELECT 
                v.id, v.name, v.variant_sku, v.price, v.attributes,
                COALESCE(i.quantity, 0) as quantity
              FROM product_variants v
              LEFT JOIN product_inventory i ON v.id = i.variant_id
              WHERE v.product_id = ?
              ORDER BY v.created_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$product_id]);
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'variants' => $variants]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>