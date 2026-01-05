document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('id');

    if (!eventId) {
        alert('Error: Event ID is missing from the URL.');
        document.body.innerHTML = '<div class="p-8 text-center text-red-600"><strong>CRITICAL ERROR:</strong> Event ID not found. Please go back and select an event.</div>';
        return;
    }

    // --- DOM Elements ---
    const searchInput = document.getElementById('search-attendee');
    const resultsContainer = document.getElementById('attendee-results-container');
    const manageMerchBtn = document.getElementById('manage-merch-btn');
    const merchModal = document.getElementById('merch-modal');
    const merchForm = document.getElementById('merch-form');
    const merchListContainer = document.getElementById('merch-list-container');
    const eventHeaderContainer = document.getElementById('eventHeader');

    // --- State & Cache ---
    let availableMerchandise = []; // Cache for merchandise

    // --- Helper Functions ---
    const formatDate = (dateString, type = 'datetime') => {
        if (!dateString) return 'N/A';
        const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        if (type !== 'datetime') {
            delete options.hour;
            delete options.minute;
        }
        return new Date(dateString).toLocaleString('en-GB', options);
    };

    // (+) --- Modal Closing Logic (FIX) ---
    const closeMerchModal = () => merchModal.classList.add('hidden');

    merchModal.addEventListener('click', (e) => {
        // Closes the modal if the dark background is clicked
        if (e.target === merchModal) {
            closeMerchModal();
        }
    });
    
    // Add listener for all buttons with the closing class
    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', closeMerchModal);
    });

    // Add listener for the 'Escape' key
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && !merchModal.classList.contains('hidden')) {
            closeMerchModal();
        }
    });
    // --- End Modal Closing Logic ---

    // (-) QR SCANNER LOGIC HAS BEEN COMPLETELY REMOVED

    // --- Render Functions ---
    const renderHeader = (details) => {
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
            <div class="mt-4 prose prose-sm max-w-none text-slate-600">${details.description || 'No description provided.'}</div>
        `;
    };
    
    const renderAttendees = (result) => {
        if (!result.success || result.data.length === 0) {
            resultsContainer.innerHTML = `<div class="text-center py-4 text-gray-500">No attendees found for "${searchInput.value}".</div>`;
            return;
        }
        resultsContainer.innerHTML = result.data.map(attendee => {
            const collectedMerchIds = attendee.collected_merch_ids || [];
            const uncollectedMerch = availableMerchandise.filter(m => !collectedMerchIds.includes(parseInt(m.id)));
            return `
                <div class="bg-white shadow rounded-lg p-4" id="attendee-${attendee.ticket_id}">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-lg font-bold text-indigo-600">${attendee.attendee_name}</p>
                            <p class="text-sm text-gray-600">${attendee.company_name || 'No company specified'}</p>
                            <p class="text-xs text-gray-400 mt-1">Ticket Code: ${attendee.ticket_code}</p>
                        </div>
                        <div>
                            ${attendee.is_checked_in
                                ? `<span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700">Checked In</span>`
                                : `<button class="check-in-btn rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500" data-ticket-id="${attendee.ticket_id}">Check In</button>`
                            }
                        </div>
                    </div>
                    <div class="mt-4 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-800">Merchandise</h4>
                        <div class="mt-2">
                            ${collectedMerchIds.length > 0
                                ? `<div class="mb-2"><strong>Collected:</strong> ${availableMerchandise.filter(m => collectedMerchIds.includes(parseInt(m.id))).map(m => `<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">${m.name}</span>`).join(' ')}</div>`
                                : '<p class="text-xs text-gray-500">No items collected yet.</p>'
                            }

                            ${uncollectedMerch.length > 0
                                ? `<div class="space-x-2">${uncollectedMerch.map(merch =>
                                    `<button class="distribute-merch-btn rounded bg-white px-2 py-1 text-xs font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50" data-ticket-id="${attendee.ticket_id}" data-merch-id="${merch.id}">Give ${merch.name}</button>`
                                  ).join('')}</div>`
                                : ''
                            }
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    };

    const renderMerchItem = (merch) => `
        <li class="py-3 flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-900 truncate">${merch.name}</p>
                <p class="text-sm text-slate-500 truncate">Qty: ${merch.total_quantity || 'Unlimited'}</p>
            </div>
            <div class="flex-shrink-0 space-x-3">
                <button class="edit-merch-btn text-sm font-semibold text-indigo-600 hover:text-indigo-500" data-id="${merch.id}">Edit</button>
                <button class="delete-merch-btn text-sm font-semibold text-red-600 hover:text-red-500" data-id="${merch.id}">Delete</button>
            </div>
        </li>
    `;


    // --- Search Functionality ---
    let searchTimeout;
    searchInput.addEventListener('input', () => { 
        clearTimeout(searchTimeout);
        const query = searchInput.value.trim();
        resultsContainer.innerHTML = `<div class="text-center py-4 text-gray-500">Searching...</div>`;
        searchTimeout = setTimeout(() => searchAttendees(query), 300);
    });

    const searchAttendees = async (query) => {
        if (query.length < 3 && query.length !== 0) {
            resultsContainer.innerHTML = '<div class="text-center py-4 text-gray-500">Please enter at least 3 characters.</div>';
            return;
        }
         if (query === '') {
            resultsContainer.innerHTML = `<div class="text-center py-10 text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" /></svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Search for attendees</h3>
                <p class="mt-1 text-sm text-gray-500">Use the search box to find an attendee.</p>
            </div>`;
            return;
        }

        try {
            const response = await fetch(`/api/events/attendees/search.php?event_id=${eventId}&q=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('Network response was not ok.');
            const result = await response.json();
            renderAttendees(result);
        } catch (error) {
            resultsContainer.innerHTML = `<div class="text-center py-4 text-red-500">Error: Could not fetch attendees.</div>`;
            console.error('Search error:', error);
        }
    };
    

    // --- Attendee Actions (Check-in, Distribute Merch) ---
    resultsContainer.addEventListener('click', (e) => {
        const checkInBtn = e.target.closest('.check-in-btn');
        const distributeBtn = e.target.closest('.distribute-merch-btn');
        if (checkInBtn) {
            checkInAttendee(checkInBtn.dataset.ticketId, checkInBtn);
        }
        if (distributeBtn) {
            distributeMerch(distributeBtn.dataset.ticketId, distributeBtn.dataset.merchId, distributeBtn);
        }
    });

    const checkInAttendee = async (ticketId, button) => {
        button.disabled = true;
        button.textContent = 'Checking in...';
        try {
            const response = await fetch('/api/events/checkin/checkin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id: ticketId })
            });
            const result = await response.json();
            if (result.success) {
                button.outerHTML = `<span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700">Checked In</span>`;
            } else {
                throw new Error(result.message || 'Failed to check in.');
            }
        } catch (error) {
            alert('Error: ' + error.message);
            button.disabled = false;
            button.textContent = 'Check In';
        }
    };

    const distributeMerch = async (ticketId, merchId, button) => {
        button.disabled = true;
        try {
            const response = await fetch('/api/events/checkin/distribute_merch.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id: ticketId, merch_id: merchId })
            });
            const result = await response.json();
            if (result.success) {
                searchAttendees(searchInput.value.trim()); // Refresh to update UI
            } else {
                throw new Error(result.message || 'Failed to distribute.');
            }
        } catch (error) {
            alert('Error: ' + error.message);
            button.disabled = false;
        }
    };


    // --- Merchandise Modal Logic ---
    const openMerchModal = () => {
        merchModal.classList.remove('hidden');
        merchForm.reset();
        merchForm.querySelector('#merch-id').value = '';
        merchModal.querySelector('#modalTitle').textContent = 'Add New Merchandise';
        loadMerchandise();
    };

    manageMerchBtn.addEventListener('click', openMerchModal);
    
    const loadMerchandise = async () => {
        merchListContainer.innerHTML = '<p class="text-sm text-gray-500">Loading...</p>';
        try {
            const response = await fetch(`/api/events/merchandise/read.php?event_id=${eventId}`);
            if (!response.ok) throw new Error('Network response was not ok.');
            
            const result = await response.json();
            if(result.success) {
                availableMerchandise = result.data || [];
                merchListContainer.innerHTML = availableMerchandise.length > 0 ?
                    `<ul class="divide-y divide-slate-200">${availableMerchandise.map(renderMerchItem).join('')}</ul>` :
                    '<p class="text-sm text-gray-500">No merchandise items have been added yet.</p>';
            } else {
                throw new Error(result.message || 'Failed to load merchandise.');
            }
        } catch (error) {
            merchListContainer.innerHTML = `<p class="text-red-500">${error.message}</p>`;
        }
    };

    merchForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(merchForm);
        const data = Object.fromEntries(formData.entries());
        const merchId = data.id;
        const url = merchId ? '/api/events/merchandise/update.php' : '/api/events/merchandise/create.php';
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                merchForm.reset();
                merchForm.querySelector('#merch-id').value = '';
                merchModal.querySelector('#modalTitle').textContent = 'Add New Merchandise';
                loadMerchandise();
            } else {
                throw new Error(result.message || 'Failed to save merchandise.');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
    
    merchListContainer.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-merch-btn');
        const deleteBtn = e.target.closest('.delete-merch-btn');
        if (editBtn) {
            const merchId = editBtn.dataset.id;
            const item = availableMerchandise.find(m => m.id == merchId);
            if (item) {
                merchModal.querySelector('#modalTitle').textContent = `Editing "${item.name}"`;
                merchForm.querySelector('#merch-id').value = item.id;
                merchForm.querySelector('#merch-name').value = item.name;
                merchForm.querySelector('#merch-description').value = item.description;
                merchForm.querySelector('#merch-quantity').value = item.total_quantity;
                merchForm.querySelector('#merch-name').focus();
            }
        }
        if (deleteBtn) {
            const merchId = deleteBtn.dataset.id;
            if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                deleteMerchandise(merchId);
            }
        }
    });

    const deleteMerchandise = async (merchId) => {
         try {
            const response = await fetch('/api/events/merchandise/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: merchId })
            });
            const result = await response.json();
            if (result.success) {
                loadMerchandise();
            } else {
                throw new Error(result.message || 'Failed to delete item.');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    };

    // --- INITIALIZATION ---
    const initializePage = async () => {
        try {
            const [detailsResponse, merchResponse] = await Promise.all([
                fetch(`/api/events/read_details.php?id=${eventId}`),
                fetch(`/api/events/merchandise/read.php?event_id=${eventId}`)
            ]);
            if (!detailsResponse.ok || !merchResponse.ok) {
                throw new Error('Failed to fetch initial page data.');
            }
            const eventDetails = await detailsResponse.json();
            const merchResult = await merchResponse.json();
            if (eventDetails) {
                renderHeader(eventDetails.details);
            }
            if (merchResult.success && merchResult.data) {
                availableMerchandise = merchResult.data;
            }
        } catch (error) {
            console.error('Initialization failed:', error);
            eventHeaderContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">Could not load event details. Please try again.</div>`;
        }
    };

    initializePage();
});