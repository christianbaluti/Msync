<?php
// /api/settings/read_app_updates.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

try {
    // Check if a single ID is requested
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM update_release WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fetch all
        $stmt = $db->prepare("SELECT * FROM update_release ORDER BY created_at DESC");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>