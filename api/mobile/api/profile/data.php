<?php
// api/profile/data.php

// ✅ Always send CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// ✅ Properly handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ .  '/../../vendor/autoload.php';
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../auth_middleware.php'; 

$auth_data = get_auth_user();
$user_id = $auth_data->user_id;

try {
    // 1. Fetch User Details (with company name)
    $stmt_user = $pdo->prepare("
        SELECT u.full_name, u.email, u.phone, u.gender, u.is_employed, u.position, c.name AS company_name
        FROM users u
        LEFT JOIN companies c ON u.company_id = c.id
        WHERE u.id = ?
    ");
    $stmt_user->execute([$user_id]);
    $user_details = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user_details) {
        http_response_code(404);
        echo json_encode(['message' => 'User not found.']);
        exit;
    }

    // 2. Fetch Membership History
    $sql_membership = "
        SELECT ms.start_date, ms.end_date, ms.status, mt.name AS membership_name
        FROM membership_subscriptions ms
        JOIN membership_types mt ON ms.membership_type_id = mt.id
        WHERE ms.user_id = ? 
        ORDER BY ms.start_date DESC
    ";
    $stmt_membership = $pdo->prepare($sql_membership);
    $stmt_membership->execute([$user_id]);
    $membership_history = $stmt_membership->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Event History (events the user has bought a ticket for)
    $sql_events = "
        SELECT e.id, e.title, e.start_datetime
        FROM event_tickets et
        JOIN events e ON et.event_id = e.id
        WHERE et.user_id = ? AND et.status = 'bought'
        ORDER BY e.start_datetime DESC
    ";
    $stmt_events = $pdo->prepare($sql_events);
    $stmt_events->execute([$user_id]);
    $event_history = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Return all data
    echo json_encode([
        'user_details' => $user_details,
        'membership_history' => $membership_history,
        'event_history' => $event_history
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
