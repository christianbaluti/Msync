<?php
// models/EventTicket.php
class EventTicket {
    private $pdo;
    private $table_name = 'event_tickets';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function purchase($ticketTypeId, $eventId, $userId) {
        // First, get the price from the event_ticket_types table
        $query = "SELECT price FROM event_ticket_types WHERE id = :ticket_type_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':ticket_type_id', $ticketTypeId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false; // Ticket type not found
        }
        $price = $row['price'];

        // Generate a unique ticket code and QR code
        $ticketCode = uniqid('TICKET-');
        $qrCode = 'qr_code_placeholder_' . $ticketCode; // In a real app, you'd generate a QR code image or data

        $query = "INSERT INTO " . $this->table_name . " (ticket_type_id, event_id, user_id, price, status, ticket_code, qr_code, purchase_date)
                  VALUES (:ticket_type_id, :event_id, :user_id, :price, 'purchased', :ticket_code, :qr_code, NOW())";

        $stmt = $this->pdo->prepare($query);

        $stmt->bindParam(':ticket_type_id', $ticketTypeId);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':ticket_code', $ticketCode);
        $stmt->bindParam(':qr_code', $qrCode);

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        } else {
            return false;
        }
    }
}