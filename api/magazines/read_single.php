<?php
// /api/magazines/read_single.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Magazine ID is required.']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM magazines WHERE id = ?");
    $stmt->execute([$id]);
    $magazine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$magazine) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Magazine not found.']);
    } else {
        echo json_encode(['success' => true, 'data' => $magazine]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>