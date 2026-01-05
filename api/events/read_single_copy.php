<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// --- Get Input and Validate ---
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_simple_request = isset($_GET['simple']) && $_GET['simple'] === 'true';

if ($id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID not provided or invalid.']);
    exit();
}

$db = (new Database())->connect();
try {
    // --- 1. Fetch Core Event Details with Creator's Name ---
    $event_query = "SELECT e.*, u.full_name AS created_by_name 
                    FROM events e 
                    LEFT JOIN users u ON e.created_by = u.id 
                    WHERE e.id = :id";
    $stmt = $db->prepare($event_query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Event not found.']);
        exit();
    }

    // --- Handle 'simple' request for the Edit Modal ---
    // This avoids unnecessary queries when just filling the form.
    if ($is_simple_request) {
        // Format datetime-local fields correctly for HTML form inputs
        $event['start_datetime'] = date('Y-m-d\TH:i', strtotime($event['start_datetime']));
        $event['end_datetime'] = date('Y-m-d\TH:i', strtotime($event['end_datetime']));
        echo json_encode($event);
        exit();
    }

    // --- 2. Fetch Related Data for the View Modal ---
    
    // Fetch Event Schedules
    $schedules_query = "SELECT * FROM event_schedules WHERE event_id = :id ORDER BY start_datetime ASC";
    $stmt_schedules = $db->prepare($schedules_query);
    $stmt_schedules->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_schedules->execute();
    $schedules = $stmt_schedules->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Ticket Types
    $ticket_types_query = "SELECT * FROM event_ticket_types WHERE event_id = :id ORDER BY name ASC";
    $stmt_ticket_types = $db->prepare($ticket_types_query);
    $stmt_ticket_types->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_ticket_types->execute();
    $ticket_types = $stmt_ticket_types->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Count of Tickets Sold (status 'bought' or 'verified')
    $tickets_sold_query = "SELECT COUNT(id) as total FROM event_tickets WHERE event_id = :id AND status IN ('bought', 'verified')";
    $stmt_tickets_sold = $db->prepare($tickets_sold_query);
    $stmt_tickets_sold->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_tickets_sold->execute();
    $tickets_sold = $stmt_tickets_sold->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // --- 3. EXPANSION: Fetch Ticket Holders (Users) ---
    $ticket_holders_query = "
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.phone,
            et.ticket_code,
            ett.name AS ticket_type_name
        FROM event_tickets et
        JOIN users u ON et.user_id = u.id
        JOIN event_ticket_types ett ON et.ticket_type_id = ett.id
        WHERE et.event_id = :id
          AND et.status IN ('bought', 'verified')
          AND et.user_id IS NOT NULL
        ORDER BY u.full_name ASC
    ";
    $stmt_ticket_holders = $db->prepare($ticket_holders_query);
    $stmt_ticket_holders->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_ticket_holders->execute();
    $ticket_holders = $stmt_ticket_holders->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. EXPANSION: Fetch Company Purchases Summary ---
    $company_purchases_query = "
        SELECT
            c.id,
            c.name,
            COUNT(et.id) AS ticket_count
        FROM event_tickets et
        JOIN users u ON et.user_id = u.id
        JOIN companies c ON u.company_id = c.id
        WHERE et.event_id = :id
          AND et.status IN ('bought', 'verified')
          AND u.company_id IS NOT NULL
        GROUP BY c.id, c.name
        ORDER BY ticket_count DESC, c.name ASC
    ";
    $stmt_company_purchases = $db->prepare($company_purchases_query);
    $stmt_company_purchases->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_company_purchases->execute();
    $company_purchases = $stmt_company_purchases->fetchAll(PDO::FETCH_ASSOC);


    // --- 5. Assemble the Final JSON Response ---
    $response_data = [
        'success' => true,
        'event' => $event,
        'schedules' => $schedules,
        'ticket_types' => $ticket_types,
        'tickets_sold' => (int)$tickets_sold,
        'ticket_holders' => $ticket_holders,        // Added data
        'company_purchases' => $company_purchases   // Added data
    ];

    echo json_encode($response_data);

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred while fetching event details.']);
}