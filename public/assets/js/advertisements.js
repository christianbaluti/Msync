// /assets/js/advertisements.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. STATE MANAGEMENT ---
    const appState = {
        activeTab: 'campaigns',
        placementsCache: [] // To store ad_pages
    };

    let adCurrentPage = 1;
    let adCurrentFilters = { status: '', search: '' };
    const adRecordsPerPage = 10;
    
    // API endpoints
    const API = {
        ads: {
            read: '/api/ads/read.php',
            create: '/api/ads/create.php',
            read_single: '/api/ads/read_single.php',
            update: '/api/ads/update.php',
            delete: '/api/ads/delete.php',
        },
        placements: {
            read: '/api/ads/read_pages.php',
            create: '/api/ads/create_page.php',
            update: '/api/ads/update_page.php',
            delete: '/api/ads/delete_page.php',
        }
    };

    // --- 2. DOM ELEMENTS ---
    // Tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    // Ad Campaigns Tab
    const adTableContainer = document.getElementById('adTableContainer');
    const adPaginationContainer = document.getElementById('adPaginationContainer');
    const adStatusFilter = document.getElementById('adStatusFilter');
    const adSearchInput = document.getElementById('adSearchInput');
    const addAdBtn = document.getElementById('addAdBtn');
    const adModal = document.getElementById('adModal');
    const adForm = document.getElementById('adForm');
    const adModalTitle = document.getElementById('adModalTitle');
    const currentMediaPreview = document.getElementById('current-media-preview');
    const placementsContainer = document.getElementById('placements-container');

    // App Placements Tab
    const placementTableContainer = document.getElementById('placementTableContainer');
    const addPlacementBtn = document.getElementById('addPlacementBtn');
    const placementModal = document.getElementById('placementModal');
    const placementForm = document.getElementById('placementForm');
    const placementModalTitle = document.getElementById('placementModalTitle');

    // --- 3. UI ELEMENTS ---
    const spinner = document.getElementById('loading-spinner');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    // --- 4. HELPER FUNCTIONS ---
    const showSpinner = () => spinner.classList.remove('hidden');
    const hideSpinner = () => spinner.classList.add('hidden');

    const showToast = (message, isError = false) => {
        toastMessage.textContent = message;
        toast.className = `fixed bottom-5 right-5 text-white py-2 px-4 rounded-lg shadow-md z-50 ${isError ? 'bg-red-600' : 'bg-green-700'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    };

    // Generic API call function
    const apiCall = async (url, options = {}, showSpinnerFlag = true) => {
        if (showSpinnerFlag) showSpinner();
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                let errorMsg = `HTTP error! status: ${response.status}`;
                try {
                    const errData = await response.json();
                    errorMsg = errData.message || errorMsg;
                } catch (e) {}
                throw new Error(errorMsg);
            }
            // Check for empty JSON response
            const text = await response.text();
            if (!text) return { success: true, message: 'Operation successful.' }; 
            
            const result = JSON.parse(text);
            if (result.success === false) throw new Error(result.message);
            return result;
        } catch (error) {
            console.error('API Call Error:', error);
            showToast(error.message || 'A network error occurred.', true);
            return { success: false, message: error.message };
        } finally {
            if (showSpinnerFlag) hideSpinner();
        }
    };
    
    // Re-usable pagination renderer
    const renderPagination = (container, total, limit, currentPage, pageChangeCallback) => {
        container.innerHTML = '';
        if (!total || total <= 0 || !limit || limit <= 0) return;
        const totalPages = Math.ceil(total / limit);
        if (totalPages <= 1) return;

        const maxVisiblePages = 5;
        let startPage, endPage;
        if (totalPages <= maxVisiblePages) {
            startPage = 1;
            endPage = totalPages;
        } else {
            const maxPagesBeforeCurrent = Math.floor(maxVisiblePages / 2);
            const maxPagesAfterCurrent = Math.ceil(maxVisiblePages / 2) - 1;
            if (currentPage <= maxPagesBeforeCurrent) {
                startPage = 1;
                endPage = maxVisiblePages;
            } else if (currentPage + maxPagesAfterCurrent >= totalPages) {
                startPage = totalPages - maxVisiblePages + 1;
                endPage = totalPages;
            } else {
                startPage = currentPage - maxPagesBeforeCurrent;
                endPage = currentPage + maxPagesAfterCurrent;
            }
        }

        const firstRecord = Math.min((currentPage - 1) * limit + 1, total);
        const lastRecord = Math.min(currentPage * limit, total);
        const resultsText = `<p class="text-sm text-gray-700">Showing <span class="font-medium">${firstRecord}</span> to <span class="font-medium">${lastRecord}</span> of <span class="font-medium">${total}</span> results</p>`;
        let pageButtonsHtml = '';

        pageButtonsHtml += `<button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="page-btn relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"><span class="sr-only">Previous</span><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg></button>`;
        if (startPage > 1) {
            pageButtonsHtml += `<button data-page="1" class="page-btn relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">1</button>`;
            if (startPage > 2) {
                pageButtonsHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>`;
            }
        }
        for (let i = startPage; i <= endPage; i++) {
            const isCurrent = i === currentPage;
            pageButtonsHtml += `<button data-page="${i}" ${isCurrent ? 'aria-current="page"' : ''} class="page-btn relative inline-flex items-center px-4 py-2 text-sm font-semibold ${isCurrent ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'}">${i}</button>`;
        }
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pageButtonsHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>`;
            }
            pageButtonsHtml += `<button data-page="${totalPages}" class="page-btn relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">${totalPages}</button>`;
        }
        pageButtonsHtml += `<button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="page-btn relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"><span class="sr-only">Next</span><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg></button>`;

        container.innerHTML = `
            <div class="flex flex-1 justify-between sm:hidden">
                <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="page-btn relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="page-btn relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>${resultsText}</div>
                <div><nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">${pageButtonsHtml}</nav></div>
            </div>`;
            
        container.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const pageToGo = parseInt(e.currentTarget.dataset.page);
                if (pageToGo !== currentPage) {
                    pageChangeCallback(pageToGo);
                }
            });
        });
    };
    
    const getAdStatusBadge = (status, start_at, end_at) => {
        const now = new Date();
        const start = new Date(start_at.replace(' ', 'T'));
        const end = new Date(end_at.replace(' ', 'T'));
        
        if (status === 'draft') {
            return '<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">Draft</span>';
        }
        if (status === 'paused') {
            return '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">Paused</span>';
        }
        if (end < now) {
            return '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Ended</span>';
        }
        if (start > now) {
            return '<span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">Scheduled</span>';
        }
        if (status === 'running' && start <= now && end >= now) {
            return '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Running</span>';
        }
        return `<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">${status}</span>`;
    };

    // --- 5. TAB & RENDER LOGIC ---
    const switchTab = (targetTab) => {
        appState.activeTab = targetTab;

        tabButtons.forEach(btn => {
            const isTarget = `tab-btn-${targetTab}` === btn.id;
            btn.classList.toggle('border-indigo-500', isTarget);
            btn.classList.toggle('text-indigo-600', isTarget);
            btn.classList.toggle('border-transparent', !isTarget);
            btn.classList.toggle('text-gray-500', !isTarget);
            btn.setAttribute('aria-current', isTarget ? 'page' : 'false');
        });
        
        tabPanels.forEach(panel => {
            panel.classList.toggle('hidden', `tab-panel-${targetTab}` !== panel.id);
        });
        
        if (targetTab === 'campaigns') loadAds();
        else if (targetTab === 'placements') loadPlacements();
    };

    // --- 6. AD CAMPAIGN LOGIC ---
    const loadAds = async () => {
        const params = new URLSearchParams({ 
            page: adCurrentPage, 
            limit: adRecordsPerPage, 
            ...adCurrentFilters 
        }).toString();
        const result = await apiCall(`${API.ads.read}?${params}`);
        if (result.success) {
            renderAdsTable(result.data);
            renderPagination(adPaginationContainer, result.total_records, adRecordsPerPage, adCurrentPage, (newPage) => {
                adCurrentPage = newPage;
                loadAds();
            });
        } else {
            adTableContainer.innerHTML = `<p class="p-8 text-center text-red-500">Could not load ad campaigns.</p>`;
        }
    };

    const renderAdsTable = (ads) => {
        let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:pl-0">Campaign</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placements</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">`;

        if (!ads || ads.length === 0) {
            tableHtml += `<tr><td colspan="5" class="px-3 py-12 text-center text-sm text-gray-500">No ad campaigns found.</td></tr>`;
        } else {
            ads.forEach(ad => {
                const startDate = new Date(ad.start_at.replace(' ', 'T')).toLocaleDateString();
                const endDate = new Date(ad.end_at.replace(' ', 'T')).toLocaleDateString();
                const placements = ad.placements ? ad.placements.split(',').join('<br>') : 'None';
                tableHtml += `
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0"><img class="h-10 w-10 rounded-md object-cover" src="${ad.media_url || 'https://via.placeholder.com/80'}" alt=""></div>
                                <div class="ml-4"><div class="font-medium text-gray-900">${ad.title}</div></div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${getAdStatusBadge(ad.status, ad.start_at, ad.end_at)}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${startDate} - ${endDate}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${placements}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <button class="text-indigo-600 hover:text-indigo-900 action-edit" data-id="${ad.id}" data-type="ad">Edit</button>
                            <button class="text-red-600 hover:text-red-900 ml-4 action-delete" data-id="${ad.id}" data-type="ad">Delete</button>
                        </td>
                    </tr>`;
            });
        }
        tableHtml += `</tbody></table>`;
        adTableContainer.innerHTML = tableHtml;
    };

    const fetchPlacementsCache = async () => {
        if (appState.placementsCache.length > 0) return true;
        const result = await apiCall(API.placements.read, {}, false);
        if (result.success && Array.isArray(result.data)) {
            appState.placementsCache = result.data;
            return true;
        }
        return false;
    };

    const openAdModal = async (adId = null) => {
        adForm.reset();
        currentMediaPreview.innerHTML = '';
        placementsContainer.innerHTML = '<div class="text-center text-sm text-gray-500">Loading placements...</div>';
        
        const success = await fetchPlacementsCache();
        if (!success) {
            placementsContainer.innerHTML = '<div class="text-center text-sm text-red-500">Could not load app placements. Please add placements first.</div>';
            return; // Don't open modal if placements fail to load
        }

        let existingPlacements = {};

        if (adId) {
            adModalTitle.textContent = 'Edit Ad Campaign';
            const result = await apiCall(`${API.ads.read_single}?id=${adId}`);
            if (!result.success) return;
            
            const ad = result.data.ad;
            const placements = result.data.placements;
            
            adForm.querySelector('[name="id"]').value = ad.id;
            adForm.querySelector('[name="title"]').value = ad.title;
            adForm.querySelector('[name="body"]').value = ad.body;
            adForm.querySelector('[name="url_target"]').value = ad.url_target;
            adForm.querySelector('[name="start_at"]').value = ad.start_at ? ad.start_at.slice(0, 16) : '';
            adForm.querySelector('[name="end_at"]').value = ad.end_at ? ad.end_at.slice(0, 16) : '';
            adForm.querySelector('[name="status"]').value = ad.status;
            
            if (ad.media_url) {
                currentMediaPreview.innerHTML = `<p class="text-xs text-gray-500 mb-1">Current Media:</p><img src="${ad.media_url}" class="h-20 rounded-md object-contain">`;
            }
            
            placements.forEach(p => {
                existingPlacements[p.page_id] = p.position;
            });

        } else {
            adModalTitle.textContent = 'Add New Ad Campaign';
            adForm.querySelector('[name="id"]').value = '';
            // Set default start/end times
            const now = new Date();
            const oneWeek = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
            adForm.querySelector('[name="start_at"]').value = now.toISOString().slice(0, 16);
            adForm.querySelector('[name="end_at"]').value = oneWeek.toISOString().slice(0, 16);
        }

        // Render placement options
        placementsContainer.innerHTML = '';
        if (appState.placementsCache.length === 0) {
            placementsContainer.innerHTML = '<div class="text-center text-sm text-red-500">No app placements found. Please add them in the "App Placements" tab.</div>';
        } else {
            appState.placementsCache.forEach(page => {
                const isChecked = existingPlacements.hasOwnProperty(page.id);
                const position = existingPlacements[page.id] || '';
                placementsContainer.innerHTML += `
                    <div class="flex items-center gap-3 p-2 border rounded-md">
                        <input id="page-${page.id}" name="page_ids[]" value="${page.id}" type="checkbox" ${isChecked ? 'checked' : ''} class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="page-${page.id}" class="text-sm font-medium text-gray-700 flex-grow">${page.label} (${page.code})</label>
                        <input type="text" name="position_${page.id}" value="${position}" placeholder="Position (e.g., TOP)" class="block w-48 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-800 shadow-sm">
                    </div>
                `;
            });
        }
        
        adModal.classList.remove('hidden');
    };

    // --- 7. APP PLACEMENT LOGIC ---
    const loadPlacements = async () => {
        const result = await apiCall(API.placements.read);
        if (result.success) {
            appState.placementsCache = result.data; // Refresh cache
            renderPlacementsTable(result.data);
        } else {
            placementTableContainer.innerHTML = `<p class="p-8 text-center text-red-500">Could not load app placements.</p>`;
        }
    };

    const renderPlacementsTable = (placements) => {
        let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:pl-0">Label</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">`;

        if (!placements || placements.length === 0) {
            tableHtml += `<tr><td colspan="3" class="px-3 py-12 text-center text-sm text-gray-500">No app placements defined.</td></tr>`;
        } else {
            placements.forEach(p => {
                tableHtml += `
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">${p.label}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono">${p.code}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <button class="text-indigo-600 hover:text-indigo-900 action-edit" data-id="${p.id}" data-type="placement">Edit</button>
                            <button class="text-red-600 hover:text-red-900 ml-4 action-delete" data-id="${p.id}" data-type="placement">Delete</button>
                        </td>
                    </tr>`;
            });
        }
        tableHtml += `</tbody></table>`;
        placementTableContainer.innerHTML = tableHtml;
    };
    
    const openPlacementModal = (placement = null) => {
        placementForm.reset();
        if (placement) {
            placementModalTitle.textContent = 'Edit App Placement';
            placementForm.querySelector('[name="id"]').value = placement.id;
            placementForm.querySelector('[name="label"]').value = placement.label;
            placementForm.querySelector('[name="code"]').value = placement.code;
        } else {
            placementModalTitle.textContent = 'Add App Placement';
            placementForm.querySelector('[name="id"]').value = '';
        }
        placementModal.classList.remove('hidden');
    };

    // --- 8. FORM SUBMISSION HANDLERS ---
    const handleSaveAd = async (e) => {
        e.preventDefault();
        
        const formData = new FormData(adForm);
        
        // Manually collect placements
        const placements = [];
        const checkedPages = adForm.querySelectorAll('input[name="page_ids[]"]:checked');
        checkedPages.forEach(checkbox => {
            const pageId = checkbox.value;
            const positionInput = adForm.querySelector(`input[name="position_${pageId}"]`);
            placements.push({
                page_id: pageId,
                position: positionInput.value || null
            });
        });
        
        // Add placements to FormData as a JSON string
        formData.append('placements', JSON.stringify(placements));

        // Determine URL
        const url = formData.get('id') ? API.ads.update : API.ads.create;
        
        // Use apiCall for POST with FormData
        showSpinner();
        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            if (!response.ok) {
                let errorMsg = `HTTP error! status: ${response.status}`;
                try {
                    const errData = await response.json();
                    errorMsg = errData.message || errorMsg;
                } catch (e) {}
                throw new Error(errorMsg);
            }
            const result = await response.json();
            if (result.success === false) throw new Error(result.message);

            showToast(result.message);
            adModal.classList.add('hidden');
            loadAds(); // Refresh table
        } catch (error) {
            showToast(error.message, true);
        } finally {
            hideSpinner();
        }
    };
    
    const handleSavePlacement = async (e) => {
        e.preventDefault();
        const formData = new FormData(placementForm);
        const data = Object.fromEntries(formData.entries());
        const url = data.id ? API.placements.update : API.placements.create;
        
        const result = await apiCall(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (result.success) {
            showToast(result.message);
            placementModal.classList.add('hidden');
            loadPlacements(); // Refresh table
            appState.placementsCache = []; // Clear cache
        }
    };

    // --- 9. EVENT LISTENERS ---
    const setupEventListeners = () => {
        // Tab
        tabButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const targetTab = e.currentTarget.id.replace('tab-btn-', '');
                switchTab(targetTab);
            });
        });

        // --- Ad Campaign Listeners ---
        addAdBtn?.addEventListener('click', () => openAdModal(null));

        let adDebounceTimer;
        adSearchInput.addEventListener('input', () => {
            clearTimeout(adDebounceTimer);
            adDebounceTimer = setTimeout(() => {
                adCurrentPage = 1;
                adCurrentFilters.search = adSearchInput.value;
                loadAds();
            }, 350);
        });
        adStatusFilter.addEventListener('change', () => {
            adCurrentPage = 1;
            adCurrentFilters.status = adStatusFilter.value;
            loadAds();
        });
        adForm.addEventListener('submit', handleSaveAd);

        // --- App Placement Listeners ---
        addPlacementBtn?.addEventListener('click', () => openPlacementModal(null));
        placementForm.addEventListener('submit', handleSavePlacement);

        // --- Table Click Delegation ---
        document.querySelector('main').addEventListener('click', (e) => {
            const target = e.target;
            const id = target.dataset.id;
            const type = target.dataset.type;
            if (!id || !type) return;

            if (target.classList.contains('action-edit')) {
                if (type === 'ad') {
                    openAdModal(id);
                } else if (type === 'placement') {
                    const p = appState.placementsCache.find(page => page.id == id);
                    if(p) openPlacementModal(p);
                }
            } else if (target.classList.contains('action-delete')) {
                const item = type === 'ad' ? 'campaign' : 'placement';
                if (confirm(`Are you sure you want to delete this ${item}?`)) {
                    const url = type === 'ad' ? API.ads.delete : API.placements.delete;
                    apiCall(url, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id }) }).then(result => {
                        if (result.success) {
                            showToast(result.message);
                            if(type === 'ad') loadAds();
                            else loadPlacements();
                        }
                    });
                }
            }
        });

        // --- Modal Close Listeners ---
        document.querySelectorAll('.close-modal-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.fixed[id*="Modal"]').classList.add('hidden');
            });
        });
        document.querySelectorAll('.fixed[id*="Modal"]').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.add('hidden');
            });
        });
    };

    // --- 10. INITIALIZATION ---
    setupEventListeners();
    switchTab('campaigns'); // Load the first tab
});