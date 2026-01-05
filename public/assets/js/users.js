document.addEventListener('DOMContentLoaded', function() {
    // --- STATE MANAGEMENT ---
    let currentPage = 1;
    let currentFilters = { search: '', status: '', role: '', employed: '', company_id: '' };
    let userIdToDelete = null; 
    let companiesList = []; 
    let rolesList = [];
    let selectedUserIds = new Set();

    // --- DOM ELEMENTS ---
    const userTableContainer = document.getElementById('userTableContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const roleFilter = document.getElementById('roleFilter');
    const employedFilter = document.getElementById('employedFilter');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    // Bulk Invoice Elements
    const bulkInvoiceBtn = document.getElementById('bulkInvoice');
    const bulkInvoiceModal = document.getElementById('bulkInvoiceModal');
    const selectedCountSpan = document.getElementById('selectedCount');
    const confirmSendInvoiceBtn = document.getElementById('confirmSendInvoiceBtn');
    const cancelInvoiceBtn = document.getElementById('cancelInvoiceBtn');
    const invoiceSubjectInput = document.getElementById('invoiceSubject');

    // Single User Modal
    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const modalTitle = document.getElementById('modalTitle');
    const userIdInput = document.getElementById('userId'); 
    const isEmployedCheckbox = document.getElementById('isEmployed');
    const employmentDetails = document.getElementById('employmentDetails');
    const baseRoleSelect = document.getElementById('role');
    const adminRolesSection = document.getElementById('adminRolesSection');
    const closeUserModalBtn = document.getElementById('closeUserModalBtn');
    const companySelect = document.getElementById('companyId'); 
    const rolesCheckboxesContainer = document.getElementById('rolesCheckboxes');

    // Delete Modal Elements
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    // Bulk User Modal Elements
    const bulkUserModal = document.getElementById('bulkUserModal');
    const bulkUserForm = document.getElementById('bulkUserForm');
    const bulkAddUserBtn = document.getElementById('bulkAddUserBtn');
    const closeBulkUserModalBtn = document.getElementById('closeBulkUserModalBtn');
    const bulkEmploymentRadios = document.querySelectorAll('input[name="employment_status"]');
    const bulkCompanySection = document.getElementById('bulkCompanySection');
    const bulkCompanySelect = document.getElementById('bulkCompanyId');
    const bulkSubmitBtn = document.getElementById('bulkSubmitBtn');
    const bulkSubmitText = document.getElementById('bulkSubmitText');
    const bulkSubmitSpinner = document.getElementById('bulkSubmitSpinner');
    const bulkResultsDiv = document.getElementById('bulkResults');
    const bulkSuccessCount = document.getElementById('bulkSuccessCount');
    const bulkErrorList = document.getElementById('bulkErrorList');

    // --- HELPER FUNCTIONS ---
    const showToast = (message, isError = false, duration = 3000) => {
        toastMessage.innerHTML = message;
        toast.className = `fixed top-5 right-5 text-white py-2 px-4 rounded-lg shadow-md max-w-sm z-50 ${isError ? 'bg-red-600' : 'bg-green-600'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), duration);
    };

    window.copyToClipboard = (text) => {
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => showToast('Copied to clipboard!')).catch(err => console.error('Failed to copy!', err));
    };

    // Improved populate function (safer/faster than innerHTML +=)
    const populateCompanyDropdown = (selectElement) => {
        if (!companiesList || companiesList.length === 0) {
            selectElement.innerHTML = '<option value="">No Companies Found</option>';
            return;
        }
        const options = companiesList.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        selectElement.innerHTML = '<option value="">Select Company...</option>' + options;
    };

    const attachCheckboxListeners = () => {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const rowCheckboxes = document.querySelectorAll('.user-checkbox');

        if(selectAllCheckbox) {
            const allVisibleSelected = Array.from(rowCheckboxes).every(cb => selectedUserIds.has(cb.value));
            selectAllCheckbox.checked = rowCheckboxes.length > 0 && allVisibleSelected;

            selectAllCheckbox.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                rowCheckboxes.forEach(cb => {
                    cb.checked = isChecked;
                    if (isChecked) selectedUserIds.add(cb.value);
                    else selectedUserIds.delete(cb.value);
                });
            });
        }

        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', (e) => {
                if (e.target.checked) selectedUserIds.add(e.target.value);
                else selectedUserIds.delete(e.target.value);
                
                if(selectAllCheckbox) {
                    const allChecked = Array.from(rowCheckboxes).every(box => box.checked);
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });
    };

    // --- API & RENDERING ---
    const loadUsers = async () => {
        const activeFilters = Object.fromEntries(Object.entries(currentFilters).filter(([_, v]) => v !== '' && v !== null && v !== undefined));
        const params = new URLSearchParams({ page: currentPage, limit: 10, ...activeFilters }).toString();

        userTableContainer.innerHTML = `<div class="text-center py-10 text-gray-500">Loading users...</div>`;
        try {
            const response = await fetch(`/api/users/read.php?${params}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            renderTable(data.users);
            renderPagination(data.total_records, 10, data.users ? data.users.length : 0);
        } catch (error) {
            console.error("Fetch error:", error);
            userTableContainer.innerHTML = `<div class="text-center py-10 text-red-500">Failed to load users. ${error.message}</div>`;
            paginationContainer.innerHTML = '';
        }
    };
    
    const renderTable = (users) => {
        let tableHtml = `<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>
            <th scope="col" class="px-6 py-3 text-left">
                <input type="checkbox" id="selectAllCheckbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role(s)</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employment</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
            </tr></thead><tbody class="bg-white divide-y divide-gray-200">`;

        if (!users || users.length === 0) {
            tableHtml += `<tr><td colspan="8" class="px-6 py-10 whitespace-nowrap text-sm text-center text-gray-500">No users found matching your criteria.</td></tr>`;
        } else {
            users.forEach(user => {
                const lastLogin = user.last_login ? new Date(user.last_login).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Never';
                const statusBadge = user.is_active == 1
                    ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Active</span>'
                    : '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Inactive</span>';

                const isChecked = selectedUserIds.has(String(user.id)) ? 'checked' : '';

                const userJson = JSON.stringify(user).replace(/"/g, '&quot;');

                tableHtml += `<tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="user-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="${user.id}" ${isChecked}>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${user.full_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="cursor-pointer hover:text-indigo-600" onclick="copyToClipboard('${user.email}')">${user.email}</div>
                        <div class="text-xs text-gray-400">${user.phone || ''}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${statusBadge}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="font-semibold capitalize">${user.role}</div>
                        <div class="text-xs text-gray-400">${user.admin_roles || ''}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.is_employed == 1 ? `${user.company_name || 'N/A'}` : 'Unemployed'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${lastLogin}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                         ${hasViewPermission ? `<a href="users/view?id=${user.id}" class="text-indigo-600 hover:text-indigo-900">View</a>` : ''}
                         ${hasUpdatePermission ? `<button type="button" class="edit-btn text-blue-600 hover:text-blue-900" data-user="${userJson}">Edit</button>` : ''}
                         ${hasDeletePermission ? `<button type="button" class="delete-btn text-red-600 hover:text-red-900" data-id="${user.id}">Delete</button>` : ''}
                    </td>
                </tr>`;
            });
        }
        tableHtml += `</tbody></table>`;
        userTableContainer.innerHTML = tableHtml;
        attachCheckboxListeners();
    };

    const renderPagination = (totalRecords, limit, currentCount) => {
        const totalPages = Math.ceil(totalRecords / limit);
        paginationContainer.innerHTML = '';
        if (totalPages <= 1) {
             if (totalRecords > 0) {
                 paginationContainer.innerHTML = `<div><p class="text-sm text-gray-700">Showing <span class="font-medium">1</span> to <span class="font-medium">${currentCount}</span> of <span class="font-medium">${totalRecords}</span> results</p></div>`;
             }
             return;
        }

        const startRecord = (currentPage - 1) * limit + 1;
        const endRecord = startRecord + (currentCount -1) ;

        let paginationHtml = `
            <div class="flex-1 flex justify-between sm:hidden">
                <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <button ${currentPage >= totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div><p class="text-sm text-gray-700">Showing <span class="font-medium">${startRecord}</span> to <span class="font-medium">${endRecord}</span> of <span class="font-medium">${totalRecords}</span> results</p></div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </button>`;

        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        if (currentPage <= 3) { endPage = Math.min(totalPages, 5); }
        if (currentPage >= totalPages - 2) { startPage = Math.max(1, totalPages - 4); }

        if (startPage > 1) {
            paginationHtml += `<button data-page="1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</button>`;
            if (startPage > 2) paginationHtml += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            const isCurrent = i === currentPage;
            paginationHtml += `<button data-page="${i}" class="relative inline-flex items-center px-4 py-2 border ${isCurrent ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'}" ${isCurrent ? 'aria-current="page"' : ''}>${i}</button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) paginationHtml += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>`;
            paginationHtml += `<button data-page="${totalPages}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">${totalPages}</button>`;
        }

        paginationHtml += `<button ${currentPage >= totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </button>
                    </nav>
                </div>
            </div>`;
        paginationContainer.innerHTML = paginationHtml;
    };

    // --- FORM SUBMISSION ---
    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = userForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';

        const formData = new FormData(userForm);
        const data = Object.fromEntries(formData.entries());
        data.admin_roles = formData.getAll('admin_roles[]') || [];
        data.is_employed = isEmployedCheckbox.checked ? 1 : 0;
        
        if (data.is_employed === 0) {
            data.company_id = null;
            data.position = '';
        }
        delete data.is_employed_checkbox;
        if (data.company_id === '') data.company_id = null;

        const url = data.id ? '/api/users/update.php' : '/api/users/create.php';
        
        // Remove password if empty during update
        if (data.id && data.password === '') delete data.password;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                userModal.classList.add('hidden');
                showToast(result.message || (data.id ? 'User updated successfully.' : 'User created successfully.'));
                loadUsers();
            } else {
                showToast(result.message || 'An error occurred.', true);
            }
        } catch (error) {
            console.error('Submit error:', error);
            showToast(`A network error occurred: ${error.message}`, true);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Save User';
        }
    });

    bulkUserForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        bulkSubmitBtn.disabled = true;
        bulkSubmitText.textContent = 'Processing...';
        bulkSubmitSpinner.classList.remove('hidden');
        bulkResultsDiv.classList.add('hidden');
        bulkSuccessCount.textContent = '';
        bulkErrorList.innerHTML = '';

        const formData = new FormData(bulkUserForm);
        if (formData.get('employment_status') === 'unemployed') {
            formData.delete('company_id');
        } else if (!formData.get('company_id')) {
             showToast('Please select a company for employed users.', true);
             bulkSubmitBtn.disabled = false;
             bulkSubmitText.textContent = 'Upload & Create Users';
             bulkSubmitSpinner.classList.add('hidden');
             return;
        }

        try {
            const response = await fetch('/api/users/bulk_create.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
             
             bulkResultsDiv.classList.remove('hidden');
             if (result.success_count > 0) {
                 bulkSuccessCount.textContent = `${result.success_count} user(s) created successfully.`;
             } else {
                 bulkSuccessCount.textContent = 'No users were created successfully.';
             }

             if (result.errors && result.errors.length > 0) {
                 bulkErrorList.innerHTML = '<strong>Errors:</strong><ul>' +
                     result.errors.map(err => `<li>Line ${err.line}: ${err.message} (Data: ${err.data})</li>`).join('') +
                     '</ul>';
                 showToast(`${result.errors.length} error(s) occurred. See results below.`, true, 5000);
             } else if (result.success_count > 0) {
                 showToast(`Bulk creation complete. ${result.success_count} user(s) added.`, false);
             }

            if (result.success || result.success_count > 0) {
                loadUsers();
            }
        } catch (error) {
            console.error('Bulk submit error:', error);
            showToast(`A network error occurred: ${error.message}`, true);
            bulkResultsDiv.classList.remove('hidden');
            bulkErrorList.innerHTML = '<strong>Error:</strong> Network request failed.';
        } finally {
            bulkSubmitBtn.disabled = false;
            bulkSubmitText.textContent = 'Upload & Create Users';
            bulkSubmitSpinner.classList.add('hidden');
        }
    });

    // --- EVENT LISTENERS ---
    const setupEventListeners = () => {
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                currentFilters.search = searchInput.value.trim();
                loadUsers();
            }, 300);
        });
        
        // Filter changes
        [statusFilter, roleFilter, employedFilter].forEach(el => {
            el.addEventListener('change', () => {
                currentPage = 1;
                currentFilters.status = statusFilter.value;
                currentFilters.role = roleFilter.value;
                currentFilters.employed = employedFilter.value;
                loadUsers();
            });
        });

        // Pagination
        paginationContainer.addEventListener('click', (e) => {
            const pageButton = e.target.closest('button[data-page]');
            if (pageButton && !pageButton.disabled) {
                currentPage = parseInt(pageButton.dataset.page);
                loadUsers();
            }
        });

        // --- Table Actions (Edit & Delete) ---
        userTableContainer.addEventListener('click', (e) => {
            // Delete Action
            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn && hasDeletePermission) {
                userIdToDelete = deleteBtn.dataset.id;
                deleteConfirmModal.classList.remove('hidden');
            }

            // Edit Action
            const editBtn = e.target.closest('.edit-btn');
            if (editBtn && hasUpdatePermission) {
                const user = JSON.parse(editBtn.dataset.user);
                
                // Populate Form
                modalTitle.textContent = 'Edit User';
                userIdInput.value = user.id;
                document.getElementById('fullName').value = user.full_name;
                document.getElementById('email').value = user.email;
                document.getElementById('phone').value = user.phone || '';
                document.getElementById('gender').value = user.gender || 'male';
                
                // Password
                const passwordInput = document.getElementById('password');
                passwordInput.removeAttribute('required');
                passwordInput.placeholder = 'Leave blank to keep unchanged';
                passwordInput.value = '';

                // Employment Logic - SYNCED LOGIC
                // We set the checkbox and manually handle the display toggle 
                // to avoid the change listener clearing the values.
                isEmployedCheckbox.checked = (user.is_employed == 1);
                
                if (user.is_employed == 1) {
                    employmentDetails.classList.remove('hidden');
                    companySelect.setAttribute('required', 'required');
                    
                    // Set company value (converted to string to ensure ID match)
                    companySelect.value = String(user.company_id || '');
                    document.getElementById('position').value = user.position || '';
                } else {
                    employmentDetails.classList.add('hidden');
                    companySelect.removeAttribute('required');
                    companySelect.value = '';
                    document.getElementById('position').value = '';
                }

                // Roles Logic
                baseRoleSelect.value = user.role;
                // Trigger change to show/hide admin checkboxes
                baseRoleSelect.dispatchEvent(new Event('change'));
                document.getElementById('isActive').value = user.is_active;

                // --- Handle Admin Roles Checkboxes ---
                const roleCheckboxes = userForm.querySelectorAll('input[name="admin_roles[]"]');
                roleCheckboxes.forEach(cb => cb.checked = false);

                if (user.admin_role_ids) {
                    const ids = Array.isArray(user.admin_role_ids) ? user.admin_role_ids : String(user.admin_role_ids).split(',');
                    ids.forEach(id => {
                        const cb = document.getElementById(`role-${id}`);
                        if(cb) cb.checked = true;
                    });
                } else if (user.admin_roles && typeof user.admin_roles === 'string') {
                    const userRoleNames = user.admin_roles.split(',').map(r => r.trim().toLowerCase());
                    rolesList.forEach(role => {
                        if (userRoleNames.includes(role.name.toLowerCase())) {
                            const cb = document.getElementById(`role-${role.id}`);
                            if(cb) cb.checked = true;
                        }
                    });
                }
                
                userModal.classList.remove('hidden');
            }
        });

        // --- Add User Button ---
        document.getElementById('addUserBtn')?.addEventListener('click', () => {
             if (!hasCreatePermission) {
                 showToast('You do not have permission to create users.', true);
                 return;
             }
            userForm.reset();
            userIdInput.value = '';
            modalTitle.textContent = 'Add New User';

            const passwordInput = document.getElementById('password');
            passwordInput.setAttribute('required', 'required');
            passwordInput.placeholder = 'Required (min 8 characters)';
            passwordInput.value = '';

            document.getElementById('gender').value = 'male';
            isEmployedCheckbox.checked = false;
            baseRoleSelect.value = 'user';
            document.getElementById('isActive').value = '1';
            
             const roleCheckboxes = userForm.querySelectorAll('input[name="admin_roles[]"]');
            roleCheckboxes.forEach(cb => cb.checked = false);

            isEmployedCheckbox.dispatchEvent(new Event('change'));
            baseRoleSelect.dispatchEvent(new Event('change'));

            userModal.classList.remove('hidden');
        });

        closeUserModalBtn.addEventListener('click', () => userModal.classList.add('hidden'));

        isEmployedCheckbox.addEventListener('change', () => {
            employmentDetails.classList.toggle('hidden', !isEmployedCheckbox.checked);
             if (isEmployedCheckbox.checked) {
                 companySelect.setAttribute('required', 'required');
             } else {
                 companySelect.removeAttribute('required');
                 companySelect.value = '';
                 document.getElementById('position').value = '';
             }
        });

        baseRoleSelect.addEventListener('change', () => {
             adminRolesSection.classList.toggle('hidden', baseRoleSelect.value !== 'admin');
        });

        // Delete Modal
        cancelDeleteBtn.addEventListener('click', () => {
            deleteConfirmModal.classList.add('hidden');
            userIdToDelete = null;
        });

        confirmDeleteBtn.addEventListener('click', async () => {
             if (!userIdToDelete || !hasDeletePermission) return;
             confirmDeleteBtn.disabled = true;
             confirmDeleteBtn.textContent = 'Deleting...';

            try {
                const response = await fetch('/api/users/delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: userIdToDelete })
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    showToast('User deleted successfully.');
                     const currentItemsOnPage = userTableContainer.querySelectorAll('tbody tr').length;
                     if (currentItemsOnPage === 1 && currentPage > 1) currentPage--;
                    loadUsers();
                } else {
                    throw new Error(result.message || 'Failed to delete user.');
                }
            } catch (error) {
                showToast(error.message, true);
            } finally {
                deleteConfirmModal.classList.add('hidden');
                userIdToDelete = null;
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = 'Confirm Delete';
            }
        });

        // Bulk Invoice Listeners
        bulkInvoiceBtn?.addEventListener('click', () => {
            if (selectedUserIds.size === 0) {
                showToast('Please select at least one user from the list.', true);
                return;
            }
            selectedCountSpan.textContent = selectedUserIds.size;
            bulkInvoiceModal.classList.remove('hidden');
        });

        cancelInvoiceBtn.addEventListener('click', () => {
            bulkInvoiceModal.classList.add('hidden');
        });

        confirmSendInvoiceBtn.addEventListener('click', async () => {
            confirmSendInvoiceBtn.disabled = true;
            confirmSendInvoiceBtn.textContent = 'Sending...';

            const payload = {
                user_ids: Array.from(selectedUserIds),
                subject: invoiceSubjectInput.value
            };

            try {
                const response = await fetch('/api/invoices/send_bulk_invites.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.success) {
                    showToast(`Successfully sent invites to ${result.sent_count} users.`);
                    bulkInvoiceModal.classList.add('hidden');
                    selectedUserIds.clear();
                    loadUsers();
                } else {
                    showToast(result.message || 'Failed to send invites.', true);
                }
            } catch (error) {
                showToast('Network error occurred.', true);
                console.error(error);
            } finally {
                confirmSendInvoiceBtn.disabled = false;
                confirmSendInvoiceBtn.textContent = 'Send Invites';
            }
        });

        // Bulk Add User Listeners
        bulkAddUserBtn?.addEventListener('click', () => {
             if (!hasCreatePermission) {
                 showToast('You do not have permission to create users.', true);
                 return;
             }
            bulkUserForm.reset();
            bulkCompanySection.classList.add('hidden');
            bulkResultsDiv.classList.add('hidden');
            bulkUserModal.classList.remove('hidden');
        });

        closeBulkUserModalBtn.addEventListener('click', () => {
            bulkUserModal.classList.add('hidden');
        });

        bulkEmploymentRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                bulkCompanySection.classList.toggle('hidden', radio.value !== 'employed');
                 if (radio.value === 'employed') {
                      bulkCompanySelect.setAttribute('required', 'required');
                 } else {
                      bulkCompanySelect.removeAttribute('required');
                      bulkCompanySelect.value = '';
                 }
            });
        });
    };

    // --- INITIALIZATION ---
    const initializePage = async () => {
        // Permissions
         window.hasCreatePermission = document.getElementById('addUserBtn') !== null;
         window.hasUpdatePermission = true; 
         window.hasDeletePermission = true; 
         window.hasViewPermission = true;   

        currentFilters.company_id = new URLSearchParams(window.location.search).get('company_id') || '';

        try {
            const [compRes, roleRes] = await Promise.all([
                fetch('/api/companies/read_for_user.php'),
                fetch('/api/roles/read.php')
            ]);

            if (!compRes.ok) throw new Error(`Failed to load companies: ${compRes.statusText}`);
            if (!roleRes.ok) throw new Error(`Failed to load roles: ${roleRes.statusText}`);

            const companyResult = await compRes.json(); 
            if (!companyResult.success) throw new Error(companyResult.message || 'Company API returned an error');
            companiesList = companyResult.data;
            
            const rolesResult = await roleRes.json();
            rolesList = rolesResult; 

            populateCompanyDropdown(companySelect);
            populateCompanyDropdown(bulkCompanySelect);

            rolesCheckboxesContainer.innerHTML = '';
            rolesList.forEach(r => {
                rolesCheckboxesContainer.innerHTML += `<div class="flex items-center">
                    <input id="role-${r.id}" name="admin_roles[]" value="${r.id}" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                    <label for="role-${r.id}" class="ml-2 text-sm text-gray-700">${r.name}</label>
                </div>`;
            });

            setupEventListeners();
            loadUsers();
        } catch (error) {
            console.error("Initialization failed:", error);
            showToast(`Failed to load initial page data: ${error.message}`, true);
             userTableContainer.innerHTML = `<div class="text-center py-10 text-red-500">Failed to load initial data.</div>`;
             if(bulkAddUserBtn) bulkAddUserBtn.disabled = true;
             if(document.getElementById('addUserBtn')) document.getElementById('addUserBtn').disabled = true;
        }
    };

    initializePage();
});