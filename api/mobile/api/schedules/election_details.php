<?php
// api/schedules/election_details.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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
    echo json_encode(['message' => 'Invalid JSON body.']);
    exit;
}

$auth_data = get_auth_user($data);
$user_id = $auth_data->user_id;

$schedule_id = $data->id ?? null;

if (!$schedule_id) {
    http_response_code(400);
    echo json_encode(['message' => 'Schedule ID (id) is required in the request body.']);
    exit;
}

try {
    // Fetch schedule with event_id and description
    $stmt_schedule = $pdo->prepare("
        SELECT id, event_id, title, description, settings, stage 
        FROM event_schedules 
        WHERE id = ? AND type = 'voting'
    ");
    $stmt_schedule->execute([$schedule_id]);
    $schedule = $stmt_schedule->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        http_response_code(404);
        echo json_encode(['message' => 'Election schedule not found.']);
        exit;
    }
    
    $settings = json_decode($schedule['settings'] ?? '{}', true);
    // Stage from column or settings (column takes priority)
    $stage = $schedule['stage'] ?? $settings['election_stage'] ?? 'nomination';
    $event_id = $schedule['event_id'];

    // =====================================================
    // ELIGIBILITY CHECK BASED ON SETTINGS
    // =====================================================
    // Settings structure:
    // {
    //   "eligibility": {
    //     "all_tickets": false,      // Any ticket holder for this event
    //     "non_members": true,       // Users without active membership
    //     "only_members": false,     // Users with any active membership
    //     "member_types": [1,4,5],   // Users with specific membership types (if not empty)
    //     "ticket_types": [12,13,14] // Users with specific ticket types (if not empty)
    //   }
    // }
    //
    // Logic: User is eligible if they match ANY of the enabled criteria
    // =====================================================
    
    $eligibility = $settings['eligibility'] ?? [];
    $all_tickets = $eligibility['all_tickets'] ?? false;
    $non_members = $eligibility['non_members'] ?? false;
    $only_members = $eligibility['only_members'] ?? false;
    $member_types = $eligibility['member_types'] ?? [];
    $ticket_types = $eligibility['ticket_types'] ?? [];
    
    // Filter out empty values from arrays
    $member_types = array_filter($member_types, fn($v) => !empty($v));
    $ticket_types = array_filter($ticket_types, fn($v) => !empty($v));
    
    $is_eligible = false;
    $eligibility_reason = '';
    
    // If no eligibility criteria set, default to all ticket holders
    $has_any_criteria = $all_tickets || $non_members || $only_members || !empty($member_types) || !empty($ticket_types);
    
    if (!$has_any_criteria) {
        // Default: any ticket holder for this event
        $stmt = $pdo->prepare("
            SELECT id FROM event_tickets 
            WHERE event_id = ? AND user_id = ? AND status = 'bought'
            LIMIT 1
        ");
        $stmt->execute([$event_id, $user_id]);
        $is_eligible = $stmt->fetch() ? true : false;
        if ($is_eligible) $eligibility_reason = 'ticket_holder';
    } else {
        // Check each enabled criterion - user is eligible if ANY match
        
        // 1. Check all_tickets - any bought ticket for this event
        if (!$is_eligible && $all_tickets) {
            $stmt = $pdo->prepare("
                SELECT id FROM event_tickets 
                WHERE event_id = ? AND user_id = ? AND status = 'bought'
                LIMIT 1
            ");
            $stmt->execute([$event_id, $user_id]);
            if ($stmt->fetch()) {
                $is_eligible = true;
                $eligibility_reason = 'all_tickets';
            }
        }
        
        // 2. Check specific ticket_types - bought ticket of specific types
        if (!$is_eligible && !empty($ticket_types)) {
            $placeholders = implode(',', array_fill(0, count($ticket_types), '?'));
            $stmt = $pdo->prepare("
                SELECT id FROM event_tickets 
                WHERE event_id = ? AND user_id = ? AND status = 'bought' AND ticket_type_id IN ($placeholders)
                LIMIT 1
            ");
            $params = array_merge([$event_id, $user_id], array_values($ticket_types));
            $stmt->execute($params);
            if ($stmt->fetch()) {
                $is_eligible = true;
                $eligibility_reason = 'specific_ticket_type';
            }
        }
        
        // 3. Check only_members - any active membership
        if (!$is_eligible && $only_members) {
            $stmt = $pdo->prepare("
                SELECT id FROM membership_subscriptions 
                WHERE user_id = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            if ($stmt->fetch()) {
                $is_eligible = true;
                $eligibility_reason = 'active_member';
            }
        }
        
        // 4. Check specific member_types - active membership of specific types
        if (!$is_eligible && !empty($member_types)) {
            $placeholders = implode(',', array_fill(0, count($member_types), '?'));
            $stmt = $pdo->prepare("
                SELECT id FROM membership_subscriptions 
                WHERE user_id = ? AND status = 'active' AND membership_type_id IN ($placeholders)
                LIMIT 1
            ");
            $params = array_merge([$user_id], array_values($member_types));
            $stmt->execute($params);
            if ($stmt->fetch()) {
                $is_eligible = true;
                $eligibility_reason = 'specific_membership_type';
            }
        }
        
        // 5. Check non_members - no active membership
        if (!$is_eligible && $non_members) {
            $stmt = $pdo->prepare("
                SELECT id FROM membership_subscriptions 
                WHERE user_id = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) {
                $is_eligible = true;
                $eligibility_reason = 'non_member';
            }
        }
    }

    // Fetch seats and candidates
    $stmt_seats = $pdo->prepare("SELECT id, name, description, nominee_type FROM election_seats WHERE schedule_id = ?");
    $stmt_seats->execute([$schedule_id]);
    $seats = $stmt_seats->fetchAll(PDO::FETCH_ASSOC);

    // Base URL for election uploads
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $uploads_base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/uploads/elections/';

    foreach ($seats as &$seat) {
        $stmt_candidates = $pdo->prepare("SELECT id, name, description, image_url FROM election_candidates WHERE seat_id = ?");
        $stmt_candidates->execute([$seat['id']]);
        $candidates = $stmt_candidates->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_nom_check = $pdo->prepare("SELECT id FROM election_nominations WHERE nominated_by_user_id = ? AND seat_id = ? LIMIT 1");
        $stmt_nom_check->execute([$user_id, $seat['id']]);
        $seat['user_has_nominated'] = $stmt_nom_check->fetch() ? true : false;
        
        foreach ($candidates as &$candidate) {
            if (!empty($candidate['image_url'])) {
                $candidate['image_url'] = $uploads_base_url . $candidate['image_url'];
            }
        }
        $seat['candidates'] = $candidates;

        $stmt_vote_check = $pdo->prepare("SELECT COUNT(*) FROM votes v JOIN election_candidates c ON v.candidate_id = c.id WHERE v.user_id = ? AND c.seat_id = ?");
        $stmt_vote_check->execute([$user_id, $seat['id']]);
        $seat['user_has_voted'] = $stmt_vote_check->fetchColumn() > 0;
    }
    unset($seat);

    echo json_encode([
        'title' => $schedule['title'],
        'description' => $schedule['description'] ?? '',
        'stage' => $stage,
        'is_eligible' => $is_eligible,
        'seats' => $seats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
