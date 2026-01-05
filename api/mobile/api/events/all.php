<?php
// api/events/all.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ .  '/../../vendor/autoload.php';
require_once __DIR__ . '/../db_connection.php'; 

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

try {
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'published' AND start_datetime >= NOW()");
    $total_events = $total_stmt->fetchColumn();

    $sql = "SELECT id, title, description, start_datetime, location, main_image 
            FROM events WHERE status = 'published'
            ORDER BY start_datetime ASC LIMIT :limit OFFSET :offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ START: URL Rewriting Logic (MODIFIED)
    
    // Get the base path from the script name (e.g., /api/mobile/api)
    $script_base = dirname(dirname($_SERVER['SCRIPT_NAME'])); 
    
    // Remove the unwanted part, as requested
    $correct_base = str_replace('/api/mobile/api', '', $script_base);
    
    // Build the final base URL (e.g., http://host.com)
    $api_base_url = 'http://' . $_SERVER['HTTP_HOST'] . $correct_base;

    foreach ($events as &$event) {
        if (!empty($event['main_image'])) {
            // The path will now be correct: http://host.com/uploads/events/image.jpg
            $event['main_image'] = $api_base_url . '/uploads/events/' . $event['main_image'];
        }
    }
    unset($event);
    // ✅ END: URL Rewriting Logic

    echo json_encode([
        'total' => (int)$total_events,
        'events' => $events
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>