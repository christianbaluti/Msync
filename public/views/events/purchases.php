<?php require_once dirname(__DIR__) . '/partials/header.php'; ?>
<?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

<div class="md:pl-72">
    <?php require_once dirname(__DIR__) . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">

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
            <hr><br>

            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Ticket Purchases</h1>
                    <p class="mt-2 text-sm text-gray-700">A list of all tickets purchased for this event.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
                    <a href="/events/manage?id=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" class="rounded-lg bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">&larr; Back to Event</a> 
                    <button type="button" id="bulkPurchaseBtn" class="rounded-md bg-gray-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-gray-500">Bulk Purchase</button>
                </div>
            </div>



            <div id="purchasesTableContainer" class="mt-8 flow-root"></div>
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


<div id="detailsModal" class="hidden fixed inset-0 bg-gray-600/50 z-50 overflow-y-auto">
  <div class="relative top-10 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
    <div class="flex justify-between items-center">
      <h3 class="text-xl font-bold text-gray-900" id="detailsModalTitle">Ticket Details</h3>
      <button type="button" class="close-modal text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>

    <div id="detailsModalContent" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
    </div>

    <div class="mt-6 pt-4 border-t flex justify-end gap-x-3">
       <button type="button" class="close-modal bg-white px-4 py-2 rounded-md text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancel</button>
      <button type="button" id="deleteTicketBtn" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:bg-red-500">Delete Ticket</button>
      <button type="button" id="saveTicketDetailsBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:bg-indigo-500">Save Changes</button>
    </div>
  </div>
</div>

