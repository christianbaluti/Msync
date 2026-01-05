<?php
// /api/ads/create_page.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if (!has_permission('ads_create')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->connect();
$data = json_decode(file_get_contents("php://input"), true);

try {
    if (empty($data['label'])) throw new Exception("Label is required.");
    if (empty($data['code'])) throw new Exception("Code is required.");

    $stmt = $db->prepare("INSERT INTO ad_pages (label, code) VALUES (?, ?)");
    $stmt->execute([$data['label'], $data['code']]);

    echo json_encode(['success' => true, 'message' => 'App placement created.']);

} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Duplicate entry
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Error: This Code already exists.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>