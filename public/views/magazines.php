<?php
// /views/magazines.php
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
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Magazine Management</h1>
                    <p class="mt-2 text-sm text-gray-700">Upload, manage, and track magazines for the mobile app.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <button type="button" id="addMagazineBtn" class="block rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Add Magazine</button>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                <div class="sm:col-span-4">
                    <label for="searchInput" class="block text-sm font-medium text-slate-700">Search Magazine</label>
                    <input type="text" id="searchInput" placeholder="Search by title..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
            </div>

            <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div id="magazineTableContainer" class="overflow-x-auto">
                    </div>
                <div id="paginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                    </div>
            </div>
        </div>
    </main>
</div>

<div id="magazineModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900" id="magazineModalTitle">Add New Magazine</h3>
        <form id="magazineForm" class="mt-4 space-y-5" enctype="multipart/form-data">
            <input type="hidden" id="magazineId" name="id">
            
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700">Title</label>
                <input type="text" id="title" name="title" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none"></textarea>
            </div>
            
            <div>
                <label for="cover_image" class="block text-sm font-medium text-slate-700">Cover Image (.jpg, .png, .webp)</label>
                <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <div id="current-cover-preview" class="mt-2"></div>
            </div>
            
            <div>
                <label for="magazine_file" class="block text-sm font-medium text-slate-700">Magazine File (.pdf, .epub)</label>
                <input type="file" id="magazine_file" name="magazine_file" accept=".pdf,.epub" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <div id="current-file-preview" class="mt-2"></div>
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Magazine</button>
            </div>
        </form>
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

<script src="/assets/js/magazines.js"></script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>