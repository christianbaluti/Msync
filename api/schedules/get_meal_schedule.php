<?php
// File: /api/schedules/get_meal_schedule.php

header('Content-Type: application/json');
// UPDATED PATH:
require_once dirname(__DIR__) . '/core/database.php';

$db = (new Database())->connect();
$schedule_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$schedule_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Schedule ID.']);
    exit;
}

try {
    // 1. Get Schedule Details
    $stmt = $db->prepare("
        SELECT es.*, e.title as event_title, e.id as event_id
        FROM event_schedules es
        JOIN events e ON es.event_id = e.id
        WHERE es.id = :schedule_id AND es.type = 'meal'
    ");
    $stmt->execute([':schedule_id' => $schedule_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Meal schedule not found.']);
        exit;
    }

    $event_id = $schedule['event_id'];

    // 2. Get Statistics
    $stmt_total = $db->prepare("SELECT COUNT(id) as total FROM event_tickets WHERE event_id = :event_id AND status = 'bought'");
    $stmt_total->execute([':event_id' => $event_id]);
    $total = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt_statuses = $db->prepare("
        SELECT status, COUNT(id) as count
        FROM meal_cards
        WHERE schedule_id = :schedule_id
        GROUP BY status
    ");
    $stmt_statuses->execute([':schedule_id' => $schedule_id]);
    $status_counts = $stmt_statuses->fetchAll(PDO::FETCH_KEY_PAIR);

    $stats = [
        'total' => (int) $total,
        'activated' => (int) ($status_counts['about_to_collect'] ?? 0),
        'collected' => (int) ($status_counts['collected'] ?? 0),
    ];

    // 3. Get Coordinators (Facilitators)
    $stmt_fac = $db->prepare("
        SELECT u.id, u.full_name
        FROM schedule_facilitators sf
        JOIN users u ON sf.user_id = u.id
        WHERE sf.schedule_id = :schedule_id
        ORDER BY u.full_name
    ");
    $stmt_fac->execute([':schedule_id' => $schedule_id]);
    $facilitators = $stmt_fac->fetchAll(PDO::FETCH_ASSOC);

    // 4. Combine and send response
    echo json_encode([
        'success' => true,
        'schedule' => $schedule,
        'stats' => $stats,
        'facilitators' => $facilitators
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}