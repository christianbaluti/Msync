document.addEventListener('DOMContentLoaded', () => {
    // --- 1. SETUP & STATE MANAGEMENT ---
    const container = document.getElementById('awardsManagementContainer');
    if (!container) return;
    let confirmAction = null;

    const genericConfirmModal = document.getElementById('genericConfirmModal');
    const confirmModalTitle = document.getElementById('confirmModalTitle');
    const confirmModalMessage = document.getElementById('confirmModalMessage');
    const confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');

    const appState = {
        details: {},
        eventDetails: {},
        facilitators: [],
        awards: [],
        stats: {},
        allUsers: [],
        membershipTypes: [],
        allCompanies: [],
        userNominationCounts: {},
        companyNominationCounts: {},
    };

    const scheduleId = container.dataset.scheduleId;
    const eventHeaderDiv = document.getElementById('eventHeader');
    
    // --- SPINNER ---
    const spinner = document.getElementById('loading-spinner');
    const showSpinner = () => spinner.classList.remove('hidden');
    const hideSpinner = () => spinner.classList.add('hidden');


    // --- 2. API ENDPOINTS ---
    const API = {
        getDashboard: `/api/awards/get_dashboard.php?id=${scheduleId}`,
        getAllUsers: '/api/users/read_all_for_awards.php',
        getAllCompanies: '/api/companies/read_all.php',
        getMembershipTypes: '/api/memberships/get_types.php',
        updateDetails: '/api/schedules/update_details.php',
        manageFacilitators: '/api/schedules/manage_facilitators.php',
        updateStatus: '/api/awards/update_status.php',
        saveSettings: '/api/awards/save_settings.php',
        createAward: '/api/awards/create_award.php',
        updateAward: '/api/awards/update_award.php',
        deleteAward: '/api/awards/delete_award.php',
        createNomination: '/api/awards/create_nomination.php',
        updateNomination: '/api/awards/update_nomination.php',
        deleteNomination: '/api/awards/delete_nomination.php',
    };

    // --- 3. ELEMENT SELECTORS (Unchanged) ---
    const scheduleTitleEl = document.getElementById('scheduleTitle');
    const scheduleTypeEl = document.getElementById('scheduleType');
    const switchStatusBtn = document.getElementById('switchStatusBtn');
    const settingsBtn = document.getElementById('settingsBtn');
    const addNewAwardBtn = document.getElementById('addNewAwardBtn');
    const awardsContainer = document.getElementById('awardsContainer');
    const scheduleDetailsContainer = document.getElementById('scheduleDetailsContainer');
    const facilitatorsListContainer = document.getElementById('facilitatorsListContainer');
    const overallStatsEl = document.getElementById('overallStats');
    const nominationStatsEl = document.getElementById('nominationStats');
    const scheduleDetailsModal = document.getElementById('scheduleDetailsModal');
    const scheduleDetailsForm = document.getElementById('scheduleDetailsForm');
    const facilitatorsModal = document.getElementById('facilitatorsModal');
    const currentFacilitatorsList = document.getElementById('currentFacilitatorsList');
    const addFacilitatorForm = document.getElementById('addFacilitatorForm');
    const facilitatorSelect = document.getElementById('facilitatorSelect');
    const saveFacilitatorsBtn = document.getElementById('saveFacilitatorsBtn');
    const settingsModal = document.getElementById('settingsModal');
    const awardsSettingsForm = document.getElementById('awardsSettingsForm');
    const awardModal = document.getElementById('awardModal');
    const awardForm = document.getElementById('awardForm');
    const nominationModal = document.getElementById('nominationModal');
    const nominationForm = document.getElementById('nominationForm');

    // --- 4. UTILITY FUNCTIONS (Unchanged) ---
    const showToast = (message, isError = false) => {
        const toastContainer = document.getElementById('toast');
        const toast = document.createElement('div');
        toast.className = `p-4 mb-2 rounded-lg shadow-lg text-white transition-opacity duration-300 ${isError ? 'bg-red-500' : 'bg-green-500'}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        toastContainer.classList.remove('hidden');
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.remove();
                if (toastContainer.childElementCount === 0) {
                    toastContainer.classList.add('hidden');
                }
            }, 300);
        }, 3000);
    };

    const showGenericConfirm = (config) => {
        confirmModalTitle.textContent = config.title || 'Confirm Action';
        confirmModalMessage.innerHTML = config.message || 'Are you sure? This action cannot be undone.';
        confirmModalConfirmBtn.textContent = config.confirmText || 'Confirm';
        confirmModalConfirmBtn.className = `rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm ${config.isDestructive === false ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-red-600 hover:bg-red-500'}`;
        confirmAction = config.onConfirm;
        genericConfirmModal.classList.remove('hidden');
    };

    confirmModalConfirmBtn.addEventListener('click', () => {
        if (typeof confirmAction === 'function') {
            confirmAction();
        }
        genericConfirmModal.classList.add('hidden');
        confirmAction = null;
    });

    const formatDate = (ds) => ds ? new Date(ds).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' }) : 'N/A';
    
    const formatToDateTimeLocal = (ds) => {
        if (!ds) return '';
        const date = new Date(ds);
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 16);
    };

    const fetchAPI = async (url, options = {}) => {
        showSpinner(); 
        try {
            if (options.method === 'POST' && options.body) {
                const data = JSON.parse(options.body);
                if (!data.schedule_id) {
                    data.schedule_id = scheduleId;
                    options.body = JSON.stringify(data);
                }
                if (!options.headers) {
                    options.headers = { 'Content-Type': 'application/json' };
                }
            }
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            if (result.success === false) throw new Error(result.message);
            return result;
        } catch (error) {
            console.error('API Error:', error);
            showToast(error.message || 'A network or server error occurred.', true);
            return { success: false, message: error.message };
        } finally {
            hideSpinner(); 
        }
    };


    // --- 5. RENDER FUNCTIONS (Largely Unchanged) ---
    const renderPage = () => {
        renderEventHeader();
        renderHeader();
        renderSidebar();
        renderAwards();
    };

    const renderEventHeader = () => {
        if (!eventHeaderDiv || !appState.eventDetails) return;
        const { eventDetails } = appState;
        const statusBadge = eventDetails.status === 'published' 
            ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Published</span>'
            : '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">Draft</span>';

        eventHeaderDiv.innerHTML = `
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:truncate">${eventDetails.title}</h1>
                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6 text-slate-500">
                        <div class="mt-2 flex items-center text-sm"><svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 3a.75.75 0 01.75.75v.5h7V3.75a.75.75 0 011.5 0v.5h.75a2.25 2.25 0 012.25 2.25v8.5a2.25 2.25 0 01-2.25 2.25H4.25A2.25 2.25 0 012 15.25v-8.5A2.25 2.25 0 014.25 4.5h.75v-.5A.75.75 0 015.75 3zm-1.5 5.5a.75.75 0 000 1.5h11.5a.75.75 0 000-1.5H4.25z" clip-rule="evenodd" /></svg>${formatDate(eventDetails.start_datetime)} to ${formatDate(eventDetails.end_datetime)}</div>
                        <div class="mt-2 flex items-center text-sm"><svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.757.433.57.57 0 00.28.14l.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>${eventDetails.location || 'N/A'}</div>
                        <div class="mt-2 flex items-center text-sm">${statusBadge}</div>
                    </div>
                </div>
            </div>
            <div class="mt-4 prose prose-sm max-w-none text-slate-600">${eventDetails.description || ''}</div>
            <div class="mt-4 text-right"><a href="/events/manage?id=${eventDetails.event_id}" class="rounded-lg bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-black-700">&larr; Back to Event</a></div>`;
    };
    
    const renderHeader = () => {
        const { details, eventDetails } = appState;
        scheduleTitleEl.textContent = details.title;
        scheduleTypeEl.innerHTML = `Awards Schedule &bull; Part of <strong>${eventDetails.title || 'Event'}</strong>`;
        [scheduleTitleEl, scheduleTypeEl].forEach(el => el.querySelector('.animate-pulse')?.classList.remove('animate-pulse', 'bg-gray-200'));
        const isActive = details.status === 'active';
        switchStatusBtn.textContent = isActive ? 'Set Inactive' : 'Set Active';
        switchStatusBtn.className = `rounded-md px-3 py-1.5 text-sm font-semibold text-white shadow-sm ${isActive ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'}`;
        switchStatusBtn.dataset.nextStatus = isActive ? 'inactive' : 'active';
    };

    const renderSidebar = () => {
        const { details, facilitators, stats } = appState;
        scheduleDetailsContainer.innerHTML = `
            <p><strong>Starts:</strong> ${formatDate(details.start_datetime)}</p>
            <p><strong>Ends:</strong> ${formatDate(details.end_datetime)}</p>
            <p><strong>Status:</strong> <span class="capitalize px-2 py-1 text-xs font-medium rounded-full ${details.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">${details.status}</span></p>
            <p class="mt-2 pt-2 border-t text-gray-600">${details.description || 'No description provided.'}</p>`;
        facilitatorsListContainer.innerHTML = facilitators.length > 0
            ? facilitators.map(f => `<p class="flex items-center gap-x-2 text-sm">${f.full_name}</p>`).join('')
            : `<p class="text-sm text-gray-500">No facilitators assigned.</p>`;
        if (stats) {
            overallStatsEl.innerHTML = `
                <div class="flex justify-between text-sm"><span class="text-gray-600">Total Awards:</span><span class="font-semibold">${stats.total_awards}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-600">Total Nominations:</span><span class="font-semibold">${stats.total_nominations}</span></div>`;
            nominationStatsEl.innerHTML = stats.nominations_by_award?.length > 0
                ? stats.nominations_by_award.map(p => `<div class="flex justify-between text-sm"><span class="text-gray-600">${p.title}:</span><span class="font-semibold">${p.nomination_count} noms</span></div>`).join('')
                : '<p class="text-sm text-gray-500">No nominations yet.</p>';
        }
    };
    
    const renderAwards = () => {
        awardsContainer.innerHTML = '';
        if (appState.awards.length === 0) {
            awardsContainer.innerHTML = '<p class="text-center text-gray-500 bg-white p-8 rounded-lg shadow-sm">No awards have been created yet.</p>';
            return;
        }
        appState.awards.forEach(award => {
            const content = renderNominationList(award);
            awardsContainer.insertAdjacentHTML('beforeend', `
                <div class="bg-white rounded-lg shadow-sm" data-award-id="${award.id}">
                    <div class="accordion-header flex items-center justify-between p-4 cursor-pointer border-b">
                        <div>
                            <h3 class="font-bold text-gray-900">${award.title}</h3>
                            <p class="text-xs text-gray-600">${award.description || ''}</p>
                            <span class="mt-1 capitalize px-2 py-0.5 text-xs font-medium rounded-full ${award.for_entity === 'user' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'}">For: ${award.for_entity}</span>
                        </div>
                        <div class="flex items-center gap-x-3 flex-shrink-0">
                            <button class="edit-award-btn text-xs font-medium text-indigo-600 hover:underline">Edit</button>
                            <button class="delete-award-btn text-xs font-medium text-red-600 hover:underline">Delete</button>
                            <svg class="accordion-chevron h-5 w-5 transform transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </div>
                    </div>
                    <div class="accordion-content hidden p-4">${content}</div>
                </div>`);
        });
    };

    const renderNominationList = (award) => {
        // The main list content for nominations.
        const listContent = !award.nominations || award.nominations.length === 0
            ? '<p class="text-sm text-center text-gray-500 py-4">No nominees for this award yet.</p>'
            // If there are nominations, map over them to create list items.
            : `<ul class="divide-y divide-gray-200">
                ${award.nominations.map(nom => {
                    // --- FIX STARTS HERE ---
                    // 1. Initialize count for this specific nominee.
                    let nomineeCount = 0;
                    let countText = '';

                    // 2. Check if the award is for a user or a company.
                    if (award.for_entity === 'user') {
                        // Use the nominee's user ID to look up their total count.
                        nomineeCount = appState.userNominationCounts[nom.nominee_user_id] || 0;
                    } else { // 'company'
                        // Use the nominee's company ID to look up their total count.
                        nomineeCount = appState.companyNominationCounts[nom.nominee_company_id] || 0;
                    }
                    
                    // 3. Create a clean text string to display the count.
                    if (nomineeCount > 0) {
                        countText = `(Total: ${nomineeCount} nomination${nomineeCount > 1 ? 's' : ''})`;
                    }

                    // 4. Return the complete HTML for the list item.
                    return `
                    <li class="flex items-center justify-between py-2" data-nomination-id="${nom.id}">
                        <div>
                            <p class="text-sm font-medium text-gray-800">
                                ${nom.nominee_display_name} 
                                <span class="text-xs text-indigo-600 font-semibold ml-1">${countText}</span>
                            </p>
                            <p class="text-xs text-gray-500">Nominated by: ${nom.nominator_name}</p>
                            ${nom.nomination_text ? `<p class="text-xs text-gray-600 mt-1 italic">"${nom.nomination_text}"</p>` : ''}
                        </div>
                        <div class="flex items-center gap-x-4">
                            <button class="edit-nomination-btn text-xs font-medium text-gray-600 hover:underline">Edit</button>
                            <button class="delete-nomination-btn text-xs font-medium text-red-600 hover:underline">Delete</button>
                        </div>
                    </li>`;
                    // --- FIX ENDS HERE ---
                }).join('')}
            </ul>`;

        // Return the complete component with the header and the list.
        return `<div class="flex justify-between items-center mb-2">
                    <h4 class="text-sm font-semibold">Nominees</h4>
                    <button class="add-nominee-btn text-xs font-medium text-indigo-600 hover:underline">+ Add Nominee Manually</button>
                </div>${listContent}`;
    };

    // --- 6. MODAL & FORM HANDLING ---
    const openDetailsModal = () => {
        const { details } = appState;
        scheduleDetailsForm.querySelector('input[name="title"]').value = details.title;
        scheduleDetailsForm.querySelector('input[name="start_datetime"]').value = formatToDateTimeLocal(details.start_datetime);
        scheduleDetailsForm.querySelector('input[name="end_datetime"]').value = formatToDateTimeLocal(details.end_datetime);
        scheduleDetailsForm.querySelector('textarea[name="description"]').value = details.description || '';
        scheduleDetailsModal.classList.remove('hidden');
    };

    const openFacilitatorsModal = async () => {
        if (appState.allUsers.length === 0) {
            const result = await fetchAPI(API.getAllUsers);
            if (result.success) appState.allUsers = result.data;
            else return;
        }
        const facilitatorIds = appState.facilitators.map(f => parseInt(f.id));
        currentFacilitatorsList.innerHTML = appState.facilitators.length > 0
            ? appState.facilitators.map(f => `<div class="flex justify-between items-center py-1"><span>${f.full_name}</span><button data-id="${f.id}" class="remove-facilitator-btn text-xs text-red-500 hover:underline">Remove</button></div>`).join('')
            : '<p class="text-sm text-gray-500">No facilitators yet.</p>';
        const availableUsers = appState.allUsers.filter(u => !facilitatorIds.includes(parseInt(u.id)));
        if (availableUsers.length > 0) {
            facilitatorSelect.innerHTML = '<option value="">-- Select a user to add --</option>' + availableUsers.map(u => `<option value="${u.id}">${u.full_name}</option>`).join('');
            addFacilitatorForm.style.display = 'flex';
        } else {
            addFacilitatorForm.style.display = 'none';
        }
        facilitatorsModal.classList.remove('hidden');
    };

    const openSettingsModal = async () => {
        if (appState.membershipTypes.length === 0) {
            const result = await fetchAPI(API.getMembershipTypes);
            if (result.success) appState.membershipTypes = result.data;
        }
        
        const buildCheckboxes = (prefix) => {
            let html = `<div class="space-y-2">
                <div class="font-semibold text-sm text-gray-800 pt-2">General Groups</div>
                <div class="flex items-center">
                    <input id="${prefix}-all-users" name="${prefix}_eligibility[]" type="checkbox" value="all_users" class="h-4 w-4 border-gray-300 text-indigo-600">
                    <label for="${prefix}-all-users" class="ml-3 text-sm text-gray-700">All Ticket Holders</label>
                </div>
                <div class="flex items-center">
                    <input id="${prefix}-all-members" name="${prefix}_eligibility[]" type="checkbox" value="all_members" class="h-4 w-4 border-gray-300 text-indigo-600">
                    <label for="${prefix}-all-members" class="ml-3 text-sm text-gray-700">All Member Users</label>
                </div>
            </div>`;
            if (appState.membershipTypes.length > 0) {
                html += `<div class="font-semibold text-sm text-gray-800 pt-3 border-t mt-3">By Membership Type</div>`;
                appState.membershipTypes.forEach(type => {
                    html += `<div class="flex items-center">
                        <input id="${prefix}-type-${type.id}" name="${prefix}_eligibility[]" type="checkbox" value="type_${type.id}" class="h-4 w-4 border-gray-300 text-indigo-600">
                        <label for="${prefix}-type-${type.id}" class="ml-3 text-sm text-gray-700">${type.name}</label>
                    </div>`;
                });
            }
            return html;
        };

        document.getElementById('nominatorEligibilityContainer').innerHTML = buildCheckboxes('nominator');
        document.getElementById('nomineeEligibilityContainer').innerHTML = buildCheckboxes('nominee');

        const savedSettings = appState.details.settings || {};
        const setCheckedState = (containerId, settings) => {
            if (!settings) return;
            const container = document.getElementById(containerId);
            if (settings.all_users) container.querySelector('input[value="all_users"]').checked = true;
            if (settings.all_members) container.querySelector('input[value="all_members"]').checked = true;
            if (Array.isArray(settings.member_types)) {
                settings.member_types.forEach(typeId => {
                    const cb = container.querySelector(`input[value="type_${typeId}"]`);
                    if (cb) cb.checked = true;
                });
            }
        };

        setCheckedState('nominatorEligibilityContainer', savedSettings.nominator_eligibility);
        setCheckedState('nomineeEligibilityContainer', savedSettings.nominee_eligibility);

        settingsModal.classList.remove('hidden');
    };

    const openAwardModal = (award = null) => {
        awardForm.reset();
        document.getElementById('awardModalTitle').textContent = award ? 'Edit Award' : 'Add New Award';
        awardForm.querySelector('[name="id"]').value = award ? award.id : '';
        if (award) {
            awardForm.querySelector('[name="title"]').value = award.title;
            awardForm.querySelector('[name="description"]').value = award.description;
            awardForm.querySelector(`[name="for_entity"][value="${award.for_entity}"]`).checked = true;
        }
        awardModal.classList.remove('hidden');
    };

    // ==================== MODIFICATION START: `openNominationModal` function is completely replaced ====================
    const openNominationModal = async (awardId, nomination = null) => {
        nominationForm.reset();
        const award = appState.awards.find(a => a.id == awardId);
        if (!award) {
            showToast('Could not find the specified award.', true);
            return;
        }

        document.getElementById('nominationModalTitle').textContent = nomination ? 'Edit Nominee' : `Add Nominee for "${award.title}"`;
        nominationForm.querySelector('[name="id"]').value = nomination ? nomination.id : '';
        nominationForm.querySelector('[name="award_id"]').value = awardId;
        
        const nomineeInputContainer = document.getElementById('nomineeInputContainer');
        const entityType = award.for_entity;
        const isEditMode = nomination !== null;

        if (isEditMode) {
            nominationForm.querySelector('[name="nomination_text"]').value = nomination.nomination_text || '';
        }

        if (isEditMode) {
            // In EDIT mode, show a disabled input with the nominee's name
            const hiddenInputName = entityType === 'user' ? 'nominee_user_id' : 'nominee_company_id';
            const hiddenInputValue = entityType === 'user' ? nomination.nominee_user_id : nomination.nominee_company_id;
            
            nomineeInputContainer.innerHTML = `
                <label class="block text-sm font-medium text-gray-700">Nominee</label>
                <input type="text" value="${nomination.nominee_display_name}" disabled class="w-full rounded-lg border-gray-300 bg-gray-100 px-4 py-2 text-sm text-gray-500 cursor-not-allowed">
                <input type="hidden" name="${hiddenInputName}" value="${hiddenInputValue}">
            `;
        } else {
            // In ADD mode, show the searchable input
            const dataSource = entityType === 'user' ? appState.allUsers : appState.allCompanies;
            const hiddenInputName = entityType === 'user' ? 'nominee_user_id' : 'nominee_company_id';
            const placeholder = entityType === 'user' ? 'Search for a user by name or company...' : 'Search for a company...';
            
            if (dataSource.length === 0) {
                const apiEndpoint = entityType === 'user' ? API.getAllUsers : API.getAllCompanies;
                const res = await fetchAPI(apiEndpoint);
                if (res.success) {
                    if (entityType === 'user') appState.allUsers = res.data;
                    else appState.allCompanies = res.data;
                }
            }

            nomineeInputContainer.innerHTML = `
                <label for="nominee-search-input" class="block text-sm font-medium text-gray-700">Select Nominee</label>
                <div class="relative">
                    <input type="text" id="nominee-search-input" placeholder="${placeholder}" autocomplete="off" class="w-full rounded-lg border-gray-300 px-4 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <input type="hidden" name="${hiddenInputName}" id="nominee-hidden-input" required>
                    <div id="nominee-search-results" class="hidden absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 max-h-60 overflow-y-auto shadow-lg"></div>
                </div>
            `;

            const searchInput = document.getElementById('nominee-search-input');
            const resultsContainer = document.getElementById('nominee-search-results');
            const hiddenInput = document.getElementById('nominee-hidden-input');
            const currentDataSource = entityType === 'user' ? appState.allUsers : appState.allCompanies;

            searchInput.addEventListener('input', () => {
                const query = searchInput.value.toLowerCase();
                hiddenInput.value = '';
                if (query.length < 2) {
                    resultsContainer.classList.add('hidden');
                    return;
                }

                // ==================== CHANGE START: Updated Search Logic ====================
                const filtered = currentDataSource.filter(item => {
                    if (entityType === 'user') {
                        const nameMatch = item.full_name.toLowerCase().includes(query);
                        // Check company_name only if it exists
                        const companyMatch = item.company_name && item.company_name.toLowerCase().includes(query);
                        return nameMatch || companyMatch;
                    }
                    // Fallback for company search
                    return item.name.toLowerCase().includes(query);
                });
                // ===================== CHANGE END: Updated Search Logic =====================

                if (filtered.length > 0) {
                    // ================== CHANGE START: Updated Display Logic ==================
                    resultsContainer.innerHTML = filtered.map(item => {
                        const id = item.id;
                        let displayName;
                        if (entityType === 'user') {
                            // Construct the display name with company info
                            const companyInfo = item.company_name ? `(${item.company_name})` : '(Not Employed)';
                            displayName = `${item.full_name} <span class="text-gray-500 text-xs">${companyInfo}</span>`;
                        } else {
                            displayName = item.name;
                        }
                        // We use `item.full_name` or `item.name` for the data-name attribute to keep the input clean on selection
                        const dataName = entityType === 'user' ? item.full_name : item.name;
                        return `<div class="cursor-pointer p-2 hover:bg-indigo-100" data-id="${id}" data-name="${dataName}">${displayName}</div>`;
                    }).join('');
                    // =================== CHANGE END: Updated Display Logic ===================
                } else {
                    resultsContainer.innerHTML = `<div class="p-2 text-sm text-gray-500">No results found</div>`;
                }
                resultsContainer.classList.remove('hidden');
            });

            resultsContainer.addEventListener('click', (e) => {
                const targetDiv = e.target.closest('[data-id]');
                if (targetDiv) {
                    searchInput.value = targetDiv.dataset.name;
                    hiddenInput.value = targetDiv.dataset.id;
                    resultsContainer.classList.add('hidden');
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!nomineeInputContainer.contains(e.target)) {
                    resultsContainer.classList.add('hidden');
                }
            });
        }

        nominationModal.classList.remove('hidden');
    };
    // ==================== MODIFICATION END ====================


    // --- 7. EVENT HANDLERS (Unchanged) ---
    const handleSaveDetails = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        data.id = scheduleId;
        const result = await fetchAPI(API.updateDetails, { method: 'POST', body: JSON.stringify(data) });
        showToast(result.message, !result.success);
        if (result.success) {
            scheduleDetailsModal.classList.add('hidden');
            await initializePage();
        }
    };
    
    const handleSaveFacilitators = async () => {
        const facilitatorIds = appState.facilitators.map(f => f.id);
        const result = await fetchAPI(API.manageFacilitators, {
            method: 'POST',
            body: JSON.stringify({ schedule_id: scheduleId, facilitator_ids: facilitatorIds })
        });
        showToast(result.message, !result.success);
        if (result.success) {
            facilitatorsModal.classList.add('hidden');
            renderSidebar();
        }
    };
    
    const handleSwitchStatus = async (e) => {
        const nextStatus = e.target.dataset.nextStatus;
        const result = await fetchAPI(API.updateStatus, { method: 'POST', body: JSON.stringify({ status: nextStatus }) });
        if (result.success) {
            showToast(`Schedule status set to ${nextStatus}.`);
            await initializePage();
        }
    };
    
    const handleSaveAward = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const id = formData.get('id');
        const url = id ? API.updateAward : API.createAward;
        const result = await fetchAPI(url, { method: 'POST', body: JSON.stringify(Object.fromEntries(formData)) });
        if (result.success) {
            showToast(`Award ${id ? 'updated' : 'created'} successfully.`);
            awardModal.classList.add('hidden');
            await initializePage();
        }
    };
    
    const handleSaveNomination = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const id = formData.get('id');
        const url = id ? API.updateNomination : API.createNomination;
        const result = await fetchAPI(url, { method: 'POST', body: JSON.stringify(Object.fromEntries(formData)) });
        if (result.success) {
            showToast(`Nominee ${id ? 'updated' : 'added'} successfully.`);
            nominationModal.classList.add('hidden');
            await initializePage();
        }
    };
    
    const handleSaveSettings = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        const buildEligibilityObject = (values) => {
            const settings = { all_users: false, all_members: false, member_types: [] };
            values.forEach(value => {
                if (value === 'all_users') settings.all_users = true;
                else if (value === 'all_members') settings.all_members = true;
                else if (value.startsWith('type_')) {
                    settings.member_types.push(parseInt(value.split('_')[1], 10));
                }
            });
            return settings;
        };

        const payload = {
            settings: {
                nominator_eligibility: buildEligibilityObject(formData.getAll('nominator_eligibility[]')),
                nominee_eligibility: buildEligibilityObject(formData.getAll('nominee_eligibility[]'))
            }
        };

        const result = await fetchAPI(API.saveSettings, { method: 'POST', body: JSON.stringify(payload) });
        if (result.success) {
            showToast('Settings saved successfully.');
            settingsModal.classList.add('hidden');
            await initializePage();
        }
    };

    const handleAwardActions = async (e) => {
        const target = e.target;
        const awardDiv = target.closest('[data-award-id]');
        if (!awardDiv) return;
        const awardId = awardDiv.dataset.awardId;
        const award = appState.awards.find(a => a.id == awardId);

        const header = target.closest('.accordion-header');
        if (header && !target.closest('button')) {
            header.nextElementSibling.classList.toggle('hidden');
            header.querySelector('.accordion-chevron').classList.toggle('rotate-180');
            return;
        }
        
        if (target.classList.contains('edit-award-btn')) {
            openAwardModal(award);
        } else if (target.classList.contains('delete-award-btn')) {
            // ==================== CHANGE START: DELETE AWARD ====================
            showGenericConfirm({
                title: 'Delete Award?',
                message: `Are you sure you want to delete the award "<strong>${award.title}</strong>"?<br>All associated nominations will also be permanently deleted. This cannot be undone.`,
                confirmText: 'Yes, Delete',
                onConfirm: async () => {
                    const result = await fetchAPI(API.deleteAward, { method: 'POST', body: JSON.stringify({ id: awardId }) });
                    if (result.success) {
                        showToast('Award deleted.');
                        await initializePage();
                    }
                }
            });
            // ===================== CHANGE END: DELETE AWARD =====================
        } else if (target.classList.contains('add-nominee-btn')) {
            openNominationModal(awardId);
        }

    const nominationLi = target.closest('[data-nomination-id]');
        if (nominationLi) {
            const nominationId = nominationLi.dataset.nominationId;
            const nomination = award.nominations.find(n => n.id == nominationId);
            
            if (target.classList.contains('edit-nomination-btn')) {
                openNominationModal(awardId, nomination);
            } else if (target.classList.contains('delete-nomination-btn')) {
                // ================== START CHANGE: DELETE NOMINATION ==================
                showGenericConfirm({
                    title: 'Delete Nomination?',
                    message: `Are you sure you want to delete the nomination for "<strong>${nomination.nominee_display_name}</strong>"? This action cannot be undone.`,
                    confirmText: 'Yes, Delete',
                    onConfirm: async () => {
                        const result = await fetchAPI(API.deleteNomination, { method: 'POST', body: JSON.stringify({ id: nominationId }) });
                        if (result.success) {
                            showToast('Nomination deleted.');
                            await initializePage();
                        }
                    }
                });
                // =================== END CHANGE: DELETE NOMINATION ===================
            }
        }
    };


    // --- 8. EVENT LISTENER ATTACHMENT (Unchanged) ---
    const attachEventListeners = () => {
        document.getElementById('editDetailsBtn').addEventListener('click', openDetailsModal);
        document.getElementById('manageFacilitatorsBtn').addEventListener('click', openFacilitatorsModal);
        switchStatusBtn.addEventListener('click', handleSwitchStatus);
        settingsBtn.addEventListener('click', openSettingsModal);
        addNewAwardBtn.addEventListener('click', () => openAwardModal());
        scheduleDetailsForm.addEventListener('submit', handleSaveDetails);
        saveFacilitatorsBtn.addEventListener('click', handleSaveFacilitators);
        awardsSettingsForm.addEventListener('submit', handleSaveSettings);
        awardForm.addEventListener('submit', handleSaveAward);
        nominationForm.addEventListener('submit', handleSaveNomination);
        awardsContainer.addEventListener('click', handleAwardActions);
        addFacilitatorForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const userId = parseInt(facilitatorSelect.value);
            if (!userId) return;
            const user = appState.allUsers.find(u => u.id == userId);
            if (user && !appState.facilitators.some(f => f.id == userId)) {
                appState.facilitators.push({ id: user.id, full_name: user.full_name });
                openFacilitatorsModal();
            }
        });
        currentFacilitatorsList.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-facilitator-btn')) {
                const userIdToRemove = parseInt(e.target.dataset.id);
                appState.facilitators = appState.facilitators.filter(f => f.id != userIdToRemove);
                openFacilitatorsModal();
            }
        });
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => btn.closest('.fixed').classList.add('hidden'));
        });
    };

    // --- 9. INITIALIZATION (Unchanged) ---
    async function initializePage() {
        if (!scheduleId) {
            container.innerHTML = '<p class="text-center text-red-500">Error: No Schedule ID found.</p>';
            return;
        }

        const result = await fetchAPI(API.getDashboard);

        if (result.success) {
            appState.details = result.data.details || {};
            appState.eventDetails = result.data.event_details || {};
            appState.facilitators = result.data.facilitators || [];
            appState.awards = result.data.awards || [];
            appState.stats = result.data.stats || {};
            appState.userNominationCounts = result.data.user_nomination_counts || {};
            appState.companyNominationCounts = result.data.company_nomination_counts || {};
            
            renderPage(); 

        } else {
            container.innerHTML = `<p class="text-center text-red-500 bg-white p-8 rounded-lg">Failed to load awards data. Please try again.</p>`;
        }
    }
    
    // Initial call
    initializePage();
    attachEventListeners();
});