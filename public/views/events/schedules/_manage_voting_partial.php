<?php
// We can assume $schedule is available from the parent manage.php file.
$schedule_id = $schedule['id'] ?? 0;
$event_id = $schedule['event_id'] ?? 0;
?>

<div id="eventHeader">
    <div class="space-y-4 animate-pulse">
        <div class="h-8 w-3/4 bg-slate-200 rounded-md"></div>
        <div class="flex space-x-6">
            <div class="h-4 w-1/4 bg-slate-200 rounded-md"></div>
            <div class="h-4 w-1/4 bg-slate-200 rounded-md"></div>
            <div class="h-4 w-1/3 bg-slate-200 rounded-md"></div>
        </div>
        <div class="h-10 bg-slate-200 rounded-md"></div>
    </div>
</div><br>

<div id="votingManagementContainer" data-schedule-id="<?php echo htmlspecialchars($schedule_id); ?>" class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

    <div class="bg-white p-4 rounded-lg shadow-sm mb-8">
        <div class="mb-4">
            <a id="backToEventLink" href="/events/manage?id=<?php echo htmlspecialchars($event_id); ?>" class="text-sm font-medium text-indigo-600 hover:underline" style="display: none">&larr; Back to Event</a>
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                </div>
                <a href="/events/manage?id=<?php echo htmlspecialchars($event_id); ?>" class="rounded-lg bg-[#E40000] px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-[#EE4000]">&larr; Back to Event</a> <br>
            </div>
            <h1 id="scheduleTitle" class="mt-2 text-3xl font-bold tracking-tight text-gray-900">
                <span class="h-8 w-3/4 bg-gray-200 rounded animate-pulse inline-block"></span>
            </h1>
            <p id="scheduleType" class="text-sm text-gray-500">
                <span class="h-4 w-1/4 bg-gray-200 rounded animate-pulse inline-block"></span>
            </p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2 border-t pt-4">
            <div class="flex items-center gap-x-2">
                <span class="text-sm font-medium text-gray-700">Stage:</span>
                <button id="switchStageBtn" class="h-8 w-32 bg-gray-200 animate-pulse rounded-md"></button>
            </div>
            <div class="flex items-center gap-x-2">
                <span class="text-sm font-medium text-gray-700">Status:</span>
                <button id="switchStatusBtn" class="h-8 w-28 bg-gray-200 animate-pulse rounded-md"></button>
            </div>
            <div class="flex-grow"></div>
            <button id="viewResultsBtn" class="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">View Results</button>
            <button id="settingsBtn" class="rounded-md bg-gray-700 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">Settings</button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-800">Election Positions</h2>
                <button id="addNewPositionBtn" class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">+ Add New Position</button>
            </div>
            
            <div id="positionsContainer" class="space-y-4">
                <div class="bg-white p-4 rounded-lg shadow-sm animate-pulse"><div class="h-12 w-full bg-gray-200 rounded"></div></div>
                <div class="bg-white p-4 rounded-lg shadow-sm animate-pulse"><div class="h-12 w-full bg-gray-200 rounded"></div></div>
            </div>
        </div>

        <aside class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900">Details</h2>
                    <button id="editDetailsBtn" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit</button>
                </div>
                <div id="scheduleDetailsContainer" class="mt-4 text-sm space-y-2 border-t pt-4">
                    <div class="h-16 bg-gray-200 animate-pulse rounded-md"></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900">Facilitators</h2>
                    <button id="manageFacilitatorsBtn" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Manage</button>
                </div>
                <div id="facilitatorsListContainer" class="mt-4 text-sm space-y-2 border-t pt-4">
                    <div class="h-8 bg-gray-200 animate-pulse rounded-md"></div>
                </div>
            </div>
             <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Overall Statistics</h3>
                <div id="overallStats" class="mt-4 space-y-3 border-t pt-4">
                     <div class="h-12 bg-gray-200 animate-pulse rounded-md"></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Votes by Position</h3>
                <div id="positionStats" class="mt-4 space-y-3 border-t pt-4">
                    <div class="h-16 bg-gray-200 animate-pulse rounded-md"></div>
                </div>
            </div>
             <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">Nomination Activity</h3>
                <div id="nominationStats" class="mt-4 space-y-3 border-t pt-4">
                     <div class="h-12 bg-gray-200 animate-pulse rounded-md"></div>
                </div>
            </div>
        </aside>
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

