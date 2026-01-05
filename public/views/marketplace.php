<?php
// /views/marketplace.php
require_once dirname(__DIR__, 2) . '/api/core/initialize.php'; // For auth
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Marketplace Management</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage orders, products, and inventory.</p>
                </div>
            </div>

            <div class="mt-6">
                <div class="sm:hidden">
                    <label for="tabs" class="sr-only">Select a tab</label>
                    <select id="tabs" name="tabs" class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option selected>Orders</option>
                        <option>Products</option>
                    </select>
                </div>
                <div class="hidden sm:block">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button id="tab-btn-orders" class="tab-btn border-indigo-500 text-indigo-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" aria-current="page">
                                Orders
                            </button>
                            <button id="tab-btn-products" class="tab-btn border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                                Products
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            <div id="tab-panel-orders" class="tab-panel mt-6">
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div class="sm:col-span-2">
                        <label for="orderStartDateFilter" class="block text-sm font-medium text-slate-700">Start Date</label>
                        <input type="date" id="orderStartDateFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="orderEndDateFilter" class="block text-sm font-medium text-slate-700">End Date</label>
                        <input type="date" id="orderEndDateFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2 flex items-end">
                        <button type="button" id="orderApplyDateFilter" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000] w-full sm:w-auto">Apply</button>
                        <button type="button" id="orderClearDateFilter" class="ml-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Clear</button>
                    </div>
                    <div class="sm:col-span-3">
                        <label for="orderSearchInput" class="block text-sm font-medium text-slate-700">Search</label>
                        <input type="text" id="orderSearchInput" placeholder="Search by Order ID, name, or email..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="orderStatusFilter" class="block text-sm font-medium text-slate-700">Status</label>
                        <select id="orderStatusFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <div id="orderTableContainer" class="overflow-x-auto"></div>
                    <div id="orderPaginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4"></div>
                </div>
            </div>

            <div id="tab-panel-products" class="tab-panel mt-6 hidden">
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div class="sm:col-span-3">
                        <label for="productSearchInput" class="block text-sm font-medium text-slate-700">Search Products</label>
                        <input type="text" id="productSearchInput" placeholder="Search by name or SKU..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="productStatusFilter" class="block text-sm font-medium text-slate-700">Status</label>
                        <select id="productStatusFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <?php if (has_permission('products_create')): // Assumes permission ?>
                    <div class="sm:col-span-1 flex items-end">
                        <button type="button" id="addProductBtn" class="block w-full rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Add Product</button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <div id="productTableContainer" class="overflow-x-auto"></div>
                    <div id="productPaginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4"></div>
                </div>
            </div>

        </div>
    </main>
</div>

<div id="viewOrderModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-4xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900" id="viewOrderTitle">Order Details</h3>
                <p class="text-sm text-gray-500" id="viewOrderDate"></p>
            </div>
            <button type="button" class="close-modal-btn text-2xl font-light text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1 space-y-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Customer</h4>
                    <p id="viewCustomerName" class="text-md font-semibold text-gray-900"></p>
                    <p id="viewCustomerEmail" class="text-sm text-gray-700"></p>
                    <p id="viewCustomerPhone" class="text-sm text-gray-700"></p>
                </div>
                 <div>
                    <h4 class="text-sm font-medium text-gray-500">Shipping Address</h4>
                    <p id="viewShippingAddress" class="text-sm text-gray-700 whitespace-pre-line"></p>
                </div>
            </div>
            <div class="md:col-span-2 space-y-6">
                <div>
                    <h4 class="text-lg font-medium text-gray-900">Order Summary</h4>
                    <dl class="mt-2 grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-3 rounded-lg"><dt class="text-sm font-medium text-gray-500">Total Amount</dt><dd class="text-lg font-semibold text-gray-900" id="viewOrderTotal"></dd></div>
                         <div class="bg-slate-50 p-3 rounded-lg"><dt class="text-sm font-medium text-gray-500">Amount Paid</dt><dd class="text-lg font-semibold text-green-700" id="viewOrderPaid"></dd></div>
                        <div class="bg-slate-50 p-3 rounded-lg"><dt class="text-sm font-medium text-gray-500">Balance Due</dt><dd class="text-lg font-semibold text-red-600" id="viewOrderBalance"></dd></div>
                        <div class="bg-slate-50 p-3 rounded-lg"><dt class="text-sm font-medium text-gray-500">Current Status</dt><dd class="text-lg font-semibold text-gray-900" id="viewOrderStatus"></dd></div>
                    </dl>
                </div>
                <div>
                    <h4 class="text-lg font-medium text-gray-900">Line Items</h4>
                    <div class="mt-2 border rounded-lg overflow-hidden"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th></tr></thead><tbody id="viewOrderItems" class="bg-white divide-y divide-gray-200"></tbody></table></div>
                </div>
                <?php if (has_permission('marketplace_orders_update')): ?>
                <div class="border-t pt-4">
                     <form id="updateStatusForm" class="flex items-end gap-4">
                        <input type="hidden" id="updateOrderId" name="order_id">
                        <div class="flex-grow"><label for="newOrderStatus" class="block text-sm font-medium text-slate-700">Update Status</label><select id="newOrderStatus" name="new_status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"><option value="pending">Pending</option><option value="processing">Processing</option><option value="shipped">Shipped</option><option value="delivered">Delivered</option><option value="cancelled">Cancelled</option></select></div>
                        <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Update</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t flex justify-end gap-x-3"><button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Close</button></div>
    </div>
