<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

try {
    $db = (new Database())->connect();

    // This query joins all necessary tables to get data for the placeholders
    $query = "
        SELECT 
            ms.id as subscription_id,
            ms.membership_card_number,
            ms.end_date,
            u.full_name,
            mt.name as membership_type,
            c.name as company_name
        FROM 
            membership_subscriptions ms
        JOIN 
            users u ON ms.user_id = u.id
        JOIN 
            membership_types mt ON ms.membership_type_id = mt.id
        LEFT JOIN 
            companies c ON u.company_id = c.id
        WHERE 
            ms.status = 'active'
        ORDER BY 
            u.full_name ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $members]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}