<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php'; // Adjust path
require_once dirname(__DIR__, 2) . '/core/auth.php'; // Adjust path

$data = json_decode(file_get_contents("php://input"));

if (empty($data->event_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit();
}

$db = (new Database())->connect();
$db->beginTransaction();

try {
    // Check if a record already exists
    $existing_id = $data->id ?? null;
    if (empty($existing_id)) {
        $check_query = "SELECT id FROM event_youtube_streams WHERE event_id = :event_id LIMIT 1";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':event_id', $data->event_id, PDO::PARAM_INT);
        $check_stmt->execute();
        $existing_id = $check_stmt->fetchColumn();
    }

    // Prepare data, setting empty strings to NULL for optional fields
    $youtube_video_id = empty($data->youtube_video_id) ? null : $data->youtube_video_id;
    $youtube_embed_url = empty($data->youtube_embed_url) ? null : $data->youtube_embed_url;
    $stream_key = empty($data->stream_key) ? null : $data->stream_key;
    $privacy_status = $data->privacy_status ?? 'unlisted';
    $is_live = $data->is_live ?? 0;
    $started_at = empty($data->started_at) ? null : $data->started_at;
    $ended_at = empty($data->ended_at) ? null : $data->ended_at;

    if ($existing_id) {
        // --- UPDATE ---
        $query = "UPDATE event_youtube_streams SET
                    youtube_video_id = :youtube_video_id,
                    youtube_embed_url = :youtube_embed_url,
                    stream_key = :stream_key,
                    privacy_status = :privacy_status,
                    is_live = :is_live,
                    started_at = :started_at,
                    ended_at = :ended_at
                  WHERE id = :id AND event_id = :event_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $existing_id, PDO::PARAM_INT);
        $stmt->bindParam(':event_id', $data->event_id, PDO::PARAM_INT);
        $message = 'Stream configuration updated successfully.';

    } else {
        // --- INSERT ---
        $query = "INSERT INTO event_youtube_streams 
                    (event_id, youtube_video_id, youtube_embed_url, stream_key, privacy_status, is_live, started_at, ended_at)
                  VALUES 
                    (:event_id, :youtube_video_id, :youtube_embed_url, :stream_key, :privacy_status, :is_live, :started_at, :ended_at)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':event_id', $data->event_id, PDO::PARAM_INT);
        $message = 'Stream configuration created successfully.';
    }

    // Bind parameters for both queries
    $stmt->bindParam(':youtube_video_id', $youtube_video_id);
    $stmt->bindParam(':youtube_embed_url', $youtube_embed_url);
    $stmt->bindParam(':stream_key', $stream_key);
    $stmt->bindParam(':privacy_status', $privacy_status);
    $stmt->bindParam(':is_live', $is_live, PDO::PARAM_INT);
    $stmt->bindParam(':started_at', $started_at);
    $stmt->bindParam(':ended_at', $ended_at);

    if ($stmt->execute()) {
        $new_id = $existing_id ? $existing_id : $db->lastInsertId();
        $db->commit();
        echo json_encode(['success' => true, 'message' => $message, 'new_id' => $new_id]);
    } else {
        throw new Exception('Database execution failed.');
    }

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while saving the configuration.']);
}