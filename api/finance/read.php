<?php
// /api/finance/read.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

// --- FILTERS ---
$params = [];
$filterClauses = [
    'payments' => [],
    'invoices' => []
];

// Date filters
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;

if ($startDate) {
    $filterClauses['payments'][] = "p.transaction_date >= :startDate";
    $filterClauses['invoices'][] = "i.issued_at >= :startDate";
    $params[':startDate'] = $startDate . ' 00:00:00';
}
if ($endDate) {
    $filterClauses['payments'][] = "p.transaction_date <= :endDate";
    $filterClauses['invoices'][] = "i.issued_at <= :endDate";
    $params[':endDate'] = $endDate . ' 23:59:59';
}

// Table-specific filters
$paymentType = $_GET['paymentType'] ?? null;
$method = $_GET['method'] ?? null;
$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? null;

if ($paymentType) {
    $filterClauses['payments'][] = "p.payment_type = :paymentType";
    $params[':paymentType'] = $paymentType;
}
if ($method) {
    $filterClauses['payments'][] = "p.method = :method";
    $params[':method'] = $method;
}
if ($status) {
    $filterClauses['payments'][] = "p.status = :status";
    $params[':status'] = $status;
}
if ($search) {
    $filterClauses['payments'][] = "(u.full_name LIKE :search OR u.email LIKE :search OR p.gateway_transaction_id LIKE :search)";
    $params[':search'] = "%$search%";
}

// Build WHERE clauses
$payments_where = !empty($filterClauses['payments']) ? 'WHERE ' . implode(' AND ', $filterClauses['payments']) : '';
$invoices_where = !empty($filterClauses['invoices']) ? 'WHERE ' . implode(' AND ', $filterClauses['invoices']) : '';

// --- REVISED LOGIC FOR SUMMARY STATS ---

// 1. Params for 'completed' payments (Revenue, Transactions)
$revenue_params = [':compStatus' => 'completed'];
$revenue_where = 'WHERE p.status = :compStatus'; 

// 2. Params for 'outstanding' invoices (Outstanding Revenue)
$invoice_params = [':unpaid' => 'unpaid', ':partial' => 'partially_paid'];
$invoice_where = 'WHERE i.status IN (:unpaid, :partial)';

// 3. Params for 'pending/failed' payments (Pending Transactions)
$pending_params = [':pending' => 'pending', ':failed' => 'failed'];
$pending_payments_where = 'WHERE p.status IN (:pending, :failed)';

// Add date filters to all three param arrays and WHERE clauses if they exist
if ($startDate) {
    $revenue_where .= " AND p.transaction_date >= :startDate";
    $invoice_where .= " AND i.issued_at >= :startDate";
    $pending_payments_where .= " AND p.transaction_date >= :startDate";

    $revenue_params[':startDate'] = $startDate . ' 00:00:00';
    $invoice_params[':startDate'] = $startDate . ' 00:00:00';
    $pending_params[':startDate'] = $startDate . ' 00:00:00';
}
if ($endDate) {
    $revenue_where .= " AND p.transaction_date <= :endDate";
    $invoice_where .= " AND i.issued_at <= :endDate";
    $pending_payments_where .= " AND p.transaction_date <= :endDate";

    $revenue_params[':endDate'] = $endDate . ' 23:59:59';
    $invoice_params[':endDate'] = $endDate . ' 23:59:59';
    $pending_params[':endDate'] = $endDate . ' 23:59:59';
}

// --- END REVISED LOGIC ---

// Pending/Failed WHERE (respects date)
$pending_params = [];
$pending_payments_where = 'WHERE p.status IN (:pending, :failed)';
$pending_params[':pending'] = 'pending';
$pending_params[':failed'] = 'failed';
if ($startDate) {
    $pending_payments_where .= " AND p.transaction_date >= :startDate";
    $pending_params[':startDate'] = $startDate . ' 00:00:00';
}
if ($endDate) {
    $pending_payments_where .= " AND p.transaction_date <= :endDate";
    $pending_params[':endDate'] = $endDate . ' 23:59:59';
}


