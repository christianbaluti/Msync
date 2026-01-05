<?php
// api/content/magazine.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ .  '/../../vendor/autoload.php';
require_once __DIR__ . '/../db_connection.php'; 

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$offset = ($page - 1) * $limit;

try {
    // Get total number of magazines
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM magazines");
    $total_items = $total_stmt->fetchColumn();

    // Get paginated magazines
    // We select all fields required by the magazine_model.dart
    $sql = "SELECT id, title, description, cover_image_url, 
                   file_url, file_type, view_count, 
                   download_count, created_at 
            FROM magazines 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the JSON response
    echo json_encode([
        'total' => (int)$total_items,
        'items' => $items
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>