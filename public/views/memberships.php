<?php require_once __DIR__ . '/partials/header.php'; ?>
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Membership Management</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage membership types, individual members, and company members.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <div class="flex items-center gap-x-2">
                        <button type="button" id="manageTypesBtn" class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Manage Types</button>
                        <button type="button" id="addMultipleMembersBtn" class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Add Multiple</button>
                        <a href="/memberships/create-id" class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Create ID</a>
                        <button type="button" id="addSingleMemberBtn" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Add Member</button>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-white p-4 rounded-lg shadow-sm">
                <form id="filtersForm">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="filter-id" class="block text-sm font-medium leading-6 text-gray-900">Membership ID</label>
                            <div class="mt-1"><input type="text" name="membership_card_number" id="filter-id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" placeholder="e.g., MS-1234"></div>
                        </div>
                        <div>
                            <label for="filter-name" class="block text-sm font-medium leading-6 text-gray-900">Name</label>
                            <div class="mt-1"><input type="text" name="full_name" id="filter-name" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" placeholder="e.g., John Doe"></div>
                        </div>
                        <div>
                            <label for="filter-company" class="block text-sm font-medium leading-6 text-gray-900">Company</label>
                            <div class="mt-1"><input type="text" name="company_name" id="filter-company" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" placeholder="e.g., TechCorp"></div>
                        </div>
                        <div>
                            <label for="filter-status" class="block text-sm font-medium leading-6 text-gray-900">Status</label>
                            <div class="mt-1">
                                <select id="filter-status" name="status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                                    <option value="">All</option>
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="expired">Expired</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-x-2">
                        <button type="button" id="clearFiltersBtn" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Clear</button>
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Filter</button>
                    </div>
                </form>
            </div>

            <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div id="membersTableContainer" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Membership</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires On</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="membersTableBody">
                            <tr>
                                <td colspan="6" class="px-3 py-12 text-center text-sm text-gray-500">
                                    <div role="status" class="flex items-center justify-center gap-x-2">
                                        <svg aria-hidden="true" class="w-6 h-6 text-gray-200 animate-spin fill-indigo-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0492C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                                        <span class="text-base">Loading members...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="paginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                     <div class="hidden sm:block">
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium" id="pagination-from">0</span> to <span class="font-medium" id="pagination-to">0</span> of <span class="font-medium" id="pagination-total">0</span> results
                        </p>
                    </div>
                    <div class="flex flex-1 justify-between sm:justify-end">
                        <button id="pagination-prev" class="relative inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50" disabled>Previous</button>
                        <button id="pagination-next" class="relative ml-3 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50" disabled>Next</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="addSingleMemberModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 id="addMemberModalTitle" class="text-lg font-medium text-gray-900">Add New Member Subscription</h3>
            <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center close-modal">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
        <form id="addSingleMemberForm" class="mt-4 space-y-4">
            <input type="hidden" name="user_id">
            <input type="hidden" name="subscription_id">
            
            <fieldset>
                <div class="space-y-2">
                    <div class="flex items-center gap-x-6">
                        <label class="text-sm font-medium text-gray-900">User Type:</label>
                        <div class="flex items-center gap-x-3">
                            <input id="user-type-existing" name="user_type" type="radio" value="existing" class="h-4 w-4 border-gray-300 text-indigo-600" checked>
                            <label for="user-type-existing" class="block text-sm font-medium leading-6 text-gray-900">Existing User</label>
                        </div>
                        <div class="flex items-center gap-x-3">
                            <input id="user-type-new" name="user_type" type="radio" value="new" class="h-4 w-4 border-gray-300 text-indigo-600">
                            <label for="user-type-new" class="block text-sm font-medium leading-6 text-gray-900">New User</label>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div id="existingUserSection" class="relative">
                <label for="user-search" class="block text-sm font-medium">Search Existing User</label>
                <input type="text" id="user-search" autocomplete="off" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" placeholder="Start typing name or email...">
                <div id="user-search-results" class="hidden absolute z-10 w-full bg-white border rounded-md shadow-lg max-h-48 overflow-y-auto"></div>
            </div>

            <div id="newUserSection" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="member-name" class="block text-sm font-medium">Full Name</label>
                    <input type="text" id="member-name" name="full_name" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                </div>
                <div>
                    <label for="member-email" class="block text-sm font-medium">Email</label>
                    <input type="email" id="member-email" name="email" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                </div>
                <div>
                    <label for="member-phone" class="block text-sm font-medium">Phone</label>
                    <input type="tel" id="member-phone" name="phone" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                </div>
            </div>
            
            <fieldset>
                 <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="member-type" class="block text-sm font-medium">Membership Type</label>
                        <select id="member-type" name="membership_type_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" required></select>
                    </div>
                    <div>
                        <label for="member-start-date" class="block text-sm font-medium">Start Date</label>
                        <input type="date" id="member-start-date" name="start_date" class="mt-1 w-full rounded border-gray-300 shadow-sm" required>
                    </div>
                </div>
            </fieldset>
            
            <fieldset class="border-t pt-4">
                 <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="member-amount-paid" class="block text-sm font-medium">Amount Paid</label>
                        <input type="number" step="0.01" id="member-amount-paid" name="amount_paid" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" placeholder="0.00">
                    </div>
                    <div>
                        <label for="member-payment" class="block text-sm font-medium">Payment Method</label>
                        <select id="member-payment" name="payment_method" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm"></select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="member-proof" class="block text-sm font-medium">Proof of Payment</label>
                        <input type="file" id="member-proof" name="payment_proof" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-x-3">
                    <input id="issue-receipt" name="issue_receipt" type="checkbox" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600" checked>
                    <label for="issue-receipt" class="text-sm text-gray-900">Issue & Email Receipt for this payment</label>
                </div>
            </fieldset>

            <div class="pt-4 flex justify-end gap-x-2 border-t">
                <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 close-modal">Cancel</button>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Save Subscription</button>
            </div>
        </form>
    </div>
