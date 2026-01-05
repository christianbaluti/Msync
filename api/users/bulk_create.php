<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php'; // Adjust path as needed
require_once dirname(__DIR__) . '/core/auth.php';
require_once dirname(__DIR__) . '/core/mailer.php';    // Include Mailer

// Check if user has permission to create users
if (!has_permission('users_create')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: You do not have permission to create users.', 'success_count' => 0, 'errors' => []]);
    exit();
}

// --- CONFIGURATION ---
$required_headers = ['Name', 'Email']; // Required CSV headers
$phone_header = 'Phone'; // Optional phone header
$max_file_size = 5 * 1024 * 1024; // 5MB limit for CSV

// --- INITIALIZE VARIABLES ---
$success_count = 0;
$errors = [];
$line_number = 1; // Start line count (1 for header)

$database = new Database();
$db = $database->connect();
$mailer = new Mailer();

// --- INPUT VALIDATION ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.', 'success_count' => 0, 'errors' => []]);
    exit();
}

// Check file upload
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'CSV file upload failed or missing. Error code: ' . ($_FILES['csv_file']['error'] ?? 'N/A'), 'success_count' => 0, 'errors' => []]);
    exit();
}
if ($_FILES['csv_file']['size'] > $max_file_size) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds the limit of 5MB.', 'success_count' => 0, 'errors' => []]);
    exit();
}
$file_path = $_FILES['csv_file']['tmp_name'];
$file_mime = mime_content_type($file_path);
if ($file_mime !== 'text/plain' && $file_mime !== 'text/csv' && $file_mime !== 'application/csv') {
     echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a CSV file. Detected type: ' . $file_mime, 'success_count' => 0, 'errors' => []]);
     exit();
}


// Check required form fields
$password = $_POST['password'] ?? null;
$employment_status = $_POST['employment_status'] ?? 'unemployed';
$company_id = ($employment_status === 'employed' && isset($_POST['company_id'])) ? filter_var($_POST['company_id'], FILTER_VALIDATE_INT) : null;
$copy_email = filter_var($_POST['copy_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null;

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required.', 'success_count' => 0, 'errors' => []]);
    exit();
}
if (strlen($password) < 8) {
     echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.', 'success_count' => 0, 'errors' => []]);
     exit();
}
if ($employment_status === 'employed' && empty($company_id)) {
    echo json_encode(['success' => false, 'message' => 'Company selection is required for employed users.', 'success_count' => 0, 'errors' => []]);
    exit();
}

$is_employed_flag = ($employment_status === 'employed') ? 1 : 0;
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// --- PROCESS CSV ---
$handle = fopen($file_path, "r");
if ($handle === FALSE) {
    echo json_encode(['success' => false, 'message' => 'Could not open the CSV file.', 'success_count' => 0, 'errors' => []]);
    exit();
}

$header_row = fgetcsv($handle);
if ($header_row === FALSE) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'Could not read header row from CSV.', 'success_count' => 0, 'errors' => []]);
    exit();
}

// Trim headers
$header_row = array_map('trim', $header_row);

// Find column indices
$name_col = array_search('Name', $header_row);
$email_col = array_search('Email', $header_row);
$phone_col = array_search('Phone', $header_row); // Optional

if ($name_col === false || $email_col === false) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'CSV file must contain "Name" and "Email" columns in the header.', 'success_count' => 0, 'errors' => []]);
    exit();
}

// Prepare statements outside the loop
$check_stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR (phone IS NOT NULL AND phone <> '' AND phone = :phone)");
$insert_stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password_hash, is_employed, company_id, position, role, is_active) 
                            VALUES (:full_name, :email, :phone, :password_hash, :is_employed, :company_id, :position, :role, :is_active)");

// Set common parameters that don't change per row
$role = 'user'; // Default role for bulk created users
$is_active = 1; // Default status
$position = null; // Position is not in CSV, default to null

// Begin transaction (optional, depends if you want all-or-nothing)
// $db->beginTransaction();

