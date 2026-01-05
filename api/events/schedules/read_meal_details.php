<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$schedule_id = $_GET['id'] ?? 0;
if (!$schedule_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required.']);
    exit();
}

$db = (new Database())->connect();
$response = ['success' => true];

try {
    // 1. Fetch main schedule details and event title
    $schedule_query = "SELECT es.*, e.title as event_title, e.id as event_id
                       FROM event_schedules es
                       JOIN events e ON es.event_id = e.id
                       WHERE es.id = :id AND es.type = 'meal'";
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->execute([':id' => $schedule_id]);
    $details = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$details) {
        throw new Exception('Meal schedule not found.', 404);
    }
    $response['details'] = $details;
    $event_id = $details['event_id'];

    // 2. Fetch statistics (remains the same)
    $total_stmt = $db->prepare("SELECT COUNT(id) FROM event_tickets WHERE event_id = :event_id");
    $total_stmt->execute([':event_id' => $event_id]);
    $response['stats']['total_attendees'] = $total_stmt->fetchColumn();
    
    $collected_stmt = $db->prepare("SELECT COUNT(mc.id) FROM meal_cards mc JOIN event_tickets et ON mc.ticket_id = et.id WHERE et.event_id = :event_id AND mc.status = 'collected'");
    $collected_stmt->execute([':event_id' => $event_id]);
    $response['stats']['meals_collected'] = $collected_stmt->fetchColumn();

    // 3. ADDED: Fetch facilitators for this schedule
    $fac_query = "SELECT u.id, u.full_name 
                  FROM schedule_facilitators sf
                  JOIN users u ON sf.user_id = u.id
                  WHERE sf.schedule_id = :id";
    $fac_stmt = $db->prepare($fac_query);
    $fac_stmt->execute([':id' => $schedule_id]);
    $response['facilitators'] = $fac_stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode($response);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}