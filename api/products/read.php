<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// /api/products/read.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

$params = [];
$filterClauses = [];

$status = $_GET['status'] ?? null;
if ($status !== '' && $status !== null) {
    $filterClauses[] = "p.is_active = :status";
    $params[':status'] = $status;
}

$search = $_GET['search'] ?? null;
if ($search) {
    $filterClauses[] = "(p.name LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($filterClauses) ? 'WHERE ' . implode(' AND ', $filterClauses) : '';

$base_query = "FROM products p
               LEFT JOIN (
                   SELECT product_id, COUNT(id) as variant_count 
                   FROM product_variants 
                   GROUP BY product_id
               ) pv ON p.id = pv.product_id
               LEFT JOIN (
                   SELECT v.product_id, SUM(i.quantity) as total_stock
                   FROM product_inventory i
                   JOIN product_variants v ON i.variant_id = v.id
                   GROUP BY v.product_id
               ) pi ON p.id = pi.product_id
              LEFT JOIN (
                    SELECT product_id, MIN(image_url) AS image_url
                    FROM product_images
                    WHERE display_order = 0 OR display_order IS NULL
                    GROUP BY product_id
                ) img ON p.id = img.product_id
               $where_clause";
try {
    $stmt_count = $db->prepare("SELECT COUNT(p.id) $base_query");
    $stmt_count->execute($params);
    $total_records = (int)$stmt_count->fetchColumn();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    $query = "SELECT 
                p.id, p.name, p.sku, p.is_active,
                COALESCE(pv.variant_count, 0) as variant_count,
                COALESCE(pi.total_stock, 0) as total_stock,
                img.image_url
              $base_query
              ORDER BY p.created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt_table = $db->prepare($query);
    foreach ($params as $key => $val) $stmt_table->bindValue($key, $val);
    $stmt_table->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt_table->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt_table->execute();
    $products = $stmt_table->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as &$product) {
        if (!empty($product['image_url'])) {
            
            $imagePath = $product['image_url'];
            
            $product['image_url'] = rtrim(UPLOAD_BASE_URL, '/') . '/' . ltrim($imagePath, '/');
        } else {
            
            $product['image_url'] = rtrim(UPLOAD_BASE_URL, '/') . '/defaults/no-image.png';
        }
    }

    echo json_encode([
        'success' => true,
        'products' => $products,
        'total_records' => $total_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>