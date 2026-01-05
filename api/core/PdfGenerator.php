<?php
// /api/core/PdfGenerator.php

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGenerator {

    // FIX: This private method is now rewritten to use Dompdf
    private function generatePdf(string $html): string {
        // Instantiate and use the dompdf class
        $options = new Options();
        $options->set('isRemoteEnabled', true); // Allows loading images from remote URLs
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Get the generated PDF output as a string, which is what the Mailer expects
        return $dompdf->output();
    }

    // Add this new method to your existing /core/PdfGenerator.php file
    public function generateMembershipReceiptPdf(array $receipt_data): string {
        $title = 'Payment Receipt: ' . htmlspecialchars($receipt_data['receipt_num']);
        $content = "
            <p>Thank you for your membership payment. Here are the details:</p>
            <table>
                <tr><th>Receipt Number</th><td>" . htmlspecialchars($receipt_data['receipt_num']) . "</td></tr>
                <tr><th>Payment For</th><td>Membership: '" . htmlspecialchars($receipt_data['membership_type']) . "'</td></tr>
                <tr><th>Member Name</th><td>" . htmlspecialchars($receipt_data['user_name']) . "</td></tr>
                <tr><th>Date</th><td>" . date('Y-m-d') . "</td></tr>
                <tr><th class='total'>Amount Paid</th><td class='total'>MWK " . number_format($receipt_data['amount_paid'], 2) . "</td></tr>
            </table>
        ";
        $html = $this->getDocumentWrapper($title, $content);
        return $this->generatePdf($html);
    }

    // Add this new method inside your existing PdfGenerator class in /api/core/PdfGenerator.php

    public function generateMembershipInvoicePdf(array $invoice_data): string {
        $title = 'Membership Invoice #' . htmlspecialchars($invoice_data['invoice_id']);
        $content = "
            <p>Please find below the invoice for your membership subscription.</p>
            <table>
                <tr><th>Invoice To</th><td>" . htmlspecialchars($invoice_data['user_name']) . "</td></tr>
                <tr><th>Membership Type</th><td>" . htmlspecialchars($invoice_data['membership_type']) . "</td></tr>
                <tr><th>Date Issued</th><td>" . htmlspecialchars($invoice_data['issued_at']) . "</td></tr>
                <tr><th>Due Date</th><td>" . htmlspecialchars($invoice_data['due_date']) . "</td></tr>
                <tr><th colspan='2'>Invoice Details</th></tr>
                <tr><td>Membership Fee</td><td>MWK " . number_format($invoice_data['total_amount'], 2) . "</td></tr>
                <tr><td>Amount Paid</td><td>MWK " . number_format($invoice_data['paid_amount'], 2) . "</td></tr>
                <tr><th class='total'>Balance Due</th><td class='total'>MWK " . number_format($invoice_data['balance_due'], 2) . "</td></tr>
            </table>
        ";
        $html = $this->getDocumentWrapper($title, $content);
        return $this->generatePdf($html);
    }

    private function getDocumentWrapper(string $title, string $content): string {
        // This HTML wrapper remains the same, it's compatible with Dompdf
        return "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <title>" . htmlspecialchars($title) . "</title>
                <style>
                    body { font-family: Helvetica, sans-serif; color: #333; }
                    .container { padding: 20px; border: 1px solid #ddd; }
                    h1 { color: #0056b3; border-bottom: 2px solid #eee; padding-bottom: 10px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .footer { margin-top: 30px; text-align: center; font-size: 0.8em; color: #777; }
                    .total { font-weight: bold; font-size: 1.2em; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h1>" . htmlspecialchars($title) . "</h1>
                    " . $content . "
                    <div class='footer'>Generated on " . date('Y-m-d H:i:s') . " by MemberSync</div>
                </div>
            </body>
            </html>
        ";
    }

    // NOTE: The public methods below do not need to change at all.
    // They just prepare the HTML and pass it to the private generatePdf method.

    public function generateTicketPdf(array $ticket_data): string {
        $title = 'Event Ticket: ' . htmlspecialchars($ticket_data['event_title']);
        $content = "
            <p>This ticket confirms your registration for the event. Please present it at the entrance.</p>
            <table>
                <tr><th>Attendee</th><td>" . htmlspecialchars($ticket_data['user_name']) . "</td></tr>
                <tr><th>Event</th><td>" . htmlspecialchars($ticket_data['event_title']) . "</td></tr>
                <tr><th>Ticket Code</th><td><b>" . htmlspecialchars($ticket_data['ticket_code']) . "</b></td></tr>
                <tr><th>Price</th><td>MWK " . number_format($ticket_data['price'], 2) . "</td></tr>
                <tr><th>Status</th><td>" . ucfirst(htmlspecialchars($ticket_data['status'])) . "</td></tr>
            </table>
        ";
        $html = $this->getDocumentWrapper($title, $content);
        return $this->generatePdf($html);
    }

    public function generateReceiptPdf(array $receipt_data): string {
        $title = 'Payment Receipt: ' . htmlspecialchars($receipt_data['receipt_num']);
        $content = "
            <p>Thank you for your payment. Here are the details:</p>
            <table>
                <tr><th>Receipt Number</th><td>" . htmlspecialchars($receipt_data['receipt_num']) . "</td></tr>
                <tr><th>Payment For</th><td>Event Ticket for '" . htmlspecialchars($receipt_data['event_title']) . "'</td></tr>
                <tr><th>Paid By</th><td>" . htmlspecialchars($receipt_data['user_name']) . "</td></tr>
                <tr><th>Date</th><td>" . date('Y-m-d') . "</td></tr>
                <tr><th class='total'>Amount Paid</th><td class='total'>MWK " . number_format($receipt_data['amount_paid'], 2) . "</td></tr>
            </table>
        ";
        $html = $this->getDocumentWrapper($title, $content);
        return $this->generatePdf($html);
    }
    
    public function generateInvoicePdf(array $invoice_data): string {
        $title = 'Invoice #' . htmlspecialchars($invoice_data['invoice_id']);
        $content = "
            <p>Please find below the invoice for your event ticket.</p>
            <table>
                <tr><th>Invoice To</th><td>" . htmlspecialchars($invoice_data['user_name']) . "</td></tr>
                <tr><th>For Event</th><td>" . htmlspecialchars($invoice_data['event_title']) . "</td></tr>
                <tr><th>Date Issued</th><td>" . date('Y-m-d') . "</td></tr>
                <tr><th colspan='2'>Invoice Details</th></tr>
                <tr><td>Ticket Price</td><td>MWK " . number_format($invoice_data['total_amount'], 2) . "</td></tr>
                <tr><td>Amount Paid</td><td>MWK " . number_format($invoice_data['paid_amount'], 2) . "</td></tr>
                <tr><th class='total'>Balance Due</th><td class='total'>MWK " . number_format($invoice_data['balance_due'], 2) . "</td></tr>
            </table>
        ";
        $html = $this->getDocumentWrapper($title, $content);
        return $this->generatePdf($html);
    }
}