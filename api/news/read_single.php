<?php
// /api/news/read_single.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { exit; }

$db = (new Database())->connect();
$stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $article]);