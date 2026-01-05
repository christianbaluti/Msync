<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'));
$nomination_id = $data->id ?? null;

if (!$nomination_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nomination ID is required.']);
    exit;
}

try {
    $db = (new Database())->connect();
    $stmt = $db->prepare("DELETE FROM nominations WHERE id = :id");
    $stmt->bindParam(':id', $nomination_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Nomination deleted successfully.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Nomination not found.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete nomination.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}