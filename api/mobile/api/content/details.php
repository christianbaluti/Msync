<?php
// api/content/details.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ .  '/../../vendor/autoload.php';
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../auth_middleware.php';

// ✅ START: READ POST BODY FIRST
$data = json_decode(file_get_contents("php://input"));

if ($data === null) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Invalid or empty JSON body.']);
    exit;
}
// ✅ END: READ POST BODY

// ✅ CHANGED: Pass the decoded data to the auth function
$auth_data = get_auth_user($data);
$user_id = $auth_data->user_id;

// ✅ CHANGED: Read 'id' and 'type' from the $data object, not $_GET
$id = $data->id ?? null;
$type = $data->type ?? null;

if (!$id || !$type) {
    http_response_code(400); // This was the error you were getting
    echo json_encode(['message' => 'ID and type are required in the request body.']);
    exit;
}

$table = '';
$image_column = '';
$upload_subdirectory = ''; // Variable to hold the subdirectory

if ($type === 'event') {
    $table = 'events';
    $image_column = 'main_image';
    $upload_subdirectory = 'events'; // Set subdirectory for events
} elseif ($type === 'news') {
    $table = 'news';
    $image_column = 'media_url';
    $upload_subdirectory = 'news'; // Set subdirectory for news
} elseif ($type === 'communication') {
    $table = 'communications';
    $upload_subdirectory = 'comm'; // Set subdirectory for communications (if applicable)
}

if (empty($table)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid content type.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        echo json_encode(['message' => 'Item not found.']);
    } else {
        // If it's an event, fetch extra data
        if ($type === 'event') {
            // Check if user has a bought ticket
            $stmt_ticket = $pdo->prepare("SELECT id FROM event_tickets WHERE event_id = ? AND user_id = ? AND status IN ('bought', 'verified') LIMIT 1");
            $stmt_ticket->execute([$id, $user_id]);
            $item['user_has_ticket'] = $stmt_ticket->fetch() ? true : false;

            // Fetch event schedules
            $stmt_schedules = $pdo->prepare("SELECT id, type, title, description, start_datetime, end_datetime FROM event_schedules WHERE event_id = ? ORDER BY start_datetime ASC");
            $stmt_schedules->execute([$id]);
            $item['schedules'] = $stmt_schedules->fetchAll(PDO::FETCH_ASSOC);

            // Fetch stream data
            $stmt_stream = $pdo->prepare("SELECT youtube_embed_url, is_live FROM event_youtube_streams WHERE event_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt_stream->execute([$id]);
            $stream_data = $stmt_stream->fetch(PDO::FETCH_ASSOC);

            if ($stream_data) {
                $item['stream_url'] = $stream_data['youtube_embed_url'] ?? null;
                $item['is_live'] = !empty($stream_data['is_live']);
            } else {
                $item['stream_url'] = null;
                $item['is_live'] = false;
            }
        }

        // --- [MODIFIED IMAGE URL REWRITING] ---
        if (!empty($image_column) && !empty($upload_subdirectory) && !empty($item[$image_column])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            
            // Simpler base URL logic:
            // Assumes 'uploads' is in the root, and your API is something like app.imm.mw/api/...
            // This will create https://app.imm.mw/uploads/events/image.png
            $base_url = $scheme . '://' . $host;
            
            $image_filename = basename($item[$image_column]);
            $item[$image_column] = $base_url . '/uploads/' . $upload_subdirectory . '/' . $image_filename;
        }
        // --- [END MODIFICATION] ---


        // --- [NEW] Handle Communication Attachments ---
        if ($type === 'communication' && isset($item['attachments'])) {
             $attachments_json = $item['attachments'];
             if ($attachments_json) {
                 $attachments_data = json_decode($attachments_json, true);
                 if (json_last_error() === JSON_ERROR_NONE && isset($attachments_data['attachments']) && is_array($attachments_data['attachments'])) {
                     $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
                     $host = $_SERVER['HTTP_HOST'];
                     $comm_upload_path = $scheme . '://' . $host . '/uploads/comm/'; // Base path for communication uploads

                     foreach ($attachments_data['attachments'] as &$attachment) {
                         if (isset($attachment['url']) && !filter_var($attachment['url'], FILTER_VALIDATE_URL)) {
                             $filename = basename($attachment['url']);
                             $attachment['url'] = $comm_upload_path . $filename;
                         }
                     }
                     unset($attachment); 
                     $item['attachments'] = $attachments_data['attachments'];
                 } else {
                     $item['attachments'] = [];
                 }
             } else {
                 $item['attachments'] = [];
             }
        }
        // --- [END NEW] ---

        echo json_encode($item);
    }
} catch (Exception $e) {
    error_log("Error in details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred fetching details.']);
}
?>