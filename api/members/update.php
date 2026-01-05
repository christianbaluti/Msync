<?php
// /api/members/update.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents('php://input'));

if (empty($data->subscription_id) || empty($data->user_id)) { /* error */ }

$db = (new Database())->connect();
$db->beginTransaction();

try {
    // 1. Update User Details
    $user_q = "UPDATE users SET full_name = :name, email = :email, phone = :phone WHERE id = :id";
    $stmt_user = $db->prepare($user_q);
    $stmt_user->execute([
        'name' => Sanitize::string($data->full_name),
        'email' => filter_var($data->email, FILTER_VALIDATE_EMAIL),
        'phone' => Sanitize::string($data->phone),
        'id' => $data->user_id
    ]);

    // 2. Get new membership type details to recalculate end date
    $stmt_type = $db->prepare("SELECT renewal_month FROM membership_types WHERE id = :id");
    $stmt_type->execute(['id' => $data->membership_type_id]);
    $type = $stmt_type->fetch();
    if (!$type) throw new Exception("Invalid membership type.");

    // 3. Update Subscription Details
    $start_date = new DateTime($data->start_date);
    $end_date = clone $start_date;
    $end_date->modify('+' . $type['renewal_month'] . ' months');

    $sub_q = "UPDATE membership_subscriptions SET membership_type_id = :type_id, start_date = :start, end_date = :end WHERE id = :id";
    $stmt_sub = $db->prepare($sub_q);
    $stmt_sub->execute([
        'type_id' => $data->membership_type_id,
        'start' => $start_date->format('Y-m-d'),
        'end' => $end_date->format('Y-m-d'),
        'id' => $data->subscription_id
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Member updated successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}