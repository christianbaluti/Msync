<?php
// /api/magazines/read.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

$params = [];
$filterClauses = [];

$search = $_GET['search'] ?? null;
if ($search) {
    $filterClauses[] = "title LIKE :search";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($filterClauses) ? 'WHERE ' . implode(' AND ', $filterClauses) : '';
$base_query = "FROM magazines $where_clause";

try {
    $stmt_count = $db->prepare("SELECT COUNT(id) $base_query");
    $stmt_count->execute($params);
    $total_records = (int)$stmt_count->fetchColumn();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    $query = "SELECT * $base_query ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt_table = $db->prepare($query);
    foreach ($params as $key => $val) $stmt_table->bindValue($key, $val);
    $stmt_table->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt_table->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt_table->execute();
    $magazines = $stmt_table->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $magazines,
        'total_records' => $total_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>