<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$schedule_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$schedule_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Schedule ID.']);
    exit;
}

try {
    // 1. Fetch Core Schedule Details
    $stmt = $db->prepare("SELECT es.*, e.title as event_title, e.id as event_id FROM event_schedules es JOIN events e ON es.event_id = e.id WHERE es.id = ?");
    $stmt->execute([$schedule_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$details) { throw new Exception('Schedule not found.'); }
    
    // Default null stage to 'nomination'
    if (empty($details['stage'])) { $details['stage'] = 'nomination'; }
    $details['settings'] = json_decode($details['settings'] ?? '{}', true);

    // 2. Fetch All Positions (Seats) for this schedule
    $stmt_seats = $db->prepare("SELECT * FROM election_seats WHERE schedule_id = ? ORDER BY name");
    $stmt_seats->execute([$schedule_id]);
    $positions = $stmt_seats->fetchAll(PDO::FETCH_ASSOC);

    // 3. For each position, fetch its nominations and candidates
    foreach ($positions as &$pos) {
        // Fetch Nominations
        $stmt_noms = $db->prepare("
            SELECT en.*, u_nominator.full_name AS nominator_name, u_nominee.full_name AS nominee_name, c_nominee.name AS nominee_company
            FROM election_nominations en
            JOIN users u_nominator ON en.nominated_by_user_id = u_nominator.id
            LEFT JOIN users u_nominee ON en.nominee_user_id = u_nominee.id
            LEFT JOIN companies c_nominee ON en.nominee_company_id = c_nominee.id
            WHERE en.seat_id = ?
        ");
        $stmt_noms->execute([$pos['id']]);
        $nominations = $stmt_noms->fetchAll(PDO::FETCH_ASSOC);
        foreach ($nominations as &$nom) {
             if ($pos['nominee_type'] == 'user') $nom['nominee_display_name'] = $nom['nominee_name'];
             else if ($pos['nominee_type'] == 'company') $nom['nominee_display_name'] = $nom['nominee_company'];
             else $nom['nominee_display_name'] = '"' . substr($nom['nomination_text'], 0, 50) . '..."';
        }
        $pos['nominations'] = $nominations;

        // Fetch Candidates with vote counts
        $stmt_cans = $db->prepare("
            SELECT ec.*, COUNT(v.id) as votes
            FROM election_candidates ec
            LEFT JOIN votes v ON ec.id = v.candidate_id
            WHERE ec.seat_id = ?
            GROUP BY ec.id ORDER BY votes DESC, ec.name
        ");
        $stmt_cans->execute([$pos['id']]);
        $pos['candidates'] = $stmt_cans->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($pos); // Unset reference

    // 4. Calculate Statistics
    // (This is a simplified version; a full implementation might be more complex based on settings)
    $stats_total_votes_stmt = $db->prepare("SELECT COUNT(v.id) FROM votes v JOIN election_seats es ON v.seat_id = es.id WHERE es.schedule_id = ?");
    $stats_total_votes_stmt->execute([$schedule_id]);
    $total_votes = $stats_total_votes_stmt->fetchColumn();

    $stats_total_noms_stmt = $db->prepare("SELECT COUNT(en.id) FROM election_nominations en JOIN election_seats es ON en.seat_id = es.id WHERE es.schedule_id = ?");
    $stats_total_noms_stmt->execute([$schedule_id]);
    $total_nominations = $stats_total_noms_stmt->fetchColumn();
    
    // Votes by position
    $stmt_vbp = $db->prepare("SELECT es.name, COUNT(v.id) as vote_count FROM votes v JOIN election_seats es ON v.seat_id = es.id WHERE es.schedule_id = ? GROUP BY v.seat_id");
    $stmt_vbp->execute([$schedule_id]);
    $votes_by_position = $stmt_vbp->fetchAll(PDO::FETCH_ASSOC);

    // Noms by position
    $stmt_nbp = $db->prepare("SELECT es.name, COUNT(en.id) as nomination_count FROM election_nominations en JOIN election_seats es ON en.seat_id = es.id WHERE es.schedule_id = ? GROUP BY en.seat_id");
    $stmt_nbp->execute([$schedule_id]);
    $nominations_by_position = $stmt_nbp->fetchAll(PDO::FETCH_ASSOC);

    $stats = [
        'eligible_voters' => 'N/A', // This requires complex logic based on settings
        'total_votes' => (int)$total_votes,
        'total_nominations' => (int)$total_nominations,
        'votes_by_position' => $votes_by_position,
        'nominations_by_position' => $nominations_by_position
    ];

    // 5. Assemble and return final payload
    echo json_encode([
        'success' => true, 
        'data' => [
            'details' => $details,
            'positions' => $positions,
            'stats' => $stats
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Dashboard Error: ' . $e->getMessage()]);
}