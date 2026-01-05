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
    // 1. Fetch schedule and parent event details
    $stmt_details = $db->prepare("
        SELECT 
            es.*, 
            e.id as event_id, e.title as event_title, e.start_datetime as event_start,
            e.end_datetime as event_end, e.location as event_location, e.status as event_status,
            e.description as event_description
        FROM event_schedules es
        JOIN events e ON es.event_id = e.id
        WHERE es.id = :schedule_id AND es.type = 'awards'
    ");
    $stmt_details->execute([':schedule_id' => $schedule_id]);
    $details = $stmt_details->fetch(PDO::FETCH_ASSOC);

    if (!$details) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Awards schedule not found.']);
        exit;
    }
    
    if ($details['settings']) {
        $details['settings'] = json_decode($details['settings']);
    }

    $event_details = [
        'event_id' => $details['event_id'], 'title' => $details['event_title'],
        'start_datetime' => $details['event_start'], 'end_datetime' => $details['event_end'],
        'location' => $details['event_location'], 'status' => $details['event_status'],
        'description' => $details['event_description']
    ];

    // 2. Fetch facilitators
    $stmt_fac = $db->prepare("
        SELECT u.id, u.full_name FROM schedule_facilitators sf
        JOIN users u ON sf.user_id = u.id
        WHERE sf.schedule_id = :schedule_id ORDER BY u.full_name
    ");
    $stmt_fac->execute([':schedule_id' => $schedule_id]);
    $facilitators = $stmt_fac->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch awards and their nominations
    $stmt_awards = $db->prepare("SELECT * FROM awards WHERE schedule_id = :schedule_id ORDER BY title");
    $stmt_awards->execute([':schedule_id' => $schedule_id]);
    $awards = $stmt_awards->fetchAll(PDO::FETCH_ASSOC);

    $stmt_noms = $db->prepare("
        SELECT 
            n.*, nominator.full_name as nominator_name,
            COALESCE(nominee_user.full_name, nominee_company.name) as nominee_display_name
        FROM nominations n
        JOIN users nominator ON n.nominated_by = nominator.id
        LEFT JOIN users nominee_user ON n.nominee_user_id = nominee_user.id
        LEFT JOIN companies nominee_company ON n.nominee_company_id = nominee_company.id
        WHERE n.award_id = :award_id ORDER BY n.created_at DESC
    ");
    foreach ($awards as $key => $award) {
        $stmt_noms->execute([':award_id' => $award['id']]);
        $awards[$key]['nominations'] = $stmt_noms->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ==================== NEW CODE START ====================
    // 4. Fetch overall nomination counts for all potential nominees in this schedule
    
    // Count for users
    $stmt_user_counts = $db->prepare("
        SELECT n.nominee_user_id, COUNT(n.id) as nomination_count
        FROM nominations n
        JOIN awards a ON n.award_id = a.id
        WHERE a.schedule_id = :schedule_id AND n.nominee_user_id IS NOT NULL
        GROUP BY n.nominee_user_id
    ");
    $stmt_user_counts->execute([':schedule_id' => $schedule_id]);
    $user_nomination_counts = $stmt_user_counts->fetchAll(PDO::FETCH_KEY_PAIR); // Creates an associative array [user_id => count]

    // Count for companies
    $stmt_company_counts = $db->prepare("
        SELECT n.nominee_company_id, COUNT(n.id) as nomination_count
        FROM nominations n
        JOIN awards a ON n.award_id = a.id
        WHERE a.schedule_id = :schedule_id AND n.nominee_company_id IS NOT NULL
        GROUP BY n.nominee_company_id
    ");
    $stmt_company_counts->execute([':schedule_id' => $schedule_id]);
    $company_nomination_counts = $stmt_company_counts->fetchAll(PDO::FETCH_KEY_PAIR); // Creates an associative array [company_id => count]

    // ===================== NEW CODE END =====================

    // 5. Fetch Statistics
    $total_nominations = 0;
    $nominations_by_award = [];
    foreach($awards as $award) {
        $count = count($award['nominations']);
        $total_nominations += $count;
        $nominations_by_award[] = ['title' => $award['title'], 'nomination_count' => $count];
    }
    $stats = [
        'total_awards' => count($awards),
        'total_nominations' => $total_nominations,
        'nominations_by_award' => $nominations_by_award
    ];


    // 6. Assemble and respond
    echo json_encode([
        'success' => true,
        'data' => [
            'details' => $details,
            'event_details' => $event_details,
            'facilitators' => $facilitators,
            'awards' => $awards,
            'stats' => $stats,
            'user_nomination_counts' => $user_nomination_counts,       // <-- ADD THIS
            'company_nomination_counts' => $company_nomination_counts, // <-- ADD THIS
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}