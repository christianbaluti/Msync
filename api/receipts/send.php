<?php
// /api/receipts/send.php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/PdfGenerator.php';
require_once dirname(__DIR__) . '/core/Mailer.php';

$input = json_decode(file_get_contents('php://input'), true);
$payment_id = filter_var($input['payment_id'] ?? null, FILTER_VALIDATE_INT);

if (!$payment_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Payment ID.']);
    exit();
}

$db = (new Database())->connect();
// This query is correct and fetches all the necessary data
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
    echo json_encode(['success' => false, 'message' => 'Receipt data not found.']);
    exit();
}

try {
    $pdfGenerator = new PdfGenerator();
    
    // FIX: Populate the array with the data fetched from the database.
    $receipt_data = [
        'receipt_num'     => $data['receipt_number'],
        'user_name'       => $data['user_name'],
        'amount_paid'     => $data['amount'],
        'membership_type' => $data['membership_type']
    ];

    $pdf_content = $pdfGenerator->generateMembershipReceiptPdf($receipt_data);
    $attachment = ['content' => $pdf_content, 'filename' => $data['receipt_number'] . '.pdf'];
    
    // Fetch user's email (this part is fine)
    $user_stmt = $db->prepare("SELECT email, full_name FROM users u JOIN payments p ON u.id=p.user_id WHERE p.id = ?");
    $user_stmt->execute([$payment_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $mailer = new Mailer();
        $mailer->send(
            $user['email'], 
            $user['full_name'], 
            "Your Payment Receipt: " . $data['receipt_number'], 
            "Dear " . $user['full_name'] . ",\n\nPlease find your payment receipt attached.", 
            [$attachment]
        );
        echo json_encode(['success' => true, 'message' => 'Receipt sent.']);
    } else {
        throw new Exception("Could not find user to email.");
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Email sending failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send receipt.']);
}