<?php
// /views/user_imports.php

// Adjust paths based on the location of this file (assuming it's in /views/)
require_once dirname(__DIR__) . '/api/core/database.php'; // Go up one level from 'views'
require_once dirname(__DIR__) . '/api/core/mailer.php';   // Go up one level from 'views'

// --- ERROR LOGGING SETUP ---
// Define a log file path (make sure the web server has write permissions to this directory/file)
// Go up one level from 'views' to place logs outside the web root if possible, or adjust as needed.
$log_file_path = dirname(__DIR__) . '/public/errors.log';

// Ensure the logs directory exists
$log_dir = dirname($log_file_path);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0775, true); // Create directory recursively if it doesn't exist
}

/**
 * Logs an error message to the specified log file and stores it for console output.
 *
 * @param string $message The error message.
 * @param array &$console_errors Reference to the array holding errors for console output.
 */
function log_error($message, &$console_errors = []) {
    global $log_file_path;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [UserImport] {$message}" . PHP_EOL;

    // Log to file
    error_log($log_entry, 3, $log_file_path);

    // Add to array for potential console output later
    $console_errors[] = "[{$timestamp}] {$message}";
}
// --- END ERROR LOGGING SETUP ---


// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- GLOBAL VARIABLES ---
$db = null;
$feedback_errors = []; // Store errors for display within the current request/redirect (HTML feedback)
$console_log_messages = []; // Store errors specifically for console logging on page load

// --- DATABASE CONNECTION & INITIAL DATA FETCH ---
try {
    $database = new Database();
    $db = $database->connect();

    // Fetch companies for the dropdown
    $stmt = $db->query("SELECT id, name FROM companies ORDER BY name ASC");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Database Connection Error: " . $e->getMessage();
    $_SESSION['flash_error'] = $error_msg; // For user feedback
    log_error($error_msg); // Log to file
    // Store for console on page load
    $_SESSION['console_log_messages'] = $_SESSION['console_log_messages'] ?? [];
    $_SESSION['console_log_messages'][] = $error_msg;
} catch (Exception $e) {
    $error_msg = "Initialization Error: " . $e->getMessage();
    $_SESSION['flash_error'] = $error_msg;
    log_error($error_msg);
    $_SESSION['console_log_messages'] = $_SESSION['console_log_messages'] ?? [];
    $_SESSION['console_log_messages'][] = $error_msg;
}

