<?php require_once __DIR__ . '/partials/header.php'; ?>
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Company Management</h1>
                    <p class="mt-2 text-sm text-gray-700">A list of all companies registered in the system.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <?php if (has_permission('companies_create')): ?>
                    <button type="button" id="addCompanyBtn" class="block rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Add Company</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-4 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                <div class="sm:col-span-2">
                    <label for="searchInput" class="block text-sm font-medium text-slate-700">Search Company</label>
                    <input type="text" id="searchInput" placeholder="Search by name, email, or phone..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                <div>
                    <label for="statusFilter" class="block text-sm font-medium text-slate-700">Status</label>
                    <select id="statusFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                        <option value="">All Statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div id="companyTableContainer" class="overflow-x-auto">
                    </div>
                <div id="paginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                    </div>
            </div>
        </div>
    </main>
</div>

<div id="companyModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900" id="modalTitle"></h3>
        <form id="companyForm" class="mt-4 space-y-5">
            <input type="hidden" id="companyId" name="id">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Company Name</label>
                <input type="text" id="name" name="name" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                    <input type="email" id="email" name="email" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
            </div>
            <div>
                <label for="address" class="block text-sm font-medium text-slate-700">Address</label>
                <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
                 <div>
                    <label for="allowLogin" class="block text-sm font-medium text-slate-700">Allow System Login?</label>
                    <select id="allowLogin" name="allow_login" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"><option value="1">Yes</option><option value="0">No</option></select>
                </div>
                <div>
                    <label for="isActive" class="block text-sm font-medium text-slate-700">Status</label>
                    <select id="isActive" name="is_active" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"><option value="1">Active</option><option value="0">Inactive</option></select>
                </div>
             </div>
             <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input type="password" id="password" name="password" placeholder="Leave blank to keep unchanged" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Company</button>
            </div>
        </form>
    </div>
</div>

<div id="viewCompanyModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900" id="viewCompanyName"></h3>
                <p class="text-sm text-gray-500" id="viewCompanyEmail"></p>
            </div>
            <button type="button" class="close-modal-btn text-2xl font-light text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="mt-6 border-t border-gray-200">
            <dl class="divide-y divide-gray-200" id="viewCompanyDetails"></dl>
            <div id="viewCompanyLinks" class="mt-4"></div>
        </div>
        <div class="mt-6 pt-4 border-t flex justify-end gap-x-3">
            <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Close</button>
            <?php if (has_permission('companies_delete')): ?>
                <button type="button" id="viewDeleteBtn" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Delete</button>
            <?php endif; ?>
            <?php if (has_permission('companies_update')): ?>
                <button type="button" id="viewEditBtn" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Edit</button>
            <?php endif; ?>
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
<div id="toast" class="hidden fixed bottom-5 right-5 z-50 bg-gray-800 text-white py-2 px-4 rounded-lg shadow-md">
    <p id="toastMessage"></p>
</div>

<script src="/assets/js/companies.js"></script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>