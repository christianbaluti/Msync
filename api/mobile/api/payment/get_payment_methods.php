<?php
// api/payment/get_payment_methods.php
declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

// ---- CONFIG ----
$logFile = __DIR__ . '/../error.log';
$debug   = (getenv('DEBUG') === '1');

// Allowed CORS origins
$allowedOriginsEnv = getenv('ALLOWED_ORIGINS') ?: '*';
$allowedOrigins = $allowedOriginsEnv === '*' ? ['*'] : array_map('trim', explode(',', $allowedOriginsEnv));

// ---- ERROR HANDLING ----
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
error_reporting(E_ALL);

function log_error_file(string $message, array $context = []): void {
    global $logFile;
    $ts = date('Y-m-d H:i:s');
    $ctx = $context ? ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
    $line = "[$ts] get_payment_methods.php: $message$ctx\n";
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    @error_log($line);
}

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) use ($debug) {
    http_response_code(500);
    log_error_file('Uncaught exception: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    header('Content-Type: application/json');
    echo json_encode(['message' => $debug ? $e->getMessage() : 'Internal Server Error']);
    exit;
});

register_shutdown_function(function() use ($debug) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        http_response_code(500);
        $text = "{$err['message']} in {$err['file']} line {$err['line']}";
        log_error_file('Fatal: ' . $text);
        header('Content-Type: application/json');
        echo json_encode(['message' => $debug ? $text : 'Internal Server Error']);
        exit;
    }
});

// ---- CORS ----
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowOrigin = '*';
if ($origin && $allowedOrigins !== ['*']) {
    foreach ($allowedOrigins as $o) {
        if ($o === $origin) { $allowOrigin = $origin; break; }
    }
} elseif ($origin && $allowedOrigins === ['*']) {
    $allowOrigin = $origin;
}
header("Access-Control-Allow-Origin: {$allowOrigin}");
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

// ---- Dependencies ----
$autoload = __DIR__ . '/../../vendor/autoload.php';
$dbconn   = __DIR__ . '/../db_connection.php';

if (!file_exists($autoload)) {
    log_error_file('Missing vendor/autoload.php');
    http_response_code(500);
    echo json_encode(['message' => $debug ? 'Missing vendor/autoload.php' : 'Internal Server Error']);
    exit;
}
require_once $autoload;

try {
    if (!file_exists($dbconn)) {
        throw new RuntimeException('Missing db_connection.php');
    }
    require_once $dbconn;
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('db_connection.php did not provide $pdo.');
    }
} catch (Throwable $t) {
    log_error_file('DB connection failed: ' . $t->getMessage());
    http_response_code(500);
    echo json_encode(['message' => $debug ? $t->getMessage() : 'Database Error']);
    exit;
}

function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo $json === false ? json_encode(['message' => 'Response encoding error']) : $json;
    exit;
}

function cfg_clean($v) {
    return is_string($v) ? trim(str_replace('`', '', $v)) : $v;
}

// ---- MAIN LOGIC ----
try {
    $gatewayName = isset($_GET['gateway']) ? trim($_GET['gateway']) : 'MALIPO_MEMBERSHIPS';
    if (!preg_match('/^[A-Z0-9_\-]+$/i', $gatewayName)) {
        log_error_file('Invalid gateway param: ' . $gatewayName);
        json_response(['message' => 'Invalid gateway parameter.'], 400);
    }

    $stmt = $pdo->prepare('SELECT id, name, config FROM payment_gateways WHERE name = :name LIMIT 1');
    $stmt->bindParam(':name', $gatewayName, PDO::PARAM_STR);
    $stmt->execute();
    $gateway = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gateway) {
        $stmt2 = $pdo->query("SELECT id, name, config FROM payment_gateways WHERE name LIKE 'MALIPO_%' LIMIT 1");
        $gateway = $stmt2->fetch(PDO::FETCH_ASSOC);
    }

    if (!$gateway) {
        log_error_file('No payment gateway found for ' . $gatewayName);
        json_response(['message' => 'No payment gateway configuration found.'], 500);
    }

    $cfg = json_decode($gateway['config'], true);
    if (!is_array($cfg)) {
        log_error_file('Invalid gateway JSON for id ' . $gateway['id']);
        json_response(['message' => 'Invalid gateway configuration JSON.'], 500);
    }

    $apiBase = cfg_clean($cfg['base_url'] ?? '');
    $apiKey  = cfg_clean($cfg['api_key'] ?? '');
    $appId   = cfg_clean($cfg['app_id'] ?? '');
    if (!$apiBase || !$apiKey || !$appId) {
        log_error_file('Incomplete gateway config for id ' . $gateway['id']);
        json_response(['message' => 'Incomplete gateway configuration.'], 500);
    }

    $client = new Client(['timeout' => 10, 'http_errors' => false]);
    $url = rtrim($apiBase, '/') . '/banklist';

    try {
        $response = $client->request('GET', $url, [
            'headers' => [
                'x-api-key' => $apiKey,
                'x-app-id'  => $appId,
                'Accept'    => 'application/json',
            ],
        ]);
    } catch (GuzzleException $ge) {
        log_error_file('Guzzle request failed: ' . $ge->getMessage());
        json_response(['message' => $debug ? $ge->getMessage() : 'Failed to contact gateway.'], 502);
    }

    $status = $response->getStatusCode();
    $body = (string)$response->getBody();

    if ($status !== 200) {
        log_error_file("Gateway returned HTTP $status", ['body' => substr($body, 0, 200)]);
        json_response(['message' => 'Failed to retrieve payment methods.'], 502);
    }

    $banks = json_decode($body, true);
    if (!is_array($banks)) {
        log_error_file('Invalid gateway response', ['body' => substr($body, 0, 500)]);
        json_response(['message' => 'Unexpected gateway response.'], 502);
    }

    $normalized = array_values(array_filter(array_map(function ($b) {
        if (!is_array($b)) return null;
        $id = $b['id'] ?? $b['bankId'] ?? null;
        $id = $id !== null ? intval($id) : null;
        $name = $b['name'] ?? $b['bankName'] ?? 'Unknown';
        $type = $b['type'] ?? $b['bankType'] ?? 'Unknown';
        if ($id === null) return null;
        return ['id' => $id, 'name' => $name, 'type' => $type];
    }, $banks)));

    json_response($normalized, 200);

} catch (Throwable $t) {
    log_error_file('Top-level error: ' . $t->getMessage());
    json_response(['message' => $debug ? $t->getMessage() : 'Internal Server Error'], 500);
}
