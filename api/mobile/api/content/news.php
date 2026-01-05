<?php
// api/content/news.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ .  '/../../vendor/autoload.php';
require_once __DIR__ . '/../db_connection.php'; 

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$offset = ($page - 1) * $limit;

try {
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM news");
    $total_items = $total_stmt->fetchColumn();

    $sql = "SELECT id, title, content, created_at, media_url 
            FROM news 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $api_base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME']));
    foreach ($items as &$item) {
        if (!empty($item['media_url'])) {
            $item['media_url'] = $api_base_url . $item['media_url'];
        }
    }

    echo json_encode([
        'total' => (int)$total_items,
        'items' => $items
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>