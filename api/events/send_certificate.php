<?php
// File: /api/events/send_certificate.php

header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/database.php';
require_once dirname(__DIR__) . '/core/mailer.php';
require_once dirname(__DIR__) . '/core/PdfGenerator.php'; // Ensure this class exists and is correct

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$event_id = $input['event_id'] ?? null;
$user_email = $input['user_email'] ?? null;
$user_name = $input['user_name'] ?? null;
$image_data = $input['image_data'] ?? null; // Base64 string of the certificate image

if (!$event_id || !$user_email || !$user_name || !$image_data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

try {
    $db = (new Database())->connect();
    
    // 1. Get Event Details (for email subject/body)
    $stmt = $db->prepare("SELECT title FROM events WHERE id = :id");
    $stmt->execute([':id' => $event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    $event_title = $event['title'] ?? 'Event';

    // 2. Prepare HTML for PDF (wrapping the image)
    // The image data comes as "data:image/png;base64,....."
    $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                @page { margin: 0; }
                body { margin: 0; padding: 0; }
                img { width: 100%; height: 100%; object-fit: contain; }
            </style>
        </head>
        <body>
            <img src='{$image_data}'>
        </body>
        </html>
    ";

    // 3. Generate PDF
    // We can instantiate Dompdf directly or use PdfGenerator if it exposes a raw method.
    // The existing PdfGenerator has private generatePdf. We should probably adjust it or just use Dompdf here directly 
    // to avoid modifying the core class too much if not needed, OR use Reflection/modify the class.
    // Looking at the codebase, PdfGenerator is in api/core/PdfGenerator.php.
    // I will use Dompdf directly here to be safe and independent, or instantiate PdfGenerator if I can make a public helper.
    // Actually, I can just use Dompdf directly as it is autoloaded.
    
    // NOTE: In the previous view_file, PdfGenerator uses `use Dompdf\Dompdf;`. So I can do the same.
    // However, I need to make sure autoload is working. Mailer requires ../vendor/autoload.php, so I should too.
    require_once dirname(__DIR__) . '/../vendor/autoload.php';
    
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    
    // Assuming Landscape for certificates usually, but maybe we should let the user decide?
    // For now, let's auto-detect from image dimensions if possible, or default to landscape.
    // Since the canvas in UI will likely be landscape, let's default to Landscape.
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $pdf_content = $dompdf->output();

    // 4. Send Email
    $mailer = new Mailer();
    $subject = "Your Certificate for " . $event_title;
    $body = "
        <p>Dear " . htmlspecialchars($user_name) . ",</p>
        <p>Thank you for attending <strong>" . htmlspecialchars($event_title) . "</strong>.</p>
        <p>Please find your certificate attached to this email.</p>
        <p>Best Regards,<br>Event Team</p>
    ";

    $attachments = [
        [
            'content' => $pdf_content,
            'filename' => 'Certificate.pdf'
        ]
    ];

    // Send to User
    $sent = $mailer->send($user_email, $user_name, $subject, $body, $attachments);

    if ($sent) {
        // 5. Send Copy to System Email (BCC logic or separate email)
        // The user request said: "send a copy to the system's email"
        // I'll fetch the system email from settings or use a default.
        // For now, I'll try to get it from the mailer settings if exposed, or query DB.
        
        $stmt_settings = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'system_email'");
        $stmt_settings->execute();
        $system_email = $stmt_settings->fetchColumn();

        if ($system_email) {
             // Send a copy. Maybe just a notification or the same email?
             // "send a copy to the system's email" usually means the same email.
             $mailer->send($system_email, 'System Admin', "Copy: " . $subject, "Copy of certificate sent to $user_email.<br><br>" . $body, $attachments);
        }

        echo json_encode(['success' => true, 'message' => 'Certificate sent successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
