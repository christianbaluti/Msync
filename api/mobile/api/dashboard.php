<?php
// api/dashboard.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/auth_middleware.php'; 

date_default_timezone_set('Africa/Blantyre');

// ✅ START: MOVED TRY BLOCK UP AND ADDED AUTH CHECK
try {
    $auth_data = get_auth_user();

    // Check if authentication failed
    if (!$auth_data || !isset($auth_data->user_id)) {
        http_response_code(401); // Unauthorized
        echo json_encode(['message' => 'Authentication failed. Invalid or missing token.']);
        exit; // Stop script execution
    }

    $user_id = $auth_data->user_id;
    // ✅ END: AUTH CHECK

    // ... (User and membership query remains the same) ...
    $sql_user = "
        SELECT 
            u.full_name, 
            ms.status AS membership_status, 
            ms.end_date AS expiry_date, 
            ms.membership_card_number, 
            mt.name AS membership_type, 
            c.name AS company_name
        FROM users u
        LEFT JOIN membership_subscriptions ms 
            ON u.id = ms.user_id
        LEFT JOIN membership_types mt 
            ON ms.membership_type_id = mt.id
        LEFT JOIN companies c 
            ON u.company_id = c.id
        WHERE u.id = ?
        ORDER BY 
            (ms.status = 'active') DESC,   -- active first
            ms.end_date DESC,              -- if none active, pick most recent
            ms.created_at DESC
        LIMIT 1
        ";

    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([$user_id]);
    $user_result = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user_result) {
        http_response_code(404);
        echo json_encode(['message' => 'User not found.']);
        exit;
    }

    // Fetch events, news, and communications
    $sql_events = "SELECT id, title, start_datetime, location, main_image FROM events WHERE status = 'published' LIMIT 6";
    $stmt_events = $pdo->query($sql_events);
    $recent_events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

    $sql_news = "SELECT id, title, content, created_at, media_url FROM news ORDER BY created_at DESC LIMIT 6";
    $stmt_news = $pdo->query($sql_news);
    $recent_news = $stmt_news->fetchAll(PDO::FETCH_ASSOC);

    $sql_comms = "SELECT id, subject, body, sent_at FROM communications ORDER BY sent_at DESC LIMIT 6";
    $stmt_comms = $pdo->query($sql_comms);
    $recent_comms = $stmt_comms->fetchAll(PDO::FETCH_ASSOC);

    // ... (URL Rewriting logic) ...
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $api_base_url = $protocol . '://' . $_SERVER['HTTP_HOST'];

    foreach ($recent_events as &$event) {
        if (!empty($event['main_image'])) {
            $event['main_image'] = $api_base_url . '/uploads/events/'. $event['main_image'];
        }
    }
    unset($event);

    foreach ($recent_news as &$news_item) {
        if (!empty($news_item['media_url'])) {
            $news_item['media_url'] = $api_base_url . $news_item['media_url'];
        }
    }
    unset($news_item);
    
    // ... (Structuring the final response data) ...
    $response_data = [
        'full_name' => $user_result['full_name'],
        'membership' => null,
        'recent_events' => $recent_events,
        'news' => $recent_news,
        'communications' => $recent_comms
    ];
    if ($user_result['membership_type']) {
        $response_data['membership'] = [
            'type' => $user_result['membership_type'],
            'id' => $user_result['membership_card_number'],
            'company' => $user_result['company_name'],
            'status' => $user_result['membership_status'],
            'expiry_date' => $user_result['expiry_date']
        ];
    }

    http_response_code(200);
    echo json_encode($response_data);

} catch (Exception $e) {
    // This will now catch any database errors OR errors from the auth middleware
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>