try {
    while (($data = fgetcsv($handle)) !== FALSE) {
        $line_number++;
        $row_data_str = implode(', ', array_map(function($d) { return '"'.htmlspecialchars($d).'"'; }, $data)); // For error reporting

        // Basic data extraction and validation
        $full_name = trim($data[$name_col] ?? '');
        $email = trim($data[$email_col] ?? '');
        $phone = ($phone_col !== false && isset($data[$phone_col])) ? trim($data[$phone_col]) : null;
        $phone = empty($phone) ? null : $phone; // Ensure empty phone is stored as NULL

        if (empty($full_name)) {
            $errors[] = ['line' => $line_number, 'message' => 'Name is missing.', 'data' => $row_data_str];
            continue;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['line' => $line_number, 'message' => 'Invalid or missing email.', 'data' => $row_data_str];
            continue;
        }
       // Optional: Add phone validation if needed

        // Check for duplicates
        $check_stmt->bindParam(':email', $email);
        $check_stmt->bindParam(':phone', $phone);
        $check_stmt->execute();
        if ($check_stmt->fetch()) {
            $errors[] = ['line' => $line_number, 'message' => 'Email or phone already exists.', 'data' => $row_data_str];
            continue;
        }

        // Insert user
        $insert_stmt->bindParam(':full_name', $full_name);
        $insert_stmt->bindParam(':email', $email);
        $insert_stmt->bindParam(':phone', $phone);
        $insert_stmt->bindParam(':password_hash', $password_hash); // Use pre-hashed common password
        $insert_stmt->bindParam(':is_employed', $is_employed_flag, PDO::PARAM_INT);
        $insert_stmt->bindParam(':company_id', $company_id, $company_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $insert_stmt->bindParam(':position', $position); // Position is null for bulk add via CSV
        $insert_stmt->bindParam(':role', $role);
        $insert_stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);

        if ($insert_stmt->execute()) {
            // Send welcome email
            try {
                $subject = "Your New Account Has Been Created";
                $user_name_html = htmlspecialchars($full_name);
                $body = "Hello {$user_name_html},<br><br>" .
                        "An account has been created for you.<br><br>" .
                        "Login using:<br>" .
                        "<b>Email:</b> {$email}<br>" .
                        "<b>Password:</b> " . htmlspecialchars($password) . "<br><br>" . // Use plain text password
                        "Please change your password after logging in.<br><br>" .
                        "Thank you.";
                
                $bcc_recipients = $copy_email ? [$copy_email] : []; // Add BCC if provided

                $mailer->send($email, $full_name, $subject, $body, [], $bcc_recipients);
                $success_count++;

            } catch (Exception $mail_e) {
                // Log email error, but user creation might still be considered successful
                error_log("Email sending failed for {$email} (Line {$line_number}): " . $mail_e->getMessage());
                $errors[] = ['line' => $line_number, 'message' => 'User created, but welcome email failed: ' . $mail_e->getMessage(), 'data' => $row_data_str];
                // Decide if you count this as success or not. Here, we incremented success already.
                // If email failure means user creation failed, you'd need to roll back this specific user
                // or handle it differently depending on transaction strategy.
            }
        } else {
            $errors[] = ['line' => $line_number, 'message' => 'Database error during insertion.', 'data' => $row_data_str];
            error_log("PDO Error inserting user (Line {$line_number}): " . implode(", ", $insert_stmt->errorInfo()));
        }
    }

    fclose($handle);
    // if ($db->inTransaction()) $db->commit(); // Commit if using transaction

    // --- FINAL RESPONSE ---
    $final_message = "Bulk process completed. {$success_count} user(s) created.";
    if (!empty($errors)) {
        $final_message .= " " . count($errors) . " error(s) occurred.";
    }

    echo json_encode([
        'success' => $success_count > 0 || empty($errors), // Consider overall success
        'message' => $final_message,
        'success_count' => $success_count,
        'errors' => $errors
    ]);

} catch (PDOException $e) {
    // if ($db->inTransaction()) $db->rollBack();
    if ($handle) fclose($handle);
    error_log("PDO Error in bulk create: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred during processing.', 'success_count' => $success_count, 'errors' => $errors]);
} catch (Exception $e) {
    // if ($db->inTransaction()) $db->rollBack();
    if ($handle) fclose($handle);
    error_log("General Error in bulk create: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage(), 'success_count' => $success_count, 'errors' => $errors]);
}

?>