// --- FORM PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $db) {

    $current_feedback_errors = []; // Errors specific to this POST request for HTML display
    $current_console_errors = []; // Errors specific to this POST request for logging

    // --- INPUT VALIDATION ---
    $company_id = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT);
    $password = $_POST['password'] ?? '';
    $bcc_email = filter_input(INPUT_POST, 'bcc_email', FILTER_VALIDATE_EMAIL);
    $file_info = $_FILES['csv_file'] ?? null;

    if (!$company_id) {
        $msg = "Please select a company.";
        $current_feedback_errors[] = $msg;
        log_error("Validation Failed: {$msg}", $current_console_errors);
    }
    if (empty($password)) {
        $msg = "Please enter a password for the users.";
        $current_feedback_errors[] = $msg;
        log_error("Validation Failed: {$msg}", $current_console_errors);
    }
    if (!$bcc_email) {
        $msg = "Please enter a valid email address to receive copies.";
        $current_feedback_errors[] = $msg;
        log_error("Validation Failed: {$msg}", $current_console_errors);
    }
    if (!$file_info || $file_info['error'] !== UPLOAD_ERR_OK) {
        $upload_error_code = ($file_info['error'] ?? 'Unknown');
        $msg = "File upload failed. Error code: {$upload_error_code}";
        $current_feedback_errors[] = $msg;
        log_error("File Upload Error: Code {$upload_error_code}", $current_console_errors);
    } else {
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            $msg = "Invalid file type. Please upload a .csv file.";
            $current_feedback_errors[] = $msg;
            log_error("Validation Failed: {$msg}", $current_console_errors);
        }
    }

    // --- PROCESS IF VALIDATION PASSED ---
    if (empty($current_feedback_errors)) {
        $db->beginTransaction();
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if (!$password_hash) {
                 $msg = "Password hashing failed.";
                 log_error($msg, $current_console_errors); // Log before throwing
                 throw new Exception($msg);
            }

            $insert_sql = "INSERT INTO users (full_name, email, phone, password_hash, company_id, is_employed, is_active, role)
                           VALUES (:full_name, :email, :phone, :password_hash, :company_id, 1, 1, 'user')";
            $stmt = $db->prepare($insert_sql);

            $mailer = new Mailer();
            $mailer->mailer->addBCC($bcc_email);

            $bccMailer = new Mailer();

            $file_path = $file_info['tmp_name'];
            if (($handle = fopen($file_path, "r")) !== FALSE) {
                $rowCount = 0;
                $insertedCount = 0;
                $skippedCount = 0;
                $emailedCount = 0;
                $bccBody = "<h2>User Import Summary</h2>";
                $bccBody .= "<p>Company Selected: (ID: " . htmlspecialchars($company_id) . ")</p>";
                $bccBody .= "<p>Password set for imported users: <strong>" . htmlspecialchars($password) . "</strong></p>";
                $bccBody .= "<p>Users processed:</p><ul>";

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $rowCount++;

                    $full_name = isset($data[0]) ? trim($data[0]) : '';
                    $email     = isset($data[1]) && trim($data[1]) !== '' ? trim($data[1]) : null;
                    $phone     = isset($data[2]) && trim($data[2]) !== '' ? trim($data[2]) : null;

                    if (empty($full_name)) {
                        $msg = "Row {$rowCount}: Skipped - Full name is missing.";
                        $current_feedback_errors[] = $msg;
                        log_error($msg, $current_console_errors);
                        $skippedCount++;
                        $bccBody .= "<li>{$msg}</li>";
                        continue;
                    }

                    try {
                        $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
                        $stmt->bindParam(':email', $email, $email === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindParam(':phone', $phone, $phone === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
                        $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);

                        if ($stmt->execute()) {
                            $insertedCount++;
                            $logMsg = "Row {$rowCount}: User '" . htmlspecialchars($full_name) . "' added successfully. " . ($email ? "(Email: ".htmlspecialchars($email).")" : "(No Email)");
                            $bccBody .= "<li>{$logMsg}</li>";
                            // Optionally log success to file if needed for debugging, but might be verbose
                            // log_error($logMsg, $current_console_errors);

                            if ($email) {
                                $subject = "Your New MemberSync Account";
                                $body = "<p>Hello " . htmlspecialchars($full_name) . ",</p>"
                                      // ... (rest of email body)
                                      . "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>"
                                      // ...
                                      . "<p>Thank you,<br>MemberSync Team</p>";
                                $to_name = $full_name;

                                try {
                                    $mailer->mailer->clearAddresses();
                                    if ($mailer->send($email, $to_name, $subject, $body)) {
                                        $emailedCount++;
                                    } else {
                                        $msg = "Row {$rowCount}: User '{$full_name}' added, but email notification failed. Mailer Error: " . $mailer->mailer->ErrorInfo;
                                        $current_feedback_errors[] = $msg;
                                        log_error($msg, $current_console_errors);
                                        $bccBody .= "<li style='color: red;'>Row {$rowCount}: Email FAILED for '" . htmlspecialchars($full_name) . "'. Error: " . htmlspecialchars($mailer->mailer->ErrorInfo) . "</li>";
                                    }
                                } catch (Exception $mailEx) {
                                     $msg = "Row {$rowCount}: Email Exception for '{$full_name}': " . $mailEx->getMessage();
                                     $current_feedback_errors[] = $msg;
                                     log_error($msg, $current_console_errors);
                                     $bccBody .= "<li style='color: red;'>Row {$rowCount}: Email EXCEPTION for '" . htmlspecialchars($full_name) . "': " . htmlspecialchars($mailEx->getMessage()) . "</li>";
                                }
                            }
                        } else {
                            $msg = "Row {$rowCount}: Failed to insert user '{$full_name}'. Database error (execute returned false).";
                            $current_feedback_errors[] = $msg;
                            log_error($msg, $current_console_errors);
                            $bccBody .= "<li style='color: red;'>{$msg}</li>";
                            $skippedCount++;
                        }
                    } catch (PDOException $ex) {
                         $error_code = $ex->getCode();
                         $error_message = $ex->getMessage();
                         $logMsgBase = "Row {$rowCount}: DB Exception for user '{$full_name}': {$error_message} (Code: {$error_code})";
                         log_error($logMsgBase, $current_console_errors); // Log detailed error

                        if ($error_code == '23000') {
                            $feedbackMsg = "Row {$rowCount}: Skipped user '{$full_name}'. Duplicate email or phone detected.";
                            $bccMsg = "<li style='color: orange;'>Row {$rowCount}: Skipped DUPLICATE user '" . htmlspecialchars($full_name) . "'.</li>";
                        } else {
                            $feedbackMsg = "Row {$rowCount}: Database Error for user '{$full_name}'. Please check logs."; // User-friendly message
                            $bccMsg = "<li style='color: red;'>{$logMsgBase}</li>";
                        }
                        $current_feedback_errors[] = $feedbackMsg;
                        $bccBody .= $bccMsg;
                        $skippedCount++;
                    }
                } // end while

                fclose($handle);

                // Finalize and Send Summary Email
                $bccBody .= "</ul><hr>";
                $bccBody .= "<p><strong>Total Rows Processed:</strong> {$rowCount}</p>";
                $bccBody .= "<p><strong>Successfully Inserted:</strong> {$insertedCount}</p>";
                $bccBody .= "<p><strong>Skipped/Failed:</strong> {$skippedCount}</p>";
                $bccBody .= "<p><strong>Welcome Emails Sent:</strong> {$emailedCount}</p>";

                try {
                     if (!$bccMailer->send($bcc_email, 'Import Admin', 'User Bulk Import Summary', $bccBody)) {
                          $msg = "Failed to send summary email to " . htmlspecialchars($bcc_email) . ". Mailer Error: " . $bccMailer->mailer->ErrorInfo;
                          $current_feedback_errors[] = $msg;
                          log_error($msg, $current_console_errors);
                     }
                } catch (Exception $summaryMailEx) {
                     $msg = "Failed to send summary email: " . $summaryMailEx->getMessage();
                     $current_feedback_errors[] = $msg;
                     log_error($msg, $current_console_errors);
                }

                $db->commit();

                 if ($insertedCount > 0 && $skippedCount == 0 && empty($current_feedback_errors)) {
                     $_SESSION['flash_success'] = "Successfully processed {$rowCount} rows. Added {$insertedCount} users. Sent {$emailedCount} emails.";
                 } elseif ($insertedCount > 0) {
                     $_SESSION['flash_warning'] = "Processed {$rowCount} rows. Added {$insertedCount} users, but skipped {$skippedCount}. Sent {$emailedCount} emails. Check details below or logs.";
                 } else {
                     $_SESSION['flash_error'] = "Processed {$rowCount} rows, but no new users were added. Skipped {$skippedCount}. Please check errors below, logs, and the CSV file.";
                 }

            } else {
                $msg = "Could not open the uploaded CSV file for reading.";
                $current_feedback_errors[] = $msg;
                log_error($msg, $current_console_errors);
                $db->rollBack();
            }

        } catch (Exception $e) {
             if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error_msg = "An error occurred during processing: " . $e->getMessage();
            $_SESSION['flash_error'] = $error_msg; // User feedback
            log_error($error_msg, $current_console_errors); // Log detailed error
            $current_feedback_errors[] = "Processing stopped due to error: " . $e->getMessage();
        }

        // --- Store errors and Redirect after POST ---
         if (!empty($current_feedback_errors)) {
            $_SESSION['feedback_errors'] = $current_feedback_errors; // Store detailed HTML errors
         }
         // Store console errors separately for clarity if needed, or merge them
         $_SESSION['console_log_messages'] = array_merge($_SESSION['console_log_messages'] ?? [], $current_console_errors);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } // end validation check
     else {
         // Validation failed before processing
         $_SESSION['flash_error'] = "Please correct the errors below and try again.";
         $_SESSION['feedback_errors'] = $current_feedback_errors;
         $_SESSION['console_log_messages'] = array_merge($_SESSION['console_log_messages'] ?? [], $current_console_errors);
         // No redirect here, let the form redisplay with errors below
         $feedback_errors = $current_feedback_errors;
         $console_log_messages = $_SESSION['console_log_messages']; // Make console errors available immediately
    }

} // end POST check