<div id="bulkPurchaseModal" class="hidden fixed inset-0 bg-slate-900/70 z-50 flex items-center justify-center p-4">
    
    <div class="relative bg-white w-full max-w-4xl rounded-xl shadow-xl max-h-[95vh] flex flex-col border-t-4 border-[#E40000]">
        
        <div class="flex justify-between items-center p-5 border-b border-slate-200">
            <h3 class="text-xl font-semibold text-slate-800">Bulk Ticket Purchase</h3>
            <button type="button" class="close-modal text-slate-400 hover:text-slate-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto p-6">
            <div id="bulkStep1">
                <p class="text-base text-center text-slate-600">How would you like to add users for this bulk purchase?</p>
                <div class="mt-6 grid sm:grid-cols-2 gap-6">
                    <button id="bulkChooseExisting" class="text-left p-5 border border-slate-300 rounded-lg hover:border-[#EE4000] hover:bg-red-50 transition group">
                        <h4 class="font-semibold text-slate-800">From Existing Users</h4>
                        <p class="text-sm text-slate-500 mt-1">Select from a list of users already registered in the system.</p>
                    </button>
                    <button id="bulkChooseNew" class="text-left p-5 border border-slate-300 rounded-lg hover:border-[#EE4000] hover:bg-red-50 transition group">
                        <h4 class="font-semibold text-slate-800">From a CSV File</h4>
                        <p class="text-sm text-slate-500 mt-1">Create new users by uploading a pre-filled CSV file.</p>
                    </button>
                </div>
            </div>

            <div id="bulkStepExisting" class="hidden">
                <form id="bulkExistingForm">
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Filter Users</label>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <input type="text" id="bulkFilterName" placeholder="Filter by name or email..." class="md:col-span-2 w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]">
                            <select id="bulkFilterEmployed" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]">
                                <option value="">Any Employment</option>
                                <option value="1">Employed</option>
                                <option value="0">Not Employed</option>
                            </select>
                            <select id="bulkFilterCompany" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]"></select>
                        </div>
                    </div>

                    <div id="bulkUserList" class="mt-4 max-h-60 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1">
                        </div>
                    
                    <div class="mt-6 border-t border-slate-200 pt-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label for="bulkTicketTypeSelect" class="block text-sm font-medium text-slate-700 mb-1">Ticket Type</label>
                                <select name="ticket_type_id" id="bulkTicketTypeSelect" required class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]"></select>
                            </div>
                            <div>
                                <label for="bulkAttendeeTypeSelect" class="block text-sm font-medium text-slate-700 mb-1">Attendee Type</label>
                                <select name="attending_as_id" id="bulkAttendeeTypeSelect" required class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]"></select>
                            </div>
                            <div>
                                <label for="bulkTicketPrice" class="block text-sm font-medium text-slate-700 mb-1">Ticket Price (MWK)</label>
                                <input type="number" name="price" id="bulkTicketPrice" required readonly class="w-full border border-gray-300 bg-slate-100 rounded-lg text-sm px-3 py-2 outline-none transition">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="bulkAmountPaid" class="block text-sm font-medium text-slate-700 mb-1">Amount Paid (per ticket)</label>
                                <input type="number" step="0.01" name="amount_paid" id="bulkAmountPaid" value="0.00" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]">
                            </div>
                            <div>
                                <label for="bulkPaymentMethod" class="block text-sm font-medium text-slate-700 mb-1">Payment Method</label>
                                <select name="payment_method" id="bulkPaymentMethod" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]">
                                    <option value="">-- Select Option --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end items-center gap-x-3">
                        <button type="button" class="bulk-back-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Back</button>
                        <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Submit for <span id="bulkSelectedUserCount">0</span> Users</button>
                    </div>
                </form>
            </div>

            <div id="bulkStepNew" class="hidden">
                <form id="bulkCsvForm" enctype="multipart/form-data">
                    <div class="space-y-3">
                        <p class="text-sm font-medium text-slate-700">Are the new users employed?</p>
                        <div class="flex items-center gap-x-6">
                            <label class="flex items-center"><input type="radio" name="is_employed_csv" value="0" class="h-4 w-4" checked> <span class="ml-2 text-sm">No, they are not</span></label>
                            <label class="flex items-center"><input type="radio" name="is_employed_csv" value="1" class="h-4 w-4"> <span class="ml-2 text-sm">Yes, they are employed</span></label>
                        </div>
                    </div>
                    <div id="bulkCsvCompanyWrapper" class="hidden mt-4">
                        <label for="bulkCsvCompanySelect" class="block text-sm font-medium text-slate-700 mb-1">Select Company</label>
                        <select name="company_id" id="bulkCsvCompanySelect" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]"></select>
                    </div>
                    
                    <div class="mt-6 rounded-md bg-yellow-50 p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                               <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM10 13a1 1 0 110-2 1 1 0 010 2zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Instructions</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <ol class="list-decimal list-inside space-y-1">
                                        <li>Download the correct sample CSV file.</li>
                                        <li>Fill in user details without changing the columns.</li>
                                        <li>Upload the completed file. The system will reject the entire file if any user's email already exists.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-x-4">
                        <a id="downloadSampleCsvLink" href="/api/downloads/sample_unemployed.csv.php" class="text-sm font-semibold text-[#E40000] hover:underline">Download Sample CSV</a>
                        <input type="file" name="user_csv" accept=".csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-[#E40000] hover:file:bg-red-100">
                    </div>

                    <div class="mt-6 border-t border-slate-200 pt-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label for="bulkCsvTicketTypeSelect" class="block text-sm font-medium text-slate-700 mb-1">Ticket Type</label>
                                <select name="ticket_type_id" id="bulkCsvTicketTypeSelect" required class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]"></select>
                            </div>
                            <div>
                                <label for="bulkCsvAttendeeTypeSelect" class="block text-sm font-medium text-slate-700 mb-1">Attendee Type</label>
                                <select name="attending_as_id" id="bulkCsvAttendeeTypeSelect" required class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]"></select>
                            </div>
                            <div>
                                <label for="bulkCsvTicketPrice" class="block text-sm font-medium text-slate-700 mb-1">Ticket Price (MWK)</label>
                                <input type="number" name="price" id="bulkCsvTicketPrice" required readonly class="w-full border border-gray-300 bg-slate-100 rounded-lg text-sm px-3 py-2 outline-none transition">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="bulkCsvAmountPaid" class="block text-sm font-medium text-slate-700 mb-1">Amount Paid (per ticket)</label>
                                <input type="number" step="0.01" name="amount_paid" id="bulkCsvAmountPaid" value="0.00" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]">
                            </div>
                            <div>
                                <label for="bulkCsvPaymentMethod" class="block text-sm font-medium text-slate-700 mb-1">Payment Method</label>
                                <select name="payment_method" id="bulkCsvPaymentMethod" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 outline-none transition focus:ring-2 focus:ring-red-300 focus:border-[#E40000]">
                                    <option value="">-- Select Option</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end items-center gap-x-3">
                        <button type="button" class="bulk-back-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Back</button>
                        <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Upload and Process</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="hidden fixed top-5 right-5 z-50"></div>
<script src="/assets/js/event_purchases.js"></script>
<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>