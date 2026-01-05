<?php require_once __DIR__ . '/partials/header.php'; ?>
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">User Management</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage all users, roles, and permissions in the system.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <?php if (has_permission('users_create')): ?>
                    <button type="button" id="addUserBtn" class="block rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Add user</button>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <button type="button" id="bulkAddUserBtn" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 mt-2 sm:mt-0">Bulk Add Users</button>
                    <?php endif; ?>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <button type="button" id="bulkInvoice" class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 mt-2 sm:mt-0">Send Bulk Invoices</button>
                </div>
            </div>

            <div class="mt-8 bg-white p-4 sm:p-6 rounded-xl shadow border border-slate-200">
              <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">
                
                <!-- Search -->
                <div class="sm:col-span-6">
                  <label for="searchInput" class="block text-sm font-medium text-slate-800">Search</label>
                  <input
                    type="text"
                    id="searchInput"
                    placeholder="Search by name, email, phone, company..."
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  >
                </div>

                <!-- Status -->
                <div class="sm:col-span-2">
                  <label for="statusFilter" class="block text-sm font-medium text-slate-800">Status</label>
                  <select
                    id="statusFilter"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  >
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>

                <!-- Role -->
                <div class="sm:col-span-2">
                  <label for="roleFilter" class="block text-sm font-medium text-slate-800">Base Role</label>
                  <select
                    id="roleFilter"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  >
                    <option value="">All</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                  </select>
                </div>

                <!-- Employment -->
                <div class="sm:col-span-2">
                  <label for="employedFilter" class="block text-sm font-medium text-slate-800">Employment</label>
                  <select
                    id="employedFilter"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  >
                    <option value="">All</option>
                    <option value="1">Employed</option>
                    <option value="0">Unemployed</option>
                  </select>
                </div>

              </div>
            </div>

            
            <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div id="userTableContainer" class="overflow-x-auto">
                    </div>
                <div id="paginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                    </div>
            </div>
        </div>
    </main>
</div>

<div id="deleteConfirmModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Delete User</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Are you sure you want to delete this user? This action is permanent and cannot be undone.</p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-auto shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Confirm Delete
                </button>
                <button id="cancelDeleteBtn" class="px-4 py-2 ml-3 bg-gray-200 text-gray-900 text-base font-medium rounded-md w-auto shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<div id="bulkUserModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-10 mx-auto p-6 border w-full max-w-xl shadow-xl rounded-xl bg-white">
    <h3 class="text-lg font-semibold text-slate-900">Bulk Add New Users</h3>

    <form id="bulkUserForm" class="mt-4 space-y-5">

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Employment Status</label>
        <div class="flex items-center space-x-4">
          <div class="flex items-center">
            <input type="radio" id="bulkUnemployed" name="employment_status" value="unemployed" checked class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <label for="bulkUnemployed" class="ml-2 text-sm font-medium text-slate-900">Unemployed</label>
          </div>
          <div class="flex items-center">
            <input type="radio" id="bulkEmployed" name="employment_status" value="employed" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <label for="bulkEmployed" class="ml-2 text-sm font-medium text-slate-900">Employed</label>
          </div>
        </div>
      </div>

      <div id="bulkCompanySection" class="hidden">
        <label for="bulkCompanyId" class="block text-sm font-medium text-slate-700">Company</label>
        <select
          id="bulkCompanyId"
          name="company_id"
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
        >
          <option value="">Select Company...</option>
          {/* Options will be populated by JS */}
        </select>
      </div>

      <div>
        <label for="bulkPassword" class="block text-sm font-medium text-slate-700">Password for All Users</label>
        <input
          type="password"
          id="bulkPassword"
          name="password"
          required
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
        >
         <p class="mt-1 text-xs text-slate-500">This password will be set for all users in the CSV and included in their welcome email.</p>
      </div>

      <div>
        <label for="copyEmail" class="block text-sm font-medium text-slate-700">Send Copy of Emails To (Optional)</label>
        <input
          type="email"
          id="copyEmail"
          name="copy_email"
          placeholder="e.g., admin@example.com"
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
        >
         <p class="mt-1 text-xs text-slate-500">A copy (BCC) of each welcome email will be sent here.</p>
      </div>

      <div>
        <label for="csvFile" class="block text-sm font-medium text-slate-700">Upload CSV File</label>
        <input
          type="file"
          id="csvFile"
          name="csv_file"
          accept=".csv"
          required
          class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 focus:outline-none"
        >
        <p class="mt-1 text-xs text-slate-500">Required columns: <code class="bg-slate-100 px-1 rounded">Name</code>, <code class="bg-slate-100 px-1 rounded">Email</code>, <code class="bg-slate-100 px-1 rounded">Phone</code> (optional). First row should be headers.</p>
      </div>

      <div class="pt-4 flex justify-end space-x-3 border-t">
        <button
          type="button"
          id="closeBulkUserModalBtn"
          class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:outline-none"
        >
          Cancel
        </button>
        <button
          type="submit"
          id="bulkSubmitBtn"
          class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 flex items-center"
        >
           <span id="bulkSubmitText">Upload & Create Users</span>
           <svg id="bulkSubmitSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
           </svg>
        </button>
      </div>
    </form>

    <div id="bulkResults" class="mt-4 hidden max-h-60 overflow-y-auto border-t pt-4">
        <h4 class="text-md font-semibold text-slate-800 mb-2">Processing Results:</h4>
        <div id="bulkSuccessCount" class="text-sm text-green-600"></div>
        <div id="bulkErrorList" class="text-sm text-red-600 mt-1"></div>
    </div>

  </div>
