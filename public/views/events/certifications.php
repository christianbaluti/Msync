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
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Certificates Designer</h1>
                    <p class="mt-2 text-sm text-gray-700">Design and send participation certificates to attendees.</p>
                </div> <br>
                <a href="/events/manage?id=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" class="rounded-lg bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">&larr; Back to Event</a> <br>
            </div>

            <div class="mt-8">
                <!-- Top Toolbar -->
                <div class="bg-white p-4 rounded-lg shadow mb-6 border border-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <!-- Section 1: Dimensions -->
                        <div class="flex items-center gap-2 border-r border-slate-200 pr-4">
                            <div class="flex flex-col">
                                <label class="text-[10px] font-bold uppercase text-slate-500">Width (cm)</label>
                                <input type="number" id="certWidth" value="29.7" step="0.1" class="w-16 h-8 text-sm border-slate-300 rounded focus:ring-indigo-500 font-medium">
                            </div>
                            <div class="flex flex-col">
                                <label class="text-[10px] font-bold uppercase text-slate-500">Height (cm)</label>
                                <input type="number" id="certHeight" value="21.0" step="0.1" class="w-16 h-8 text-sm border-slate-300 rounded focus:ring-indigo-500 font-medium">
                            </div>
                            <button id="apply-size-btn" class="self-end p-1.5 bg-slate-100 hover:bg-slate-200 rounded text-slate-600" title="Apply Size"><i class="fa-solid fa-check"></i></button>
                        </div>

                        <!-- Section 2: Add Elements -->
                        <div class="flex items-center gap-2 border-r border-slate-200 pr-4">
                             <div class="flex gap-1">
                                <button id="add-text-btn" class="flex flex-col items-center justify-center w-12 h-12 rounded hover:bg-slate-50 border border-transparent hover:border-slate-200 transition" title="Add Text">
                                    <span class="text-lg font-serif font-bold text-slate-700">T</span>
                                    <span class="text-[10px] text-slate-500">Text</span>
                                </button>
                                
                                <button id="trigger-image-upload" class="flex flex-col items-center justify-center w-12 h-12 rounded hover:bg-slate-50 border border-transparent hover:border-slate-200 transition" title="Add Image">
                                    <i class="fa-regular fa-image text-lg text-slate-700"></i>
                                    <span class="text-[10px] text-slate-500">Image</span>
                                </button>
                                <input type="file" id="image-upload-input" class="hidden" accept="image/*">
                             </div>
                             
                             <!-- Shapes -->
                             <div class="flex flex-col justify-center gap-1">
                                <div class="flex gap-1">
                                    <button class="add-shape-btn w-6 h-6 flex items-center justify-center bg-slate-50 border rounded hover:bg-slate-100" data-type="rect" title="Rectangle"><div class="w-3 h-2 bg-slate-600"></div></button>
                                    <button class="add-shape-btn w-6 h-6 flex items-center justify-center bg-slate-50 border rounded hover:bg-slate-100" data-type="circle" title="Circle"><div class="w-3 h-3 rounded-full bg-slate-600"></div></button>
                                </div>
                                <div class="flex gap-1">
                                    <button class="add-shape-btn w-6 h-6 flex items-center justify-center bg-slate-50 border rounded hover:bg-slate-100" data-type="triangle" title="Triangle"><div class="w-0 h-0 border-l-[4px] border-l-transparent border-r-[4px] border-r-transparent border-b-[6px] border-b-slate-600"></div></button>
                                    <button class="add-shape-btn w-6 h-6 flex items-center justify-center bg-slate-50 border rounded hover:bg-slate-100" data-type="line" title="Line"><div class="w-3 h-0.5 bg-slate-600"></div></button>
                                </div>
                             </div>
                        </div>
                        
                        <!-- Section 3: Placeholders -->
                        <div class="flex items-center gap-2 border-r border-slate-200 pr-4">
                             <div class="flex flex-col w-40">
                                <label class="text-[10px] font-bold uppercase text-slate-500">Insert Placeholder</label>
                                <div class="flex gap-1">
                                    <select id="placeholder-select" class="block w-full h-8 text-xs border-slate-300 rounded focus:ring-indigo-500">
                                        <option value="{{user_name}}">Full Name</option>
                                        <option value="{{company_name}}">Company</option>
                                        <option value="{{ticket_code}}">Ticket Code</option>
                                        <option value="{{date}}">Date</option>
                                    </select>
                                    <button id="add-placeholder-btn" class="px-2 h-8 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-xs font-bold">+</button>
                                </div>
                             </div>
                        </div>

                        <!-- Section 4: Actions -->
                        <div class="flex items-center gap-2 ml-auto">
                             <button id="zoom-out-btn" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600" title="Zoom Out"><i class="fa-solid fa-minus"></i></button>
                             <span id="zoom-level" class="text-xs font-mono text-slate-500 w-8 text-center">100%</span>
                             <button id="zoom-in-btn" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600" title="Zoom In"><i class="fa-solid fa-plus"></i></button>
                             <button id="zoom-fit-btn" class="ml-1 px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded text-xs text-slate-600 font-medium">Fit</button>
                             
                             <div class="h-8 w-px bg-slate-200 mx-2"></div>

                             <button id="save-design-btn" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 rounded text-sm hover:bg-slate-50 shadow-sm">Save</button>
                             <button id="load-design-btn" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 rounded text-sm hover:bg-slate-50 shadow-sm">Load</button>
                             <button id="clear-canvas-btn" class="px-3 py-1.5 bg-white border border-red-200 text-red-600 rounded text-sm hover:bg-red-50 hover:border-red-300 shadow-sm">Clear</button>
                        </div>
                    </div>
                </div>

                <!-- Main Workspace Grid -->
                <div class="grid grid-cols-12 gap-6 h-[750px]"> <!-- Fixed height for workspace feel -->
                    
                    <!-- Canvas Area -->
                    <div class="col-span-12 lg:col-span-9 bg-slate-100 border border-slate-200 rounded-lg shadow-inner overflow-hidden relative flex items-center justify-center">
                         <div id="canvas-container-outer" class="max-w-full max-h-full overflow-auto p-8 flex items-center justify-center">
                             <div id="canvas-wrapper" class="bg-white shadow-[0_0_20px_rgba(0,0,0,0.1)]">
                                 <canvas id="certCanvas"></canvas>
                             </div>
                         </div>
                    </div>

                    <!-- Right Sidebar: Properties & Layers -->
                    <div class="col-span-12 lg:col-span-3 flex flex-col gap-6 h-full overflow-hidden">
                        
                        <!-- Properties Panel -->
                        <div class="bg-white border border-slate-200 rounded-lg shadow-sm flex-shrink-0">
                            <div class="bg-slate-50 px-4 py-2 border-b border-slate-200">
                                <h3 class="font-semibold text-slate-800 text-sm">Properties</h3>
                            </div>
                            <div class="p-4">
                                <div id="properties-panel" class="text-sm text-slate-500 italic">
                                    Select an element to edit properties.
                                </div>
                                <div id="object-controls" class="hidden space-y-4">
                                     <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fill Color</label>
                                        <div class="flex gap-2 items-center">
                                            <input type="color" id="obj-color" class="w-8 h-8 p-0 border border-slate-300 rounded cursor-pointer">
                                            <span class="text-xs text-slate-400 font-mono" id="obj-color-hex">#000000</span>
                                        </div>
                                     </div>
                                     
                                     <div id="prop-text-group">
                                         <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Font Config</label>
                                         <div class="flex gap-2 mb-2">
                                             <input type="number" id="obj-fontsize" class="w-16 text-sm border-slate-300 rounded" title="Size">
                                             <select id="obj-fontfamily" class="flex-grow text-sm border-slate-300 rounded">
                                                <option value="Arial">Arial</option>
                                                <option value="Times New Roman">Times New Roman</option>
                                                <option value="Courier New">Courier New</option>
                                                <option value="Helvetica">Helvetica</option>
                                                <option value="Georgia">Georgia</option>
                                                <option value="Verdana">Verdana</option>
                                             </select>
                                         </div>
                                     </div>
                                     
                                     <div>
                                         <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Opacity</label>
                                         <input type="range" id="obj-opacity" min="0" max="1" step="0.1" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer">
                                     </div>

                                     <div class="pt-2 border-t border-slate-100">
                                        <button id="delete-obj-btn" class="w-full py-1.5 px-3 bg-red-50 text-red-600 rounded text-sm hover:bg-red-100 font-medium transition flex items-center justify-center gap-2">
                                            <i class="fa-solid fa-trash-can"></i> Delete Element
                                        </button>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <!-- Layers List -->
                        <div class="bg-white border border-slate-200 rounded-lg shadow-sm flex-grow flex flex-col min-h-0">
                            <div class="bg-slate-50 px-4 py-2 border-b border-slate-200">
                                <h3 class="font-semibold text-slate-800 text-sm">Layers</h3>
                            </div>
                            <div id="layers-list" class="flex-grow overflow-y-auto p-2 space-y-1 bg-white">
                                 <!-- JS populates -->
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Attendee Selection (Bottom) -->
                <div class="mt-6 bg-white border border-slate-200 rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                        <h3 class="text-lg font-bold text-slate-800">Select Attendees</h3>
                        
                        <div class="flex flex-wrap items-center gap-4">
                             <div class="relative">
                                 <input type="search" id="attendee-search" class="pl-8 pr-3 py-1.5 text-sm border-slate-300 rounded-md focus:ring-indigo-500 w-64" placeholder="Search name, email...">
                                 <i class="fa-solid fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                             </div>
                             
                             <div class="h-4 w-px bg-slate-300"></div>

                             <label class="inline-flex items-center text-sm font-medium text-slate-700">
                                <input type="checkbox" id="master-checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-2">
                                Select Visible
                             </label>

                             <button id="send-certs-btn" class="ml-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition shadow-[0_4px_14px_0_rgba(79,70,229,0.3)]">
                                <i class="fa-solid fa-paper-plane mr-2"></i> Send Certificates (<span id="selected-count">0</span>)
                             </button>
                        </div>
                    </div>
                    
                    <div id="attendee-list-container" class="max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50 sticky top-0 z-10">
                                <tr>
                                    <th scope="col" class="w-12 py-3.5 pl-4 pr-3 text-left"></th>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase text-slate-500">Attendee</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase text-slate-500">Email</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase text-slate-500">Company</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                                </tr>
                            </thead>
                            <tbody id="attendee-table-body" class="divide-y divide-slate-200 bg-white">
                                <!-- Loads here -->
                            </tbody>
                        </table>
                        <div id="loading-attendees" class="p-8 text-center text-slate-500">
                            <i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Loading attendees...
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<!-- Sending Progress Modal -->
<div id="sendingModal" class="hidden fixed inset-0 bg-slate-600/50 z-50 overflow-y-auto">
    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900">Sending Certificates</h3>
        <p class="mt-2 text-sm text-slate-600">Please wait while certificates are generated and emailed.</p>
        
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div id="progress-bar" class="bg-indigo-600 h-2.5 rounded-full" style="width: 0%"></div>
            </div>
            <p id="progress-text" class="text-xs text-right mt-1 text-gray-500">0%</p>
        </div>

        <div id="sending-log" class="mt-4 h-32 overflow-y-auto bg-gray-50 p-2 rounded text-xs text-gray-600 font-mono border">
            
        </div>

        <div class="mt-6 flex justify-end">
            <button type="button" id="cancel-sending-btn" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel/Close</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="hidden fixed top-5 right-5 z-[101] bg-slate-800 text-white py-2 px-4 rounded-lg shadow-md transition-opacity duration-300"></div>

<!-- Generic Confirmation Modal -->
<div id="genericConfirmModal" class="hidden fixed inset-0 bg-slate-600/50 z-[100] overflow-y-auto backdrop-blur-sm">
    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-xl rounded-xl bg-white animate-fade-in-down">
        <h3 id="confirmModalTitle" class="text-lg font-semibold text-slate-900">Confirm Action</h3>
        <p id="confirmModalMessage" class="mt-2 text-sm text-slate-600">Are you sure you want to proceed?</p>
        <div class="mt-6 flex justify-end gap-x-3">
            <button type="button" class="close-modal rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
            <button type="button" id="confirmModalConfirmBtn" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Confirm</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script src="/assets/js/cert_designer.js"></script>
<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
