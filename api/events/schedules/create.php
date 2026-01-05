<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php'; 

$data = json_decode(file_get_contents("php://input"));

if (empty($data->event_id) || empty($data->title) || empty($data->type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: event_id, title, type.']);
    exit();
}

// Automatically generate settings based on schedule type
$settings = null;
if ($data->type === 'voting') {
    $settings = json_encode([
        'allow_nominations' => true,
        'max_votes_per_user' => 1,
        'results_visibility' => 'hidden' // e.g., hidden, visible_after_close, live
    ]);
}
// Add other types if they need default settings
// else if ($data->type === 'training') { ... }

$db = (new Database())->connect();
$db->beginTransaction();

try {
    // Corrected query to use the 'settings' column instead of 'meta'
    $query = "INSERT INTO event_schedules (event_id, title, description, type, start_datetime, end_datetime, status, settings) 
              VALUES (:event_id, :title, :description, :type, :start_datetime, :end_datetime, :status, :settings)";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':event_id', $data->event_id);
    $stmt->bindParam(':title', $data->title);
    $stmt->bindParam(':description', $data->description);
    $stmt->bindParam(':type', $data->type);
    $stmt->bindParam(':start_datetime', $data->start_datetime);
    $stmt->bindParam(':end_datetime', $data->end_datetime);
    $stmt->bindParam(':status', $data->status);
    $stmt->bindParam(':settings', $settings); // Use the generated settings
    $stmt->execute();
    
    $schedule_id = $db->lastInsertId();

    // Link facilitators
    if (!empty($data->facilitators) && is_array($data->facilitators)) {
        $fac_query = "INSERT INTO schedule_facilitators (schedule_id, user_id) VALUES (:schedule_id, :user_id)";
        $fac_stmt = $db->prepare($fac_query);
        foreach ($data->facilitators as $user_id) {
            $fac_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
            $fac_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $fac_stmt->execute();
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Schedule item created successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    error_log($e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'Server Error: Could not create schedule.']);
}