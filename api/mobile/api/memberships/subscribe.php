<?php
// --- CONFIGURE LOGGING ----------------------------------------------------
$logPath = __DIR__ . '/../../../../public/error.log'; // relative to this PHP file
// Ensure errors are reported
error_reporting(E_ALL);
ini_set('display_errors', '0');       // do NOT display errors to users
ini_set('log_errors', '1');           // enable PHP internal logging
ini_set('error_log', $logPath);       // point PHP error_log to our file

// Create the log file if it doesn't exist (attempt)
if (!file_exists($logPath)) {
    @mkdir(dirname($logPath), 0755, true);
    @touch($logPath);
    @chmod($logPath, 0644);
}

// Helper to format log entries
function log_entry($level, $message, $file = null, $line = null) {
    global $logPath;
    $time = date('Y-m-d H:i:s');
    $filePart = $file !== null ? " in {$file}:{$line}" : "";
    $entry = "[{$time}] {$level}: {$message}{$filePart}\n";
    // Use error_log with type 3 to append to our chosen file (more reliable)
    @error_log($entry, 3, $logPath);
}

// Custom error handler (catches warnings, notices, etc.)
set_error_handler(function($severity, $message, $file, $line) {
    // Respect error_reporting level
    if (!(error_reporting() & $severity)) {
        return false; // let PHP handle it
    }
    $sevMap = [
        E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING', E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE', E_CORE_ERROR => 'E_CORE_ERROR', E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR', E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR', E_USER_WARNING => 'E_USER_WARNING', E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT', E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR', E_DEPRECATED => 'E_DEPRECATED', E_USER_DEPRECATED => 'E_USER_DEPRECATED'
    ];
    $level = isset($sevMap[$severity]) ? $sevMap[$severity] : "E_UNKNOWN({$severity})";
    log_entry($level, $message, $file, $line);

    // Return false so PHP's internal handler can still run (optional).
    // Return true if you want to prevent PHP internal handling.
    return false;
});

// Custom exception handler
set_exception_handler(function(Throwable $e) {
    $msg = $e->getMessage() . "\nStack trace:\n" . $e->getTraceAsString();
    log_entry('UNCAUGHT_EXCEPTION', $msg, $e->getFile(), $e->getLine());

    // Respond to client with generic message (don't leak internals)
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Internal Server Error']);
    exit;
});

// Shutdown handler to catch fatal errors
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err !== null) {
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (in_array($err['type'], $fatalTypes, true)) {
            $message = $err['message'] . ' (shutdown)';
            log_entry('FATAL_ERROR', $message, $err['file'], $err['line']);
            // We cannot reliably send JSON if headers/body already sent, so just log.
        }
    }
});
// -------------------------------------------------------------------------

// Keep your existing CORS + basic request checks
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method Not Allowed']);
    exit;
}

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];

    $auth = get_auth_user();
    if (!$auth || empty($auth->user_id)) {
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => 'Unauthorized']);
        exit;
    }

    $userId = (int)$auth->user_id;
    $membershipTypeId = isset($input['membership_type_id']) ? (int)$input['membership_type_id'] : 0;
    $orderId = isset($input['order_id']) ? trim((string)$input['order_id']) : '';

    if ($membershipTypeId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Membership Type ID is required']);
        exit;
    }

    $stmtType = $pdo->prepare("SELECT fee, COALESCE(prefix, 'MID') AS prefix FROM membership_types WHERE id = ?");
    $stmtType->execute([$membershipTypeId]);
    $type = $stmtType->fetch(PDO::FETCH_ASSOC);
    if (!$type) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Invalid Membership Type']);
        exit;
    }
    $fee = (float)$type['fee'];
    $prefix = (string)$type['prefix'];

    $pdo->beginTransaction();

    $stmtPrev = $pdo->prepare("SELECT membership_card_number FROM membership_subscriptions WHERE user_id = ? AND membership_card_number IS NOT NULL ORDER BY id DESC LIMIT 1");
    $stmtPrev->execute([$userId]);
    $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

    if ($prev && !empty($prev['membership_card_number'])) {
        $cardNumber = $prev['membership_card_number'];
    } else {
        $stmtSeq = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(membership_card_number, '_', -1) AS UNSIGNED)) AS max_seq FROM membership_subscriptions WHERE membership_card_number REGEXP '^[A-Za-z0-9]+_[0-9]+$'");
        $rowSeq = $stmtSeq->fetch(PDO::FETCH_ASSOC);
        $nextSeq = isset($rowSeq['max_seq']) && $rowSeq['max_seq'] !== null ? ((int)$rowSeq['max_seq'] + 1) : 1;
        $cardNumber = $prefix . '_' . $nextSeq;
    }

    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+1 year'));

    $stmtSub = $pdo->prepare("INSERT INTO membership_subscriptions (user_id, membership_type_id, start_date, end_date, status, membership_card_number, balance_due) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $stmtSub->execute([$userId, $membershipTypeId, $startDate, $endDate, $cardNumber, $fee]);
    $subscriptionId = (int)$pdo->lastInsertId();

    $stmtInv = $pdo->prepare("INSERT INTO invoices (user_id, related_type, related_id, total_amount, balance_due, status) VALUES (?, 'membership', ?, ?, ?, 'pending')");
    $stmtInv->execute([$userId, $subscriptionId, $fee, $fee]);
    $invoiceId = (int)$pdo->lastInsertId();

    $stmtUpd = $pdo->prepare("UPDATE membership_subscriptions SET invoice_id = ? WHERE id = ?");
    $stmtUpd->execute([$invoiceId, $subscriptionId]);

    $meta = json_encode([
        'order_id' => $orderId,
        'membership_type_id' => $membershipTypeId,
        'invoice_id' => $invoiceId,
        'card_number' => $cardNumber,
    ]);

    $stmtPay = $pdo->prepare("INSERT INTO payments (user_id, payment_type, reference_id, amount, method, status, gateway_transaction_id, meta) VALUES (?, 'membership', ?, ?, 'gateway', 'pending', ?, ?)");
    $stmtPay->execute([$userId, $subscriptionId, $fee, $orderId, $meta]);
    $paymentId = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'error' => false,
        'message' => 'Subscription created',
        'subscription' => [
            'id' => $subscriptionId,
            'membership_card_number' => $cardNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'pending',
            'invoice_id' => $invoiceId,
        ],
        'invoice' => [
            'id' => $invoiceId,
            'total_amount' => $fee,
            'balance_due' => $fee,
            'status' => 'pending',
        ],
        'payment' => [
            'id' => $paymentId,
            'status' => 'pending',
            'gateway_transaction_id' => $orderId,
        ],
        'order_id' => $orderId,
    ]);
} catch (Exception $e) {
    // Rollback if inside transaction
    if (isset($pdo) && $pdo && $pdo->inTransaction()) { $pdo->rollBack(); }

    // Log the exception details to the log file
    $msg = $e->getMessage() . "\nFile: " . $e->getFile() . ":" . $e->getLine() . "\nStack:\n" . $e->getTraceAsString();
    log_entry('EXCEPTION_CATCH', $msg, $e->getFile(), $e->getLine());

    // Return a generic error to the client
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Subscription error']);
    exit;
}
