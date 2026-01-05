<?php
// /api/news/create.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = $_POST;
if (empty($data['title']) || empty($data['content'])) {
    echo json_encode(['success' => false, 'message' => 'Title and Content are required.']);
    exit;
}

$media_url = null;
if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = dirname(__DIR__, 2) . '/public/uploads/news/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $filename = uniqid() . '-' . basename($_FILES['media']['name']);
    $target_path = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['media']['tmp_name'], $target_path)) {
        $media_url = '/uploads/news/' . $filename;
    }
}

$db = (new Database())->connect();
$query = "INSERT INTO news (title, content, media_url, scheduled_date, created_by) VALUES (?, ?, ?, ?, ?)";
$stmt = $db->prepare($query);

$scheduled_date = !empty($data['scheduled_date']) ? $data['scheduled_date'] : null;

if ($stmt->execute([$data['title'], $data['content'], $media_url, $scheduled_date, $_SESSION['user_id']])) {
    echo json_encode(['success' => true, 'message' => 'Article created successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create article.']);
}