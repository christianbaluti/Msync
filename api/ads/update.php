<?php
// /api/ads/update.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$db->beginTransaction();

try {
    $data = $_POST;
    $file = $_FILES['media'] ?? null;
    $ad_id = $data['id'] ?? null;

    if (!$ad_id) throw new Exception("Ad ID is required.");

    // 1. Get existing ad to check old media
    $stmt_old = $db->prepare("SELECT media_url FROM ads WHERE id = ?");
    $stmt_old->execute([$ad_id]);
    $old_media_path = $stmt_old->fetchColumn();
    $media_url = $old_media_path;
    $old_media_fullpath = $old_media_path ? dirname(__DIR__, 2) . $old_media_path : null;

    // 2. Handle File Upload (if new file provided)
    $new_target_path = null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__, 2) . '/public/uploads/ads/';
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
        
        $filename = 'ad_' . time() . '_' . basename($file['name']);
        $new_target_path = $upload_dir . $filename;
        $media_url = '/uploads/ads/' . $filename; // Set new URL

        if (!move_uploaded_file($file['tmp_name'], $new_target_path)) {
            throw new Exception("Failed to upload new media file.");
        }
    }

    // 3. Update Ad
    $stmt = $db->prepare("UPDATE ads SET title = ?, body = ?, media_url = ?, url_target = ?, start_at = ?, end_at = ?, status = ? WHERE id = ?");
    $stmt->execute([
        $data['title'],
        $data['body'] ?? null,
        $media_url,
        $data['url_target'] ?? null,
        $data['start_at'],
        $data['end_at'],
        $data['status'] ?? 'draft',
        $ad_id
    ]);

    // 4. Handle Placements (Delete old, insert new)
    $stmt_delete_placements = $db->prepare("DELETE FROM ad_placements WHERE ad_id = ?");
    $stmt_delete_placements->execute([$ad_id]);

    $placements = json_decode($data['placements'] ?? '[]', true);
    if (!empty($placements)) {
        $stmt_place = $db->prepare("INSERT INTO ad_placements (ad_id, page_id, position) VALUES (?, ?, ?)");
        foreach ($placements as $p) {
            $stmt_place->execute([$ad_id, $p['page_id'], $p['position']]);
        }
    }

    $db->commit();
    
    // 5. Delete old file *after* commit
    if ($new_target_path && $old_media_fullpath && file_exists($old_media_fullpath)) {
        @unlink($old_media_fullpath);
    }
    
    echo json_encode(['success' => true, 'message' => 'Ad campaign updated successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    // Delete newly uploaded file on error
    if (!empty($new_target_path) && file_exists($new_target_path)) {
        @unlink($new_target_path);
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>