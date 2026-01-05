<?php
// /views/advertisements.php
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
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Advertisement Management</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage ad campaigns and app placements for the mobile app.</p>
                </div>
            </div>

            <div class="mt-6">
                <div class="sm:hidden">
                    <label for="tabs" class="sr-only">Select a tab</label>
                    <select id="tabs" name="tabs" class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option selected>Ad Campaigns</option>
                        <option>App Placements</option>
                    </select>
                </div>
                <div class="hidden sm:block">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button id="tab-btn-campaigns" class="tab-btn border-indigo-500 text-indigo-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" aria-current="page">
                                Ad Campaigns
                            </button>
                            <button id="tab-btn-placements" class="tab-btn border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                                App Placements
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            <div id="tab-panel-campaigns" class="tab-panel mt-6">
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div class="sm:col-span-3">
                        <label for="adSearchInput" class="block text-sm font-medium text-slate-700">Search Campaigns</label>
                        <input type="text" id="adSearchInput" placeholder="Search by title..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="adStatusFilter" class="block text-sm font-medium text-slate-700">Status</label>
                        <select id="adStatusFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="running">Running</option>
                            <option value="paused">Paused</option>
                            <option value="ended">Ended</option>
                        </select>
                    </div>
                    <?php if (has_permission('ads_create')): ?>
                    <div class="sm:col-span-1 flex items-end">
                        <button type="button" id="addAdBtn" class="block w-full rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Add Campaign</button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <div id="adTableContainer" class="overflow-x-auto"></div>
                    <div id="adPaginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4"></div>
                </div>
            </div>

            <div id="tab-panel-placements" class="tab-panel mt-6 hidden">
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div class="sm:col-span-5">
                         <p class="text-sm text-gray-600">Manage the designated pages and positions where ads can be displayed in the mobile app.</p>
                    </div>
                    <?php if (has_permission('ads_create')): // Using same perm for simplicity ?>
                    <div class="sm:col-span-1 flex items-end">
                        <button type="button" id="addPlacementBtn" class="block w-full rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Add Placement</button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <div id="placementTableContainer" class="overflow-x-auto"></div>
                    </div>
            </div>

        </div>
    </main>
</div>

<div id="adModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900" id="adModalTitle">Add New Ad Campaign</h3>
        <form id="adForm" class="mt-4 space-y-5" enctype="multipart/form-data">
            <input type="hidden" id="adId" name="id">
            
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700">Title</label>
                <input type="text" id="title" name="title" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            
            <div>
                <label for="media" class="block text-sm font-medium text-slate-700">Ad Media (Image/Video)</label>
                <input type="file" id="media" name="media" accept="image/*,video/*" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <div id="current-media-preview" class="mt-2"></div>
            </div>

            <div>
                <label for="body" class="block text-sm font-medium text-slate-700">Body Text (Optional)</label>
                <textarea id="body" name="body" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></textarea>
            </div>

            <div>
                <label for="url_target" class="block text-sm font-medium text-slate-700">Click-through URL (e.g., https://example.com)</label>
                <input type="url" id="url_target" name="url_target" placeholder="https://example.com" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="start_at" class="block text-sm font-medium text-slate-700">Start Date & Time</label>
                    <input type="datetime-local" id="start_at" name="start_at" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                <div>
                    <label for="end_at" class="block text-sm font-medium text-slate-700">End Date & Time</label>
                    <input type="datetime-local" id="end_at" name="end_at" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    <option value="draft">Draft</option>
                    <option value="running">Running</option>
                    <option value="paused">Paused</option>
                </select>
            </div>

            <div class="space-y-3 border-t pt-4">
                <label class="block text-sm font-medium text-slate-900">App Placements</label>
                <p class="text-xs text-gray-500">Select where this ad should appear in the app and define its position (e.g., 'TOP_BANNER', 'IN_FEED').</p>
                <div id="placements-container" class="space-y-2 max-h-48 overflow-y-auto">
                    </div>
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Campaign</button>
            </div>
        </form>
    </div>
</div>

<div id="placementModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900" id="placementModalTitle">Add App Placement</h3>
        <form id="placementForm" class="mt-4 space-y-5">
            <input type="hidden" id="pageId" name="id">
            <div>
                <label for="label" class="block text-sm font-medium text-slate-700">Label (Human-friendly name)</label>
                <input type="text" id="label" name="label" required placeholder="e.g., Home Screen Banner" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <div>
                <label for="code" class="block text-sm font-medium text-slate-700">Code (Unique identifier for app)</label>
                <input type="text" id="code" name="code" required placeholder="e.g., HOME_SCREEN_BANNER" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                <p class="mt-1 text-xs text-gray-500">Must be unique and match the code used in the mobile app.</p>
            </div>
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Placement</button>
            </div>
        </form>
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

<script src="/assets/js/advertisements.js"></script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>