<?php
// api/awards/submit_nomination.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';
require __DIR__ . '/../eligibility_helper.php'; // Need eligibility checker

$auth_data = get_auth_user();
$nominator_user_id = $auth_data->user_id;

// Get raw posted data
$data = json_decode(file_get_contents("php://input"));

// --- Basic Input Validation ---
if (
    !$data ||
    !isset($data->award_id) || !is_numeric($data->award_id) ||
    !isset($data->nomination_text) || empty(trim($data->nomination_text)) ||
    (!isset($data->nominee_user_id) && !isset($data->nominee_company_id)) || // Must have one nominee ID
    (isset($data->nominee_user_id) && $data->nominee_user_id !== null && !is_numeric($data->nominee_user_id)) || // Check if set, not null, and numeric
    (isset($data->nominee_company_id) && $data->nominee_company_id !== null && !is_numeric($data->nominee_company_id)) || // Check if set, not null, and numeric
    (isset($data->nominee_user_id) && $data->nominee_user_id !== null && isset($data->nominee_company_id) && $data->nominee_company_id !== null) // Cannot have both
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input. Provide award ID, text, and either user ID or company ID.']);
    exit();
}

$award_id = intval($data->award_id);
$nomination_text = trim($data->nomination_text);
// Handle null values explicitly for binding
$nominee_user_id = isset($data->nominee_user_id) && is_numeric($data->nominee_user_id) ? intval($data->nominee_user_id) : null;
$nominee_company_id = isset($data->nominee_company_id) && is_numeric($data->nominee_company_id) ? intval($data->nominee_company_id) : null;


try {
    // --- Get Award and Schedule Settings ---
    $stmt_award_sched = $pdo->prepare("
        SELECT a.for_entity, es.settings
        FROM awards a
        JOIN event_schedules es ON a.schedule_id = es.id
        WHERE a.id = ? AND es.status = 'active' AND (es.stage = 'nomination' OR es.stage IS NULL) -- Ensure schedule is active and in nomination phase (or stage not set)
        LIMIT 1");
    $stmt_award_sched->execute([$award_id]);
    $award_sched_info = $stmt_award_sched->fetch(PDO::FETCH_ASSOC);

    if (!$award_sched_info) {
        throw new Exception("Award not found, schedule is inactive, or nomination period is closed.");
    }

    $for_entity = $award_sched_info['for_entity'];
    $settings = json_decode($award_sched_info['settings'] ?? '{}', true);

    $nominator_eligibility_rules = $settings['nominator_eligibility'] ?? null;
    $nominee_eligibility_rules = $settings['nominee_eligibility'] ?? null;

    // --- Validate Entity Type Match ---
    if (($for_entity == 'user' && $nominee_company_id !== null) || ($for_entity == 'company' && $nominee_user_id !== null)) {
        throw new Exception("Nominee type mismatch. This award is for a '$for_entity'.");
    }

    // --- Check Nominator Eligibility ---
    if (!check_user_eligibility($pdo, $nominator_user_id, $nominator_eligibility_rules)) {
        http_response_code(403); // Forbidden
        throw new Exception("You are not eligible to nominate for this award.");
    }

    // --- Check Nominee Eligibility ---
    if ($for_entity == 'user' && !check_user_eligibility($pdo, $nominee_user_id, $nominee_eligibility_rules)) {
        throw new Exception("The selected user is not eligible to be nominated for this award.");
    } elseif ($for_entity == 'company') {
        // Placeholder: Add company eligibility check if necessary
        $is_company_eligible = true; // Assume true for now
        // $is_company_eligible = check_company_eligibility($pdo, $nominee_company_id, $nominee_eligibility_rules);
        if (!$is_company_eligible) {
             throw new Exception("The selected company is not eligible to be nominated for this award.");
        }
    }


    // --- Check if Already Nominated ---
    $stmt_check = $pdo->prepare("SELECT 1 FROM nominations WHERE award_id = ? AND nominated_by = ? LIMIT 1");
    $stmt_check->execute([$award_id, $nominator_user_id]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("You have already submitted a nomination for this award.");
    }

    // --- Insert Nomination ---
    $stmt_insert = $pdo->prepare("INSERT INTO nominations
                                    (award_id, nominee_user_id, nominee_company_id, nominated_by, nomination_text, verified, created_at)
                                  VALUES (?, ?, ?, ?, ?, 0, NOW())");

    // Bind parameters carefully, especially NULLable ones
    $stmt_insert->bindParam(1, $award_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(2, $nominee_user_id, $nominee_user_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_insert->bindParam(3, $nominee_company_id, $nominee_company_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_insert->bindParam(4, $nominator_user_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(5, $nomination_text, PDO::PARAM_STR);


    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Nomination submitted successfully.']);
    } else {
         error_log("Failed to execute nomination insert: " . print_r($stmt_insert->errorInfo(), true));
        throw new Exception("Database error: Could not save nomination.");
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Submit Award Nomination PDO Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during nomination.']);
} catch (Exception $e) {
    // Use existing response code if set (like 403), otherwise default to 400
    if (http_response_code() < 400) {
        http_response_code(400); // Bad Request
    }
    error_log("Submit Award Nomination General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>