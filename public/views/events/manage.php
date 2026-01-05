<?php require_once dirname(__DIR__) . '/partials/header.php'; ?>
<?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

<div class="md:pl-72">
    <?php require_once dirname(__DIR__) . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8 space-y-8">
            <div id="eventHeader">
                <div class="space-y-4 animate-pulse">
                    <div class="h-8 w-3/4 bg-slate-200 rounded-md"></div>
                    <div class="flex space-x-6">
                        <div class="h-4 w-1/4 bg-slate-200 rounded-md"></div>
                        <div class="h-4 w-1/4 bg-slate-200 rounded-md"></div>
                        <div class="h-4 w-1/3 bg-slate-200 rounded-md"></div>
                    </div>
                    <div class="h-16 bg-slate-200 rounded-md"></div>
                </div>
            </div>

<div class="flex flex-wrap items-center gap-3">
                <button type="button" id="addScheduleBtn" class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors text-sm font-medium text-slate-700">
                    <svg class="w-4 h-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18M-4.5 12h22.5" /></svg>
                    Add Schedule
                </button>
                <button type="button" id="manageTicketTypesBtn" class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors text-sm font-medium text-slate-700">
                    <svg class="w-4 h-4 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12V6a2.25 2.25 0 012.25-2.25h5.25A2.25 2.25 0 0118 6v12a2.25 2.25 0 01-2.25 2.25H9A2.25 2.25 0 016.75 18V6a2.25 2.25 0 012.25-2.25h5.25A2.25 2.25 0 0118 6" /></svg>
                    Ticket Types
                </button>
                <button type="button" id="manageAttendeeTypesBtn" class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors text-sm font-medium text-slate-700">
                    <svg class="w-4 h-4 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.952a4.5 4.5 0 011.08-2.032M7.86 8.25a6.75 6.75 0 0110.14-1.332 6.75 6.75 0 01-10.14 1.332zM3.86 19.094A9.094 9.094 0 017.6 18.615m0 0a3 3 0 014.682-2.72m-4.682 2.72a3 3 0 004.682 2.72" /></svg>
                    Attendee Types
                </button>
                <button type="button" id="manageStreamBtn" class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors text-sm font-medium text-slate-700">
                    <svg class="w-4 h-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 15.362a9 9 0 11-5.724 0M12 12.75v-3.75m0 3.75a3 3 0 01-3 3H9m3-3a3 3 0 003 3h.362m-3.362 0a3 3 0 013 3V15m-3-3V9m3 3h.362m-3.362 0a3 3 0 013 3v1.5m0-9l-2.25 2.25m0 0l-2.25 2.25M12 9l2.25 2.25M12 9l-2.25 2.25" /></svg>
                    Streaming
                </button>
                <a href="/events/checkin?id=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors text-sm font-medium text-slate-700">
                    <svg class="w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Desk Check-in
                </a>
                <a href="/events/nametags?id=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors text-sm font-medium text-slate-700">
                    <svg class="w-4 h-4 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                    Name Tags
                </a>
                <a href="/events/purchases?id=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors text-sm font-medium text-slate-700">
                     <svg class="w-4 h-4 text-rose-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.75A.75.75 0 013 4.5h.75m0 0h.75A.75.75 0 015.25 6v.75m0 0v-.75A.75.75 0 014.5 5.25h.75M5.25 6h.75m-1.5 0h.75M3 12h18M3 15h18" /></svg>
                    Purchases
                </a>
                <a href="/events/certifications?id=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors text-sm font-medium text-slate-700">
                    <svg class="w-4 h-4 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
  <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
</svg>
                    Certifications
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <h2 class="text-xl font-bold text-slate-900">Event Schedule</h2>
                <p class="mt-1 text-sm text-slate-500">A timeline of all activities planned for the event.</p>
                <div id="schedulesContainer" class="mt-4 overflow-x-auto">
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

<div id="toast" class="hidden fixed top-5 right-5 z-[101] bg-slate-800 text-white py-2 px-4 rounded-lg shadow-md"></div>

<style>
    .form-input {
        @apply mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none;
    }
    .form-label {
        @apply block text-sm font-medium text-slate-700;
    }
</style>

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

