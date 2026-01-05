document.addEventListener('DOMContentLoaded', () => {
    // --- 1. SETUP & STATE MANAGEMENT ---
    const container = document.getElementById('votingManagementContainer');
    if (!container) return;

    const appState = {
        details: {},
        facilitators: [],
        positions: [],
        stats: {},
        allUsers: [],
        membershipTypes: [],
        allCompanies: [],
        resultsVisible: false
    };

    let confirmAction = null;
    const scheduleId = container.dataset.scheduleId;

    // --- 2. API ENDPOINTS ---
    const API = {
        getDashboard: `/api/elections/get_dashboard.php?id=${scheduleId}`,
        getAllUsers: '/api/users/read_all_for_voting.php',
        updateDetails: '/api/schedules/update_details.php',
        manageFacilitators: '/api/schedules/manage_facilitators.php',
        updateStage: '/api/elections/update_stage.php',
        getAllCompanies: '/api/companies/read_all.php',
        createNominationManual: '/api/elections/create_nomination_manual.php',
        updateStatus: '/api/elections/update_status.php',
        saveSettings: '/api/elections/save_settings.php',
        getMembershipTypes: '/api/membership_types/read.php', // Corrected path
        createPosition: '/api/elections/create_position.php',
        updatePosition: '/api/elections/update_position.php',
        deletePosition: '/api/elections/delete_position.php',
        createCandidate: '/api/elections/create_candidate.php',
        updateCandidate: '/api/elections/update_candidate.php',
        deleteCandidate: '/api/elections/delete_candidate.php',
        promoteNomination: '/api/elections/promote_nomination.php',
        getNomineesForPosition: '/api/elections/get_nominees_for_position.php',
        promoteNominations: '/api/elections/promote_nominations_to_candidates.php',
        search: '/api/search/search.php' // NEW: Search endpoint
    };

    // --- 3. ELEMENT SELECTORS ---
    const spinner = document.getElementById('loading-spinner');
    const scheduleTitleEl = document.getElementById('scheduleTitle');
    const scheduleTypeEl = document.getElementById('scheduleType');
    const backToEventLink = document.getElementById('backToEventLink');
    const switchStageBtn = document.getElementById('switchStageBtn');
    const switchStatusBtn = document.getElementById('switchStatusBtn');
    const viewResultsBtn = document.getElementById('viewResultsBtn');
    const settingsBtn = document.getElementById('settingsBtn');
    const addNewPositionBtn = document.getElementById('addNewPositionBtn');
    const nominationModal = document.getElementById('nominationModal');
    const nominationForm = document.getElementById('nominationForm');
    const nomineeInputContainer = document.getElementById('nomineeInputContainer');
    const scheduleDetailsContainer = document.getElementById('scheduleDetailsContainer');
    const facilitatorsListContainer = document.getElementById('facilitatorsListContainer');
    const positionsContainer = document.getElementById('positionsContainer');
    const overallStatsEl = document.getElementById('overallStats');
    const positionStatsEl = document.getElementById('positionStats');
    const nominationStatsEl = document.getElementById('nominationStats');
    const scheduleDetailsModal = document.getElementById('scheduleDetailsModal');
    const scheduleDetailsForm = document.getElementById('scheduleDetailsForm');
    const facilitatorsModal = document.getElementById('facilitatorsModal');
    const currentFacilitatorsList = document.getElementById('currentFacilitatorsList');
    const addFacilitatorForm = document.getElementById('addFacilitatorForm');
    const facilitatorSelect = document.getElementById('facilitatorSelect');
    const saveFacilitatorsBtn = document.getElementById('saveFacilitatorsBtn');
    const settingsModal = document.getElementById('settingsModal');
    const settingsForm = document.getElementById('electionSettingsForm');
    const positionModal = document.getElementById('positionModal');
    const positionForm = document.getElementById('positionForm');
    const candidateModal = document.getElementById('candidateModal');
    const candidateForm = document.getElementById('candidateForm');
    const promoteNominationsModal = document.getElementById('promoteNominationsModal');
    const promoteModalTitle = document.getElementById('promoteModalTitle');
    const nomineeListContainer = document.getElementById('nomineeListContainer');
    const promoteNominationsForm = document.getElementById('promoteNominationsForm');
    const genericConfirmModal = document.getElementById('genericConfirmModal');
    const confirmModalTitle = document.getElementById('confirmModalTitle');
    const confirmModalMessage = document.getElementById('confirmModalMessage');
    const confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');

    // --- 4. UTILITY FUNCTIONS ---
    const showSpinner = () => spinner && spinner.classList.remove('hidden');
    const hideSpinner = () => spinner && spinner.classList.add('hidden');
    
    // NEW: Debounce function to limit API calls while typing
    const debounce = (func, delay) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    };

    const showToast = (message, isError = false) => {
        const toastContainer = document.getElementById('toast');
        if (!toastContainer) { // Graceful fallback
            alert(message);
            return;
        }
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

    const renderEventHeader = (details) => {
        const eventHeaderContainer = document.getElementById('eventHeader');
        if (!eventHeaderContainer || !details || !details.event_title) return;

        const statusBadge = details.status === 'active' ?
            '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Active</span>' :
            '<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">Inactive</span>';

        eventHeaderContainer.innerHTML = `
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:truncate">${details.event_title}</h1>
                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6 text-slate-500">
                        <div class="mt-2 flex items-center text-sm">
                            <i class="fa-regular fa-calendar-days mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400"></i>
                            Schedule runs from ${formatDate(details.start_datetime)} to ${formatDate(details.end_datetime)}
                        </div>
                        <div class="mt-2 flex items-center text-sm">${statusBadge}</div>
                    </div>
                </div>
            </div>
        `;
    };

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

    // --- 5. RENDER FUNCTIONS --- (No changes in this section)
    const renderPage = () => {
        // FIXED: Call renderEventHeader with the correct data from the state.
        renderEventHeader(appState.details);
        renderSidebar();
        renderScheduleHeader();
        renderPositions();
    };

    const renderScheduleHeader = () => {
        const { details } = appState;
        if (!details.event_id) return; // Don't render if details aren't loaded

        backToEventLink.href = `/events/manage?id=${details.event_id}`;
        backToEventLink.innerHTML = `&larr; Back to ${details.event_title}`;
        scheduleTitleEl.textContent = details.title;
        scheduleTypeEl.innerHTML = `Election Schedule &bull; Part of <strong>${details.event_title}</strong>`;
        
        [scheduleTitleEl, scheduleTypeEl, backToEventLink].forEach(el => el.classList.remove('animate-pulse', 'bg-gray-200', 'rounded-md', 'h-6'));

        const stage = details.stage || 'nomination';
        if (stage === 'nomination') {
            addNewPositionBtn.textContent = '+ Add Nominee Manually';
            addNewPositionBtn.dataset.action = 'addNominee';
        } else {
            addNewPositionBtn.textContent = '+ Add New Position';
            addNewPositionBtn.dataset.action = 'addPosition';
        }
        const isNomination = stage === 'nomination';
        switchStageBtn.textContent = isNomination ? 'Switch to Voting' : 'Back to Nomination';
        switchStageBtn.className = `rounded-md px-3 py-1.5 text-sm font-semibold text-white shadow-sm ${isNomination ? 'bg-green-600 hover:bg-green-700' : 'bg-yellow-500 hover:bg-yellow-600'}`;
        switchStageBtn.dataset.nextStage = isNomination ? 'voting' : 'nomination';

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
                <div class="flex justify-between text-sm"><span class="text-gray-600">Eligible Voters:</span><span class="font-semibold">${stats.eligible_voters}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-600">Total Votes Cast:</span><span class="font-semibold">${stats.total_votes}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-600">Total Nominations:</span><span class="font-semibold">${stats.total_nominations}</span></div>`;

            positionStatsEl.innerHTML = stats.votes_by_position?.length > 0
                ? stats.votes_by_position.map(p => `<div class="flex justify-between text-sm"><span class="text-gray-600">${p.name}:</span><span class="font-semibold">${p.vote_count} votes</span></div>`).join('')
                : '<p class="text-sm text-gray-500">No votes cast yet.</p>';

            if (stats.nominations_by_position?.length > 0) {
                nominationStatsEl.innerHTML = stats.nominations_by_position.map(statItem => {
                    const position = appState.positions.find(pos => pos.name === statItem.name);
                    const positionId = position ? position.id : null; 

                    if (positionId) {
                        return `
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">${statItem.name}:</span>
                                <button class="font-semibold text-indigo-600 hover:underline open-promote-modal-btn" data-position-id="${positionId}" data-position-name="${statItem.name}">
                                    ${statItem.nomination_count} noms
                                </button>
                            </div>`;
                    }
                    return `
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">${statItem.name}:</span>
                                <span class="font-semibold text-gray-500">${statItem.nomination_count} noms (ID missing)</span>
                            </div>`;

                }).join('');
            } else {
                nominationStatsEl.innerHTML = '<p class="text-sm text-gray-500">No nominations yet.</p>';
            }
        } else {
            overallStatsEl.innerHTML = '<div class="h-12 bg-gray-200 animate-pulse rounded-md"></div>';
            positionStatsEl.innerHTML = '<div class="h-16 bg-gray-200 animate-pulse rounded-md"></div>';
            nominationStatsEl.innerHTML = '<div class="h-12 bg-gray-200 animate-pulse rounded-md"></div>';
        }
    };
    
    const renderPositions = () => {
        const stage = appState.details.stage || 'nomination';
        positionsContainer.innerHTML = '';
        if (appState.positions.length === 0) {
            positionsContainer.innerHTML = '<p class="text-center text-gray-500 bg-white p-8 rounded-lg shadow-sm">No election positions have been created yet.</p>';
            return;
        }
        appState.positions.forEach(pos => {
            const content = stage === 'nomination' ? renderNominationList(pos) : renderCandidateList(pos);
            positionsContainer.insertAdjacentHTML('beforeend', `
                <div class="bg-white rounded-lg shadow-sm" data-position-id="${pos.id}">
                    <div class="accordion-header flex items-center justify-between p-4 cursor-pointer border-b">
                        <div>
                            <h3 class="font-bold text-gray-900">${pos.name}</h3>
                            <p class="text-xs text-gray-600">${pos.description}</p>
                        </div>
                        <div class="flex items-center gap-x-3 flex-shrink-0">
                            <button class="edit-position-btn text-xs font-medium text-indigo-600 hover:underline">Edit</button>
                            <button class="delete-position-btn text-xs font-medium text-red-600 hover:underline">Delete</button>
                            <svg class="accordion-chevron h-5 w-5 transform transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </div>
                    </div>
                    <div class="accordion-content hidden p-4">${content}</div>
                </div>`);
        });
    };

    const renderNominationList = (position) => {
        if (!position.nominations || position.nominations.length === 0) return '<p class="text-sm text-center text-gray-500 py-4">No nominations yet.</p>';
        return `<h4 class="text-sm font-semibold mb-2">Received Nominations</h4>
            <ul class="divide-y divide-gray-200">
                ${position.nominations.map(nom => `
                    <li class="flex items-center justify-between py-2" data-nomination-id="${nom.id}">
                        <div>
                            <p class="text-sm font-medium text-gray-800">${nom.nominee_display_name}</p>
                            <p class="text-xs text-gray-500">Nominated by: ${nom.nominator_name}</p>
                        </div>
                    </li>`).join('')}
            </ul>`;
    };

    const openPromoteModal = async (positionId, positionName) => {
        promoteModalTitle.textContent = `Promote Nominees for: ${positionName}`;
        nomineeListContainer.innerHTML = '<div class="h-24 bg-gray-200 animate-pulse rounded-md"></div>'; 
        promoteNominationsForm.dataset.seatId = positionId; 
        promoteNominationsModal.classList.remove('hidden');

        const result = await fetchAPI(`${API.getNomineesForPosition}?position_id=${positionId}`);
        
        if (result.success && result.data.length > 0) {
            nomineeListContainer.innerHTML = result.data.map(nominee => `
                <div class="flex items-center">
                    <input id="nominee-${nominee.id}" name="nomination_ids[]" type="checkbox" value="${nominee.id}" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="nominee-${nominee.id}" class="ml-3 text-sm text-gray-700">${nominee.display_name}</label>
                </div>
            `).join('');
        } else {
            nomineeListContainer.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No nominations found to promote.</p>';
        }
    };

    const renderCandidateList = (position) => {
        const listContent = !position.candidates || position.candidates.length === 0
            ? '<p class="text-sm text-center text-gray-500 py-4">No candidates for this position.</p>'
            : `<ul class="divide-y divide-gray-200">
                ${position.candidates.map(can => `
                    <li class="flex items-center justify-between py-2" data-candidate-id="${can.id}">
                        <div>
                            <p class="text-sm font-medium text-gray-800">${can.name}</p>
                            <p class="text-xs text-gray-500">${can.description || ''}</p>
                        </div>
                        <div class="flex items-center gap-x-4">
                            <span class="results-display ${appState.resultsVisible ? '' : 'hidden'} text-lg font-bold text-indigo-600">${can.votes} Votes</span>
                            <button class="edit-candidate-btn text-xs font-medium text-gray-600 hover:underline">Edit</button>
                            <button class="delete-candidate-btn text-xs font-medium text-red-600 hover:underline">Delete</button>
                        </div>
                    </li>`).join('')}
            </ul>`;
        return `<div class="flex justify-between items-center mb-2">
                <h4 class="text-sm font-semibold">Ballot Candidates</h4>
                <button class="add-candidate-btn text-xs font-medium text-indigo-600 hover:underline">+ Add Manually</button>
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
            addFacilitatorForm.style.display = 'block';
        } else {
            addFacilitatorForm.style.display = 'none';
        }
        facilitatorsModal.classList.remove('hidden');
    };

    const openSettingsModal = async () => {
        if (appState.membershipTypes.length === 0) {
            const result = await fetchAPI(API.getMembershipTypes);
            if (result.success) {
                appState.membershipTypes = result.data;
            }
        }

        const eligibilityCheckboxesContainer = document.getElementById('eligibilityCheckboxesContainer');
        eligibilityCheckboxesContainer.innerHTML = ''; 

        let checkboxesHtml = `
            <div class="space-y-2">
                <div class="font-semibold text-sm text-gray-800 pt-2">General Groups</div>
                <div class="flex items-center">
                    <input id="el-all-users" name="eligibility_criteria[]" type="checkbox" value="all_users" class="h-4 w-4 border-gray-300 text-indigo-600">
                    <label for="el-all-users" class="ml-3 text-sm text-gray-700">All Users (members and non-members)</label>
                </div>
                <div class="flex items-center">
                    <input id="el-all-members" name="eligibility_criteria[]" type="checkbox" value="all_members" class="h-4 w-4 border-gray-300 text-indigo-600">
                    <label for="el-all-members" class="ml-3 text-sm text-gray-700">All Member Users</label>
                </div>
                <div class="flex items-center">
                    <input id="el-non-members" name="eligibility_criteria[]" type="checkbox" value="non_members" class="h-4 w-4 border-gray-300 text-indigo-600">
                    <label for="el-non-members" class="ml-3 text-sm text-gray-700">Non-Member Users Only</label>
                </div>
            </div>`;

        if (appState.membershipTypes.length > 0) {
            checkboxesHtml += `<div class="font-semibold text-sm text-gray-800 pt-3 border-t mt-3">By Membership Type</div>`;
            appState.membershipTypes.forEach(type => {
                checkboxesHtml += `
                    <div class="flex items-center">
                        <input id="type-${type.id}" name="eligibility_criteria[]" type="checkbox" value="type_${type.id}" class="h-4 w-4 border-gray-300 text-indigo-600">
                        <label for="type-${type.id}" class="ml-3 text-sm text-gray-700">${type.name}</label>
                    </div>`;
            });
        }
        eligibilityCheckboxesContainer.innerHTML = checkboxesHtml;

        const savedSettings = appState.details.settings?.eligibility || {};

        if (savedSettings.all_tickets) {
            const cb = settingsForm.querySelector('input[value="all_users"]');
            if (cb) cb.checked = true;
        }
        if (savedSettings.only_members) {
            const cb = settingsForm.querySelector('input[value="all_members"]');
            if (cb) cb.checked = true;
        }
        if (savedSettings.non_members) {
            const cb = settingsForm.querySelector('input[value="non_members"]');
            if (cb) cb.checked = true;
        }

        if (Array.isArray(savedSettings.member_types)) {
            savedSettings.member_types.forEach(typeId => {
                const cb = settingsForm.querySelector(`input[value="type_${typeId}"]`);
                if (cb) cb.checked = true;
            });
        }

        settingsModal.classList.remove('hidden');
    };

    const openPositionModal = (position = null) => {
        positionForm.reset();
        document.getElementById('positionModalTitle').textContent = position ? 'Edit Position' : 'Add New Position';
        positionForm.querySelector('[name="id"]').value = position ? position.id : '';
        if (position) {
            positionForm.querySelector('[name="name"]').value = position.name;
            positionForm.querySelector('[name="nominee_type"]').value = position.nominee_type;
            positionForm.querySelector('[name="description"]').value = position.description;
        }
        positionModal.classList.remove('hidden');
    };

    const openCandidateModal = (positionId, candidate = null) => {
        candidateForm.reset();
        document.getElementById('candidateModalTitle').textContent = candidate ? 'Edit Candidate' : 'Add New Candidate';
        candidateForm.querySelector('[name="seat_id"]').value = positionId;
        candidateForm.querySelector('[name="id"]').value = candidate ? candidate.id : '';
        if (candidate) {
            candidateForm.querySelector('[name="name"]').value = candidate.name;
            candidateForm.querySelector('[name="description"]').value = candidate.description;
        }
        candidateModal.classList.remove('hidden');
    };

    // MODIFIED: This function is now much simpler. It just opens the modal.
    const openNominationModal = async () => {
        nominationForm.reset();
        nomineeInputContainer.innerHTML = '<p class="text-sm text-gray-500">Please select a position to see nominee options.</p>';

        const positionSelect = nominationForm.querySelector('[name="seat_id"]');
        const nominationPositions = appState.positions.filter(p => p.nominee_type);
        positionSelect.innerHTML = '<option value="">-- Select Position --</option>' +
            nominationPositions.map(p => `<option value="${p.id}">${p.name}</option>`).join('');

        nominationModal.classList.remove('hidden');
    };

    // --- 7. EVENT HANDLERS ---
    async function handleSaveDetails(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        data.id = scheduleId;
        const result = await fetchAPI(API.updateDetails, { method: 'POST', body: JSON.stringify(data) });
        showToast(result.message, !result.success);
        if (result.success) {
            scheduleDetailsModal.classList.add('hidden');
            initializePage();
        }
    }

    async function handlePromoteNominations(e) {
        e.preventDefault();
        const seatId = e.target.dataset.seatId;
        const formData = new FormData(e.target);
        const nominationIds = formData.getAll('nomination_ids[]').map(id => parseInt(id, 10));

        if (nominationIds.length === 0) {
            showToast('Please select at least one nominee to promote.', true);
            return;
        }

        const result = await fetchAPI(API.promoteNominations, {
            method: 'POST',
            body: JSON.stringify({
                seat_id: seatId,
                nomination_ids: nominationIds
            })
        });

        if (result.success) {
            showToast(result.message || 'Nominees promoted successfully!');
            promoteNominationsModal.classList.add('hidden');
            initializePage(); 
        }
    }

    // MODIFIED: This function now gets the correct nominee ID from the hidden input.
    async function handleSaveNomination(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
    
        // Clean up the data object to only send relevant nominee ID
        if(data.nominee_user_id) delete data.nominee_company_id;
        if(data.nominee_company_id) delete data.nominee_user_id;

        const result = await fetchAPI(API.createNominationManual, {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (result.success) {
            showToast('Nomination added successfully.');
            nominationModal.classList.add('hidden');
            initializePage(); 
        }
    }
    
    async function handleSaveFacilitators() {
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
    }
    async function handleSwitchStage(e) {
        const nextStage = e.target.dataset.nextStage;
        showGenericConfirm({
            title: `Switch to ${nextStage.charAt(0).toUpperCase() + nextStage.slice(1)} Stage`,
            message: `Are you sure you want to switch the election to the <strong>${nextStage}</strong> stage? This action cannot be undone.`,
            confirmText: 'Yes, Switch Stage',
            onConfirm: async () => {
                const result = await fetchAPI(API.updateStage, { method: 'POST', body: JSON.stringify({ stage: nextStage }) });
                if (result.success) {
                    showToast(`Election stage switched to ${nextStage}.`);
                    initializePage();
                }
            }
        });
    }
    async function handleSwitchStatus(e) {
        const nextStatus = e.target.dataset.nextStatus;
        const result = await fetchAPI(API.updateStatus, { method: 'POST', body: JSON.stringify({ status: nextStatus }) });
        if (result.success) {
            showToast(`Schedule status set to ${nextStatus}.`);
            initializePage();
        }
    }
    function handleViewResults() {
        appState.resultsVisible = !appState.resultsVisible;
        viewResultsBtn.textContent = appState.resultsVisible ? 'Hide Results' : 'View Results';
        viewResultsBtn.classList.toggle('bg-indigo-100', appState.resultsVisible);
        renderPositions();
    }
    async function handleSaveSettings(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const checkedValues = formData.getAll('eligibility_criteria[]');
        const newEligibilitySettings = {
            all_tickets: false,
            non_members: false,
            only_members: false,
            member_types: []
        };

        checkedValues.forEach(value => {
            if (value === 'all_users') newEligibilitySettings.all_tickets = true;
            else if (value === 'all_members') newEligibilitySettings.only_members = true;
            else if (value === 'non_members') newEligibilitySettings.non_members = true;
            else if (value.startsWith('type_')) {
                const typeId = parseInt(value.split('_')[1], 10);
                if (!isNaN(typeId)) newEligibilitySettings.member_types.push(typeId);
            }
        });

        const result = await fetchAPI(API.saveSettings, {
            method: 'POST',
            body: JSON.stringify({ settings: { eligibility: newEligibilitySettings } })
        });

        if (result.success) {
            showToast('Settings saved successfully.');
            settingsModal.classList.add('hidden');
            initializePage();
        }
    }
    async function handleSavePosition(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const id = formData.get('id');
        const url = id ? API.updatePosition : API.createPosition;
        const result = await fetchAPI(url, { method: 'POST', body: JSON.stringify(Object.fromEntries(formData)) });
        if (result.success) {
            showToast(`Position ${id ? 'updated' : 'created'} successfully.`);
            positionModal.classList.add('hidden');
            initializePage();
        }
    }
    async function handleSaveCandidate(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const id = formData.get('id');
        const url = id ? API.updateCandidate : API.createCandidate;
        const result = await fetchAPI(url, { method: 'POST', body: JSON.stringify(Object.fromEntries(formData)) });
        if (result.success) {
            showToast(`Candidate ${id ? 'updated' : 'created'} successfully.`);
            candidateModal.classList.add('hidden');
            initializePage();
        }
    }

    function handlePositionActions(e) {
        const target = e.target;
        const positionDiv = target.closest('[data-position-id]');
        if (!positionDiv) return; 

        const positionId = positionDiv.dataset.positionId;
        const position = appState.positions.find(p => p.id == positionId);

        if (target.classList.contains('edit-position-btn')) {
            openPositionModal(position);
            return;
        }

        if (target.classList.contains('delete-position-btn')) {
            showGenericConfirm({
                title: 'Delete Position',
                message: `Are you sure you want to delete the position "<strong>${position.name}</strong>"? This will remove all its candidates and nominations.`,
                confirmText: 'Yes, Delete',
                onConfirm: async () => {
                    const result = await fetchAPI(API.deletePosition, { method: 'POST', body: JSON.stringify({ id: positionId }) });
                    if (result.success) {
                        showToast('Position deleted.');
                        initializePage();
                    }
                }
            });
            return;
        }

        if (target.classList.contains('add-candidate-btn')) {
            openCandidateModal(positionId);
            return;
        }
        
        const candidateLi = target.closest('[data-candidate-id]');
        if (candidateLi) {
            const candidateId = candidateLi.dataset.candidateId;
            const candidate = position.candidates.find(c => c.id == candidateId);

            if (target.classList.contains('edit-candidate-btn')) {
                openCandidateModal(positionId, candidate);
            } else if (target.classList.contains('delete-candidate-btn')) {
                showGenericConfirm({
                    title: 'Delete Candidate',
                    message: `Are you sure you want to delete the candidate "<strong>${candidate.name}</strong>"?`,
                    confirmText: 'Yes, Delete',
                    onConfirm: async () => {
                        const result = await fetchAPI(API.deleteCandidate, { method: 'POST', body: JSON.stringify({ id: candidateId }) });
                        if (result.success) {
                            showToast('Candidate deleted.');
                            initializePage();
                        }
                    }
                });
            }
            return; 
        }

        const header = target.closest('.accordion-header');
        if (header) {
            header.nextElementSibling.classList.toggle('hidden');
            header.querySelector('.accordion-chevron').classList.toggle('rotate-180');
        }
    }

    // --- 8. EVENT LISTENER ATTACHMENT ---
    const attachEventListeners = () => {
        // ... (all previous event listeners remain the same) ...
        document.getElementById('editDetailsBtn').addEventListener('click', openDetailsModal);
        document.getElementById('manageFacilitatorsBtn').addEventListener('click', openFacilitatorsModal);
        switchStageBtn.addEventListener('click', handleSwitchStage);
        switchStatusBtn.addEventListener('click', handleSwitchStatus);
        viewResultsBtn.addEventListener('click', handleViewResults);
        settingsBtn.addEventListener('click', openSettingsModal);
        addNewPositionBtn.addEventListener('click', (e) => {
            if (e.currentTarget.dataset.action === 'addNominee') openNominationModal();
            else openPositionModal();
        });
        scheduleDetailsForm.addEventListener('submit', handleSaveDetails);
        saveFacilitatorsBtn.addEventListener('click', handleSaveFacilitators);
        settingsForm.addEventListener('submit', handleSaveSettings);
        positionForm.addEventListener('submit', handleSavePosition);
        candidateForm.addEventListener('submit', handleSaveCandidate);
        nominationForm.addEventListener('submit', handleSaveNomination);
        positionsContainer.addEventListener('click', handlePositionActions);
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
        nominationStatsEl.addEventListener('click', e => {
            const button = e.target.closest('.open-promote-modal-btn');
            if (button) {
                const positionId = button.dataset.positionId;
                const positionName = button.dataset.positionName;
                openPromoteModal(positionId, positionName);
            }
        });
        promoteNominationsForm.addEventListener('submit', handlePromoteNominations);


        // MODIFIED: This entire section is new or heavily changed to support the live search component.
        const positionSelect = nominationForm.querySelector('[name="seat_id"]');
        positionSelect.addEventListener('change', (e) => {
            const positionId = e.target.value;
            if (!positionId) {
                nomineeInputContainer.innerHTML = '<p class="text-sm text-gray-500">Please select a position.</p>';
                return;
            }
            const position = appState.positions.find(p => p.id == positionId);
            let inputHtml = '';
            
            // This function creates the HTML for our new search component
            const createSearchComponent = (type, label, hiddenInputName) => {
                return `
                    <div class="relative" data-search-type="${type}">
                        <label for="nom-search" class="block text-sm font-medium text-gray-700">${label}</label>
                        <input type="text" id="nom-search" autocomplete="off" placeholder="Start typing to search..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm searchable-input">
                        <input type="hidden" name="${hiddenInputName}" class="searchable-hidden-input">
                        <div class="search-results absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                `;
            };

            switch (position.nominee_type) {
                case 'user':
                    inputHtml = createSearchComponent('user', 'Select Nominee (User)', 'nominee_user_id');
                    break;
                case 'company':
                    inputHtml = createSearchComponent('company', 'Select Nominee (Company)', 'nominee_company_id');
                    break;
                case 'idea':
                    inputHtml = `
                        <label for="nom-idea" class="block text-sm font-medium text-gray-700">Describe Nominee (Idea/Text)</label>
                        <textarea name="nomination_text" id="nom-idea" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Enter the name or description of the idea."></textarea>`;
                    break;
                default:
                    inputHtml = '<p class="text-sm text-red-500">This position type cannot be nominated manually.</p>';
            }
            nomineeInputContainer.innerHTML = inputHtml;
        });
        
        // NEW: Event delegation for the search inputs inside the nomination modal
        nominationModal.addEventListener('input', debounce(async (e) => {
            if (!e.target.classList.contains('searchable-input')) return;

            const container = e.target.parentElement;
            const searchType = container.dataset.searchType;
            const resultsContainer = container.querySelector('.search-results');
            const hiddenInput = container.querySelector('.searchable-hidden-input');
            const term = e.target.value;

            hiddenInput.value = ''; // Clear hidden value if user is typing again

            if (term.length < 2) {
                resultsContainer.innerHTML = '';
                resultsContainer.classList.add('hidden');
                return;
            }

            const result = await fetchAPI(`${API.search}?type=${searchType}&term=${term}`);

            resultsContainer.innerHTML = '';
            if (result.success && result.data.length > 0) {
                result.data.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-2 cursor-pointer hover:bg-gray-100';
                    li.textContent = item.text;
                    li.dataset.id = item.id;
                    resultsContainer.appendChild(li);
                });
                resultsContainer.classList.remove('hidden');
            } else {
                resultsContainer.innerHTML = '<li class="px-4 py-2 text-gray-500">No results found.</li>';
                resultsContainer.classList.remove('hidden');
            }
        }, 300));
        
        // NEW: Event delegation for clicking on a search result
        nominationModal.addEventListener('click', (e) => {
            const resultsContainer = e.target.closest('.search-results');
            if (!resultsContainer || e.target.tagName !== 'LI' || !e.target.dataset.id) return;
            
            const container = resultsContainer.parentElement;
            const searchInput = container.querySelector('.searchable-input');
            const hiddenInput = container.querySelector('.searchable-hidden-input');
            
            searchInput.value = e.target.textContent;
            hiddenInput.value = e.target.dataset.id;
            
            resultsContainer.innerHTML = '';
            resultsContainer.classList.add('hidden');
        });
    };

    // --- 9. INITIALIZATION ---
    async function initializePage() {
        if (!scheduleId) {
            container.innerHTML = '<p class="text-center text-red-500">Error: No Schedule ID found.</p>';
            return;
        }
        const result = await fetchAPI(API.getDashboard);
        if (result.success) {
            appState.details = result.data.details || {};
            appState.facilitators = result.data.facilitators || [];
            appState.positions = result.data.positions || [];
            appState.stats = result.data.stats || {};
            renderPage();
        } else {
            container.innerHTML = `<p class="text-center text-red-500 bg-white p-8 rounded-lg">Failed to load election data. Please try again.</p>`;
        }
    }
    
    initializePage();
    attachEventListeners();
});