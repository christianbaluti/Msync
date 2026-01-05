<?php require_once dirname(__DIR__) . '/partials/header.php'; ?>
<?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

<div class="md:pl-72">
    <?php require_once dirname(__DIR__) . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Membership ID Designer</h1>
                    <p class="mt-2 text-sm text-gray-700">Design and generate printable membership cards.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
                    <button id="save-design-btn" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save Design</button>
                    <button id="load-design-btn" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Load Design</button>
                    <a href="/memberships" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">&larr; Back</a>
                </div>
            </div>

            <div class="mt-8">
                <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-12 md:col-span-3 bg-white p-4 rounded-lg shadow space-y-6">
                        <div>
                            <h3 class="font-semibold text-gray-800 border-b pb-2">1. Card Setup</h3>
                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <label for="cardWidth" class="block text-sm font-medium text-gray-700">Width (cm)</label>
                                    <input type="number" id="cardWidth" value="8.56" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="cardHeight" class="block text-sm font-medium text-gray-700">Height (cm)</label>
                                    <input type="number" id="cardHeight" value="5.398" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 border-b pb-2">2. Add Elements</h3>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <button id="add-text-btn" class="p-2 bg-gray-200 text-gray-800 rounded-md text-sm hover:bg-gray-300">Add Text</button>
                                <button id="add-image-btn" class="p-2 bg-gray-200 text-gray-800 rounded-md text-sm hover:bg-gray-300">Add Image</button>
                                <button id="add-qr-code-btn" class="p-2 bg-gray-200 text-gray-800 rounded-md text-sm hover:bg-gray-300">Add QR</button>
                                <button id="add-shape-btn" class="p-2 bg-gray-200 text-gray-800 rounded-md text-sm hover:bg-gray-300">Add Shape</button>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 border-b pb-2">3. Add Placeholders</h3>
                            <div class="mt-4 flex gap-2">
                                <select id="placeholder-select" class="flex-grow block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="{{member_name}}">Member Name</option>
                                    <option value="{{member_id}}">Membership ID</option>
                                    <option value="{{membership_type}}">Membership Type</option>
                                    <option value="{{expiry_date}}">Expiry Date</option>
                                    <option value="{{company_name}}">Company Name</option>
                                </select>
                                <button id="add-placeholder-btn" class="p-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6 bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center border-b">
                            <button id="front-tab-btn" class="px-4 py-2 text-sm font-semibold border-b-2">Front Design</button>
                            <button id="back-tab-btn" class="px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-700">Back Design</button>
                        </div>
                        <div class="mt-4 flex items-center justify-center bg-gray-100 p-4 rounded-md min-h-[400px]">
                            <div id="front-canvas-wrapper">
                                <canvas id="frontCanvas" class="border-2 border-dashed border-gray-400"></canvas>
                            </div>
                             <div id="back-canvas-wrapper" class="hidden">
                                <canvas id="backCanvas" class="border-2 border-dashed border-gray-400"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-3 bg-white p-4 rounded-lg shadow">
                        <h3 class="font-semibold text-gray-800 border-b pb-2">Properties</h3>
                        <div id="properties-panel" class="mt-4 text-sm text-gray-500 space-y-2">
                            Select an object on the canvas to see its properties.
                        </div>
                         <h3 class="font-semibold text-gray-800 border-b pb-2 mt-6">Layers</h3>
                         <div id="layers-panel" class="mt-4 text-sm text-gray-500 space-y-1">
                            Object layers will appear here.
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-white p-4 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-800">4. Select Members</h3>
                    <div class="mt-2">
                        <input type="search" id="member-search-input" placeholder="Search members by name, ID, or company..." class="block w-full sm:w-1/2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div id="member-list-container" class="mt-4 max-h-80 overflow-y-auto border rounded-md">
                        <p class="text-gray-500 text-center py-4">Loading members...</p>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button id="generate-pdf-btn" class="rounded-md bg-green-600 px-6 py-3 text-center text-base font-semibold text-white shadow-sm hover:bg-green-700 disabled:opacity-50">
                        Generate PDF for Selected Members
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="load-design-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Load a Saved Design</h3>
            <div id="design-list-container" class="mt-2 px-7 py-3 max-h-60 overflow-y-auto text-left border rounded-md">
                <p class="text-gray-400">Loading...</p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="close-modal-btn" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-600">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<?php 
    require_once dirname(__DIR__) . '/partials/footer.php'; 
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
<script src="/assets/js/id_designer.js"></script>