</div>

<div id="bulkInvoiceModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Send Membership Invites</h3>
            <div class="mt-2 px-2">
                <p class="text-sm text-gray-500">
                    You have selected <span id="selectedCount" class="font-bold text-gray-900">0</span> users.
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    This will send a unique link to their email allowing them to select a membership type and pay via Malipo.
                </p>
            </div>
            
            <div class="mt-4 text-left">
                <label for="invoiceSubject" class="block text-sm font-medium text-slate-700">Email Subject</label>
                <input type="text" id="invoiceSubject" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="Complete your Membership Registration">
            </div>

            <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                <button id="confirmSendInvoiceBtn" type="button" class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:col-start-2">
                    Send Invites
                </button>
                <button id="cancelInvoiceBtn" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<div id="userModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
    <h3 class="text-lg font-semibold text-slate-900" id="modalTitle">Add New User</h3>

    <form id="userForm" class="mt-4 space-y-5">
      <input type="hidden" id="userId" name="id">

      <!-- Full Name / Gender -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="fullName" class="block text-sm font-medium text-slate-700">Full Name</label>
          <input
            type="text"
            id="fullName"
            name="full_name"
            required
            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
          >
        </div>
        <div>
          <label for="gender" class="block text-sm font-medium text-slate-700">Gender</label>
          <select
            id="gender"
            name="gender"
            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
          >
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
        </div>
      </div>

      <!-- Email / Phone -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
          <input
            type="email"
            id="email"
            name="email"
            required
            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
          >
        </div>
        <div>
          <label for="phone" class="block text-sm font-medium text-slate-700">Phone</label>
          <input
            type="tel"
            id="phone"
            name="phone"
            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
          >
        </div>
      </div>

      <!-- Employment -->
      <div class="space-y-3">
        <div class="flex items-center">
          <input
            type="checkbox"
            id="isEmployed"
            name="is_employed_checkbox"
            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
          >
          <label for="isEmployed" class="ml-3 text-sm font-medium text-slate-900">Is Employed?</label>
        </div>

        <div id="employmentDetails" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
          <div>
            <label for="companyId" class="block text-sm font-medium text-slate-700">Company</label>
            <select
              id="companyId"
              name="company_id"
              class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
            ></select>
          </div>
          <div>
            <label for="position" class="block text-sm font-medium text-slate-700">Position</label>
            <input
              type="text"
              id="position"
              name="position"
              class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
            >
          </div>
        </div>
      </div>

      <!-- Role / Status -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
        <div>
          <label for="role" class="block text-sm font-medium text-slate-700">Base Role</label>
          <select
            id="role"
            name="role"
            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
          >
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div>
          <label for="isActive" class="block text-sm font-medium text-slate-700">Status</label>
          <select
            id="isActive"
            name="is_active"
            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
          >
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>

      <!-- Admin Roles -->
      <div id="adminRolesSection" class="hidden space-y-3 border-t pt-4">
        <label class="block text-sm font-medium text-slate-900">Admin Roles</label>
        <div id="rolesCheckboxes" class="grid grid-cols-2 md:grid-cols-4 gap-2"></div>
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          placeholder="Leave blank to keep unchanged"
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
        >
      </div>

      <!-- Buttons -->
      <div class="pt-4 flex justify-end space-x-3">
        <button
          type="button"
          id="closeUserModalBtn"
          class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:outline-none"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
        >
          Save User
        </button>
      </div>
    </form>
  </div>
</div>


<div id="toast" class="hidden fixed top-5 right-5 bg-green-500 text-white py-2 px-4 rounded-lg shadow-md"><p id="toastMessage"></p></div>

<script src="/assets/js/users.js"></script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>