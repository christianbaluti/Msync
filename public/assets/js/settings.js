// /assets/js/settings.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. STATE MANAGEMENT ---
    const appState = {
        activeTab: 'general',
        allPermissions: [], // Cache for all permissions
        gateways: {} // To hold gateway configs
    };
    
    const API = {
        general: {
            read: '/api/settings/read_general.php',
            update: '/api/settings/update_general.php'
        },
        gateways: {
            read: '/api/settings/read_gateways.php',
            update: '/api/settings/update_gateway.php'
        },
        roles: {
            read: '/api/settings/read_roles.php',
            read_permissions: '/api/settings/read_permissions.php',
            read_role_permissions: '/api/settings/read_role_permissions.php',
            update_role_permissions: '/api/settings/update_role_permissions.php'
        },
        updates: {
            read: '/api/settings/read_app_updates.php',
            create: '/api/settings/create_app_update.php',
            update: '/api/settings/update_app_update.php',
            delete: '/api/settings/delete_app_update.php'
        }
    };

    // --- 2. DOM ELEMENTS ---
    // Tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    // General
    const generalSettingsForm = document.getElementById('generalSettingsForm');

    // Gateways
    const gatewaySettingsForm = document.getElementById('gatewaySettingsForm');
    const gatewayFormContainer = document.getElementById('gateway-form-container');
    
    // Roles
    const rolesListContainer = document.getElementById('roles-list-container');
    const roleModal = document.getElementById('roleModal');
    const roleForm = document.getElementById('roleForm');
    const roleModalTitle = document.getElementById('roleModalTitle');
    const permissionsContainer = document.getElementById('permissions-container');

    // App Updates
    const addAppUpdateBtn = document.getElementById('addAppUpdateBtn');
    const appUpdatesTableContainer = document.getElementById('appUpdatesTableContainer');
    const appUpdateModal = document.getElementById('appUpdateModal');
    const appUpdateForm = document.getElementById('appUpdateForm');
    const appUpdateModalTitle = document.getElementById('appUpdateModalTitle');

    // --- 3. UI ELEMENTS ---
    const spinner = document.getElementById('loading-spinner');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    // --- 4. HELPER FUNCTIONS ---
    const showSpinner = () => spinner.classList.remove('hidden');
    const hideSpinner = () => spinner.classList.add('hidden');

    const showToast = (message, isError = false) => {
        toastMessage.textContent = message;
        toast.className = `fixed bottom-5 right-5 text-white py-2 px-4 rounded-lg shadow-md z-50 ${isError ? 'bg-red-600' : 'bg-green-700'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    };

    // Generic API call function
    const apiCall = async (url, options = {}, showSpinnerFlag = true) => {
        if (showSpinnerFlag) showSpinner();
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                let errorMsg = `HTTP error! status: ${response.status}`;
                try { const errData = await response.json(); errorMsg = errData.message || errorMsg; } catch (e) {}
                throw new Error(errorMsg);
            }
            const result = await response.json();
            if (result.success === false) throw new Error(result.message);
            return result;
        } catch (error) {
            console.error('API Call Error:', error);
            showToast(error.message || 'A network error occurred.', true);
            return { success: false, message: error.message };
        } finally {
            if (showSpinnerFlag) hideSpinner();
        }
    };
    
    // --- 5. TAB SWITCHING LOGIC ---
    const switchTab = (targetTab) => {
        appState.activeTab = targetTab;

        tabButtons.forEach(btn => {
            const isTarget = `tab-btn-${targetTab}` === btn.id;
            btn.classList.toggle('border-indigo-500', isTarget);
            btn.classList.toggle('text-indigo-600', isTarget);
            btn.classList.toggle('border-transparent', !isTarget);
            btn.classList.toggle('text-gray-500', !isTarget);
            btn.setAttribute('aria-current', isTarget ? 'page' : 'false');
        });
        
        tabPanels.forEach(panel => {
            panel.classList.toggle('hidden', `tab-panel-${targetTab}` !== panel.id);
        });
        
        // Load data for the activated tab
        if (targetTab === 'general') loadGeneralSettings();
        else if (targetTab === 'gateways') loadPaymentGateways();
        else if (targetTab === 'roles') loadRolesAndPermissions();
        else if (targetTab === 'updates') loadAppUpdates();
    };

    // --- 6. GENERAL SETTINGS LOGIC ---
    const loadGeneralSettings = async () => {
        const result = await apiCall(API.general.read);
        if (result.success) {
            result.data.forEach(setting => {
                const input = generalSettingsForm.querySelector(`[name="${setting.setting_key}"]`);
                if (input) {
                    input.value = setting.setting_value;
                }
            });
        }
    };
    
    const handleSaveGeneralSettings = async (e) => {
        e.preventDefault();
        const formData = new FormData(generalSettingsForm);
        const data = Object.fromEntries(formData.entries());
        
        const result = await apiCall(API.general.update, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (result.success) {
            showToast('General settings saved successfully.');
            loadGeneralSettings(); // Reload
        }
    };

    // --- 7. PAYMENT GATEWAYS LOGIC ---
    const loadPaymentGateways = async () => {
        const result = await apiCall(API.gateways.read);
        if (result.success) {
            gatewayFormContainer.innerHTML = ''; // Clear
            appState.gateways = {}; // Reset cache
            result.data.forEach(gateway => {
                appState.gateways[gateway.id] = gateway; // Cache it
                let config = {};
                try {
                    config = JSON.parse(gateway.config);
                } catch (e) {
                    console.error('Failed to parse gateway config JSON', e);
                }
                
                // Build form for this gateway
                let fieldsHtml = '';
                for (const key in config) {
                    fieldsHtml += `
                        <div class="sm:col-span-1">
                            <label for="gateway_${gateway.id}_${key}" class="block text-sm font-medium text-slate-700">${key.replace(/_/g, ' ')}</label>
                            <input type="text" id="gateway_${gateway.id}_${key}" name="${key}" value="${config[key] || ''}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                        </div>
                    `;
                }
                
                gatewayFormContainer.innerHTML += `
                    <div class="space-y-4 pt-6 border-t border-gray-200 first:border-t-0 first:pt-0">
                        <h4 class="text-base font-medium text-gray-900">${gateway.name}</h4>
                        <input type="hidden" name="gateway_id" value="${gateway.id}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${fieldsHtml}
                        </div>
                    </div>
                `;
            });
        }
    };
    
    const handleSavePaymentGateways = async (e) => {
        e.preventDefault();
        // This form is tricky as it's one <form> with multiple gateways
        // We need to group the fields by gateway
        
        const allInputs = gatewaySettingsForm.querySelectorAll('input[type="text"], input[type="hidden"]');
        const gatewayPayloads = {}; // { "1": { config: {...} }, "2": { config: {...} } }

        let currentGatewayId = null;
        allInputs.forEach(input => {
            if (input.name === 'gateway_id') {
                currentGatewayId = input.value;
                if (!gatewayPayloads[currentGatewayId]) {
                    // Initialize payload from the cached full config
                    gatewayPayloads[currentGatewayId] = {
                        id: currentGatewayId,
                        config: JSON.parse(appState.gateways[currentGatewayId].config) 
                    };
                }
            } else if (currentGatewayId) {
                // This is a config key for the current gateway
                gatewayPayloads[currentGatewayId].config[input.name] = input.value;
            }
        });

        // Now, send one API request per gateway
        showSpinner();
        let allSuccess = true;
        for (const id in gatewayPayloads) {
            const payload = gatewayPayloads[id];
            // We only send the 'config' object, stringified
            const result = await apiCall(API.gateways.update, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: payload.id,
                    config: JSON.stringify(payload.config) // Re-stringify the modified config
                })
            }, false); // Don't hide spinner yet
            
            if (!result.success) allSuccess = false;
        }
        hideSpinner();
        
        if (allSuccess) {
            showToast('Payment gateway settings saved.');
            loadPaymentGateways(); // Reload
        } else {
            showToast('One or more gateways failed to save.', true);
        }
    };

    // --- 8. ROLES & PERMISSIONS LOGIC ---
    const loadRolesAndPermissions = async () => {
        showSpinner();
        rolesListContainer.innerHTML = '<div class="p-6 text-center text-sm text-gray-500">Loading roles...</div>';
        
        // Fetch all permissions first and cache them
        if (appState.allPermissions.length === 0) {
            const permsResult = await apiCall(API.roles.read_permissions, {}, false);
            if (permsResult.success) {
                appState.allPermissions = permsResult.data;
            } else {
                rolesListContainer.innerHTML = '<div class="p-6 text-center text-red-500">Failed to load permissions.</div>';
                hideSpinner();
                return;
            }
        }
        
        // Fetch all roles
        const rolesResult = await apiCall(API.roles.read, {}, false);
        if (rolesResult.success) {
            renderRolesList(rolesResult.data);
        } else {
            rolesListContainer.innerHTML = '<div class="p-6 text-center text-red-500">Failed to load roles.</div>';
        }
        hideSpinner();
    };

    const renderRolesList = (roles) => {
        rolesListContainer.innerHTML = '';
        if (roles.length === 0) {
            rolesListContainer.innerHTML = '<div class="p-6 text-center text-sm text-gray-500">No roles found.</div>';
            return;
        }
        
        const list = document.createElement('ul');
        list.className = 'divide-y divide-gray-200';
        roles.forEach(role => {
            list.innerHTML += `
                <li class="flex items-center justify-between p-4 sm:p-6">
                    <div>
                        <p class="text-sm font-medium text-indigo-600">${role.name}</p>
                        <p class="text-sm text-gray-500">${role.description || 'No description'}</p>
                    </div>
                    <button type="button" class="action-edit-role rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50" data-id="${role.id}" data-name="${role.name}">
                        Manage
                    </button>
                </li>
            `;
        });
        rolesListContainer.appendChild(list);
    };

    const openRoleModal = async (roleId, roleName) => {
        roleModalTitle.textContent = `Manage Permissions for: ${roleName}`;
        roleForm.querySelector('#roleId').value = roleId;
        permissionsContainer.innerHTML = '<div class="h-64 bg-gray-200 animate-pulse rounded-md"></div>';
        roleModal.classList.remove('hidden');
        
        // Get the permissions for this specific role
        const result = await apiCall(`${API.roles.read_role_permissions}?role_id=${roleId}`);
        if (!result.success) {
            roleModal.classList.add('hidden');
            return;
        }
        
        const assignedPerms = result.data.map(p => p.permission_id);
        
        // Group permissions by category (e.g., 'users_create' -> 'users')
        const permGroups = {};
        appState.allPermissions.forEach(perm => {
            const groupName = perm.name.split('_')[0];
            if (!permGroups[groupName]) {
                permGroups[groupName] = [];
            }
            permGroups[groupName].push(perm);
        });
        
        // Render the checkboxes
        let html = '';
        for (const group in permGroups) {
            html += `<div class="space-y-3">
                        <h4 class="text-sm font-medium text-gray-900 capitalize border-b pb-2">${group}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">`;
            
            permGroups[group].forEach(perm => {
                const isChecked = assignedPerms.includes(perm.id);
                html += `
                    <div class="relative flex items-start">
                        <div class="flex h-6 items-center">
                            <input id="perm-${perm.id}" name="permissions[]" value="${perm.id}" type="checkbox" ${isChecked ? 'checked' : ''} class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                        </div>
                        <div class="ml-3 text-sm leading-6">
                            <label for="perm-${perm.id}" class="font-medium text-gray-700">${perm.name}</label>
                        </div>
                    </div>
                `;
            });
            
            html += `</div></div>`;
        }
        permissionsContainer.innerHTML = html;
    };
    
    const handleSaveRolePermissions = async (e) => {
        e.preventDefault();
        const formData = new FormData(roleForm);
        const data = {
            role_id: formData.get('role_id'),
            permissions: formData.getAll('permissions[]') // Gets an array of checked IDs
        };

        const result = await apiCall(API.roles.update_role_permissions, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (result.success) {
            showToast('Role permissions updated successfully.');
            roleModal.classList.add('hidden');
        }
    };

    // --- 9. APP UPDATES LOGIC ---
    const loadAppUpdates = async () => {
        const result = await apiCall(API.updates.read);
        if (result.success) {
            renderAppUpdatesTable(result.data);
        } else {
            appUpdatesTableContainer.innerHTML = `<p class="p-8 text-center text-red-500">Could not load app updates.</p>`;
        }
    };
    
    const renderAppUpdatesTable = (updates) => {
        let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:pl-0">Version</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">`;

        if (!updates || updates.length === 0) {
            tableHtml += `<tr><td colspan="5" class="px-3 py-12 text-center text-sm text-gray-500">No app update versions found.</td></tr>`;
        } else {
            updates.forEach(upd => {
                const typeBadge = upd.is_force_update == 1
                    ? '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Forced</span>'
                    : '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Optional</span>';

                tableHtml += `
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                            <div class="font-medium text-gray-900">${upd.version_name}</div>
                            <div class="text-gray-500">Code: ${upd.version_code}</div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 capitalize">${upd.platform}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">${typeBadge}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${new Date(upd.created_at.replace(' ', 'T')).toLocaleDateString()}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <button class="text-indigo-600 hover:text-indigo-900 action-edit-update" data-id="${upd.id}">Edit</button>
                            <button class="text-red-600 hover:text-red-900 ml-4 action-delete-update" data-id="${upd.id}">Delete</button>
                        </td>
                    </tr>`;
            });
        }
        tableHtml += `</tbody></table>`;
        appUpdatesTableContainer.innerHTML = tableHtml;
    };
    
    const openAppUpdateModal = (update = null) => {
        appUpdateForm.reset();
        if (update) {
            appUpdateModalTitle.textContent = 'Edit App Version';
            appUpdateForm.querySelector('[name="id"]').value = update.id;
            appUpdateForm.querySelector('[name="platform"]').value = update.platform;
            appUpdateForm.querySelector('[name="is_force_update"]').value = update.is_force_update;
            appUpdateForm.querySelector('[name="version_name"]').value = update.version_name;
            appUpdateForm.querySelector('[name="version_code"]').value = update.version_code;
            appUpdateForm.querySelector('[name="release_notes"]').value = update.release_notes;
        } else {
            appUpdateModalTitle.textContent = 'Add New App Version';
            appUpdateForm.querySelector('[name="id"]').value = '';
        }
        appUpdateModal.classList.remove('hidden');
    };
    
    const handleSaveAppUpdate = async (e) => {
        e.preventDefault();
        const formData = new FormData(appUpdateForm);
        const data = Object.fromEntries(formData.entries());
        const url = data.id ? API.updates.update : API.updates.create;

        const result = await apiCall(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (result.success) {
            showToast(result.message);
            appUpdateModal.classList.add('hidden');
            loadAppUpdates();
        }
    };

    // --- 10. EVENT LISTENERS ---
    const setupEventListeners = () => {
        // Tab
        tabButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const targetTab = e.currentTarget.id.replace('tab-btn-', '');
                switchTab(targetTab);
            });
        });
        
        // General
        generalSettingsForm.addEventListener('submit', handleSaveGeneralSettings);
        
        // Gateways
        gatewaySettingsForm.addEventListener('submit', handleSavePaymentGateways);
        
        // Roles
        rolesListContainer.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.action-edit-role');
            if (editBtn) {
                openRoleModal(editBtn.dataset.id, editBtn.dataset.name);
            }
        });
        roleForm.addEventListener('submit', handleSaveRolePermissions);
        
        // App Updates
        addAppUpdateBtn.addEventListener('click', () => openAppUpdateModal(null));
        appUpdateForm.addEventListener('submit', handleSaveAppUpdate);
        appUpdatesTableContainer.addEventListener('click', async (e) => {
            const target = e.target;
            const id = target.dataset.id;
            if (!id) return;
            
            if (target.classList.contains('action-edit-update')) {
                const result = await apiCall(`${API.updates.read}?id=${id}`);
                if (result.success && result.data.length > 0) {
                    openAppUpdateModal(result.data[0]);
                }
            } else if (target.classList.contains('action-delete-update')) {
                if (confirm('Are you sure you want to delete this app version?')) {
                    const result = await apiCall(API.updates.delete, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ id })
                    });
                    if (result.success) {
                        showToast('App update deleted.');
                        loadAppUpdates();
                    }
                }
            }
        });
        
        // --- Modal Close Listeners ---
        document.querySelectorAll('.close-modal-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.fixed[id*="Modal"]').classList.add('hidden');
            });
        });
        document.querySelectorAll('.fixed[id*="Modal"]').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.add('hidden');
            });
        });
    };

    // --- 11. INITIALIZATION ---
    setupEventListeners();
    switchTab('general'); // Load the first tab
});