<div id="scheduleModal" class="hidden fixed inset-0 bg-slate-600/50 z-50 overflow-y-auto">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start">
            <h3 id="scheduleModalTitle" class="text-lg font-semibold text-slate-900">Add Schedule Item</h3>
            <button type="button" class="close-modal text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form id="scheduleForm" class="mt-4 space-y-5">
            <input type="hidden" name="id">
            <input type="hidden" name="event_id">

            <div>
                <label for="scheduleTitle" class="block text-sm font-medium text-slate-700">Title</label>
                <input type="text" id="scheduleTitle" name="title" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            
            <div>
                <label for="scheduleType" class="block text-sm font-medium text-slate-700">Type</label>
                <select id="scheduleType" name="type" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    <option value="general">General</option>
                    <option value="voting">Voting</option>
                    <option value="training">Training</option>
                    <option value="meal">Meal</option>
                    <option value="awards">Awards</option>
                </select>
            </div>
            
            <div>
                <label for="scheduleDescription" class="block text-sm font-medium text-slate-700">Description</label>
                <textarea id="scheduleDescription" name="description" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="scheduleStart" class="block text-sm font-medium text-slate-700">Start Time</label>
                    <input type="datetime-local" id="scheduleStart" name="start_datetime" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                <div>
                    <label for="scheduleEnd" class="block text-sm font-medium text-slate-700">End Time</label>
                    <input type="datetime-local" id="scheduleEnd" name="end_datetime" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
            </div>
            
            <div>
                <label for="facilitatorSearch" class="block text-sm font-medium text-slate-700">Search Facilitators</label>
                <input type="text" id="facilitatorSearch" placeholder="Type to filter..." class="mt-1 mb-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                
                <label for="facilitatorSelect" class="block text-sm font-medium text-slate-700 sr-only">Facilitators</label>
                <select name="facilitators[]" id="facilitatorSelect" multiple class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none h-32"></select>
                <p class="text-xs text-slate-500 mt-1">Hold Ctrl/Cmd to select multiple. Results are filtered by your search above.</p>
            </div>

            <div>
                <label for="scheduleStatus" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="scheduleStatus" name="status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="pt-4 flex justify-end gap-x-3 border-t">
                <button type="button" class="close-modal rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<div id="ticketTypesManageModal" class="hidden fixed inset-0 bg-slate-600/50 z-50 overflow-y-auto">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start">
            <h3 class="text-xl font-bold text-slate-900">Manage Ticket Types</h3>
            <button type="button" class="close-modal text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        
        <div class="mt-4" id="ticketTypesList"></div>
        
        <div class="mt-6 border-t pt-6 flex justify-end">
            <button type="button" id="addNewTicketTypeBtn" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Add New Ticket Type
            </button>
        </div>
    </div>
</div>

<div id="ticketTypeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-800/50 backdrop-blur-sm">
  <div class="relative w-full max-w-2xl mx-4 md:mx-auto bg-white rounded-2xl shadow-2xl border border-slate-200">
    <div class="flex items-start justify-between p-6 border-b border-slate-200">
      <h3 id="ticketTypeModalTitle" class="text-lg font-semibold text-slate-900">Add Ticket Type</h3>
      <button type="button" class="close-modal text-2xl text-slate-400 hover:text-slate-600 transition-colors">&times;</button>
    </div>

    <form id="ticketTypeForm" class="p-6 space-y-6">
      <input type="hidden" name="id">
      <input type="hidden" name="event_id">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex flex-col">
          <label for="ticketName" class="text-sm font-medium text-slate-700 mb-1">Ticket Name</label>
          <input type="text" id="ticketName" name="name" required class="rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 text-sm px-3 py-2 outline-none transition">
        </div>

        <div class="flex flex-col">
          <label for="ticketPrice" class="text-sm font-medium text-slate-700 mb-1">Price (K)</label>
          <input type="number" id="ticketPrice" step="0.01" name="price" required class="rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 text-sm px-3 py-2 outline-none transition">
        </div>
      </div>

      <div class="flex flex-col">
        <label for="ticketMemberType" class="text-sm font-medium text-slate-700 mb-1">For Membership</label>
        <select id="ticketMemberType" name="member_type_id" class="rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 text-sm px-3 py-2 outline-none transition">
          <option value="0">General (Everyone)</option>
        </select>
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
        <button type="button" class="close-modal rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-300 hover:bg-slate-50 transition">Cancel</button>
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">Save Ticket Type</button>
      </div>
    </form>
  </div>
</div>


