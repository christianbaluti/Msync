<?php
// /api/members/read_for_members.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Filtering
$where_clauses = "1=1";
$params = [];

if (!empty($_GET['membership_card_number'])) {
    $where_clauses .= " AND ms.membership_card_number LIKE :card_number";
    $params[':card_number'] = '%' . $_GET['membership_card_number'] . '%';
}
if (!empty($_GET['full_name'])) {
    $where_clauses .= " AND u.full_name LIKE :full_name";
    $params[':full_name'] = '%' . $_GET['full_name'] . '%';
}
if (!empty($_GET['company_name'])) {
    $where_clauses .= " AND c.name LIKE :company_name";
    $params[':company_name'] = '%' . $_GET['company_name'] . '%';
}
if (!empty($_GET['status'])) {
    $where_clauses .= " AND ms.status = :status";
    $params[':status'] = $_GET['status'];
}

try {
    // Get total records for pagination
    // **FIX**: Added JOIN for membership_types to ensure filtering works if needed in future
    $count_query = "SELECT COUNT(ms.id) 
                    FROM membership_subscriptions ms 
                    LEFT JOIN users u ON ms.user_id = u.id 
                    LEFT JOIN companies c ON u.company_id = c.id
                    LEFT JOIN membership_types mt ON ms.membership_type_id = mt.id
                    WHERE $where_clauses";
    $stmt_count = $db->prepare($count_query);
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Get paginated data
    // **FIX**: Updated the SELECT statement and added a JOIN to membership_types
    $data_query = "SELECT 
                        ms.id AS subscription_id,
                        ms.membership_card_number,
                        ms.status,
                        ms.end_date,
                        u.id AS user_id,
                        u.full_name,
                        u.email,
                        c.name AS company_name,
                        mt.name AS type_name,
                        NULL AS user_avatar -- Your schema doesn't have an avatar, so we send NULL. JS will use a placeholder.
                   FROM membership_subscriptions ms
                   LEFT JOIN users u ON ms.user_id = u.id
                   LEFT JOIN companies c ON u.company_id = c.id
                   LEFT JOIN membership_types mt ON ms.membership_type_id = mt.id
                   WHERE $where_clauses
                   ORDER BY ms.created_at DESC
                   LIMIT :limit OFFSET :offset";
    
    $stmt_data = $db->prepare($data_query);

    // Bind filter parameters
    foreach ($params as $key => &$val) {
        $stmt_data->bindParam($key, $val);
    }

    // Bind pagination parameters separately
    $stmt_data->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt_data->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt_data->execute();
    $members = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $members,
        'pagination' => [
            'currentPage' => $page,
            'limit' => $limit,
            'totalRecords' => (int)$total_records,
            'totalPages' => (int)$total_pages
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database Error in /api/members/read_for_members.php: " . $e->getMessage());
    // Send a generic error message to the client
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred while fetching members.']);
}