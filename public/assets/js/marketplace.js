// /assets/js/marketplace.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- STATE MANAGEMENT ---
    let orderCurrentPage = 1;
    let orderCurrentFilters = { startDate: '', endDate: '', status: '', search: '' };
    let orderRecordsPerPage = 10;
    
    let productCurrentPage = 1;
    let productCurrentFilters = { status: '', search: '' };
    let productRecordsPerPage = 10;

    let currentManageProductId = null; // For the variants modal

    // --- DOM ELEMENTS ---
    // Tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    // Orders Tab
    const orderTableContainer = document.getElementById('orderTableContainer');
    const orderPaginationContainer = document.getElementById('orderPaginationContainer');
    const orderStartDateFilter = document.getElementById('orderStartDateFilter');
    const orderEndDateFilter = document.getElementById('orderEndDateFilter');
    const orderApplyDateFilter = document.getElementById('orderApplyDateFilter');
    const orderClearDateFilter = document.getElementById('orderClearDateFilter');
    const orderStatusFilter = document.getElementById('orderStatusFilter');
    const orderSearchInput = document.getElementById('orderSearchInput');

    // Products Tab
    const productTableContainer = document.getElementById('productTableContainer');
    const productPaginationContainer = document.getElementById('productPaginationContainer');
    const productStatusFilter = document.getElementById('productStatusFilter');
    const productSearchInput = document.getElementById('productSearchInput');
    const addProductBtn = document.getElementById('addProductBtn');

    // --- MODAL ELEMENTS ---
    const viewOrderModal = document.getElementById('viewOrderModal');
    const updateStatusForm = document.getElementById('updateStatusForm');

    const productModal = document.getElementById('productModal');
    const productForm = document.getElementById('productForm');
    const productModalTitle = document.getElementById('productModalTitle');
    const initialVariantSection = document.getElementById('initialVariantSection');

    const variantsModal = document.getElementById('variantsModal');
    const variantsModalTitle = document.getElementById('variantsModalTitle');
    const variantForm = document.getElementById('variantForm');
    const variantsTableContainer = document.getElementById('variantsTableContainer');

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
        if (isNaN(num)) return `${currency}0.00`;
        return `${currency}${num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;
    };

    const formatStatus = (status) => {
        if (!status) return '';
        return status.charAt(0).toUpperCase() + status.slice(1);
    };

    const getOrderStatusBadge = (status) => {
        switch (status) {
            case 'pending': return '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">Pending</span>';
            case 'processing': return '<span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">Processing</span>';
            case 'shipped': return '<span class="inline-flex items-center rounded-md bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-700">Shipped</span>';
            case 'delivered': return '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Delivered</span>';
            case 'cancelled': return '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Cancelled</span>';
            default: return `<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">${formatStatus(status)}</span>`;
        }
    };
    
    const getProductStatusBadge = (status) => {
        return status == 1
            ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Active</span>'
            : '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Inactive</span>';
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
            
        // Attach event listeners
        container.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const pageToGo = parseInt(e.currentTarget.dataset.page);
                if (pageToGo !== currentPage) {
                    pageChangeCallback(pageToGo);
                }
            });
        });
    };

    // --- TAB SWITCHING LOGIC ---
    const switchTab = (targetTab) => {
        tabButtons.forEach(btn => {
            const isTarget = `tab-btn-${targetTab}` === btn.id;
            btn.classList.toggle('border-indigo-500', isTarget);
            btn.classList.toggle('text-indigo-600', isTarget);
            btn.classList.toggle('border-transparent', !isTarget);
            btn.classList.toggle('text-gray-500', !isTarget);
            btn.classList.toggle('hover:border-gray-300', !isTarget);
            btn.classList.toggle('hover:text-gray-700', !isTarget);
            btn.setAttribute('aria-current', isTarget ? 'page' : 'false');
        });
        
        tabPanels.forEach(panel => {
            panel.classList.toggle('hidden', `tab-panel-${targetTab}` !== panel.id);
        });
        
        // Load data for the activated tab
        if (targetTab === 'orders') {
            loadOrders();
        } else if (targetTab === 'products') {
            loadProducts();
        }
    };


    // =================================================================
    // ORDERS TAB LOGIC
    // =================================================================

    const loadOrders = async () => {
        showSpinner();
        const params = new URLSearchParams({
            page: orderCurrentPage,
            limit: orderRecordsPerPage,
            ...orderCurrentFilters
        }).toString();

        try {
            const response = await fetch(`/api/marketplace/read.php?${params}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();

            if (data.success) {
                renderOrdersTable(data.orders);
                renderPagination(orderPaginationContainer, data.total_records, orderRecordsPerPage, orderCurrentPage, (newPage) => {
                    orderCurrentPage = newPage;
                    loadOrders();
                });
            } else {
                throw new Error(data.message || 'Invalid data structure.');
            }
        } catch (error) {
            console.error("Fetch error:", error);
            showToast(`Failed to load orders. ${error.message}`, true);
            orderTableContainer.innerHTML = `<div class="p-8 text-center text-red-500">Failed to load orders. ${error.message}</div>`;
            orderPaginationContainer.innerHTML = '';
        } finally {
            hideSpinner();
        }
    };

    const renderOrdersTable = (orders) => {
        let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">`;

        if (!orders || orders.length === 0) {
            tableHtml += `<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No orders found matching your criteria.</td></tr>`;
        } else {
            orders.forEach(o => {
                tableHtml += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#${o.id}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="font-medium text-gray-800">${o.customer_name || 'N/A'}</div>
                            <div class="text-gray-500">${o.customer_email || ''}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(o.created_at).toLocaleString()}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${formatCurrency(o.total_amount)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${getOrderStatusBadge(o.status)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" class="view-order-btn text-indigo-600 hover:text-indigo-900" data-id="${o.id}">View</button>
                        </td>
                    </tr>`;
            });
        }
        tableHtml += `</tbody></table>`;
        orderTableContainer.innerHTML = tableHtml;
    };

    const openViewOrderModal = async (id) => {
        showSpinner();
        try {
            const response = await fetch(`/api/orders/read_single.php?id=${id}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Could not load order details.');

            const order = data.order;
            const items = data.items;

            viewOrderModal.querySelector('#viewOrderTitle').textContent = `Order Details #${order.id}`;
            viewOrderModal.querySelector('#viewOrderDate').textContent = `Placed on ${new Date(order.created_at).toLocaleString()}`;
            viewOrderModal.querySelector('#viewCustomerName').textContent = order.customer_name || 'N/A';
            viewOrderModal.querySelector('#viewCustomerEmail').textContent = order.customer_email || 'N/A';
            viewOrderModal.querySelector('#viewCustomerPhone').textContent = order.customer_phone || 'N/A';
            viewOrderModal.querySelector('#viewShippingAddress').textContent = order.shipping_address || 'No address provided.';
            viewOrderModal.querySelector('#viewOrderTotal').textContent = formatCurrency(order.total_amount);
            viewOrderModal.querySelector('#viewOrderPaid').textContent = formatCurrency(order.paid_amount);
            viewOrderModal.querySelector('#viewOrderBalance').textContent = formatCurrency(order.balance_due);
            viewOrderModal.querySelector('#viewOrderStatus').innerHTML = getOrderStatusBadge(order.status);
            
            if (updateStatusForm) {
                viewOrderModal.querySelector('#updateOrderId').value = order.id;
                viewOrderModal.querySelector('#newOrderStatus').value = order.status;
            }

            const itemsTbody = viewOrderModal.querySelector('#viewOrderItems');
            itemsTbody.innerHTML = '';
            if (items && items.length > 0) {
                items.forEach(item => {
                    itemsTbody.innerHTML += `
                        <tr class="text-sm">
                            <td class="px-4 py-3 text-gray-800"><div>${item.product_name || 'N/A'}</div><div class="text-xs text-gray-500">${item.variant_name || 'Base Product'}</div></td>
                            <td class="px-4 py-3 text-gray-500">${item.variant_sku || 'N/A'}</td>
                            <td class="px-4 py-3 text-gray-800 text-right">${item.quantity}</td>
                            <td class="px-4 py-3 text-gray-800 text-right">${formatCurrency(item.unit_price)}</td>
                            <td class="px-4 py-3 text-gray-900 font-medium text-right">${formatCurrency(item.total_price)}</td>
                        </tr>`;
                });
            } else {
                itemsTbody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">No items found for this order.</td></tr>';
            }
            
            viewOrderModal.classList.remove('hidden');

        } catch (error) {
            console.error("Error opening view modal:", error);
            showToast(`Error fetching order details: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    };

    // =================================================================
    // PRODUCTS TAB LOGIC
    // =================================================================

    const loadProducts = async () => {
        showSpinner();
        const params = new URLSearchParams({
            page: productCurrentPage,
            limit: productRecordsPerPage,
            ...productCurrentFilters
        }).toString();

        try {
            const response = await fetch(`/api/products/read.php?${params}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();

            if (data.success) {
                renderProductsTable(data.products);
                renderPagination(productPaginationContainer, data.total_records, productRecordsPerPage, productCurrentPage, (newPage) => {
                    productCurrentPage = newPage;
                    loadProducts();
                });
            } else {
                throw new Error(data.message || 'Invalid data structure.');
            }
        } catch (error) {
            console.error("Fetch error:", error);
            showToast(`Failed to load products. ${error.message}`, true);
            productTableContainer.innerHTML = `<div class="p-8 text-center text-red-500">Failed to load products. ${error.message}</div>`;
            productPaginationContainer.innerHTML = '';
        } finally {
            hideSpinner();
        }
    };

    const renderProductsTable = (products) => {
        let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Variants</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Stock</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">`;

        if (!products || products.length === 0) {
            tableHtml += `<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No products found.</td></tr>`;
        } else {
            products.forEach(p => {
                tableHtml += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap flex items-center gap-3">
                            ${p.image_url 
                                ? `<img src="${p.image_url}" alt="${p.name}" class="w-10 h-10 rounded object-cover border">` 
                                : `<div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center text-gray-400 text-xs">N/A</div>`
                            }
                            <span class="text-sm font-medium text-gray-900">${p.name}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${p.sku || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${p.variant_count || 0}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${p.total_stock || 0}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${getProductStatusBadge(p.is_active)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" class="manage-variants-btn text-green-600 hover:text-green-900" data-id="${p.id}" data-name="${p.name}">Manage Variants</button>
                            <button type="button" class="edit-product-btn text-indigo-600 hover:text-indigo-900 ml-4" data-id="${p.id}">Edit</button>
                        </td>
                    </tr>`;
                    console.log('images', p.image_url);
            });
        }
        tableHtml += `</tbody></table>`;
        productTableContainer.innerHTML = tableHtml;
    };

    const openAddProductModal = () => {
        productForm.reset();
        document.getElementById('productId').value = '';
        productModalTitle.textContent = 'Add New Product';
        initialVariantSection.style.display = 'block'; // Show variant section
        // Make initial variant fields required
        document.getElementById('variantName').required = true;
        document.getElementById('variantPrice').required = true;
        document.getElementById('variantQuantity').required = true;
        productModal.classList.remove('hidden');

        
    };

    const openEditProductModal = async (id) => {
        showSpinner();
        try {
            const response = await fetch(`/api/products/read_single.php?id=${id}`);
            if (!response.ok) throw new Error('Failed to fetch product data.');
            const data = await response.json();
            if (!data.success) throw new Error(data.message);

            productForm.reset();
            document.getElementById('productId').value = data.product.id;
            document.getElementById('productName').value = data.product.name;
            document.getElementById('productSku').value = data.product.sku;
            document.getElementById('productDescription').value = data.product.description;
            document.getElementById('productIsActive').value = data.product.is_active;
            
            productModalTitle.textContent = 'Edit Product';
            initialVariantSection.style.display = 'none'; // Hide variant section
            // Make initial variant fields not required
            document.getElementById('variantName').required = false;
            document.getElementById('variantPrice').required = false;
            document.getElementById('variantQuantity').required = false;
            const imagePreviewContainer = document.getElementById('productImagePreview');
            if(imagePreviewContainer){
                if(data.images && data.images.length > 0){
                    imagePreviewContainer.innerHTML = data.images
                    .map(img => `
                        <div class="relative w-20 h-20">
                            <img src="${img.image_url}" alt="Product Image" class="w-full h-full object-cover rounded-md border">
                        </div>
                    `)
                    .join('');
                } else{
                     imagePreviewContainer.innerHTML = `<p class="text-gray-400 text-sm">No images available</p>`;
                }

            }
            
            productModal.classList.remove('hidden');
        } catch (error) {
            showToast(error.message, true);
        } finally {
            hideSpinner();
        }
    };
    
    const openManageVariantsModal = async (productId, productName) => {
        currentManageProductId = productId;
        variantsModalTitle.textContent = `Manage Variants for: ${productName}`;
        document.getElementById('variantProductId').value = productId;
        variantForm.reset();
        variantsTableContainer.innerHTML = '<p class="p-4 text-center">Loading variants...</p>';
        variantsModal.classList.remove('hidden');
        
        await loadVariants(productId);
    };

    const loadVariants = async (productId) => {
        if (!productId) return;
        
        try {
            const response = await fetch(`/api/products/read_variants.php?product_id=${productId}`);
            if (!response.ok) throw new Error('Failed to fetch variants.');
            const data = await response.json();
            if (!data.success) throw new Error(data.message);
            
            renderVariantsTable(data.variants);
        } catch (error) {
            showToast(error.message, true);
            variantsTableContainer.innerHTML = `<p class="p-4 text-center text-red-500">${error.message}</p>`;
        }
    };
    
    const renderVariantsTable = (variants) => {
         let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">`;
            
        if (!variants || variants.length === 0) {
            tableHtml += `<tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No variants found for this product.</td></tr>`;
        } else {
            variants.forEach(v => {
                tableHtml += `
                    <tr class="text-sm">
                        <td class="px-4 py-2 text-gray-900">${v.name}</td>
                        <td class="px-4 py-2 text-gray-500">${v.variant_sku || 'N/A'}</td>
                        <td class="px-4 py-2 text-gray-800"><input type="number" step="0.01" class="variant-price-input w-24 rounded-md border-gray-300 shadow-sm text-sm" data-id="${v.id}" value="${parseFloat(v.price).toFixed(2)}"></td>
                        <td class="px-4 py-2 text-gray-800"><input type="number" class="variant-stock-input w-20 rounded-md border-gray-300 shadow-sm text-sm" data-id="${v.id}" value="${v.quantity}"></td>
                        <td class="px-4 py-2 text-gray-800"><button class="update-variant-btn rounded bg-indigo-600 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500" data-id="${v.id}">Save</button></td>
                    </tr>
                `;
            });
        }
        tableHtml += `</tbody></table>`;
        variantsTableContainer.innerHTML = tableHtml;
    };


    // =================================================================
    // EVENT LISTENERS
    // =================================================================
    
    // Tab Switching
    tabButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetTab = e.currentTarget.id.replace('tab-btn-', '');
            switchTab(targetTab);
        });
    });

    // --- Order Tab Listeners ---
    orderApplyDateFilter.addEventListener('click', () => {
        orderCurrentFilters.startDate = orderStartDateFilter.value;
        orderCurrentFilters.endDate = orderEndDateFilter.value;
        orderCurrentPage = 1;
        loadOrders();
    });

    orderClearDateFilter.addEventListener('click', () => {
        orderStartDateFilter.value = '';
        orderEndDateFilter.value = '';
        orderCurrentFilters.startDate = '';
        orderCurrentFilters.endDate = '';
        orderCurrentPage = 1;
        loadOrders();
    });

    orderStatusFilter.addEventListener('change', () => {
        orderCurrentFilters.status = orderStatusFilter.value;
        orderCurrentPage = 1;
        loadOrders();
    });

    let orderDebounceTimer;
    orderSearchInput.addEventListener('input', () => {
        clearTimeout(orderDebounceTimer);
        orderDebounceTimer = setTimeout(() => {
            orderCurrentPage = 1;
            orderCurrentFilters.search = orderSearchInput.value;
            loadOrders();
        }, 350);
    });

    orderTableContainer.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.view-order-btn');
        if (viewBtn) {
            openViewOrderModal(viewBtn.dataset.id);
        }
    });
    
    viewOrderModal.addEventListener('click', (e) => {
        if (e.target.closest('.close-modal-btn') || e.target === viewOrderModal) {
            viewOrderModal.classList.add('hidden');
        }
    });

    updateStatusForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        showSpinner();
        const formData = new FormData(updateStatusForm);
        const data = Object.fromEntries(formData.entries());
        try {
            const response = await fetch('/api/orders/update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message);
                viewOrderModal.querySelector('#viewOrderStatus').innerHTML = getOrderStatusBadge(data.new_status);
                loadOrders(); // Reload table
            } else { throw new Error(result.message); }
        } catch (error) {
            showToast(`Error: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    });


    // --- Product Tab Listeners ---
    productStatusFilter.addEventListener('change', () => {
        productCurrentFilters.status = productStatusFilter.value;
        productCurrentPage = 1;
        loadProducts();
    });

    let productDebounceTimer;
    productSearchInput.addEventListener('input', () => {
        clearTimeout(productDebounceTimer);
        productDebounceTimer = setTimeout(() => {
            productCurrentPage = 1;
            productCurrentFilters.search = productSearchInput.value;
            loadProducts();
        }, 350);
    });
    
    addProductBtn?.addEventListener('click', openAddProductModal);
    
    productTableContainer.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-product-btn');
        if (editBtn) {
            openEditProductModal(editBtn.dataset.id);
            return;
        }
        const variantsBtn = e.target.closest('.manage-variants-btn');
        if (variantsBtn) {
            openManageVariantsModal(variantsBtn.dataset.id, variantsBtn.dataset.name);
            return;
        }
    });
    
    productModal.addEventListener('click', (e) => {
        if (e.target.closest('.close-modal-btn') || e.target === productModal) {
            productModal.classList.add('hidden');
        }
    });

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showSpinner();
        const formData = new FormData(productForm);
        const data = Object.fromEntries(formData.entries());
        const url = data.product_id ? '/api/products/update.php' : '/api/products/create.php';

        try {
            //upload image
            let uploadedImages = [];
            if(selectedFiles && selectedFiles.length > 0){
                const uploadFormData = new FormData();
                selectedFiles.forEach((file) => uploadFormData.append('images[]', file));

                const uploadResponse = await fetch('/api/utility/product_upload.php', {
                    method: 'POST',
                    body: uploadFormData
                });

                const uploadResult = await uploadResponse.json();
                if (!uploadResult.success) {
                    throw new Error(uploadResult.message || 'Image upload failed');
                }

                // Store uploaded file paths in the product data
                uploadedImages = uploadResult.filePaths; // array of paths like ["product/tshirt_blue.jpg"]
                data.image_urls = uploadedImages;
            }

            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message);
                productModal.classList.add('hidden');
                loadProducts();
            } else { throw new Error(result.message); }
        } catch (error) {
            showToast(`Error: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    });
    
    // --- Variants Modal Listeners ---
    variantsModal.addEventListener('click', (e) => {
        if (e.target.closest('.close-modal-btn') || e.target === variantsModal) {
            variantsModal.classList.add('hidden');
            loadProducts(); // Refresh product table in background to update stock counts
        }
    });
    
    variantForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showSpinner();
        
        const attributes = {};
        const attrKey = document.getElementById('newVariantAttrKey').value.trim();
        const attrValue = document.getElementById('newVariantAttrValue').value.trim();
        if (attrKey && attrValue) {
            attributes[attrKey] = attrValue;
        }

        const data = {
            product_id: document.getElementById('variantProductId').value,
            name: document.getElementById('newVariantName').value,
            variant_sku: document.getElementById('newVariantSku').value,
            price: document.getElementById('newVariantPrice').value,
            quantity: document.getElementById('newVariantQuantity').value,
            attributes: attributes
        };

        try {
            const response = await fetch('/api/products/create_variant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message);
                variantForm.reset();
                await loadVariants(data.product_id); // Reload list
            } else { throw new Error(result.message); }
        } catch (error) {
            showToast(`Error: ${error.message}`, true);
        } finally {
            hideSpinner();
        }
    });
    
    variantsTableContainer.addEventListener('click', async (e) => {
        const updateBtn = e.target.closest('.update-variant-btn');
        if (updateBtn) {
            const variantId = updateBtn.dataset.id;
            const row = updateBtn.closest('tr');
            const price = row.querySelector('.variant-price-input').value;
            const quantity = row.querySelector('.variant-stock-input').value;
            
            showSpinner();
            try {
                 const response = await fetch('/api/products/update_variant.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ variant_id: variantId, price: price, quantity: quantity })
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message);
                } else { throw new Error(result.message); }
            } catch (error) {
                showToast(`Error: ${error.message}`, true);
            } finally {
                hideSpinner();
            }
        }
    });

    const imageInput = document.getElementById('productImages');
    const previewContainer = document.getElementById('uploadPreviewContainer');
    let selectedFiles = [];

    imageInput?.addEventListener('change', (e) => {
        selectedFiles = Array.from(e.target.files);
        previewContainer.innerHTML = '';
        selectedFiles.forEach(file => {
            const reader = new FileReader();
            const img = document.createElement('img');
            img.classList.add('w-20', 'h-20', 'rounded', 'object-cover', 'border');
            reader.onload = () => img.src = reader.result;
            reader.readAsDataURL(file);
            previewContainer.appendChild(img);
        });
    });


    // --- INITIALIZATION ---
    switchTab('orders'); // Load the first tab
});