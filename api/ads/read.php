<?php
// /api/ads/read.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

$params = [];
$filterClauses = [];

$status = $_GET['status'] ?? null;
if ($status) {
    $filterClauses[] = "a.status = :status";
    $params[':status'] = $status;
}

$search = $_GET['search'] ?? null;
if ($search) {
    $filterClauses[] = "a.title LIKE :search";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($filterClauses) ? 'WHERE ' . implode(' AND ', $filterClauses) : '';

$base_query = "FROM ads a $where_clause";

try {
    $stmt_count = $db->prepare("SELECT COUNT(a.id) $base_query");
    $stmt_count->execute($params);
    $total_records = (int)$stmt_count->fetchColumn();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Join with ad_pages and ad_placements to get placement info
    $query = "SELECT 
                a.*,
                GROUP_CONCAT(CONCAT(apages.label, ' (', apl.position, ')') SEPARATOR ', ') as placements
              FROM ads a
              LEFT JOIN ad_placements apl ON a.id = apl.ad_id
              LEFT JOIN ad_pages apages ON apl.page_id = apages.id
              $where_clause
              GROUP BY a.id
              ORDER BY a.created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt_table = $db->prepare($query);
    foreach ($params as $key => $val) $stmt_table->bindValue($key, $val);
    $stmt_table->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt_table->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt_table->execute();
    $ads = $stmt_table->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $ads,
        'total_records' => $total_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>