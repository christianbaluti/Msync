<?php
// /api/ads/read_pages.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if (!has_permission('ads_read')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->connect();

try {
    // This list is expected to be small, so no pagination
    $stmt = $db->prepare("SELECT * FROM ad_pages ORDER BY label ASC");
    $stmt->execute();
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $pages]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>