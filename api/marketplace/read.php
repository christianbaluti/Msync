<?php
// /api/orders/read.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

// --- FILTERS ---
$params = [];
$filterClauses = [];

// Date filters
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
if ($startDate) {
    $filterClauses[] = "o.created_at >= :startDate";
    $params[':startDate'] = $startDate . ' 00:00:00';
}
if ($endDate) {
    $filterClauses[] = "o.created_at <= :endDate";
    $params[':endDate'] = $endDate . ' 23:59:59';
}

// Status filter
$status = $_GET['status'] ?? null;
if ($status) {
    $filterClauses[] = "o.status = :status";
    $params[':status'] = $status;
}

// Search filter
$search = $_GET['search'] ?? null;
if ($search) {
    $filterClauses[] = "(u.full_name LIKE :search OR u.email LIKE :search OR c.name LIKE :search OR o.id = :search_id)";
    $params[':search'] = "%$search%";
    // Check if search term is purely numeric to also search by ID
    $params[':search_id'] = is_numeric($search) ? (int)$search : 0;
}

// Build WHERE clause
$where_clause = !empty($filterClauses) ? 'WHERE ' . implode(' AND ', $filterClauses) : '';

// Base query
$base_query = "FROM marketplace_orders o
               LEFT JOIN users u ON o.user_id = u.id
               LEFT JOIN companies c ON o.company_id = c.id
               $where_clause";

try {
    // Count total records for pagination
    $stmt_count = $db->prepare("SELECT COUNT(o.id) $base_query");
    $stmt_count->execute($params);
    $total_records = (int)$stmt_count->fetchColumn();

    // Fetch paginated data
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    $query = "SELECT 
                o.id, o.created_at, o.total_amount, o.status,
                COALESCE(u.full_name, c.name) as customer_name,
                COALESCE(u.email, c.email) as customer_email
              $base_query
              ORDER BY o.created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt_table = $db->prepare($query);
    
    // Bind all filter params
    foreach ($params as $key => $val) {
        $stmt_table->bindValue($key, $val);
    }
    // Bind pagination params
    $stmt_table->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt_table->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt_table->execute();
    $orders = $stmt_table->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total_records' => $total_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Query Error: ' . $e->getMessage()]);
}
?>