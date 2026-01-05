<?php
// /api/audit/read.php
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
    $filterClauses[] = "al.created_at >= :startDate";
    $params[':startDate'] = $startDate . ' 00:00:00';
}
if ($endDate) {
    $filterClauses[] = "al.created_at <= :endDate";
    $params[':endDate'] = $endDate . ' 23:59:59';
}

// Actor Type filter
$actorType = $_GET['actorType'] ?? null;
if ($actorType) {
    $filterClauses[] = "al.actor_type = :actorType";
    $params[':actorType'] = $actorType;
}

// Actor Search filter
$actorSearch = $_GET['actorSearch'] ?? null;
if ($actorSearch) {
    $filterClauses[] = "(u.full_name LIKE :actorSearch OR c.name LIKE :actorSearch)";
    $params[':actorSearch'] = "%$actorSearch%";
}

// Action Search filter
$actionSearch = $_GET['actionSearch'] ?? null;
if ($actionSearch) {
    $filterClauses[] = "al.action LIKE :actionSearch";
    $params[':actionSearch'] = "%$actionSearch%";
}

// Build WHERE clause
$where_clause = !empty($filterClauses) ? 'WHERE ' . implode(' AND ', $filterClauses) : '';

// Base query
$base_query = "FROM audit_logs al
               LEFT JOIN users u ON al.actor_type = 'user' AND al.actor_id = u.id
               LEFT JOIN companies c ON al.actor_type = 'company' AND al.actor_id = c.id
               $where_clause";

try {
    // Count total records for pagination
    $stmt_count = $db->prepare("SELECT COUNT(al.id) $base_query");
    $stmt_count->execute($params);
    $total_records = (int)$stmt_count->fetchColumn();

    // Fetch paginated data
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15; // Match JS
    $offset = ($page - 1) * $limit;

    $query = "SELECT 
                al.id, al.actor_type, al.actor_id, al.action, al.object_type, al.object_id, al.meta, al.created_at,
                COALESCE(u.full_name, c.name) as actor_name
              $base_query
              ORDER BY al.created_at DESC
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
    $logs = $stmt_table->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $logs,
        'total_records' => $total_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Audit Log Read Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database Query Error: ' . $e->getMessage()]);
}
?>