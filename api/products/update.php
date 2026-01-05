<?php
// /api/products/update.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents("php://input"), true);
$db = (new Database())->connect();

try {
    $product_id = $data['product_id'] ?? null;
    if (empty($product_id)) throw new Exception("Product ID is required.");
    if (empty($data['name'])) throw new Exception("Product name is required.");

    $stmt = $db->prepare("UPDATE products SET name = ?, sku = ?, description = ?, is_active = ? WHERE id = ?");
    $stmt->execute([
        $data['name'],
        $data['sku'] ?? null,
        $data['description'] ?? null,
        $data['is_active'] ?? 1,
        $product_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>