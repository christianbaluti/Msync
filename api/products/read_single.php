<?php
// /api/products/read_single.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
    exit;
}

try {
    // Fetch product details
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    // Fetch product images (if any)
    $imgStmt = $db->prepare("
        SELECT id, image_url, display_order 
        FROM product_images 
        WHERE product_id = ? 
        ORDER BY display_order ASC, id ASC
    ");
    $imgStmt->execute([$product_id]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($images as &$image) {
        $image['image_url'] = UPLOAD_BASE_URL . $image['image_url'];
    }

    // Combine data
    echo json_encode([
        'success' => true,
        'product' => $product,
        'images' => $images
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>
