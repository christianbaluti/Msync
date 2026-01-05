<?php
// /api/products/create_variant.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents("php://input"), true);
$db = (new Database())->connect();
$db->beginTransaction();

try {
    if (empty($data['product_id'])) throw new Exception("Product ID is required.");
    if (empty($data['name'])) throw new Exception("Variant name is required.");
    if (!isset($data['price']) || !is_numeric($data['price'])) throw new Exception("Variant price is required.");
    if (!isset($data['quantity']) || !is_numeric($data['quantity'])) throw new Exception("Variant stock is required.");

    // 1. Create Variant
    $stmt_var = $db->prepare("INSERT INTO product_variants (product_id, name, variant_sku, price, attributes) VALUES (?, ?, ?, ?, ?)");
    $stmt_var->execute([
        $data['product_id'],
        $data['name'],
        $data['variant_sku'] ?? null,
        (float)$data['price'],
        !empty($data['attributes']) ? json_encode($data['attributes']) : null
    ]);
    $variant_id = $db->lastInsertId();

    // 2. Create Inventory
    $stmt_inv = $db->prepare("INSERT INTO product_inventory (variant_id, quantity) VALUES (?, ?)");
    $stmt_inv->execute([
        $variant_id,
        (int)$data['quantity']
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'New variant added successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>