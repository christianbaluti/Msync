<?php
// /views/settings.php
require_once dirname(__DIR__, 2) . '/api/core/initialize.php'; // For auth
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-bold leading-6 text-gray-900">System Settings</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage global system configuration, roles, and integrations.</p>
                </div>
            </div>

            <div class="mt-6">
                <div class="sm:hidden">
                    <label for="tabs" class="sr-only">Select a tab</label>
                    <select id="tabs" name="tabs" class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option selected>General</option>
                        <option>Payment Gateways</option>
                        <option>Roles & Permissions</option>
                        <option>App Updates</option>
                    </select>
                </div>
                <div class="hidden sm:block">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button id="tab-btn-general" class="tab-btn border-indigo-500 text-indigo-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" aria-current="page">
                                General
                            </button>
                            <button id="tab-btn-gateways" class="tab-btn border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                                Payment Gateways
                            </button>
                             <button id="tab-btn-roles" class="tab-btn border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                                Roles & Permissions
                            </button>
                            <button id="tab-btn-updates" class="tab-btn border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                                App Updates
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            <div id="tab-panel-general" class="tab-panel mt-6">
                <form id="generalSettingsForm" class="bg-white shadow-sm border border-slate-200 rounded-lg">
                    <div class="px-4 py-5 sm:p-6 space-y-6">
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900">General Settings</h3>
                            <p class="mt-1 text-sm text-gray-500">Manage basic system information.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="system_name" class="block text-sm font-medium text-slate-700">System Name</label>
                                <input type="text" id="system_name" name="system_name" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-5 sm:p-6 space-y-6 border-t border-gray-200">
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900">SMTP Settings</h3>
                            <p class="mt-1 text-sm text-gray-500">Configure the outgoing mail server for all system emails.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="smtp_host" class="block text-sm font-medium text-slate-700">SMTP Host</label>
                                <input type="text" id="smtp_host" name="smtp_host" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                            </div>
                            <div>
                                <label for="smtp_port" class="block text-sm font-medium text-slate-700">SMTP Port</label>
                                <input type="text" id="smtp_port" name="smtp_port" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                            </div>
                            <div>
                                <label for="smtp_username" class="block text-sm font-medium text-slate-700">SMTP Username</label>
                                <input type="text" id="smtp_username" name="smtp_username" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                            </div>
                            <div>
                                <label for="smtp_password" class="block text-sm font-medium text-slate-700">SMTP Password</label>
                                <input type="password" id="smtp_password" name="smtp_password" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                            </div>
                            <div>
                                <label for="smtp_secure" class="block text-sm font-medium text-slate-700">SMTP Security</label>
                                <select id="smtp_secure" name="smtp_secure" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                                    <option value="">None</option>
                                    <option value="ssl">SSL</option>
                                    <option value="tls">TLS</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="smtp_from_email" class="block text-sm font-medium text-slate-700">From Email</label>
                                <input type="email" id="smtp_from_email" name="smtp_from_email" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                            </div>
                            <div>
                                <label for="smtp_from_name" class="block text-sm font-medium text-slate-700">From Name</label>
                                <input type="text" id="smtp_from_name" name="smtp_from_name" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 text-right sm:px-6 rounded-b-lg">
                        <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save General Settings</button>
                    </div>
                </form>
            </div>

            <div id="tab-panel-gateways" class="tab-panel mt-6 hidden">
                <form id="gatewaySettingsForm" class="bg-white shadow-sm border border-slate-200 rounded-lg">
                    <div class="px-4 py-5 sm:p-6 space-y-6">
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Payment Gateways</h3>
                            <p class="mt-1 text-sm text-gray-500">Configure API keys and settings for payment providers.</p>
                        </div>
                        <div id="gateway-form-container" class="space-y-8">
                            </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 text-right sm:px-6 rounded-b-lg">
                        <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Gateway Settings</button>
                    </div>
                </form>
            </div>

            <div id="tab-panel-roles" class="tab-panel mt-6 hidden">
                 <div class="bg-white shadow-sm border border-slate-200 rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Roles & Permissions</h3>
                        <p class="mt-1 text-sm text-gray-500">Manage user roles and assign specific permissions.</p>
                    </div>
                    <div id="roles-list-container" class="border-t border-gray-200">
                        </div>
                </div>
            </div>

            <div id="tab-panel-updates" class="tab-panel mt-6 hidden">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Mobile App Updates</h3>
                        <p class="mt-1 text-sm text-gray-500">Manage app versions and force updates for mobile users.</p>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <button type="button" id="addAppUpdateBtn" class="block rounded-md bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Add New Version</button>
                    </div>
                </div>
                 <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <div id="appUpdatesTableContainer" class="overflow-x-auto">
                        </div>
                </div>
            </div>

        </div>
    </main>
</div>

<div id="roleModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-4xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900" id="roleModalTitle">Manage Permissions for...</h3>
        <form id="roleForm" class="mt-4 space-y-5">
            <input type="hidden" id="roleId" name="role_id">
            <div id="permissions-container" class="max-h-[60vh] overflow-y-auto space-y-5 p-4 bg-gray-50 rounded-lg border">
                </div>
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Permissions</button>
            </div>
        </form>
    </div>
</div>

<div id="appUpdateModal" class="hidden fixed inset-0 bg-slate-700/50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-xl shadow-xl rounded-xl bg-white">
        <h3 class="text-lg font-semibold text-slate-900" id="appUpdateModalTitle">Add New App Version</h3>
        <form id="appUpdateForm" class="mt-4 space-y-5">
            <input type="hidden" id="updateId" name="id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div>
                    <label for="platform" class="block text-sm font-medium text-slate-700">Platform</label>
                    <select id="platform" name="platform" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                        <option value="all">All</option>
                        <option value="android">Android</option>
                        <option value="ios">iOS</option>
                    </select>
                </div>
                 <div>
                    <label for="is_force_update" class="block text-sm font-medium text-slate-700">Update Type</label>
                    <select id="is_force_update" name="is_force_update" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                        <option value="1">Force Update</option>
                        <option value="0">Optional</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="version_name" class="block text-sm font-medium text-slate-700">Version Name</label>
                    <input type="text" id="version_name" name="version_name" required placeholder="e.g., 1.0.2" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                </div>
                <div>
                    <label for="version_code" class="block text-sm font-medium text-slate-700">Version Code</label>
                    <input type="number" id="version_code" name="version_code" required placeholder="e.g., 10002" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm">
                </div>
            </div>
            <div>
                <label for="release_notes" class="block text-sm font-medium text-slate-700">Release Notes</label>
                <textarea id="release_notes" name="release_notes" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm"></textarea>
            </div>
            
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" class="close-modal-btn rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#E40000] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">Save Version</button>
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

<script src="/assets/js/settings.js"></script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>