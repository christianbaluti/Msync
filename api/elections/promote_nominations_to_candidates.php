<?php
// /api/elections/promote_nominations_to_candidates.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// Ensure the request is a POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$db = (new Database())->connect();
$data = json_decode(file_get_contents('php://input'));

// Basic validation of the incoming data
if (empty($data->seat_id) || !is_array($data->nomination_ids) || empty($data->nomination_ids)) {
    echo json_encode(['success' => false, 'message' => 'Seat ID and at least one Nomination ID are required.']);
    exit;
}

$seat_id = filter_var($data->seat_id, FILTER_VALIDATE_INT);
$nomination_ids = array_map('intval', $data->nomination_ids);
$placeholders = rtrim(str_repeat('?,', count($nomination_ids)), ',');

// Start a transaction for data integrity
$db->beginTransaction();

try {
    // Step 1: Fetch the details of the nominations to be promoted
    $query_fetch = "
        SELECT
            en.id,
            en.nomination_text,
            u.full_name AS user_name,
            c.name AS company_name
        FROM election_nominations en
        LEFT JOIN users u ON en.nominee_user_id = u.id
        LEFT JOIN companies c ON en.nominee_company_id = c.id
        WHERE en.id IN ($placeholders) AND en.seat_id = ?
    ";
    
    $stmt_fetch = $db->prepare($query_fetch);
    $params = array_merge($nomination_ids, [$seat_id]);
    $stmt_fetch->execute($params);
    $nominations_to_promote = $stmt_fetch->fetchAll(PDO::FETCH_ASSOC);

    if (empty($nominations_to_promote)) {
        throw new Exception("No valid nominations found for this position.");
    }
    
    // Step 2: Insert the new candidates into the election_candidates table
    $query_insert = "INSERT INTO election_candidates (seat_id, name) VALUES (:seat_id, :name)";
    $stmt_insert = $db->prepare($query_insert);
    
    foreach ($nominations_to_promote as $nom) {
        $candidate_name = $nom['user_name'] ?? $nom['company_name'] ?? $nom['nomination_text'];
        if (empty($candidate_name)) continue; // Skip if for some reason the name is empty
        
        $stmt_insert->execute([
            ':seat_id' => $seat_id,
            ':name' => $candidate_name
        ]);
    }
    
    // Step 3: Delete the promoted nominations from the original table
    $query_delete = "DELETE FROM election_nominations WHERE id IN ($placeholders)";
    $stmt_delete = $db->prepare($query_delete);
    $stmt_delete->execute($nomination_ids);
    
    // If all steps succeeded, commit the changes to the database
    $db->commit();
    
    $count = count($nominations_to_promote);
    echo json_encode(['success' => true, 'message' => "$count nominee(s) successfully promoted to candidates."]);

} catch (Exception $e) {
    // If any step failed, roll back the entire transaction
    $db->rollBack();
    // For debugging: error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during promotion. No changes were made.']);
}