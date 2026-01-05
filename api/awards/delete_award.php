<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'));
$award_id = $data->id ?? null;

if (!$award_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Award ID is required.']);
    exit;
}

$db = (new Database())->connect();
try {
    $db->beginTransaction();

    // First, delete all nominations for this award
    $stmt_noms = $db->prepare("DELETE FROM nominations WHERE award_id = :award_id");
    $stmt_noms->bindParam(':award_id', $award_id, PDO::PARAM_INT);
    $stmt_noms->execute();
    
    // Second, delete the award itself
    $stmt_award = $db->prepare("DELETE FROM awards WHERE id = :id");
    $stmt_award->bindParam(':id', $award_id, PDO::PARAM_INT);
    $stmt_award->execute();

    if ($stmt_award->rowCount() > 0) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Award and its nominations deleted successfully.']);
    } else {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Award not found.']);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}