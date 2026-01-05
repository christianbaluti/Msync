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
$nominee_user_id = $data->nominee_user_id ?? null;
$nominee_company_id = $data->nominee_company_id ?? null;
$nomination_text = $data->nomination_text ?? '';

if (!$nomination_id || (!$nominee_user_id && !$nominee_company_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing nomination ID or nominee ID.']);
    exit;
}

try {
    $db = (new Database())->connect();
    $stmt = $db->prepare("
        UPDATE nominations 
        SET nominee_user_id = :nominee_user_id, 
            nominee_company_id = :nominee_company_id, 
            nomination_text = :nomination_text
        WHERE id = :id
    ");
    
    $stmt->bindParam(':id', $nomination_id, PDO::PARAM_INT);
    $stmt->bindParam(':nominee_user_id', $nominee_user_id, $nominee_user_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':nominee_company_id', $nominee_company_id, $nominee_company_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':nomination_text', $nomination_text);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Nomination updated successfully.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Nomination not found or no changes made.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update nomination.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}