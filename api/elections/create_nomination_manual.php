<?php
// /api/elections/create_nomination_manual.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$db = (new Database())->connect();
$data = json_decode(file_get_contents('php://input'));

// --- Input Validation ---
if (empty($data->seat_id)) {
    echo json_encode(['success' => false, 'message' => 'Position (seat_id) is required.']);
    exit;
}

$seat_id = filter_var($data->seat_id, FILTER_VALIDATE_INT);
$admin_user_id = $_SESSION['user_id']; // MODIFIED: Get admin ID from the session.

// Get the nominee type for the selected seat to validate the input
$stmt = $db->prepare("SELECT nominee_type FROM election_seats WHERE id = :seat_id");
$stmt->bindParam(':seat_id', $seat_id);
$stmt->execute();
$seat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seat) {
    echo json_encode(['success' => false, 'message' => 'Invalid Position ID.']);
    exit;
}

$nominee_user_id = null;
$nominee_company_id = null;
$nomination_text = null;
$is_valid_nominee = false;

// Validate nominee based on the seat's expected type
switch ($seat['nominee_type']) {
    case 'user':
        if (!empty($data->nominee_user_id)) {
            $nominee_user_id = filter_var($data->nominee_user_id, FILTER_VALIDATE_INT);
            $is_valid_nominee = true;
        }
        break;
    case 'company':
        if (!empty($data->nominee_company_id)) {
            $nominee_company_id = filter_var($data->nominee_company_id, FILTER_VALIDATE_INT);
            $is_valid_nominee = true;
        }
        break;
    case 'idea':
        if (!empty($data->nomination_text)) {
            // Assuming you don't have a Sanitize class, use a standard function.
            $nomination_text = htmlspecialchars(strip_tags($data->nomination_text));
            $is_valid_nominee = true;
        }
        break;
}

if (!$is_valid_nominee) {
    echo json_encode(['success' => false, 'message' => 'A valid nominee for this position type is required.']);
    exit;
}

// --- Database Insertion ---
$query = "INSERT INTO election_nominations 
            (seat_id, nominated_by_user_id, nominee_user_id, nominee_company_id, nomination_text) 
          VALUES 
            (:seat_id, :nominated_by_user_id, :nominee_user_id, :nominee_company_id, :nomination_text)";

try {
    $stmt = $db->prepare($query);

    $stmt->bindParam(':seat_id', $seat_id, PDO::PARAM_INT);
    $stmt->bindParam(':nominated_by_user_id', $admin_user_id, PDO::PARAM_INT);
    $stmt->bindParam(':nominee_user_id', $nominee_user_id, $nominee_user_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':nominee_company_id', $nominee_company_id, $nominee_company_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':nomination_text', $nomination_text, $nomination_text ? PDO::PARAM_STR : PDO::PARAM_NULL);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Nomination successfully added.']);
    } else {
        throw new Exception('Failed to execute database statement.');
    }
} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'message' => 'A nomination from this administrator already exists for this position.']);
    } else {
        error_log($e->getMessage()); // Log error for debugging
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
    }
} catch (Exception $e) {
    error_log($e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}