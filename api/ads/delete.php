<?php
// /api/ads/delete.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if (!has_permission('ads_delete')) { // Assumes this permission
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->connect();
$db->beginTransaction();
$data = json_decode(file_get_contents("php://input"), true);
$ad_id = $data['id'] ?? null;

if (!$ad_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ad ID is required.']);
    exit;
}

try {
    // 1. Get media path before deleting
    $stmt_old = $db->prepare("SELECT media_url FROM ads WHERE id = ?");
    $stmt_old->execute([$ad_id]);
    $old_media_path = $stmt_old->fetchColumn();
    $old_media_fullpath = $old_media_path ? dirname(__DIR__, 3) . $old_media_path : null;

    // 2. Delete from ad_placements (or let cascade)
    $stmt_placements = $db->prepare("DELETE FROM ad_placements WHERE ad_id = ?");
    $stmt_placements->execute([$ad_id]);

    // 3. Delete from ads
    $stmt_ad = $db->prepare("DELETE FROM ads WHERE id = ?");
    $stmt_ad->execute([$ad_id]);
    
    $db->commit();

    // 4. Delete file from server *after* commit
    if ($old_media_fullpath && file_exists($old_media_fullpath)) {
        @unlink($old_media_fullpath);
    }

    echo json_encode(['success' => true, 'message' => 'Ad campaign deleted.']);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>