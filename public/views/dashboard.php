<?php require_once __DIR__ . '/partials/header.php'; ?>

<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

    <main class="flex-1 p-6 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="stats-cards-container">
            </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Recent Users This Month</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Joined</th>
                                </tr>
                            </thead>
                            <tbody id="recent-users-body" class="bg-white divide-y divide-gray-200">
                                </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Recent Memberships This Month</h2>
                     <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membership Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                </tr>
                            </thead>
                            <tbody id="recent-members-body" class="bg-white divide-y divide-gray-200">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200" id="recent-event-container">
                     <h2 class="text-lg font-semibold text-gray-700 mb-4">Most Recent Event</h2>
                     <div id="event-details">
                        </div>
                </div>
            </div>
        </div>
    </main>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- NEW DATE FORMATTING HELPERS ---

    // Function to get the ordinal suffix for a day (1st, 2nd, 3rd, 4th)
    const getOrdinalSuffix = (day) => {
        if (day > 3 && day < 21) return 'th';
        switch (day % 10) {
            case 1:  return "st";
            case 2:  return "nd";
            case 3:  return "rd";
            default: return "th";
        }
    };

    // Function to format date string to "3rd of October 2025"
    const formatDateWithOrdinal = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = date.getDate();
        const month = date.toLocaleString('default', { month: 'long' });
        const year = date.getFullYear();
        return `${day}<sup>${getOrdinalSuffix(day)}</sup> ${month} ${year}`;
    };

    // Function to format time to "10:30 AM"
    const formatTime = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // --- END HELPERS ---


    // Helper to format growth percentage
    const formatGrowth = (growth) => {
        const sign = growth >= 0 ? '+' : '';
        const color = growth >= 0 ? 'text-green-500' : 'text-red-500';
        return `<span class="${color} text-sm font-medium">${sign}${growth.toFixed(1)}%</span>`;
    };
    
    // A. Fetch Stats Cards
    fetch('/api/dashboard/stats.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const stats = result.data;
                const container = document.getElementById('stats-cards-container');
                container.innerHTML = `
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex flex-col justify-between">
                        <div><div class="flex items-center justify-between"><div><p class="text-sm font-medium text-slate-600">Total Users</p><p class="text-2xl font-bold text-slate-900">${stats.users.total}</p></div><div class="bg-sky-500 w-8 h-8 rounded-md flex items-center justify-center"><i class="fas fa-users text-white"></i></div></div></div>
                        <div class="mt-4">${formatGrowth(stats.users.growth)}<span class="text-slate-500 text-sm ml-1">from last month</span></div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex flex-col justify-between">
                        <div><div class="flex items-center justify-between"><div><p class="text-sm font-medium text-slate-600">Active Members</p><p class="text-2xl font-bold text-slate-900">${stats.members.total}</p></div><div class="bg-green-500 w-8 h-8 rounded-md flex items-center justify-center"><i class="fas fa-id-card text-white"></i></div></div></div>
                        <div class="mt-4">${formatGrowth(stats.members.growth)}<span class="text-slate-500 text-sm ml-1">from last month</span></div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex flex-col justify-between">
                        <div><div class="flex items-center justify-between"><div><p class="text-sm font-medium text-slate-600">Total Companies</p><p class="text-2xl font-bold text-slate-900">${stats.companies.total}</p></div><div class="bg-yellow-500 w-8 h-8 rounded-md flex items-center justify-center"><i class="fas fa-building text-white"></i></div></div></div>
                        <div class="mt-4">${formatGrowth(stats.companies.growth)}<span class="text-slate-500 text-sm ml-1">from last month</span></div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex flex-col justify-between">
                        <div><div class="flex items-center justify-between"><div><p class="text-sm font-medium text-slate-600">Published Events</p><p class="text-2xl font-bold text-slate-900">${stats.events.total}</p></div><div class="bg-red-500 w-8 h-8 rounded-md flex items-center justify-center"><i class="fas fa-calendar-alt text-white"></i></div></div></div>
                        <div class="mt-4">${formatGrowth(stats.events.growth)}<span class="text-slate-500 text-sm ml-1">from last month</span></div>
                    </div>
                `;
            }
        });

    // B. Fetch Recent Users
    fetch('/api/dashboard/recent-users.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const tbody = document.getElementById('recent-users-body');
                if(result.data.length > 0) {
                    tbody.innerHTML = result.data.map(user => `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${user.full_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDateWithOrdinal(user.created_at)}</td>
                        </tr>
                    `).join('');
                } else {
                     tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-gray-500">No new users this month.</td></tr>';
                }
            }
        });
        
    // C. Fetch Recent Members
    fetch('/api/dashboard/recent-members.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const tbody = document.getElementById('recent-members-body');
                 if(result.data.length > 0) {
                    tbody.innerHTML = result.data.map(member => `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${member.full_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${member.membership_type}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDateWithOrdinal(member.start_date)}</td>
                        </tr>
                    `).join('');
                 } else {
                     tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-gray-500">No new members this month.</td></tr>';
                 }
            }
        });

    // D. Fetch Recent Event
    fetch('/api/dashboard/recent-event.php')
        .then(response => response.json())
        .then(result => {
            const container = document.getElementById('event-details');
            if (result.success) {
                const event = result.data;
                let schedulesHtml = '<p class="text-sm text-gray-500">No schedule found.</p>';
                if (event.schedules.length > 0) {
                    schedulesHtml = event.schedules.map(s => `
                        <a href="/events/schedules/manage?id=${s.id}" class="block -ml-2 p-2 rounded-md hover:bg-gray-50 transition-colors">
                            <div class="pl-2 border-l-2 border-gray-200">
                                <p class="font-semibold text-gray-700 text-sm">${s.title}</p>
                                <p class="text-xs text-gray-500">${formatDateWithOrdinal(s.start_datetime)}, ${formatTime(s.start_datetime)} - ${formatTime(s.end_datetime)}</p>
                            </div>
                        </a>
                    `).join('');
                }
                
                container.innerHTML = `
                    <a href="/events/manage?id=${event.id}" class="hover:underline">
                        <h3 class="font-bold text-md text-gray-800">${event.title}</h3>
                    </a>
                    <p class="text-sm text-gray-500 mb-2">${formatDateWithOrdinal(event.start_datetime)}</p>
                    <p class="text-sm text-gray-600 mb-4">${event.description}</p>
                    <h4 class="font-semibold text-gray-700 mb-2 text-sm">Schedule</h4>
                    <div class="space-y-1">
                        ${schedulesHtml}
                    </div>
                `;

            } else {
                container.innerHTML = '<p class="text-center py-4 text-gray-500">No recent events found.</p>';
            }
        });

});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>