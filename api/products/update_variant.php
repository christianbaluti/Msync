<?php
// /api/products/update_variant.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents("php://input"), true);
$db = (new Database())->connect();
$db->beginTransaction();

try {
    $variant_id = $data['variant_id'] ?? null;
    $price = $data['price'] ?? null;
    $quantity = $data['quantity'] ?? null;

    if (!$variant_id) throw new Exception("Variant ID is required.");
    if (!is_numeric($price)) throw new Exception("Price must be a number.");
    if (!is_numeric($quantity)) throw new Exception("Quantity must be a number.");

    // 1. Update Price
    $stmt_var = $db->prepare("UPDATE product_variants SET price = ? WHERE id = ?");
    $stmt_var->execute([(float)$price, $variant_id]);

    // 2. Update Stock (UPSERT)
    // Try to update existing record, if not, insert a new one
    $stmt_inv_update = $db->prepare("UPDATE product_inventory SET quantity = ? WHERE variant_id = ?");
    $stmt_inv_update->execute([(int)$quantity, $variant_id]);

    if ($stmt_inv_update->rowCount() === 0) {
        // No record was updated, so insert
        $stmt_inv_insert = $db->prepare("INSERT INTO product_inventory (variant_id, quantity) VALUES (?, ?)");
        $stmt_inv_insert->execute([$variant_id, (int)$quantity]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Variant updated.']);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>