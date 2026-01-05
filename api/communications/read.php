<?php
// /api/communications/read.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// Add permission check (assuming 'communications_read' or similar)
if (!has_permission('communications_read')) { 
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->connect();

// --- FILTERS ---
$params = [];
$filterClauses = [];

// Channel filter
$channel = $_GET['channel'] ?? null;
if ($channel) {
    $filterClauses[] = "c.channel = :channel";
    $params[':channel'] = $channel;
}

// Search filter (searching the 'subject' field as per the table)
$search = $_GET['search'] ?? null;
if ($search) {
    $filterClauses[] = "c.subject LIKE :search";
    $params[':search'] = "%$search%";
}

// Build WHERE clause
$where_clause = !empty($filterClauses) ? 'WHERE ' . implode(' AND ', $filterClauses) : '';

// Base query
$base_query = "FROM communications c $where_clause";

try {
    // 1. Count total records for pagination
    $stmt_count = $db->prepare("SELECT COUNT(c.id) $base_query");
    $stmt_count->execute($params);
    $total_records = (int)$stmt_count->fetchColumn();

    // 2. Fetch paginated data
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Match JS
    $offset = ($page - 1) * $limit;

    $query = "SELECT c.id, c.subject, c.channel, c.sent_at
              $base_query
              ORDER BY c.sent_at DESC
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
    $data = $stmt_table->fetchAll(PDO::FETCH_ASSOC);

    // 3. Send response in the format JS expects
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total_records' => $total_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Query Error: ' . $e->getMessage()]);
}
?>