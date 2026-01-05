<?php
// /api/ads/create.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$db->beginTransaction();

try {
    $data = $_POST;
    $file = $_FILES['media'] ?? null;

    // 1. Validation
    if (empty($data['title'])) throw new Exception("Title is required.");
    if (empty($data['start_at'])) throw new Exception("Start date is required.");
    if (empty($data['end_at'])) throw new Exception("End date is required.");
    if (!$file) throw new Exception("Ad Media file is required.");
    
    $created_by = $_SESSION['user_id'] ?? null; // Get from session
    if (!$created_by) throw new Exception("User session not found. Please log in.");

    // 2. Handle File Upload
    $upload_dir = dirname(__DIR__, 2) . '/public/uploads/ads/';
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
    
    $filename = 'ad_' . time() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;
    $media_url = '/uploads/ads/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception("Failed to upload media file.");
    }

    // 3. Insert Ad
    $stmt = $db->prepare("INSERT INTO ads (title, body, media_url, url_target, created_by, start_at, end_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['title'],
        $data['body'] ?? null,
        $media_url,
        $data['url_target'] ?? null,
        $created_by,
        $data['start_at'],
        $data['end_at'],
        $data['status'] ?? 'draft'
    ]);
    $ad_id = $db->lastInsertId();

    // 4. Handle Placements
    $placements = json_decode($data['placements'] ?? '[]', true);
    if (!empty($placements)) {
        $stmt_place = $db->prepare("INSERT INTO ad_placements (ad_id, page_id, position) VALUES (?, ?, ?)");
        foreach ($placements as $p) {
            $stmt_place->execute([$ad_id, $p['page_id'], $p['position']]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Ad campaign created successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    // Delete uploaded file on error
    if (!empty($target_path) && file_exists($target_path)) {
        @unlink($target_path);
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>