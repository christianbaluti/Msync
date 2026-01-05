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
                <h2 class="text-xl font-bold text-gray-900">Details</h2>
                <button id="editDetailsBtn" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit</button>
            </div>
            <div id="scheduleDetails" class="mt-4 text-sm space-y-2">
                <div class="h-16 bg-gray-200 animate-pulse rounded-md"></div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Facilitators</h2>
                <button id="manageFacilitatorsBtn" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Manage</button>
            </div>
            <div id="facilitatorsList" class="mt-4 text-sm space-y-2">
                <div class="h-8 bg-gray-200 animate-pulse rounded-md"></div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900">Training Materials</h2>
            <button id="addMaterialBtn" class="bg-indigo-600 text-white px-3 py-1.5 text-sm font-semibold rounded-md hover:bg-indigo-700 shadow-sm">Add Material</button>
        </div>
        <div id="materialsContainer" class="mt-4 flow-root">
            <div class="h-32 bg-gray-200 animate-pulse rounded-md"></div>
        </div>
    </div>
</div>

<div id="genericConfirmModal" class="hidden fixed inset-0 bg-slate-600/50 z-100 overflow-y-auto">
    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-xl rounded-xl bg-white">
        <h3 id="confirmModalTitle" class="text-lg font-semibold text-slate-900">Confirm Action</h3>
        <p id="confirmModalMessage" class="mt-2 text-sm text-slate-600">Are you sure you want to proceed?</p>
        <div class="mt-6 flex justify-end gap-x-3">
            <button type="button" class="close-modal rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
            <button type="button" id="confirmModalConfirmBtn" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Delete</button>
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
<style>
    @keyframes wave { 0%, 100% { transform: scaleY(0.5); } 50% { transform: scaleY(1.5); } }
    .wave-bar { animation: wave 1s infinite ease-in-out; }
</style>

<div id="materialModal" class="hidden fixed inset-0 bg-gray-800/10 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 id="materialModalTitle" class="text-lg font-medium leading-6 text-gray-900">Add Training Material</h3>
        <form id="materialForm" class="mt-4 space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="id">
            <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="title" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" id="type" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
                    <option value="link">Link</option>
                    <option value="video">Video</option>
                    <option value="pdf">PDF</option>
                    <option value="ppt">PowerPoint</option>
                    <option value="audio">Audio</option>
                </select>
            </div>
            <div id="materialUrlOrFileContainer">
                </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description" rows="2" class="w-full rounded-lg border-1 px-4 py-2 text-sm"></textarea>
            </div>
            <div class="pt-2 flex justify-end gap-x-2">
                <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm hover:bg-indigo-500">Save Material</button>
            </div>
        </form>
    </div>
</div>

<div id="scheduleDetailsModal" class="hidden fixed inset-0 bg-gray-800/10 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Edit Schedule Details</h3>
        <form id="scheduleDetailsForm" class="mt-4 space-y-4">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">
            <div>
                <label for="details_title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="details_title" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
            </div>
            <div>
                <label for="details_start" class="block text-sm font-medium text-gray-700">Start DateTime</label>
                <input type="datetime-local" name="start_datetime" id="details_start" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
            </div>
            <div>
                <label for="details_end" class="block text-sm font-medium text-gray-700">End DateTime</label>
                <input type="datetime-local" name="end_datetime" id="details_end" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
            </div>
            <div>
                <label for="details_status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="details_status" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div>
                <label for="details_description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="details_description" rows="3" class="w-full rounded-lg border-1 px-4 py-2 text-sm"></textarea>
            </div>
            <div class="pt-2 flex justify-end gap-x-2">
                <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm hover:bg-indigo-500">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="facilitatorsModal" class="hidden fixed inset-0 bg-gray-800/10 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Manage Facilitators</h3>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Current Facilitators</label>
            <div id="currentFacilitatorsList" class="mt-2 space-y-2 max-h-40 overflow-y-auto border p-3 rounded-md bg-gray-50"></div>
        </div>
        <form id="addFacilitatorForm" class="mt-4 pt-4 border-t">
            <label for="facilitatorSelect" class="block text-sm font-medium text-gray-700">Add New Facilitator</label>
            <div class="mt-2 flex gap-x-2">
                <select name="user_id" id="facilitatorSelect" class="w-full rounded-lg border-1 px-4 py-2 text-sm"></select>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Add</button>
            </div>
        </form>
        <div class="mt-6 flex justify-end gap-x-2">
            <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancel</button>
            <button type="button" id="saveFacilitatorsBtn" class="bg-green-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm hover:bg-green-500">Save Changes</button>
        </div>
    </div>
</div>

<div id="toast" class="hidden fixed top-5 right-5 z-50"></div>

<script src="/assets/js/manage-training.js"></script>