<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

$schedule_id = $data->schedule_id ?? null;
$settings = $data->settings ?? null;

if (!$schedule_id || !$settings) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing schedule ID or settings data.']);
    exit;
}

try {
    $db = (new Database())->connect();
    $stmt = $db->prepare("UPDATE event_schedules SET settings = :settings WHERE id = :schedule_id");
    
    // Convert the settings object back to a JSON string for database storage
    $settings_json = json_encode($settings);

    $stmt->bindParam(':settings', $settings_json);
    $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save settings.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}