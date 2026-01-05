<?php
// /api/products/create.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents("php://input"), true);
$db = (new Database())->connect();
$db->beginTransaction();

try {
    // 1. Validate Product
    if (empty($data['name'])) throw new Exception("Product name is required.");
    
    // 2. Validate Initial Variant
    if (empty($data['variant_name'])) throw new Exception("Variant name is required.");
    if (!isset($data['price']) || !is_numeric($data['price'])) throw new Exception("Variant price is required.");
    if (!isset($data['quantity']) || !is_numeric($data['quantity'])) throw new Exception("Variant stock is required.");

    // 3. Create Product
    $stmt_prod = $db->prepare("INSERT INTO products (name, sku, description, is_active) VALUES (?, ?, ?, ?)");
    $stmt_prod->execute([
        $data['name'],
        $data['sku'] ?? null,
        $data['description'] ?? null,
        $data['is_active'] ?? 1
    ]);
    $product_id = $db->lastInsertId();

    // 4. Create Initial Variant
    $stmt_var = $db->prepare("INSERT INTO product_variants (product_id, name, variant_sku, price) VALUES (?, ?, ?, ?)");
    $stmt_var->execute([
        $product_id,
        $data['variant_name'],
        $data['variant_sku'] ?? null,
        (float)$data['price']
    ]);
    $variant_id = $db->lastInsertId();

    // 5. Create Initial Inventory
    $stmt_inv = $db->prepare("INSERT INTO product_inventory (variant_id, quantity) VALUES (?, ?)");
    $stmt_inv->execute([
        $variant_id,
        (int)$data['quantity']
    ]);

    // 6. insert product images if any
    if (!empty($data['image_urls']) && is_array($data['image_urls'])) {
        $stmt_img = $db->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
        foreach ($data['image_urls'] as $imgUrl) {
            $stmt_img->execute([$product_id, $imgUrl]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Product and initial variant created successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>