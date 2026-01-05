<?php
// /api/news/update.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = $_POST;
if (empty($data['id']) || empty($data['title']) || empty($data['content'])) { /* error */ }

$db = (new Database())->connect();

// Get existing record to check for old image
$stmt = $db->prepare("SELECT media_url FROM news WHERE id = ?");
$stmt->execute([$data['id']]);
$article = $stmt->fetch();
$media_url = $article['media_url'];

if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
    // Delete old image if it exists
    if ($media_url) {
        $old_path = dirname(__DIR__, 2) . '/public' . $media_url;
        if (file_exists($old_path)) unlink($old_path);
    }
    // Upload new image
    $upload_dir = dirname(__DIR__, 2) . '/public/uploads/news/';
    $filename = uniqid() . '-' . basename($_FILES['media']['name']);
    $target_path = $upload_dir . $filename;
    move_uploaded_file($_FILES['media']['tmp_name'], $target_path);
    $media_url = '/uploads/news/' . $filename;
}

$query = "UPDATE news SET title = ?, content = ?, media_url = ?, scheduled_date = ? WHERE id = ?";
$stmt = $db->prepare($query);
$scheduled_date = !empty($data['scheduled_date']) ? $data['scheduled_date'] : null;

if ($stmt->execute([$data['title'], $data['content'], $media_url, $scheduled_date, $data['id']])) {
    echo json_encode(['success' => true, 'message' => 'Article updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update article.']);
}