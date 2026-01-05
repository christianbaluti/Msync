<?php
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
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Financial Overview</h1>
                    <p class="mt-2 text-sm text-gray-700">View summaries, charts, and detailed transaction reports.</p>
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
            </div>

            <div class="mt-8">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Summary</h3>
                <dl class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm border border-slate-200 sm:p-6">
                        <dt class="truncate text-sm font-medium text-gray-500">Total Revenue (Completed)</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="kpiTotalRevenue">K0.00</dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm border border-slate-200 sm:p-6">
                        <dt class="truncate text-sm font-medium text-gray-500">Outstanding Revenue</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="kpiOutstandingRevenue">K0.00</dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm border border-slate-200 sm:p-6">
                        <dt class="truncate text-sm font-medium text-gray-500">Total Transactions</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="kpiTotalTransactions">0</dd>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm border border-slate-200 sm:p-6">
                        <dt class="truncate text-sm font-medium text-gray-500">Pending/Failed Payments</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="kpiPendingTransactions">0</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2 p-6 bg-white rounded-lg shadow-sm border border-slate-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Revenue Over Time</h3>
                    <div class="mt-4" style="height: 300px;">
                        <canvas id="revenueOverTimeChart"></canvas>
                    </div>
                </div>
                <div class="lg:col-span-1 p-6 bg-white rounded-lg shadow-sm border border-slate-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Revenue by Type</h3>
                    <div class="mt-4" style="height: 300px;">
                        <canvas id="revenueByTypeChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Transaction Report</h3>
                <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-4 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div class="sm:col-span-1">
                        <label for="typeFilter" class="block text-sm font-medium text-slate-700">Payment Type</label>
                        <select id="typeFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            <option value="">All Types</option>
                            <option value="membership">Membership</option>
                            <option value="event_ticket">Event Ticket</option>
                            <option value="marketplace_order">Marketplace</option>
                            <option value="invoice">Invoice</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label for="methodFilter" class="block text-sm font-medium text-slate-700">Method</label>
                        <select id="methodFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            <option value="">All Methods</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="gateway">Gateway</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label for="statusFilter" class="block text-sm font-medium text-slate-700">Status</label>
                        <select id="statusFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                     <div class="sm:col-span-1">
                        <label for="searchInput" class="block text-sm font-medium text-slate-700">Search</label>
                        <input type="text" id="searchInput" placeholder="User name, email, ref..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                </div>

                <div class="mt-4 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <div id="paymentsTableContainer" class="overflow-x-auto">
                        </div>
                    <div id="paginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                        </div>
                </div>
            </div>

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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script src="/assets/js/finance.js"></script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>