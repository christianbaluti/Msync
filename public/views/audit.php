<?php
// /views/audit.php
require_once dirname(__DIR__, 2) . '/api/core/initialize.php'; // For auth
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Audit Logs</h1>
                    <p class="mt-2 text-sm text-gray-700">A log of all important actions performed in the system.</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                <div class="sm:col-span-2">
                    <label for="startDateFilter" class="block text-sm font-medium text-slate-700">Start Date</label>
                    <input type="date" id="startDateFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label for="endDateFilter" class="block text-sm font-medium text-slate-700">End Date</label>
                    <input type="date" id="endDateFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                <div class="sm:col-span-2 flex items-end">
                    <button type="button" id="applyDateFilter" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000] w-full sm:w-auto">Apply</button>
                    <button type="button" id="clearDateFilter" class="ml-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Clear</button>
                </div>
                <div class="sm:col-span-2">
                    <label for="actorTypeFilter" class="block text-sm font-medium text-slate-700">Actor Type</label>
                    <select id="actorTypeFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                        <option value="">All Types</option>
                        <option value="user">User</option>
                        <option value="company">Company</option>
                        <option value="system">System</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="actorSearchInput" class="block text-sm font-medium text-slate-700">Search Actor</label>
                    <input type="text" id="actorSearchInput" placeholder="Search by actor name..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label for="actionSearchInput" class="block text-sm font-medium text-slate-700">Search Action</label>
                    <input type="text" id="actionSearchInput" placeholder="Search by action name (e.g., user_create)" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
            </div>

            <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div id="auditTableContainer" class="overflow-x-auto">
                    </div>
                <div id="paginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                    </div>
            </div>
        </div>
    </main>
</div>

<div id="viewLogModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start">
            <h3 class="text-xl font-bold text-gray-900" id="viewLogTitle">Log Details</h3>
            <button type="button" class="close-modal-btn text-2xl font-light text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="mt-6 border-t border-gray-200">
            <dl class="divide-y divide-gray-200" id="viewLogDetails">
                </dl>
            <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-500">Metadata</h4>
                <pre id="viewLogMeta" class="mt-1 text-sm text-gray-900 bg-gray-50 p-4 rounded-md overflow-x-auto"></pre>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t flex justify-end gap-x-3">
            <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Close</button>
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

<script src="/assets/js/audit.js"></script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>