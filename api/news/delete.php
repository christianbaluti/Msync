<?php
// /api/news/delete.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents('php://input'));
if (empty($data->id)) { /* error */ }

$db = (new Database())->connect();

// First, get the media_url to delete the file
$stmt = $db->prepare("SELECT media_url FROM news WHERE id = ?");
$stmt->execute([$data->id]);
$article = $stmt->fetch();

if ($article && $article['media_url']) {
    $file_path = dirname(__DIR__, 2) . '/public' . $article['media_url'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Then, delete the database record
$stmt_delete = $db->prepare("DELETE FROM news WHERE id = ?");
if ($stmt_delete->execute([$data->id])) {
    echo json_encode(['success' => true, 'message' => 'Article deleted.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete article.']);
}