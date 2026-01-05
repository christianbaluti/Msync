<?php
// /api/magazines/create.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$db->beginTransaction();

$cover_file = $_FILES['cover_image'] ?? null;
$magazine_file = $_FILES['magazine_file'] ?? null;
$data = $_POST;
$cover_url = null;
$file_url = null;

try {
    // 1. Validation
    if (empty($data['title'])) throw new Exception("Title is required.");
    if (!$cover_file || $cover_file['error'] !== UPLOAD_ERR_OK) throw new Exception("Cover image is required.");
    if (!$magazine_file || $magazine_file['error'] !== UPLOAD_ERR_OK) throw new Exception("Magazine file is required.");

    // 2. Handle Cover Image Upload
    $upload_dir_cover = dirname(__DIR__, 2) . '/public/uploads/magazines/covers/';
    if (!is_dir($upload_dir_cover)) @mkdir($upload_dir_cover, 0755, true);
    
    $cover_ext = strtolower(pathinfo($cover_file['name'], PATHINFO_EXTENSION));
    if (!in_array($cover_ext, ['jpg', 'jpeg', 'png', 'webp'])) throw new Exception("Invalid cover image type.");
    
    $cover_filename = 'cover_' . time() . '_' . uniqid() . '.' . $cover_ext;
    $cover_path = $upload_dir_cover . $cover_filename;
    $cover_url = '/uploads/magazines/covers/' . $cover_filename;
    
    if (!move_uploaded_file($cover_file['tmp_name'], $cover_path)) throw new Exception("Failed to upload cover image.");

    // 3. Handle Magazine File Upload
    $upload_dir_files = dirname(__DIR__, 2) . '/public/uploads/magazines/files/';
    if (!is_dir($upload_dir_files)) @mkdir($upload_dir_files, 0755, true);

    $file_ext = strtolower(pathinfo($magazine_file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ['pdf', 'epub'])) throw new Exception("Invalid magazine file type. Must be PDF or EPUB.");
    
    $file_filename = 'mag_' . time() . '_' . uniqid() . '.' . $file_ext;
    $file_path = $upload_dir_files . $file_filename;
    $file_url = '/uploads/magazines/files/' . $file_filename;
    
    if (!move_uploaded_file($magazine_file['tmp_name'], $file_path)) throw new Exception("Failed to upload magazine file.");

    // 4. Insert into Database
    $stmt = $db->prepare("INSERT INTO magazines (title, description, cover_image_url, file_url, file_type, view_count, download_count) VALUES (?, ?, ?, ?, ?, 0, 0)");
    $stmt->execute([
        $data['title'],
        $data['description'] ?? null,
        $cover_url,
        $file_url,
        $file_ext // 'pdf' or 'epub'
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Magazine created successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    // Clean up uploaded files on error
    if ($cover_path && file_exists($cover_path)) @unlink($cover_path);
    if ($file_path && file_exists($file_path)) @unlink($file_path);
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>