// /assets/js/content.js

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. STATE MANAGEMENT ---
    const appState = {
        activeTab: 'news',
        membershipTypes: []
    };
    
    let newsCurrentPage = 1;
    let newsCurrentFilters = { search: '', date: '', status: '' };
    const newsRecordsPerPage = 10;
    
    let commsCurrentPage = 1;
    let commsCurrentFilters = { search: '', channel: '' };
    const commsRecordsPerPage = 10;

    const API = {
        news: {
            read: '/api/news/read.php',
            create: '/api/news/create.php',
            read_single: '/api/news/read_single.php',
            update: '/api/news/update.php',
            delete: '/api/news/delete.php',
        },
        comms: {
            read: '/api/communications/read.php',
            create: '/api/communications/create.php',
            delete: '/api/communications/delete.php',
            read_single: '/api/communications/read_single.php',
        },
        readTypes: '/api/memberships/get_types.php',
    };

    // --- 2. DOM ELEMENTS ---
    const addNewBtn = document.getElementById('addNewBtn');
    
    // Tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    // News
    const newsSection = document.getElementById('tab-panel-news');
    const newsTableContainer = document.getElementById('newsTableContainer');
    const newsPaginationContainer = document.getElementById('newsPaginationContainer');
    const newsSearchInput = document.getElementById('newsSearchInput');
    const newsDateFilter = document.getElementById('newsDateFilter');
    const newsStatusFilter = document.getElementById('newsStatusFilter');
    const newsModal = document.getElementById('newsModal');
    const newsForm = document.getElementById('newsForm');
    const newsModalTitle = document.getElementById('newsModalTitle');
    const currentImagePreview = document.getElementById('current-image-preview');
    const viewNewsModal = document.getElementById('viewNewsModal');

    // Comms
    const commsSection = document.getElementById('tab-panel-comms');
    const commsTableContainer = document.getElementById('commsTableContainer');
    const commsPaginationContainer = document.getElementById('commsPaginationContainer');
    const commsSearchInput = document.getElementById('commsSearchInput');
    const commsChannelFilter = document.getElementById('commsChannelFilter');
    const commsModal = document.getElementById('commsModal');
    const commsForm = document.getElementById('commsForm');
    const commsSubjectWrapper = document.getElementById('comms-subject-wrapper');
    const viewCommsModal = document.getElementById('viewCommsModal');
    
    // Quill
    const quillNews = new Quill('#news-editor', { theme: 'snow', modules: { toolbar: true } });
    const quillComms = new Quill('#comms-editor', { theme: 'snow', modules: { toolbar: true } });

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

    const apiCall = async (url, options = {}, showSpinnerFlag = true) => {
        if (showSpinnerFlag) showSpinner();
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                let errorMsg = `HTTP error! status: ${response.status}`;
                try {
                    const errData = await response.json();
                    errorMsg = errData.message || errorMsg;
                } catch (e) {}
                throw new Error(errorMsg);
            }
            const result = await response.json();
            if (result.success === false) throw new Error(result.message);
            return result;
        } catch (error) {
            showToast(error.message || 'A network error occurred.', true);
            return { success: false, message: error.message };
        } finally {
            if (showSpinnerFlag) hideSpinner();
        }
    };

    const renderPagination = (container, total, limit, currentPage, pageChangeCallback) => {
        container.innerHTML = '';
        if (!total || total <= 0 || !limit || limit <= 0) return;
        const totalPages = Math.ceil(total / limit);
        if (totalPages <= 1) return;

        const maxVisiblePages = 5;
        let startPage, endPage;
        if (totalPages <= maxVisiblePages) {
            startPage = 1;
            endPage = totalPages;
        } else {
            const maxPagesBeforeCurrent = Math.floor(maxVisiblePages / 2);
            const maxPagesAfterCurrent = Math.ceil(maxVisiblePages / 2) - 1;
            if (currentPage <= maxPagesBeforeCurrent) {
                startPage = 1;
                endPage = maxVisiblePages;
            } else if (currentPage + maxPagesAfterCurrent >= totalPages) {
                startPage = totalPages - maxVisiblePages + 1;
                endPage = totalPages;
            } else {
                startPage = currentPage - maxPagesBeforeCurrent;
                endPage = currentPage + maxPagesAfterCurrent;
            }
        }

        const firstRecord = Math.min((currentPage - 1) * limit + 1, total);
        const lastRecord = Math.min(currentPage * limit, total);
        const resultsText = `<p class="text-sm text-gray-700">Showing <span class="font-medium">${firstRecord}</span> to <span class="font-medium">${lastRecord}</span> of <span class="font-medium">${total}</span> results</p>`;
        let pageButtonsHtml = '';

        pageButtonsHtml += `<button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="page-btn relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"><span class="sr-only">Previous</span><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg></button>`;
        if (startPage > 1) {
            pageButtonsHtml += `<button data-page="1" class="page-btn relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">1</button>`;
            if (startPage > 2) {
                pageButtonsHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>`;
            }
        }
        for (let i = startPage; i <= endPage; i++) {
            const isCurrent = i === currentPage;
            pageButtonsHtml += `<button data-page="${i}" ${isCurrent ? 'aria-current="page"' : ''} class="page-btn relative inline-flex items-center px-4 py-2 text-sm font-semibold ${isCurrent ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'}">${i}</button>`;
        }
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pageButtonsHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>`;
            }
            pageButtonsHtml += `<button data-page="${totalPages}" class="page-btn relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">${totalPages}</button>`;
        }
        pageButtonsHtml += `<button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="page-btn relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"><span class="sr-only">Next</span><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg></button>`;

        container.innerHTML = `
            <div class="flex flex-1 justify-between sm:hidden">
                <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="page-btn relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="page-btn relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>${resultsText}</div>
                <div><nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">${pageButtonsHtml}</nav></div>
            </div>`;
            
        container.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const pageToGo = parseInt(e.currentTarget.dataset.page);
                if (pageToGo !== currentPage) {
                    pageChangeCallback(pageToGo);
                }
            });
        });
    };

    // --- 5. TAB & RENDER LOGIC ---

    const switchTab = (targetTab) => {
        appState.activeTab = targetTab;
        const isNews = targetTab === 'news';

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

        addNewBtn.textContent = isNews ? '+ Add New Article' : '+ New Communication';
        
        if (targetTab === 'news') loadNews();
        else if (targetTab === 'comms') loadComms();
    };

    // --- NEWS LOGIC ---
    const loadNews = async () => {
        const params = new URLSearchParams({ 
            page: newsCurrentPage, 
            limit: newsRecordsPerPage, 
            ...newsCurrentFilters 
        }).toString();
        const result = await apiCall(`${API.news.read}?${params}`);
        if (result.success) {
            renderNewsTable(result.data);
            renderPagination(newsPaginationContainer, result.total_records, newsRecordsPerPage, newsCurrentPage, (newPage) => {
                newsCurrentPage = newPage;
                loadNews();
            });
        } else {
            // Ensure table structure exists even on error
            if (!newsTableContainer.querySelector('thead')) {
                 renderNewsTable([]); // Render empty table structure
            }
            newsTableContainer.querySelector('tbody').innerHTML = `<tr><td colspan="5" class="px-3 py-12 text-center text-sm text-gray-500">Could not load articles.</td></tr>`;
        }
    };

    //
    // ======================================================
    // START: CORRECTED renderNewsTable
    // ======================================================
    //
    const renderNewsTable = (articles) => {
        // 1. Ensure table structure exists
        if (!newsTableContainer.querySelector('thead')) {
            newsTableContainer.innerHTML = `
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:pl-0">Article</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white"></tbody>
                </table>`;
        }
        
        // 2. NOW, get the correct tbody reference
        const tableBody = newsTableContainer.querySelector('tbody');
        
        // 3. Build an HTML string for all rows
        let rowsHtml = '';
        if (!articles || articles.length === 0) {
            rowsHtml = `<tr><td colspan="5" class="px-3 py-12 text-center text-sm text-gray-500">No news articles found.</td></tr>`;
        } else {
            articles.forEach(article => {
                const now = new Date();
                const scheduledDate = article.scheduled_date ? new Date(article.scheduled_date.replace(' ', 'T')) : new Date(article.created_at.replace(' ', 'T'));
                
                let status, statusClass;
                if (article.scheduled_date && scheduledDate > now) {
                    status = 'Scheduled';
                    statusClass = 'bg-yellow-100 text-yellow-800';
                } else {
                    status = 'Published';
                    statusClass = 'bg-green-100 text-green-700';
                }

                rowsHtml += `
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <img class="h-10 w-10 rounded-md object-cover" src="${article.media_url || 'https://via.placeholder.com/80'}" alt="">
                                </div>
                                <div class="ml-4">
                                    <div class="font-medium text-gray-900">${article.title}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${article.view_count || 0}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500"><span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${statusClass}">${status}</span></td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${scheduledDate.toLocaleDateString()}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <button class="text-gray-500 hover:text-indigo-600 action-view" data-id="${article.id}" data-type="news">View</button>
                            <button class="text-indigo-600 hover:text-indigo-900 ml-4 action-edit" data-id="${article.id}" data-type="news">Edit</button>
                            <button class="text-red-600 hover:text-red-900 ml-4 action-delete" data-id="${article.id}" data-type="news">Delete</button>
                        </td>
                    </tr>
                `;
            });
        }
        
        // 4. Set the innerHTML of the tbody once
        tableBody.innerHTML = rowsHtml;
    };
    //
    // ======================================================
    // END: CORRECTED renderNewsTable
    // ======================================================
    //

    
    // --- COMMS LOGIC ---
    const loadComms = async () => {
        const params = new URLSearchParams({ 
            page: commsCurrentPage, 
            limit: commsRecordsPerPage, 
            ...commsCurrentFilters 
        }).toString();
        const result = await apiCall(`${API.comms.read}?${params}`);
        if (result.success) {
            renderCommsTable(result.data);
            renderPagination(commsPaginationContainer, result.total_records, commsRecordsPerPage, commsCurrentPage, (newPage) => {
                commsCurrentPage = newPage;
                loadComms();
            });
        } else {
             // Ensure table structure exists even on error
            if (!commsTableContainer.querySelector('thead')) {
                 renderCommsTable([]); // Render empty table structure
            }
            commsTableContainer.querySelector('tbody').innerHTML = `<tr><td colspan="4" class="px-3 py-12 text-center text-sm text-gray-500">Could not load communications.</td></tr>`;
        }
    };
    
    //
    // ======================================================
    // START: CORRECTED renderCommsTable
    // ======================================================
    //
    const renderCommsTable = (comms) => {
        // 1. Ensure table structure exists
        if (!commsTableContainer.querySelector('thead')) {
            commsTableContainer.innerHTML = `
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:pl-0">Subject</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Channel</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white"></tbody>
                </table>`;
        }
        
        // 2. NOW, get the correct tbody reference
        const commsTableBody = commsTableContainer.querySelector('tbody');
        
        // 3. Build an HTML string for all rows
        let rowsHtml = '';
        if (!comms || comms.length === 0) {
            rowsHtml = `<tr><td colspan="4" class="px-3 py-12 text-center text-sm text-gray-500">No communications found.</td></tr>`;
        } else {
            comms.forEach(comm => {
                rowsHtml += `
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">${comm.subject || '(No Subject)'}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 capitalize">${comm.channel}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${new Date(comm.sent_at.replace(' ','T')).toLocaleString()}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <button class="text-gray-500 hover:text-indigo-600 action-view" data-id="${comm.id}" data-type="comms">View</button>
                            <button class="text-red-600 hover:text-red-900 ml-4 action-delete" data-id="${comm.id}" data-type="comms">Delete</button>
                        </td>
                    </tr>
                `;
            });
        }
        
        // 4. Set the innerHTML of the tbody once
        commsTableBody.innerHTML = rowsHtml;
    };
    //
    // ======================================================
    // END: CORRECTED renderCommsTable
    // ======================================================
    //


    const fetchMembershipTypes = async () => {
        if (appState.membershipTypes.length > 0) return; // Only fetch once
        const result = await apiCall(API.readTypes, {}, false); // No spinner
        if (result.success && Array.isArray(result.data)) { // Check if it's an array
            appState.membershipTypes = result.data;
        } else if (result.success && typeof result.data === 'object') { // Handle if API returns { "1": "Name", "2": "Name" }
             appState.membershipTypes = Object.keys(result.data).map(key => ({
                id: key,
                name: result.data[key]
            }));
        } else if (result.success && Array.isArray(result)) { // Handle if API just returns an array
             appState.membershipTypes = result;
        }
    };

    // --- 6. MODAL LOGIC ---

    const openViewNewsModal = async (articleId) => {
        viewNewsModal.classList.remove('hidden');
        const titleEl = document.getElementById('viewNewsTitle');
        const contentEl = document.getElementById('viewNewsContent');
        titleEl.textContent = 'Loading...';
        contentEl.innerHTML = '<div class="h-48 bg-gray-200 animate-pulse rounded-md"></div>';

        const result = await apiCall(`${API.news.read_single}?id=${articleId}`);
        if(result.success) {
            const article = result.data;
            titleEl.textContent = article.title;
            let contentHtml = '';
            if(article.media_url) {
                contentHtml += `<img src="${article.media_url}" class="w-full h-64 object-cover rounded-lg mb-4">`;
            }
            contentHtml += article.content;
            contentEl.innerHTML = contentHtml;
        }
    };

    const openViewCommsModal = async (commId) => {
        viewCommsModal.classList.remove('hidden');
        const titleEl = document.getElementById('viewCommsTitle');
        const bodyEl = document.getElementById('viewCommsBody');
        const recipientsEl = document.getElementById('viewCommsRecipients');
        const countEl = document.getElementById('recipient-count');
        
        titleEl.textContent = 'Loading...';
        bodyEl.innerHTML = '<div class="h-32 bg-gray-200 animate-pulse rounded-md"></div>';
        recipientsEl.innerHTML = '<div class="h-24 bg-gray-200 animate-pulse rounded-md"></div>';
        countEl.textContent = '0';

        const result = await apiCall(`${API.comms.read_single}?id=${commId}`);
        if(result.success) {
            const comm = result.data;
            titleEl.textContent = comm.details.subject || '(No Subject)';
            bodyEl.innerHTML = comm.details.body;
            
            if (comm.recipients && comm.recipients.length > 0) {
                countEl.textContent = comm.recipients.length;
                recipientsEl.innerHTML = comm.recipients.map(r => `<div class="p-2 border-b last:border-b-0">${r.full_name || 'N/A'} &lt;${r.email || r.phone}&gt; - ${r.status}</div>`).join('');
            } else {
                countEl.textContent = '0';
                recipientsEl.innerHTML = '<p class="p-4 text-center text-gray-500">No recipients found.</p>';
            }
        }
    };

    const openNewsModal = async (articleId = null) => {
        newsForm.reset();
        currentImagePreview.innerHTML = '';
        quillNews.setContents([]); 

        if (articleId) {
            newsModalTitle.textContent = 'Edit Article';
            const result = await apiCall(`${API.news.read_single}?id=${articleId}`);
            if (result.success) {
                const article = result.data;
                newsForm.querySelector('[name="id"]').value = article.id;
                newsForm.querySelector('[name="title"]').value = article.title;
                quillNews.root.innerHTML = article.content;
                if (article.scheduled_date) {
                    newsForm.querySelector('[name="scheduled_date"]').value = article.scheduled_date.slice(0, 16);
                }
                if(article.media_url) {
                    currentImagePreview.innerHTML = `<p class="text-xs text-gray-500 mb-1">Current Image:</p><img src="${article.media_url}" class="h-20 rounded-md">`;
                }
            }
        } else {
            newsModalTitle.textContent = 'Add New Article';
            newsForm.querySelector('[name="id"]').value = '';
        }
        newsModal.classList.remove('hidden');
    };

    const openCommsModal = async () => {
        commsForm.reset();
        quillComms.setContents([]);
        await fetchMembershipTypes();

        const recipientsSelect = document.getElementById('comms-recipients');
        let options = `
            <option value="all_users">All Users</option>
            <option value="all_members">All Active Members</option>
        `;
        appState.membershipTypes.forEach(type => {
            options += `<option value="type_${type.id}">Members: ${type.name}</option>`;
        });
        recipientsSelect.innerHTML = options;
        
        commsSubjectWrapper.style.display = 'block'; // Default to email
        commsModal.classList.remove('hidden');
    };

    // --- 7. FORM SUBMISSION ---
    const handleSaveNews = async (e) => {
        e.preventDefault();
        const formData = new FormData(newsForm);
        formData.set('content', quillNews.root.innerHTML); // Get HTML from Quill
        
        const url = formData.get('id') ? API.news.update : API.news.create;
        const result = await apiCall(url, { method: 'POST', body: formData });

        if (result.success) {
            showToast(result.message);
            newsModal.classList.add('hidden');
            loadNews();
        }
    };

    const handleSendComms = async (e) => {
        e.preventDefault();
        const formData = new FormData(commsForm);
        const recipients = Array.from(commsForm.querySelector('#comms-recipients').selectedOptions).map(opt => opt.value);
        
        const payload = {
            channel: formData.get('channel'),
            subject: formData.get('subject'),
            body: quillComms.root.innerHTML,
            recipients: recipients,
        };
        
        const result = await apiCall(API.comms.create, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
        if (result.success) {
            showToast(result.message);
            commsModal.classList.add('hidden');
            loadComms(); // Refresh comms list
        }
    };

    // --- 8. EVENT LISTENERS ---
    const setupEventListeners = () => {
        // Tab
        tabButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const targetTab = e.currentTarget.id.replace('tab-btn-', '');
                switchTab(targetTab);
            });
        });
        
        addNewBtn.addEventListener('click', () => {
            if (appState.activeTab === 'news') openNewsModal();
            else openCommsModal();
        });

        // --- News Listeners ---
        let newsDebounceTimer;
        newsSearchInput.addEventListener('input', () => {
            clearTimeout(newsDebounceTimer);
            newsDebounceTimer = setTimeout(() => {
                newsCurrentPage = 1;
                newsCurrentFilters.search = newsSearchInput.value;
                loadNews();
            }, 350);
        });
        newsDateFilter.addEventListener('change', () => {
            newsCurrentPage = 1;
            newsCurrentFilters.date = newsDateFilter.value;
            loadNews();
        });
        newsStatusFilter.addEventListener('change', () => {
            newsCurrentPage = 1;
            newsCurrentFilters.status = newsStatusFilter.value;
            loadNews();
        });
        newsForm.addEventListener('submit', handleSaveNews);

        // --- Comms Listeners ---
        let commsDebounceTimer;
        commsSearchInput.addEventListener('input', () => {
            clearTimeout(commsDebounceTimer);
            commsDebounceTimer = setTimeout(() => {
                commsCurrentPage = 1;
                commsCurrentFilters.search = commsSearchInput.value;
                loadComms();
            }, 350);
        });
        commsChannelFilter.addEventListener('change', () => {
            commsCurrentPage = 1;
            commsCurrentFilters.channel = commsChannelFilter.value;
            loadComms();
        });
        commsForm.addEventListener('submit', handleSendComms);
        
        document.querySelectorAll('input[name="channel"]').forEach(radio => {
            radio.addEventListener('change', e => {
                commsSubjectWrapper.style.display = e.target.value === 'email' ? 'block' : 'none';
                commsForm.querySelector('#comms-subject').required = e.target.value === 'email';
            });
        });

        // --- Table Click Delegation ---
        document.querySelector('main').addEventListener('click', (e) => {
            const target = e.target;
            const id = target.dataset.id;
            const type = target.dataset.type;
            if (!id || !type) return;

            if (target.classList.contains('action-view')) {
                if (type === 'news') openViewNewsModal(id);
                if (type === 'comms') openViewCommsModal(id);
            } else if (target.classList.contains('action-edit')) {
                if (type === 'news') openNewsModal(id);
            } else if (target.classList.contains('action-delete')) {
                const item = type === 'news' ? 'article' : 'communication';
                if (confirm(`Are you sure you want to delete this ${item}?`)) {
                    const url = type === 'news' ? API.news.delete : API.comms.delete;
                    apiCall(url, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id }) }).then(result => {
                        if (result.success) {
                            showToast(result.message);
                            if(type === 'news') loadNews();
                            else loadComms();
                        }
                    });
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

    // --- 9. INITIALIZATION ---
    setupEventListeners();
    switchTab('news'); // Initial page load
});