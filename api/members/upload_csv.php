<?php
// /api/members/upload_csv.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* error */ }

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error.']);
    exit;
}
if (empty($_POST['membership_type_id'])) {
    echo json_encode(['success' => false, 'message' => 'Membership Type is required.']);
    exit;
}

$db = (new Database())->connect();
$db->beginTransaction();

$membership_type_id = (int)$_POST['membership_type_id'];
$company_id = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

$success_count = 0;
$failure_count = 0;
$errors = [];

try {
    // Get membership type details
    $stmt_type = $db->prepare("SELECT fee, renewal_month FROM membership_types WHERE id = ?");
    $stmt_type->execute([$membership_type_id]);
    $type = $stmt_type->fetch();
    if (!$type) throw new Exception("Invalid membership type selected.");

    $file_path = $_FILES['csv_file']['tmp_name'];
    $file = fopen($file_path, 'r');
    
    // Skip header row
    fgetcsv($file);

    $row_num = 1;
    while (($row = fgetcsv($file)) !== FALSE) {
        $row_num++;
        $full_name = trim($row[0]);
        $email = trim($row[1]);
        $phone = trim($row[2]);

        if (empty($full_name) || empty($email) || empty($phone)) {
            $errors[] = "Row $row_num: Missing required data.";
            $failure_count++;
            continue;
        }

        // Check if user exists
        $stmt_check = $db->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $stmt_check->execute([$email, $phone]);
        if ($stmt_check->fetch()) {
            $errors[] = "Row $row_num: User with email '$email' or phone '$phone' already exists.";
            $failure_count++;
            continue;
        }

        // Create User
        $user_query = "INSERT INTO users (full_name, email, phone, password_hash, company_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_user = $db->prepare($user_query);
        $stmt_user->execute([$full_name, $email, $phone, password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT), $company_id]);
        $user_id = $db->lastInsertId();

        // Create Subscription
        $start_date = new DateTime();
        $end_date = (clone $start_date)->modify('+' . $type['renewal_month'] . ' months');
        $card_number = 'MS-' . $user_id . '-' . time();
        
        $sub_query = "INSERT INTO membership_subscriptions (user_id, membership_type_id, start_date, end_date, membership_card_number, status, balance_due) VALUES (?, ?, ?, ?, ?, 'pending', ?)";
        $stmt_sub = $db->prepare($sub_query);
        $stmt_sub->execute([$user_id, $membership_type_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'), $card_number, $type['fee']]);
        
        $success_count++;
    }

    fclose($file);
    $db->commit();

    $message = "CSV processed. $success_count members created successfully.";
    if ($failure_count > 0) {
        $message .= " $failure_count records failed.";
    }

    echo json_encode(['success' => true, 'message' => $message, 'errors' => $errors]);

} catch (Exception $e) {
    $db->rollBack();
    // error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A critical error occurred: ' . $e->getMessage(), 'errors' => $errors]);
} finally {
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }
}