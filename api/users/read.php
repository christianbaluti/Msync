<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

if (!has_permission('users_read')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

$database = new Database();
$db = $database->connect();

// --- Pagination ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// --- Filtering & Searching ---
$where_clauses = [];
$params = [];

// Text search (name, email, phone, company)
if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search OR c.name LIKE :search)";
    $params[':search'] = $search_term;
}

// ... inside the filtering section
if (!empty($_GET['company_id'])) {
    $where_clauses[] = "u.company_id = :company_id";
    $params[':company_id'] = $_GET['company_id'];
}
// ...

// Status filter
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where_clauses[] = "u.is_active = :status";
    $params[':status'] = $_GET['status'];
}
// Base role filter
if (!empty($_GET['role'])) {
    $where_clauses[] = "u.role = :role";
    $params[':role'] = $_GET['role'];
}
// Employment status filter
if (isset($_GET['employed']) && $_GET['employed'] !== '') {
    $where_clauses[] = "u.is_employed = :employed";
    $params[':employed'] = $_GET['employed'];
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// --- Main Query for fetching data ---
$query = "
    SELECT 
        u.id, u.full_name, u.email, u.phone, u.gender, u.is_employed, u.position,
        u.role, u.is_active, u.last_login,
        c.name as company_name,
        GROUP_CONCAT(r.name SEPARATOR ', ') as admin_roles
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    $where_sql
    GROUP BY u.id
    ORDER BY u.full_name ASC
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($query);

// --- Query for total count (for pagination) ---
$count_query = "
    SELECT COUNT(DISTINCT u.id) 
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    $where_sql
";
$count_stmt = $db->prepare($count_query);

// Bind params for both queries
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
    $count_stmt->bindParam($key, $val);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

// Execute queries
$stmt->execute();
$count_stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_records = $count_stmt->fetchColumn();

// Return combined result
echo json_encode([
    'total_records' => $total_records,
    'users' => $users
]);