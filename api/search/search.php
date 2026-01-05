<?php
// /api/search/search.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

try {
    $db = (new Database())->connect();

    $type = $_GET['type'] ?? '';
    $term = $_GET['term'] ?? '';

    // Basic validation
    if (empty($type) || empty($term)) {
        echo json_encode(['success' => false, 'data' => [], 'message' => 'Type and term are required.']);
        exit;
    }

    $results = [];
    $searchTerm = '%' . $term . '%';

    switch ($type) {
        case 'user':
            // Query to find users by name, joining with companies to get the company name.
            // COALESCE is used to show 'Not Employed' if the company_id is NULL.
            $query = "
                SELECT 
                    u.id, 
                    u.full_name, 
                    c.name AS company_name 
                FROM 
                    users u
                LEFT JOIN 
                    companies c ON u.company_id = c.id
                WHERE 
                    u.full_name LIKE :term
                ORDER BY 
                    u.full_name ASC
                LIMIT 10
            ";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':term', $searchTerm, PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the result text to include the company name
            foreach ($data as $row) {
                $displayText = $row['full_name'] . ' (' . ($row['company_name'] ?? 'Not Employed') . ')';
                $results[] = ['id' => $row['id'], 'text' => $displayText];
            }
            break;

        case 'company':
            // Simpler query to find companies by name
            $query = "
                SELECT id, name FROM companies 
                WHERE name LIKE :term 
                AND is_active = 1 
                ORDER BY name ASC 
                LIMIT 10
            ";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':term', $searchTerm, PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the result text
            foreach ($data as $row) {
                $results[] = ['id' => $row['id'], 'text' => $row['name']];
            }
            break;
            
        default:
             echo json_encode(['success' => false, 'data' => [], 'message' => 'Invalid search type specified.']);
             exit;
    }

    echo json_encode(['success' => true, 'data' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Search API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during the search operation.'
    ]);
}