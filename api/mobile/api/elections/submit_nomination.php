<?php
// api/elections/submit_nomination.php
// Flutter app endpoint for submitting nominations
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

$auth_data = get_auth_user($data);
$user_id = $auth_data->user_id;

// Validate required fields
$seat_id = $data->seat_id ?? null;

if (!$seat_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Seat ID is required.']);
    exit;
}

try {
    // Get the seat details including nominee_type and schedule_id
    $stmt = $pdo->prepare("
        SELECT es.id, es.nominee_type, es.schedule_id, sch.stage, sch.settings
        FROM election_seats es
        JOIN event_schedules sch ON es.schedule_id = sch.id
        WHERE es.id = ?
    ");
    $stmt->execute([$seat_id]);
    $seat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seat) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Seat not found.']);
        exit;
    }

    // Check if election is in nomination stage
    $settings = json_decode($seat['settings'] ?? '{}', true);
    $stage = $seat['stage'] ?? $settings['election_stage'] ?? 'nomination';
    
    if ($stage !== 'nomination') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nominations are closed. The election is now in voting stage.']);
        exit;
    }

    // Check if user has already nominated for this seat
    $stmt_check = $pdo->prepare("SELECT id FROM election_nominations WHERE nominated_by_user_id = ? AND seat_id = ? LIMIT 1");
    $stmt_check->execute([$user_id, $seat_id]);
    if ($stmt_check->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You have already submitted a nomination for this position.']);
        exit;
    }

    // Validate nominee based on seat's nominee_type
    $nominee_user_id = null;
    $nominee_company_id = null;
    $nomination_text = null;
    $is_valid = false;

    switch ($seat['nominee_type']) {
        case 'user':
            if (!empty($data->nominee_user_id)) {
                $nominee_user_id = filter_var($data->nominee_user_id, FILTER_VALIDATE_INT);
                // Verify user exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $stmt->execute([$nominee_user_id]);
                if ($stmt->fetch()) {
                    $is_valid = true;
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Selected user does not exist.']);
                    exit;
                }
            }
            break;

        case 'company':
            if (!empty($data->nominee_company_id)) {
                $nominee_company_id = filter_var($data->nominee_company_id, FILTER_VALIDATE_INT);
                // Verify company exists
                $stmt = $pdo->prepare("SELECT id FROM companies WHERE id = ?");
                $stmt->execute([$nominee_company_id]);
                if ($stmt->fetch()) {
                    $is_valid = true;
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Selected company/organization does not exist.']);
                    exit;
                }
            }
            break;

        case 'idea':
            if (!empty($data->nomination_text)) {
                $nomination_text = htmlspecialchars(strip_tags(trim($data->nomination_text)));
                if (strlen($nomination_text) >= 3) {
                    $is_valid = true;
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Idea must be at least 3 characters.']);
                    exit;
                }
            }
            break;
    }

    if (!$is_valid) {
        http_response_code(400);
        $type_msg = $seat['nominee_type'] === 'idea' ? 'an idea' : "a {$seat['nominee_type']}";
        echo json_encode(['success' => false, 'message' => "Please provide $type_msg to nominate."]);
        exit;
    }

    // Insert the nomination
    $stmt = $pdo->prepare("
        INSERT INTO election_nominations 
            (seat_id, nominated_by_user_id, nominee_user_id, nominee_company_id, nomination_text, created_at)
        VALUES 
            (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$seat_id, $user_id, $nominee_user_id, $nominee_company_id, $nomination_text]);

    echo json_encode([
        'success' => true, 
        'message' => 'Your nomination has been submitted successfully!'
    ]);

} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
        // Duplicate entry (user already nominated)
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You have already submitted a nomination for this position.']);
    } else {
        error_log("Nomination error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
    }
} catch (Exception $e) {
    error_log("Nomination error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