<div id="genericConfirmModal" class="hidden fixed inset-0 bg-slate-600/50 z-100 overflow-y-auto">
    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-xl rounded-xl bg-white">
        <h3 id="confirmModalTitle" class="text-lg font-semibold text-slate-900">Confirm Action</h3>
        <p id="confirmModalMessage" class="mt-2 text-sm text-slate-600">Are you sure you want to proceed?</p>
        <div class="mt-6 flex justify-end gap-x-3">
            <button type="button" class="close-modal rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
            <button type="button" id="confirmModalConfirmBtn" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Delete</button>
        </div>
    </div>
</div>

<div id="scheduleDetailsModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Edit Schedule Details</h3>
        <form id="scheduleDetailsForm" class="mt-4 space-y-4">
            <div>
                <label for="sch-title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="sch-title" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="sch-start" class="block text-sm font-medium text-gray-700">Start Datetime</label>
                    <input type="datetime-local" name="start_datetime" id="sch-start" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
                </div>
                <div>
                    <label for="sch-end" class="block text-sm font-medium text-gray-700">End Datetime</label>
                    <input type="datetime-local" name="end_datetime" id="sch-end" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
                </div>
            </div>
            <div>
                <label for="sch-desc" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="sch-desc" rows="3" class="w-full rounded-lg border-1 px-4 py-2 text-sm"></textarea>
            </div>
            <div class="pt-4 flex justify-end gap-x-2 border-t">
                <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm ring-1 ring-gray-300">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="facilitatorsModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Manage Facilitators</h3>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Current Facilitators</label>
            <div id="currentFacilitatorsList" class="mt-2 space-y-2 max-h-40 overflow-y-auto border p-3 rounded-md bg-gray-50">
                </div>
        </div>
        <form id="addFacilitatorForm" class="mt-4 pt-4 border-t flex items-center gap-x-2">
            <select id="facilitatorSelect" name="user_id" class="w-full rounded-lg border-1 px-4 py-2 text-sm"></select>
            <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm">Add</button>
        </form>
        <div class="mt-6 flex justify-end gap-x-2 border-t pt-4">
            <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm ring-1 ring-gray-300">Cancel</button>
            <button type="button" id="saveFacilitatorsBtn" class="bg-green-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm">Save All Changes</button>
        </div>
    </div>
</div>

<div id="nominationModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 id="nominationModalTitle" class="text-lg font-medium leading-6 text-gray-900">Add Nominee Manually</h3>
        <p class="text-sm text-gray-500">This nomination will be recorded as submitted by you (the administrator).</p>
        <form id="nominationForm" class="mt-4 space-y-4">
            <div>
                <label for="nom-seat" class="block text-sm font-medium text-gray-700">Position</label>
                <select name="seat_id" id="nom-seat" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
                    <option value="">Loading positions...</option>
                </select>
            </div>
            
            <div id="nomineeInputContainer">
                <p class="text-sm text-gray-500">Please select a position to see nominee options.</p>
            </div>

            <div class="pt-4 flex justify-end gap-x-2 border-t">
                <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm ring-1 ring-gray-300">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm">Add Nomination</button>
            </div>
        </form>
    </div>
</div>