</div>

<div id="membershipTypesModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-lg font-medium text-gray-900">Manage Membership Types</h3>
            <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center close-modal">
                 <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-1 gap-6">
            <div>
                <form id="membershipTypeForm" class="space-y-4 bg-gray-50 p-4 rounded-lg">
                    <input type="hidden" name="id">
                    <h4 id="typeFormTitle" class="text-md font-semibold text-gray-800">Add New Type</h4>
                    <div>
                        <label for="type-name" class="block text-sm font-medium">Name</label>
                        <input type="text" id="type-name" name="name" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="type-fee" class="block text-sm font-medium">Fee (MWK)</label>
                            <input type="number" step="0.01" id="type-fee" name="fee" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" required>
                        </div>
                        <div>
                            <label for="type-renewal" class="block text-sm font-medium">Renewal Month</label>
                            <select id="type-renewal" name="renewal_month" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" required>
                                <option value="1">January</option> <option value="2">February</option> <option value="3">March</option> <option value="4">April</option> <option value="5">May</option> <option value="6">June</option> <option value="7">July</option> <option value="8">August</option> <option value="9">September</option> <option value="10">October</option> <option value="11">November</option> <option value="12">December</option>
                            </select>
                        </div>
                    </div>
                     <div>
                        <label for="type-description" class="block text-sm font-medium">Description</label>
                        <textarea id="type-description" name="description" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-x-2">
                        <button type="button" id="cancelEditTypeBtn" class="hidden rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300">Cancel</button>
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Save Type</button>
                    </div>
                </form>
            </div>
            <div id="membershipTypesList" class="max-h-64 overflow-y-auto border rounded-lg">
                </div>
        </div>
    </div>
</div>

