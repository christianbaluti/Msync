<?php require_once __DIR__ . '/partials/header.php'; ?>
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold leading-6 text-gray-900">News & Communications</h1>

            <div class="mt-4 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button id="tab-news" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium border-indigo-500 text-indigo-600">News</button>
                    <button id="tab-communications" class="whitespace-nowrap border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 border-b-2 py-4 px-1 text-sm font-medium">Communications</button>
                </nav>
            </div>

            <div id="news-section">
                <div class="sm:flex sm:items-center mt-8">
                    <div class="sm:flex-auto">
                        <p class="text-sm text-gray-700">Create, edit, and manage news articles for your members.</p>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <button type="button" id="add-news-btn" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Add New Article</button>
                    </div>
                </div>
                <div class="mt-4 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead>
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Title</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody id="news-table-body" class="divide-y divide-gray-200">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="communications-section" class="hidden">
                <div class="sm:flex sm:items-center mt-8">
                    <div class="sm:flex-auto">
                        <p class="text-sm text-gray-700">Send bulk communications to members via Email or WhatsApp.</p>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <button type="button" id="add-comm-btn" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Send Communication</button>
                    </div>
                </div>
                 <div class="mt-4 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead>
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Subject / Message</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Channel</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Sent By</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date Sent</th>
                                    </tr>
                                </thead>
                                <tbody id="comms-table-body" class="divide-y divide-gray-200">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="newsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <h3 id="newsModalTitle" class="text-lg font-medium leading-6 text-gray-900">Add New Article</h3>
        <form id="newsForm" class="mt-4 space-y-4">
            <input type="hidden" name="id">
            <div>
                <label for="news-title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="news-title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label for="news-content" class="block text-sm font-medium text-gray-700">Content</label>
                <textarea name="content" id="news-content" rows="10" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>
            <div>
                <label for="news-media" class="block text-sm font-medium text-gray-700">Featured Image (Optional)</label>
                <input type="file" name="media" id="news-media" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <img id="media-preview" class="hidden mt-2 h-32 w-auto rounded" src="" alt="Image preview"/>
            </div>
            <div>
                <label for="news-scheduled-date" class="block text-sm font-medium text-gray-700">Schedule for Later (Optional)</label>
                <input type="datetime-local" name="scheduled_date" id="news-scheduled-date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="pt-4 flex justify-end gap-x-2 border-t">
                <button type="button" class="close-modal bg-white px-3 py-2 rounded-md text-sm font-semibold shadow-sm ring-1 ring-gray-300">Cancel</button>
                <button type="submit" name="status" value="draft" class="bg-gray-600 text-white px-3 py-2 rounded-md text-sm font-semibold shadow-sm">Save as Draft</button>
                <button type="submit" name="status" value="published" class="bg-indigo-600 text-white px-3 py-2 rounded-md text-sm font-semibold shadow-sm">Publish Now</button>
            </div>
        </form>
    </div>
</div>

<div id="communicationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Send New Communication</h3>
        <form id="communicationForm" class="mt-4 space-y-4">
             <div>
                <label for="comm-channel" class="block text-sm font-medium text-gray-700">Channel</label>
                <select name="channel" id="comm-channel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="email">Email</option>
                    <option value="whatsapp" disabled>WhatsApp (Coming Soon)</option>
                </select>
            </div>
            <div>
                <label for="comm-recipients" class="block text-sm font-medium text-gray-700">Recipients</label>
                <select multiple name="recipients[]" id="comm-recipients" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm h-32"></select>
                 <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple options.</p>
            </div>
            <div id="comm-subject-field">
                <label for="comm-subject" class="block text-sm font-medium text-gray-700">Subject</label>
                <input type="text" name="subject" id="comm-subject" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label for="comm-body" class="block text-sm font-medium text-gray-700">Message Body</label>
                <textarea name="body" id="comm-body" rows="8" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>
            <div class="pt-4 flex justify-end gap-x-2 border-t">
                <button type="button" class="close-modal bg-white px-3 py-2 rounded-md text-sm font-semibold shadow-sm ring-1 ring-gray-300">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-2 rounded-md text-sm font-semibold shadow-sm">Send Communication</button>
            </div>
        </form>
    </div>
</div>


<div id="toast" class="hidden fixed top-5 right-5 z-[100] space-y-2"></div>
<script src="/assets/js/news-and-communications.js"></script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>