// --- HTML FORM DISPLAY FUNCTION ---
function display_form($companies, $feedback_errors = [], $console_log_messages = []) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk User Import</title>
    <style>
        /* ... (keep existing CSS) ... */
        .feedback.error ul, .feedback.warning ul { max-height: 200px; overflow-y: auto; } /* Scroll long error lists */
    </style>
</head>
<body>
    <div class="container">
        <h1>Bulk User Import 👥</h1>

        <?php
        // Display Flash Messages
        if (isset($_SESSION['flash_success'])) {
            echo '<div class="feedback success">✅ ' . htmlspecialchars($_SESSION['flash_success']) . '</div>';
            unset($_SESSION['flash_success']);
        }
        if (isset($_SESSION['flash_error'])) {
            echo '<div class="feedback error">❌ ' . htmlspecialchars($_SESSION['flash_error']) . '</div>';
            unset($_SESSION['flash_error']);
        }
         if (isset($_SESSION['flash_warning'])) {
            echo '<div class="feedback warning">⚠️ ' . htmlspecialchars($_SESSION['flash_warning']) . '</div>';
            unset($_SESSION['flash_warning']);
        }

        // Display Detailed Errors from Session (after redirect) or immediate validation
        $session_feedback_errors = $_SESSION['feedback_errors'] ?? [];
        $all_feedback_errors = array_unique(array_merge($feedback_errors, $session_feedback_errors)); // Combine immediate and session errors

        if (!empty($all_feedback_errors)) {
            echo '<div class="feedback error"><strong>Error Details:</strong><ul>';
            $error_limit = 20;
            $error_count = 0;
            foreach ($all_feedback_errors as $error) {
                 if ($error_count >= $error_limit) {
                     echo '<li>... (additional errors hidden)</li>';
                     break;
                 }
                echo '<li>' . htmlspecialchars($error) . '</li>';
                $error_count++;
            }
            echo '</ul></div>';
            unset($_SESSION['feedback_errors']); // Clear after displaying
        }

        // --- CONSOLE LOGGING SCRIPT ---
        // Get console messages from session (after redirect) or immediate variable
        $session_console_messages = $_SESSION['console_log_messages'] ?? [];
        $all_console_messages = array_unique(array_merge($console_log_messages, $session_console_messages));

        if (!empty($all_console_messages)) {
            echo "<script>\n";
            echo "console.group('User Import Errors');\n"; // Group console messages
            foreach ($all_console_messages as $msg) {
                // Escape message for JavaScript string literal
                echo "console.error(" . json_encode($msg) . ");\n";
            }
            echo "console.groupEnd();\n";
            echo "</script>\n";
            unset($_SESSION['console_log_messages']); // Clear after outputting
        }
        // --- END CONSOLE LOGGING SCRIPT ---
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div>
                <label for="company_id">Select Company:</label>
                <select id="company_id" name="company_id" required>
                    <option value="">-- Select Company --</option>
                    <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo htmlspecialchars($company['id']); ?>">
                                <?php echo htmlspecialchars($company['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No companies found</option>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="password">Password for All New Users:</label>
                <input type="password" id="password" name="password" required>
                 <small>This password will be assigned to all new users created by this import. They will be advised to change it.</small>
            </div>

             <div>
                <label for="bcc_email">Your Email (for Copies & Summary):</label>
                <input type="email" id="bcc_email" name="bcc_email" required placeholder="e.g., admin@example.com">
                <small>A copy of each welcome email and a final summary report will be sent here.</small>
            </div>

             <hr>

            <div>
                <label for="csv_file">Upload CSV File:</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                <small>Format: <strong>Full Name, Email (optional), Phone (optional)</strong> per line. No header row needed. Blank emails/phones will be ignored.</small>
            </div>

            <div>
                <input type="submit" value="Import Users">
            </div>
        </form>
    </div>
</body>
</html>
<?php
} // end display_form function

// --- RENDER THE FORM ---
// Retrieve any immediate console messages if POST failed without redirect
$immediate_console_messages = $console_log_messages ?? [];
display_form($companies, $feedback_errors, $immediate_console_messages);
?>