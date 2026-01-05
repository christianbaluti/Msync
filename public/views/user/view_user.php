<?php
require_once dirname(__DIR__, 3) . '/api/core/initialize.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/partials/sidebar.php';

// --- Configuration ---
$user_id = $_GET['id'] ?? null;
$page_error = null;

if (!$user_id) {
    $page_error = "User ID is required. You cannot proceed without a valid user.";
}

// Fetch MALIPO SDK URL and Config from DB
$malipo_sdk_url = '';
$malipo_config = null;
try {
    $db = (new Database())->connect();
    // Assuming MALIPO gateway has name 'MALIPO'
    $stmt = $db->prepare("SELECT config FROM payment_gateways WHERE name = 'MALIPO' LIMIT 1");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $malipo_config = json_decode($row['config'], true);
        if (isset($malipo_config['sdk'])) {
            $malipo_sdk_url = $malipo_config['sdk'];
        }
    } else {
        error_log("MALIPO configuration not found in payment_gateways table.");
    }
} catch (Exception $e) {
    error_log("Failed to fetch MALIPO config: " . $e->getMessage());
}

?>

<div class="md:pl-72">
    <?php require_once dirname(__DIR__) . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <?php if ($page_error) : ?>
                <div class="text-center py-16">
                    <h2 class="text-xl font-semibold text-gray-700">An Error Occurred</h2>
                    <p class="mt-2 text-gray-500"><?php echo htmlspecialchars($page_error); ?></p>
                    <a href="/users" class="mt-6 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Return to User List</a>
                </div>
            <?php else : ?>
                <div class="max-w-4xl mx-auto space-y-8">
                    <div id="userDetailsContainer"></div>
                    <div id="currentMembershipContainer"></div>
                    <div id="membershipHistoryContainer"></div>
                    <div id="transactionHistoryContainer"></div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="loading-spinner" class="hidden fixed inset-0 bg-opacity-75 flex items-center justify-center z-[100]">
    <div class="flex items-center justify-center space-x-2">
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.1s;"></div>
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.2s;"></div>
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.3s;"></div>
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.4s;"></div>
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.5s;"></div>
    </div>
</div>

<div id="toast" class="hidden fixed bottom-5 right-5 z-50 bg-gray-800 text-white py-2 px-4 rounded-lg shadow-md">
    <p id="toastMessage"></p>
</div>

<div id="editUserModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900">Edit User Details</h3>
        <form id="editUserForm" class="mt-4 space-y-5">
            <input type="hidden" id="userId" name="id" value="<?php echo $user_id; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label for="fullName" class="block text-sm font-medium text-slate-700">Full Name</label><input type="text" id="fullName" name="full_name" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
                <div><label for="gender" class="block text-sm font-medium text-slate-700">Gender</label><select id="gender" name="gender" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"><option value="">-- Select --</option><option value="male">Male</option><option value="female">Female</option></select></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label for="email" class="block text-sm font-medium text-slate-700">Email</label><input type="email" id="email" name="email" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
                <div><label for="phone" class="block text-sm font-medium text-slate-700">Phone</label><input type="tel" id="phone" name="phone" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
            </div>
            <div class="space-y-3">
                <div class="flex items-center"><input type="checkbox" id="isEmployed" name="is_employed_checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><label for="isEmployed" class="ml-3 text-sm font-medium text-slate-900">Is Employed?</label></div>
                <div id="employmentDetails" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div><label for="companyId" class="block text-sm font-medium text-slate-700">Company</label><select id="companyId" name="company_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></select></div>
                    <div><label for="position" class="block text-sm font-medium text-slate-700">Position</label><input type="text" id="position" name="position" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
                <div><label for="role" class="block text-sm font-medium text-slate-700">Base Role</label><select id="role" name="role" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"><option value="user">User</option><option value="admin">Admin</option></select></div>
                <div><label for="isActive" class="block text-sm font-medium text-slate-700">Status</label><select id="isActive" name="is_active" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            </div>
            <div id="adminRolesSection" class="hidden space-y-3 border-t pt-4"><label class="block text-sm font-medium text-slate-900">Admin Roles</label><div id="rolesCheckboxes" class="grid grid-cols-2 md:grid-cols-4 gap-2"></div></div>
            <div><label for="password" class="block text-sm font-medium text-slate-700">Password</label><input type="password" id="password" name="password" placeholder="Leave blank to keep unchanged" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:outline-none">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save User</button>
            </div>
        </form>
    </div>
