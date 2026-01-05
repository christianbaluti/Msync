document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('id');

    // --- UI ELEMENTS ---
    const spinner = document.getElementById('loading-spinner');
    const toast = document.getElementById('toast');

    // Generic Confirmation Modal Elements
    const genericConfirmModal = document.getElementById('genericConfirmModal');
    const confirmModalTitle = document.getElementById('confirmModalTitle');
    const confirmModalMessage = document.getElementById('confirmModalMessage');
    const confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');

    // Schedule Elements
    const addScheduleBtn = document.getElementById('addScheduleBtn');
    const scheduleModal = document.getElementById('scheduleModal');
    const scheduleForm = document.getElementById('scheduleForm');
    
    // Ticket Type Elements
    const manageTicketTypesBtn = document.getElementById('manageTicketTypesBtn');
    const ticketTypesManageModal = document.getElementById('ticketTypesManageModal');
    const ticketTypesList = document.getElementById('ticketTypesList');
    const addNewTicketTypeBtn = document.getElementById('addNewTicketTypeBtn');
    const ticketTypeModal = document.getElementById('ticketTypeModal');
    const ticketTypeForm = document.getElementById('ticketTypeForm');

    // Attendee Type Elements
    const manageAttendeeTypesBtn = document.getElementById('manageAttendeeTypesBtn');
    const attendeeTypesManageModal = document.getElementById('attendeeTypesManageModal');
    const attendeeTypesList = document.getElementById('attendeeTypesList');
    const addAttendeeTypeForm = document.getElementById('addAttendeeTypeForm');

    // --- NEW STREAMING ELEMENTS ---
    const manageStreamBtn = document.getElementById('manageStreamBtn');
    const streamManageModal = document.getElementById('streamManageModal');
    const streamForm = document.getElementById('streamForm');
    const deleteStreamBtn = document.getElementById('deleteStreamBtn');

    // --- CACHE & STATE ---
    let allFacilitatorsCache = [];
    let membershipTypesCache = [];
    let confirmAction = null; // Stores the function to run on confirmation

    // --- UI HELPER FUNCTIONS ---
    const showSpinner = () => spinner?.classList.remove('hidden');
    const hideSpinner = () => spinner?.classList.add('hidden');

    const showToast = (message, isError = false) => {
        if (!toast) return;
        toast.textContent = message;
        toast.className = `fixed top-5 right-5 z-[101] text-white py-2 px-4 rounded-lg shadow-md ${isError ? 'bg-red-600' : 'bg-green-700'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    };

    const formatDate = (dateString, type = 'datetime') => {
        if (!dateString) return 'N/A';
        const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        if (type !== 'datetime') {
            delete options.hour;
            delete options.minute;
        }
        return new Date(dateString).toLocaleString('en-GB', options);
    };
    
    // Helper to format datetime-local input
    const formatToDateTimeLocal = (isoString) => {
        if (!isoString) return '';
        const date = new Date(isoString);
        // Adjust for timezone offset
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        // Return in 'YYYY-MM-DDTHH:mm' format
        return date.toISOString().slice(0, 16);
    };

    const getScheduleTypeBadge = (type) => {
        const colors = {
            'general': 'bg-slate-100 text-slate-700', 'voting': 'bg-blue-100 text-blue-700',
            'training': 'bg-purple-100 text-purple-700', 'meal': 'bg-amber-100 text-amber-700',
            'awards': 'bg-yellow-100 text-yellow-700'
        };
        const colorClass = colors[type] || 'bg-gray-100 text-gray-700';
        return `<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${colorClass}">${type.charAt(0).toUpperCase() + type.slice(1)}</span>`;
    };

    // --- **NEW** GENERIC CONFIRMATION MODAL LOGIC ---
    const showGenericConfirm = (config) => {
        confirmModalTitle.textContent = config.title || 'Confirm Action';
        confirmModalMessage.innerHTML = config.message || 'Are you sure? This action cannot be undone.';
        confirmModalConfirmBtn.textContent = config.confirmText || 'Confirm';

        // Store the action to be executed when the confirm button is clicked
        confirmAction = config.onConfirm;

        genericConfirmModal.classList.remove('hidden');
    };

    confirmModalConfirmBtn.addEventListener('click', () => {
        if (typeof confirmAction === 'function') {
            confirmAction();
        }
        genericConfirmModal.classList.add('hidden');
        confirmAction = null; // Reset action
    });
    
    // --- MAIN INITIALIZATION & DATA REFRESH ---
    const initializePage = async () => {
        if (!eventId) {
            document.querySelector('main').innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Event ID is missing from the URL.</span></div>`;
            return;
        }
        showSpinner();
        try {
            const response = await fetch(`/api/events/read_details.php?id=${eventId}`);
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const data = await response.json();
            
            renderHeader(data.details);
            renderSchedulesTable(data.schedules);
            
            document.querySelectorAll('input[name="event_id"]').forEach(el => el.value = eventId);
        } catch (error) {
            console.error("Initialization failed:", error);
            showToast(`Error loading event data: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    };

    // --- RENDER FUNCTIONS ---
    const renderHeader = (details) => {
        const header = document.getElementById('eventHeader');
        if (!header || !details) return;
        const statusBadge = details.status === 'published' 
            ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Published</span>'
            : '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">Draft</span>';

        header.innerHTML = `
            <div class="md:flex md:items-center md:justify-between"><div class="min-w-0 flex-1"><h1 class="text-3xl font-bold leading-tight text-slate-900 sm:truncate">${details.title}</h1><div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6 text-slate-500"><div class="mt-2 flex items-center text-sm"><svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5A.75.75 0 014 6.75v8.5a1.25 1.25 0 001.25 1.25h10.5a1.25 1.25 0 001.25-1.25v-8.5a.75.75 0 01-1.5 0V15a.75.75 0 01-1.5 0V7.5a.75.75 0 01-1.5 0V15a.75.75 0 01-1.5 0V7.5a.75.75 0 01-1.5 0V15a.75.75 0 01-1.5 0V7.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>${formatDate(details.start_datetime)} to ${formatDate(details.end_datetime)}</div><div class="mt-2 flex items-center text-sm"><svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.274 1.765 11.842 11.842 0 00.979.57c.134.067.267.127.38.177.028.012.054.023.079.034l.006.002zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>${details.location || 'N/A'}</div><div class="mt-2 flex items-center text-sm">${statusBadge}</div></div></div></div><div class="mt-4 prose prose-sm max-w-none text-slate-600">${details.description || 'No description provided.'}</div>`;
    };
    
    const renderSchedulesTable = (schedules) => {
        const container = document.getElementById('schedulesContainer');
        if (!container) return;
        let tableContent = `<table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Title</th><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Type</th><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Duration</th><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Facilitators</th><th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th></tr></thead><tbody class="bg-white divide-y divide-slate-200">`;
        if (!schedules || schedules.length === 0) {
            tableContent += `<tr><td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500">No schedule items have been added.</td></tr>`;
        } else {
            schedules.forEach(s => {
                const manageLink = s.type !== 'general' ? `<a href="/events/schedules/manage?id=${s.id}" class="text-indigo-600 hover:text-indigo-900">Manage</a>` : ``;
                tableContent += `<tr><td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-slate-900">${s.title}</div><div class="text-sm text-slate-500 truncate max-w-xs">${s.description || ''}</div></td><td class="px-6 py-4 whitespace-nowrap text-sm">${getScheduleTypeBadge(s.type)}</td><td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><div>${formatDate(s.start_datetime)}</div><div class="text-xs text-slate-400">to ${formatDate(s.end_datetime)}</div></td><td class="px-6 py-4 text-sm text-slate-500">${s.facilitator_names || 'N/A'}</td><td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">${manageLink}<button type="button" class="edit-schedule-btn text-slate-600 hover:text-slate-900" data-id="${s.id}">Edit</button><button type="button" class="delete-schedule-btn text-red-600 hover:text-red-900" data-id="${s.id}">Delete</button></td></tr>`;
            });
        }
        tableContent += `</tbody></table>`;
        container.innerHTML = tableContent;
    };

    // --- GENERIC API HANDLER ---
    const setupApiInteraction = async (endpoint, options, successCallback) => {
        showSpinner();
        let shouldHideSpinner = true;
        try {
            const response = await fetch(endpoint, options);
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'An API error occurred.');
            
            showToast(result.message, !result.success);

            if (result.success && successCallback) {
                shouldHideSpinner = false;
                successCallback(result);
            }
        } catch (error) {
            showToast(error.message, true);
            console.error(error);
        } finally {
            if (shouldHideSpinner) {
                hideSpinner();
            }
        }
    };
    
    // --- SCHEDULE MANAGEMENT ---
    const openScheduleModal = async (scheduleId = null) => {
        scheduleForm.reset();
        document.querySelector('#facilitatorSearch').value = '';
        document.querySelector('#scheduleForm input[name="event_id"]').value = eventId;
        const modalTitle = document.getElementById('scheduleModalTitle');
        const facilitatorSelect = document.getElementById('facilitatorSelect');
        showSpinner();
        try {
            if (allFacilitatorsCache.length === 0) {
                const usersRes = await fetch('/api/users/read.php?role=admin');
                const { users } = await usersRes.json();
                if (Array.isArray(users)) { allFacilitatorsCache = users; }
            }
            facilitatorSelect.innerHTML = allFacilitatorsCache.map(u => `<option value="${u.id}">${u.full_name}</option>`).join('');
            
            if (scheduleId) {
                modalTitle.textContent = "Edit Schedule Item";
                const scheduleRes = await fetch(`/api/events/schedules/read_single.php?id=${scheduleId}`);
                const scheduleData = await scheduleRes.json();
                for (const key in scheduleData) {
                    const el = scheduleForm.elements[key];
                    if (el) {
                        if (key === 'facilitators' && Array.isArray(scheduleData.facilitators)) {
                           Array.from(el.options).forEach(opt => { opt.selected = scheduleData.facilitators.includes(parseInt(opt.value)); });
                        } else if ((key === 'start_datetime' || key === 'end_datetime') && scheduleData[key]) {
                            el.value = formatToDateTimeLocal(scheduleData[key]);
                        } else {
                            el.value = scheduleData[key];
                        }
                    }
                }
            } else {
                modalTitle.textContent = "Add Schedule Item";
            }
            scheduleModal.classList.remove('hidden');
        } catch(error) {
            showToast('Could not open schedule manager.', true);
            console.error(error);
        } finally {
            hideSpinner();
        }
    };
    
    document.getElementById('facilitatorSearch').addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const facilitatorSelect = document.getElementById('facilitatorSelect');
        const selectedValues = new Set(Array.from(facilitatorSelect.selectedOptions).map(opt => opt.value));
        const filteredFacilitators = allFacilitatorsCache.filter(user => user.full_name.toLowerCase().includes(searchTerm));
        facilitatorSelect.innerHTML = filteredFacilitators.map(u => `<option value="${u.id}" ${selectedValues.has(String(u.id)) ? 'selected' : ''}>${u.full_name}</option>`).join('');
    });

    scheduleForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        data.facilitators = formData.getAll('facilitators[]');
        const action = data.id ? 'update_schedule' : 'create';
        setupApiInteraction(`/api/events/schedules/${action}.php`, {
            method: 'POST', body: JSON.stringify(data), headers: { 'Content-Type': 'application/json' }
        }, () => {
            scheduleModal.classList.add('hidden');
            // This line will force the entire page to reload
            location.reload(); 
        });
    });

    addScheduleBtn?.addEventListener('click', () => openScheduleModal());
    
    document.getElementById('schedulesContainer').addEventListener('click', (e) => {
        const scheduleId = e.target.dataset.id;
        if (!scheduleId) return;

        if (e.target.classList.contains('edit-schedule-btn')) {
            openScheduleModal(scheduleId);
        }
        if (e.target.classList.contains('delete-schedule-btn')) {
            // **UPDATED:** Use generic confirmation modal
            showGenericConfirm({
                title: 'Confirm Deletion',
                message: 'Are you sure you want to delete this schedule item? This action cannot be undone.',
                confirmText: 'Delete',
                onConfirm: () => {
                    setupApiInteraction('/api/events/schedules/delete_schedule.php', {
                        method: 'POST', 
                        body: JSON.stringify({ id: scheduleId }), 
                        headers: { 'Content-Type': 'application/json' }
                    }, () => {
                        initializePage();
                    });
                }
            });
        }
    });

    // --- TICKET TYPE MANAGEMENT ---
    const loadTicketTypes = async () => {
        showSpinner();
        try {
            const response = await fetch(`/api/events/ticket_types/read.php?event_id=${eventId}`);
            const types = await response.json();
            let tableHTML = `<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name / For</th><th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Price</th><th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Sold</th><th class="relative px-6 py-3"></th></tr></thead><tbody class="bg-white divide-y divide-slate-200"></tbody></table></div>`;
            ticketTypesList.innerHTML = tableHTML;
            const tbody = ticketTypesList.querySelector('tbody');

            if (types.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-4 text-center text-sm text-slate-500">No ticket types created yet.</td></tr>`;
            } else {
                types.forEach(t => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">${t.name}<div class="text-xs text-slate-500">${t.member_type_name || 'General Admission'}</div></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">K${parseFloat(t.price).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">${t.tickets_sold}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                <button class="edit-ticket-type-btn text-indigo-600 hover:text-indigo-900" data-id="${t.id}">Edit</button>
                                <button class="delete-ticket-type-btn text-red-600 hover:text-red-900" data-id="${t.id}">Delete</button>
                            </td>
                        </tr>`;
                });
            }
        } catch(error) {
            showToast('Could not load ticket types.', true);
            console.error(error);
        } finally {
            hideSpinner();
        }
    };
    
    const openTicketTypeModal = async (ticketTypeId = null) => {
        ticketTypeForm.reset();
        document.querySelector('#ticketTypeForm input[name="event_id"]').value = eventId;
        const modalTitle = document.getElementById('ticketTypeModalTitle');
        const memberTypeSelect = document.getElementById('ticketMemberType');

        showSpinner();
        try {
            if (membershipTypesCache.length === 0) {
                const res = await fetch('/api/membership_types/read.php');
                const types = await res.json();
                if (Array.isArray(types)) membershipTypesCache = types;
            }
            memberTypeSelect.options.length = 1; // Keep "General"
            membershipTypesCache.forEach(mt => memberTypeSelect.add(new Option(mt.name, mt.id)));

            if (ticketTypeId) {
                modalTitle.textContent = "Edit Ticket Type";
                const res = await fetch(`/api/events/ticket_types/read_single.php?id=${ticketTypeId}`);
                const data = await res.json();
                
                document.querySelector('#ticketTypeForm input[name="id"]').value = data.id;
                document.querySelector('#ticketTypeForm input[name="name"]').value = data.name;
                document.querySelector('#ticketTypeForm input[name="price"]').value = data.price;
                document.querySelector('#ticketTypeForm select[name="member_type_id"]').value = data.member_type_id || '0';
            } else {
                modalTitle.textContent = "Add Ticket Type";
            }
            ticketTypeModal.classList.remove('hidden');
        } catch(error) {
            showToast("Error preparing ticket type form.", true);
            console.error(error);
        } finally {
            hideSpinner();
        }
    };

    manageTicketTypesBtn?.addEventListener('click', () => {
        loadTicketTypes();
        ticketTypesManageModal.classList.remove('hidden');
    });
    
    addNewTicketTypeBtn?.addEventListener('click', () => openTicketTypeModal());

    ticketTypeForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        const action = data.id ? 'update' : 'create';
        
        setupApiInteraction(`/api/events/ticket_types/${action}.php`, {
            method: 'POST', body: JSON.stringify(data), headers: { 'Content-Type': 'application/json' }
        }, () => {
            ticketTypeModal.classList.add('hidden');
            loadTicketTypes();
        });
    });
    
    ticketTypesList.addEventListener('click', (e) => {
        const ticketId = e.target.dataset.id;
        if (!ticketId) return;

        if (e.target.classList.contains('edit-ticket-type-btn')) {
            openTicketTypeModal(ticketId);
        }
        if (e.target.classList.contains('delete-ticket-type-btn')) {
             // **UPDATED:** Use generic confirmation modal instead of window.confirm
            showGenericConfirm({
                title: 'Delete Ticket Type',
                message: 'Are you sure you want to delete this ticket type? This cannot be undone.',
                confirmText: 'Delete',
                onConfirm: () => {
                    setupApiInteraction('/api/events/ticket_types/delete.php', {
                        method: 'POST',
                        body: JSON.stringify({ id: ticketId }),
                        headers: { 'Content-Type': 'application/json' }
                    }, () => loadTicketTypes());
                }
            });
        }
    });

    // --- ATTENDEE TYPE MANAGEMENT ---
    const loadAttendeeTypes = async () => {
        showSpinner();
        try {
            const response = await fetch(`/api/attendee_types/read.php?event_id=${eventId}`);
            const types = await response.json();
            let tableHTML = `<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name & Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Attendees (This Event)</th>
                <th class="relative px-6 py-3"></th></tr></thead><tbody class="bg-white divide-y divide-slate-200"></tbody></table></div>`;
            attendeeTypesList.innerHTML = tableHTML;
            const tbody = attendeeTypesList.querySelector('tbody');
            if (types.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-4 text-center text-sm text-slate-500">No global attendee types found.</td></tr>`;
            } else {
                types.forEach(t => {
                    const row = document.createElement('tr');
                    row.dataset.id = t.id;
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                            <span class="view-mode">${t.name}</span>
                            <input type="text" value="${t.name}" class="edit-mode hidden form-input">
                            <div class="text-xs text-slate-500 view-mode mt-1">${t.description || ''}</div>
                            <input type="text" value="${t.description || ''}" class="edit-mode hidden form-input mt-1">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">${t.attendee_count}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                            <button class="edit-btn text-indigo-600 hover:text-indigo-900 view-mode">Edit</button>
                            <button class="save-btn hidden text-green-600 hover:text-green-900 edit-mode">Save</button>
                            <button class="cancel-btn hidden text-slate-600 hover:text-slate-900 edit-mode">Cancel</button>
                            <button class="delete-btn text-red-600 hover:text-red-900 view-mode">Delete</button>
                        </td>`;
                    tbody.appendChild(row);
                });
            }
        } catch (error) {
            showToast('Could not load attendee types.', true);
            console.error(error);
        } finally {
            hideSpinner();
        }
    };

    manageAttendeeTypesBtn?.addEventListener('click', () => {
        loadAttendeeTypes();
        attendeeTypesManageModal.classList.remove('hidden');
    });
    
    addAttendeeTypeForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        setupApiInteraction('/api/attendee_types/create.php', {
            method: 'POST', body: JSON.stringify(data), headers: { 'Content-Type': 'application/json' }
        }, () => { e.target.reset(); loadAttendeeTypes(); });
    });

    attendeeTypesList.addEventListener('click', (e) => {
        const row = e.target.closest('tr');
        if (!row) return;
        const id = row.dataset.id;
        const inputs = row.querySelectorAll('input[type="text"]');

        if (e.target.classList.contains('edit-btn')) {
            row.querySelectorAll('.view-mode').forEach(el => el.classList.add('hidden'));
            row.querySelectorAll('.edit-mode').forEach(el => el.classList.remove('hidden'));
        }
        if (e.target.classList.contains('cancel-btn')) {
            row.querySelectorAll('.view-mode').forEach(el => el.classList.remove('hidden'));
            row.querySelectorAll('.edit-mode').forEach(el => el.classList.add('hidden'));
        }
        if (e.target.classList.contains('save-btn')) {
            setupApiInteraction('/api/attendee_types/update.php', {
                method: 'POST',
                body: JSON.stringify({ id, name: inputs[0].value, description: inputs[1].value }),
                headers: { 'Content-Type': 'application/json' }
            }, () => loadAttendeeTypes());
        }
        if (e.target.classList.contains('delete-btn')) {
            // **UPDATED:** Use generic confirmation modal
            showGenericConfirm({
                title: 'Delete Attendee Type',
                message: 'Are you sure? Deleting a <strong>global</strong> attendee type cannot be undone and may affect other events.',
                confirmText: 'Delete',
                onConfirm: () => {
                    setupApiInteraction('/api/attendee_types/delete.php', {
                        method: 'POST',
                        body: JSON.stringify({ id }),
                        headers: { 'Content-Type': 'application/json' }
                    }, () => loadAttendeeTypes());
                }
            });
        }
    });

    // --- NEW STREAMING MANAGEMENT ---

    const openStreamModal = async () => {
        streamForm.reset();
        document.querySelector('#streamForm input[name="event_id"]').value = eventId;
        deleteStreamBtn.classList.add('hidden'); // Hide delete btn by default
        showSpinner();

        try {
            const response = await fetch(`/api/events/streaming/read.php?event_id=${eventId}`);
            const data = await response.json();

            if (data && data.id) {
                // Populate form with existing data
                streamForm.elements['id'].value = data.id;
                streamForm.elements['youtube_video_id'].value = data.youtube_video_id || '';
                streamForm.elements['youtube_embed_url'].value = data.youtube_embed_url || '';
                streamForm.elements['stream_key'].value = data.stream_key || '';
                streamForm.elements['privacy_status'].value = data.privacy_status || 'unlisted';
                streamForm.elements['is_live'].value = data.is_live || '0';
                streamForm.elements['started_at'].value = formatToDateTimeLocal(data.started_at);
                streamForm.elements['ended_at'].value = formatToDateTimeLocal(data.ended_at);
                
                deleteStreamBtn.classList.remove('hidden'); // Show delete btn if data exists
            }
            
            streamManageModal.classList.remove('hidden');
        } catch (error) {
            showToast('Could not load streaming configuration.', true);
            console.error(error);
        } finally {
            hideSpinner();
        }
    };

    manageStreamBtn?.addEventListener('click', openStreamModal);

    streamForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        // Handle auto-populating embed URL if empty
        if (!data.youtube_embed_url && data.youtube_video_id) {
            data.youtube_embed_url = `https://www.youtube.com/embed/${data.youtube_video_id}`;
            streamForm.elements['youtube_embed_url'].value = data.youtube_embed_url;
        }

        setupApiInteraction('/api/events/streaming/save.php', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'Content-Type': 'application/json' }
        }, (result) => {
            if (result.new_id) {
                streamForm.elements['id'].value = result.new_id; // Store new ID if created
            }
            deleteStreamBtn.classList.remove('hidden');
            streamManageModal.classList.add('hidden');
            // No need to reload the whole page, just close modal
            hideSpinner();
        });
    });

    deleteStreamBtn?.addEventListener('click', () => {
        showGenericConfirm({
            title: 'Remove Stream Configuration',
            message: 'Are you sure you want to remove the streaming configuration for this event?',
            confirmText: 'Remove',
            onConfirm: () => {
                const streamId = streamForm.elements['id'].value;
                setupApiInteraction('/api/events/streaming/delete.php', {
                    method: 'POST',
                    body: JSON.stringify({ id: streamId, event_id: eventId }), // Send both for safety
                    headers: { 'Content-Type': 'application/json' }
                }, () => {
                    streamForm.reset();
                    streamForm.elements['event_id'].value = eventId;
                    deleteStreamBtn.classList.add('hidden');
                    streamManageModal.classList.add('hidden');
                    hideSpinner();
                });
            }
        });
    });

    // --- UNIVERSAL CLOSE MODAL LISTENERS ---
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.fixed[id$="Modal"]').classList.add('hidden');
        });
    });

    // --- INITIALIZE THE PAGE ---
    initializePage();
});