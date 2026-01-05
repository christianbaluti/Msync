// lib/assets/js/companies.js

document.addEventListener('DOMContentLoaded', function() {
    // --- STATE MANAGEMENT ---
    let currentPage = 1;
    let currentFilters = { search: '', status: '' };
    let currentCompanyId = null;
    const recordsPerPage = 10; // Match the backend limit

    // --- DOM ELEMENTS ---
    const tableContainer = document.getElementById('companyTableContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');

    // --- UI ELEMENTS ---
    const spinner = document.getElementById('loading-spinner');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    // --- MODAL ELEMENTS ---
    const companyModal = document.getElementById('companyModal');
    const viewCompanyModal = document.getElementById('viewCompanyModal');
    const companyForm = document.getElementById('companyForm');
    const modalTitle = document.getElementById('modalTitle');

    // --- HELPER FUNCTIONS ---
    const showSpinner = () => spinner.classList.remove('hidden');
    const hideSpinner = () => spinner.classList.add('hidden');

    const showToast = (message, isError = false) => {
        toastMessage.textContent = message;
        toast.className = `fixed bottom-5 right-5 text-white py-2 px-4 rounded-lg shadow-md z-50 ${isError ? 'bg-red-600' : 'bg-green-700'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    };

    const copyToClipboard = (text, type = 'Text') => {
        if (!navigator.clipboard) {
            showToast('Clipboard API is not available on your browser.', true);
            return;
        }
        navigator.clipboard.writeText(text).then(() => {
            showToast(`${type} copied to clipboard!`);
        }).catch(err => {
            showToast(`Failed to copy ${type}.`, true);
            console.error('Clipboard copy failed:', err);
        });
    };

    // --- API & RENDERING ---
    const loadCompanies = async () => {
        showSpinner();
        // Use the recordsPerPage variable
        const params = new URLSearchParams({ page: currentPage, limit: recordsPerPage, ...currentFilters }).toString();
        try {
            const response = await fetch(`/api/companies/read.php?${params}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();

            if (data && data.companies !== undefined && data.total_records !== undefined) {
                 renderTable(data.companies);
                 // Pass currentPage to renderPagination
                 renderPagination(data.total_records, recordsPerPage, currentPage);
            } else {
                 throw new Error('Invalid data structure received from API.');
            }
        } catch (error) {
            console.error("Fetch error:", error);
            tableContainer.innerHTML = `<div class="p-8 text-center text-red-500">Failed to load companies. ${error.message}</div>`;
            paginationContainer.innerHTML = '';
        } finally {
            hideSpinner();
        }
    };

    const renderTable = (companies) => {
        // Keep your existing renderTable function as is
        // It correctly renders the company data.
        let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Related Links</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">`;

        if (!companies || companies.length === 0) {
            tableHtml += `<tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No companies found matching your criteria.</td></tr>`;
        } else {
            companies.forEach(c => {
                const statusBadge = c.is_active == 1
                    ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Active</span>'
                    : '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Inactive</span>';

                tableHtml += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${c.name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="cursor-pointer hover:text-indigo-600" data-copy="${c.email}" data-copy-type="Email" title="Copy email">${c.email}</div>
                            <div class="text-gray-400 cursor-pointer hover:text-indigo-600" data-copy="${c.phone}" data-copy-type="Phone" title="Copy phone">${c.phone}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <a href="/users?company_id=${c.id}" class="text-indigo-600 hover:underline">Users (${c.user_count || 0})</a><br>
                            <a href="/memberships?company_id=${c.id}" class="text-indigo-600 hover:underline">Members (${c.member_count || 0})</a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" class="view-btn text-indigo-600 hover:text-indigo-900" data-id="${c.id}">View</button>
                            <button type="button" class="edit-btn text-gray-600 hover:text-gray-900 ml-4" data-id="${c.id}">Edit</button>
                        </td>
                    </tr>`;
            });
        }
        tableHtml += `</tbody></table>`;
        tableContainer.innerHTML = tableHtml;
    };


    // --- *** ENHANCED renderPagination Function *** ---
    const renderPagination = (total, limit, currentPage) => {
        paginationContainer.innerHTML = ''; // Clear previous pagination
        if (!total || total <= 0 || !limit || limit <= 0) return; // No need for pagination

        const totalPages = Math.ceil(total / limit);
        if (totalPages <= 1) return; // No pagination needed if only one page

        const maxVisiblePages = 5; // How many page number links to show (e.g., 1 ... 4 5 6 ... 10)
        let startPage, endPage;

        if (totalPages <= maxVisiblePages) {
            // Show all pages if total pages is less than or equal to max visible
            startPage = 1;
            endPage = totalPages;
        } else {
            // Calculate start and end pages with ellipsis
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

        // --- Generate Pagination HTML ---

        // Showing results text
        const firstRecord = Math.min((currentPage - 1) * limit + 1, total);
        const lastRecord = Math.min(currentPage * limit, total);
        const resultsText = `<p class="text-sm text-gray-700">Showing <span class="font-medium">${firstRecord}</span> to <span class="font-medium">${lastRecord}</span> of <span class="font-medium">${total}</span> results</p>`;

        // Buttons HTML string
        let pageButtonsHtml = '';

        // Previous Button
        pageButtonsHtml += `
            <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
            </button>`;

        // Page Number Buttons (with ellipsis logic)
        if (startPage > 1) {
            pageButtonsHtml += `<button data-page="1" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">1</button>`;
            if (startPage > 2) {
                pageButtonsHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const isCurrent = i === currentPage;
            pageButtonsHtml += `
                <button data-page="${i}" ${isCurrent ? 'aria-current="page"' : ''} class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ${isCurrent ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'}">
                    ${i}
                </button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pageButtonsHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>`;
            }
            pageButtonsHtml += `<button data-page="${totalPages}" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">${totalPages}</button>`;
        }

        // Next Button
        pageButtonsHtml += `
            <button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
            </button>`;

        // Assemble final pagination HTML
        paginationContainer.innerHTML = `
            <div class="flex flex-1 justify-between sm:hidden">
                <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>${resultsText}</div>
                <div><nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    ${pageButtonsHtml}
                </nav></div>
            </div>`;
    };
    // --- *** END ENHANCED renderPagination Function *** ---


    // --- MODAL HANDLING & FORM SUBMISSION (No changes needed here) ---
    const openEditModal = async (id) => {
        showSpinner();
        try {
            const response = await fetch(`/api/companies/read_single.php?id=${id}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const company = await response.json();
            if (!company || company.error) { throw new Error(company.error || 'Company data not found.'); }

            companyForm.reset();
            modalTitle.textContent = `Edit Company: ${company.name}`;
            document.getElementById('companyId').value = company.id;
            document.getElementById('name').value = company.name;
            document.getElementById('email').value = company.email;
            document.getElementById('phone').value = company.phone;
            document.getElementById('address').value = company.address || '';
            document.getElementById('allowLogin').value = company.allow_login;
            document.getElementById('isActive').value = company.is_active;
            document.getElementById('password').removeAttribute('required'); // Make password optional on edit
            document.getElementById('password').placeholder = "Leave blank to keep current password";
            companyModal.classList.remove('hidden');
        } catch (error) {
            console.error("Error opening edit modal:", error);
            showToast(`Error fetching company details: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    };

    const openViewModal = async (id) => {
        showSpinner();
        currentCompanyId = id; // Store ID for potential edit/delete actions
        try {
            const response = await fetch(`/api/companies/read_single.php?id=${id}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const company = await response.json();
            if (!company || company.error) { throw new Error(company.error || 'Company data not found.'); }

            document.getElementById('viewCompanyName').textContent = company.name;
            document.getElementById('viewCompanyEmail').textContent = company.email;
            // Clear previous details first
            document.getElementById('viewCompanyDetails').innerHTML = '';
            document.getElementById('viewCompanyLinks').innerHTML = '';

            // Populate details using Tailwind classes for definition list styling
            document.getElementById('viewCompanyDetails').innerHTML = `
                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${company.phone || 'N/A'}</dd>
                </div>
                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${company.address || 'N/A'}</dd>
                </div>
                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Can Login</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${company.allow_login == 1 ? 'Yes' : 'No'}</dd>
                </div>
                 <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${company.is_active == 1 ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Active</span>' : '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Inactive</span>'}</dd>
                </div>
                 <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Created At</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${company.created_at ? new Date(company.created_at).toLocaleString() : 'N/A'}</dd>
                </div>
                 <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${company.updated_at ? new Date(company.updated_at).toLocaleString() : 'N/A'}</dd>
                </div>
            `;
            // Populate links
            document.getElementById('viewCompanyLinks').innerHTML = `
                <h4 class="text-md font-semibold text-gray-700 border-t pt-4 px-6">Quick Links</h4>
                <div class="mt-2 px-6 grid grid-cols-2 gap-2 text-sm">
                    <a href="/users?company_id=${id}" class="text-indigo-600 hover:underline">View Users (${company.user_count || 0})</a>
                    <a href="/memberships?company_id=${id}" class="text-indigo-600 hover:underline">View Members (${company.member_count || 0})</a>
                    <a href="/invoices?company_id=${id}" class="text-indigo-600 hover:underline" style="display:none">View Invoices (${company.invoice_count || 0})</a>
                    <a href="/receipts?company_id=${id}" class="text-indigo-600 hover:underline" style="display:none">View Receipts (${company.receipt_count || 0})</a>
                    <a href="/quotations?company_id=${id}" class="text-indigo-600 hover:underline" style="display:none">View Quotations (${company.quotation_count || 0})</a>
                </div>`;

            viewCompanyModal.classList.remove('hidden');
        } catch (error) {
            console.error("Error opening view modal:", error);
            showToast(`Error fetching company details: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    };

    const deleteCompany = async (id) => {
        showSpinner();
        try {
            const response = await fetch('/api/companies/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();
            if (result.success) {
                viewCompanyModal.classList.add('hidden');
                showToast(result.message);
                currentPage = 1; // Reset to first page after delete
                loadCompanies();
            } else { throw new Error(result.message); }
        } catch (error) {
            showToast(`Error deleting company: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    };


    // --- EVENT LISTENERS ---
    const setupEventListeners = () => {
        // Debounced search
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentPage = 1; // Reset page on new search
                currentFilters.search = searchInput.value;
                loadCompanies();
            }, 350); // Slightly increased debounce time
        });

        statusFilter.addEventListener('change', () => {
            currentPage = 1; // Reset page on filter change
            currentFilters.status = statusFilter.value;
            loadCompanies();
        });

        // Use event delegation for pagination buttons
        paginationContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-page]');
            if (btn && !btn.disabled) {
                const pageToGo = parseInt(btn.dataset.page);
                if (pageToGo !== currentPage) { // Only load if page changes
                    currentPage = pageToGo;
                    loadCompanies();
                }
            }
        });

        // Event delegation for table actions
        tableContainer.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('.view-btn');
            if (viewBtn) {
                openViewModal(viewBtn.dataset.id);
                return; // Stop further processing
            }

            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                openEditModal(editBtn.dataset.id);
                return; // Stop further processing
            }

            const copyEl = e.target.closest('[data-copy]');
            if (copyEl) {
                copyToClipboard(copyEl.dataset.copy, copyEl.dataset.copyType);
                return; // Stop further processing
            }
        });

        document.getElementById('addCompanyBtn')?.addEventListener('click', () => {
            companyForm.reset();
            document.getElementById('companyId').value = '';
            modalTitle.textContent = 'Add New Company';
            document.getElementById('password').setAttribute('required', 'required'); // Password required for new company
            document.getElementById('password').placeholder = "Enter password";
            companyModal.classList.remove('hidden');
        });

        // Modal closing logic (buttons and backdrop click)
        [companyModal, viewCompanyModal].forEach(modal => {
            modal.addEventListener('click', (e) => {
                // Close if the close button is clicked OR if the backdrop itself is clicked
                if (e.target.closest('.close-modal-btn') || e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });

        // Form submission
        companyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            showSpinner();
            const formData = new FormData(companyForm);
            const data = Object.fromEntries(formData.entries());
            const url = data.id ? '/api/companies/update.php' : '/api/companies/create.php';

            // Only include password if it's not empty
            if (!data.password) {
                delete data.password;
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    companyModal.classList.add('hidden');
                    showToast(result.message);
                    // Decide whether to stay on current page or go to page 1
                    // For simplicity, let's reload the current page after edit/add
                    // If adding, maybe go to page 1 if sorted by name? Let's keep it simple.
                     loadCompanies();
                } else {
                    throw new Error(result.message || 'Failed to save company.');
                }
            } catch (error) {
                showToast(`Error saving company: ${error.message}`, true);
            } finally {
                hideSpinner();
            }
        });

        // View Modal action buttons
        document.getElementById('viewEditBtn')?.addEventListener('click', () => {
            if (currentCompanyId) {
                viewCompanyModal.classList.add('hidden'); // Close view modal first
                openEditModal(currentCompanyId);
            }
        });

        document.getElementById('viewDeleteBtn')?.addEventListener('click', () => {
            if (currentCompanyId && confirm('Are you sure you want to delete this company? This action cannot be undone.')) {
                deleteCompany(currentCompanyId);
            }
        });
    };

    // --- INITIALIZATION ---
    setupEventListeners();
    loadCompanies(); // Initial load
});