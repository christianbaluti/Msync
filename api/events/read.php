<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = [];
$params = [];

if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(e.title LIKE :search OR e.location LIKE :search)";
    $params[':search'] = $search_term;
}
if (!empty($_GET['status'])) {
    $where_clauses[] = "e.status = :status";
    $params[':status'] = $_GET['status'];
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Main Query (Added main_image)
$query = "
    SELECT 
        e.id, e.title, e.location, e.start_datetime, e.end_datetime, e.status, e.main_image,
        (SELECT COUNT(*) FROM event_tickets WHERE event_id = e.id AND (status = 'bought' OR status = 'verified')) as ticket_count
    FROM events e
    $where_sql
    ORDER BY e.start_datetime DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($query);

// Count Query
$count_query = "SELECT COUNT(*) FROM events e $where_sql";
$count_stmt = $db->prepare($count_query);

// Bind & Execute
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
    $count_stmt->bindParam($key, $val);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$count_stmt->execute();

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'total_records' => $count_stmt->fetchColumn(),
    'limit' => $limit,
    'events' => $events
]);