<?php
// /api/magazines/update.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$db->beginTransaction();

$data = $_POST;
$id = $data['id'] ?? null;
$cover_file = $_FILES['cover_image'] ?? null;
$magazine_file = $_FILES['magazine_file'] ?? null;

$new_cover_path = null;
$new_file_path = null;

try {
    if (!$id) throw new Exception("Magazine ID is required.");
    if (empty($data['title'])) throw new Exception("Title is required.");

    // 1. Get existing record
    $stmt_old = $db->prepare("SELECT cover_image_url, file_url FROM magazines WHERE id = ?");
    $stmt_old->execute([$id]);
    $old_files = $stmt_old->fetch(PDO::FETCH_ASSOC);
    if (!$old_files) throw new Exception("Magazine not found.");

    $cover_url = $old_files['cover_image_url'];
    $file_url = $old_files['file_url'];
    $file_ext = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));

    // 2. Handle NEW Cover Image
    if ($cover_file && $cover_file['error'] === UPLOAD_ERR_OK) {
        $upload_dir_cover = dirname(__DIR__, 2) . '/public/uploads/magazines/covers/';
        $cover_ext = strtolower(pathinfo($cover_file['name'], PATHINFO_EXTENSION));
        if (!in_array($cover_ext, ['jpg', 'jpeg', 'png', 'webp'])) throw new Exception("Invalid cover image type.");
        
        $cover_filename = 'cover_' . time() . '_' . uniqid() . '.' . $cover_ext;
        $new_cover_path = $upload_dir_cover . $cover_filename;
        $cover_url = '/uploads/magazines/covers/' . $cover_filename;

        if (!move_uploaded_file($cover_file['tmp_name'], $new_cover_path)) throw new Exception("Failed to upload new cover image.");
    }

    // 3. Handle NEW Magazine File
    if ($magazine_file && $magazine_file['error'] === UPLOAD_ERR_OK) {
        $upload_dir_files = dirname(__DIR__, 2) . '/public/uploads/magazines/files/';
        $file_ext = strtolower(pathinfo($magazine_file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, ['pdf', 'epub'])) throw new Exception("Invalid magazine file type.");

        $file_filename = 'mag_' . time() . '_' . uniqid() . '.' . $file_ext;
        $new_file_path = $upload_dir_files . $file_filename;
        $file_url = '/uploads/magazines/files/' . $file_filename;

        if (!move_uploaded_file($magazine_file['tmp_name'], $new_file_path)) throw new Exception("Failed to upload new magazine file.");
    }

    // 4. Update Database
    $stmt = $db->prepare("UPDATE magazines SET title = ?, description = ?, cover_image_url = ?, file_url = ?, file_type = ? WHERE id = ?");
    $stmt->execute([
        $data['title'],
        $data['description'] ?? null,
        $cover_url,
        $file_url,
        $file_ext,
        $id
    ]);

    $db->commit();
    
    // 5. Clean up old files *after* commit
    if ($new_cover_path && !empty($old_files['cover_image_url'])) {
        $old_cover_fullpath = dirname(__DIR__, 2) . $old_files['cover_image_url'];
        if (file_exists($old_cover_fullpath)) @unlink($old_cover_fullpath);
    }
    if ($new_file_path && !empty($old_files['file_url'])) {
        $old_file_fullpath = dirname(__DIR__, 3) . $old_files['file_url'];
        if (file_exists($old_file_fullpath)) @unlink($old_file_fullpath);
    }

    echo json_encode(['success' => true, 'message' => 'Magazine updated successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    // Clean up *newly* uploaded files on error
    if ($new_cover_path && file_exists($new_cover_path)) @unlink($new_cover_path);
    if ($new_file_path && file_exists($new_file_path)) @unlink($new_file_path);
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>