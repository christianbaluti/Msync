document.addEventListener('DOMContentLoaded', function() {
    // --- STATE & HELPERS ---
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('id');
    const toast = document.getElementById('toast');
    const spinner = document.getElementById('loading-spinner');
    let searchTimeout;
    let allUsers = [];
    let allPurchases = [];
    let filteredPurchases = [];
    let currentPage = 1;
    let itemsPerPage = 10;
    let paymentMethodsCache = [];

    // --- SPINNER & TOAST HELPERS ---
    const showSpinner = () => spinner && spinner.classList.remove('hidden');
    const hideSpinner = () => spinner && spinner.classList.add('hidden');

    const tableContainer = document.getElementById('purchasesTableContainer');
    // REMOVED: addPurchaseBtn
    // REMOVED: purchaseModal
    // REMOVED: purchaseForm
    const detailsModal = document.getElementById('detailsModal');
    
    // REMOVED: All variable declarations for the single purchase modal
    // (userSearchInput, userSearchResults, newUserFields, etc.)
    
    // --- BULK MODAL ELEMENTS ---
    const bulkPurchaseBtn = document.getElementById('bulkPurchaseBtn');
    const bulkPurchaseModal = document.getElementById('bulkPurchaseModal');
    const bulkStep1 = document.getElementById('bulkStep1');
    const bulkStepExisting = document.getElementById('bulkStepExisting');
    const bulkStepNew = document.getElementById('bulkStepNew');

    const showToast = (message, isError = false) => {
        if (!toast) return;
        toast.textContent = message;
        toast.className = `fixed top-5 right-5 text-white py-2 px-4 rounded-lg shadow-md z-50 ${isError ? 'bg-red-600' : 'bg-green-600'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleString('en-GB', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    };

    const renderHeader = (details) => {
        const eventHeaderContainer = document.getElementById('eventHeader');
        if (!eventHeaderContainer || !details) return;

        const statusBadge = details.status === 'published' 
            ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Published</span>'
            : '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">Draft</span>';

        eventHeaderContainer.innerHTML = `
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:truncate">${details.title}</h1>
                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6 text-slate-500">
                        <div class="mt-2 flex items-center text-sm">
                            <i class="fa-regular fa-calendar-days mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400"></i>
                            ${formatDate(details.start_datetime)} to ${formatDate(details.end_datetime)}
                        </div>
                        <div class="mt-2 flex items-center text-sm">
                            <i class="fa-solid fa-location-dot mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400"></i>
                            ${details.location || 'N/A'}
                        </div>
                        <div class="mt-2 flex items-center text-sm">${statusBadge}</div>
                    </div>
                </div>
            </div>
            <div class="mt-4 prose prose-sm max-w-none text-slate-600">${details.description || ''}</div>
        `;
    };

    // --- API & DATA LOADING FUNCTIONS ---
    const loadEventDetails = async () => {
        if (!eventId) {
            document.getElementById('eventHeader').innerHTML = '<p class="text-red-500">Error: No Event ID provided.</p>';
            return;
        }
        showSpinner();
        try {
            const response = await fetch(`/api/events/read_details.php?id=${eventId}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const result = await response.json();
            if (result.details) {
                renderHeader(result.details);
            } else {
                throw new Error('Event details could not be found.');
            }
        } catch (error) {
            console.error('Error loading event details:', error);
            document.getElementById('eventHeader').innerHTML = `<p class="text-red-500">Could not load event details: ${error.message}</p>`;
        } finally {
            hideSpinner();
        }
    };

    const loadPurchases = async () => {
        if (!eventId) return;
        tableContainer.innerHTML = `<div class="text-center py-5 text-gray-500">Loading purchases...</div>`;
        showSpinner();
        try {
            const response = await fetch(`/api/events/purchases/read.php?event_id=${eventId}`);
            if (!response.ok) throw new Error('Failed to fetch data');
            const purchases = await response.json();
            allPurchases = purchases;
            currentPage = 1;
            applyFiltersAndRender();
        } catch (error) {
            tableContainer.innerHTML = `<div class="text-center py-5 text-red-500">Could not load purchases.</div>`;
            console.error(error);
        } finally {
            hideSpinner();
        }
    };

    // --- RENDER FUNCTIONS (These are local, no spinners needed) ---
    const applyFiltersAndRender = () => {
        const nameFilter = document.getElementById('filterName')?.value.toLowerCase() || '';
        const statusFilter = document.getElementById('filterStatus')?.value || '';
        const ticketTypeFilter = document.getElementById('filterTicketType')?.value || '';

        // 1. Apply filters
        filteredPurchases = allPurchases.filter(p => {
            const nameMatch = nameFilter ? (p.user_name?.toLowerCase().includes(nameFilter) || p.user_email?.toLowerCase().includes(nameFilter)) : true;
            const statusMatch = statusFilter ? p.status === statusFilter : true;
            const ticketTypeMatch = ticketTypeFilter ? p.ticket_type_name === ticketTypeFilter : true;
            return nameMatch && statusMatch && ticketTypeMatch;
        });

        // 2. Apply pagination
        const totalPages = Math.ceil(filteredPurchases.length / itemsPerPage);
        currentPage = Math.max(1, Math.min(currentPage, totalPages || 1));
        
        const startIndex = (currentPage - 1) * itemsPerPage;
        const paginatedPurchases = filteredPurchases.slice(startIndex, startIndex + itemsPerPage);

        // 3. Render the final result
        renderPurchasesTable(paginatedPurchases);
    };
    
    const populatePaymentMethods = (selectElement, methods) => {
        if (!selectElement || !methods) return;
        selectElement.innerHTML = '<option value="">-- Select Method --</option>';
        methods.forEach(method => {
            // Capitalize and replace underscores for display
            const displayText = method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            selectElement.innerHTML += `<option value="${method}">${displayText}</option>`;
        });
    };

    const renderPurchasesTable = (purchases) => {
        const totalPages = Math.ceil(filteredPurchases.length / itemsPerPage);
        
        // Get unique ticket types for the filter dropdown
        const uniqueTicketTypes = [...new Set(allPurchases.map(p => p.ticket_type_name))];

        let componentHtml = `
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 pb-4 border-b">
                    <div>
                        <label for="filterName" class="sr-only">Filter by name or email</label>
                        <input type="text" id="filterName" placeholder="Filter by name or email..." class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    </div>
                    <div>
                        <label for="filterTicketType" class="sr-only">Filter by ticket type</label>
                        <select id="filterTicketType" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                            <option value="">All Ticket Types</option>
                            ${uniqueTicketTypes.map(type => `<option value="${type}">${type}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label for="filterStatus" class="sr-only">Filter by status</label>
                        <select id="filterStatus" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                            <option value="">All Statuses</option>
                            <option value="bought">Bought</option>
                            <option value="verified">Verified</option>
                            <option value="pending">Pending</option>
                            <option value="denied">Denied</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Attendee</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Ticket Details</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Financials</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">`;

        if (!purchases || purchases.length === 0) {
            componentHtml += `<tr><td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">No matching ticket purchases found.</td></tr>`;
        } else {
            purchases.forEach(p => {
                const balance = parseFloat(p.balance_due);
                let statusBadge = '';
                switch (p.status) {
                    case 'bought': statusBadge = `<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Bought</span>`; break;
                    case 'verified': statusBadge = `<span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">Verified</span>`; break;
                    case 'pending': statusBadge = `<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">Pending</span>`; break;
                    case 'denied': statusBadge = `<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Denied</span>`; break;
                    default: statusBadge = `<span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">${p.status}</span>`; break;
                }

                componentHtml += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-slate-900">${p.user_name || p.company_name || 'N/A'}</div><div class="text-sm text-slate-500">${p.user_email || 'No email'}</div></td>
                        <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-slate-900">${p.ticket_type_name}</div><div class="text-sm text-slate-500">${p.attendee_type_name || ''}</div></td>
                        <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-slate-900">Price: MWK ${parseFloat(p.price).toFixed(2)}</div><div class="text-sm ${balance > 0 ? 'text-red-600 font-medium' : 'text-slate-500'}">Balance: MWK ${balance.toFixed(2)}</div></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">${statusBadge}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4"><button type="button" data-id="${p.id}" class="details-btn text-indigo-600 hover:text-indigo-900">Details</button></td>
                    </tr>`;
            });
        }
        
        componentHtml += `</tbody></table></div>
        
            <div class="mt-4 flex items-center justify-between border-t pt-4">
                <div class="flex items-center text-sm text-gray-700">
                    <span>Show</span>
                    <select id="itemsPerPageSelect" class="w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        <option value="10" ${itemsPerPage === 10 ? 'selected' : ''}>10</option>
                        <option value="25" ${itemsPerPage === 25 ? 'selected' : ''}>25</option>
                        <option value="50" ${itemsPerPage === 50 ? 'selected' : ''}>50</option>
                    </select>
                    <span>entries. Showing ${filteredPurchases.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0} to ${Math.min(currentPage * itemsPerPage, filteredPurchases.length)} of ${filteredPurchases.length} results.</span>
                </div>
                <div class="flex items-center space-x-1">
                    <button type="button" id="prevPageBtn" ${currentPage <= 1 ? 'disabled' : ''} class="pagination-btn px-3 py-1 border rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">&larr; Previous</button>
                    <span class="px-3 py-1 text-sm">Page ${currentPage} of ${totalPages}</span>
                    <button type="button" id="nextPageBtn" ${currentPage >= totalPages ? 'disabled' : ''} class="pagination-btn px-3 py-1 border rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">Next &rarr;</button>
                </div>
            </div>
        </div>`;
        
        tableContainer.innerHTML = componentHtml;
    };

    const renderDetailsModal = (data) => {
        const { ticket, payments, invoices, receipts } = data;
        const content = document.getElementById('detailsModalContent');
        
        document.getElementById('detailsModalTitle').textContent = `Ticket: ${ticket.ticket_code}`;
        document.getElementById('saveTicketDetailsBtn').dataset.id = ticket.id;
        document.getElementById('deleteTicketBtn').dataset.id = ticket.id;

        const balanceDue = parseFloat(ticket.balance_due);
        let actionOptionsHTML = `<option value="save_changes">Just Save Changes</option>`;
        if (balanceDue > 0) {
            actionOptionsHTML += `<option value="process_payment">Process Payment & Issue Receipt</option>`;
            actionOptionsHTML += `<option value="issue_invoice">Issue Invoice for Balance</option>`;
        }

        const paymentMethodOptions = paymentMethodsCache.map(method => {
            const displayText = method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            return `<option value="${method}">${displayText}</option>`;
        }).join('');

        // --- Helper for styling form inputs and selects ---
        const formElementClasses = "w-full border border-gray-300 bg-white rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition";

        // --- Build document links ---
        const invoicesHTML = invoices?.length > 0
            ? invoices.map(inv => `<div><a href="/invoices/view.php?id=${inv.id}" target="_blank" class="text-indigo-600 hover:underline">Invoice #${inv.invoice_number} (${inv.status})</a></div>`).join('')
            : '';
        const receiptsHTML = receipts?.length > 0
            ? receipts.map(r => `<div><a href="/api/receipts/download_receipt.php?receipt_id=${r.id}" target="_blank" class="text-indigo-600 hover:underline">Receipt ${r.receipt_number}</a></div>`).join('')
            : '';
        const noDocumentsHTML = !invoicesHTML && !receiptsHTML ? `<p class="text-sm text-gray-500">None</p>` : '';

        // --- Generate the Payment Section conditionally ---
        const paymentSectionHTML = balanceDue > 0 ? `
            <div class="p-4 border rounded-lg">
                <h4 class="text-sm font-bold text-gray-500 uppercase">Record a Payment</h4>
                <div class="space-y-3 mt-2">
                    <div>
                        <label for="payment_amount" class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="number" name="payment_amount" id="payment_amount" placeholder="0.00" max="${balanceDue}" step="0.01" class="${formElementClasses}">
                    </div>
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">Method</label>
                        <select name="payment_method" id="payment_method" class="${formElementClasses}">
                            <option value="">-- Select Method --</option>
                            ${paymentMethodOptions}
                        </select>
                    </div>
                </div>
            </div>` : '';


        // --- Set the final innerHTML for the modal content ---
        content.innerHTML = `
            <div class="md:col-span-2 space-y-4">
                <div class="p-4 border rounded-lg">
                    <h4 class="text-sm font-bold text-gray-500 uppercase">Attendee</h4>
                    <p class="mt-1 text-lg font-semibold text-gray-800">${ticket.user_name}</p>
                    <p class="text-sm text-gray-600">${ticket.user_email}</p>
                </div>

                <div class="p-4 border rounded-lg">
                    <h4 class="text-sm font-bold text-gray-500 uppercase">Ticket Details</h4>
                    <div class="mt-2 space-y-2">
                        <p class="text-sm"><strong>Type:</strong> ${ticket.ticket_type_name}</p>
                        <p class="text-sm"><strong>Group:</strong> ${ticket.attendee_type_name || 'N/A'}</p>
                        <div>
                            <label for="ticket_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="ticket_status" class="${formElementClasses} capitalize">
                                <option value="pending" ${ticket.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="bought" ${ticket.status === 'bought' ? 'selected' : ''}>Bought</option>
                                <option value="verified" ${ticket.status === 'verified' ? 'selected' : ''}>Verified</option>
                                <option value="denied" ${ticket.status === 'denied' ? 'selected' : ''}>Denied</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-gray-50 border rounded-lg">
                    <h4 class="text-sm font-bold text-gray-500 uppercase">Financials</h4>
                    <div class="mt-2 space-y-1">
                        <p class="text-sm flex justify-between"><span>Price:</span> <span>MWK ${parseFloat(ticket.price).toFixed(2)}</span></p>
                        <p class="text-sm flex justify-between font-bold text-red-600"><span>Balance Due:</span> <span>MWK ${balanceDue.toFixed(2)}</span></p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                ${paymentSectionHTML}
                
                <div class="p-4 border rounded-lg">
                    <h4 class="text-sm font-bold text-gray-500 uppercase">Final Action</h4>
                     <label for="final_action" class="sr-only">Final Action</label> <select name="action" id="final_action" class="${formElementClasses}">
                        ${actionOptionsHTML}
                    </select>
                </div>

                <div class="p-4 border rounded-lg">
                    <h4 class="text-sm font-bold text-gray-500 uppercase">Documents</h4>
                    <div class="text-sm space-y-1 mt-2">
                        ${invoicesHTML}
                        ${receiptsHTML}
                        ${noDocumentsHTML}
                    </div>
                </div>
            </div>`;
    };
    
    const renderBulkUserList = () => {
        const userListContainer = document.getElementById('bulkUserList');
        const filterName = document.getElementById('bulkFilterName').value.toLowerCase();
        const filterEmployed = document.getElementById('bulkFilterEmployed').value;
        const filterCompany = document.getElementById('bulkFilterCompany').value;
        const selectedUserCountSpan = document.getElementById('bulkSelectedUserCount');
        
        let filteredUsers = allUsers;

        if (filterName) {
            filteredUsers = filteredUsers.filter(u => u.full_name.toLowerCase().includes(filterName) || u.email.toLowerCase().includes(filterName));
        }
        if (filterEmployed !== "") {
            filteredUsers = filteredUsers.filter(u => u.is_employed == filterEmployed);
        }
        if (filterCompany !== "") {
            filteredUsers = filteredUsers.filter(u => u.company_id == filterCompany);
        }

        userListContainer.innerHTML = filteredUsers.map(user => `
            <label class="flex items-center p-2 hover:bg-gray-100 rounded-md">
                <input type="checkbox" name="user_ids[]" value="${user.id}" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 bulk-user-checkbox">
                <span class="ml-3 text-sm">${user.full_name} (${user.email}) - ${user.company_name || 'Not Employed'}</span>
            </label>
        `).join('');

        userListContainer.addEventListener('change', () => {
             const selectedCount = userListContainer.querySelectorAll('input:checked').length;
             selectedUserCountSpan.textContent = selectedCount;
        });
    };

    // --- EVENT LISTENERS ---
    const setupEventListeners = () => {
        // REMOVED: addPurchaseBtn?.addEventListener('click', ...) block

        tableContainer.addEventListener('click', async (e) => {
            const detailsBtn = e.target.closest('.details-btn');
            if (detailsBtn) {
                const ticketId = detailsBtn.dataset.id;
                showSpinner();
                try {
                    const response = await fetch(`/api/events/purchases/read_single.php?id=${ticketId}`);
                    const data = await response.json();
                    
                    // Fetch payment methods if not already cached
                    if (paymentMethodsCache.length === 0) {
                        const pmRes = await fetch('/api/payments/get_methods.php');
                        const paymentMethodsResult = await pmRes.json();
                        if(paymentMethodsResult.success) {
                            paymentMethodsCache = paymentMethodsResult.data;
                        }
                    }
                    
                    renderDetailsModal(data);
                    detailsModal.classList.remove('hidden');
                } catch (error) {
                    showToast('Could not load ticket details.', true);
                    console.error(error);
                } finally {
                    hideSpinner();
                }
            }
            // Pagination clicks (local, no spinner)
            if (e.target.id === 'prevPageBtn' && currentPage > 1) {
                currentPage--;
                applyFiltersAndRender();
            }
            if (e.target.id === 'nextPageBtn') {
                const totalPages = Math.ceil(filteredPurchases.length / itemsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    applyFiltersAndRender();
                }
            }
        });
        
        // Filter and pagination controls (local, no spinner)
        tableContainer.addEventListener('input', e => {
            if (e.target.id === 'filterName') {
                currentPage = 1; // Reset to page 1 when filter changes
                applyFiltersAndRender();
            }
        });

        tableContainer.addEventListener('change', e => {
            if (e.target.id === 'filterStatus' || e.target.id === 'filterTicketType') {
                currentPage = 1; // Reset to page 1
                applyFiltersAndRender();
            }
            if (e.target.id === 'itemsPerPageSelect') {
                itemsPerPage = parseInt(e.target.value, 10);
                currentPage = 1; // Reset to page 1
                applyFiltersAndRender();
            }
        });
        
        // REMOVED: if (purchaseForm) { ... } block
        
        detailsModal.addEventListener('click', async (e) => {
            if (e.target.closest('.close-modal')) {
                detailsModal.classList.add('hidden');
            }

            if (e.target.id === 'saveTicketDetailsBtn') {
                const ticketId = e.target.dataset.id;
                
                const status = detailsModal.querySelector('select[name="status"]').value;
                const action = detailsModal.querySelector('select[name="action"]').value;
                const paymentAmountInput = detailsModal.querySelector('input[name="payment_amount"]');
                const paymentMethodInput = detailsModal.querySelector('select[name="payment_method"]');

                const payload = {
                    ticket_id: ticketId,
                    status: status,
                    action: action,
                    payment_amount: 0,
                    payment_method: null
                };

                // Add payment info only if the action requires it
                if (action === 'process_payment') {
                    const amount = parseFloat(paymentAmountInput?.value || 0);
                    const method = paymentMethodInput?.value || null;
                    if (amount <= 0) {
                        return showToast('Payment amount must be greater than zero to process payment.', true);
                    }
                    if (!method) {
                        return showToast('Please select a payment method.', true);
                    }
                    payload.payment_amount = amount;
                    payload.payment_method = method;
                }

                showSpinner();
                try {
                    const response = await fetch('/api/events/purchases/update.php', {
                         method: 'POST',
                         headers: {'Content-Type': 'application/json'},
                         body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    showToast(result.message, !result.success);
                    if(result.success) {
                        detailsModal.classList.add('hidden');
                        await loadPurchases(); // Await reload
                    }
                } catch (error) {
                    showToast('An unexpected error occurred.', true);
                    console.error(error);
                } finally {
                    hideSpinner();
                }
            }
            
            if (e.target.id === 'deleteTicketBtn') {
                 if (confirm('Are you absolutely sure you want to delete this ticket? This action cannot be undone.')) {
                    const ticketId = e.target.dataset.id;
                    showSpinner();
                    try {
                        const response = await fetch('/api/events/purchases/delete.php', {
                            method: 'POST',
                            body: JSON.stringify({ id: ticketId }),
                            headers: { 'Content-Type': 'application/json' }
                        });
                        const result = await response.json();
                        showToast(result.message, !result.success);
                        if (result.success) {
                            detailsModal.classList.add('hidden');
                            await loadPurchases(); // Await reload
                        }
                    } catch (error) {
                        showToast('An unexpected error occurred.', true);
                        console.error(error);
                    } finally {
                        hideSpinner();
                    }
                }
            }
        });

        if (bulkPurchaseBtn) {
            bulkPurchaseBtn.addEventListener('click', async () => {
                bulkStep1.classList.remove('hidden');
                bulkStepExisting.classList.add('hidden');
                bulkStepNew.classList.add('hidden');
                
                showSpinner();
                try {
                     const [ttRes, atRes, compRes, usersRes, pmRes] = await Promise.all([
                        fetch(`/api/events/ticket_types/read_for_tickets_purchases.php?event_id=${eventId}`),
                        fetch(`/api/attendee_types/read_for_tickets_purchases.php`),
                        fetch(`/api/companies/read_for_tickets_purchases.php`),
                        fetch(`/api/users/read_for_ticket_purchases.php?all=true`),
                        fetch('/api/payments/get_methods.php')
                    ]);

                     const ticketTypes = await ttRes.json();
                    const attendeeTypes = await atRes.json();
                    const companies = await compRes.json();
                    allUsers = (await usersRes.json()).users;
                    const paymentMethodsResult = await pmRes.json();

                    if(paymentMethodsResult.success && paymentMethodsCache.length === 0) {
                        paymentMethodsCache = paymentMethodsResult.data;
                    }

                    const ticketTypeOptions = ticketTypes.map(t => `<option value="${t.id}" data-price="${t.price}">${t.name}</option>`).join('');
                    const attendeeTypeOptions = attendeeTypes.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
                    const companyOptions = `<option value="">All Companies</option>` + companies.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
                    const companyOptionsRequired = `<option value="">Select Company...</option>` + companies.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
                    
                    document.getElementById('bulkTicketTypeSelect').innerHTML = ticketTypeOptions;
                    document.getElementById('bulkCsvTicketTypeSelect').innerHTML = ticketTypeOptions;
                    document.getElementById('bulkAttendeeTypeSelect').innerHTML = attendeeTypeOptions;
                    document.getElementById('bulkCsvAttendeeTypeSelect').innerHTML = attendeeTypeOptions;
                    document.getElementById('bulkFilterCompany').innerHTML = companyOptions;
                    document.getElementById('bulkCsvCompanySelect').innerHTML = companyOptionsRequired;
                    
                    document.getElementById('bulkTicketTypeSelect').dispatchEvent(new Event('change'));
                    document.getElementById('bulkCsvTicketTypeSelect').dispatchEvent(new Event('change'));

                    populatePaymentMethods(document.getElementById('bulkPaymentMethod'), paymentMethodsCache);
                    populatePaymentMethods(document.getElementById('bulkCsvPaymentMethod'), paymentMethodsCache);

                    renderBulkUserList();
                    bulkPurchaseModal.classList.remove('hidden');
                } catch (error) {
                    showToast('Failed to load required data for bulk purchase.', true);
                    console.error(error);
                } finally {
                    hideSpinner();
                }
            });

            document.getElementById('bulkChooseExisting').addEventListener('click', () => {
                bulkStep1.classList.add('hidden');
                bulkStepExisting.classList.remove('hidden');
            });
             document.getElementById('bulkChooseNew').addEventListener('click', () => {
                bulkStep1.classList.add('hidden');
                bulkStepNew.classList.remove('hidden');
            });
            bulkPurchaseModal.querySelectorAll('.bulk-back-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    bulkStepExisting.classList.add('hidden');
                    bulkStepNew.classList.add('hidden');
                    bulkStep1.classList.remove('hidden');
                });
            });

            document.getElementById('bulkTicketTypeSelect').addEventListener('change', e => {
                const price = e.target.options[e.target.selectedIndex].dataset.price || '0.00';
                document.getElementById('bulkTicketPrice').value = price;
            });
             document.getElementById('bulkCsvTicketTypeSelect').addEventListener('change', e => {
                const price = e.target.options[e.target.selectedIndex].dataset.price || '0.00';
                document.getElementById('bulkCsvTicketPrice').value = price;
            });

            document.getElementById('bulkFilterName').addEventListener('keyup', renderBulkUserList);
            document.getElementById('bulkFilterEmployed').addEventListener('change', renderBulkUserList);
            document.getElementById('bulkFilterCompany').addEventListener('change', renderBulkUserList);
            
            document.querySelectorAll('input[name="is_employed_csv"]').forEach(radio => {
                radio.addEventListener('change', e => {
                    const isEmployed = e.target.value === '1';
                    document.getElementById('bulkCsvCompanyWrapper').classList.toggle('hidden', !isEmployed);
                    document.getElementById('bulkCsvCompanySelect').required = isEmployed;
                    const link = isEmployed ? '/api/downloads/sample_employed.csv.php' : '/api/downloads/sample_unemployed.csv.php';
                    document.getElementById('downloadSampleCsvLink').href = link;
                });
            });

            document.getElementById('bulkExistingForm').addEventListener('submit', async e => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData.entries());
                data.user_ids = formData.getAll('user_ids[]');
                data.event_id = eventId;
                data.action = 'process_payment'; // This can be adjusted if needed

                if (data.user_ids.length === 0) {
                    return showToast('Please select at least one user.', true);
                }
                
                showSpinner();
                try {
                    const response = await fetch('/api/events/purchases/create_bulk_existing.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();
                    showToast(result.message, !result.success);
                    if (result.success) {
                        bulkPurchaseModal.classList.add('hidden');
                        await loadPurchases();
                    }
                } catch (error) {
                    showToast('An unexpected error occurred during bulk processing.', true);
                    console.error(error);
                } finally {
                    hideSpinner();
                }
            });

            document.getElementById('bulkCsvForm').addEventListener('submit', async e => {
                e.preventDefault();
                const formData = new FormData(e.target);
                formData.append('event_id', eventId);
                formData.append('action', 'process_payment'); // This can be adjusted if needed
                
                showSpinner();
                try {
                    const response = await fetch('/api/events/purchases/create_bulk_csv.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                        if (!result.success && result.message.length > 100) {
                         toast.style.width = '400px';
                    } else {
                        toast.style.width = '';
                    }
                    
                    showToast(result.message, !result.success); // Show toast regardless

                    if (result.success) {
                        bulkPurchaseModal.classList.add('hidden');
                        await loadPurchases();
                    }
                } catch (error) {
                    showToast('An unexpected error occurred during CSV processing.', true);
                    console.error(error);
                } finally {
                    hideSpinner();
                }
            });
        }
        
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.fixed').classList.add('hidden');
            });
        });
    };

    // --- INITIALIZATION ---
    if (eventId) {
        // We now load details and purchases sequentially to avoid multiple spinners
        const initializePage = async () => {
            await loadEventDetails();
            await loadPurchases();
            setupEventListeners();
        };
        initializePage();
    } else {
        document.querySelector('main').innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Event ID is missing from the URL.</span></div>`;
    }
});