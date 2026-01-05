<?php
// --- CORS Headers ---
// Allow requests from any origin (*). Replace * with specific domains for better security.
header("Access-Control-Allow-Origin: *");
// Allow specific methods (GET is needed here, others might be needed for other endpoints)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// Allow specific headers in the request (adjust if your frontend sends others)
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle potential preflight OPTIONS requests (common with CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200); // OK status for preflight
    exit(); // Stop script execution for OPTIONS requests
}

// Set the response header to indicate JSON content *after* CORS headers
header('Content-Type: application/json');

// --- Includes ---
// Include necessary core files - adjust paths as needed
require_once dirname(__DIR__) . '/core/initialize.php'; // For Database connection
// require_once dirname(__DIR__) . '/core/auth.php'; // Uncomment if authentication is needed

// --- Database Connection ---
$db = (new Database())->connect();
if (!$db) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// --- Hardcoded Parameters ---
$event_id_filter = 104;
$cutoff_time_filter = '2025-10-23 14:00:00';

// --- Filtering ---
$where_clauses = [
    "et.event_id = :event_id",              // Filter by specific event
    "ea.checked_in = 1",                    // Ensure they are checked in
    "ea.checked_in_at < :cutoff_time"       // Filter by check-in time
];
$params = [
    ':event_id' => $event_id_filter,
    ':cutoff_time' => $cutoff_time_filter
];

// Example: Add search functionality like your companies API if needed
/*
if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search OR c.name LIKE :search)";
    $params[':search'] = $search_term;
}
*/

$where_sql = "WHERE " . implode(" AND ", $where_clauses); // Combine WHERE clauses

// --- Main Query (No Pagination) ---
// Selects user details, company name (if applicable), and check-in time
$query = "
    SELECT
        u.id AS user_id,
        u.full_name,
        u.email,
        u.phone,
        CASE
            WHEN u.is_employed = 1 THEN c.name
            ELSE NULL -- Changed to NULL for consistency, can be 'Not Employed' if preferred
        END AS company_name,
        ea.checked_in_at
    FROM
        event_attendance ea
    JOIN
        event_tickets et ON ea.ticket_id = et.id
    JOIN
        users u ON et.user_id = u.id
    LEFT JOIN                           -- Use LEFT JOIN for company
        companies c ON u.company_id = c.id
    $where_sql                          -- Apply filters
    ORDER BY
        ea.checked_in_at ASC            -- Order by check-in time
    -- LIMIT and OFFSET removed
";

// --- Count Query (Still useful to know total) ---
// Counts total matching records
$count_query = "
    SELECT COUNT(*)
    FROM
        event_attendance ea
    JOIN
        event_tickets et ON ea.ticket_id = et.id
    JOIN
        users u ON et.user_id = u.id
    -- No need to join companies table just for the count
    $where_sql                          -- Apply the same filters
";

try {
    // --- Prepare Statements ---
    $stmt = $db->prepare($query);
    $count_stmt = $db->prepare($count_query);

    // --- Bind Parameters ---
    // Bind filter parameters (common to both queries)
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
        $count_stmt->bindParam($key, $val);
    }
    // No pagination parameters to bind for the main query anymore

    // --- Execute Queries ---
    $stmt->execute();
    $count_stmt->execute();

    // --- Fetch Results ---
    $total_records = $count_stmt->fetchColumn();
    $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Output JSON Response (No pagination info) ---
    echo json_encode([
        'total_records' => (int)$total_records, // Total number of records found
        'attendees' => $attendees // Array containing all matching attendees
    ]);

} catch (PDOException $e) {
    // Handle potential database errors
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'error' => 'Database query failed',
        'message' => $e->getMessage() // Provide error message in development/debug mode
    ]);
}
?>