document.addEventListener('DOMContentLoaded', function() {
    // --- STATE & CONSTANTS ---
    const urlParams = new URLSearchParams(window.location.search);
    const scheduleId = urlParams.get('id');
    let eventId = null; // Will be fetched from schedule details
    let currentData = {}; // Cache for all fetched data
    let allUsers = []; // Cache for user list
    let confirmAction = null;

    // --- DOM ELEMENTS ---
    const spinner = document.getElementById('loading-spinner');
    const toast = document.getElementById('toast');
    const addMaterialBtn = document.getElementById('addMaterialBtn');
    const materialsContainer = document.getElementById('materialsContainer');
    const materialModal = document.getElementById('materialModal');
    const materialModalTitle = document.getElementById('materialModalTitle');
    const materialForm = document.getElementById('materialForm');
    const materialTypeSelect = materialForm.querySelector('select[name="type"]');
    const materialUrlOrFileContainer = document.getElementById('materialUrlOrFileContainer');
    const editDetailsBtn = document.getElementById('editDetailsBtn');
    const scheduleDetailsModal = document.getElementById('scheduleDetailsModal');
    const scheduleDetailsForm = document.getElementById('scheduleDetailsForm');
    const manageFacilitatorsBtn = document.getElementById('manageFacilitatorsBtn');
    const facilitatorsModal = document.getElementById('facilitatorsModal');
    const currentFacilitatorsList = document.getElementById('currentFacilitatorsList');
    const addFacilitatorForm = document.getElementById('addFacilitatorForm');
    const facilitatorSelect = document.getElementById('facilitatorSelect');
    const saveFacilitatorsBtn = document.getElementById('saveFacilitatorsBtn');

    const genericConfirmModal = document.getElementById('genericConfirmModal');
    const confirmModalTitle = document.getElementById('confirmModalTitle');
    const confirmModalMessage = document.getElementById('confirmModalMessage');
    const confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');


    // --- UTILITIES ---
    const showSpinner = () => spinner && spinner.classList.remove('hidden');
    const hideSpinner = () => spinner && spinner.classList.add('hidden');

    const showToast = (message, isError = false) => {
        toast.textContent = message;
        toast.className = `fixed top-5 right-5 px-4 py-2 rounded-md shadow-lg transition-opacity duration-300 ${isError ? 'bg-red-600 text-white' : 'bg-green-600 text-white'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    };

    // --- **NEW**: GENERIC CONFIRMATION MODAL LOGIC ---
    const showGenericConfirm = (config) => {
        confirmModalTitle.textContent = config.title || 'Confirm Action';
        confirmModalMessage.innerHTML = config.message || 'Are you sure? This action cannot be undone.';
        confirmModalConfirmBtn.textContent = config.confirmText || 'Confirm';
        confirmAction = config.onConfirm; // Store the action to run
        genericConfirmModal.classList.remove('hidden');
    };

    confirmModalConfirmBtn.addEventListener('click', () => {
        if (typeof confirmAction === 'function') {
            confirmAction(); // Execute the stored action
        }
        genericConfirmModal.classList.add('hidden');
        confirmAction = null; // Reset for next use
    });

    const formatDate = (ds) => ds ? new Date(ds).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' }) : 'N/A';
    const formatToDateTimeLocal = (ds) => {
        if (!ds) return '';
        const date = new Date(ds);
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 16);
    };

    // --- RENDER FUNCTIONS ---
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

    const renderPage = (data) => {
        currentData = data;
        const { details, facilitators, materials } = data;

        document.getElementById('scheduleHeader').innerHTML = `
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">${details.title} (Training Session)</h1>
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                </div>
                <a href="/events/manage?id=${details.event_id}" class="rounded-lg bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">&larr; Back to Event</a> <br>
            </div>
            `;

        document.getElementById('scheduleDetails').innerHTML = `
            <p><strong>Starts:</strong> ${formatDate(details.start_datetime)}</p>
            <p><strong>Ends:</strong> ${formatDate(details.end_datetime)}</p>
            <p><strong>Status:</strong> <span class="capitalize px-2 py-1 text-xs font-medium rounded-full ${details.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">${details.status}</span></p>
            <p class="mt-2 pt-2 border-t text-gray-600">${details.description || 'No description provided.'}</p>`;

        renderFacilitators(facilitators);
        renderMaterials(materials);
    };

    const renderFacilitators = (facilitators) => {
        document.getElementById('facilitatorsList').innerHTML = facilitators.length > 0 ?
            facilitators.map(f => `<p class="flex items-center gap-x-2"><svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-5.5-2.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zM10 12a5.99 5.99 0 00-4.793 2.39A6.483 6.483 0 0010 16.5a6.483 6.483 0 004.793-2.11A5.99 5.99 0 0010 12z" clip-rule="evenodd" /></svg>${f.full_name}</p>`).join('') :
            `<p class="text-gray-500">No facilitators assigned.</p>`;
    };

    const renderMaterials = (materials) => {
        if (!materials || materials.length === 0) {
            materialsContainer.innerHTML = `<div class="text-center py-10 border-2 border-dashed border-gray-300 rounded-lg"><h3 class="mt-2 text-sm font-semibold text-gray-900">No Materials</h3><p class="mt-1 text-sm text-gray-500">Get started by adding a training material.</p></div>`;
        } else {
            const tableRows = materials.map(mat => {
                let actionHtml;
                if (mat.type === 'link' && mat.url) {
                    actionHtml = `<a href="${mat.url}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-900">Open Link</a>`;
                } else if (mat.url) {
                    const downloadUrl = `/api/events/schedules/materials/download.php?id=${mat.id}`;
                    actionHtml = `<a href="${downloadUrl}" class="text-indigo-600 hover:text-indigo-900">Download</a>`;
                } else {
                    actionHtml = `<span class="text-gray-400">Not available</span>`;
                }
                return `
                    <tr id="material-row-${mat.id}">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0"><div class="font-medium text-gray-900">${mat.title}</div><div class="text-gray-500">${mat.description || ''}</div></td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 capitalize">${mat.type}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${actionHtml}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <button class="edit-material-btn text-indigo-600 hover:text-indigo-900" data-id="${mat.id}">Edit</button>
                            <button class="delete-material-btn text-red-600 hover:text-red-900 ml-4" data-id="${mat.id}">Delete</button>
                        </td>
                    </tr>`;
            }).join('');
            materialsContainer.innerHTML = `
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8"><div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead><tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Title</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Action</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200">${tableRows}</tbody>
                    </table>
                </div></div>`;
        }
    };

    const performMaterialDeletion = async (materialId) => {
        showSpinner();
        try {
            const response = await fetch('/api/events/schedules/materials/delete.php', {
                method: 'POST',
                body: JSON.stringify({ id: materialId }),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            showToast(result.message, !result.success);
            if (result.success) {
                initializePage(); // Refresh the page content on success
            }
        } catch (error) {
            showToast('An error occurred while deleting.', true);
        } finally {
            hideSpinner();
        }
    };

    // --- API CALLS ---
    const loadEventDetails = async () => {
        if (!eventId) return; // Wait until we have eventId
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
        }
    };
    
    const initializePage = async () => {
        if (!scheduleId) {
            document.body.innerHTML = '<p class="text-red-500 text-center p-8">Error: Schedule ID is missing from the URL.</p>';
            return;
        }
        showSpinner();
        try {
            const response = await fetch(`/api/events/schedules/read_training_details.php?id=${scheduleId}`);
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Failed to load data.');
            
            eventId = data.details.event_id; // Get the event ID from the response
            renderPage(data);
            await loadEventDetails(); // Now load the main event header
        } catch (error) {
            showToast(error.message, true);
        } finally {
            hideSpinner();
        }
    };

    const fetchAllUsers = async () => {
        if (allUsers.length > 0) return;
        showSpinner();
        try {
            const response = await fetch('/api/users/read_all.php');
            const result = await response.json();
            if (result.success) {
                allUsers = result.data;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            showToast('Could not load user list for facilitators.', true);
        } finally {
            hideSpinner();
        }
    };

    // --- MODAL & FORM LOGIC ---
    const updateMaterialInput = (type = 'link') => {
        if (type === 'link') {
            materialUrlOrFileContainer.innerHTML = `<label for="url" class="block text-sm font-medium text-gray-700">URL</label><input type="text" name="url" id="url" placeholder="https://..." required class="w-full rounded-lg border-1 px-4 py-2 text-sm">`;
        } else {
            const fileTypeAccepts = { 'video': 'video/*', 'pdf': 'application/pdf,.pdf', 'ppt': '.ppt,.pptx', 'audio': 'audio/*' };
            const acceptAttr = fileTypeAccepts[type] || '';
            materialUrlOrFileContainer.innerHTML = `<label for="material_file" class="block text-sm font-medium text-gray-700">File</label><input type="file" name="material_file" id="material_file" accept="${acceptAttr}" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"><input type="hidden" name="url"><p class="mt-1 text-xs text-gray-500">To keep the current file, leave this blank. Size: less than 50MB.</p>`;
        }
    };

    const openModalForCreateMaterial = () => {
        materialForm.reset();
        materialForm.querySelector('input[name="id"]').value = '';
        materialModalTitle.textContent = 'Add Training Material';
        updateMaterialInput('link');
        materialModal.classList.remove('hidden');
    };

    const openModalForEditMaterial = (materialId) => {
        const material = currentData.materials.find(m => m.id == materialId);
        if (!material) return;
        materialForm.reset();
        materialForm.querySelector('input[name="id"]').value = material.id;
        materialForm.querySelector('input[name="title"]').value = material.title;
        materialTypeSelect.value = material.type;
        materialForm.querySelector('textarea[name="description"]').value = material.description || '';
        updateMaterialInput(material.type);
        if (material.type === 'link') {
            materialForm.querySelector('input[name="url"]').value = material.url || '';
        } else {
            const container = materialForm.querySelector('#materialUrlOrFileContainer');
            if (material.url) {
                const downloadUrl = `/api/events/schedules/materials/download.php?id=${material.id}`;
                const currentFileHtml = `<p class="mt-2 text-sm text-gray-600">Current file: <a target="blank" href="${downloadUrl}" class="text-indigo-600">${material.url.split('/').pop()}</a></p>`;
                container.insertAdjacentHTML('beforeend', currentFileHtml);
            }
            container.querySelector('input[name="url"]').value = material.url || '';
        }
        materialModalTitle.textContent = 'Edit Training Material';
        materialModal.classList.remove('hidden');
    };
    
    const openDetailsModal = () => {
        const { details } = currentData;
        scheduleDetailsForm.querySelector('input[name="title"]').value = details.title;
        scheduleDetailsForm.querySelector('input[name="start_datetime"]').value = formatToDateTimeLocal(details.start_datetime);
        scheduleDetailsForm.querySelector('input[name="end_datetime"]').value = formatToDateTimeLocal(details.end_datetime);
        scheduleDetailsForm.querySelector('select[name="status"]').value = details.status;
        scheduleDetailsForm.querySelector('textarea[name="description"]').value = details.description || '';
        scheduleDetailsModal.classList.remove('hidden');
    };

    const openFacilitatorsModal = async () => {
        await fetchAllUsers();
        const { facilitators } = currentData;
        const facilitatorIds = facilitators.map(f => parseInt(f.id));
        currentFacilitatorsList.innerHTML = facilitators.length > 0 ?
            facilitators.map(f => `<div class="flex justify-between items-center py-1"><span>${f.full_name}</span><button data-id="${f.id}" class="remove-facilitator-btn text-xs text-red-500 hover:underline">Remove</button></div>`).join('') :
            '<p class="text-sm text-gray-500">No facilitators yet.</p>';
        const availableUsers = allUsers.filter(u => !facilitatorIds.includes(parseInt(u.id)));
        if (availableUsers.length > 0) {
            facilitatorSelect.innerHTML = availableUsers.map(u => `<option value="${u.id}">${u.full_name}</option>`).join('');
            addFacilitatorForm.style.display = 'block';
        } else {
            addFacilitatorForm.style.display = 'none';
        }
        facilitatorsModal.classList.remove('hidden');
    };

    // --- EVENT LISTENERS ---
    addMaterialBtn.addEventListener('click', openModalForCreateMaterial);
    materialTypeSelect.addEventListener('change', () => updateMaterialInput(materialTypeSelect.value));
    
    materialForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showSpinner();
        try {
            const response = await fetch('/api/events/schedules/materials/save_material.php', {
                method: 'POST', body: new FormData(e.target)
            });
            const result = await response.json();
            showToast(result.message, !result.success);
            if (result.success) {
                materialModal.classList.add('hidden');
                initializePage();
            }
        } catch (error) {
            showToast('An error occurred during submission.', true);
        } finally {
            hideSpinner();
        }
    });

    document.body.addEventListener('click', (e) => {
        if (e.target.matches('.edit-material-btn')) openModalForEditMaterial(e.target.dataset.id);
        if (e.target.matches('.delete-material-btn')) {
            const materialId = e.target.dataset.id;
            showGenericConfirm({
                title: 'Delete Material',
                message: 'Are you sure you want to delete this training material? This action cannot be undone.',
                confirmText: 'Delete',
                onConfirm: () => performMaterialDeletion(materialId)
            });
        }

        if (e.target.matches('.remove-facilitator-btn')) {
            const userId = parseInt(e.target.dataset.id);
            currentData.facilitators = currentData.facilitators.filter(f => f.id != userId);
            openFacilitatorsModal();
        }

        // Universal close button for all modals, including the new confirmation one.
        if (e.target.matches('.close-modal')) {
            e.target.closest('.fixed.inset-0')?.classList.add('hidden');
        }
    });

    editDetailsBtn.addEventListener('click', openDetailsModal);
    scheduleDetailsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showSpinner();
        try {
            const response = await fetch('/api/events/schedules/update_schedule_details.php', {
                method: 'POST', body: JSON.stringify(Object.fromEntries(new FormData(e.target))), headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            showToast(result.message, !result.success);
            if (result.success) {
                scheduleDetailsModal.classList.add('hidden');
                initializePage();
            }
        } catch (error) {
            showToast('Error saving schedule details.', true);
        } finally {
            hideSpinner();
        }
    });

    manageFacilitatorsBtn.addEventListener('click', openFacilitatorsModal);
    addFacilitatorForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const userId = parseInt(facilitatorSelect.value);
        if (!userId) return;
        const user = allUsers.find(u => u.id == userId);
        if (user && !currentData.facilitators.some(f => f.id == userId)) {
            currentData.facilitators.push({ id: user.id, full_name: user.full_name });
            openFacilitatorsModal();
        }
    });
    saveFacilitatorsBtn.addEventListener('click', async () => {
        const facilitatorIds = currentData.facilitators.map(f => f.id);
        showSpinner();
        try {
            const response = await fetch('/api/events/schedules/manage_facilitators.php', {
                method: 'POST',
                body: JSON.stringify({ schedule_id: scheduleId, facilitator_ids: facilitatorIds }),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            showToast(result.message, !result.success);
            if (result.success) {
                facilitatorsModal.classList.add('hidden');
                renderFacilitators(currentData.facilitators);
            }
        } catch (error) {
            showToast('Error saving facilitators.', true);
        } finally {
            hideSpinner();
        }
    });

    // --- INITIALIZE ---
    initializePage();
});