<div id="attendeeTypesManageModal" class="hidden fixed inset-0 bg-slate-600/50 z-50 overflow-y-auto">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-slate-900">Manage Attendee Types</h3>
                <p class="text-sm text-slate-500 mt-1">These types are global. Attendee counts are specific to this event.</p>
            </div>
            <button type="button" class="close-modal text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        
        <div class="mt-4" id="attendeeTypesList"></div>
        
        <form id="addAttendeeTypeForm" class="mt-6 border-t pt-6">
            <h4 class="text-lg font-semibold text-slate-800 mb-4">Add New Global Attendee Type</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="md:col-span-1">
                    <label for="newAttendeeName" class="block text-sm font-medium text-slate-700">Type Name</label>
                    <input type="text" id="newAttendeeName" name="name" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                
                <div class="md:col-span-2">
                    <label for="newAttendeeDescription" class="block text-sm font-medium text-slate-700">Description</label>
                    <input type="text" id="newAttendeeDescription" name="description" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
            </div>
            
            <div class="mt-5 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Add Attendee Type</button>
            </div>
        </form>
    </div>
</div>

<!-- Stream Config Modal (Compact Version) -->
<div id="streamManageModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm">
  <div class="flex min-h-full items-center justify-center p-2">
    <div class="w-full max-w-xl rounded-lg bg-white shadow-lg border border-slate-200 animate-fade-in">
      
      <!-- Header -->
      <div class="flex items-start justify-between px-4 py-3 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-900">Configure YouTube Streaming</h3>
        <button type="button"
          class="close-modal text-xl text-slate-400 hover:text-slate-600 transition-colors">&times;</button>
      </div>

      <!-- Form -->
      <form id="streamForm" class="px-4 py-4 space-y-4">
        <input type="hidden" name="id">
        <input type="hidden" name="event_id">

        <div>
          <label for="youtube_video_id" class="block text-sm font-medium text-slate-700 mb-1">YouTube Video ID</label>
          <input type="text" id="youtube_video_id" name="youtube_video_id"
            placeholder="e.g., dQw4w9WgXcQ"
            class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
          <p class="mt-1 text-xs text-slate-500">Part of the URL after “watch?v=”.</p>
        </div>

        <div>
          <label for="youtube_embed_url" class="block text-sm font-medium text-slate-700 mb-1">YouTube Embed URL</label>
          <input type="text" id="youtube_embed_url" name="youtube_embed_url"
            placeholder="https://www.youtube.com/embed/dQw4w9WgXcQ"
            class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
          <p class="mt-1 text-xs text-slate-500">Optional. If blank, generated from ID.</p>
        </div>

        <div>
          <label for="stream_key" class="block text-sm font-medium text-slate-700 mb-1">Stream Key</label>
          <input type="text" id="stream_key" name="stream_key"
            placeholder="e.g., abcd-1234-efgh-5678"
            class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label for="privacy_status" class="block text-sm font-medium text-slate-700 mb-1">Privacy Status</label>
            <select id="privacy_status" name="privacy_status"
              class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
              <option value="unlisted">Unlisted</option>
              <option value="public">Public</option>
              <option value="private">Private</option>
            </select>
          </div>

          <div>
            <label for="is_live" class="block text-sm font-medium text-slate-700 mb-1">Stream Status</label>
            <select id="is_live" name="is_live"
              class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
              <option value="0">Offline</option>
              <option value="1">Live</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label for="started_at" class="block text-sm font-medium text-slate-700 mb-1">Start Time (Optional)</label>
            <input type="datetime-local" id="started_at" name="started_at"
              class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
          </div>

          <div>
            <label for="ended_at" class="block text-sm font-medium text-slate-700 mb-1">End Time (Optional)</label>
            <input type="datetime-local" id="ended_at" name="ended_at"
              class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
          </div>
        </div>
      </form>

      <!-- Footer -->
      <div class="flex justify-between items-center px-4 py-3 border-t border-slate-200">
        <button type="button" id="deleteStreamBtn"
          class="hidden rounded-md bg-red-600 px-3 py-1 text-sm font-medium text-white shadow hover:bg-red-500 transition">
          Remove
        </button>
        <div class="flex gap-2 ml-auto">
          <button type="button"
            class="close-modal rounded-md bg-white px-3 py-1 text-sm font-medium text-slate-900 shadow ring-1 ring-slate-300 hover:bg-slate-50 transition">
            Cancel
          </button>
          <button type="submit" form="streamForm"
            class="rounded-md bg-indigo-600 px-3 py-1 text-sm font-medium text-white shadow hover:bg-indigo-500 transition">
            Save
          </button>
        </div>
      </div>
    </div>
  </div>
</div>


<script src="/assets/js/event_manage.js"></script>
<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>