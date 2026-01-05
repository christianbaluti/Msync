<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

$db = (new Database())->connect();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtering
$where_clauses = [];
$params = [];

if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)";
    $params[':search'] = $search_term;
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where_clauses[] = "c.is_active = :status";
    $params[':status'] = $_GET['status'];
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Main Query with subqueries for counts
$query = "
    SELECT c.*,
           (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
           (SELECT COUNT(*) FROM membership_subscriptions WHERE company_id = c.id) as member_count,
           (SELECT COUNT(*) FROM invoices WHERE company_id = c.id) as invoice_count,
           (SELECT COUNT(*) FROM quotations WHERE company_id = c.id) as quotation_count,
           (SELECT COUNT(*) FROM receipts r JOIN payments p ON r.payment_id = p.id WHERE p.company_id = c.id) as receipt_count
    FROM companies c
    $where_sql
    ORDER BY c.name ASC
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($query);

// Count Query for pagination
$count_query = "SELECT COUNT(*) FROM companies c $where_sql";
$count_stmt = $db->prepare($count_query);

// Bind params
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
    $count_stmt->bindParam($key, $val);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

// Execute
$stmt->execute();
$count_stmt->execute();

echo json_encode([
    'total_records' => $count_stmt->fetchColumn(),
    'companies' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);