<div id="settingsModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Election Settings</h3>
        <form id="electionSettingsForm" class="mt-4 space-y-4">
            <div>
                <label class="text-base font-semibold text-gray-900">Voter Eligibility</label>
                <p class="text-sm text-gray-500">Select all groups that are allowed to participate in this election.</p>
                
                <fieldset id="eligibilityCheckboxesContainer" class="mt-4 space-y-2 max-h-60 overflow-y-auto border p-3 rounded-md">
                    <div class="space-y-2">
                        <div class="h-5 bg-gray-200 rounded animate-pulse w-3/4"></div>
                        <div class="h-5 bg-gray-200 rounded animate-pulse w-1/2"></div>
                        <div class="h-5 bg-gray-200 rounded animate-pulse w-2/3"></div>
                    </div>
                </fieldset>
            </div>
            
            <div class="pt-4 flex justify-end gap-x-2 border-t">
                <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm ring-1 ring-gray-300">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<div id="positionModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
     <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 id="positionModalTitle" class="text-lg font-medium leading-6 text-gray-900">Add New Position</h3>
        <form id="positionForm" class="mt-4 space-y-4">
            <input type="hidden" name="id">
            <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($schedule_id); ?>">
            <div>
                <label for="pos-name" class="block text-sm font-medium text-gray-700">Position Title</label>
                <input type="text" name="name" id="pos-name" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
            </div>
            <div>
                <label for="pos-type" class="block text-sm font-medium text-gray-700">Election Type</label>
                <select name="nominee_type" id="pos-type" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
                    <option value="user">For a Person (e.g., President)</option>
                    <option value="company">For a Company (e.g., Partner of the Year)</option>
                    <option value="idea">For an Idea (e.g., Community Project)</option>
                </select>
            </div>
            <div>
                <label for="pos-desc" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="pos-desc" rows="3" class="w-full rounded-lg border-1 px-4 py-2 text-sm"></textarea>
            </div>
            <div class="pt-4 flex justify-end gap-x-2 border-t">
                <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm ring-1 ring-gray-300">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm">Save Position</button>
            </div>
        </form>
    </div>
</div>

<div id="candidateModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 id="candidateModalTitle" class="text-lg font-medium leading-6 text-gray-900">Add New Candidate</h3>
        <form id="candidateForm" class="mt-4 space-y-4">
            <input type="hidden" name="id">
            <input type="hidden" name="seat_id">
            <div>
                <label for="can-name" class="block text-sm font-medium text-gray-700">Candidate Name / Idea Title</label>
                <input type="text" name="name" id="can-name" required class="w-full rounded-lg border-1 px-4 py-2 text-sm">
            </div>
            <div>
                <label for="can-desc" class="block text-sm font-medium text-gray-700">Description / Bio</label>
                <textarea name="description" id="can-desc" rows="3" class="w-full rounded-lg border-1 px-4 py-2 text-sm"></textarea>
            </div>
            <div class="pt-4 flex justify-end gap-x-2 border-t">
                <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm ring-1 ring-gray-300">Cancel</button>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm">Save Candidate</button>
            </div>
        </form>
    </div>
</div>

<div id="promoteNominationsModal" class="hidden fixed inset-0 bg-gray-800/10 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 id="promoteModalTitle" class="text-lg font-medium leading-6 text-gray-900">Promote Nominees</h3>
        <p class="text-sm text-gray-500 mt-1">Select the nominees you wish to approve as official candidates.</p>
        <form id="promoteNominationsForm" class="mt-4 space-y-4">
            <fieldset id="nomineeListContainer" class="space-y-2 max-h-60 overflow-y-auto border p-3 rounded-md">
                <div class="h-24 bg-gray-200 animate-pulse rounded-md"></div>
            </fieldset>

            <div class="pt-4 flex justify-end gap-x-2 border-t">
                <button type="button" class="close-modal bg-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm ring-1 ring-gray-300">Cancel</button>
                <button type="submit" class="bg-green-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold shadow-sm">Promote Selected</button>
            </div>
        </form>
    </div>
</div>

<div id="toast" class="hidden fixed top-5 right-5 z-[100] space-y-2"></div>
<script src="/assets/js/manage-voting.js"></script>