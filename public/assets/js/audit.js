// /assets/js/audit.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. STATE MANAGEMENT ---
    let currentPage = 1;
    let currentFilters = {
        startDate: '',
        endDate: '',
        actorType: '',
        actorSearch: '',
        actionSearch: ''
    };
    const recordsPerPage = 15;

    // --- 2. DOM ELEMENTS ---
    const tableContainer = document.getElementById('auditTableContainer');
    const paginationContainer = document.getElementById('paginationContainer');

    // Filters
    const startDateFilter = document.getElementById('startDateFilter');
    const endDateFilter = document.getElementById('endDateFilter');
    const applyDateFilter = document.getElementById('applyDateFilter');
    const clearDateFilter = document.getElementById('clearDateFilter');
    const actorTypeFilter = document.getElementById('actorTypeFilter');
    const actorSearchInput = document.getElementById('actorSearchInput');
    const actionSearchInput = document.getElementById('actionSearchInput');

    // --- 3. MODAL ELEMENTS ---
    const viewLogModal = document.getElementById('viewLogModal');
    const viewLogTitle = document.getElementById('viewLogTitle');
    const viewLogDetails = document.getElementById('viewLogDetails');
    const viewLogMeta = document.getElementById('viewLogMeta');

    // --- 4. UI ELEMENTS ---
    const spinner = document.getElementById('loading-spinner');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    // --- 5. HELPER FUNCTIONS ---
    const showSpinner = () => spinner.classList.remove('hidden');
    const hideSpinner = () => spinner.classList.add('hidden');

    const showToast = (message, isError = false) => {
        toastMessage.textContent = message;
        toast.className = `fixed bottom-5 right-5 text-white py-2 px-4 rounded-lg shadow-md z-50 ${isError ? 'bg-red-600' : 'bg-green-700'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    };

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
            const result = await response.json();
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

    // --- 6. AUDIT LOGIC ---
    const loadLogs = async () => {
        const params = new URLSearchParams({ 
            page: currentPage, 
            limit: recordsPerPage, 
            ...currentFilters 
        }).toString();
        const result = await apiCall(`/api/audit/read.php?${params}`);
        if (result.success) {
            renderTable(result.data);
            renderPagination(paginationContainer, result.total_records, recordsPerPage, currentPage, (newPage) => {
                currentPage = newPage;
                loadLogs();
            });
        } else {
            tableContainer.innerHTML = `<p class="p-8 text-center text-red-500">Could not load audit logs.</p>`;
        }
    };

    const renderTable = (logs) => {
        let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:pl-0">Timestamp</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actor</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Object</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">`;

        if (!logs || logs.length === 0) {
            tableHtml += `<tr><td colspan="5" class="px-3 py-12 text-center text-sm text-gray-500">No audit logs found.</td></tr>`;
        } else {
            logs.forEach(log => {
                let actor = '';
                switch(log.actor_type) {
                    case 'user':
                        actor = `User: ${log.actor_name || 'N/A'} (ID: ${log.actor_id})`;
                        break;
                    case 'company':
                        actor = `Company: ${log.actor_name || 'N/A'} (ID: ${log.actor_id})`;
                        break;
                    case 'system':
                        actor = '<span class="font-medium text-indigo-600">System</span>';
                        break;
                    default:
                        actor = log.actor_type;
                }
                
                let object = log.object_type ? `${log.object_type} (ID: ${log.object_id})` : 'N/A';
                
                tableHtml += `
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0 text-gray-500">${new Date(log.created_at.replace(' ', 'T')).toLocaleString()}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-800">${actor}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">${log.action}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${object}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <button class="view-log-btn text-indigo-600 hover:text-indigo-900" data-log-id="${log.id}">View</button>
                        </td>
                    </tr>`;
                
                // Store log data for modal
                tableContainer.dataset[log.id] = JSON.stringify(log);
            });
        }
        tableHtml += `</tbody></table>`;
        tableContainer.innerHTML = tableHtml;
    };

    const openViewModal = (logId) => {
        const logData = tableContainer.dataset[logId];
        if (!logData) {
            showToast('Could not find log data.', true);
            return;
        }
        
        const log = JSON.parse(logData);
        
        let actor = '';
        switch(log.actor_type) {
            case 'user': actor = `User: ${log.actor_name || 'N/A'} (ID: ${log.actor_id})`; break;
            case 'company': actor = `Company: ${log.actor_name || 'N/A'} (ID: ${log.actor_id})`; break;
            case 'system': actor = 'System'; break;
            default: actor = log.actor_type;
        }
        
        viewLogTitle.textContent = `Log #${log.id}: ${log.action}`;
        viewLogDetails.innerHTML = `
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Timestamp</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${new Date(log.created_at.replace(' ', 'T')).toLocaleString()}</dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Actor</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${actor}</dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Action</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${log.action}</dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Object Type</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${log.object_type || 'N/A'}</dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Object ID</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${log.object_id || 'N/A'}</dd>
            </div>
        `;
        
        // Format and display meta
        try {
            const metaJson = JSON.parse(log.meta);
            viewLogMeta.textContent = JSON.stringify(metaJson, null, 2);
        } catch (e) {
            viewLogMeta.textContent = log.meta || 'No metadata provided.';
        }
        
        viewLogModal.classList.remove('hidden');
    };

    // --- 7. EVENT LISTENERS ---
    const setupEventListeners = () => {
        // --- Filters ---
        applyDateFilter.addEventListener('click', () => {
            currentFilters.startDate = startDateFilter.value;
            currentFilters.endDate = endDateFilter.value;
            currentPage = 1;
            loadLogs();
        });

        clearDateFilter.addEventListener('click', () => {
            startDateFilter.value = '';
            endDateFilter.value = '';
            currentFilters.startDate = '';
            currentFilters.endDate = '';
            currentPage = 1;
            loadLogs();
        });

        actorTypeFilter.addEventListener('change', () => {
            currentFilters.actorType = actorTypeFilter.value;
            currentPage = 1;
            loadLogs();
        });

        let actorDebounceTimer;
        actorSearchInput.addEventListener('input', () => {
            clearTimeout(actorDebounceTimer);
            actorDebounceTimer = setTimeout(() => {
                currentPage = 1;
                currentFilters.actorSearch = actorSearchInput.value;
                loadLogs();
            }, 350);
        });
        
        let actionDebounceTimer;
        actionSearchInput.addEventListener('input', () => {
            clearTimeout(actionDebounceTimer);
            actionDebounceTimer = setTimeout(() => {
                currentPage = 1;
                currentFilters.actionSearch = actionSearchInput.value;
                loadLogs();
            }, 350);
        });

        // --- Table Click Delegation ---
        tableContainer.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('.view-log-btn');
            if (viewBtn) {
                openViewModal(viewBtn.dataset.logId);
            }
        });

        // --- Modal Close Listeners ---
        viewLogModal.addEventListener('click', (e) => {
            if (e.target.closest('.close-modal-btn') || e.target === viewLogModal) {
                viewLogModal.classList.add('hidden');
            }
        });
    };

    // --- 8. INITIALIZATION ---
    setupEventListeners();
    loadLogs(); // Initial page load
});