<?php
// /api/magazines/delete.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$db->beginTransaction();
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Magazine ID is required.']);
    exit;
}

try {
    // 1. Get file paths before deleting
    $stmt_old = $db->prepare("SELECT cover_image_url, file_url FROM magazines WHERE id = ?");
    $stmt_old->execute([$id]);
    $files = $stmt_old->fetch(PDO::FETCH_ASSOC);

    if ($files) {
        // 2. Delete from database
        $stmt_del = $db->prepare("DELETE FROM magazines WHERE id = ?");
        $stmt_del->execute([$id]);
        
        $db->commit();

        // 3. Delete files from server *after* commit
        $old_cover_fullpath = dirname(__DIR__, 3) . $files['cover_image_url'];
        if (file_exists($old_cover_fullpath)) @unlink($old_cover_fullpath);
        
        $old_file_fullpath = dirname(__DIR__, 3) . $files['file_url'];
        if (file_exists($old_file_fullpath)) @unlink($old_file_fullpath);

        echo json_encode(['success' => true, 'message' => 'Magazine deleted successfully.']);
    } else {
        throw new Exception("Magazine not found.");
    }

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>