<?php
/**
 * Single File API for Event App
 * * Usage:
 * 1. List Events:   GET /api.php?action=events
 * 2. Event Details: GET /api.php?action=event_details&id=104
 */

// 1. Configuration & Headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); // Allow any app to access
header("Access-Control-Allow-Methods: GET");

// --- Database Credentials (UPDATE THESE) ---
$db_host = 'localhost';
$db_name = 'hallmark_membersync';
$db_user = 'hallmark_membersync';
$db_pass = 'hallmark_membersync';
$base_image_url = "https://membersync.hallmark.mw/uploads/events/";

// 2. Database Connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}

// 3. Router Logic
$action = isset($_GET['action']) ? $_GET['action'] : 'events';

switch ($action) {
    case 'events':
        fetchEvents($pdo, $base_image_url);
        break;
    case 'event_details':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        fetchEventDetails($pdo, $id, $base_image_url);
        break;
    default:
        echo json_encode(["error" => "Invalid action parameter"]);
        break;
}

// 4. Functions

function fetchEvents($pdo, $baseUrl) {
    // Query to get list of published events
    // We alias columns to match common App JSON keys (imageUrl, date, etc.)
    $sql = "SELECT 
                id, 
                title, 
                description, 
                DATE_FORMAT(start_datetime, '%M %d, %Y • %h:%i %p') as date,
                location, 
                CONCAT(:base_url, main_image) as imageUrl 
            FROM events 
            WHERE status = 'published' 
            ORDER BY start_datetime DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['base_url' => $baseUrl]);
        $events = $stmt->fetchAll();
        
        echo json_encode($events);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

function fetchEventDetails($pdo, $id, $baseUrl) {
    if ($id <= 0) {
        echo json_encode(["error" => "Invalid Event ID"]);
        return;
    }

    try {
        // A. Get Main Event Info
        $eventSql = "SELECT 
                        id, 
                        title, 
                        description, 
                        DATE_FORMAT(start_datetime, '%M %d, %Y • %h:%i %p') as date,
                        location, 
                        CONCAT(:base_url, main_image) as imageUrl 
                     FROM events 
                     WHERE id = :id";
        
        $stmt = $pdo->prepare($eventSql);
        $stmt->execute(['id' => $id, 'base_url' => $baseUrl]);
        $event = $stmt->fetch();

        if (!$event) {
            http_response_code(404);
            echo json_encode(["error" => "Event not found"]);
            return;
        }

        // B. Get Schedule & Speakers
        // We join with schedule_facilitators and users to get speaker names
        $scheduleSql = "SELECT 
                            es.id,
                            DATE_FORMAT(es.start_datetime, '%H:%i') as time,
                            es.title,
                            es.type,
                            es.description as activity_desc,
                            COALESCE(GROUP_CONCAT(u.full_name SEPARATOR ', '), 'TBA') as speaker
                        FROM event_schedules es
                        LEFT JOIN schedule_facilitators sf ON es.id = sf.schedule_id
                        LEFT JOIN users u ON sf.user_id = u.id
                        WHERE es.event_id = :id AND es.status = 'active'
                        GROUP BY es.id
                        ORDER BY es.start_datetime ASC";

        $schedStmt = $pdo->prepare($scheduleSql);
        $schedStmt->execute(['id' => $id]);
        $schedule = $schedStmt->fetchAll();

        // Combine
        $event['schedule'] = $schedule;

        echo json_encode($event);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>