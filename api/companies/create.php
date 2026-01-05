<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

// Check for the correct permission
if (!has_permission('companies_create')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// Basic validation for company fields
if (empty($data->name) || empty($data->email) || empty($data->phone) || empty($data->password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
    exit();
}

$db = (new Database())->connect();

try {
    // Insert into the companies table
    $query = "INSERT INTO companies (name, email, phone, password_hash, address, allow_login, is_active) 
              VALUES (:name, :email, :phone, :password_hash, :address, :allow_login, :is_active)";
    
    $stmt = $db->prepare($query);

    $password_hash = password_hash($data->password, PASSWORD_DEFAULT);

    // Bind parameters from the incoming data
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':phone', $data->phone);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->bindParam(':address', $data->address);
    $stmt->bindParam(':allow_login', $data->allow_login, PDO::PARAM_INT);
    $stmt->bindParam(':is_active', $data->is_active, PDO::PARAM_INT);
    
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Company created successfully.']);

} catch (Exception $e) {
    // Check for duplicate entry error
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'A company with this email or phone already exists.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}