<?php
// server/api/memberships/data.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../auth_middleware.php';

try {
    // Parse JSON body if provided
    $rawBody = file_get_contents('php://input');
    $bodyData = json_decode($rawBody ?: '{}');
    if (!is_object($bodyData)) { $bodyData = new stdClass(); }

    // Authenticate; middleware reads auth_token from php://input
    $auth_data = get_auth_user();
    $user_id = isset($auth_data->user_id) ? $auth_data->user_id : null;
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid auth payload: user_id missing']);
        exit;
    }

    // 1. Fetch all available membership types
    $stmt_types = $pdo->query("SELECT id, name, description, fee FROM membership_types ORDER BY fee ASC");
    $available_types_raw = $stmt_types->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure 'fee' is a string and 'id' is an int
    $available_types = array_map(function($row) {
        $row['id'] = isset($row['id']) ? (int)$row['id'] : 0; // <-- MODIFIED
        $row['fee'] = isset($row['fee']) ? (string)$row['fee'] : '';
        return $row;
    }, $available_types_raw ?: []);

    // 2. Fetch user's subscription history
    $sql_history = "
        SELECT 
            ms.id, ms.start_date, ms.end_date, ms.status, 
            mt.name AS membership_name, 
            p.id as payment_id, 
            r.receipt_number 
        FROM membership_subscriptions ms 
        JOIN membership_types mt ON ms.membership_type_id = mt.id 
        LEFT JOIN payments p ON p.reference_id = ms.id AND p.payment_type = 'membership' AND p.status = 'completed' 
        LEFT JOIN receipts r ON r.payment_id = p.id 
        WHERE ms.user_id = ? 
        ORDER BY ms.start_date DESC 
    ";
    $stmt_history = $pdo->prepare($sql_history);
    $stmt_history->execute([$user_id]);
    $history_rows = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

    // Normalize to expected client fields, now including integer IDs
    $history = array_map(function($row) {
        return [
            'id' => isset($row['id']) ? (int)$row['id'] : 0, // <-- ADDED
            'payment_id' => isset($row['payment_id']) ? (int)$row['payment_id'] : null, // <-- ADDED
            'membership_name' => $row['membership_name'] ?? '',
            'status' => $row['status'] ?? 'pending',
            'start_date' => isset($row['start_date']) ? (string)$row['start_date'] : '',
            'end_date' => isset($row['end_date']) ? (string)$row['end_date'] : '',
            'receipt_number' => $row['receipt_number'] ?? null,
        ];
    }, $history_rows ?: []);

    echo json_encode([
        'available_types' => $available_types,
        'history' => $history,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>