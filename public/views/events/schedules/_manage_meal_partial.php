<?php
// File: /partials/schedules/_manage_meal_partial.php

// We can assume $schedule is available from the parent manage.php file.
$schedule_id = $schedule['id'] ?? 0;
$event_id = $schedule['event_id'] ?? 0;
?>
<div id="eventHeader">
    <div class="space-y-4 animate-pulse">
        <div class="h-8 w-3/4 bg-slate-200 rounded-md"></div>
        <div class="flex space-x-6">
            <div class="h-4 w-1/4 bg-slate-200 rounded-md"></div>
            <div class="h-4 w-1/4 bg-slate-200 rounded-md"></div>
            <div class="h-4 w-1/3 bg-slate-200 rounded-md"></div>
        </div>
        <div class="h-10 bg-slate-200 rounded-md"></div>
    </div>
</div><br>

<div id="scheduleHeader">
    <div class="h-20 bg-gray-200 animate-pulse rounded-md"></div>
</div>

<div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Meal Details</h2>
                <button id="editDetailsBtn" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit</button>
            </div>
            <div id="scheduleDetails" class="mt-4 text-sm space-y-2">
                <div class="h-16 bg-gray-200 animate-pulse rounded-md"></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold text-gray-900">Statistics</h2>
            <div id="mealStats" class="mt-4 space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Total Attendees:</span>
                    <span id="totalAttendees" class="font-semibold text-gray-900 bg-gray-200 rounded-full px-2 animate-pulse">&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Cards Activated:</span>
                    <span id="activatedCount" class="font-semibold text-blue-600 bg-gray-200 rounded-full px-2 animate-pulse">&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Cards Collected:</span>
                    <span id="collectedCount" class="font-semibold text-green-600 bg-gray-200 rounded-full px-2 animate-pulse">&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Coordinators</h2>
                <button id="manageFacilitatorsBtn" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Manage</button>
            </div>
            <div id="facilitatorsList" class="mt-4 text-sm space-y-2">
                <div class="h-8 bg-gray-200 animate-pulse rounded-md"></div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
        <div class="sm:flex sm:justify-between sm:items-center">
            <h2 class="text-xl font-bold text-gray-900">Meal Card Attendees</h2>
            <button id="showQrBtn" class="mt-2 sm:mt-0 w-full sm:w-auto bg-gray-800 text-white px-3 py-1.5 text-sm font-semibold rounded-md hover:bg-gray-900 shadow-sm flex items-center justify-center gap-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V4h2v2H5zM3 10a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm2 2v-2h2v2H5zM9 4a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1V4zm2 2V4h2v2h-2zM9 10a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1v-2zm2 2v-2h2v2h-2zM3 16a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm2 2v-2h2v2H5zm6-4a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1v-2zm2 2v-2h2v2h-2zm-4 2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1v-2zm2 2v-2h2v2h-2z" clip-rule="evenodd" /></svg>
                Show Collection QR
            </button>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 border-t pt-4">
            <input type="text" id="searchInput" placeholder="Search ticket, name, email" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            <select id="companyFilter" class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                <option value="">All Companies</option>
            </select>
            <select id="employmentFilter" class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                <option value="">All Employment</option>
                <option value="1">Employed</option>
                <option value="0">Unemployed</option>
            </select>
            <select id="statusFilter" class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                <option value="">All Statuses</option>
                <option value="inactive">Inactive</option>
                <option value="about_to_collect">Activated</option>
                <option value="collected">Collected</option>
            </select>
        </div>

        <div id="bulkActions" class="hidden mt-4 flex items-center gap-x-3">
             <p class="text-sm text-gray-600">With <span id="selectedCount">0</span> selected:</p>
            <button id="bulkActivateBtn" class="bg-blue-600 text-white px-3 py-1.5 text-xs font-semibold rounded-md hover:bg-blue-700 shadow-sm">Activate Cards</button>
            <button id="bulkCollectBtn" class="bg-green-600 text-white px-3 py-1.5 text-xs font-semibold rounded-md hover:bg-green-700 shadow-sm">Mark as Collected</button>
        </div>
        
        <div id="attendeesContainer" class="mt-4 flow-root">
             <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th scope="col" class="relative py-3.5 pl-4 pr-3 sm:pl-6">
                                <input type="checkbox" id="selectAllCheckbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            </th>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ticket Code</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Company</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Card Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendeesTableBody" class="divide-y divide-gray-200">
                        <tr><td colspan="5"><div class="h-32 bg-gray-200 animate-pulse rounded-md my-4"></div></td></tr>
                    </tbody>
                </table>
             </div>
        </div>
    </div>
</div>


<div id="scheduleDetailsModal" class="hidden fixed inset-0 bg-slate-600/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Edit Schedule Details</h3>
        <form id="scheduleDetailsForm" class="mt-4 space-y-4">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($schedule_id); ?>">
            <div>
                <label for="details_title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="details_title" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <div>
                <label for="details_start" class="block text-sm font-medium text-gray-700">Start DateTime</label>
                <input type="datetime-local" name="start_datetime" id="details_start" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <div>
                <label for="details_end" class="block text-sm font-medium text-gray-700">End DateTime</label>
                <input type="datetime-local" name="end_datetime" id="details_end" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <div>
                <label for="details_status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="details_status" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div>
                <label for="details_description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="details_description" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></textarea>
            </div>
            <div class="pt-2 flex justify-end gap-x-2">
                <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm hover:bg-indigo-500">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="facilitatorsModal" class="hidden fixed inset-0 bg-slate-600/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Manage Coordinators</h3>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Current Coordinators</label>
            <div id="currentFacilitatorsList" class="mt-2 space-y-2 max-h-40 overflow-y-auto border p-3 rounded-md bg-gray-50"></div>
        </div>
        <form id="addFacilitatorForm" class="mt-4 pt-4 border-t">
            <label for="facilitatorSelect" class="block text-sm font-medium text-gray-700">Add New Coordinator</label>
            <div class="mt-2 flex gap-x-2">
                <select name="user_id" id="facilitatorSelect" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></select>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Add</button>
            </div>
        </form>
        <div class="mt-6 flex justify-end gap-x-2">
            <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<div id="qrModal" class="hidden fixed inset-0 bg-slate-600/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-sm shadow-lg rounded-md bg-white flex flex-col items-center">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Scan to Collect Meal Card</h3>
        <div id="qrcode" class="mt-4 w-64 h-64 bg-gray-100 flex items-center justify-center">
            <p class="text-xs text-gray-500">Generating QR Code...</p>
        </div>
        <p class="mt-4 text-center text-sm text-gray-600">Attendees can scan this code to mark their meal card as collected.</p>
        <div class="mt-6 w-full flex justify-center">
            <button type="button" class="close-modal bg-white px-4 py-2 rounded-md text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>


<input type="hidden" id="scheduleId" value="<?php echo htmlspecialchars($schedule_id); ?>">
<input type="hidden" id="eventId" value="<?php echo htmlspecialchars($event_id); ?>">

<div id="toast" class="hidden fixed top-5 right-5 z-[100]"></div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="/assets/js/manage-meal.js"></script>