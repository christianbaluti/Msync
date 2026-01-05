document.addEventListener('DOMContentLoaded', function() {
    // --- STATE & DOM ELEMENTS ---
    let currentPage = 1;
    let currentFilters = { search: '', status: '' };

    const tableContainer = document.getElementById('eventTableContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const spinner = document.getElementById('loading-spinner');

    const eventModal = document.getElementById('eventModal');
    const eventForm = document.getElementById('eventForm');
    const modalTitle = document.getElementById('modalTitle');
    const addEventBtn = document.getElementById('addEventBtn');
    const imagePreview = document.getElementById('imagePreview');
    const imageInput = document.getElementById('main_image');

    const viewEventModal = document.getElementById('viewEventModal');

    // --- Confirmation Modal Elements ---
    const confirmModal = document.getElementById('genericConfirmModal');
    const confirmModalTitle = document.getElementById('confirmModalTitle');
    const confirmModalMessage = document.getElementById('confirmModalMessage');
    const confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');
    const confirmModalCloseBtns = confirmModal.querySelectorAll('.close-modal'); // Select by class

    // --- HELPER FUNCTIONS ---
    const showSpinner = () => spinner.classList.remove('hidden', 'opacity-0');
    const hideSpinner = () => spinner.classList.add('hidden', 'opacity-0');

    const showToast = (message, isError = false) => {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        if (!toast || !toastMessage) return;

        toastMessage.textContent = message;
        toast.className = `fixed top-5 right-5 text-white py-2 px-4 rounded-lg shadow-md z-[110] transition-opacity duration-300 ease-out ${isError ? 'bg-red-600' : 'bg-green-700'}`; // Higher z-index for toast
        toast.classList.remove('hidden', 'opacity-0');
        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.classList.add('hidden'), 300); // Wait for fade out
        }, 3500);
    };

     const formatDateTime = (dateString) => {
        if (!dateString || dateString === '0000-00-00 00:00:00') return 'N/A'; // Handle invalid DB default
        try {
            // Adjust for potential timezone issues if needed, locale can be dynamic
            return new Date(dateString.replace(' ', 'T')).toLocaleString(navigator.language || 'en-GB', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true
            });
        } catch (e) {
            console.error("Error formatting date:", dateString, e);
            return 'Invalid Date';
        }
    };

    // --- DATA LOADING & RENDERING ---
     const loadEvents = async () => {
        showSpinner();
        // Ensure page is at least 1
        currentPage = Math.max(1, currentPage);
        const params = new URLSearchParams({ page: currentPage, ...currentFilters }).toString();
        
        try {
            const response = await fetch(`/api/events/read.php?${params}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
             // Validate response structure
            if (!data || typeof data !== 'object' || !Array.isArray(data.events)) {
                 throw new Error("Invalid data format received from server.");
            }
            renderTable(data.events);
            renderPagination(data.total_records || 0, data.limit || 10);
        } catch (error) {
            console.error("Fetch error:", error);
            tableContainer.innerHTML = `<div class="text-center py-8 text-red-500">Failed to load events. ${error.message}</div>`;
             paginationContainer.innerHTML = ''; // Clear pagination on error
        } finally {
            hideSpinner();
        }
    };

    const renderTable = (events) => {
        let tableHtml = `<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timeline</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tickets Sold</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th></tr></thead><tbody class="bg-white divide-y divide-gray-200">`;

        if (!events || events.length === 0) {
            tableHtml += `<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No events found matching your criteria.</td></tr>`;
        } else {
            events.forEach(event => {
                const statusBadge = event.status === 'published' ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Published</span>' : '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">Draft</span>';
                // Use relative path assuming uploads is accessible from web root
                const imageUrl = event.main_image ? `/uploads/events/${event.main_image}` : '/assets/img/placeholder.png';
                tableHtml += `<tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <img src="${imageUrl}" alt="${event.title || 'Event'}" class="h-12 w-16 object-cover rounded-md bg-gray-100" onerror="this.onerror=null;this.src='/assets/img/placeholder.png';">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <div>${event.title || 'Untitled Event'}</div>
                                    <div class="text-xs text-gray-500">${event.location || 'N/A'}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDateTime(event.start_datetime)}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${event.ticket_count || 0}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                    <button type="button" class="view-btn text-indigo-600 hover:text-indigo-900" data-id="${event.id}">View</button>
                                    <button type="button" class="edit-btn text-gray-600 hover:text-gray-900" data-id="${event.id}">Edit</button>
                                    <a class="text-gray-600 hover:text-gray-900" href="/events/manage?id=${event.id}">Manage</a>
                                    <button type="button" class="delete-btn text-red-600 hover:text-red-900" data-id="${event.id}">Delete</button>
                                </td>
                              </tr>`;
            });
        }
        tableHtml += `</tbody></table>`;
        tableContainer.innerHTML = tableHtml;
    };

     const renderPagination = (totalRecords, limit) => {
        const totalPages = Math.ceil(totalRecords / limit);
        paginationContainer.innerHTML = ''; // Clear previous pagination
        if (totalPages <= 1) return; // No pagination needed for 0 or 1 page

        let paginationHtml = `<div class="flex flex-1 justify-between sm:justify-end gap-x-2">`;

        // Previous Button
        paginationHtml += `<button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="relative inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>`;

        // Next Button
        paginationHtml += `<button ${currentPage >= totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="relative inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>`;

        paginationHtml += `</div>`;
        paginationContainer.innerHTML = paginationHtml;
    };

    // --- MODAL & FORM HANDLING ---
      const openEditModal = async (eventId) => {
        showSpinner();
        try {
            // Fetch simple data without relations for editing
            const response = await fetch(`/api/events/read_single.php?id=${eventId}&simple=true`);
            if (!response.ok) throw new Error('Failed to fetch event data for editing.');
            const event = await response.json();

            // Check if event data is valid
             if (!event || typeof event !== 'object' || !event.id) {
                 throw new Error('Invalid event data received.');
            }

            eventForm.reset(); // Clear previous data
            modalTitle.textContent = `Edit Event: ${event.title || 'Untitled'}`;
            // Use setAttribute for hidden fields if needed, or direct value setting
            eventForm.querySelector('#eventId').value = event.id;
            eventForm.querySelector('#title').value = event.title || '';
            eventForm.querySelector('#description').value = event.description || '';
            
             // Format datetime-local requires YYYY-MM-DDTHH:mm
             const formatForInput = (dtString) => {
                 if (!dtString || dtString === '0000-00-00 00:00:00') return '';
                 try {
                     const date = new Date(dtString.replace(' ', 'T'));
                     // Pad month, day, hour, minute with leading zeros if necessary
                     const year = date.getFullYear();
                     const month = (date.getMonth() + 1).toString().padStart(2, '0');
                     const day = date.getDate().toString().padStart(2, '0');
                     const hours = date.getHours().toString().padStart(2, '0');
                     const minutes = date.getMinutes().toString().padStart(2, '0');
                     return `${year}-${month}-${day}T${hours}:${minutes}`;
                 } catch (e) {
                     console.error("Error formatting date for input:", dtString, e);
                     return ''; // Return empty if formatting fails
                 }
             };

            eventForm.querySelector('#start_datetime').value = formatForInput(event.start_datetime);
            eventForm.querySelector('#end_datetime').value = formatForInput(event.end_datetime);
            eventForm.querySelector('#location').value = event.location || '';
            eventForm.querySelector('#status').value = event.status || 'draft'; // Default to draft if null/missing

            // Image preview logic
             imageInput.value = ''; // Clear the file input
            if (event.main_image) {
                imagePreview.src = `/uploads/events/${event.main_image}?t=${new Date().getTime()}`; // Add timestamp to bust cache
                imagePreview.classList.remove('hidden');
            } else {
                imagePreview.src = '';
                imagePreview.classList.add('hidden');
            }
            
            eventModal.classList.remove('hidden');
        } catch (error) {
            console.error("Error opening edit modal:", error);
            showToast(`Error: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    };

    // --- MODIFIED: openViewModal to include Check-ins ---
    const openViewModal = async (eventId) => {
        showSpinner();
        try {
            const response = await fetch(`/api/events/read_single.php?id=${eventId}`); // Fetch detailed data
            if (!response.ok) throw new Error(`Failed to load event details (Status: ${response.status}).`);
            const data = await response.json();

             // Basic validation of the response structure
            if (!data || typeof data !== 'object' || !data.event || typeof data.event !== 'object') {
                 throw new Error('Invalid or incomplete event details received.');
            }

            const event = data.event;
            
            // Populate Core Data safely
            viewEventModal.querySelector('#viewEventTitle').textContent = event.title || 'Untitled Event';
            
            const descriptionContainer = viewEventModal.querySelector('#viewEventDescription');
            if (descriptionContainer) {
                 if (event.description) {
                     descriptionContainer.innerHTML = (typeof marked !== 'undefined') 
                         ? marked.parse(event.description) 
                         : event.description.replace(/\n/g, '<br>'); // Simple fallback
                 } else {
                     descriptionContainer.textContent = 'No description provided.';
                 }
            }

            viewEventModal.querySelector('#viewEventLocation').textContent = event.location || 'N/A';
            viewEventModal.querySelector('#viewEventTimeline').textContent = `${formatDateTime(event.start_datetime)} - ${formatDateTime(event.end_datetime)}`;
            viewEventModal.querySelector('#viewEventCreatedBy').textContent = event.created_by_name || 'System';
            viewEventModal.querySelector('#viewEventCreatedAt').textContent = formatDateTime(event.created_at);
            viewEventModal.querySelector('#viewEventTicketsSold').textContent = data.tickets_sold != null ? data.tickets_sold.toString() : '0'; // Handle null
            viewEventModal.querySelector('#viewEventStatus').innerHTML = event.status === 'published' ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Published</span>' : '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">Draft</span>';
            viewEventModal.querySelector('#viewEventImage').src = event.main_image ? `/uploads/events/${event.main_image}?t=${new Date().getTime()}` : '/assets/img/placeholder.png'; // Cache bust
            viewEventModal.querySelector('#viewEventImage').onerror = function() { this.src='/assets/img/placeholder.png'; }; // Add onerror handler


            // Populate Tabs safely
            const schedulesContainer = viewEventModal.querySelector('#tab-content-schedules');
             if (schedulesContainer) {
                 schedulesContainer.innerHTML = data.schedules && data.schedules.length > 0
                    ? data.schedules.map(s => `<a href="/events/schedules/manage?id=${s.id}" class="block p-3 border-b hover:bg-gray-50 rounded-md transition duration-150 ease-in-out"><p class="font-semibold text-gray-800">${s.title || 'Untitled Schedule'} <span class="capitalize text-gray-500 font-normal text-xs">(${s.type || 'N/A'})</span></p><p class="text-xs text-gray-600 mt-1">${formatDateTime(s.start_datetime)} - ${formatDateTime(s.end_datetime)}</p></a>`).join('')
                    : '<p class="text-gray-500 p-4 text-center">No schedules defined for this event.</p>';
            }

            const ticketsContainer = viewEventModal.querySelector('#tab-content-tickets');
             if (ticketsContainer) {
                 ticketsContainer.innerHTML = data.ticket_types && data.ticket_types.length > 0
                    ? data.ticket_types.map(t => `<div class="p-3 border-b flex justify-between items-center"><div><p class="font-medium text-gray-800">${t.name || 'Unnamed Ticket'}</p><p class="text-xs text-gray-500">${t.member_type_name ? `(${t.member_type_name})` : '(General)'}</p></div><span class="font-semibold text-gray-800">K${parseFloat(t.price || 0).toFixed(2)}</span></div>`).join('')
                    : '<p class="text-gray-500 p-4 text-center">No ticket types configured.</p>';
            }
            
            const holdersTbody = viewEventModal.querySelector('#tab-content-holders tbody');
             if (holdersTbody) {
                 holdersTbody.innerHTML = data.ticket_holders && data.ticket_holders.length > 0
                    ? data.ticket_holders.map(h => `<tr class="hover:bg-gray-50 transition duration-150 ease-in-out"><td class="px-4 py-3 font-medium text-gray-900">${h.full_name || 'N/A'}</td><td class="px-4 py-3 text-gray-600">${h.email || h.phone || 'N/A'}</td><td class="px-4 py-3 text-gray-600">${h.ticket_type_name || 'N/A'}</td><td class="px-4 py-3 text-gray-500 font-mono text-xs">${h.ticket_code || 'N/A'}</td></tr>`).join('')
                    : '<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No attendees have purchased tickets yet.</td></tr>';
            }

            const companiesContainer = viewEventModal.querySelector('#tab-content-companies');
             if (companiesContainer) {
                 companiesContainer.innerHTML = data.company_purchases && data.company_purchases.length > 0
                    ? data.company_purchases.map(c => `<div class="p-3 border-b flex justify-between items-center hover:bg-gray-50 rounded-md transition duration-150 ease-in-out"><span class="font-medium text-gray-800">${c.name || 'Unnamed Company'}</span><span class="font-semibold text-sm text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">${c.ticket_count || 0} ticket(s)</span></div>`).join('')
                    : '<p class="p-4 text-center text-gray-500">No bulk company purchases recorded.</p>';
            }

            // --- NEW: Populate Check-ins Tab ---
            const checkinsTbody = viewEventModal.querySelector('#tab-content-checkins tbody');
            if (checkinsTbody) {
                 checkinsTbody.innerHTML = data.check_ins && data.check_ins.length > 0
                    ? data.check_ins.map(ci => `<tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                                    <td class="px-4 py-3 font-medium text-gray-900">${ci.full_name || 'N/A'}</td>
                                                    <td class="px-4 py-3 text-gray-600">${ci.merchandise_name || 'N/A'}</td>
                                                    <td class="px-4 py-3 text-gray-500 text-sm">${formatDateTime(ci.distributed_at)}</td>
                                                 </tr>`).join('')
                    : '<tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No check-ins recorded yet.</td></tr>';
            }
            // --- End of New Section ---

            // Ensure the first tab is active
            const firstTabButton = viewEventModal.querySelector('.tab-btn[data-tab="schedules"]');
            if (firstTabButton) firstTabButton.click(); // Programmatically click to set active state
            else { // Fallback if the button isn't found
                viewEventModal.querySelectorAll('.tab-btn').forEach(btn => {btn.classList.remove('border-indigo-500', 'text-indigo-600'); btn.classList.add('border-transparent', 'text-gray-500');});
                viewEventModal.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
                if(schedulesContainer) schedulesContainer.classList.remove('hidden'); // Show schedules by default
            }
            
            // Show the modal
            viewEventModal.classList.remove('hidden');

        } catch (error) {
            console.error("Error opening view modal:", error);
            showToast(`Error loading details: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    };
    
    // --- Function to handle deletion ---
    const deleteEvent = async (eventId) => {
        showSpinner();
        try {
            const response = await fetch('/api/events/delete_event.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: eventId }) // Send ID in JSON body
            });
            const result = await response.json();
            if (response.ok && result.success) {
                showToast(result.message || 'Event deleted successfully!');
                loadEvents(); // Reload the table
            } else {
                throw new Error(result.message || 'Failed to delete event.');
            }
        } catch (error) {
            showToast(error.message, true);
        } finally {
            hideSpinner();
        }
    };

    // --- EVENT LISTENERS ---
    
    // Filtering and Pagination
    let searchTimeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentFilters.search = searchInput.value;
            currentPage = 1;
            loadEvents();
        }, 300); // Wait 300ms after user stops typing
    });
    statusFilter.addEventListener('change', () => { currentFilters.status = statusFilter.value; currentPage = 1; loadEvents(); });
    paginationContainer.addEventListener('click', (e) => {
        const pageButton = e.target.closest('button[data-page]');
        if (pageButton && !pageButton.disabled) { currentPage = parseInt(pageButton.dataset.page); loadEvents(); }
    });
    
    // Table Actions (View, Edit, Delete)
    tableContainer.addEventListener('click', (e) => {
        const editButton = e.target.closest('.edit-btn');
        const viewButton = e.target.closest('.view-btn');
        const deleteButton = e.target.closest('.delete-btn');

        if (editButton) openEditModal(editButton.dataset.id);
        if (viewButton) openViewModal(viewButton.dataset.id);
        if (deleteButton) {
            const eventId = deleteButton.dataset.id;
            confirmModalTitle.textContent = 'Confirm Deletion';
            confirmModalMessage.textContent = `Are you sure you want to delete this event? This action cannot be undone. Schedules and tickets associated with it might also be affected.`;
            confirmModalConfirmBtn.textContent = 'Delete Event'; // Change button text
            confirmModalConfirmBtn.className = 'rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500'; // Ensure red color
            confirmModalConfirmBtn.dataset.id = eventId; // Store the ID on the confirm button
            confirmModal.classList.remove('hidden');
        }
    });

    // Add/Edit Modal Form Submission
    eventForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showSpinner();
        const formData = new FormData(eventForm);
        const eventId = formData.get('id');
        const url = eventId ? '/api/events/update.php' : '/api/events/create.php';
        
        // Basic date validation
        const start = formData.get('start_datetime');
        const end = formData.get('end_datetime');
        if (start && end && new Date(start) >= new Date(end)) {
            hideSpinner();
            showToast('End date/time must be after the start date/time.', true);
            return; // Stop submission
        }

        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            // Check if response is JSON, handle non-JSON responses gracefully
            let result;
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                result = await response.json();
            } else {
                 const textResponse = await response.text();
                 throw new Error(`Server returned non-JSON response: ${textResponse}`);
            }

            if (response.ok && result.success) {
                eventModal.classList.add('hidden');
                showToast(result.message || 'Event saved successfully!');
                 // If creating, maybe go to page 1? If updating, stay on current page.
                 if (!eventId) currentPage = 1; // Go to first page after creating
                loadEvents();
            } else { throw new Error(result.message || 'Failed to save event.'); }
        } catch (error) {
             console.error("Form submission error:", error);
            showToast(error.message, true);
        } finally {
            hideSpinner();
        }
    });

    // View Modal Tab Switching
     viewEventModal.addEventListener('click', (e) => {
        const tabButton = e.target.closest('.tab-btn');
        if (!tabButton) return;

        const tabName = tabButton.dataset.tab;
        
        // Update button styles
        viewEventModal.querySelectorAll('.tab-btn').forEach(btn => {
            const isActive = btn === tabButton;
            btn.classList.toggle('border-indigo-500', isActive); 
            btn.classList.toggle('text-indigo-600', isActive);
            btn.classList.toggle('border-transparent', !isActive); 
            btn.classList.toggle('text-gray-500', !isActive);
            btn.classList.toggle('hover:text-gray-700', !isActive); // Add hover effect for inactive tabs
             btn.classList.toggle('hover:border-gray-300', !isActive);
        });

        // Show/hide content panels
        viewEventModal.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('hidden', content.id !== `tab-content-${tabName}`);
        });
    });

    // --- Confirmation Modal Logic ---
    confirmModalConfirmBtn.addEventListener('click', () => {
        const eventId = confirmModalConfirmBtn.dataset.id;
        if (eventId) {
            deleteEvent(eventId);
        }
        confirmModal.classList.add('hidden'); // Hide modal after clicking confirm
    });

    confirmModalCloseBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            confirmModal.classList.add('hidden');
        });
    });
     // Close confirm modal if clicking outside
     confirmModal.addEventListener('click', (e) => {
        if (e.target === confirmModal) {
            confirmModal.classList.add('hidden');
        }
    });


    // General Modal Controls (Add/Edit, View)
    if (addEventBtn) {
        addEventBtn.addEventListener('click', () => {
            eventForm.reset();
            modalTitle.textContent = 'Create New Event';
            eventForm.querySelector('#eventId').value = ''; // Ensure ID is cleared
            imagePreview.src = ''; // Clear image preview src
            imagePreview.classList.add('hidden');
             imageInput.value = ''; // Clear file input
            eventModal.classList.remove('hidden');
        });
    }
    // Combined close logic for all modals with class 'close-modal-btn' or backdrop click
    document.querySelectorAll('.close-modal-btn, .close-modal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Find the closest parent modal and hide it
            const modalToClose = e.target.closest('.fixed[id$="Modal"]'); // Select modals ending with 'Modal'
             if (modalToClose && !modalToClose.classList.contains('hidden')) {
                 modalToClose.classList.add('hidden');
             }
        });
    });
    // Backdrop click for modals (excluding confirm modal, handled separately)
    document.querySelectorAll('#eventModal, #viewEventModal').forEach(modal => {
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); });
    });
    // Image Preview Update
    imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
                imagePreview.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        } else {
             // If no file is selected (e.g., user cancels), check if there was an existing image ID
             const eventId = eventForm.querySelector('#eventId').value;
             if (!eventId) { // Only hide if it's a new event
                 imagePreview.src = '';
                 imagePreview.classList.add('hidden');
             }
             // If editing, keep the existing preview shown until form submission replaces it
        }
    });

    // --- INITIAL LOAD ---
    loadEvents();
});