<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
$data = json_decode(file_get_contents("php://input"), true);
$nomination_id = $data['id'] ?? 0;
if (!$nomination_id) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'Invalid Nomination ID.']); exit;
}
$db = (new Database())->connect();
$db->beginTransaction();
try {
    $stmt_nom = $db->prepare("SELECT * FROM election_nominations WHERE id = ?");
    $stmt_nom->execute([$nomination_id]);
    $nomination = $stmt_nom->fetch(PDO::FETCH_ASSOC);
    if(!$nomination) { throw new Exception("Nomination not found."); }

    $stmt_seat = $db->prepare("SELECT nominee_type FROM election_seats WHERE id = ?");
    $stmt_seat->execute([$nomination['seat_id']]);
    $seat_type = $stmt_seat->fetchColumn();

    $candidate_name = '';
    if ($seat_type == 'idea') {
        $candidate_name = $nomination['nomination_text'];
    } else if ($seat_type == 'user' && $nomination['nominee_user_id']) {
        $stmt_user = $db->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt_user->execute([$nomination['nominee_user_id']]);
        $candidate_name = $stmt_user->fetchColumn();
    } else if ($seat_type == 'company' && $nomination['nominee_company_id']) {
        $stmt_comp = $db->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt_comp->execute([$nomination['nominee_company_id']]);
        $candidate_name = $stmt_comp->fetchColumn();
    }
    if (empty($candidate_name)) { throw new Exception("Could not determine candidate name."); }

    $stmt_insert = $db->prepare("INSERT INTO election_candidates (seat_id, name) VALUES (?, ?)");
    $stmt_insert->execute([$nomination['seat_id'], $candidate_name]);
    
    $stmt_del = $db->prepare("DELETE FROM election_nominations WHERE id = ?");
    $stmt_del->execute([$nomination_id]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Nomination promoted.']);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}