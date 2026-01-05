<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';

$schedule_id = $_GET['id'] ?? 0;
if (!$schedule_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required.']);
    exit();
}

$db = (new Database())->connect();
$response = [
    'success' => true,
    'details' => null,
    'facilitators' => [],
    'materials' => []
];

try {
    // 1. Fetch main schedule details
    $schedule_query = "SELECT es.*, e.title as event_title 
                       FROM event_schedules es
                       JOIN events e ON es.event_id = e.id
                       WHERE es.id = :id AND es.type = 'training'";
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->execute([':id' => $schedule_id]);
    $response['details'] = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$response['details']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Training schedule not found.']);
        exit();
    }

    // 2. Fetch facilitators
    $fac_query = "SELECT u.id, u.full_name 
                  FROM schedule_facilitators sf
                  JOIN users u ON sf.user_id = u.id
                  WHERE sf.schedule_id = :id";
    $fac_stmt = $db->prepare($fac_query);
    $fac_stmt->execute([':id' => $schedule_id]);
    $response['facilitators'] = $fac_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch training materials
    $mats_query = "SELECT * FROM training_materials WHERE schedule_id = :id ORDER BY id DESC";
    $mats_stmt = $db->prepare($mats_query);
    $mats_stmt->execute([':id' => $schedule_id]);
    $response['materials'] = $mats_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}