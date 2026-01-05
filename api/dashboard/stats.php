<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

function get_growth_percentage($current_count, $previous_count) {
    if ($previous_count == 0) {
        return $current_count > 0 ? 100.0 : 0.0;
    }
    return (($current_count - $previous_count) / $previous_count) * 100;
}

// Time intervals
$current_month_start = date('Y-m-01 00:00:00');
$current_month_end = date('Y-m-t 23:59:59');
$previous_month_start = date('Y-m-01 00:00:00', strtotime('first day of last month'));
$previous_month_end = date('Y-m-t 23:59:59', strtotime('last day of last month'));

// 1. Users Growth
$stmt_users_current = $db->prepare("SELECT COUNT(id) FROM users WHERE created_at BETWEEN ? AND ?");
$stmt_users_current->execute([$current_month_start, $current_month_end]);
$users_current_count = $stmt_users_current->fetchColumn();

$stmt_users_previous = $db->prepare("SELECT COUNT(id) FROM users WHERE created_at BETWEEN ? AND ?");
$stmt_users_previous->execute([$previous_month_start, $previous_month_end]);
$users_previous_count = $stmt_users_previous->fetchColumn();

// 2. Members Growth (Active Subscriptions)
$stmt_members_current = $db->prepare("SELECT COUNT(id) FROM membership_subscriptions WHERE start_date BETWEEN ? AND ? AND status = 'active'");
$stmt_members_current->execute([$current_month_start, $current_month_end]);
$members_current_count = $stmt_members_current->fetchColumn();

$stmt_members_previous = $db->prepare("SELECT COUNT(id) FROM membership_subscriptions WHERE start_date BETWEEN ? AND ? AND status = 'active'");
$stmt_members_previous->execute([$previous_month_start, $previous_month_end]);
$members_previous_count = $stmt_members_previous->fetchColumn();

// 3. Companies Growth
$stmt_companies_current = $db->prepare("SELECT COUNT(id) FROM companies WHERE created_at BETWEEN ? AND ?");
$stmt_companies_current->execute([$current_month_start, $current_month_end]);
$companies_current_count = $stmt_companies_current->fetchColumn();

$stmt_companies_previous = $db->prepare("SELECT COUNT(id) FROM companies WHERE created_at BETWEEN ? AND ?");
$stmt_companies_previous->execute([$previous_month_start, $previous_month_end]);
$companies_previous_count = $stmt_companies_previous->fetchColumn();

// 4. Published Events Growth
$stmt_events_current = $db->prepare("SELECT COUNT(id) FROM events WHERE created_at BETWEEN ? AND ? AND status = 'published'");
$stmt_events_current->execute([$current_month_start, $current_month_end]);
$events_current_count = $stmt_events_current->fetchColumn();

$stmt_events_previous = $db->prepare("SELECT COUNT(id) FROM events WHERE created_at BETWEEN ? AND ? AND status = 'published'");
$stmt_events_previous->execute([$previous_month_start, $previous_month_end]);
$events_previous_count = $stmt_events_previous->fetchColumn();


$stats = [
    'users' => [
        'total' => $db->query("SELECT COUNT(id) FROM users")->fetchColumn(),
        'growth' => get_growth_percentage($users_current_count, $users_previous_count)
    ],
    'members' => [
        'total' => $db->query("SELECT COUNT(id) FROM membership_subscriptions WHERE status = 'active'")->fetchColumn(),
        'growth' => get_growth_percentage($members_current_count, $members_previous_count)
    ],
    'companies' => [
        'total' => $db->query("SELECT COUNT(id) FROM companies")->fetchColumn(),
        'growth' => get_growth_percentage($companies_current_count, $companies_previous_count)
    ],
    'events' => [
        'total' => $db->query("SELECT COUNT(id) FROM events WHERE status = 'published'")->fetchColumn(),
        'growth' => get_growth_percentage($events_current_count, $events_previous_count)
    ]
];

echo json_encode(['success' => true, 'data' => $stats]);