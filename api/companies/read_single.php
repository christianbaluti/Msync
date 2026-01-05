<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

if (!has_permission('companies_read')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Company ID not provided.']);
    exit();
}

$db = (new Database())->connect();

// Get company's main details and counts of related items
$query = "
    SELECT c.*,
           (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
           (SELECT COUNT(*) FROM membership_subscriptions WHERE company_id = c.id) as member_count,
           (SELECT COUNT(*) FROM invoices WHERE company_id = c.id) as invoice_count,
           (SELECT COUNT(*) FROM quotations WHERE company_id = c.id) as quotation_count,
           (SELECT COUNT(*) FROM receipts r JOIN payments p ON r.payment_id = p.id WHERE p.company_id = c.id) as receipt_count
    FROM companies c
    WHERE c.id = :id
";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Company not found.']);
    exit();
}

echo json_encode($company);