<?php
// api/awards/search_award_nominees.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware_for_all.php';
require __DIR__ . '/../eligibility_helper.php';

// Read POST body
$data = json_decode(file_get_contents("php://input"));
if ($data === null) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

$auth_data = get_auth_user($data);
$user_id = $auth_data->user_id;

// Get parameters from POST body
$type = $data->type ?? '';
$query_param = isset($data->query) ? trim($data->query) : '';
$schedule_id = $data->schedule_id ?? null;

// Validate input
if (empty($type) || empty($query_param) || !$schedule_id || !is_numeric($schedule_id)) {
    http_response_code(400);
    echo json_encode([]);
    exit();
}
$schedule_id = intval($schedule_id);

$results = [];
$search_term = '%' . $query_param . '%';

try {
    // --- Get Nominee Eligibility Settings from Schedule ---
    $stmt_settings = $pdo->prepare("SELECT settings, type FROM event_schedules WHERE id = ? LIMIT 1");
    $stmt_settings->execute([$schedule_id]);
    $schedule_data = $stmt_settings->fetch(PDO::FETCH_ASSOC);

    if (!$schedule_data) {
        echo json_encode([]); // Schedule not found
        exit();
    }

    $settings = json_decode($schedule_data['settings'] ?? '{}', true);
    $schedule_type = $schedule_data['type']; // 'voting' or 'awards'

    // Determine which eligibility rules to use based on schedule type
    $nominee_eligibility_rules = null;
    if ($schedule_type === 'awards' && isset($settings['nominee_eligibility'])) {
        $nominee_eligibility_rules = $settings['nominee_eligibility'];
    } elseif ($schedule_type === 'voting' && isset($settings['eligibility'])) {
        $nominee_eligibility_rules = $settings['eligibility'];
    }

    // --- Perform Search based on type ---
    if ($type === 'user') {
        $stmt_search = $pdo->prepare("SELECT id, full_name as name FROM users WHERE full_name LIKE ? AND is_active = 1 LIMIT 30");
        $stmt_search->execute([$search_term]);
        $potential_nominees = $stmt_search->fetchAll(PDO::FETCH_ASSOC);

        // Filter based on eligibility rules
        foreach ($potential_nominees as $nominee) {
            $nominee_id = intval($nominee['id']);
            
            // If no eligibility rules, include all users (or you can default to exclude)
            if ($nominee_eligibility_rules === null || empty($nominee_eligibility_rules)) {
                // Default: include all if no rules set
                $results[] = $nominee;
            } elseif (check_user_eligibility($pdo, $nominee_id, $nominee_eligibility_rules)) {
                $results[] = $nominee;
            }
            
            // Limit results to 10 eligible nominees
            if (count($results) >= 10) break;
        }

    } elseif ($type === 'company') {
        // Companies have no eligibility restrictions - return all matching active companies
        $stmt_search = $pdo->prepare("SELECT id, name FROM companies WHERE name LIKE ? AND is_active = 1 LIMIT 10");
        $stmt_search->execute([$search_term]);
        $results = $stmt_search->fetchAll(PDO::FETCH_ASSOC);
    }
    // 'idea' type doesn't involve searching existing entities

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Search Award Nominees PDO Error: " . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Search Award Nominees General Error: " . $e->getMessage());
    echo json_encode([]);
}
?>
