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
            </div>
            
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Nametag Designer</h1>
                    <p class="mt-2 text-sm text-gray-700">Design and generate printable nametags for event attendees.</p>
                </div> <br>
                <a href="/events/manage?id=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" class="rounded-lg bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">&larr; Back to Event</a> <br>
            </div>

           

            <div class="mt-8">
                <div class="grid grid-cols-12 gap-6">

                    <div class="col-span-12 md:col-span-3 bg-white p-4 rounded-lg shadow space-y-6">
                        <div>
                            <h3 class="font-semibold text-gray-800 border-b pb-2">1. Nametag Setup</h3>
                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <label for="tagWidth" class="block text-sm font-medium text-gray-700">Width (cm)</label>
                                    <input type="number" id="tagWidth" value="7" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                </div>
                                <div>
                                    <label for="tagHeight" class="block text-sm font-medium text-gray-700">Height (cm)</label>
                                    <input type="number" id="tagHeight" value="13" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 border-b pb-2">2. Add Elements</h3>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <button id="add-text-btn" class="p-2 bg-gray-200 text-gray-800 rounded-md text-sm hover:bg-gray-300">Add Text</button>
                                <button id="add-image-btn" class="p-2 bg-gray-200 text-gray-800 rounded-md text-sm hover:bg-gray-300">Add Image</button>
                                <button id="add-qr-code-btn" class="p-2 bg-gray-200 text-gray-800 rounded-md text-sm hover:bg-gray-300">Add QR Code</button>
                                <button id="add-shape-btn" class="p-2 bg-gray-200 text-gray-800 rounded-md text-sm hover:bg-gray-300">Add Shape</button>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 border-b pb-2">3. Add Placeholders</h3>
                            <div class="mt-4 flex gap-2">
                                <select id="placeholder-select" class="flex-grow block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="{{user_name}}">Full Name</option>
                                    <option value="{{company_name}}">Company Name</option>
                                    <option value="{{ticket_code}}">Ticket Code</option>
                                    <option value="{{attendee_type}}">Attendee Type</option>
                                </select>
                                <button id="add-placeholder-btn" class="p-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6 bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center border-b">
                            <button id="front-tab-btn" class="px-4 py-2 text-sm font-semibold border-b-2 border-indigo-500 text-indigo-600">Front Design</button>
                            <button id="back-tab-btn" class="px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-700">Back Design</button>
                        </div>
                        <div class="mt-4 flex items-center justify-center bg-gray-100 p-4 rounded-md">
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
                        <div id="properties-panel" class="mt-4 text-sm text-gray-500">
                            Select an object on the canvas to see its properties.
                        </div>
                         <h3 class="font-semibold text-gray-800 border-b pb-2 mt-6">Layers</h3>
                         <div id="layers-panel" class="mt-4 text-sm text-gray-500">
                            Object layers will appear here.
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-white p-4 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-800">4. Select Attendees</h3>
                    
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label for="attendee-search" class="block text-sm font-medium text-gray-700">Search by Name or Company</label>
                            <input type="search" id="attendee-search" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Search...">
                        </div>
                        <div>
                            <label for="attendee-type-filter" class="block text-sm font-medium text-gray-700">Filter by Type</label>
                            <select id="attendee-type-filter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Types</option>
                                </select>
                        </div>
                    </div>
                    
                    <div id="attendee-list-container" class="mt-4 max-h-80 overflow-y-auto border-t border-gray-200">
                        <p class="text-gray-500 p-4">Attendee list will be loaded here...</p>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button id="generate-pdf-btn" class="rounded-md bg-green-600 px-6 py-3 text-center text-base font-semibold text-white shadow-sm hover:bg-green-700">
                        Generate PDF for Selected Attendees
                    </button>
                </div>

            </div>
        </div>
    </main>
</div>

<?php 
    require_once dirname(__DIR__) . '/partials/footer.php'; 
    require_once dirname(__DIR__) . '/partials/fabric.php';
?>