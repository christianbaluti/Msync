<?php
// api/elections/search_nominees.php
// Search users or companies for nomination
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware_for_all.php';

// Read POST body
$data = json_decode(file_get_contents("php://input"));
if ($data === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
    exit;
}

// Authenticate user
$auth_data = get_auth_user($data);

// Get search parameters
$type = $data->type ?? 'user'; // 'user' or 'company'
$query = trim($data->query ?? '');

if (strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

$search_pattern = "%{$query}%";

try {
    $results = [];

    if ($type === 'user') {
        // Search users by name or email
        $stmt = $pdo->prepare("
            SELECT id, full_name, email
            FROM users 
            WHERE (full_name LIKE ? OR email LIKE ?)
            AND is_active = 1
            ORDER BY full_name
            LIMIT 20
        ");
        $stmt->execute([$search_pattern, $search_pattern]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $results[] = [
                'id' => (int)$user['id'],
                'name' => $user['full_name'],
                'subtitle' => $user['email'] ?? '',
                'image_url' => ''
            ];
        }
    } elseif ($type === 'company') {
        // Search companies by name
        $stmt = $pdo->prepare("
            SELECT id, name, email
            FROM companies 
            WHERE name LIKE ?
            AND is_active = 1
            ORDER BY name
            LIMIT 20
        ");
        $stmt->execute([$search_pattern]);
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($companies as $company) {
            $results[] = [
                'id' => (int)$company['id'],
                'name' => $company['name'],
                'subtitle' => $company['email'] ?? 'Organization',
                'image_url' => ''
            ];
        }
    }

    echo json_encode($results);

} catch (Exception $e) {
    error_log("Search nominees error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Search failed.']);
}
?>
