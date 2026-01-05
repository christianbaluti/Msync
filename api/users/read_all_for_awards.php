<?php
// Set the content type to JSON for the API response
header('Content-Type: application/json');
// Include the core initialization file
require_once dirname(__DIR__) . '/core/initialize.php';

try {
    // Establish a database connection
    $database = new Database();
    $db = $database->connect();

    // ==================== CHANGE START ====================
    // The SQL query is modified to include a LEFT JOIN to the companies table.
    // - `u.*`: Selects all columns from the users table.
    // - `c.name AS company_name`: Selects the 'name' column from the companies table and renames it to 'company_name'.
    // - A LEFT JOIN is used so that users who are NOT associated with a company (company_id is NULL) are still included in the results.
    $query = "
        SELECT 
            u.id, 
            u.full_name, 
            u.email, 
            u.phone,
            c.name AS company_name 
        FROM 
            users u
        LEFT JOIN 
            companies c ON u.company_id = c.id
        ORDER BY 
            u.full_name ASC
    ";
    // ===================== CHANGE END =====================

    // Prepare and execute the query
    $stmt = $db->prepare($query);
    $stmt->execute();

    // Fetch all results as an associative array
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return a successful response with the user data
    echo json_encode(['success' => true, 'data' => $users]);

} catch (PDOException $e) {
    // If a database error occurs, return a server error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Catch any other general errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}