<?php
/**
 * Generates and streams a PDF receipt for a given receipt ID.
 * URL: /api/receipts/download.php?receipt_id=XX
 */

// 1. BOOTSTRAPPING
// Ensure Composer's autoloader is included for PDF generation library (e.g., Dompdf)
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Include core application files
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/PdfGenerator.php'; // Assuming your PDF class is here

// 2. INPUT VALIDATION
// Get the receipt ID from the URL and validate it's an integer.
$receipt_id = filter_input(INPUT_GET, 'receipt_id', FILTER_VALIDATE_INT);

if (!$receipt_id) {
    http_response_code(400); // Bad Request
    die("Error: A valid numeric receipt ID is required.");
}

try {
    // 3. DATABASE CONNECTION
    $database = new Database();
    $db = $database->connect();

    // 4. DATA FETCHING
    // This comprehensive query fetches all necessary details for the receipt
    // regardless of the payment type (membership, event, etc.) using LEFT JOINs.
    $query = "
        SELECT
            r.receipt_number,
            r.issued_at,
            p.amount,
            p.method,
            p.transaction_date,
            p.payment_type,
            COALESCE(u.full_name, c.name) AS payer_name,
            -- Item details derived from different related tables
            mt.name AS membership_type_name,
            e.title AS event_title,
            mo.id AS order_id,
            inv.id AS invoice_id
        FROM
            receipts r
        JOIN
            payments p ON r.payment_id = p.id
        LEFT JOIN
            users u ON p.user_id = u.id
        LEFT JOIN
            companies c ON p.company_id = c.id
        -- Join for Membership type payments
        LEFT JOIN
            membership_subscriptions ms ON p.reference_id = ms.id AND p.payment_type = 'membership'
        LEFT JOIN
            membership_types mt ON ms.membership_type_id = mt.id
        -- Join for Event Ticket type payments
        LEFT JOIN
            event_tickets et ON p.reference_id = et.id AND p.payment_type = 'event_ticket'
        LEFT JOIN
            events e ON et.event_id = e.id
        -- Join for Marketplace Order type payments
        LEFT JOIN
            marketplace_orders mo ON p.reference_id = mo.id AND p.payment_type = 'marketplace_order'
        -- Join for Invoice type payments
        LEFT JOIN
            invoices inv ON p.reference_id = inv.id AND p.payment_type = 'invoice'
        WHERE
            r.id = :receipt_id;
    ";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':receipt_id', $receipt_id, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. VERIFY RECORD EXISTS
    if (!$data) {
        http_response_code(404); // Not Found
        die("Error: Receipt with the specified ID could not be found.");
    }

    // 6. PROCESS DATA FOR PDF
    // Determine the main description and details for the item that was paid for.
    $item_description = 'Payment for ';
    $item_details = 'N/A';

    switch ($data['payment_type']) {
        case 'membership':
            $item_description .= 'Membership';
            $item_details = $data['membership_type_name'] ?? 'General Membership';
            break;
        case 'event_ticket':
            $item_description .= 'Event Ticket';
            $item_details = $data['event_title'] ?? 'General Event';
            break;
        case 'marketplace_order':
            $item_description .= 'Marketplace Order';
            $item_details = 'Order #' . $data['order_id'];
            break;
        case 'invoice':
            $item_description .= 'Invoice';
            $item_details = 'Invoice #' . $data['invoice_id'];
            break;
        default:
            $item_description = 'General Payment';
    }

    // Prepare a clean array to pass to the PDF generator.
    // This adapts the dynamic data to fit the structure your PdfGenerator expects.
    $receipt_data_for_pdf = [
        'receipt_num'     => $data['receipt_number'],
        'user_name'       => $data['payer_name'],
        'amount_paid'     => $data['amount'],
        'membership_type' => "{$item_description}: {$item_details}" // Combines description and detail
    ];

    // 7. PDF GENERATION
    $pdfGenerator = new PdfGenerator();
    $pdf_content = $pdfGenerator->generateMembershipReceiptPdf($receipt_data_for_pdf);

    // 8. SEND PDF TO BROWSER
    // Set headers to force the browser to download the file instead of displaying it.
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Receipt-' . htmlspecialchars($data['receipt_number']) . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output the PDF content
    echo $pdf_content;

} catch (PDOException $e) {
    // Handle potential database errors gracefully
    http_response_code(500); // Internal Server Error
    // Log the detailed error for the administrator
    error_log("Receipt Generation DB Error: " . $e->getMessage());
    // Show a generic message to the user
    die("A server error occurred while generating the receipt. Please try again later.");
} catch (Exception $e) {
    // Handle other potential errors (e.g., PDF library issues)
    http_response_code(500);
    error_log("Receipt Generation General Error: " . $e->getMessage());
    die("An unexpected error occurred. Please contact support.");
}