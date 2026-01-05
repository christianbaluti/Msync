<?php
// /views/news.php
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
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">Content & Communications</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage public news articles and direct communications to members.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <button type="button" id="addNewBtn" class="block rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">
                        + Add New Article
                    </button>
                </div>
            </div>

            <div class="mt-6">
                <div class="sm:hidden">
                    <label for="tabs" class="sr-only">Select a tab</label>
                    <select id="tabs" name="tabs" class="block w-full rounded-md border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="news" selected>News Articles</option>
                        <option value="comms">Communications</option>
                    </select>
                </div>
                <div class="hidden sm:block">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button id="tab-btn-news" class="tab-btn border-indigo-500 text-indigo-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" aria-current="page">
                                News Articles
                            </button>
                            <button id="tab-btn-comms" class="tab-btn border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                                Communications
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            <div id="tab-panel-news" class="tab-panel mt-6">
                <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-4 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div class="sm:col-span-2">
                        <label for="newsSearchInput" class="block text-sm font-medium text-slate-700">Search Title</label>
                        <input type="text" id="newsSearchInput" placeholder="Search by article title..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                    <div>
                        <label for="newsDateFilter" class="block text-sm font-medium text-slate-700">Date</label>
                        <input type="date" id="newsDateFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                    <div>
                        <label for="newsStatusFilter" class="block text-sm font-medium text-slate-700">Status</label>
                        <select id="newsStatusFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            <option value="">All</option>
                            <option value="published">Published</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>
                </div>
                <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <div id="newsTableContainer" class="overflow-x-auto">
                        </div>
                    <div id="newsPaginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                        </div>
                </div>
            </div>

            <div id="tab-panel-comms" class="tab-panel mt-6 hidden">
                <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-4 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div class="sm:col-span-2">
                        <label for="commsSearchInput" class="block text-sm font-medium text-slate-700">Search Subject</label>
                        <input type="text" id="commsSearchInput" placeholder="Search by subject..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                    <div>
                        <label for="commsChannelFilter" class="block text-sm font-medium text-slate-700">Channel</label>
                        <select id="commsChannelFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            <option value="">All</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                        </select>
                    </div>
                </div>
                <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <div id="commsTableContainer" class="overflow-x-auto">
                        </div>
                    <div id="commsPaginationContainer" class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                        </div>
                </div>
            </div>
            
        </div>
    </main>
</div>

<div id="newsModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <h3 id="newsModalTitle" class="text-lg font-semibold text-slate-900">Add New Article</h3>
        <form id="newsForm" class="mt-4 space-y-5 max-h-[75vh] overflow-y-auto pr-2">
            <input type="hidden" name="id">
            <div>
                <label for="news-title" class="block text-sm font-medium text-slate-700">Title</label>
                <input type="text" name="title" id="news-title" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Content</label>
                <div id="news-editor" class="mt-1 h-60 bg-white border border-slate-300 rounded-lg"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="news-image" class="block text-sm font-medium text-slate-700">Featured Image</label>
                    <input type="file" name="media" id="news-image" accept="image/*" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <div id="current-image-preview" class="mt-2"></div>
                </div>
                <div>
                    <label for="news-scheduled-date" class="block text-sm font-medium text-slate-700">Scheduled Publish Date (Optional)</label>
                    <input type="datetime-local" name="scheduled_date" id="news-scheduled-date" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to publish immediately.</p>
                </div>
            </div>
        </form>
        <div class="mt-6 flex justify-end gap-x-3 border-t pt-4">
            <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
            <button type="submit" form="newsForm" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Article</button>
        </div>
    </div>
</div>

<div id="commsModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900">New Communication</h3>
        <form id="commsForm" class="mt-4 space-y-5 max-h-[75vh] overflow-y-auto pr-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Channel</label>
                <div class="mt-1 flex gap-x-4">
                    <label class="flex items-center"><input type="radio" name="channel" value="email" checked class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"> <span class="ml-2 text-sm">Email</span></label>
                    <label class="flex items-center"><input type="radio" name="channel" value="whatsapp" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"> <span class="ml-2 text-sm">WhatsApp</span></label>
                </div>
            </div>
            <div>
                <label for="comms-recipients" class="block text-sm font-medium text-slate-700">Recipients</label>
                <select name="recipients[]" id="comms-recipients" multiple required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none h-32"></select>
                <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple options.</p>
            </div>
            <div id="comms-subject-wrapper">
                <label for="comms-subject" class="block text-sm font-medium text-slate-700">Subject</label>
                <input type="text" name="subject" id="comms-subject" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Body</label>
                <div id="comms-editor" class="mt-1 h-60 bg-white border border-slate-300 rounded-lg"></div>
            </div>
        </form>
        <div class="mt-6 flex justify-end gap-x-3 border-t pt-4">
            <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
            <button type="submit" form="commsForm" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Send Communication</button>
        </div>
    </div>
</div>

<div id="viewNewsModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start mb-4">
            <h3 id="viewNewsTitle" class="text-xl font-bold text-gray-900">Article Title</h3>
            <button type="button" class="close-modal-btn text-2xl font-light text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div id="viewNewsContent" class="prose max-w-none max-h-[70vh] overflow-y-auto border-t pt-4">
            </div>
    </div>
</div>

<div id="viewCommsModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-xl rounded-xl bg-white">
        <div class="flex justify-between items-start mb-4">
            <h3 id="viewCommsTitle" class="text-xl font-bold text-gray-900">Communication Details</h3>
            <button type="button" class="close-modal-btn text-2xl font-light text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="space-y-4 max-h-[70vh] overflow-y-auto border-t pt-4">
            <div id="viewCommsBody" class="prose max-w-none p-4 border rounded-md bg-gray-50"></div>
            <div>
                <h4 class="text-md font-semibold text-gray-800">Recipients (<span id="recipient-count">0</span>)</h4>
                <div id="viewCommsRecipients" class="mt-2 border rounded-md max-h-48 overflow-y-auto text-sm"></div>
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

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script src="/assets/js/content.js"></script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>