</div>


<div id="manageMembershipModal" class="hidden fixed inset-0 bg-gray-600/75 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 id="membershipModalTitle" class="text-lg font-medium text-gray-900">Manage Membership</h3>
        <form id="membershipForm" class="mt-4 space-y-4">
            <input type="hidden" name="user_id" id="membershipUserId" value="<?php echo $user_id; ?>">
            <input type="hidden" name="action" id="membershipAction">
            <input type="hidden" name="subscription_id" id="subscriptionId">
            <input type="hidden" name="generated_order_id" id="generatedOrderId"> <div id="membershipTypeWrapper">
                <label for="membershipTypeSelectModal" class="block text-sm font-medium">Membership Type</label>
                <select name="membership_type_id" id="membershipTypeSelectModal" class="mt-1 w-full rounded border-gray-300 shadow-sm" required></select>
            </div>

            <div class="grid grid-cols-2 gap-4 p-3 bg-gray-50 rounded-md">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Plan Cost</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900" id="planCost">K0.00</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Balance Due</dt>
                    <dd class="mt-1 text-lg font-semibold text-red-600" id="balanceDueDisplay">K0.00</dd>
                </div>
            </div>

            <div id="startDateWrapper"><label for="startDate" class="block text-sm font-medium">Start Date</label><input type="date" id="startDate" name="start_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 w-full rounded border-gray-300 shadow-sm" required></div>
            <div><label for="amountPaid" class="block text-sm font-medium">Amount Being Paid Now</label><input type="number" id="amountPaid" step="0.01" name="amount_paid" placeholder="0.00" class="mt-1 w-full rounded border-gray-300 shadow-sm"></div>
            <div><label for="paymentMethodSelectModal" class="block text-sm font-medium">Payment Method</label><select name="payment_method" id="paymentMethodSelectModal" class="mt-1 w-full rounded border-gray-300 shadow-sm"></select></div>
            <div id="proofOfPaymentWrapper" style="display: none;"><label class="block text-sm font-medium">Proof of Payment (Optional)</label><input type="file" name="payment_proof" class="mt-1 w-full text-sm"></div>

            <div class="flex items-center gap-x-4 border-t pt-4">
                <input type="checkbox" name="issue_invoice" id="issueInvoice" class="h-4 w-4 rounded"><label for="issueInvoice" class="text-sm">Issue & Email Invoice</label>
                <input type="checkbox" name="issue_receipt" id="issueReceipt" class="h-4 w-4 rounded" checked><label for="issueReceipt" class="text-sm">Issue & Email Receipt</label>
            </div>

            <div class="pt-4 flex justify-end space-x-2">
                <button type="button" class="close-modal-btn bg-gray-200 py-2 px-4 rounded">Cancel</button>
                <button type="submit" id="membershipSubmitBtn" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-500">Submit</button>
            </div>
        </form>
    </div>
</div>
<div id="changeStatusModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900">Change Membership Status</h3>
        <form id="changeStatusForm" class="mt-4 space-y-5">
            <input type="hidden" id="statusSubscriptionId" name="subscription_id">
            <div>
                <label for="newMembershipStatus" class="block text-sm font-medium text-slate-700">New Status</label>
                <select id="newMembershipStatus" name="new_status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none" required>
                    <option value="">-- Select Status --</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="suspended">Suspended</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:outline-none">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save Status</button>
            </div>
        </form>
    </div>
</div>

