document.addEventListener('DOMContentLoaded', () => {
    // --- 1. STATE & CONFIG ---
    const appState = {
        activeTab: 'news',
        articles: [],
        communications: [],
        membershipTypes: []
    };

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

    // --- 2. DOM & QUILL SELECTORS (FIXED) ---
    const addNewBtn = document.getElementById('addNewBtn');
    const newsSection = document.getElementById('news-section');
    const commsSection = document.getElementById('comms-section');
    const newsTableBody = document.getElementById('newsTableBody');
    const commsTableBody = document.getElementById('commsTableBody');
    const newsModal = document.getElementById('newsModal');
    const newsForm = document.getElementById('newsForm');
    const newsModalTitle = document.getElementById('newsModalTitle');
    const currentImagePreview = document.getElementById('current-image-preview'); // Added this missing selector
    const commsModal = document.getElementById('commsModal');
    const commsForm = document.getElementById('commsForm');
    const commsSubjectWrapper = document.getElementById('comms-subject-wrapper');
    const tabNews = document.getElementById('tab-news');
    const tabComms = document.getElementById('tab-comms');
    const newsFiltersForm = document.getElementById('newsFiltersForm');
    const clearNewsFiltersBtn = document.getElementById('clearNewsFiltersBtn');
    const viewNewsModal = document.getElementById('viewNewsModal');
    const viewCommsModal = document.getElementById('viewCommsModal');
    
    // Quill instances are correct
    const quillNews = new Quill('#news-editor', { theme: 'snow', modules: { toolbar: true } });
    const quillComms = new Quill('#comms-editor', { theme: 'snow', modules: { toolbar: true } });

    // --- 3. UTILITY FUNCTIONS ---
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
        try {
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            if (result.success === false) throw new Error(result.message);
            return result;
        } catch (error) {
            showToast(error.message || 'A network error occurred.', true);
            return { success: false, message: error.message };
        }
    };

    // --- 4. RENDER FUNCTIONS (FIXED) ---
    const renderNewsTable = () => {
        newsTableBody.innerHTML = '';
        if (appState.articles.length === 0) {
            newsTableBody.innerHTML = `<tr><td colspan="5" class="px-3 py-12 text-center text-sm text-gray-500">No news articles found.</td></tr>`;
            return;
        }
        appState.articles.forEach(article => {
            const row = document.createElement('tr');
            const now = new Date();
            // FIX: Added .replace(' ', 'T') for better cross-browser date parsing
            const scheduledDate = article.scheduled_date ? new Date(article.scheduled_date.replace(' ', 'T')) : new Date(article.created_at.replace(' ', 'T'));
            
            let status, statusClass;
            if (article.scheduled_date && scheduledDate > now) {
                status = 'Scheduled';
                statusClass = 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
            } else {
                status = 'Published';
                statusClass = 'bg-green-50 text-green-700 ring-green-600/20';
            }

            row.innerHTML = `
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
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500"><span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusClass}">${status}</span></td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${scheduledDate.toLocaleDateString()}</td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex items-center justify-end gap-x-4">
                        <button class="text-gray-500 hover:text-indigo-600 action-view" data-id="${article.id}" data-type="news">View</button>
                        <button class="text-indigo-600 hover:text-indigo-900 action-edit" data-id="${article.id}" data-type="news">Edit</button>
                        <button class="text-red-600 hover:text-red-900 action-delete" data-id="${article.id}" data-type="news">Delete</button>
                    </div>
                </td>
            `;
            newsTableBody.appendChild(row);
        });
    };

    const renderCommsTable = () => {
        commsTableBody.innerHTML = '';
        if (appState.communications.length === 0) {
            commsTableBody.innerHTML = `<tr><td colspan="4" class="px-3 py-12 text-center text-sm text-gray-500">No communications have been sent.</td></tr>`;
            return;
        }
        appState.communications.forEach(comm => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">${comm.subject || '(No Subject)'}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 capitalize">${comm.channel}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${new Date(comm.sent_at.replace(' ','T')).toLocaleString()}</td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex items-center justify-end gap-x-2">
                        <button class="text-gray-500 hover:text-indigo-600 action-view" data-id="${comm.id}" data-type="comms">View</button>
                        <button class="text-red-600 hover:text-red-900 action-delete" data-id="${comm.id}" data-type="comms">Delete</button>
                    </div>
                </td>
            `;
            commsTableBody.appendChild(row);
        });
    };
    
    // --- 5. DATA FETCHING (FIXED) ---
    const fetchNews = async () => {
        newsTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10">Loading articles...</td></tr>`;
        const params = new URLSearchParams(new FormData(newsFiltersForm));
        const result = await apiCall(`${API.news.read}?${params.toString()}`);
        if (result.success) {
            appState.articles = result.data;
            renderNewsTable();
        }
    };
    
    const fetchComms = async () => {
        if(appState.communications.length > 0) return; // Only fetch once per page load
        commsTableBody.innerHTML = `<tr><td colspan="4" class="text-center py-10">Loading communications...</td></tr>`;
        const result = await apiCall(API.comms.read);
        if (result.success) {
            appState.communications = result.data;
            renderCommsTable();
        }
    };
    
    const fetchMembershipTypes = async () => {
        if(appState.membershipTypes.length > 0) return; // Only fetch once
        const result = await apiCall(API.readTypes);
        if (result.success) appState.membershipTypes = result.data;
    };

    // --- 6. MODAL & EVENT LOGIC (FIXED) ---
    const handleTabClick = (tab) => {
        appState.activeTab = tab;
        const isNews = tab === 'news';
        
        // Correctly toggle all classes
        [tabNews, tabComms].forEach(t => {
            t.classList.remove('border-indigo-500', 'text-indigo-600');
            t.classList.add('border-transparent', 'text-gray-500');
        });
        const activeTabEl = isNews ? tabNews : tabComms;
        activeTabEl.classList.add('border-indigo-500', 'text-indigo-600');
        activeTabEl.classList.remove('border-transparent', 'text-gray-500');
        
        newsSection.classList.toggle('hidden', !isNews);
        commsSection.classList.toggle('hidden', isNews);
        
        addNewBtn.textContent = isNews ? '+ Add New Article' : '+ New Communication';
        
        if (!isNews) fetchComms();
    };

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
                recipientsEl.innerHTML = comm.recipients.map(r => `<div class="p-2 border-b">${r.full_name} &lt;${r.email}&gt;</div>`).join('');
            } else {
                countEl.textContent = '0';
                recipientsEl.innerHTML = '<p class="p-4 text-center text-gray-500">No recipients found.</p>';
            }
        }
    };

    const openNewsModal = async (articleId = null) => {
        newsForm.reset();
        currentImagePreview.innerHTML = '';
        quillNews.setContents([]); // FIX: Changed from tinymce to quill

        if (articleId) {
            newsModalTitle.textContent = 'Edit Article';
            const result = await apiCall(`${API.news.read_single}?id=${articleId}`);
            if (result.success) {
                const article = result.data;
                newsForm.querySelector('[name="id"]').value = article.id;
                newsForm.querySelector('[name="title"]').value = article.title;
                quillNews.root.innerHTML = article.content; // FIX: Changed from tinymce to quill
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
        
        commsModal.classList.remove('hidden');
    };

    const handleSaveNews = async (e) => {
        e.preventDefault();
        const formData = new FormData(newsForm);
        formData.set('content', quillNews.root.innerHTML); // Get HTML from Quill
        
        const url = formData.get('id') ? API.news.update : API.news.create;
        const result = await apiCall(url, { method: 'POST', body: formData });

        if (result.success) {
            showToast(result.message);
            newsModal.classList.add('hidden');
            fetchNews();
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
        
        const result = await apiCall(API.comms.create, { method: 'POST', body: JSON.stringify(payload) });
        if (result.success) {
            showToast(result.message);
            commsModal.classList.add('hidden');
            appState.communications = []; // Force refetch on next tab click
            fetchComms();
        }
    };
    
    const handleTableClick = (e) => {
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
                apiCall(url, { method: 'POST', body: JSON.stringify({ id }) }).then(result => {
                    if (result.success) {
                        showToast(result.message);
                        if(type === 'news') fetchNews();
                        else {
                            appState.communications = [];
                            fetchComms();
                        }
                    }
                });
            }
        }
    };

    // --- 7. EVENT LISTENERS ---
    const attachEventListeners = () => {
        tabNews.addEventListener('click', () => handleTabClick('news'));
        tabComms.addEventListener('click', () => handleTabClick('comms'));
        
        addNewBtn.addEventListener('click', () => {
            if (appState.activeTab === 'news') openNewsModal();
            else openCommsModal();
        });

        newsFiltersForm.addEventListener('submit', (e) => {
            e.preventDefault();
            fetchNews();
        });
        clearNewsFiltersBtn.addEventListener('click', () => {
            newsFiltersForm.reset();
            fetchNews();
        });
        
        newsForm.addEventListener('submit', handleSaveNews);
        commsForm.addEventListener('submit', handleSendComms);
        
        newsTableBody.addEventListener('click', handleTableClick);
        commsTableBody.addEventListener('click', handleTableClick);
        
        document.querySelectorAll('input[name="channel"]').forEach(radio => {
            radio.addEventListener('change', e => {
                commsSubjectWrapper.style.display = e.target.value === 'email' ? 'block' : 'none';
            });
        });

        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => btn.closest('.fixed').classList.add('hidden'));
        });
    };

    // --- 8. INITIALIZATION ---
    const initialize = () => {
        attachEventListeners();
        fetchNews(); // Initial page load always shows news
    };

    initialize();
});