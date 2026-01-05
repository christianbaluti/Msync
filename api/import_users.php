<?php
// user_imports.php

// --- DEPENDENCIES ---
require_once '/core/database.php';
require_once '/core/mailer.php'; // Ensure Mailer class path is correct

session_start(); // Start session for flash messages

// --- GLOBAL VARIABLES ---
$db = null;
$feedback = ['errors' => [], 'success' => []];
$companies = [];

// --- DATABASE CONNECTION & INITIAL DATA FETCH ---
try {
    $database = new Database();
    $db = $database->connect();

    // Fetch companies for the dropdown
    $stmt = $db->query("SELECT id, name FROM companies ORDER BY name ASC");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $feedback['errors'][] = "Database Connection Error: " . $e->getMessage();
    // Stop execution if DB connection fails initially
    // In a real app, you might render an error page
} catch (Exception $e) {
    $feedback['errors'][] = "Error: " . $e->getMessage();
}


// --- FORM PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $db) { // Only process if DB is connected

    // --- INPUT VALIDATION ---
    $company_id = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT);
    $password = $_POST['password'] ?? ''; // Keep raw password for email
    $bcc_email = filter_input(INPUT_POST, 'bcc_email', FILTER_VALIDATE_EMAIL);
    $file_info = $_FILES['csv_file'] ?? null;

    if (!$company_id) {
        $feedback['errors'][] = "Please select a company.";
    }
    if (empty($password)) {
        $feedback['errors'][] = "Please enter a password for the users.";
    }
     if (!$bcc_email) {
        $feedback['errors'][] = "Please enter a valid email address to receive copies.";
    }
    if (!$file_info || $file_info['error'] !== UPLOAD_ERR_OK) {
        $feedback['errors'][] = "File upload failed. Error code: " . ($file_info['error'] ?? 'Unknown');
    } else {
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            $feedback['errors'][] = "Invalid file type. Please upload a .csv file.";
        }
    }

    // --- PROCESS IF VALIDATION PASSED ---
    if (empty($feedback['errors'])) {
        try {
            // Hash the password once
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if (!$password_hash) {
                 throw new Exception("Password hashing failed.");
            }

            // Prepare statements
            $insert_sql = "INSERT INTO users (full_name, email, phone, password_hash, company_id, is_employed, is_active, role)
                           VALUES (:full_name, :email, :phone, :password_hash, :company_id, 1, 1, 'user')";
            $stmt = $db->prepare($insert_sql);

            $mailer = new Mailer(); // Initialize Mailer

            // Process CSV
            $file_path = $file_info['tmp_name'];
            if (($handle = fopen($file_path, "r")) !== FALSE) {
                $rowCount = 0;
                $insertedCount = 0;
                $skippedCount = 0;
                $emailedCount = 0;

                $bccMailer = new Mailer(); // Second instance for BCC summary (optional, could reuse)
                $bccBody = "<h2>User Import Summary</h2>";
                $bccBody .= "<p>Password set for imported users: <strong>" . htmlspecialchars($password) . "</strong></p>";
                $bccBody .= "<p>Users processed:</p><ul>";


                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $rowCount++;

                    // --- Data Extraction and Basic Cleaning ---
                    $full_name = trim($data[0] ?? '');
                    $email = isset($data[1]) && trim($data[1]) !== '' ? trim($data[1]) : null;
                    $phone = isset($data[2]) && trim($data[2]) !== '' ? trim($data[2]) : null;

                    // Skip if name is empty
                    if (empty($full_name)) {
                        $feedback['errors'][] = "Row {$rowCount}: Skipped - Full name is missing.";
                        $skippedCount++;
                        $bccBody .= "<li>Row {$rowCount}: Skipped - Full name missing.</li>";
                        continue;
                    }

                    // --- Database Insertion ---
                    try {
                        $stmt->bindParam(':full_name', $full_name);
                        $stmt->bindParam(':email', $email, $email === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindParam(':phone', $phone, $phone === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindParam(':password_hash', $password_hash);
                        $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);

                        if ($stmt->execute()) {
                            $insertedCount++;
                            $feedback['success'][] = "Row {$rowCount}: User '{$full_name}' added successfully.";
                             $bccBody .= "<li>Row {$rowCount}: User '" . htmlspecialchars($full_name) . "' added successfully. " . ($email ? "(Email: ".htmlspecialchars($email).")" : "(No Email)") . "</li>";

                            // --- Send Email if Email Exists ---
                            if ($email) {
                                $subject = "Your New Account on MemberSync";
                                $body = "<p>Hello " . htmlspecialchars($full_name) . ",</p>"
                                      . "<p>An account has been created for you on the MemberSync platform.</p>"
                                      . "<p>You can log in using your email address and the following temporary password:</p>"
                                      . "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>" // Plain text password
                                      . "<p>Please change your password immediately after your first login.</p>"
                                      . "<p>Thank you.</p>";

                                // Send to user and BCC the admin
                                $to_name = $full_name;
                                try {
                                    // Need to re-init mailer or clear addresses before adding BCC
                                    $emailSender = new Mailer(); // Use a fresh mailer instance per email to easily add BCC
                                    $emailSender->mailer->addBCC($bcc_email); // Add BCC here
                                    if ($emailSender->send($email, $to_name, $subject, $body)) {
                                        $emailedCount++;
                                    } else {
                                        $feedback['errors'][] = "Row {$rowCount}: User '{$full_name}' added, but email notification failed.";
                                        $bccBody .= "<li style='color: red;'>Row {$rowCount}: Email FAILED for '" . htmlspecialchars($full_name) . "'.</li>";
                                    }
                                } catch (Exception $mailEx) {
                                     $feedback['errors'][] = "Row {$rowCount}: Email Error for '{$full_name}': " . $mailEx->getMessage();
                                     $bccBody .= "<li style='color: red;'>Row {$rowCount}: Email EXCEPTION for '" . htmlspecialchars($full_name) . "': " . $mailEx->getMessage() . "</li>";
                                }
                            }
                        } else {
                            $feedback['errors'][] = "Row {$rowCount}: Failed to insert user '{$full_name}'. Database error.";
                             $bccBody .= "<li style='color: red;'>Row {$rowCount}: FAILED DB INSERT for '" . htmlspecialchars($full_name) . "'.</li>";
                            $skippedCount++;
                        }
                    } catch (PDOException $ex) {
                        // Handle potential duplicate entry errors etc.
                         $error_code = $ex->getCode();
                         $error_message = $ex->getMessage();
                        if ($error_code == '23000') { // Integrity constraint violation (like unique email/phone)
                            $feedback['errors'][] = "Row {$rowCount}: Skipped user '{$full_name}'. Duplicate email or phone detected.";
                            $bccBody .= "<li style='color: orange;'>Row {$rowCount}: Skipped DUPLICATE user '" . htmlspecialchars($full_name) . "'.</li>";

                        } else {
                            $feedback['errors'][] = "Row {$rowCount}: Database Error for user '{$full_name}': {$error_message} (Code: {$error_code})";
                            $bccBody .= "<li style='color: red;'>Row {$rowCount}: DB EXCEPTION for '" . htmlspecialchars($full_name) . "': {$error_message}</li>";
                        }
                        $skippedCount++;
                    }

                } // end while loop

                fclose($handle);

                $bccBody .= "</ul>";
                $bccBody .= "<p><strong>Total Rows Processed:</strong> {$rowCount}</p>";
                $bccBody .= "<p><strong>Successfully Inserted:</strong> {$insertedCount}</p>";
                $bccBody .= "<p><strong>Skipped/Failed:</strong> {$skippedCount}</p>";
                $bccBody .= "<p><strong>Emails Sent:</strong> {$emailedCount}</p>";

                // Send the summary BCC email
                try {
                     if (!$bccMailer->send($bcc_email, 'Admin Summary', 'User Bulk Import Summary', $bccBody)) {
                          $feedback['errors'][] = "Failed to send summary email to " . htmlspecialchars($bcc_email);
                     }
                } catch (Exception $summaryMailEx) {
                     $feedback['errors'][] = "Failed to send summary email: " . $summaryMailEx->getMessage();
                }


                // Final success message
                 if ($insertedCount > 0) {
                     $_SESSION['flash_success'] = "Processed {$rowCount} rows. Added {$insertedCount} users successfully. Skipped {$skippedCount}. Sent {$emailedCount} emails.";
                 } else {
                      $_SESSION['flash_error'] = "Processed {$rowCount} rows, but no new users were added. Skipped {$skippedCount}. Please check errors below and the CSV file.";
                 }


            } else {
                $feedback['errors'][] = "Could not open the uploaded CSV file.";
            }

        } catch (Exception $e) {
            $feedback['errors'][] = "An error occurred during processing: " . $e->getMessage();
             $_SESSION['flash_error'] = "An error occurred: " . $e->getMessage();
        }

        // --- Redirect after POST to prevent re-submission ---
         if (empty($feedback['errors'])) { // Only store detailed errors if general success message isn't set
            $_SESSION['feedback_errors'] = $feedback['errors']; // Store detailed errors
         } else if(!isset($_SESSION['flash_error'])) {
             // If there were specific row errors but some succeeded, store them
            $_SESSION['flash_warning'] = "Processing completed with some errors. See details below.";
             $_SESSION['feedback_errors'] = $feedback['errors'];
         } else {
             // General error already set
             $_SESSION['feedback_errors'] = $feedback['errors']; // Still store details
         }

        header("Location: " . $_SERVER['PHP_SELF']); // Redirect to the same page
        exit();

    } // end validation check
     else {
         // Validation failed, store errors in session for display after redirect might be better
         // For simplicity here, we'll just let the $feedback array be used directly by the display function
         $_SESSION['flash_error'] = "Please correct the errors below and try again.";
         $_SESSION['feedback_errors'] = $feedback['errors'];
         // No redirect here, just let the form redisplay with errors below
    }

} // end POST check


