<?php
// Set header to JSON
header('Content-Type: application/json');

// Include core files
require_once dirname(__DIR__) . '/core/initialize.php';

// Instantiate DB & connect
$db = (new Database())->connect();

// Get Event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Check for 'simple' flag (used by edit modal)
$simple = isset($_GET['simple']) ? (bool)$_GET['simple'] : false;

if ($event_id === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid Event ID.']);
    exit;
}

// --- Handle 'simple' request for Edit Modal ---
// This returns only the raw event data
if ($simple) {
    try {
        $stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
        $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            echo json_encode($event); // Return simple event object
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => 'Event not found.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// --- Handle Full Data Request for View Modal ---
$response = [];

try {
    // 1. Fetch Event Details (with created_by_name)
    $query_event = "
        SELECT e.*, u.full_name as created_by_name 
        FROM events e 
        LEFT JOIN users u ON e.created_by = u.id
        WHERE e.id = :id
    ";
    $stmt_event = $db->prepare($query_event);
    $stmt_event->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt_event->execute();
    $event = $stmt_event->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        throw new Exception('Event not found.');
    }
    $response['event'] = $event;

    // 2. Fetch Schedules
    $query_schedules = "SELECT * FROM event_schedules WHERE event_id = :id ORDER BY start_datetime ASC";
    $stmt_schedules = $db->prepare($query_schedules);
    $stmt_schedules->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt_schedules->execute();
    $response['schedules'] = $stmt_schedules->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Ticket Types (with member type name)
    $query_tickets = "
        SELECT tt.*, mt.name as member_type_name
        FROM event_ticket_types tt
        LEFT JOIN membership_types mt ON tt.member_type_id = mt.id
        WHERE tt.event_id = :id
        ORDER BY tt.price ASC
    ";
    $stmt_tickets = $db->prepare($query_tickets);
    $stmt_tickets->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt_tickets->execute();
    $response['ticket_types'] = $stmt_tickets->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Ticket Holders (with ticket type name)
    $query_holders = "
        SELECT 
            COALESCE(u.full_name, c.name) as full_name, 
            COALESCE(u.email, c.email) as email,
            u.phone,
            et.ticket_code,
            ett.name as ticket_type_name
        FROM event_tickets et
        LEFT JOIN users u ON et.user_id = u.id
        LEFT JOIN companies c ON et.company_id = c.id
        JOIN event_ticket_types ett ON et.ticket_type_id = ett.id
        WHERE et.event_id = :id AND (et.status = 'bought' OR et.status = 'verified')
        ORDER BY full_name ASC
    ";
    $stmt_holders = $db->prepare($query_holders);
    $stmt_holders->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt_holders->execute();
    $response['ticket_holders'] = $stmt_holders->fetchAll(PDO::FETCH_ASSOC);

    // 5. Fetch Tickets Sold Count
    $query_sold = "SELECT COUNT(*) FROM event_tickets WHERE event_id = :id AND (status = 'bought' OR status = 'verified')";
    $stmt_sold = $db->prepare($query_sold);
    $stmt_sold->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt_sold->execute();
    $response['tickets_sold'] = $stmt_sold->fetchColumn();
    
    // 6. Fetch Company Purchases (Group by company)
    $query_company = "
        SELECT 
            c.name, 
            COUNT(et.id) as ticket_count
        FROM event_tickets et
        JOIN companies c ON et.company_id = c.id
        WHERE et.event_id = :id AND (et.status = 'bought' OR et.status = 'verified')
        GROUP BY c.id, c.name
        ORDER BY c.name ASC
    ";
    $stmt_company = $db->prepare($query_company);
    $stmt_company->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt_company->execute();
    $response['company_purchases'] = $stmt_company->fetchAll(PDO::FETCH_ASSOC);

    // 7. *** NEW: Fetch Check-ins (Merchandise Distribution) ***
    // This query joins distribution to tickets (to get event_id), users (to get name), 
    // and merchandise (to get item name).
    // 7. *** NEW: Fetch Check-ins (Merchandise Distribution) ***
    // This query joins distribution to tickets (to get event_id), users/companies (to get name), 
    // and merchandise (to get item name).
    $query_checkins = "
        SELECT
            md.distributed_at,
            COALESCE(u.full_name, c.name) as full_name,
            m.name AS merchandise_name
        FROM 
            merchandise_distribution md
        JOIN 
            event_tickets et ON md.ticket_id = et.id
        LEFT JOIN 
            users u ON et.user_id = u.id
        LEFT JOIN 
            companies c ON et.company_id = c.id 
        JOIN 
            merchandise m ON md.merch_id = m.id
        WHERE 
            et.event_id = :id
        ORDER BY 
            md.distributed_at ASC
    ";
    $stmt_checkins = $db->prepare($query_checkins);
    $stmt_checkins->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt_checkins->execute();
    $response['check_ins'] = $stmt_checkins->fetchAll(PDO::FETCH_ASSOC);
    
    // --- Final Output ---
    echo json_encode($response);

} catch (Exception $e) {
    // Catch any exception (including the 'Event not found' one)
    if (http_response_code() == 200) {
        http_response_code(500); // Set 500 if not already set
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>