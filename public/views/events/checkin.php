<?php
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/partials/sidebar.php';

// It's crucial to have the event ID for all operations
$event_id = htmlspecialchars($_GET['id'] ?? '0');
?>

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
                    <div class="h-10 bg-slate-200 rounded-md"></div>
                </div>
            </div>
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Desk Check-in</h1>
                    <p class="mt-2 text-sm text-gray-700">Search for attendees below to check them in and distribute merchandise.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
                    <button type="button" id="manage-merch-btn" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Manage Merchandise</button>
                    <a href="/events/manage?id=<?php echo $event_id; ?>" class="rounded-lg bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">&larr; Back to Event</a>
                </div>
            </div>

            <div class="mt-2 bg-white p-6 rounded-lg shadow">
                <div class="w-full max-w-lg mx-auto">
                    <label for="search-attendee" class="block text-sm font-semibold text-gray-800">Search Attendee</label>
                    <div class="relative mt-2">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search-attendee" id="search-attendee" placeholder="Search by name, email, or ticket code..." class="block w-full rounded-2xl border border-gray-200 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 placeholder:text-gray-400" />
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Start typing to see results automatically.</p>
                </div>
            </div>

            <div class="mt-8 flow-root">
                <div id="attendee-results-container" class="space-y-4">
                    <div class="text-center py-10 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" /></svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Search for attendees</h3>
                        <p class="mt-1 text-sm text-gray-500">Use the search box to find an attendee.</p>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<div id="merch-modal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg leading-6 font-semibold text-slate-900" id="modalTitle">Manage Merchandise</h3>
        <form id="merch-form" class="mt-4 space-y-4" novalidate>
            <input type="hidden" id="merch-id" name="id" value="">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div>
                    <label for="merch-name" class="block text-sm font-medium text-slate-700">Item Name</label>
                    <input type="text" id="merch-name" name="name" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label for="merch-quantity" class="block text-sm font-medium text-slate-700">Total Quantity (Optional)</label>
                    <input type="number" id="merch-quantity" name="total_quantity" placeholder="e.g., 100" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400">
                </div>
            </div>
            <div>
                <label for="merch-description" class="block text-sm font-medium text-slate-700">Description</label>
                <textarea id="merch-description" name="description" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400" placeholder="A short description of the merchandise item..."></textarea>
            </div>
            <div class="pt-4 flex justify-end space-x-3 border-t border-slate-200">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Item</button>
            </div>
        </form>
        <div class="mt-6 pt-4 border-t border-slate-200">
            <h4 class="text-md font-semibold text-gray-800 mb-2">Existing Items</h4>
            <div id="merch-list-container" class="max-h-64 overflow-y-auto pr-2"></div>
        </div>
    </div>
</div>

<script src="../assets/js/checkin.js"></script>
<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>