try {
    $output = [
        'success' => true,
        'summary' => [],
        'charts' => [],
        'payments' => [],
        'total_records' => 0
    ];

    // --- 1. SUMMARY STATS (KPIs) ---
    // Use the specific parameter arrays for each query
    $stmt_rev = $db->prepare("SELECT SUM(p.amount) FROM payments p $revenue_where");
    $stmt_rev->execute($revenue_params); // <-- Use revenue_params
    $output['summary']['totalRevenue'] = $stmt_rev->fetchColumn() ?: 0;
    
    $stmt_out = $db->prepare("SELECT SUM(i.balance_due) FROM invoices i $invoice_where");
    $stmt_out->execute($invoice_params); // <-- Use invoice_params
    $output['summary']['outstandingRevenue'] = $stmt_out->fetchColumn() ?: 0;

    $stmt_trans = $db->prepare("SELECT COUNT(p.id) FROM payments p $revenue_where");
    $stmt_trans->execute($revenue_params); // <-- Use revenue_params
    $output['summary']['totalTransactions'] = $stmt_trans->fetchColumn() ?: 0;

    $stmt_pend = $db->prepare("SELECT COUNT(p.id) FROM payments p $pending_payments_where");
    $stmt_pend->execute($pending_params); // <-- This one was already correct
    $output['summary']['pendingTransactions'] = $stmt_pend->fetchColumn() ?: 0;


    // --- 2. CHART DATA ---
    // Charts respect the date filter AND require 'completed' status
    $chart_params = [':compStatus' => 'completed'];
    $chart_where = 'WHERE p.status = :compStatus';
    if ($startDate) {
        $chart_where .= " AND p.transaction_date >= :startDate";
        $chart_params[':startDate'] = $startDate . ' 00:00:00';
    }
    if ($endDate) {
        $chart_where .= " AND p.transaction_date <= :endDate";
        $chart_params[':endDate'] = $endDate . ' 23:59:59';
    }

    // Revenue over time
    $stmt_chart_time = $db->prepare("SELECT DATE(p.transaction_date) as day, SUM(p.amount) as total 
                                     FROM payments p 
                                     $chart_where 
                                     GROUP BY day 
                                     ORDER BY day ASC");
    $stmt_chart_time->execute($chart_params);
    $output['charts']['revenueOverTime'] = $stmt_chart_time->fetchAll(PDO::FETCH_ASSOC);

    // Revenue by type
    $stmt_chart_type = $db->prepare("SELECT p.payment_type, SUM(p.amount) as total 
                                     FROM payments p 
                                     $chart_where 
                                     GROUP BY p.payment_type");
    $stmt_chart_type->execute($chart_params);
    $output['charts']['revenueByType'] = $stmt_chart_type->fetchAll(PDO::FETCH_ASSOC);


    // --- 3. PAGINATED TABLE DATA ---
    // Uses all filters ($payments_where and $params)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Count total records for pagination
    $stmt_count = $db->prepare("SELECT COUNT(p.id) 
                                FROM payments p 
                                LEFT JOIN users u ON p.user_id = u.id 
                                $payments_where");
    $stmt_count->execute($params);
    $output['total_records'] = (int)$stmt_count->fetchColumn();

    // Fetch paginated data
    $query = "SELECT p.id, p.transaction_date, p.payment_type, p.amount, p.method, p.status, p.gateway_transaction_id,
                     u.full_name as user_name, c.name as company_name, r.id as receipt_id
              FROM payments p
              LEFT JOIN users u ON p.user_id = u.id
              LEFT JOIN companies c ON p.company_id = c.id
              LEFT JOIN receipts r ON r.payment_id = p.id
              $payments_where
              ORDER BY p.transaction_date DESC
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
    $output['payments'] = $stmt_table->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($output);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Query Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'General Error: ' . $e->getMessage()]);
}
?>