<div id="addMultipleMembersModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-lg font-medium text-gray-900">Add Multiple Members</h3>
            <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center close-modal">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
        <form id="addMultipleMembersForm" data-current-step="1" class="mt-4 space-y-6" enctype="multipart/form-data">
            
            <div data-step="1">
                <h4 class="text-md font-semibold text-gray-800 mb-2">How do you want to add members?</h4>
                <fieldset class="space-y-4">
                    <div class="flex items-center">
                        <input id="bulk-type-existing" name="bulk_user_type" type="radio" value="existing" class="h-4 w-4 border-gray-300 text-indigo-600" checked>
                        <label for="bulk-type-existing" class="ml-3 block text-sm font-medium leading-6 text-gray-900">From an Existing Company</label>
                    </div>
                    <div class="flex items-center">
                        <input id="bulk-type-csv" name="bulk_user_type" type="radio" value="csv" class="h-4 w-4 border-gray-300 text-indigo-600">
                        <label for="bulk-type-csv" class="ml-3 block text-sm font-medium leading-6 text-gray-900">By Uploading a CSV File</label>
                    </div>
                </fieldset>
            </div>

            <div data-step="2" data-section="existing" class="hidden">
                 <h4 class="text-md font-semibold text-gray-800 mb-2">Select Company & Users</h4>
                 <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                     <div>
                        <label for="bulk-company-select" class="block text-sm font-medium">Company</label>
                        <select id="bulk-company-select" name="company_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm"></select>
                    </div>
                    <div>
                        <label for="bulk-user-search" class="block text-sm font-medium">Search Users</label>
                        <input type="text" id="bulk-user-search" placeholder="Search by name or email..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                    </div>
                 </div>
                <div id="bulk-user-list" class="mt-4 max-h-48 overflow-y-auto space-y-4 border p-3 rounded-md bg-gray-50">
                    <p class="text-sm text-gray-500 text-center py-4">Select a company to see its users.</p>
                </div>
                <div class="mt-3">
                    <label class="text-sm font-medium text-gray-700">Selected Users:</label>
                    <div id="bulk-selected-users" class="mt-1 p-2 border rounded-md min-h-[40px] bg-white flex flex-wrap gap-2">
                        <span class="text-sm text-gray-400">None</span>
                    </div>
                </div>
            </div>

             <div data-step="2" data-section="csv" class="hidden">
                 <h4 class="text-md font-semibold text-gray-800 mb-2">Upload CSV File</h4>
                 <p class="text-sm text-gray-600 mb-2">The CSV must have columns: `full_name`, `email`, `phone` (optional), `position` (optional).</p>
                 <input type="file" name="csv_file" id="bulk-csv-input" accept=".csv" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
            </div>

            <div data-step="3" class="hidden">
                <h4 class="text-md font-semibold text-gray-800 mb-2">Configure Subscription Details</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="bulk-membership-type" class="block text-sm font-medium">Membership Type</label>
                        <select id="bulk-membership-type" name="membership_type_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" required></select>
                    </div>
                    <div>
                        <label for="bulk-start-date" class="block text-sm font-medium">Start Date</label>
                        <input type="date" id="bulk-start-date" name="start_date" class="mt-1 w-full rounded border-gray-300 shadow-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4 p-3 bg-gray-50 rounded-md">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Plan Cost</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900" id="bulk-plan-cost">MWK 0.00</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Balance Due (Per Member)</dt>
                        <dd class="mt-1 text-lg font-semibold text-red-600" id="bulk-balance-due">MWK 0.00</dd>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                     <div>
                        <label for="bulk-amount-paid" class="block text-sm font-medium">Amount Paid (Per Member)</label>
                        <input type="number" step="0.01" id="bulk-amount-paid" name="amount_paid" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm" placeholder="0.00" value="0">
                    </div>
                    <div>
                        <label for="bulk-payment-method" class="block text-sm font-medium">Payment Method</label>
                        <select id="bulk-payment-method" name="payment_method" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm"></select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="bulk-payment-proof" class="block text-sm font-medium">Proof of Payment (Optional)</label>
                        <input type="file" id="bulk-payment-proof" name="payment_proof" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-x-6 mt-2">
                        <div class="flex items-center gap-x-3">
                           <input id="bulk-issue-invoice" name="issue_invoice" type="checkbox" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600">
                           <label for="bulk-issue-invoice" class="text-sm text-gray-900">Issue & Email Invoices</label>
                       </div>
                        <div class="flex items-center gap-x-3">
                            <input id="bulk-issue-receipt" name="issue_receipt" type="checkbox" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600" checked>
                            <label for="bulk-issue-receipt" class="text-sm text-gray-900">Issue & Email Receipts</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-between gap-x-2 border-t">
                <button type="button" id="bulk-back-btn" class="hidden rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300">Back</button>
                <div>
                     <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 close-modal">Cancel</button>
                    <button type="button" id="bulk-next-btn" class="ml-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Next</button>
                    <button type="submit" id="bulk-submit-btn" class="hidden ml-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Subscribe Users</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="deleteConfirmModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <h3 id="deleteModalTitle" class="text-lg leading-6 font-medium text-gray-900 mt-2">Confirm Deletion</h3>
            <div class="mt-2 px-7 py-3">
                <p id="deleteModalMessage" class="text-sm text-gray-500">Are you sure? This action cannot be undone.</p>
            </div>
            <div class="flex justify-center items-center gap-x-4 px-4 py-3">
                <button id="cancelDeleteBtn" class="px-4 py-2 bg-gray-200 text-gray-900 text-base font-medium rounded-md w-auto shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    Cancel
                </button>
                <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-auto shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Confirm Delete
                </button>
            </div>
        </div>
    </div>
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

<div id="toast" class="hidden fixed top-5 right-5 z-[100] space-y-2"></div>
<script src="/assets/js/members.js"></script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>