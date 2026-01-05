<?php
// Set the content type to JSON for API responses
header('Content-Type: application/json');

// Include necessary core files
require_once dirname(__DIR__, 2) . '/core/initialize.php';

// Check for the required 'id' GET parameter
$schedule_id = $_GET['id'] ?? null;

if (!$schedule_id) {
    // If no ID is provided, return a 400 Bad Request error
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required.']);
    exit();
}

try {
    // Establish a database connection
    $db = (new Database())->connect();

    // This query fetches all details for a specific schedule item.
    // It uses LEFT JOINs to ensure a schedule is returned even if it has no facilitators.
    // GROUP_CONCAT is used to aggregate facilitator IDs into a single comma-separated string.
    $query = "
        SELECT 
            es.id,
            es.event_id,
            es.type,
            es.title,
            es.description,
            es.start_datetime,
            es.end_datetime,
            es.status,
            es.settings,
            GROUP_CONCAT(sf.user_id) AS facilitators
        FROM 
            event_schedules es
        LEFT JOIN 
            schedule_facilitators sf ON es.id = sf.schedule_id
        WHERE 
            es.id = :id
        GROUP BY
            es.id
    ";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);
    $stmt->execute();

    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($schedule) {
        // The JavaScript expects 'facilitators' to be an array of numbers.
        // The database query returns it as a comma-separated string (e.g., "1,5,12") or NULL.
        // We need to convert it here.
        if ($schedule['facilitators']) {
            // Explode the string into an array of strings, then convert each to an integer
            $schedule['facilitators'] = array_map('intval', explode(',', $schedule['facilitators']));
        } else {
            // If there are no facilitators, ensure it's an empty array for consistency
            $schedule['facilitators'] = [];
        }

        // The 'settings' column is stored as a JSON string. We should decode it before sending.
        if ($schedule['settings']) {
            $schedule['settings'] = json_decode($schedule['settings']);
        } else {
            $schedule['settings'] = null; // Or an empty object: new stdClass();
        }


        // Return the final data as JSON
        echo json_encode($schedule);
    } else {
        // If no schedule with that ID was found, return a 404 Not Found error
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Schedule item not found.']);
    }

} catch (PDOException $e) {
    // Handle potential database errors
    http_response_code(500);
    error_log("Database Error: " . $e->getMessage()); // Log the actual error for debugging
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
} catch (Exception $e) {
    // Handle other general errors
    http_response_code(500);
    error_log("General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected server error occurred.']);
}