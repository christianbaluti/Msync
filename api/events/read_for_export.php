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

// --- Hardcoded Parameter ---
$event_id_filter = 104;
$ticket_status_filter = 'bought'; // Filter for bought tickets

// --- Filtering ---
$where_clauses = [
    "et.event_id = :event_id",     // Filter by specific event
    "et.status = :ticket_status"   // Filter by ticket status
];
$params = [
    ':event_id' => $event_id_filter,
    ':ticket_status' => $ticket_status_filter
];

$where_sql = "WHERE " . implode(" AND ", $where_clauses); // Combine WHERE clauses

// --- Main Query (No Pagination) ---
// Selects user's full name and company name
$query = "
    SELECT
        u.full_name,
        c.name AS company_name -- Selects the company name
    FROM
        event_tickets et
    JOIN
        users u ON et.user_id = u.id -- Joins tickets to users
    LEFT JOIN                           -- Use LEFT JOIN in case a user doesn't have a company assigned
        companies c ON u.company_id = c.id -- Joins users to companies
    $where_sql                          -- Apply filters
    ORDER BY
        u.full_name ASC;                -- Optional: Orders the results alphabetically by user name
    -- No LIMIT or OFFSET
";

// --- Count Query (Still useful to know total) ---
// Counts total matching records
$count_query = "
    SELECT COUNT(*)
    FROM
        event_tickets et
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