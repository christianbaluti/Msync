


document.addEventListener('DOMContentLoaded', function() {
    // --- STATE & CONSTANTS ---
    const urlParams = new URLSearchParams(window.location.search);
    const scheduleId = urlParams.get('id');
    let currentData = {}; // Cache for all fetched data
    let allUsers = []; // Cache for user list

    // --- DOM ELEMENTS ---
    const toast = document.getElementById('toast');
    const addMaterialBtn = document.getElementById('addMaterialBtn');
    const materialsContainer = document.getElementById('materialsContainer');
    const materialModal = document.getElementById('materialModal');
    const materialModalTitle = document.getElementById('materialModalTitle');
    const materialForm = document.getElementById('materialForm');
    const materialTypeSelect = materialForm.querySelector('select[name="type"]');
    const materialUrlOrFileContainer = document.getElementById('materialUrlOrFileContainer');
    const viewerModal = document.getElementById('viewerModal');
    const viewerModalTitle = document.getElementById('viewerModalTitle');
    const viewerModalContent = document.getElementById('viewerModalContent');
    const editDetailsBtn = document.getElementById('editDetailsBtn');
    const scheduleDetailsModal = document.getElementById('scheduleDetailsModal');
    const scheduleDetailsForm = document.getElementById('scheduleDetailsForm');
    const manageFacilitatorsBtn = document.getElementById('manageFacilitatorsBtn');
    const facilitatorsModal = document.getElementById('facilitatorsModal');
    const currentFacilitatorsList = document.getElementById('currentFacilitatorsList');
    const addFacilitatorForm = document.getElementById('addFacilitatorForm');
    const facilitatorSelect = document.getElementById('facilitatorSelect');
    const saveFacilitatorsBtn = document.getElementById('saveFacilitatorsBtn');

    // --- UTILITIES ---
    const showToast = (message, isError = false) => {
        toast.textContent = message;
        toast.className = `fixed top-5 right-5 px-4 py-2 rounded-md shadow-lg transition-opacity duration-300 ${isError ? 'bg-red-600 text-white' : 'bg-green-600 text-white'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    };
    const formatDate = (ds) => ds ? new Date(ds).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' }) : 'N/A';
    const formatToDateTimeLocal = (ds) => {
        if (!ds) return '';
        const date = new Date(ds);
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 16);
    };

    // --- RENDER FUNCTIONS ---
    const renderPage = (data) => {
        currentData = data;
        const { details, facilitators, materials } = data;

        document.getElementById('scheduleHeader').innerHTML = `
            <a href="/events/manage?id=${details.event_id}" class="text-sm font-medium text-indigo-600 hover:underline">&larr; Back to ${details.event_title}</a>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">${details.title} (Training Session)</h1>`;

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
                let viewAction;
                if (mat.type === 'link' && mat.url) {
                    viewAction = `<a href="${mat.url}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-900">Open Link</a>`;
                } else if (mat.url) {
                    // ============= CHANGE IS HERE: "View" button is now a "Download" link =============
                    const downloadUrl = `/api/events/schedules/materials/view_material.php?id=${mat.id}`;
                    viewAction = `<a href="${downloadUrl}" download class="text-indigo-600 hover:text-indigo-900">Download</a>`;
                } else {
                    viewAction = `<span class="text-gray-400">Not available</span>`;
                }
                return `
                    <tr id="material-row-${mat.id}">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                            <div class="font-medium text-gray-900">${mat.title}</div>
                            <div class="text-gray-500">${mat.description || ''}</div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 capitalize">${mat.type}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${viewAction}</td>
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

    // --- API CALLS ---
    const initializePage = async () => {
        if (!scheduleId) {
            document.body.innerHTML = '<p class="text-red-500 text-center p-8">Error: Schedule ID is missing from the URL.</p>';
            return;
        }
        try {
            const response = await fetch(`/api/events/schedules/read_training_details.php?id=${scheduleId}`);
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Failed to load data.');
            renderPage(data);
        } catch (error) {
            showToast(error.message, true);
        }
    };

    const fetchAllUsers = async () => {
        if (allUsers.length > 0) return;
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
        }
    };

    // --- MODAL & FORM LOGIC ---
    // ============= CHANGE IS HERE: File input is now filtered and help text is updated =============
    const updateMaterialInput = (type = 'link') => {
        if (type === 'link') {
            materialUrlOrFileContainer.innerHTML = `
                <label for="url" class="block text-sm font-medium text-gray-700">URL</label>
                <input type="text" name="url" id="url" placeholder="https://..." required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">`;
        } else {
            const fileTypeAccepts = {
                'video': 'video/mp4,video/webm,video/mov',
                'pdf': 'application/pdf,.pdf',
                'ppt': '.ppt,.pptx',
                'audio': 'audio/mpeg,audio/wav'
            };
            const acceptAttr = fileTypeAccepts[type] || ''; // Get the accept string for the file type

            materialUrlOrFileContainer.innerHTML = `
                <label for="material_file" class="block text-sm font-medium text-gray-700">File</label>
                <input type="file" name="material_file" id="material_file" accept="${acceptAttr}" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <input type="hidden" name="url">
                <p class="mt-1 text-xs text-gray-500">To keep the current file, leave this blank. Size: less than 50MB.</p>`;
        }
    };

    const openModalForCreateMaterial = () => {
        materialForm.reset();
        materialForm.querySelector('input[name="schedule_id"]').value = scheduleId;
        materialForm.querySelector('input[name="id"]').value = '';
        materialModalTitle.textContent = 'Add Training Material';
        updateMaterialInput('link');
        materialForm.querySelector('select[name="type"]').value = 'link';
        materialModal.classList.remove('hidden');
    };

    const openModalForEditMaterial = (materialId) => {
        const material = currentData.materials.find(m => m.id == materialId);
        if (!material) return;
        materialForm.reset();
        materialForm.querySelector('input[name="id"]').value = material.id;
        materialForm.querySelector('input[name="schedule_id"]').value = scheduleId;
        materialForm.querySelector('input[name="title"]').value = material.title;
        materialTypeSelect.value = material.type;
        materialForm.querySelector('textarea[name="description"]').value = material.description || '';
        updateMaterialInput(material.type);
        if (material.type === 'link') {
            materialForm.querySelector('input[name="url"]').value = material.url || '';
        } else {
            const container = materialForm.querySelector('#materialUrlOrFileContainer');
            if (material.url) {
                // For editing, show a link to the current file (which will now download it)
                const downloadUrl = `/api/events/schedules/materials/view_material.php?id=${material.id}`;
                const currentFileHtml = `<p class="mt-2 text-sm text-gray-600">Current file: <a href="${downloadUrl}" download class="text-indigo-600">${material.url.split('/').pop()}</a></p>`;
                container.insertAdjacentHTML('beforeend', currentFileHtml);
            }
            container.querySelector('input[name="url"]').value = material.url || '';
        }
        materialModalTitle.textContent = 'Edit Training Material';
        materialModal.classList.remove('hidden');
    };

    const deleteMaterial = async (materialId) => {
        if (!confirm('Are you sure you want to delete this material? This will also delete the uploaded file.')) return;
        try {
            const response = await fetch('/api/events/schedules/materials/delete.php', {
                method: 'POST',
                body: JSON.stringify({ id: materialId }),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            showToast(result.message, !result.success);
            if (result.success) initializePage();
        } catch (error) {
            showToast('An error occurred while deleting.', true);
        }
    };

    // The viewer modal is no longer used for files, but we'll leave the code in case it's needed for other features later.
    const openViewerModal = (title, contentHTML) => {
        viewerModalTitle.textContent = title;
        viewerModalContent.innerHTML = contentHTML;
        viewerModal.classList.remove('hidden');
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
        const formData = new FormData(e.target);
        try {
            const response = await fetch('/api/events/schedules/materials/save_material.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            showToast(result.message, !result.success);
            if (result.success) {
                materialModal.classList.add('hidden');
                initializePage();
            }
        } catch (error) {
            showToast('An error occurred during submission.', true);
            console.error('Submission Error:', error);
        }
    });

    document.body.addEventListener('click', (e) => {
        // Since we changed the 'View' button to a download link, the old logic for opening a viewer modal for files is no longer triggered.

        if (e.target.matches('.edit-material-btn')) openModalForEditMaterial(e.target.dataset.id);
        if (e.target.matches('.delete-material-btn')) deleteMaterial(e.target.dataset.id);

        if (e.target.matches('.remove-facilitator-btn')) {
            const userId = parseInt(e.target.dataset.id);
            currentData.facilitators = currentData.facilitators.filter(f => f.id != userId);
            openFacilitatorsModal();
        }
        if (e.target.matches('.close-modal')) {
            const modal = e.target.closest('.fixed.inset-0');
            if (modal) {
                modal.classList.add('hidden');
                if (modal.id === 'viewerModal') {
                    viewerModalContent.innerHTML = '';
                }
            }
        }
    });

    viewerModal.addEventListener('click', (e) => {
        if (e.target === viewerModal) {
            viewerModal.classList.add('hidden');
            viewerModalContent.innerHTML = ''; 
        }
    });

    editDetailsBtn.addEventListener('click', openDetailsModal);
    
    scheduleDetailsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        data.id = scheduleId; // Ensure ID is part of the data
        try {
            const response = await fetch('/api/events/schedules/update_schedule_details.php', {
                method: 'POST',
                body: JSON.stringify(data),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            showToast(result.message, !result.success);
            if (result.success) {
                scheduleDetailsModal.classList.add('hidden');
                initializePage();
            }
        } catch (error) {
            showToast('Error saving schedule details.', true);
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
        }
    });

    // --- INITIALIZE ---
    initializePage();
});