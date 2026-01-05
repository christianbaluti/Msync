<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($event_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit();
}

$db = (new Database())->connect();
$response = [];

try {
    // 1. Fetch main event details (unchanged)
    $event_query = "SELECT * FROM events WHERE id = :id";
    $event_stmt = $db->prepare($event_query);
    $event_stmt->bindParam(':id', $event_id);
    $event_stmt->execute();
    $response['details'] = $event_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$response['details']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Event not found.']);
        exit();
    }

    // 2. Fetch event schedules (base query)
    $schedule_query = "SELECT * FROM event_schedules WHERE event_id = :id ORDER BY start_datetime ASC";
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->bindParam(':id', $event_id);
    $schedule_stmt->execute();
    $schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. NEW: Efficiently fetch all facilitators for the retrieved schedules
    if (!empty($schedules)) {
        // Get an array of all schedule IDs
        $schedule_ids = array_column($schedules, 'id');
        
        // Prepare the placeholder string for the IN clause (e.g., ?,?,?)
        $placeholders = implode(',', array_fill(0, count($schedule_ids), '?'));
        
        $facilitator_query = "SELECT sf.schedule_id, u.id as user_id, u.full_name 
                              FROM schedule_facilitators sf
                              JOIN users u ON sf.user_id = u.id
                              WHERE sf.schedule_id IN ($placeholders)";
        
        $facilitator_stmt = $db->prepare($facilitator_query);
        
        // Bind each schedule ID to its placeholder
        foreach ($schedule_ids as $k => $id) {
            $facilitator_stmt->bindValue(($k + 1), $id, PDO::PARAM_INT);
        }
        
        $facilitator_stmt->execute();
        $all_facilitators = $facilitator_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Map facilitators to their schedule ID for easy lookup
        $facilitators_by_schedule = [];
        foreach ($all_facilitators as $facilitator) {
            $facilitators_by_schedule[$facilitator['schedule_id']][] = [
                'id' => $facilitator['user_id'],
                'full_name' => $facilitator['full_name']
            ];
        }
        
        // 4. NEW: Attach the facilitator data to each schedule object
        foreach ($schedules as &$schedule) { // Use a reference (&) to modify the array directly
            $current_schedule_id = $schedule['id'];
            if (isset($facilitators_by_schedule[$current_schedule_id])) {
                $schedule_facilitators = $facilitators_by_schedule[$current_schedule_id];
                // 'facilitators' key with an array of IDs for the edit modal
                $schedule['facilitators'] = array_column($schedule_facilitators, 'id');
                // 'facilitator_names' key with a string for the table view
                $schedule['facilitator_names'] = implode(', ', array_column($schedule_facilitators, 'full_name'));
            } else {
                // Ensure the keys exist even if there are no facilitators
                $schedule['facilitators'] = [];
                $schedule['facilitator_names'] = null;
            }
        }
        unset($schedule); // Break the reference
    }
    
    $response['schedules'] = $schedules;

    // 5. Fetch event ticket types with tickets_sold count
    $tickets_query = "SELECT 
                        ett.*, 
                        mt.name as member_type_name,
                        (SELECT COUNT(*) FROM event_tickets et WHERE et.ticket_type_id = ett.id AND et.status IN ('bought', 'verified')) as tickets_sold
                      FROM event_ticket_types ett
                      LEFT JOIN membership_types mt ON ett.member_type_id = mt.id
                      WHERE ett.event_id = :id 
                      ORDER BY ett.price ASC";
    $tickets_stmt = $db->prepare($tickets_query);
    $tickets_stmt->bindParam(':id', $event_id);
    $tickets_stmt->execute();
    $response['ticket_types'] = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}