</div>

<div id="productModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900" id="productModalTitle">Add New Product</h3>
        <form id="productForm" class="mt-4 space-y-5">
            <input type="hidden" id="productId" name="product_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label for="productName" class="block text-sm font-medium text-slate-700">Product Name</label><input type="text" id="productName" name="name" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
                <div><label for="productSku" class="block text-sm font-medium text-slate-700">Product SKU</label><input type="text" id="productSku" name="sku" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
            </div>
            <div>
                <label for="productDescription" class="block text-sm font-medium text-slate-700">Description</label>
                <textarea id="productDescription" name="description" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></textarea>
            </div>
            <div>
                <label for="productIsActive" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="productIsActive" name="is_active" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div>
                <label for="productImages" class="block text-sm font-medium text-slate-700">
                    Upload Product Images
                </label>
                <input type="file" id="productImages" name="images[]" multiple accept="image/*"
                    class="mt-1 block w-full text-sm text-slate-700 border border-slate-300 rounded-lg px-3 py-2">
                <div id="uploadPreviewContainer" class="mt-3 flex gap-3 flex-wrap"></div>
            </div>

            <div id="productImagePreview" class="border-t pt-4">
                <h4 class="text-md font-medium text-gray-800 mb-2">Product Images</h4>
                <div class="flex gap-3 flex-wrap" id="imagePreviewContainer">
                    <p class="text-gray-400 text-sm">No images available</p>
                </div>
            </div>


            <div id="initialVariantSection" class="space-y-4 border-t pt-4">
                <h4 class="text-md font-medium text-gray-800">Initial Product Variant</h4>
                <p class="text-sm text-gray-500">All products must have at least one variant (e.g., "Default") with a price and stock level.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label for="variantName" class="block text-sm font-medium text-slate-700">Variant Name</label><input type="text" id="variantName" name="variant_name" value="Default" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
                    <div><label for="variantSku" class="block text-sm font-medium text-slate-700">Variant SKU</label><input type="text" id="variantSku" name="variant_sku" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
                </div>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label for="variantPrice" class="block text-sm font-medium text-slate-700">Price (K)</label><input type="number" step="0.01" id="variantPrice" name="price" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
                    <div><label for="variantQuantity" class="block text-sm font-medium text-slate-700">Initial Stock</label><input type="number" id="variantQuantity" name="quantity" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></div>
                </div>
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Product</button>
            </div>
        </form>
    </div>
</div>

<div id="variantsModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-5xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start">
            <h3 class="text-lg font-semibold text-slate-900" id="variantsModalTitle">Manage Variants for...</h3>
            <button type="button" class="close-modal-btn text-2xl font-light text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        
        <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <?php if (has_permission('products_update')): // Assumes permission ?>
            <div class="lg:col-span-1">
                <h4 class="text-md font-medium text-gray-800">Add New Variant</h4>
                <form id="variantForm" class="mt-4 space-y-4 p-4 rounded-lg bg-gray-50 border">
                    <input type="hidden" id="variantProductId" name="product_id">
                    <div><label for="newVariantName" class="block text-sm font-medium text-slate-700">Variant Name</label><input type="text" id="newVariantName" name="name" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm"></div>
                    <div><label for="newVariantSku" class="block text-sm font-medium text-slate-700">Variant SKU</label><input type="text" id="newVariantSku" name="variant_sku" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm"></div>
                    <div><label for="newVariantPrice" class="block text-sm font-medium text-slate-700">Price (K)</label><input type="number" step="0.01" id="newVariantPrice" name="price" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm"></div>
                    <div><label for="newVariantQuantity" class="block text-sm font-medium text-slate-700">Initial Stock</label><input type="number" id="newVariantQuantity" name="quantity" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm"></div>
                    <div><label for="newVariantAttribute" class="block text-sm font-medium text-slate-700">Attribute (e.g., Size, Color)</label>
                        <div class="flex gap-2">
                             <input type="text" id="newVariantAttrKey" placeholder="e.g. Size" class="mt-1 block w-1/2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                             <input type="text" id="newVariantAttrValue" placeholder="e.g. Large" class="mt-1 block w-1/2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                        </div>
                    </div>
                    <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 w-full">Add Variant</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="lg:col-span-2">
                <h4 class="text-md font-medium text-gray-800">Existing Variants</h4>
                <div id="variantsTableContainer" class="mt-4 border rounded-lg overflow-x-auto">
                    </div>
            </div>
        </div>
        
    </div>
</div>

<div id="loading-spinner" class="hidden fixed inset-0 bg-opacity-75 flex items-center justify-center z-[100]">
    <div class="flex items-center justify-center space-x-2">
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.1s;"></div>
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.2s;"></div>
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.3s;"></div>
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.4s;"></div>
        <div class="wave-bar h-8 w-2 bg-[#E40000] rounded-full" style="animation-delay: 0.5s;"></div>
    </div>
</div>
<div id="toast" class="hidden fixed bottom-5 right-5 z-50 bg-gray-800 text-white py-2 px-4 rounded-lg shadow-md">
    <p id="toastMessage"></p>
</div>

<script src="/assets/js/marketplace.js"></script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>