<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// ==========================================================
// --- ERROR LOGGING SETUP ---
// ==========================================================
ini_set('log_errors', 1);
ini_set('display_errors', 0); // Hide errors from users
ini_set('error_log', dirname(__DIR__, 2) . '/public/error.log');

// Catch uncaught exceptions
set_exception_handler(function ($e) {
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " (Line " . $e->getLine() . ")");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Check error.log for details.']);
    exit;
});

// Log fatal errors on shutdown
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal Error: {$error['message']} in {$error['file']} (Line {$error['line']})");
    }
});

// ==========================================================
// --- HELPER FUNCTIONS ---
// ==========================================================
function format_datetime($datetime) {
    return $datetime ? date('M j, Y, g:i A', strtotime($datetime)) : 'N/A';
}
function get_time_remaining($end_date_str) {
    try {
        $end_date = new DateTime($end_date_str);
        $now = new DateTime();
        if ($now > $end_date) return "Expired";
        $interval = $now->diff($end_date);
        $parts = [];
        if ($interval->m > 0) $parts[] = $interval->m . " month" . ($interval->m > 1 ? 's' : '');
        if ($interval->d > 0) $parts[] = $interval->d . " day" . ($interval->d > 1 ? 's' : '');
        return empty($parts) ? "Expires today" : implode(', ', $parts) . " left";
    } catch (Exception $e) {
        error_log("get_time_remaining() failed: " . $e->getMessage());
        return "Invalid date";
    }
}
function get_user_status_badge($is_active) {
    return $is_active
        ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Active</span>'
        : '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Inactive</span>';
}
function get_membership_status_badge($status) {
    $color_classes = [
        'active' => 'bg-green-100 text-green-800', 'pending' => 'bg-yellow-100 text-yellow-800',
        'expired' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-gray-100 text-gray-800',
        'suspended' => 'bg-gray-100 text-gray-800',
    ];
    $classes = $color_classes[$status] ?? 'bg-gray-100 text-gray-800';
    return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $classes . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
}
// ==========================================================

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    $html = [];

    // --- 1. Fetch All Necessary Data ---
    $user_query = "SELECT u.*, c.name AS company_name FROM users u LEFT JOIN companies c ON u.company_id = c.id WHERE u.id = :id";
    $stmt_user = $db->prepare($user_query);
    $stmt_user->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt_user->execute();
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("The user you are looking for does not exist.");
    }

    $mem_query = "SELECT ms.id, ms.start_date, ms.end_date, ms.status, ms.balance_due, mt.name as type_name, mt.id as type_id, mt.fee as type_fee 
                  FROM membership_subscriptions ms 
                  JOIN membership_types mt ON ms.membership_type_id = mt.id 
                  WHERE ms.user_id = :user_id 
                  ORDER BY ms.start_date DESC";
    $stmt_mem = $db->prepare($mem_query);
    $stmt_mem->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_mem->execute();
    $all_memberships = $stmt_mem->fetchAll(PDO::FETCH_ASSOC);

    $active_membership = null;
    $membership_history = [];
    $active_found = false;
    foreach ($all_memberships as $mem) {
        if (!$active_found && in_array($mem['status'], ['active', 'pending'])) {
            $active_membership = $mem;
            $active_found = true;
        } else {
            $membership_history[] = $mem;
        }
    }

    $trans_query = "SELECT p.id, p.amount, p.method, p.transaction_date, p.meta, r.receipt_number 
                    FROM payments p 
                    LEFT JOIN receipts r ON p.id = r.payment_id 
                    WHERE p.user_id = :user_id AND p.payment_type = 'membership' 
                    ORDER BY p.transaction_date DESC";
    $stmt_trans = $db->prepare($trans_query);
    $stmt_trans->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_trans->execute();
    $transaction_history = $stmt_trans->fetchAll(PDO::FETCH_ASSOC);

    // --- 2. Render HTML Sections ---
    ob_start();
    ?>
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold leading-6 text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></h1>
            <p class="mt-1 text-sm text-gray-500">User Profile and Details</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-x-3">
            <a href="/users" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Back to List</a>
            <?php if (has_permission('users_update')) : ?>
                <button type="button" id="editUserBtn" class="rounded-md bg-[#E40000] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Edit User</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="mt-6 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <dl class="divide-y divide-gray-200">
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Full Name</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"><?php echo htmlspecialchars($user['full_name']); ?></dd></div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Email Address</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"><?php echo htmlspecialchars($user['email']); ?></dd></div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Phone</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></dd></div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Gender</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 capitalize"><?php echo htmlspecialchars($user['gender'] ?? 'Not specified'); ?></dd></div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Employment</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"><?php echo $user['is_employed'] ? 'Employed at <strong>' . htmlspecialchars($user['company_name'] ?? 'N/A') . '</strong> as ' . htmlspecialchars($user['position'] ?? 'N/A') : 'Unemployed'; ?></dd></div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Base Role</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 capitalize"><?php echo htmlspecialchars($user['role']); ?></dd></div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Account Status</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"><?php echo get_user_status_badge($user['is_active']); ?></dd></div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Last Login</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"><?php echo format_datetime($user['last_login']); ?></dd></div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Date Created</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"><?php echo format_datetime($user['created_at']); ?></dd></div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4"><dt class="text-sm font-medium text-gray-500">Last Updated</dt><dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"><?php echo format_datetime($user['updated_at']); ?></dd></div>
        </dl>
    </div>
    <?php
    $html['userDetails'] = ob_get_clean();

    // CURRENT MEMBERSHIP
    ob_start();
    ?>
    <h2 class="text-xl font-bold leading-6 text-gray-900">Current Membership Status</h2>
    <div class="mt-4 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <?php if ($active_membership) : ?>
            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Active Plan</p>
                    <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($active_membership['type_name']); ?></h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Expires on <?php echo date('F j, Y', strtotime($active_membership['end_date'])); ?>
                        <span class="font-medium text-indigo-600">(<?php echo get_time_remaining($active_membership['end_date']); ?>)</span>
                    </p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-sm text-gray-500">Balance Due</p>
                    <p class="text-2xl font-bold <?php echo ($active_membership['balance_due'] > 0) ? 'text-red-600' : 'text-green-600'; ?>">
                        K<?php echo number_format($active_membership['balance_due'], 2); ?>
                    </p>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-200 flex flex-wrap gap-3 justify-end">
                <?php if ($active_membership['balance_due'] > 0): ?>
                <button type="button" class="membership-action-btn rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500" data-action="add_payment" data-subscription-id="<?php echo $active_membership['id']; ?>" data-type-id="<?php echo $active_membership['type_id']; ?>" data-balance-due="<?php echo $active_membership['balance_due']; ?>">Make Payment</button>
                <?php endif; ?>
                <button type="button" class="membership-action-btn rounded-md bg-[#E40000] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]" data-action="update" data-subscription-id="<?php echo $active_membership['id']; ?>" data-type-id="<?php echo $active_membership['type_id']; ?>">Upgrade / Downgrade</button>
            </div>
        <?php else : ?>
            <div class="text-center">
                <p class="text-gray-600">This user does not have an active membership.</p>
                <button type="button" class="membership-action-btn mt-4 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500" data-action="create">Subscribe to a New Membership</button>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $html['currentMembership'] = ob_get_clean();

    // MEMBERSHIP HISTORY
    ob_start();
    ?>
    <h2 class="text-xl font-bold leading-6 text-gray-900">Membership History</h2>
    <p class="mt-1 text-sm text-gray-500">A log of all previous memberships for this user.</p>
    <div class="mt-4 bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance Due</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($membership_history)) : ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No previous membership history.</td></tr>
                    <?php else : ?>
                        <?php foreach ($membership_history as $mem) : ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($mem['type_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y', strtotime($mem['start_date'])) . ' - ' . date('M j, Y', strtotime($mem['end_date'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo get_membership_status_badge($mem['status']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">K<?php echo number_format($mem['balance_due'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    $html['membershipHistory'] = ob_get_clean();

    // TRANSACTION HISTORY
    ob_start();
    ?>
    <h2 class="text-xl font-bold leading-6 text-gray-900">Transaction History</h2>
    <p class="mt-1 text-sm text-gray-500">A log of all membership-related payments.</p>
    <div class="mt-4 bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proof</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt #</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($transaction_history)) : ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No transactions found.</td></tr>
                    <?php else : ?>
                        <?php foreach ($transaction_history as $trans) : ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo format_datetime($trans['transaction_date']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">K<?php echo number_format($trans['amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars(ucfirst($trans['method'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php
                                        $meta = [];
    
                                        if (!empty($trans['meta']) && is_string($trans['meta'])) {
                                            $decoded = json_decode($trans['meta'], true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                $meta = $decoded;
                                            }
                                        }
                                    echo !empty($meta['proof'])
                                        ? '<a href="' . htmlspecialchars($meta['proof']) . '" target="_blank" class="text-indigo-600 hover:text-indigo-900 underline">View</a>'
                                        : '—';
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($trans['receipt_number'] ?? '—'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="view-receipt text-indigo-600 hover:text-indigo-900" data-id="<?php echo $trans['id']; ?>">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    $html['transactionHistory'] = ob_get_clean();

    // --- Return JSON ---
    echo json_encode(['success' => true, 'html' => $html]);

} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
