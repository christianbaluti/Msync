<?php require_once __DIR__ . '/partials/header.php'; ?>
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Event Management</h1>
                    <p class="mt-2 text-sm text-gray-700">A list of all past, current, and upcoming events.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <?php if (has_permission('events_create')): ?>
                    <button type="button" id="addEventBtn" class="block rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Create Event</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-4 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                <div class="sm:col-span-2">
                    <label for="searchInput" class="block text-sm font-medium text-slate-700">Search</label>
                    <input type="text" id="searchInput" placeholder="Search by title or location..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label for="statusFilter" class="block text-sm font-medium text-slate-700">Status</label>
                    <select id="statusFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                        <option value="">All</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div id="eventTableContainer" class="overflow-x-auto"></div>
                <div id="paginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4"></div>
            </div>
        </div>
    </main>
</div>

<div id="eventModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg leading-6 font-semibold text-slate-900" id="modalTitle"></h3>
        <form id="eventForm" class="mt-4 space-y-4" enctype="multipart/form-data">
            <input type="hidden" id="eventId" name="id">
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700">Title</label>
                <input type="text" id="title" name="title" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400"></textarea>
            </div>
            <div>
                <label for="main_image" class="block text-sm font-medium text-slate-700">Event Image</label>
                <input type="file" id="main_image" name="main_image" accept="image/png, image/jpeg, image/gif" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <img id="imagePreview" src="" alt="Image Preview" class="mt-2 h-32 w-auto object-cover rounded hidden">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="start_datetime" class="block text-sm font-medium text-slate-700">Start Date & Time</label>
                    <input type="datetime-local" id="start_datetime" name="start_datetime" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label for="end_datetime" class="block text-sm font-medium text-slate-700">End Date & Time</label>
                    <input type="datetime-local" id="end_datetime" name="end_datetime" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400">
                </div>
            </div>
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="location" class="block text-sm font-medium text-slate-700">Location</label>
                    <input type="text" id="location" name="location" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>
             </div>
             <div class="pt-4 flex justify-end space-x-3 border-t border-slate-200">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Event</button>
            </div>
        </form>
    </div>
</div>

<div id="viewEventModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-4xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start pb-4 border-b">
            <div>
                <h3 id="viewEventTitle" class="text-2xl font-bold text-gray-900"></h3>
                <p id="viewEventLocation" class="mt-2 text-sm text-gray-500 font-medium"></p>
            </div>
            <button type="button" class="close-modal-btn absolute top-3 right-3 p-2 rounded-full text-gray-400 hover:bg-gray-100 focus:outline-none" aria-label="Close modal">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 flex flex-col md:flex-row gap-8">
            <div class="flex-1 bg-white rounded-xl space-y-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">Description</h4>
                    <p id="viewEventDescription" class="mt-2 text-gray-900 text-sm leading-relaxed prose prose-sm max-w-none"></p>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <dt class="text-xs font-medium text-gray-500 uppercase">Timeline</dt>
                        <dd id="viewEventTimeline" class="mt-1 text-gray-900 text-sm font-medium"></dd>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <dt class="text-xs font-medium text-gray-500 uppercase">Created By</dt>
                        <dd id="viewEventCreatedBy" class="mt-1 text-gray-900 text-sm font-medium"></dd>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border sm:col-span-2">
                        <dt class="text-xs font-medium text-gray-500 uppercase">Created At</dt>
                        <dd id="viewEventCreatedAt" class="mt-1 text-gray-900 text-sm font-medium"></dd>
                    </div>
                </dl>
            </div>
            <div class="md:w-72 flex-shrink-0">
                <div class="bg-slate-50 border border-slate-200 rounded-lg shadow-sm">
                    <img id="viewEventImage" src="/assets/img/placeholder.png" alt="Event Image" class="w-full h-40 object-cover rounded-t-lg bg-gray-100">
                    <div class="p-4 space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd id="viewEventStatus" class="mt-1 text-sm text-gray-900"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tickets Sold</dt>
                            <dd id="viewEventTicketsSold" class="mt-1 text-2xl font-semibold text-gray-900"></dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                    <button data-tab="schedules" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">Schedules</button>
                    <button data-tab="tickets" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">Ticket Types</button>
                    <button data-tab="holders" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">Ticket Holders</button>
                    <button data-tab="companies" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">Company Purchases</button>
                    <button data-tab="checkins" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">Check Ins</button>
                </nav>
            </div>
            <div class="mt-4 max-h-64 overflow-y-auto">
                <div id="tab-content-schedules" class="tab-content space-y-2 text-sm"></div>
                <div id="tab-content-tickets" class="tab-content hidden space-y-2 text-sm"></div>
                <div id="tab-content-holders" class="tab-content hidden text-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email / Phone</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ticket Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ticket Code</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            </tbody>
                    </table>
                </div>
                <div id="tab-content-companies" class="tab-content hidden space-y-1 text-sm"></div>
                <div id="tab-content-checkins" class="tab-content hidden text-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item Checked In</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-6 pt-4 border-t flex justify-end">
             <button type="button" class="close-modal-btn rounded-md bg-white px-3.5 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Close
            </button>
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
<div id="toast" class="hidden fixed top-5 right-5 text-white py-2 px-4 rounded-lg shadow-md z-50"><p id="toastMessage"></p></div>

<div id="genericConfirmModal" class="hidden fixed inset-0 bg-slate-600/50 z-[60] overflow-y-auto flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-full max-w-md shadow-xl rounded-xl bg-white">
        <h3 id="confirmModalTitle" class="text-lg font-semibold text-slate-900">Confirm Action</h3>
        <p id="confirmModalMessage" class="mt-2 text-sm text-slate-600">Are you sure you want to proceed?</p>
        <div class="mt-6 flex justify-end gap-x-3">
            <button type="button" class="close-modal rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
            <button type="button" id="confirmModalConfirmBtn" data-id="" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Delete</button>
        </div>
    </div>
</div>

<script src="/assets/js/events.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>