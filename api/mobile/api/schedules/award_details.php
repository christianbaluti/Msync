<?php
// api/schedules/awards_details.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // ✅ Allow POST
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php'; 
require __DIR__ . '/../db_connection.php';      
require __DIR__ . '/../auth_middleware_for_all.php';    
require __DIR__ . '/../eligibility_helper.php'; 

// ✅ START: Read POST body ONCE
$data = json_decode(file_get_contents("php://input"));
if ($data === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
    exit;
}
// ✅ END: Read POST body

// ✅ CHANGED: Pass $data to auth function
$auth_data = get_auth_user($data);
$user_id = $auth_data->user_id;

// ✅ CHANGED: Read 'id' from $data, not $_GET
$schedule_id = $data->id ?? null;

if (!$schedule_id || !is_numeric($schedule_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid Schedule ID (id) is required in the request body.']);
    exit;
}
$schedule_id = intval($schedule_id);

try {
    // ... (rest of your logic is correct and remains unchanged) ...
    
    // 1. Fetch Schedule Details
    $stmt_schedule = $pdo->prepare("SELECT title, description, settings, status, stage FROM event_schedules WHERE id = ? AND type = 'awards'");
    $stmt_schedule->execute([$schedule_id]);
    $schedule = $stmt_schedule->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Awards schedule not found.']);
        exit;
    }

     // 2. Check if active
    if ($schedule['status'] !== 'active') {
        echo json_encode([
            'success' => true,
            'schedule_id' => $schedule_id,
            'title' => $schedule['title'] ?? 'Awards Ceremony',
            'description' => $schedule['description'] ?? 'This awards nomination is not currently active.',
            'stage' => 'closed',
            'is_eligible_to_nominate' => false,
            'awards' => []
        ]);
        exit;
    }

    $settings = json_decode($schedule['settings'] ?? '{}', true);
    $nominator_eligibility_rules = $settings['nominator_eligibility'] ?? null;
    $stage = $schedule['stage'] ?? 'nomination'; 

    // 3. Determine Nominator Eligibility
    $is_eligible_to_nominate = check_user_eligibility($pdo, $user_id, $nominator_eligibility_rules);

    // 4. Fetch Awards for this Schedule
    $stmt_awards = $pdo->prepare("SELECT id, title, description, for_entity FROM awards WHERE schedule_id = ?");
    $stmt_awards->execute([$schedule_id]);
    $awards = $stmt_awards->fetchAll(PDO::FETCH_ASSOC);

    $stmt_check_nom = $pdo->prepare("SELECT 1 FROM nominations WHERE award_id = :award_id AND nominated_by = :user_id LIMIT 1");

    foreach ($awards as &$award) {
        $award['description'] = $award['description'] ?? '';
        $award['title'] = $award['title'] ?? 'Untitled Award';
        $stmt_check_nom->execute([':award_id' => $award['id'], ':user_id' => $user_id]);
        $award['user_has_nominated'] = $stmt_check_nom->fetchColumn() > 0;
    }
    unset($award);

    // 5. Send Final Response
    echo json_encode([
        'success' => true,
        'schedule_id' => $schedule_id,
        'title' => $schedule['title'] ?? 'Awards Ceremony',
        'description' => $schedule['description'] ?? '',
        'stage' => $stage, 
        'is_eligible_to_nominate' => $is_eligible_to_nominate,
        'awards' => $awards
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Awards Details PDO Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error fetching award details.']);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Awards Details General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred: ' . $e->getMessage()]);
}
?>