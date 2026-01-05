<?php
    // /api/members/update_status.php
    header('Content-Type: application/json');
    
    require_once dirname(__DIR__) . '/core/initialize.php';
    
    // Get raw posted data
    $data = json_decode(file_get_contents("php://input"), true);
    
    $db = (new Database())->connect();
    
    try {
        // --- 1. VALIDATION ---
        $subscription_id = filter_var($data['subscription_id'] ?? null, FILTER_VALIDATE_INT);
        $new_status = $data['new_status'] ?? null;
    
        if (!$subscription_id) {
            throw new Exception("Subscription ID is required.");
        }
    
        $allowed_statuses = ['active', 'pending', 'expired', 'suspended'];
        if (!$new_status || !in_array($new_status, $allowed_statuses)) {
            throw new Exception("Invalid or missing status. Must be one of: " . implode(', ', $allowed_statuses));
        }
    
        // --- 2. CHECK IF SUBSCRIPTION EXISTS ---
        $stmt_check = $db->prepare("SELECT id FROM membership_subscriptions WHERE id = ?");
        $stmt_check->execute([$subscription_id]);
        if ($stmt_check->rowCount() === 0) {
            throw new Exception("Subscription not found.");
        }
    
        // --- 3. UPDATE STATUS ---
        $stmt_update = $db->prepare("UPDATE membership_subscriptions SET status = ? WHERE id = ?");
        
        if ($stmt_update->execute([$new_status, $subscription_id])) {
            // Optionally, log this action
            // (new AuditLog($db))->log('system', null, 'membership_status_update', 'membership_subscription', $subscription_id, ['new_status' => $new_status]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Membership status updated to ' . $new_status . '.'
            ]);
        } else {
            throw new Exception("Failed to update status in database.");
        }
    
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("DB Error update_status.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Ref: PDB-US']);
    
    } catch (Exception $e) {
        http_response_code(400);
        error_log("App Error update_status.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
?>