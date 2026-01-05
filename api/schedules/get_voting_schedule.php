<?php
// File: /api/schedules/get_voting_schedule.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$schedule_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$schedule_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Schedule ID.']);
    exit;
}

try {
    // 1. Fetch schedule details
    $stmt = $db->prepare("
        SELECT es.*, e.title as event_title, e.id as event_id 
        FROM event_schedules es 
        JOIN events e ON es.event_id = e.id 
        WHERE es.id = ?
    ");
    $stmt->execute([$schedule_id]);
    $schedule_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule_details) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Schedule not found.']);
        exit;
    }
    
    // 2. Fetch facilitators for this schedule
    $stmt_fac = $db->prepare("
        SELECT u.id, u.full_name 
        FROM schedule_facilitators sf
        JOIN users u ON sf.user_id = u.id
        WHERE sf.schedule_id = ?
        ORDER BY u.full_name
    ");
    $stmt_fac->execute([$schedule_id]);
    $facilitators = $stmt_fac->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. (We will add election seats here later)

    echo json_encode([
        'success' => true, 
        'data' => [
            'details' => $schedule_details,
            'facilitators' => $facilitators,
            'seats' => [] // Placeholder for the next step
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}