<?php
// Fetches all nominations and candidates for one seat
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';
$db = (new Database())->connect();
$seat_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
// ... (Error handling for seat_id) ...

// 1. Get Seat info
$stmt_seat = $db->prepare("SELECT * FROM election_seats WHERE id = ?");
$stmt_seat->execute([$seat_id]);
$seat = $stmt_seat->fetch(PDO::FETCH_ASSOC);

// 2. Get Nominations with names
$stmt_noms = $db->prepare("
    SELECT en.*, u_nominator.full_name AS nominator_name, u_nominee.full_name AS nominee_name, c_nominee.name AS nominee_company
    FROM election_nominations en
    JOIN users u_nominator ON en.nominated_by_user_id = u_nominator.id
    LEFT JOIN users u_nominee ON en.nominee_user_id = u_nominee.id
    LEFT JOIN companies c_nominee ON en.nominee_company_id = c_nominee.id
    WHERE en.seat_id = ?
");
$stmt_noms->execute([$seat_id]);
$nominations = $stmt_noms->fetchAll(PDO::FETCH_ASSOC);

// 3. Get Candidates with vote counts
$stmt_cans = $db->prepare("
    SELECT ec.*, COUNT(v.id) as votes
    FROM election_candidates ec
    LEFT JOIN votes v ON ec.id = v.candidate_id
    WHERE ec.seat_id = ?
    GROUP BY ec.id
    ORDER BY votes DESC, ec.name
");
$stmt_cans->execute([$seat_id]);
$candidates = $stmt_cans->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'seat' => $seat, 'nominations' => $nominations, 'candidates' => $candidates]);