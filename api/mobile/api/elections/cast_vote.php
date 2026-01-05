<?php
// api/elections/cast_vote.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

$auth_data = get_auth_user();
$user_id = $auth_data->user_id;

$data = json_decode(file_get_contents("php://input"));
$candidate_id = $data->candidate_id ?? null;

if (!$candidate_id) {
    http_response_code(400);
    echo json_encode(['message' => 'Candidate ID is required.']);
    exit;
}

try {
    $stmt_seat = $pdo->prepare("SELECT seat_id FROM election_candidates WHERE id = ?");
    $stmt_seat->execute([$candidate_id]);
    $seat = $stmt_seat->fetch(PDO::FETCH_ASSOC);
    if (!$seat) { throw new Exception("Candidate not found."); }
    $seat_id = $seat['seat_id'];
    
    // Server-side check using UNIQUE key is preferred, but this logic is a safe fallback.
    $stmt_vote_check = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE user_id = ? AND seat_id = ?");
    $stmt_vote_check->execute([$user_id, $seat_id]);
    if ($stmt_vote_check->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['message' => 'You have already cast your vote for this seat.']);
        exit;
    }
    
    $stmt_vote = $pdo->prepare("INSERT INTO votes (candidate_id, user_id, seat_id, voted_at) VALUES (?, ?, ?, NOW())");
    $stmt_vote->execute([$candidate_id, $user_id, $seat_id]);

    echo json_encode(['message' => 'Your vote has been cast successfully!']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>