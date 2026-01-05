<?php
// /api/receipts/download.php

// FIX: Add the Composer autoloader to load the Dompdf library
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/PdfGenerator.php';

$payment_id = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);
if (!$payment_id) { 
    http_response_code(400); 
    die("Invalid Payment ID."); 
}

$db = (new Database())->connect();
// Query to get all data needed for the receipt
$stmt = $db->prepare("
    SELECT r.receipt_number, p.amount, u.full_name as user_name, mt.name as membership_type
    FROM payments p
    JOIN receipts r ON p.id = r.payment_id
    JOIN users u ON p.user_id = u.id
    JOIN membership_subscriptions ms ON p.reference_id = ms.id AND p.payment_type = 'membership'
    JOIN membership_types mt ON ms.membership_type_id = mt.id
    WHERE p.id = ?
");
$stmt->execute([$payment_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) { 
    http_response_code(404); 
    die("Receipt not found."); 
}

$pdfGenerator = new PdfGenerator();
$receipt_data = [
    'receipt_num' => $data['receipt_number'],
    'user_name' => $data['user_name'],
    'amount_paid' => $data['amount'],
    'membership_type' => $data['membership_type']
];
$pdf_content = $pdfGenerator->generateMembershipReceiptPdf($receipt_data);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . htmlspecialchars($data['receipt_number']) . '.pdf"');
header('Content-Length: ' . strlen($pdf_content));
echo $pdf_content;