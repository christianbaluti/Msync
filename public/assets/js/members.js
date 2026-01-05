document.addEventListener('DOMContentLoaded', () => {
    // --- 1. STATE & CONFIG ---
    const appState = {
        members: [],
        membershipTypes: [],
        companies: [],
        paymentMethods: [],
        filters: {
            membership_card_number: '',
            full_name: '',
            company_name: '',
            status: ''
        },
        pagination: {
            currentPage: 1,
            limit: 10,
            totalRecords: 0,
            totalPages: 1
        },
        // State to hold the function that should run on delete confirmation
        pendingDeleteAction: null
    };

    const API = {
        readMembers: '/api/members/read_for_members.php',
        searchUsers: '/api/users/search.php',
        readTypes: '/api/memberships/get_types.php',
        createType: '/api/memberships/create_type.php',
        updateType: '/api/memberships/update_type.php',
        deleteType: '/api/memberships/delete_type.php',
        createSingle: '/api/members/create_single.php',
        deleteSubscription: '/api/members/delete.php',
        readCompanies: '/api/companies/read_all.php',
        uploadCsv: '/api/members/upload_csv_2.php',
        getPaymentMethods: '/api/payments/get_methods.php',
        getUsersByCompany: '/api/users/read_by_company.php',
        createBulkSubscriptions: '/api/members/create_bulk.php',
    };

    // --- 2. DOM ELEMENT SELECTORS ---
    const membersTableBody = document.getElementById('membersTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const filtersForm = document.getElementById('filtersForm');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const manageTypesBtn = document.getElementById('manageTypesBtn');
    const addSingleMemberBtn = document.getElementById('addSingleMemberBtn');
    const addMultipleMembersBtn = document.getElementById('addMultipleMembersBtn');
    const membershipTypesModal = document.getElementById('membershipTypesModal');
    const membershipTypeForm = document.getElementById('membershipTypeForm');
    const membershipTypesList = document.getElementById('membershipTypesList');
    const typeFormTitle = document.getElementById('typeFormTitle');
    const cancelEditTypeBtn = document.getElementById('cancelEditTypeBtn');
    const addSingleMemberModal = document.getElementById('addSingleMemberModal');
    const addSingleMemberForm = document.getElementById('addSingleMemberForm');
    const addMemberModalTitle = document.getElementById('addMemberModalTitle');
    const addMultipleMembersModal = document.getElementById('addMultipleMembersModal');
    const userTypeRadios = document.querySelectorAll('input[name="user_type"]');
    const existingUserSection = document.getElementById('existingUserSection');
    const newUserSection = document.getElementById('newUserSection');
    const userSearchInput = document.getElementById('user-search');
    const userSearchResults = document.getElementById('user-search-results');
    const addMultipleMembersForm = document.getElementById('addMultipleMembersForm');
    const bulkUserTypeRadios = document.querySelectorAll('input[name="bulk_user_type"]');
    const bulkBackButton = document.getElementById('bulk-back-btn');
    const bulkNextButton = document.getElementById('bulk-next-btn');
    const bulkSubmitButton = document.getElementById('bulk-submit-btn');

    // New selectors for spinner and delete modal
    const loadingSpinner = document.getElementById('loading-spinner');
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const deleteModalTitle = document.getElementById('deleteModalTitle');
    const deleteModalMessage = document.getElementById('deleteModalMessage');

    let companyUsersCache = [];

    // --- 3. UTILITY & HELPER FUNCTIONS ---
    const showSpinner = () => loadingSpinner.classList.remove('hidden');
    const hideSpinner = () => loadingSpinner.classList.add('hidden');

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
                if (toastContainer.childElementCount === 0) toastContainer.classList.add('hidden');
            }, 300);
        }, 3000);
    };

    const apiCall = async (url, options = {}) => {
        showSpinner(); // Show spinner on every API call
        try {
            const response = await fetch(url, options);
            console.log('response', response);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            if (result.success === false) throw new Error(result.message);
            return result;
        } catch (error) {
            console.error('API Error for url:', url, 'with options:', options);
            console.error('API Error:', error);
            showToast(error.message || 'A network or server error occurred.', true);
            return {
                success: false,
                message: error.message
            };
        } finally {
            hideSpinner(); // Hide spinner when call is finished (success or fail)
        }
    };

    // --- 4. RENDER FUNCTIONS ---
    const renderTable = () => {
        membersTableBody.innerHTML = '';
        if (appState.members.length === 0) {
            membersTableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">No members found matching your criteria.</td></tr>`;
            return;
        }

        const statusClasses = {
            active: 'bg-green-100 text-green-700',
            pending: 'bg-yellow-100 text-yellow-800',
            expired: 'bg-red-100 text-red-700',
            suspended: 'bg-gray-100 text-gray-800'
        };

        appState.members.forEach(member => {
            const row = document.createElement('tr');
            const userAvatar = member.user_avatar || '/assets/img/placeholder.png';
            const typeName = member.type_name || 'N/A';
            const endDate = member.end_date ? new Date(member.end_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric'}) : 'N/A';

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            <img class="h-12 w-12 rounded-full object-cover" src="${userAvatar}" alt="${member.full_name}" onerror="this.onerror=null;this.src='/assets/img/placeholder.png';">
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${member.full_name}</div>
                            <div class="text-sm text-gray-500">${member.email || ''}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <div>${typeName}</div>
                    <div class="text-xs text-gray-500">${member.membership_card_number || '-'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${member.company_name || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${statusClasses[member.status] || 'bg-gray-100 text-gray-800'}">${member.status}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${endDate}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                    <a href="/users/view?id=${member.user_id}" class="text-indigo-600 hover:text-indigo-900">View</a>
                    <button type="button" class="text-red-600 hover:text-red-900 member-action-delete" data-id="${member.subscription_id}">Delete</button>
                </td>
            `;
            membersTableBody.appendChild(row);
        });
    };
    
    // ... renderPagination and renderMembershipTypes are unchanged ...
    const renderPagination = () => {
        const {
            currentPage,
            totalRecords,
            limit,
            totalPages
        } = appState.pagination;
        if (totalRecords === 0) {
            paginationContainer.classList.add('hidden');
            return;
        }
        paginationContainer.classList.remove('hidden');
        document.getElementById('pagination-from').textContent = totalRecords > 0 ? (currentPage - 1) * limit + 1 : 0;
        document.getElementById('pagination-to').textContent = Math.min(currentPage * limit, totalRecords);
        document.getElementById('pagination-total').textContent = totalRecords;
        document.getElementById('pagination-prev').disabled = currentPage === 1;
        document.getElementById('pagination-next').disabled = currentPage >= totalPages;
    };

    const renderMembershipTypes = () => {
        membershipTypesList.innerHTML = '';
        if (appState.membershipTypes.length === 0) {
            membershipTypesList.innerHTML = `<p class="text-sm text-gray-500 p-4 text-center">No membership types created yet.</p>`;
            return;
        }
        const monthNames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        appState.membershipTypes.forEach(type => {
            const div = document.createElement('div');
            div.className = 'p-2 border-b flex justify-between items-center';
            const renewalMonthName = monthNames[type.renewal_month] || 'N/A';
            div.innerHTML = `
                <div>
                    <p class="font-medium text-gray-800">${type.name}</p>
                    <p class="text-sm text-gray-500">MWK${parseFloat(type.fee).toFixed(2)} | Renews in ${renewalMonthName}</p>
                </div>
                <div class="flex gap-x-2">
                    <button class="text-indigo-600 hover:underline text-sm type-action-edit" data-id="${type.id}">Edit</button>
                    <button class="text-red-600 hover:underline text-sm type-action-delete" data-id="${type.id}">Delete</button>
                </div>
            `;
            membershipTypesList.appendChild(div);
        });
    };

    // --- 5. DATA FETCHING ---
    const fetchMembers = async () => {
        const params = new URLSearchParams({ page: appState.pagination.currentPage, limit: appState.pagination.limit, ...appState.filters });
        // The apiCall function now handles the spinner automatically.
        const result = await apiCall(`${API.readMembers}?${params.toString()}`);
        if (result.success) {
            appState.members = result.data;
            appState.pagination = result.pagination;
            renderTable();
            renderPagination();
        } else {
             membersTableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-sm text-red-500">Could not load members. ${result.message}</td></tr>`;
        }
    };
    
    // ... other fetch functions are unchanged ...
    const fetchPaymentMethods = async () => {
        console.log("Fetching payment methods...");
        if (appState.paymentMethods.length > 0) return;
        const result = await apiCall(API.getPaymentMethods);
        console.log("Payment methods fetched:", result);
        if (result.success) appState.paymentMethods = result.data;
    };

    const fetchMembershipTypes = async () => {
        const result = await apiCall(API.readTypes);
        if (result.success) {
            appState.membershipTypes = result.data;
            renderMembershipTypes();
        }
    };

    const fetchCompanies = async () => {
        const result = await apiCall(API.readCompanies);
        if (result.success) appState.companies = result.data;
    };
    // --- 6. EVENT HANDLERS & MODAL LOGIC ---
    
    // NEW: Function to open and configure the delete confirmation modal
    const openDeleteConfirmModal = (title, message, onConfirm) => {
        deleteModalTitle.textContent = title;
        deleteModalMessage.textContent = message;
        appState.pendingDeleteAction = onConfirm; // Store the action to run
        deleteConfirmModal.classList.remove('hidden');
    };

    const closeDeleteConfirmModal = () => {
        appState.pendingDeleteAction = null; // Clear the action
        deleteConfirmModal.classList.add('hidden');
    };

    // ... handleFilterSubmit, handleClearFilters, handlePagination, openTypesModal, resetTypeForm, handleTypeFormSubmit are unchanged ...
    const handleFilterSubmit = (e) => {
        e.preventDefault();
        appState.filters = Object.fromEntries(new FormData(filtersForm).entries());
        appState.pagination.currentPage = 1;
        fetchMembers();
    };

    const handleClearFilters = () => {
        filtersForm.reset();
        appState.filters = {
            membership_card_number: '',
            full_name: '',
            company_name: '',
            status: ''
        };
        appState.pagination.currentPage = 1;
        fetchMembers();
    };

    const handlePagination = (direction) => {
        if (direction === 'next' && appState.pagination.currentPage < appState.pagination.totalPages) {
            appState.pagination.currentPage++;
        } else if (direction === 'prev' && appState.pagination.currentPage > 1) {
            appState.pagination.currentPage--;
        }
        fetchMembers();
    };

    const openTypesModal = () => {
        resetTypeForm();
        fetchMembershipTypes();
        membershipTypesModal.classList.remove('hidden');
    };

    const resetTypeForm = () => {
        membershipTypeForm.reset();
        membershipTypeForm.querySelector('[name="id"]').value = '';
        typeFormTitle.textContent = 'Add New Type';
        cancelEditTypeBtn.classList.add('hidden');
    };

    const handleTypeFormSubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(membershipTypeForm);
        const id = formData.get('id');
        const result = await apiCall(id ? API.updateType : API.createType, {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(formData))
        });
        if (result.success) {
            showToast(`Membership type ${id ? 'updated' : 'created'} successfully.`);
            resetTypeForm();
            fetchMembershipTypes();
        }
    };
    // MODIFIED: handleTypesListClick now uses the confirmation modal
    const handleTypesListClick = (e) => {
        const target = e.target;
        const id = target.dataset.id;
        if (!id) return;

        if (target.classList.contains('type-action-edit')) {
            const type = appState.membershipTypes.find(t => t.id == id);
            if (type) {
                membershipTypeForm.querySelector('[name="id"]').value = type.id;
                membershipTypeForm.querySelector('[name="name"]').value = type.name;
                membershipTypeForm.querySelector('[name="fee"]').value = type.fee;
                membershipTypeForm.querySelector('[name="renewal_month"]').value = type.renewal_month;
                membershipTypeForm.querySelector('[name="description"]').value = type.description;
                typeFormTitle.textContent = 'Edit Type';
                cancelEditTypeBtn.classList.remove('hidden');
            }
        } else if (target.classList.contains('type-action-delete')) {
            openDeleteConfirmModal(
                'Delete Membership Type',
                'Are you sure you want to delete this membership type? This cannot be undone.',
                async () => {
                    const result = await apiCall(API.deleteType, { method: 'POST', body: JSON.stringify({ id }) });
                    if (result.success) {
                        showToast('Membership type deleted.');
                        fetchMembershipTypes(); // Refresh the list in the modal
                    }
                    closeDeleteConfirmModal();
                }
            );
        }
    };
    
    // MODIFIED: Simplified openAddSingleModal to ONLY add new members
    const openAddSingleModal = async () => {
        addSingleMemberForm.reset();
        addMemberModalTitle.textContent = 'Add New Member Subscription';

        // Reset fields to their default state for adding
        document.getElementById('user-type-existing').checked = true;
        existingUserSection.classList.remove('hidden');
        newUserSection.classList.add('hidden');
        userSearchResults.innerHTML = '';
        userSearchResults.classList.add('hidden');
        userSearchInput.disabled = false;
        userSearchInput.value = '';
        
        ['member-name', 'member-email', 'member-phone'].forEach(id => {
            const el = document.getElementById(id);
            el.disabled = false;
            el.required = false;
        });

        await Promise.all([
            appState.membershipTypes.length === 0 ? fetchMembershipTypes() : Promise.resolve(),
            appState.paymentMethods.length === 0 ? fetchPaymentMethods() : Promise.resolve()
        ]);

        const memberTypeSelect = addSingleMemberForm.querySelector('#member-type');
        memberTypeSelect.innerHTML = '<option value="">-- Select a type --</option>' + appState.membershipTypes.map(t => `<option value="${t.id}" data-fee="${t.fee}">${t.name} (MWK${parseFloat(t.fee).toFixed(2)})</option>`).join('');
        
        const paymentMethodSelect = addSingleMemberForm.querySelector('#member-payment');
        paymentMethodSelect.innerHTML = appState.paymentMethods.map(m => `<option value="${m}">${m.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</option>`).join('');
        
        const today = new Date().toISOString().split('T')[0];
        addSingleMemberForm.querySelector('[name="start_date"]').value = today;

        addSingleMemberModal.classList.remove('hidden');
    };

    // ... handleUserTypeChange, handleUserSearch, handleSelectUser are unchanged ...
    const handleUserTypeChange = (e) => {
        const isNew = e.target.value === 'new';
        existingUserSection.classList.toggle('hidden', isNew);
        newUserSection.classList.toggle('hidden', !isNew);
        ['member-name', 'member-email', 'member-phone'].forEach(id => {
            const el = document.getElementById(id);
            el.required = isNew;
            el.disabled = !isNew;
            if (isNew) el.value = '';
        });
        if (isNew) {
            addSingleMemberForm.querySelector('[name="user_id"]').value = '';
            userSearchInput.value = '';
        }
    };
    let searchTimeout;
    const handleUserSearch = (e) => {
        clearTimeout(searchTimeout);
        if (e.target.value.length < 2) {
            userSearchResults.classList.add('hidden');
            return;
        }
        searchTimeout = setTimeout(async () => {
            const result = await apiCall(`${API.searchUsers}?term=${encodeURIComponent(e.target.value)}`);
            userSearchResults.innerHTML = '';
            if (result.success && result.data.length > 0) {
                result.data.forEach(user => {
                    const div = document.createElement('div');
                    div.className = 'p-2 hover:bg-indigo-100 cursor-pointer';
                    div.textContent = `${user.full_name} (${user.email})`;
                    div.dataset.userId = user.id;
                    div.dataset.userName = user.full_name;
                    div.dataset.userEmail = user.email;
                    div.dataset.userPhone = user.phone;
                    userSearchResults.appendChild(div);
                });
            } else {
                userSearchResults.innerHTML = '<div class="p-2 text-sm text-gray-500">No users found.</div>';
            }
            userSearchResults.classList.remove('hidden');
        }, 300);
    };
    
    const handleSelectUser = (e) => {
        if (!e.target.dataset.userId) return;
        const {
            userId,
            userName,
            userEmail,
            userPhone
        } = e.target.dataset;
        addSingleMemberForm.querySelector('[name="user_id"]').value = userId;
        userSearchInput.value = `${userName} (${userEmail})`;
        document.getElementById('member-name').value = userName;
        document.getElementById('member-email').value = userEmail;
        document.getElementById('member-phone').value = userPhone;
        ['member-name', 'member-email', 'member-phone'].forEach(id => document.getElementById(id).disabled = true);
        userSearchResults.classList.add('hidden');
    };

    // MODIFIED: Simplified handleSaveSingleMember to ONLY create new members
    const handleSaveSingleMember = async (e) => {
        e.preventDefault();
        ['member-name', 'member-email', 'member-phone'].forEach(id => document.getElementById(id).disabled = false);
        const formData = new FormData(addSingleMemberForm);
        const result = await apiCall(API.createSingle, { method: 'POST', body: formData });
        if (result.success) {
            showToast(result.message || 'Member created successfully.');
            addSingleMemberModal.classList.add('hidden');
            fetchMembers();
        }
    };
    
    // MODIFIED: handleMembersTableClick to remove edit and use confirm modal
    const handleMembersTableClick = (e) => {
        const target = e.target;
        const id = target.dataset.id;
        if (!id) return;

        if (target.classList.contains('member-action-delete')) {
            openDeleteConfirmModal(
                'Delete Subscription',
                'Are you sure you want to delete this membership subscription? This will not delete the user.',
                async () => {
                    const result = await apiCall(API.deleteSubscription, { method: 'POST', body: JSON.stringify({ id }) });
                    if (result.success) {
                        showToast('Membership subscription deleted.');
                        fetchMembers(); // Refresh the main table
                    }
                    closeDeleteConfirmModal();
                }
            );
        }
    };

    // ... All "Add Multiple Members" functions (openAddMultipleModal, updateBulkModalStep, etc.) are unchanged ...
    const openAddMultipleModal = async () => {
        addMultipleMembersForm.reset();
        await Promise.all([
            appState.companies.length === 0 ? fetchCompanies() : Promise.resolve(),
            appState.membershipTypes.length === 0 ? fetchMembershipTypes() : Promise.resolve(),
            appState.paymentMethods.length === 0 ? fetchPaymentMethods() : Promise.resolve()
        ]);

        const companySelect = document.getElementById('bulk-company-select');
        companySelect.innerHTML = '<option value="">-- Select Company --</option>' + appState.companies.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

        const typeSelect = document.getElementById('bulk-membership-type');
        typeSelect.innerHTML = '<option value="">-- Select Type --</option>' + appState.membershipTypes.map(t => `<option value="${t.id}" data-fee="${t.fee}">${t.name}</option>`).join('');

        const paymentSelect = document.getElementById('bulk-payment-method');
        paymentSelect.innerHTML = appState.paymentMethods.map(m => `<option value="${m}">${m.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</option>`).join('');

        document.getElementById('bulk-user-list').innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Select a company to see its users.</p>';
        document.getElementById('bulk-selected-users').innerHTML = '<span class="text-sm text-gray-400">None</span>';
        document.getElementById('bulk-user-search').value = '';
        document.getElementById('bulk-plan-cost').textContent = 'MWK 0.00';
        document.getElementById('bulk-balance-due').textContent = 'MWK 0.00';

        // ## KEY FIX 1: Set initial 'required' state on modal open. ##
        // Default is 'existing', so company select is required, and CSV is not.
        document.getElementById('bulk-type-existing').checked = true;
        companySelect.required = true;
        document.getElementById('bulk-csv-input').required = false;

        updateBulkModalStep(1);
        addMultipleMembersModal.classList.remove('hidden');
    };

    const updateBulkModalStep = (step) => {
        const sections = addMultipleMembersForm.querySelectorAll('[data-step]');
        sections.forEach(s => s.classList.add('hidden'));

        const currentSection = addMultipleMembersForm.querySelector(`[data-step="${step}"]`);
        if (currentSection) currentSection.classList.remove('hidden');

        if (step === 2) {
            const selectedType = document.querySelector('input[name="bulk_user_type"]:checked').value;
            sections.forEach(s => {
                if (s.dataset.step === '2') s.classList.add('hidden');
            });
            addMultipleMembersForm.querySelector(`[data-step="2"][data-section="${selectedType}"]`).classList.remove('hidden');
        }

        bulkBackButton.classList.toggle('hidden', step === 1);
        bulkNextButton.classList.toggle('hidden', step === 3);
        bulkSubmitButton.classList.toggle('hidden', step !== 3);
        addMultipleMembersForm.dataset.currentStep = step;
    };

    const updateSelectedUserTags = () => {
        const selectedUsersDiv = document.getElementById('bulk-selected-users');
        const checkedBoxes = document.querySelectorAll('#bulk-user-list input[type="checkbox"]:checked');

        selectedUsersDiv.innerHTML = '';

        if (checkedBoxes.length === 0) {
            selectedUsersDiv.innerHTML = '<span class="text-sm text-gray-400">None</span>';
            return;
        }

        checkedBoxes.forEach(checkbox => {
            const userLabel = document.querySelector(`label[for="${checkbox.id}"]`);
            if (userLabel) {
                const tag = document.createElement('span');
                tag.className = 'inline-flex items-center gap-x-1.5 rounded-md bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-700';
                tag.textContent = userLabel.textContent;
                selectedUsersDiv.appendChild(tag);
            }
        });
    };

    const handleBulkUserSearch = (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const userItems = document.querySelectorAll('#bulk-user-list .user-item');

        userItems.forEach(item => {
            const name = item.dataset.name.toLowerCase();
            const email = item.dataset.email.toLowerCase();
            if (name.includes(searchTerm) || email.includes(searchTerm)) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    };

    const updateBulkCostDisplay = () => {
        const typeSelect = document.getElementById('bulk-membership-type');
        const amountPaidInput = document.getElementById('bulk-amount-paid');
        const costDisplay = document.getElementById('bulk-plan-cost');
        const balanceDisplay = document.getElementById('bulk-balance-due');

        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        const fee = parseFloat(selectedOption.dataset.fee || 0);
        const paid = parseFloat(amountPaidInput.value || 0);
        const balance = fee - paid;

        costDisplay.textContent = `MWK ${fee.toFixed(2)}`;
        balanceDisplay.textContent = `MWK ${balance.toFixed(2)}`;
    };

    const handleBulkSubscribe = async (e) => {
        e.preventDefault();
        const formData = new FormData(addMultipleMembersForm);
        const selectedType = formData.get('bulk_user_type');

        let url;
        if (selectedType === 'existing') {
            const userIds = formData.getAll('user_ids[]');
            if (userIds.length === 0) {
                showToast('Please select at least one user.', true);
                return;
            }
            url = API.createBulkSubscriptions;
        } else { // 'csv'
            if (!formData.get('csv_file') || !formData.get('csv_file').name) {
                showToast('Please select a CSV file.', true);
                return;
            }
            url = API.uploadCsv;
        }

        const result = await apiCall(url, {
            method: 'POST',
            body: formData
        });

        if (result.success) {
            showToast(result.message || 'Bulk operation successful.');
            addMultipleMembersModal.classList.add('hidden');
            fetchMembers();
        }
    };
    
    // --- 7. EVENT LISTENERS ---
    const attachEventListeners = () => {
        filtersForm.addEventListener('submit', handleFilterSubmit);
        clearFiltersBtn.addEventListener('click', handleClearFilters);
        document.getElementById('pagination-prev').addEventListener('click', () => handlePagination('prev'));
        document.getElementById('pagination-next').addEventListener('click', () => handlePagination('next'));
        
        manageTypesBtn.addEventListener('click', openTypesModal);
        addSingleMemberBtn.addEventListener('click', openAddSingleModal); // Simplified call
        addMultipleMembersBtn.addEventListener('click', openAddMultipleModal);

        membershipTypeForm.addEventListener('submit', handleTypeFormSubmit);
        membershipTypesList.addEventListener('click', handleTypesListClick);
        cancelEditTypeBtn.addEventListener('click', resetTypeForm);
        
        addSingleMemberForm.addEventListener('submit', handleSaveSingleMember);
        userTypeRadios.forEach(radio => radio.addEventListener('change', handleUserTypeChange));
        userSearchInput.addEventListener('input', handleUserSearch);
        userSearchResults.addEventListener('click', handleSelectUser);
        
        membersTableBody.addEventListener('click', handleMembersTableClick);

        // Listeners for the new delete confirmation modal
        confirmDeleteBtn.addEventListener('click', () => {
            if (typeof appState.pendingDeleteAction === 'function') {
                appState.pendingDeleteAction();
            }
        });
        cancelDeleteBtn.addEventListener('click', closeDeleteConfirmModal);
        
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.fixed').classList.add('hidden');
            });
        });

        // Event listeners for the multi-add modal (unchanged from your fixed version)
        bulkUserTypeRadios.forEach(radio => radio.addEventListener('change', (e) => {
            const selectedType = e.target.value;
            const companySelect = document.getElementById('bulk-company-select');
            const csvInput = document.getElementById('bulk-csv-input');
            if (selectedType === 'existing') {
                companySelect.required = true;
                csvInput.required = false;
            } else {
                companySelect.required = false;
                csvInput.required = true;
            }
            if (addMultipleMembersForm.dataset.currentStep === '2') {
                updateBulkModalStep(2);
            }
        }));
        bulkNextButton.addEventListener('click', () => {
            const currentStep = parseInt(addMultipleMembersForm.dataset.currentStep, 10);
            updateBulkModalStep(currentStep + 1);
        });

        bulkBackButton.addEventListener('click', () => {
            const currentStep = parseInt(addMultipleMembersForm.dataset.currentStep, 10);
            updateBulkModalStep(currentStep - 1);
        });

        document.getElementById('bulk-company-select').addEventListener('change', async (e) => {
            const companyId = e.target.value;
            const userListDiv = document.getElementById('bulk-user-list');
            companyUsersCache = [];
            updateSelectedUserTags();
            if (!companyId) {
                userListDiv.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Select a company to see its users.</p>';
                return;
            }
            userListDiv.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Loading users...</p>';
            const result = await apiCall(`${API.getUsersByCompany}?company_id=${companyId}`);
            if (result.success && result.data.length > 0) {
                companyUsersCache = result.data;
                userListDiv.innerHTML = result.data.map(user => `
                    <div class="relative flex items-start user-item" data-name="${user.full_name}" data-email="${user.email}">
                        <div class="flex h-6 items-center">
                            <input id="bulk-user-${user.id}" name="user_ids[]" value="${user.id}" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="bulk-user-${user.id}" class="font-medium text-gray-900">${user.full_name}</label>
                            <p class="text-gray-500">${user.email}</p>
                        </div>
                    </div>
                `).join('');
            } else {
                userListDiv.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No users found for this company.</p>';
            }
        });

        addSingleMemberForm.addEventListener('change', e => {
            const amountPaidInput = document.getElementById('member-amount-paid');
            const memberTypeSelect = document.getElementById('member-type');

            if (e.target.id === 'issue-receipt') {
                if (e.target.checked) {
                    const selectedOption = memberTypeSelect.options[memberTypeSelect.selectedIndex];
                    if (selectedOption && selectedOption.dataset.fee) {
                        amountPaidInput.value = selectedOption.dataset.fee;
                    }
                }
            }
            if (e.target.id === 'member-type') {
                const issueReceiptCheckbox = document.getElementById('issue-receipt');
                if (issueReceiptCheckbox.checked) {
                    const selectedOption = e.target.options[e.target.selectedIndex];
                    amountPaidInput.value = selectedOption.dataset.fee || '0.00';
                }
            }
        });
        addMultipleMembersForm.addEventListener('submit', handleBulkSubscribe);
        document.getElementById('bulk-user-list').addEventListener('change', updateSelectedUserTags);
        document.getElementById('bulk-user-search').addEventListener('input', handleBulkUserSearch);
        document.getElementById('bulk-membership-type').addEventListener('change', updateBulkCostDisplay);
        document.getElementById('bulk-amount-paid').addEventListener('input', updateBulkCostDisplay);
    };

    // --- 8. INITIALIZATION ---
    const initialize = () => {
        attachEventListeners();
        fetchMembers();
    };

    initialize();
});