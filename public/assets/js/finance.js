// /assets/js/finance.js

document.addEventListener('DOMContentLoaded', function() {
    // --- STATE MANAGEMENT ---
    let currentPage = 1;
    let currentFilters = {
        startDate: '',
        endDate: '',
        paymentType: '',
        method: '',
        status: '',
        search: ''
    };
    const recordsPerPage = 10;
    
    // Chart instances
    let revenueChart = null;
    let typeChart = null;

    // --- DOM ELEMENTS ---
    const tableContainer = document.getElementById('paymentsTableContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    
    // Global Filters
    const startDateFilter = document.getElementById('startDateFilter');
    const endDateFilter = document.getElementById('endDateFilter');
    const applyDateFilter = document.getElementById('applyDateFilter');
    const clearDateFilter = document.getElementById('clearDateFilter');

    // Table Filters
    const typeFilter = document.getElementById('typeFilter');
    const methodFilter = document.getElementById('methodFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');

    // KPI Elements
    const kpiTotalRevenue = document.getElementById('kpiTotalRevenue');
    const kpiOutstandingRevenue = document.getElementById('kpiOutstandingRevenue');
    const kpiTotalTransactions = document.getElementById('kpiTotalTransactions');
    const kpiPendingTransactions = document.getElementById('kpiPendingTransactions');
    
    // Chart Canvases
    const revenueCtx = document.getElementById('revenueOverTimeChart')?.getContext('2d');
    const typeCtx = document.getElementById('revenueByTypeChart')?.getContext('2d');

    // --- UI ELEMENTS ---
    const spinner = document.getElementById('loading-spinner');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    // --- HELPER FUNCTIONS ---
    const showSpinner = () => spinner.classList.remove('hidden');
    const hideSpinner = () => spinner.classList.add('hidden');

    const showToast = (message, isError = false) => {
        toastMessage.textContent = message;
        toast.className = `fixed bottom-5 right-5 text-white py-2 px-4 rounded-lg shadow-md z-50 ${isError ? 'bg-red-600' : 'bg-green-700'}`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    };

    const formatCurrency = (amount, currency = 'K') => {
        const num = parseFloat(amount);
        if (isNaN(num)) {
            return `${currency}0.00`;
        }
        return `${currency}${num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;
    };

    // --- API & RENDERING ---
    const loadData = async () => {
        showSpinner();
        
        // Only add table-level filters to the params for pagination
        const tableFilters = { 
            paymentType: currentFilters.paymentType,
            method: currentFilters.method,
            status: currentFilters.status,
            search: currentFilters.search
        };

        const params = new URLSearchParams({ 
            page: currentPage, 
            limit: recordsPerPage,
            startDate: currentFilters.startDate, // Global filters
            endDate: currentFilters.endDate,     // Global filters
            ...tableFilters
        }).toString();
        
        try {
            const response = await fetch(`/api/finance/read.php?${params}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();

            if (data.success) {
                 renderSummary(data.summary);
                 renderCharts(data.charts);
                 renderTable(data.payments);
                 renderPagination(data.total_records, recordsPerPage, currentPage);
            } else {
                 throw new Error(data.message || 'Invalid data structure received from API.');
            }
        } catch (error) {
            console.error("Fetch error:", error);
            showToast(`Failed to load financial data: ${error.message}`, true);
            tableContainer.innerHTML = `<div class="p-8 text-center text-red-500">Failed to load transactions. ${error.message}</div>`;
            paginationContainer.innerHTML = '';
        } finally {
            hideSpinner();
        }
    };
    
    const renderSummary = (summary) => {
        kpiTotalRevenue.textContent = formatCurrency(summary.totalRevenue);
        kpiOutstandingRevenue.textContent = formatCurrency(summary.outstandingRevenue);
        kpiTotalTransactions.textContent = parseInt(summary.totalTransactions, 10).toLocaleString();
        kpiPendingTransactions.textContent = parseInt(summary.pendingTransactions, 10).toLocaleString();
    };

    const renderCharts = (charts) => {
        // --- Revenue Over Time Chart (Line) ---
        if (revenueChart) {
            revenueChart.destroy();
        }
        if (revenueCtx) {
            const timeData = charts.revenueOverTime || [];
            revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Revenue',
                        data: timeData.map(item => ({ x: new Date(item.day), y: item.total })),
                        borderColor: '#E40000',
                        backgroundColor: '#E4000033',
                        fill: true,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day',
                                tooltipFormat: 'MMM d, yyyy'
                            },
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Revenue (K)'
                            },
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) => `Revenue: ${formatCurrency(context.parsed.y)}`
                            }
                        }
                    }
                }
            });
        }

        // --- Revenue By Type Chart (Doughnut) ---
        if (typeChart) {
            typeChart.destroy();
        }
        if (typeCtx) {
            const typeData = charts.revenueByType || [];
            const labels = typeData.map(item => (item.payment_type || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
            const data = typeData.map(item => item.total);
            
            typeChart = new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        backgroundColor: ['#E40000', '#1D4ED8', '#10B981', '#F59E0B', '#6366F1'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.label}: ${formatCurrency(context.parsed)}`
                            }
                        }
                    }
                }
            });
        }
    };

    const renderTable = (payments) => {
        let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payer</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type / Method</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">`;

        if (!payments || payments.length === 0) {
            tableHtml += `<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No payments found matching your criteria.</td></tr>`;
        } else {
            payments.forEach(p => {
                let statusBadge;
                switch(p.status) {
                    case 'completed':
                        statusBadge = '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Completed</span>';
                        break;
                    case 'pending':
                        statusBadge = '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">Pending</span>';
                        break;
                    case 'failed':
                        statusBadge = '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Failed</span>';
                        break;
                    case 'refunded':
                        statusBadge = '<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">Refunded</span>';
                        break;
                    default:
                        statusBadge = `<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">${p.status}</span>`;
                }

                const payerName = p.user_name || p.company_name || 'N/A';
                const payerLink = p.user_id ? `/users/view.php?id=${p.user_id}` : (p.company_id ? `/companies/view.php?id=${p.company_id}` : '#');
                const typeLabel = (p.payment_type || 'N/A').replace(/_/g, ' ');
                const methodLabel = (p.method || 'N/A').replace(/_/g, ' ');

                tableHtml += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(p.transaction_date).toLocaleString()}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ${p.user_id || p.company_id ? `<a href="${payerLink}" class="text-indigo-600 hover:underline">${payerName}</a>` : payerName}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="font-medium text-gray-800 capitalize">${typeLabel}</div>
                            <div class="text-gray-500 capitalize">${methodLabel}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${formatCurrency(p.amount)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            ${p.receipt_id ? `<a href="/api/receipts/view.php?id=${p.receipt_id}" target="_blank" class="text-indigo-600 hover:text-indigo-900">View Receipt</a>` : ''}
                        </td>
                    </tr>`;
            });
        }
        tableHtml += `</tbody></table>`;
        tableContainer.innerHTML = tableHtml;
    };

    const renderPagination = (total, limit, currentPage) => {
        // This function is identical to the one in companies.js
        // Paste your existing, enhanced renderPagination function here
        
        paginationContainer.innerHTML = ''; // Clear previous pagination
        if (!total || total <= 0 || !limit || limit <= 0) return; // No need for pagination

        const totalPages = Math.ceil(total / limit);
        if (totalPages <= 1) return; // No pagination needed if only one page

        const maxVisiblePages = 5; // How many page number links to show (e.g., 1 ... 4 5 6 ... 10)
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

        // Previous Button
        pageButtonsHtml += `
            <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
            </button>`;

        // Page Number Buttons
        if (startPage > 1) {
            pageButtonsHtml += `<button data-page="1" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">1</button>`;
            if (startPage > 2) {
                pageButtonsHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const isCurrent = i === currentPage;
            pageButtonsHtml += `
                <button data-page="${i}" ${isCurrent ? 'aria-current="page"' : ''} class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ${isCurrent ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'}">
                    ${i}
                </button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pageButtonsHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>`;
            }
            pageButtonsHtml += `<button data-page="${totalPages}" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">${totalPages}</button>`;
        }

        // Next Button
        pageButtonsHtml += `
            <button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
            </button>`;

        // Assemble final pagination HTML
        paginationContainer.innerHTML = `
            <div class="flex flex-1 justify-between sm:hidden">
                <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>${resultsText}</div>
                <div><nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    ${pageButtonsHtml}
                </nav></div>
            </div>`;
    };

    // --- EVENT LISTENERS ---
    const setupEventListeners = () => {
        
        // Global Filters
        applyDateFilter.addEventListener('click', () => {
            currentFilters.startDate = startDateFilter.value;
            currentFilters.endDate = endDateFilter.value;
            currentPage = 1; // Reset page
            loadData();
        });

        clearDateFilter.addEventListener('click', () => {
            startDateFilter.value = '';
            endDateFilter.value = '';
            currentFilters.startDate = '';
            currentFilters.endDate = '';
            currentPage = 1; // Reset page
            loadData();
        });

        // Table Filters
        [typeFilter, methodFilter, statusFilter].forEach(filter => {
            filter.addEventListener('change', () => {
                currentFilters.paymentType = typeFilter.value;
                currentFilters.method = methodFilter.value;
                currentFilters.status = statusFilter.value;
                currentPage = 1; // Reset page
                loadData();
            });
        });

        // Debounced search
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentPage = 1; // Reset page on new search
                currentFilters.search = searchInput.value;
                loadData();
            }, 350);
        });

        // Pagination
        paginationContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-page]');
            if (btn && !btn.disabled) {
                const pageToGo = parseInt(btn.dataset.page);
                if (pageToGo !== currentPage) {
                    currentPage = pageToGo;
                    loadData();
                }
            }
        });
    };

    // --- INITIALIZATION ---
    setupEventListeners();
    loadData(); // Initial load
});