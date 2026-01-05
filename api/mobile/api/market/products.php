<?php
// api/market/products.php

// 1. Set Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// 2. Handle pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

// 3. Includes
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php'; // This file must provide the $pdo object

try {
    // 4. Calculate Base URL for images
    // This logic correctly builds the absolute path for your image proxy
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $api_base_dir = dirname(dirname($_SERVER['SCRIPT_NAME']));
    if ($api_base_dir === '/' || $api_base_dir === '\\') {
        $api_base_dir = '';
    }
    $base_url = rtrim("$protocol://$host" . $api_base_dir, '/');

    
    // --- QUERY 1: Get all active products ---
    // We only fetch the main product data and its primary image here.
    $sql_products = "
        SELECT
            p.id,
            p.name,
            p.sku,
            p.description,
            p.created_at,
            (
                SELECT pi.image_url
                FROM product_images pi
                WHERE pi.product_id = p.id
                ORDER BY pi.display_order ASC, pi.id ASC
                LIMIT 1
            ) AS primary_image_url
        FROM
            products p
        WHERE
            p.is_active = 1
        ORDER BY
            p.created_at DESC;
    ";
    
    $stmt_products = $pdo->query($sql_products);
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    // If no products, return an empty array now.
    if (empty($products)) {
        echo json_encode([]);
        exit;
    }

    // --- Data Fetching (Anti N+1) ---
    // Get all product IDs to fetch their "children" (variants and images) in one go.
    $product_ids = array_column($products, 'id');
    $in_placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    
    // --- QUERY 2: Get all variants and their stock for all products ---
    $sql_variants = "
        SELECT
            pv.id,
            pv.product_id,
            pv.variant_sku,
            pv.name,
            pv.attributes,
            pv.price,
            COALESCE(inv.quantity, 0) AS quantity
        FROM
            product_variants pv
        LEFT JOIN
            product_inventory inv ON pv.id = inv.variant_id
        WHERE
            pv.product_id IN ($in_placeholders)
        ORDER BY
            pv.product_id, pv.id;
    ";
    $stmt_variants = $pdo->prepare($sql_variants);
    $stmt_variants->execute($product_ids);
    $all_variants = $stmt_variants->fetchAll(PDO::FETCH_ASSOC);


    // --- QUERY 3: Get all images for all products ---
    $sql_images = "
        SELECT
            pi.id,
            pi.product_id,
            pi.image_url,
            pi.display_order
        FROM
            product_images pi
        WHERE
            pi.product_id IN ($in_placeholders)
        ORDER BY
            pi.product_id, pi.display_order ASC, pi.id ASC;
    ";
    $stmt_images = $pdo->prepare($sql_images);
    $stmt_images->execute($product_ids);
    $all_images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);

    
    // --- Data Mapping Phase ---
    // Create lookup maps for fast assembly. This is much faster than nested loops.
    $variants_map = [];
    $images_map = [];

    // Process and map all variants
    foreach ($all_variants as $variant) {
        // Ensure correct data types
        $variant['id'] = (int) $variant['id'];
        $variant['product_id'] = (int) $variant['product_id'];
        $variant['price'] = (float) $variant['price'];
        $variant['quantity'] = (int) $variant['quantity'];
        
        // Decode the attributes JSON string into a PHP object/array
        $variant['attributes'] = json_decode($variant['attributes'], true);
        
        // Add to the map
        $variants_map[$variant['product_id']][] = $variant;
    }

    // Process and map all images
    foreach ($all_images as $image) {
        // Ensure correct data types
        $image['id'] = (int) $image['id'];
        $image['product_id'] = (int) $image['product_id'];
        $image['display_order'] = (int) $image['display_order'];
        
        // Prepend the full base URL
        $image['image_url'] = $base_url . $image['image_url'];
        
        // Add to the map
        $images_map[$image['product_id']][] = $image;
    }

    
    // --- Final Assembly ---
    // Loop through the main $products array (by reference) and attach children.
    foreach ($products as &$product) {
        $product_id = (int) $product['id'];

        // 1. Attach variants and images from our maps
        $product_variants = $variants_map[$product_id] ?? [];
        $product['variants'] = $product_variants;
        $product['images'] = $images_map[$product_id] ?? [];

        // 2. Fix product ID data type
        $product['id'] = $product_id;

        // 3. Fix primary image URL
        if (!empty($product['primary_image_url'])) {
            $product['primary_image_url'] = $base_url . $product['primary_image_url'];
        }

        // 4. Calculate aggregates (total_stock, starting_price) in PHP
        // This is more accurate and flexible than doing it in the main SQL query.
        $total_stock = 0;
        $starting_price = 0.00;

        if (!empty($product_variants)) {
            $prices = array_column($product_variants, 'price');
            $stocks = array_column($product_variants, 'quantity');
            $total_stock = array_sum($stocks);
            $starting_price = (float) min($prices);
        }
        
        $product['total_stock'] = (int) $total_stock;
        $product['starting_price'] = $starting_price;
    }
    unset($product); // Unset the reference

    // 7. Send final JSON response
    echo json_encode($products);

} catch (PDOException $e) {
    // 8. Robust error handling
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Database query failed.',
        // 'error' => $e->getMessage() // Uncomment for debugging
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred.',
        // 'error' => $e->getMessage() // Uncomment for debugging
    ]);
}
?>