// --- HTML FORM DISPLAY FUNCTION ---
function display_form($companies, $feedback_errors = []) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk User Import</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        input[type="password"], input[type="file"], input[type="email"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Needed for 100% width */
        }
        input[type="submit"] {
            background-color: #5cb85c;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        input[type="submit"]:hover { background-color: #4cae4c; }
        .feedback { margin-top: 20px; padding: 15px; border-radius: 4px; }
        .feedback.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .feedback.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .feedback.warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .feedback ul { padding-left: 20px; margin-top: 5px; }
        .feedback li { margin-bottom: 5px; }
         small { color: #777; display: block; margin-top: -10px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bulk User Import</h1>

        <?php
        // --- Display Flash Messages ---
        if (isset($_SESSION['flash_success'])) {
            echo '<div class="feedback success">' . htmlspecialchars($_SESSION['flash_success']) . '</div>';
            unset($_SESSION['flash_success']);
        }
        if (isset($_SESSION['flash_error'])) {
            echo '<div class="feedback error">' . htmlspecialchars($_SESSION['flash_error']) . '</div>';
            unset($_SESSION['flash_error']);
        }
         if (isset($_SESSION['flash_warning'])) {
            echo '<div class="feedback warning">' . htmlspecialchars($_SESSION['flash_warning']) . '</div>';
            unset($_SESSION['flash_warning']);
        }

        // --- Display Detailed Errors from Session ---
        $detailed_errors = $feedback_errors; // Use errors from current request first
        if(isset($_SESSION['feedback_errors']) && !empty($_SESSION['feedback_errors'])) {
             $detailed_errors = array_merge($detailed_errors, $_SESSION['feedback_errors']); // Merge if needed
             unset($_SESSION['feedback_errors']); // Clear after displaying
        }

        if (!empty($detailed_errors)) {
            echo '<div class="feedback error"><strong>Details:</strong><ul>';
            foreach (array_unique($detailed_errors) as $error) { // Display unique errors
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div>
                <label for="company_id">Select Company:</label>
                <select id="company_id" name="company_id" required>
                    <option value="">-- Select Company --</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo htmlspecialchars($company['id']); ?>">
                            <?php echo htmlspecialchars($company['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="password">Password for All Users:</label>
                <input type="password" id="password" name="password" required>
                 <small>This password will be assigned to all new users. They should change it upon first login.</small>
            </div>

             <div>
                <label for="bcc_email">Email for Copies:</label>
                <input type="email" id="bcc_email" name="bcc_email" required>
                <small>A copy of each welcome email and a final summary will be sent here.</small>
            </div>

            <div>
                <label for="csv_file">Upload CSV File:</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                <small>CSV format: Full Name, Email (optional), Phone (optional). No header row.</small>
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
// Pass errors collected *before* potential redirect
display_form($companies, $feedback['errors']);
?>