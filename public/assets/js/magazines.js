// /assets/js/magazines.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. STATE MANAGEMENT ---
    let currentPage = 1;
    let currentFilters = { search: '' };
    const recordsPerPage = 10;

    const API = {
        read: '/api/magazines/read.php',
        create: '/api/magazines/create.php',
        read_single: '/api/magazines/read_single.php',
        update: '/api/magazines/update.php',
        delete: '/api/magazines/delete.php',
    };

    // --- 2. DOM ELEMENTS ---
    const tableContainer = document.getElementById('magazineTableContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    const searchInput = document.getElementById('searchInput');
    const addMagazineBtn = document.getElementById('addMagazineBtn');
    
    // Modal
    const magazineModal = document.getElementById('magazineModal');
    const magazineForm = document.getElementById('magazineForm');
    const magazineModalTitle = document.getElementById('magazineModalTitle');
    const coverImageInput = document.getElementById('cover_image');
    const magazineFileInput = document.getElementById('magazine_file');
    const currentCoverPreview = document.getElementById('current-cover-preview');
    const currentFilePreview = document.getElementById('current-file-preview');

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

    // Re-usable API call for FormData
    const apiCallWithForm = async (url, formData, showSpinnerFlag = true) => {
        if (showSpinnerFlag) showSpinner();
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

    // --- 5. MAGAZINE CRUD ---

    const loadMagazines = async () => {
        showSpinner();
        const params = new URLSearchParams({ 
            page: currentPage, 
            limit: recordsPerPage, 
            ...currentFilters 
        }).toString();
        
        try {
            const response = await fetch(`${API.read}?${params}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            
            if (result.success) {
                renderMagazinesTable(result.data);
                renderPagination(paginationContainer, result.total_records, recordsPerPage, currentPage, (newPage) => {
                    currentPage = newPage;
                    loadMagazines();
                });
            } else {
                throw new Error(result.message || 'Invalid data structure.');
            }
        } catch (error) {
            console.error("Fetch error:", error);
            showToast(`Failed to load magazines. ${error.message}`, true);
            tableContainer.innerHTML = `<div class="p-8 text-center text-red-500">Failed to load magazines.</div>`;
        } finally {
            hideSpinner();
        }
    };

    const renderMagazinesTable = (magazines) => {
        let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:pl-0">Magazine</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Type</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Downloads</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">`;

        if (!magazines || magazines.length === 0) {
            tableHtml += `<tr><td colspan="6" class="px-3 py-12 text-center text-sm text-gray-500">No magazines found.</td></tr>`;
        } else {
            magazines.forEach(mag => {
                tableHtml += `
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0"><img class="h-10 w-10 rounded-md object-cover" src="${mag.cover_image_url || 'https://via.placeholder.com/80'}" alt="Cover"></div>
                                <div class="ml-4"><div class="font-medium text-gray-900">${mag.title}</div></div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 uppercase">${mag.file_type}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${mag.view_count}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${mag.download_count}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${new Date(mag.created_at.replace(' ','T')).toLocaleDateString()}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <button class="text-indigo-600 hover:text-indigo-900 action-edit" data-id="${mag.id}">Edit</button>
                            <button class="text-red-600 hover:text-red-900 ml-4 action-delete" data-id="${mag.id}">Delete</button>
                        </td>
                    </tr>`;
            });
        }
        tableHtml += `</tbody></table>`;
        tableContainer.innerHTML = tableHtml;
    };

    const openAddModal = () => {
        magazineForm.reset();
        magazineModalTitle.textContent = 'Add New Magazine';
        document.getElementById('magazineId').value = '';
        currentCoverPreview.innerHTML = '';
        currentFilePreview.innerHTML = '';
        // Make files required for new
        coverImageInput.required = true;
        magazineFileInput.required = true;
        magazineModal.classList.remove('hidden');
    };

    const openEditModal = async (id) => {
        showSpinner();
        try {
            const response = await fetch(`${API.read_single}?id=${id}`);
            if (!response.ok) throw new Error('Failed to fetch magazine data.');
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            
            const mag = result.data;
            magazineForm.reset();
            magazineModalTitle.textContent = 'Edit Magazine';
            document.getElementById('magazineId').value = mag.id;
            document.getElementById('title').value = mag.title;
            document.getElementById('description').value = mag.description;
            
            // Show current files
            currentCoverPreview.innerHTML = `<p class="text-xs text-gray-500 mb-1">Current Cover:</p><img src="${mag.cover_image_url}" class="h-20 rounded-md object-contain">`;
            currentFilePreview.innerHTML = `<p class="text-xs text-gray-500 mb-1">Current File:</p><a href="${mag.file_url}" target="_blank" class="text-indigo-600 underline">${mag.file_url.split('/').pop()}</a>`;
            
            // Files are not required on edit
            coverImageInput.required = false;
            magazineFileInput.required = false;

            magazineModal.classList.remove('hidden');
        } catch (error) {
            showToast(error.message, true);
        } finally {
            hideSpinner();
        }
    };
    
    const handleSaveMagazine = async (e) => {
        e.preventDefault();
        const formData = new FormData(magazineForm);
        const url = formData.get('id') ? API.update : API.create;
        
        const result = await apiCallWithForm(url, formData);

        if (result.success) {
            showToast(result.message);
            magazineModal.classList.add('hidden');
            loadMagazines();
        }
    };
    
    const handleDeleteMagazine = async (id) => {
        if (!confirm('Are you sure you want to delete this magazine? This will delete its files and cannot be undone.')) {
            return;
        }
        
        showSpinner();
        try {
            const response = await fetch(API.delete, { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify({ id }) 
            });
            if (!response.ok) throw new Error('Failed to delete.');
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            showToast(result.message);
            loadMagazines(); // Refresh
        } catch (error) {
            showToast(error.message, true);
        } finally {
            hideSpinner();
        }
    };

    // --- 6. EVENT LISTENERS ---
    const setupEventListeners = () => {
        // Search
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentPage = 1;
                currentFilters.search = searchInput.value;
                loadMagazines();
            }, 350);
        });

        // Add button
        addMagazineBtn?.addEventListener('click', openAddModal);

        // Form submit
        magazineForm.addEventListener('submit', handleSaveMagazine);

        // Table actions
        tableContainer.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.action-edit');
            if (editBtn) {
                openEditModal(editBtn.dataset.id);
                return;
            }
            const deleteBtn = e.target.closest('.action-delete');
            if (deleteBtn) {
                handleDeleteMagazine(deleteBtn.dataset.id);
                return;
            }
        });

        // Modal close
        magazineModal.addEventListener('click', (e) => {
            if (e.target.closest('.close-modal-btn') || e.target === magazineModal) {
                magazineModal.classList.add('hidden');
            }
        });
    };

    // --- 7. INITIALIZATION ---
    setupEventListeners();
    loadMagazines();
});