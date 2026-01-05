<?php
// Set the content type to JSON for all responses
header('Content-Type: application/json');

// Include the core application file which handles initialization and database connection
require_once dirname(__DIR__) . '/core/initialize.php';

// --- 1. VERIFY REQUEST METHOD ---
// This endpoint should only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST is accepted.']);
    exit;
}

// --- 2. RETRIEVE AND DECODE INPUT ---
// Get the raw JSON payload from the request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Check if the JSON is valid
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload provided.']);
    exit;
}

// --- 3. VALIDATE INPUT DATA ---
// Extract data into variables and perform checks
$schedule_id = $data['id'] ?? null;
$title = $data['title'] ?? null;
$start_datetime = $data['start_datetime'] ?? null;
$end_datetime = $data['end_datetime'] ?? null;
// Use an empty string as a default for the description if it's not provided
$description = $data['description'] ?? '';

// Ensure the schedule ID is a valid integer
if (!$schedule_id || !filter_var($schedule_id, FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid Schedule ID is required.']);
    exit;
}

// Ensure the title is not empty
if (empty(trim($title))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'The title field cannot be empty.']);
    exit;
}

// Optional: You could add more robust validation here for date formats if needed

// --- 4. PERFORM DATABASE UPDATE ---
try {
    // Establish a database connection
    $db = (new Database())->connect();

    // Prepare the SQL UPDATE statement to prevent SQL injection
    $sql = "UPDATE event_schedules 
            SET 
                title = :title, 
                start_datetime = :start_datetime, 
                end_datetime = :end_datetime, 
                description = :description
            WHERE 
                id = :id";

    $stmt = $db->prepare($sql);

    // Bind the parameters to the prepared statement
    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':start_datetime', $start_datetime);
    $stmt->bindParam(':end_datetime', $end_datetime);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);

    // Execute the statement
    if ($stmt->execute()) {
        // Check if any rows were actually affected by the update
        if ($stmt->rowCount() > 0) {
            // --- 5. SEND SUCCESS RESPONSE (CHANGES MADE) ---
            echo json_encode(['success' => true, 'message' => 'Schedule details updated successfully.']);
        } else {
            // --- 5. SEND SUCCESS RESPONSE (NO CHANGES) ---
            // This case occurs if the submitted data is identical to what's already in the database,
            // or if the schedule ID doesn't exist. It's not an error.
            echo json_encode(['success' => true, 'message' => 'No changes were made to the schedule details.']);
        }
    } else {
        // If execute() returns false, throw an exception for the catch block
        throw new Exception("The database query failed to execute.");
    }

} catch (PDOException $e) {
    // --- 6. HANDLE DATABASE ERRORS ---
    http_response_code(500); // Internal Server Error
    // Log the detailed error for debugging purposes (optional)
    // error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again later.']);
} catch (Exception $e) {
    // --- 6. HANDLE GENERAL ERRORS ---
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}