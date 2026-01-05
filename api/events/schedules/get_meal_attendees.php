<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

// --- Parameters ---
$schedule_id = $_GET['schedule_id'] ?? 0;
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;
$q = $_GET['q'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

if (!$schedule_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required.']);
    exit();
}

// Get the event_id from the schedule_id
$db = (new Database())->connect();
$event_stmt = $db->prepare("SELECT event_id FROM event_schedules WHERE id = :schedule_id");
$event_stmt->execute([':schedule_id' => $schedule_id]);
$event_id = $event_stmt->fetchColumn();

if (!$event_id) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Schedule not found.']);
    exit();
}

// --- Build Query ---
$params = [':event_id' => $event_id];
$where_clauses = [];

if (!empty($q)) {
    $where_clauses[] = "(u.full_name LIKE :q OR u.email LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

if ($status_filter !== 'all') {
    if ($status_filter === 'inactive') {
        $where_clauses[] = "(mc.status IS NULL OR mc.status = 'inactive')";
    } else {
        $where_clauses[] = "mc.status = :status";
        $params[':status'] = $status_filter;
    }
}

$where_sql = count($where_clauses) > 0 ? ' AND ' . implode(' AND ', $where_clauses) : '';

// --- Get Total Count for Pagination ---
$count_sql = "SELECT COUNT(t.id) 
              FROM event_tickets t 
              JOIN users u ON t.user_id = u.id 
              LEFT JOIN meal_cards mc ON t.id = mc.ticket_id 
              WHERE t.event_id = :event_id" . $where_sql;

$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// --- Get Paginated Data ---
$data_sql = "SELECT t.id AS ticket_id, u.full_name, u.email, COALESCE(mc.status, 'inactive') AS meal_status
             FROM event_tickets t
             JOIN users u ON t.user_id = u.id
             LEFT JOIN meal_cards mc ON t.id = mc.ticket_id
             WHERE t.event_id = :event_id" . $where_sql . "
             ORDER BY u.full_name ASC
             LIMIT :limit OFFSET :offset";

$data_stmt = $db->prepare($data_sql);
// Bind all params for the main query
foreach ($params as $key => &$val) {
    $data_stmt->bindParam($key, $val);
}
$data_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$data_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$data_stmt->execute();
$attendees = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Response ---
echo json_encode([
    'success' => true,
    'data' => $attendees,
    'pagination' => [
        'currentPage' => $page,
        'totalPages' => $total_pages,
        'totalRecords' => $total_records
    ]
]);