<?php if ($malipo_sdk_url): ?>
    <script src="<?php echo htmlspecialchars($malipo_sdk_url); ?>"></script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const USER_ID = <?php echo json_encode($user_id); ?>;
    let MALIPO_MERCHANT_ID = null;
    let currentUserData = {};

    // --- Spinner, Toast & Modal Handlers ---
    const spinner = document.getElementById('loading-spinner');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const showSpinner = () => spinner.classList.remove('hidden');
    const hideSpinner = () => spinner.classList.add('hidden');
    const showToast = (message, isError = false) => {
        toastMessage.textContent = message;
        toast.className = `fixed bottom-5 right-5 text-white py-2 px-4 rounded-lg shadow-md z-50 ${isError ? 'bg-red-600' : 'bg-green-700'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    };
    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.closest('.fixed[id*="Modal"]').classList.add('hidden');
        });
    });
    document.querySelectorAll('.fixed[id*="Modal"]').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });
    });

    // =================================================================
    // FETCH MALIPO MERCHANT ID
    // =================================================================
    const fetchMalipoMerchantId = async () => {
        try {
            const response = await fetch('/api/payments/get_merchant.php');
            if (!response.ok) throw new Error('Failed to fetch merchant ID status: ' + response.status);
            const result = await response.json();
            if (result.success && result.merchantId) {
                MALIPO_MERCHANT_ID = result.merchantId;
                console.log('MALIPO Merchant ID fetched:', MALIPO_MERCHANT_ID);
            } else {
                throw new Error(result.message || 'Merchant ID not found.');
            }
        } catch (error) {
            console.error('Error fetching MALIPO Merchant ID:', error);
            // Don't show toast here, handle lack of ID in updateMalipoOption
        }
    };
    fetchMalipoMerchantId(); // Fetch on page load

    // =================================================================
    // DYNAMIC UI REFRESH FUNCTION
    // =================================================================
    const refreshPageData = async () => { /* Keep as is */
        showSpinner();
        try {
            const response = await fetch(`/api/users/get_view_data.php?id=${USER_ID}`); // Ensure this path is correct
            if (!response.ok) throw new Error('Network response was not ok.');

            const result = await response.json();

            if (result.success) {
                currentUserData = result.userData || {}; // Store user data
                document.getElementById('userDetailsContainer').innerHTML = result.html.userDetails;
                document.getElementById('currentMembershipContainer').innerHTML = result.html.currentMembership;
                document.getElementById('membershipHistoryContainer').innerHTML = result.html.membershipHistory;
                document.getElementById('transactionHistoryContainer').innerHTML = result.html.transactionHistory;
            } else {
                throw new Error(result.message || 'API returned an error.');
            }
        } catch (error) {
            console.error('Refresh Error:', error);
            showToast('Could not refresh page data. ' + error.message, true);
        } finally {
            hideSpinner();
        }
     };
    if (USER_ID) refreshPageData();


    // =================================================================
    // EVENT DELEGATION FOR DYNAMIC CONTENT
    // =================================================================
     document.querySelector('main').addEventListener('click', function(e) { /* Keep as is */
        const membershipBtn = e.target.closest('.membership-action-btn');
        const transactionBtn = e.target.closest('.action-btn');
        const editUserBtn = e.target.closest('#editUserBtn');
        const changeStatusBtn = e.target.closest('.change-status-btn');

        if (editUserBtn) {
            openEditUserModal();
        }
        
        if (changeStatusBtn) { // <-- ADD THIS BLOCK
            const subId = changeStatusBtn.dataset.subscriptionId;
            const currentStatus = changeStatusBtn.dataset.currentStatus;
            openChangeStatusModal(subId, currentStatus);
        }

        if (membershipBtn) {
            const action = membershipBtn.dataset.action;
            let config = {};
            switch (action) {
                case 'create':
                    config = { title: 'Subscribe to a New Membership', action: 'create', buttonText: 'Submit / Pay' }; break;
                case 'update':
                    config = { title: 'Upgrade or Downgrade Membership', action: 'update', buttonText: 'Update / Pay', subscriptionId: membershipBtn.dataset.subscriptionId, typeId: membershipBtn.dataset.typeId }; break;
                case 'add_payment':
                    config = { title: 'Make a Payment', action: 'add_payment', buttonText: 'Submit Payment / Pay', subscriptionId: membershipBtn.dataset.subscriptionId, typeId: membershipBtn.dataset.typeId, balanceDue: membershipBtn.dataset.balanceDue }; break;
            }
            if (Object.keys(config).length > 0) openManageMembershipModal(config);
        }

        if (transactionBtn) {
            const action = transactionBtn.dataset.action;
            const paymentId = transactionBtn.dataset.paymentId;
            if (action === 'email_receipt' && paymentId) {
                transactionBtn.disabled = true;
                transactionBtn.textContent = 'Sending...';
                showSpinner();
                fetch(`/api/receipts/send.php`, { // Ensure this path is correct
                    method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ payment_id: paymentId })
                })
                .then(res => res.json()).then(data => {
                    showToast(data.success ? 'Receipt sent successfully!' : (data.message || 'Failed to send receipt.'), !data.success);
                })
                .catch(() => showToast('Network error while sending receipt.', true))
                .finally(() => {
                    transactionBtn.disabled = false;
                    transactionBtn.textContent = 'Email';
                    hideSpinner();
                });
            }
        }
    });
    
    // =================================================================
    // MEMBERSHIP STATUS CHANGE LOGIC (NEW)
    // =================================================================
    const changeStatusModal = document.getElementById('changeStatusModal');
    const changeStatusForm = document.getElementById('changeStatusForm');

    const openChangeStatusModal = (subscriptionId, currentStatus) => {
        if (!subscriptionId) {
            showToast('Subscription ID not found.', true);
            return;
        }
        changeStatusForm.reset();
        changeStatusForm.querySelector('#statusSubscriptionId').value = subscriptionId;
        changeStatusForm.querySelector('#newMembershipStatus').value = currentStatus || '';
        changeStatusModal.classList.remove('hidden');
    };

    changeStatusForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        if (!data.new_status) {
            showToast('Please select a new status.', true);
            return;
        }

        showSpinner();
        fetch('/api/members/update_status.php', { // <-- NEW API ENDPOINT
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json().then(apiData => ({ ok: res.ok, status: res.status, data: apiData })))
        .then(({ ok, status, data }) => {
            if (ok && data.success) {
                showToast(data.message || 'Status updated successfully.');
                changeStatusModal.classList.add('hidden');
                refreshPageData(); // Refresh page data on success
            } else {
                throw new Error(data.message || `Failed to update status. Status: ${status}`);
            }
        }).catch(error => {
            showToast(`Update Error: ${error.message}`, true);
            console.error('Update Status Error:', error);
        })
        .finally(() => hideSpinner());
    });

    // =================================================================
    // MEMBERSHIP MANAGEMENT LOGIC (Including MALIPO)
    // =================================================================
    const manageModal = document.getElementById('manageMembershipModal');
    const membershipForm = document.getElementById('membershipForm');
    const typeSelect = document.getElementById('membershipTypeSelectModal');
    const methodSelect = document.getElementById('paymentMethodSelectModal');
    const amountPaidInput = document.getElementById('amountPaid');
    const proofWrapper = document.getElementById('proofOfPaymentWrapper');
    let membershipTypesCache = [];
    let paymentMethodsCache = [];

    // --- Helper to add/remove Malipo Option ---
    const updateMalipoOption = () => {
        // **Check for Malipo object correctly**
        const malipoAvailable = MALIPO_MERCHANT_ID && typeof Malipo !== 'undefined';
        let malipoOption = methodSelect.querySelector('option[value="gateway"]');

        // Add option if needed
        if (malipoAvailable && !malipoOption) {
            console.log("Adding MALIPO option");
            const option = document.createElement('option');
            option.value = 'gateway';
            option.textContent = 'MALIPO GATEWAY';
            methodSelect.appendChild(option);
        }
        // Remove option if needed
        else if (!malipoAvailable && malipoOption) {
            console.log("Removing MALIPO option");
             methodSelect.removeChild(malipoOption);
        } else {
             console.log("MALIPO option status unchanged. Available:", malipoAvailable, "Exists:", !!malipoOption);
        }
    };


    const updateCostDisplay = (initialBalance = 0) => { /* Keep as is */
        const selectedTypeOption = typeSelect.options[typeSelect.selectedIndex];
        const action = document.getElementById('membershipAction').value;
        let cost = 0; // The amount required for the current operation
        let planFee = 0; // The fee of the selected plan

        if (selectedTypeOption && selectedTypeOption.dataset.fee) {
            planFee = parseFloat(selectedTypeOption.dataset.fee);
        }

        if (action === 'add_payment') {
            cost = initialBalance; // For payments, the "cost" is the balance due
        } else {
            cost = planFee; // For create/update, cost is the plan fee
        }

        const paidNow = parseFloat(amountPaidInput.value) || 0;
        const balanceDue = Math.max(0, cost - paidNow); // Calculate remaining balance, cannot be negative

        manageModal.querySelector('#planCost').textContent = `K${planFee.toFixed(2)}`; // Always show plan fee
        manageModal.querySelector('#balanceDueDisplay').textContent = `K${balanceDue.toFixed(2)}`;

        // Enable/Disable Amount Paid & Show/Hide Proof based on method
        const selectedMethod = methodSelect.value;
        if (selectedMethod === 'gateway') {
            // Pre-fill amount with the cost/balance, make it read-only
            amountPaidInput.value = cost > 0 ? cost.toFixed(2) : '0.00';
            amountPaidInput.readOnly = true; // Make read-only for gateway
            manageModal.querySelector('#balanceDueDisplay').textContent = 'K0.00'; // Assume full payment via gateway
            proofWrapper.style.display = 'none'; // Hide proof for gateway
        } else {
            amountPaidInput.readOnly = false; // Editable for manual methods
             // Show/Hide Proof of Payment for manual methods
             proofWrapper.style.display = (selectedMethod && selectedMethod !== 'cash') ? 'block' : 'none';
        }
    };


    const openManageMembershipModal = async (config) => { /* Keep most, update method population */
        membershipForm.reset();

        manageModal.querySelector('#membershipTypeWrapper').style.display = (config.action !== 'add_payment') ? 'block' : 'none';
        manageModal.querySelector('#startDateWrapper').style.display = (config.action !== 'add_payment') ? 'block' : 'none';
        proofWrapper.style.display = 'none';
        amountPaidInput.readOnly = false;
        amountPaidInput.value = '';

        manageModal.querySelector('#membershipModalTitle').textContent = config.title;
        manageModal.querySelector('#membershipAction').value = config.action;
        manageModal.querySelector('#subscriptionId').value = config.subscriptionId || '';
        manageModal.querySelector('#membershipSubmitBtn').textContent = config.buttonText;
        document.getElementById('generatedOrderId').value = ''; // Clear previous order ID


        showSpinner();
        try {
            // Fetch types AND methods every time to ensure MALIPO option status is correct
            const [typesRes, methodsRes] = await Promise.all([
                 fetch('/api/membership_types/read.php').then(res => res.json()),
                 fetch('/api/payments/get_methods.php').then(res => res.json())
             ]);

             // Populate Types
             if (Array.isArray(typesRes)) {
                 membershipTypesCache = typesRes; // Update cache
                 typeSelect.innerHTML = '<option value="">-- Select Type --</option>' + typesRes.map(type => `<option value="${type.id}" data-fee="${parseFloat(type.fee || 0).toFixed(2)}">${type.name} (K${parseFloat(type.fee || 0).toFixed(2)})</option>`).join('');
             } else {
                  throw new Error(typesRes.message || "Failed to load membership types.");
             }

             // Populate Methods (excluding gateway initially)
             if (methodsRes.success && Array.isArray(methodsRes.data)) {
                 paymentMethodsCache = methodsRes.data; // Update cache
                 methodSelect.innerHTML = '<option value="">-- Select Method --</option>' + paymentMethodsCache
                     .filter(method => method !== 'gateway') // Exclude gateway
                     .map(method => `<option value="${method}">${method.replace(/_/g, ' ').toUpperCase()}</option>`).join('');
             } else {
                  throw new Error(methodsRes.message || "Failed to load payment methods.");
             }

             // --- Add/Remove Malipo option based on latest check ---
             updateMalipoOption();


            let initialBalance = 0;
            typeSelect.value = config.typeId || ''; // Pre-select type if applicable

            if (config.action === 'add_payment') {
                initialBalance = parseFloat(config.balanceDue) || 0;
            }

            // --- Set up event listeners ---
            const costUpdateHandler = () => updateCostDisplay(initialBalance);
            typeSelect.removeEventListener('change', costUpdateHandler); // Remove old before adding
            amountPaidInput.removeEventListener('input', costUpdateHandler);
            methodSelect.removeEventListener('change', costUpdateHandler);
            typeSelect.addEventListener('change', costUpdateHandler);
            amountPaidInput.addEventListener('input', costUpdateHandler);
            methodSelect.addEventListener('change', costUpdateHandler);

            updateCostDisplay(initialBalance); // Initial calculation
            manageModal.classList.remove('hidden');

        } catch (error) {
            showToast('Could not load data for membership management. ' + error.message, true);
            console.error("Modal Loading Error:", error);
            manageModal.classList.add('hidden');
        } finally {
            hideSpinner();
        }
    };


    // --- Generate Unique Order ID for Malipo ---
    const generateUniqueOrderId = () => { /* Keep as is */
        const now = new Date();
        const timestamp = now.getFullYear().toString() +
                          (now.getMonth() + 1).toString().padStart(2, '0') +
                          now.getDate().toString().padStart(2, '0') +
                          now.getHours().toString().padStart(2, '0') +
                          now.getMinutes().toString().padStart(2, '0') +
                          now.getSeconds().toString().padStart(2, '0');
        const randomPart = Math.random().toString(36).substring(2, 8).toUpperCase();
        return `MS-${USER_ID}-${timestamp}-${randomPart}`;
    };


    // --- Initiate MALIPO Payment ---
    const initiateMalipoPayment = (orderId, amountToPay) => { // Now accepts orderId and amount
        // Check for Malipo object
        if (!MALIPO_MERCHANT_ID || typeof Malipo === 'undefined') {
            showToast('MALIPO Gateway is not configured or SDK failed to load.', true);
            hideSpinner(); // Ensure spinner hidden if we bail early
            return;
        }

        const action = document.getElementById('membershipAction').value;
        const selectedTypeOption = typeSelect.options[typeSelect.selectedIndex];
        const typeName = selectedTypeOption ? selectedTypeOption.text.split(' (K')[0] : (action === 'add_payment' ? 'Membership Payment' : 'Membership');

        const description = `${typeName} for ${currentUserData.full_name || 'User'} (ID: ${USER_ID})`;

        console.log('Initiating MALIPO with:', {
             merchantAccount: MALIPO_MERCHANT_ID,
             currency: "MWK",
             amount: amountToPay,
             order_id: orderId, // Use the generated ID passed from submit handler
             description: description
         });

        // Spinner is already shown by submit handler

        Malipo.open({
            merchantAccount: MALIPO_MERCHANT_ID,
            currency: "MWK",
            amount: amountToPay,
            order_id: orderId, // Use the pre-generated order ID
            description: description,
            onSuccess: (result) => {
                console.log('Malipo Success:', result);
                hideSpinner(); // Hide spinner on success
                // Don't call backend here, just give user feedback
                manageModal.classList.add('hidden'); // Close modal
                showToast(`Payment initiated (ID: ${result.transaction_id}). Confirmation pending callback.`);
                // Optionally refresh data after a delay, or rely on manual refresh/callback update
                // setTimeout(refreshPageData, 5000); // Example: refresh after 5 seconds
            },
            onError: (error) => {
                console.error('Malipo Fail:', error);
                hideSpinner(); // Hide spinner on failure
                showToast(`MALIPO payment failed: ${error.message || JSON.stringify(error)}`, true);
                // Optionally: try to update the pending payment record to 'failed' via another API call
            },
             onClose: function() {
                 console.log('Malipo modal closed by user.');
                 hideSpinner(); // Hide spinner if user closes modal
                 showToast('MALIPO payment cancelled.', false); // Inform user
             }
        });
    };

     // --- Centralized Form Submission Logic (for PRE-Malipo and Non-Gateway) ---
     const submitMembershipForm = async (formData) => {
        showSpinner();
        try {
            const response = await fetch('/api/members/purchase.php', { // Ensure path is correct
                method: 'POST',
                body: formData
            });
            const data = await response.json(); // Read response body

            console.log("Pre-submit/Non-Gateway API Response Status:", response.status);
            console.log("Pre-submit/Non-Gateway API Response Data:", data);

            if (response.ok && data.success) {
                // If it was NOT a gateway payment, close modal, show success, refresh
                if (formData.get('payment_method') !== 'gateway') {
                    showToast(data.message || 'Operation successful!');
                    manageModal.classList.add('hidden');
                    refreshPageData();
                }
                // Return true to indicate success for gateway flow
                return true;
            } else {
                 // Throw error with message from API or a default
                throw new Error(data.message || `Request failed with status ${response.status}`);
            }
        } catch (error) {
            showToast(`Submission Error: ${error.message}`, true);
            console.error('Submission Error:', error);
            hideSpinner(); // Hide spinner on error
            return false; // Indicate failure
        }
        // finally { // Don't hide spinner here for gateway flow }
     };

    // --- Membership Form Submit Handler (Revised Flow) ---
    membershipForm.addEventListener('submit', async function(e) { // Make async
        e.preventDefault();
        const formData = new FormData(this);
        const selectedMethod = formData.get('payment_method');
        const amount_paid = parseFloat(formData.get('amount_paid') || '0');
        const action = formData.get('action');

         // --- Validation ---
         if ((action === 'create' || action === 'update') && !formData.get('membership_type_id')) {
             return showToast('Please select a membership type.', true);
         }
         if (amount_paid < 0) {
             return showToast('Amount paid cannot be negative.', true);
         }
         if (amount_paid > 0 && !selectedMethod) {
              return showToast('Please select a payment method.', true);
         }
         // --- End Validation ---


         if (selectedMethod === 'gateway') {
             if (amount_paid <= 0) {
                 return showToast('Amount must be greater than 0 for gateway payment.', true);
             }
             // 1. Generate Order ID for Malipo
             const generatedOrderId = generateUniqueOrderId();
             formData.set('generated_order_id', generatedOrderId); // Add to form data for backend
             document.getElementById('generatedOrderId').value = generatedOrderId; // Also set hidden input

             // 2. Submit to backend to create PENDING records
             const preSubmitSuccess = await submitMembershipForm(formData); // Await the result

             // 3. If backend pre-submit was OK, initiate Malipo
             if (preSubmitSuccess) {
                 initiateMalipoPayment(generatedOrderId, amount_paid);
                 // Spinner remains active until Malipo callback finishes or fails/closes
             } else {
                 // Error handled within submitMembershipForm
                 hideSpinner(); // Ensure spinner is hidden if pre-submit failed
             }

         } else {
             // Submit normally for non-gateway methods or if amount is 0
             // Remove generated_order_id if it exists from a previous attempt
             if (formData.has('generated_order_id')) {
                 formData.delete('generated_order_id');
             }
             submitMembershipForm(formData).finally(() => hideSpinner()); // Hide spinner after non-gateway submission
         }
    });


    // =================================================================
    // EDIT USER DETAILS LOGIC (Keep as is from previous version)
    // =================================================================
    const editUserModal = document.getElementById('editUserModal');
    const editUserForm = document.getElementById('editUserForm');
    const openEditUserModal = async () => { /* Keep as is */
        showSpinner();
        try {
            // Fetch user, companies, and roles data concurrently
            const [userRes, compRes, roleRes] = await Promise.all([
                fetch(`/api/users/read_single.php?id=${USER_ID}`),
                fetch('/api/companies/read_for_user.php'), // Ensure this endpoint returns {success: true, data: [...]}
                fetch('/api/roles/read_for_user.php')
            ]);

            // Check responses first
            if (!userRes.ok) throw new Error(`Failed to load user data: ${userRes.statusText}`);
            if (!compRes.ok) throw new Error(`Failed to load companies: ${compRes.statusText}`);
            if (!roleRes.ok) throw new Error(`Failed to load roles: ${roleRes.statusText}`);

            // Parse JSON
            const userDataResult = await userRes.json();
            const companiesResult = await compRes.json();
            const rolesResult = await roleRes.json();

            // Validate API success flags
            if (!userDataResult || !userDataResult.success) throw new Error(userDataResult.message || 'User data not found');
            if (!companiesResult || !companiesResult.success) throw new Error(companiesResult.message || 'Companies data not found');
            if (!rolesResult || !rolesResult.success) throw new Error(rolesResult.message || 'Roles data not found');

            const userData = userDataResult.data; // Access the nested data object
            const companiesData = companiesResult.data; // Access the nested data object
            const rolesData = rolesResult.data; // Access the nested data object


            // --- Populate form fields ---
            editUserForm.querySelector('#fullName').value = userData.full_name || '';
            editUserForm.querySelector('#gender').value = userData.gender || ''; // Use empty string if null
            editUserForm.querySelector('#email').value = userData.email || '';
            editUserForm.querySelector('#phone').value = userData.phone || '';
            editUserForm.querySelector('#role').value = userData.role || 'user';
            editUserForm.querySelector('#isActive').value = userData.is_active != null ? String(userData.is_active) : '1'; // Handle null, default to active
            editUserForm.querySelector('#position').value = userData.position || '';

            const isEmployedCheckbox = editUserForm.querySelector('#isEmployed');
            isEmployedCheckbox.checked = userData.is_employed == 1;

            // Populate Company Select
            const companySelect = editUserForm.querySelector('#companyId');
            companySelect.innerHTML = '<option value="">Select Company...</option>'; // Start fresh
            if (Array.isArray(companiesData)) {
                companiesData.forEach(c => {
                    const option = document.createElement('option');
                    option.value = c.id;
                    option.textContent = c.name;
                    companySelect.appendChild(option);
                });
            }
            // Set selected company AFTER populating
            companySelect.value = userData.company_id || '';


            // Populate Admin Roles Checkboxes
            const rolesCheckboxesContainer = editUserForm.querySelector('#rolesCheckboxes');
            rolesCheckboxesContainer.innerHTML = ''; // Clear previous
            const userAdminRoleIds = Array.isArray(userData.admin_roles) ? userData.admin_roles.map(r => String(r.id)) : [];
            if (Array.isArray(rolesData)) {
                rolesData.forEach(r => {
                    const isChecked = userAdminRoleIds.includes(String(r.id));
                    rolesCheckboxesContainer.innerHTML += `<div class="flex items-center"><input id="edit-role-${r.id}" name="admin_roles[]" value="${r.id}" type="checkbox" ${isChecked ? 'checked' : ''} class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><label for="edit-role-${r.id}" class="ml-2 text-sm text-slate-700">${r.name}</label></div>`;
                });
            }

            // Clear password field
             editUserForm.querySelector('#password').value = '';

            // Trigger change events AFTER setting initial values
            isEmployedCheckbox.dispatchEvent(new Event('change'));
            editUserForm.querySelector('#role').dispatchEvent(new Event('change'));

            editUserModal.classList.remove('hidden'); // Show modal

        } catch (error) {
            console.error("Error fetching user edit data:", error);
            showToast("Could not load user data for editing: " + error.message, true);
        } finally {
            hideSpinner();
        }
    };
    editUserModal.querySelector('#isEmployed').addEventListener('change', (e) => { /* Keep as is */
        editUserModal.querySelector('#employmentDetails').classList.toggle('hidden', !e.target.checked);
     });
    editUserModal.querySelector('#role').addEventListener('change', (e) => { /* Keep as is */
        editUserModal.querySelector('#adminRolesSection').classList.toggle('hidden', e.target.value !== 'admin');
    });
    editUserForm?.addEventListener('submit', function(e) { /* Keep as is */
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        // Process checkboxes correctly
        data.admin_roles = formData.getAll('admin_roles[]'); // Use getAll for checkboxes
        data.is_employed = this.querySelector('#isEmployed').checked ? 1 : 0;
        delete data.is_employed_checkbox; // Remove the temporary field

        // Handle null values for optional fields
        if (!data.is_employed) {
             data.company_id = null;
             data.position = '';
        } else if (data.company_id === '') { // If employed but no company selected
            data.company_id = null;
        }

        // Only include password if it's not blank
        if (data.password === '') {
            delete data.password;
        }

        showSpinner();
        fetch('/api/users/update.php', { // Ensure path is correct
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json().then(apiData => ({ ok: res.ok, status: res.status, data: apiData }))) // Get status and parse JSON
        .then(({ ok, status, data }) => {
            if (ok && data.success) { // Check for API success flag
                showToast(data.message || 'User updated successfully.');
                editUserModal.classList.add('hidden');
                refreshPageData(); // Refresh page data on success
            } else {
                 // Throw error with message from API or default
                throw new Error(data.message || `Failed to update user. Status: ${status}`);
            }
        }).catch(error => {
            showToast(`Update Error: ${error.message}`, true); // Show specific error
            console.error('Update User Error:', error);
        })
        .finally(() => hideSpinner());
    });


});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>