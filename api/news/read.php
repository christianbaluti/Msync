<?php
// /api/news/read.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

// Filtering
$where_clauses = [];
$params = []; // Use named parameters for clarity

// 1. Handle 'search' (was 'title')
if (!empty($_GET['search'])) {
    $where_clauses[] = "n.title LIKE :search";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

// 2. Handle 'date'
if (!empty($_GET['date'])) {
    // Only need one placeholder, use it twice in the query
    $where_clauses[] = "(DATE(n.scheduled_date) = :date OR DATE(n.created_at) = :date)";
    $params[':date'] = $_GET['date'];
}

// 3. Handle 'status'
if (!empty($_GET['status'])) {
    if ($_GET['status'] === 'published') {
        $where_clauses[] = "(n.scheduled_date IS NULL OR n.scheduled_date <= NOW())";
    } elseif ($_GET['status'] === 'scheduled') {
        $where_clauses[] = "n.scheduled_date > NOW()";
    }
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

try {
    // --- 4. Get Total Records (for pagination) ---
    // We count based on the same filters, but before grouping and limiting
    $count_query = "SELECT COUNT(n.id) FROM news n $where_sql";
    $stmt_count = $db->prepare($count_query);
    $stmt_count->execute($params);
    $total_records = (int)$stmt_count->fetchColumn();

    // --- 5. Get Pagination Parameters ---
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // --- 6. Get Paginated Data ---
    $query = "SELECT n.*, COUNT(nv.id) as view_count
              FROM news n
              LEFT JOIN news_views nv ON n.id = nv.news_id
              $where_sql
              GROUP BY n.id
              ORDER BY n.created_at DESC
              LIMIT :limit OFFSET :offset"; // Add LIMIT and OFFSET
              
    $stmt = $db->prepare($query);
    
    // Bind all filtering parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind pagination parameters
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 7. Return JSON in the expected format ---
    echo json_encode([
        'success' => true, 
        'data' => $data, 
        'total_records' => $total_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Query Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'General Error: ' . $e->getMessage()]);
}
?>