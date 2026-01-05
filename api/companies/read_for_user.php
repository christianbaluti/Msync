<?php
// Set the header to return JSON
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

try {
    $database = new Database();
    $db = $database->connect();

    // Query to get all companies (or active ones)
    // Adjust 'name' and 'id' to match your table columns
    $query = "SELECT id, name FROM companies ORDER BY name ASC"; 
    
    $stmt = $db->prepare($query);
    $stmt->execute();

    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($companies) {
        // Return in the format the JavaScript expects
        echo json_encode([
            'success' => true,
            'data'    => $companies 
        ]);
    } else {
        // Still return success, but with an empty array
        echo json_encode([
            'success' => true,
            'data'    => []
        ]);
    }

} catch (PDOException $e) {
    // Return an error format the JavaScript can understand
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>