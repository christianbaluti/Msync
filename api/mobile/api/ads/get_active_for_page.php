<?php
// Allow all origins
header("Access-Control-Allow-Origin: *");

// Allow all request methods
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

// Allow all headers
header("Access-Control-Allow-Headers: *");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';

// Remove strict GET validation (since all methods allowed)
// But still allow GET logic to run only when using GET.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    // Instead of blocking other methods, just respond gracefully
    echo json_encode([
        "warning" => "This endpoint is meant for GET requests. Other methods are allowed for CORS but will not return data."
    ]);
    exit;
}

// Validate 'code' parameter
$code = $_GET['code'] ?? null;

if (!$code || trim($code) === '') {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing or empty 'code' parameter."
    ]);
    exit;
}

$code = trim($code);

try {
    // Build base URL dynamically using server variables
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . $host;

    // Fetch the most recent running ad for the given page code
    $sql = "SELECT 
                a.id,
                a.title,
                a.body,
                a.media_url,
                a.url_target,
                a.created_by,
                a.start_at,
                a.end_at,
                a.status,
                a.created_at
            FROM ads a
            JOIN ad_placements p ON p.ad_id = a.id
            JOIN ad_pages pg ON pg.id = p.page_id
            WHERE pg.code = :code
              AND a.status = 'running'
              AND NOW() BETWEEN a.start_at AND a.end_at
            ORDER BY a.created_at DESC
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':code' => $code]);
    $ad = $stmt->fetch(PDO::FETCH_ASSOC);

    // Convert media_url into a full URL
    if ($ad && !empty($ad['media_url'])) {
        $ad['media_url'] = rtrim($base_url, '/') . '/' . ltrim($ad['media_url'], '/');
    }

    echo json_encode([
        "success" => true,
        "page_code" => $code,
        "ad" => $ad ?: null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
}
