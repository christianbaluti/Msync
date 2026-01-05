<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

function get_current_user_id() {
    return $_SESSION['user_id'] ?? 0;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

$award_id = $data->award_id ?? null;
$nominee_user_id = $data->nominee_user_id ?? null;
$nominee_company_id = $data->nominee_company_id ?? null;
$nomination_text = $data->nomination_text ?? '';
$nominated_by = get_current_user_id();

if (!$nominated_by) {
     http_response_code(401);
     echo json_encode(['success' => false, 'message' => 'Authentication required.']);
     exit;
}

if (!$award_id || (!$nominee_user_id && !$nominee_company_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing award ID or nominee ID.']);
    exit;
}

try {
    $db = (new Database())->connect();
    $stmt = $db->prepare("
        INSERT INTO nominations (award_id, nominee_user_id, nominee_company_id, nominated_by, nomination_text) 
        VALUES (:award_id, :nominee_user_id, :nominee_company_id, :nominated_by, :nomination_text)
    ");
    
    $stmt->bindParam(':award_id', $award_id, PDO::PARAM_INT);
    $stmt->bindParam(':nominee_user_id', $nominee_user_id, $nominee_user_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':nominee_company_id', $nominee_company_id, $nominee_company_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':nominated_by', $nominated_by, PDO::PARAM_INT);
    $stmt->bindParam(':nomination_text', $nomination_text);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Nomination created successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create nomination.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}