<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once dirname(__DIR__) . '/core/initialize.php';

$uid = $_GET['uid'] ?? 0;
$token = $_GET['token'] ?? '';

if (!$uid || !$token) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid link parameters.']);
    exit;
}

$database = new Database();
$db = $database->connect();

try {
    // 1. Validate Token
    $query = "SELECT t.id, t.expires_at, t.status, u.full_name, u.email 
              FROM invoice_access_tokens t
              JOIN users u ON t.user_id = u.id
              WHERE t.user_id = :uid AND t.token = :token AND t.target_type = 'user'
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':uid' => $uid, ':token' => $token]);
    $access = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$access) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid or broken link.']);
        exit;
    }

    if ($access['status'] === 'expired') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This invitation link has expired.']);
        exit;
    }
    
    if (strtotime($access['expires_at']) < time()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This invitation link has expired.']);
        exit;
    }
    
    if ($access['status'] === 'used') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This invitation link has already been used.']);
        exit;
    }

    // 2. Fetch System Settings (Address & Name)
    $stmtSettings = $db->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('address', 'system_name')");
    $stmtSettings->execute();
    $settingsRaw = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR); // Returns ['address' => '...', 'system_name' => '...']

    // 3. Fetch Membership Types
    $stmtTypes = $db->query("SELECT id, name, description, fee, renewal_month FROM membership_types ORDER BY fee ASC");
    $types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'user' => [
            'name' => $access['full_name'],
            'email' => $access['email']
        ],
        'system' => [
            'name' => $settingsRaw['system_name'] ?? 'Organization',
            'address' => $settingsRaw['address'] ?? 'Address not configured'
        ],
        'membership_types' => $types
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}