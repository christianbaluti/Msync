document.addEventListener('DOMContentLoaded', () => {
    // ---ELEMENT SELECTORS---
    const scheduleId = document.getElementById('scheduleId').value;
    const eventId = document.getElementById('eventId').value;
    const toastContainer = document.getElementById('toast');
    
    // Header Elements
    const eventHeaderDiv = document.getElementById('eventHeader');
    const scheduleHeaderDiv = document.getElementById('scheduleHeader');

    // Left Column Elements
    const scheduleDetailsDiv = document.getElementById('scheduleDetails');
    const mealStatsDiv = document.getElementById('mealStats');
    const facilitatorsListDiv = document.getElementById('facilitatorsList');

    // Right Column Elements
    const searchInput = document.getElementById('searchInput');
    const companyFilter = document.getElementById('companyFilter');
    const employmentFilter = document.getElementById('employmentFilter');
    const statusFilter = document.getElementById('statusFilter');
    const attendeesTableBody = document.getElementById('attendeesTableBody');
    const bulkActionsDiv = document.getElementById('bulkActions');
    const selectedCountSpan = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

    // Modals & Buttons
    const qrModal = document.getElementById('qrModal');
    const showQrBtn = document.getElementById('showQrBtn');
    const editDetailsBtn = document.getElementById('editDetailsBtn');
    const scheduleDetailsModal = document.getElementById('scheduleDetailsModal');
    const scheduleDetailsForm = document.getElementById('scheduleDetailsForm');
    const bulkActivateBtn = document.getElementById('bulkActivateBtn');
    const bulkCollectBtn = document.getElementById('bulkCollectBtn');
    const manageFacilitatorsBtn = document.getElementById('manageFacilitatorsBtn');
    const facilitatorsModal = document.getElementById('facilitatorsModal');
    const currentFacilitatorsList = document.getElementById('currentFacilitatorsList');
    const addFacilitatorForm = document.getElementById('addFacilitatorForm');
    const facilitatorSelect = document.getElementById('facilitatorSelect');

    // ---STATE MANAGEMENT---
    let attendeesData = [];
    let uniqueCompanies = new Set();
    let debounceTimer;

    // ---API ENDPOINTS---
    const API = {
        getScheduleData: `/api/schedules/get_meal_schedule.php?id=${scheduleId}`,
        getEventDetails: `/api/events/read_details.php?id=${eventId}`,
        getAttendees: `/api/events/get_attendees.php?event_id=${eventId}&schedule_id=${scheduleId}`,
        updateSchedule: '/api/events/schedules/update_schedule_details.php',
        updateMealCardStatus: '/api/schedules/update_meal_card_status.php',
        getAllUsers: '/api/users/get_all.php',
        addFacilitator: '/api/schedules/add_facilitator.php',
        removeFacilitator: '/api/schedules/remove_facilitator.php'
    };

    // ---UTILITY FUNCTIONS---
    const showToast = (message, isError = false) => {
        const toast = document.createElement('div');
        toast.className = `p-4 rounded-lg shadow-lg text-white ${isError ? 'bg-red-500' : 'bg-green-500'}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        toastContainer.classList.remove('hidden');

        setTimeout(() => {
            toast.remove();
            if (toastContainer.childElementCount === 0) {
                toastContainer.classList.add('hidden');
            }
        }, 3000);
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return new Date(dateString).toLocaleString('en-US', options);
    };

    // ---RENDERING FUNCTIONS---

    /**
     * Renders the main event header (Event Title, Date, Location).
     * @param {object} eventDetails - The details of the parent event.
     */
    const renderEventHeader = (eventDetails) => {
        if (!eventHeaderDiv || !eventDetails) return;

        const statusBadge = eventDetails.status === 'published' 
            ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Published</span>'
            : '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">Draft</span>';

        eventHeaderDiv.innerHTML = `
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:truncate">${eventDetails.title}</h1>
                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6 text-slate-500">
                        <div class="mt-2 flex items-center text-sm">
                            <i class="fa-regular fa-calendar-days mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400"></i>
                            ${formatDate(eventDetails.start_datetime)} to ${formatDate(eventDetails.end_datetime)}
                        </div>
                        <div class="mt-2 flex items-center text-sm">
                            <i class="fa-solid fa-location-dot mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400"></i>
                            ${eventDetails.location || 'N/A'}
                        </div>
                        <div class="mt-2 flex items-center text-sm">${statusBadge}</div>
                    </div>
                </div>
            </div>
            <div class="mt-4 prose prose-sm max-w-none text-slate-600">${eventDetails.description || ''}</div>
        `;
    };

    /**
     * Renders the specific meal schedule header (Meal Title, Back Button).
     * @param {object} schedule - The details of the meal schedule.
     */
    const renderScheduleHeader = (schedule) => {
        scheduleHeaderDiv.innerHTML = `
            <div class="sm:flex sm:items-center sm:justify-between">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-semibold leading-6 text-gray-900">${schedule.title} (Meal Session)</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage attendees and details for this specific meal session.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                     <a href="/events/manage?id=${schedule.event_id}" class="block rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">&larr; Back to Event</a>
                </div>
            </div>
        `;
    };

    const renderDetails = (schedule) => {
        scheduleDetailsDiv.innerHTML = `
            <p><strong>Start:</strong> ${formatDate(schedule.start_datetime)}</p>
            <p><strong>End:</strong> ${formatDate(schedule.end_datetime)}</p>
            <p><strong>Status:</strong> <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${schedule.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${schedule.status}</span></p>
            <p class="text-gray-600 mt-2">${schedule.description || 'No description provided.'}</p>
        `;
    };
    
    const renderStats = (stats) => {
        document.getElementById('totalAttendees').textContent = stats.total || 0;
        document.getElementById('activatedCount').textContent = stats.activated || 0;
        document.getElementById('collectedCount').textContent = stats.collected || 0;
        document.getElementById('totalAttendees').classList.remove('animate-pulse', 'bg-gray-200');
        document.getElementById('activatedCount').classList.remove('animate-pulse', 'bg-gray-200');
        document.getElementById('collectedCount').classList.remove('animate-pulse', 'bg-gray-200');
    };

    const renderFacilitators = (facilitators) => {
        facilitatorsListDiv.innerHTML = '';
        if (facilitators.length === 0) {
            facilitatorsListDiv.innerHTML = '<p class="text-sm text-gray-500">No coordinators assigned.</p>';
        } else {
            const list = document.createElement('ul');
            list.className = 'space-y-2';
            facilitators.forEach(f => {
                list.innerHTML += `<li class="text-sm font-medium text-gray-800">${f.full_name}</li>`;
            });
            facilitatorsListDiv.appendChild(list);
        }

        currentFacilitatorsList.innerHTML = '';
         if (facilitators.length === 0) {
            currentFacilitatorsList.innerHTML = '<p class="text-sm text-center text-gray-500 py-4">No coordinators assigned.</p>';
        } else {
            facilitators.forEach(f => {
                currentFacilitatorsList.innerHTML += `
                    <div class="flex justify-between items-center p-2 bg-white rounded-md border" data-user-id="${f.id}">
                        <span class="text-sm font-medium">${f.full_name}</span>
                        <button class="remove-facilitator-btn text-red-500 hover:text-red-700 text-xs font-bold">REMOVE</button>
                    </div>
                `;
            });
        }
    };

    const renderAttendees = () => {
        const search = searchInput.value.toLowerCase();
        const company = companyFilter.value;
        const employment = employmentFilter.value;
        const status = statusFilter.value;

        const filteredAttendees = attendeesData.filter(att => {
            const matchesSearch = search === '' || att.user_name.toLowerCase().includes(search) || att.user_email.toLowerCase().includes(search) || att.ticket_code.toLowerCase().includes(search);
            const matchesCompany = company === '' || att.company_name === company;
            const matchesEmployment = employment === '' || att.is_employed.toString() === employment;
            const matchesStatus = status === '' || att.meal_card_status === status;
            return matchesSearch && matchesCompany && matchesEmployment && matchesStatus;
        });

        attendeesTableBody.innerHTML = '';
        if (filteredAttendees.length === 0) {
            attendeesTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-gray-500">No attendees found.</td></tr>`;
            return;
        }

        filteredAttendees.forEach(att => {
            let statusBadge;
            switch(att.meal_card_status) {
                case 'about_to_collect':
                    statusBadge = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Activated</span>`;
                    break;
                case 'collected':
                    statusBadge = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Collected</span>`;
                    break;
                default:
                    statusBadge = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>`;
            }
            
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="relative py-4 pl-4 pr-3 sm:pl-6">
                        <input type="checkbox" class="attendee-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" data-id="${att.ticket_id}">
                    </td>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">${att.user_name}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${att.ticket_code}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${att.company_name || 'N/A'}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${statusBadge}</td>
                </tr>
            `;
            attendeesTableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    const populateCompanyFilter = () => {
        companyFilter.innerHTML = '<option value="">All Companies</option>';
        uniqueCompanies.forEach(company => {
            if(company) {
                const option = document.createElement('option');
                option.value = company;
                option.textContent = company;
                companyFilter.appendChild(option);
            }
        });
    };
    
    // ---DATA FETCHING---
    const loadPageData = async () => {
        try {
            // Fetch all initial data in parallel for better performance
            const [scheduleRes, eventRes, attendeesRes] = await Promise.all([
                fetch(API.getScheduleData),
                fetch(API.getEventDetails),
                fetch(API.getAttendees)
            ]);

            if (!scheduleRes.ok || !eventRes.ok || !attendeesRes.ok) {
                throw new Error('One or more network responses were not ok.');
            }

            const scheduleData = await scheduleRes.json();
            const eventData = await eventRes.json();
            const attendeesResult = await attendeesRes.json();
            
            // Render Event Header
            if (eventData.details) {
                renderEventHeader(eventData.details);
            } else {
                 eventHeaderDiv.innerHTML = `<p class="text-red-500">Could not load event details.</p>`;
            }

            // Render Schedule-specific sections
            if (scheduleData.success) {
                renderScheduleHeader(scheduleData.schedule);
                renderDetails(scheduleData.schedule);
                renderStats(scheduleData.stats);
                renderFacilitators(scheduleData.facilitators);
            } else {
                scheduleHeaderDiv.innerHTML = `<p class="text-red-500">Could not load schedule details.</p>`;
            }

            // Render Attendees
            if (attendeesResult.success) {
                attendeesData = attendeesResult.attendees;
                attendeesData.forEach(att => uniqueCompanies.add(att.company_name));
                populateCompanyFilter();
                renderAttendees();
            } else {
                 attendeesTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-red-500">Could not load attendees.</td></tr>`;
            }

        } catch (error) {
            console.error('Failed to load initial page data:', error);
            showToast('Error loading page data. Check the console for details.', true);
        }
    };

    // ---EVENT LISTENERS---
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(renderAttendees, 300);
    });
    [companyFilter, employmentFilter, statusFilter].forEach(el => {
        el.addEventListener('change', renderAttendees);
    });

    showQrBtn.addEventListener('click', () => {
        qrModal.classList.remove('hidden');
        const qrContainer = document.getElementById('qrcode');
        qrContainer.innerHTML = '';
        new QRCode(qrContainer, {
            text: JSON.stringify({ event_id: eventId, schedule_id: scheduleId }),
            width: 256,
            height: 256,
        });
    });

    editDetailsBtn.addEventListener('click', async () => {
        try {
            const res = await fetch(API.getScheduleData);
            const data = await res.json();
            if(data.success) {
                const s = data.schedule;
                scheduleDetailsForm.querySelector('[name="title"]').value = s.title;
                scheduleDetailsForm.querySelector('[name="start_datetime"]').value = s.start_datetime.slice(0, 16);
                scheduleDetailsForm.querySelector('[name="end_datetime"]').value = s.end_datetime.slice(0, 16);
                scheduleDetailsForm.querySelector('[name="status"]').value = s.status;
                scheduleDetailsForm.querySelector('[name="description"]').value = s.description;
                scheduleDetailsModal.classList.remove('hidden');
            } else {
                showToast('Could not fetch latest details to edit.', true);
            }
        } catch(e) {
            showToast('An error occurred while fetching details.', true);
        }
    });

    scheduleDetailsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(scheduleDetailsForm);
        // 1. Convert the FormData into a plain JavaScript object.
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(API.updateSchedule, {
                method: 'POST',
                // 2. Set the correct Content-Type header to specify you're sending JSON.
                headers: {
                    'Content-Type': 'application/json'
                },
                // 3. Convert the JavaScript object into a JSON string for the request body.
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showToast('Schedule details updated successfully!');
                scheduleDetailsModal.classList.add('hidden');
                loadPageData(); // Reload all data to reflect changes
            } else {
                showToast(result.message || 'Failed to update details.', true);
            }
        } catch (error) {
            showToast('An error occurred.', true);
            console.error('Update Error:', error);
        }
    });

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.fixed').classList.add('hidden');
        });
    });

    const updateSelectedCount = () => {
        const selectedCheckboxes = document.querySelectorAll('.attendee-checkbox:checked');
        const count = selectedCheckboxes.length;
        selectedCountSpan.textContent = count;
        bulkActionsDiv.classList.toggle('hidden', count === 0);
        selectAllCheckbox.checked = count > 0 && count === document.querySelectorAll('.attendee-checkbox').length;
    };

    attendeesTableBody.addEventListener('change', (e) => {
        if (e.target.classList.contains('attendee-checkbox')) {
            updateSelectedCount();
        }
    });

    selectAllCheckbox.addEventListener('change', () => {
        document.querySelectorAll('.attendee-checkbox').forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateSelectedCount();
    });
    
    const handleBulkUpdate = async (newStatus) => {
        const selectedCheckboxes = document.querySelectorAll('.attendee-checkbox:checked');
        const ticketIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.id);

        if (ticketIds.length === 0) {
            showToast('No attendees selected.', true);
            return;
        }

        try {
            const response = await fetch(API.updateMealCardStatus, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: ticketIds, status: newStatus, schedule_id: scheduleId })
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Successfully updated ${ticketIds.length} meal card(s).`);
                selectAllCheckbox.checked = false;
                bulkActionsDiv.classList.add('hidden');
                loadPageData(); // Reload all data to reflect changes
            } else {
                showToast(result.message || 'Update failed.', true);
            }
        } catch (error) {
            showToast('An error occurred.', true);
        }
    };

    bulkActivateBtn.addEventListener('click', () => handleBulkUpdate('about_to_collect'));
    bulkCollectBtn.addEventListener('click', () => handleBulkUpdate('collected'));

    manageFacilitatorsBtn.addEventListener('click', async () => {
        facilitatorsModal.classList.remove('hidden');
        try {
            // Load users for the dropdown
            const usersRes = await fetch(API.getAllUsers);
            const usersData = await usersRes.json();
            if (usersData.success) {
                facilitatorSelect.innerHTML = '<option value="">Select a user...</option>';
                usersData.users.forEach(user => {
                    facilitatorSelect.innerHTML += `<option value="${user.id}">${user.full_name}</option>`;
                });
            }
            // Load current facilitators
            const scheduleRes = await fetch(API.getScheduleData);
            const scheduleData = await scheduleRes.json();
            if (scheduleData.success) {
                renderFacilitators(scheduleData.facilitators);
            }
        } catch(e) { 
            console.error("Could not load coordinator data:", e); 
            showToast('Could not load coordinator data.', true);
        }
    });

    addFacilitatorForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = facilitatorSelect.value;
        if (!userId) return;

        try {
            const res = await fetch(API.addFacilitator, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ schedule_id: scheduleId, user_id: userId })
            });
            const result = await res.json();
            if(result.success) {
                showToast('Coordinator added.');
                facilitatorSelect.value = '';
                // Reload data to show updated list
                const newData = await (await fetch(API.getScheduleData)).json();
                if(newData.success) renderFacilitators(newData.facilitators);
            } else {
                showToast(result.message || 'Failed to add coordinator.', true);
            }
        } catch (error) {
            showToast('An error occurred.', true);
        }
    });

    currentFacilitatorsList.addEventListener('click', async (e) => {
        if (e.target.classList.contains('remove-facilitator-btn')) {
            const itemDiv = e.target.closest('[data-user-id]');
            const userId = itemDiv.dataset.userId;

            if (confirm('Are you sure you want to remove this coordinator?')) {
                try {
                    const res = await fetch(API.removeFacilitator, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ schedule_id: scheduleId, user_id: userId })
                    });
                    const result = await res.json();
                    if(result.success) {
                        showToast('Coordinator removed.');
                        // Reload data to show updated list
                        const newData = await (await fetch(API.getScheduleData)).json();
                        if (newData.success) renderFacilitators(newData.facilitators);
                    } else {
                         showToast(result.message || 'Failed to remove coordinator.', true);
                    }
                } catch (error) {
                    showToast('An error occurred.', true);
                }
            }
        }
    });

    // ---INITIALIZATION---
    loadPageData();
});