<?php
// /api/ads/delete_page.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if (!has_permission('ads_delete')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Page ID is required.']);
    exit;
}

try {
    // First, delete placements referencing this page to avoid constraint errors
    $stmt_pl = $db->prepare("DELETE FROM ad_placements WHERE page_id = ?");
    $stmt_pl->execute([$id]);
    
    // Then, delete the page itself
    $stmt = $db->prepare("DELETE FROM ad_pages WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'App placement and all associated ads deleted.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>