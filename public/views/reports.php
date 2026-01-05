<?php require_once __DIR__ . '/partials/header.php'; ?>
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<?php

// CSRF token generation (simple)
if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

?>

<div class="md:pl-72">
    <?php require_once __DIR__ . '/partials/menubar.php'; ?>

   <main class="max-w-7xl mx-auto p-6 py-10 space-y-8">

  <!-- Header -->
  <header class="flex flex-col md:flex-row items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-2xl shadow-sm">
    <div>
      <h1 class="text-3xl font-extrabold text-gray-900 mb-1">Reports Dashboard</h1>
      <p class="text-gray-600 max-w-xl">
        Generate insightful reports across users, events, marketplace, elections, and more. Export your data instantly or visualize trends.
      </p>
    </div>
    <img src="/assets/img/reports_illustration.svg" alt="Reports Illustration" class="w-48 md:w-56 mt-4 md:mt-0">
  </header>

  <!-- Smart Report Builder -->
  <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
        <i class="fa-solid fa-brain text-blue-600"></i>
        Smart Report Builder
      </h2>
      <span class="text-xs text-gray-500">Build custom data exports and analytics</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Data Source -->
      <!-- Data Source -->
<div>
  <label class="block text-sm font-medium text-gray-700 mb-1">Data source</label>
  <select id="tableSelect" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
    <option value="">-- Select a table --</option>

    <!-- User and Company -->
    <option value="users">Users</option>
    <option value="companies">Companies</option>

    <!-- Events -->
    <option value="events">Events</option>
    <option value="event_ticket_types">Event Ticket Types</option>
    <option value="event_tickets">Event Tickets</option>
    <option value="event_attendance">Event Attendance</option>
    <option value="event_schedules">Event Schedules</option>
    <option value="meal_cards">Meal Cards</option>

    <!-- Marketplace -->
    <option value="marketplace_orders">Marketplace Orders</option>
    <option value="marketplace_order_items">Marketplace Order Items</option>
    <option value="product_variants">Product Variants</option>

    <!-- Membership -->
    <option value="membership_types">Membership Types</option>
    <option value="membership_subscriptions">Membership Subscriptions</option>

    <!-- Elections -->
    <option value="election_candidates">Election Candidates</option>
    <option value="election_nominations">Election Nominations</option>
    <option value="election_seats">Election Seats</option>
    <option value="votes">Votes</option>

    <!-- Finance -->
    <option value="payments">Payments</option>
    <option value="invoices">Invoices</option>

    <!-- Content / UI / Logs -->
    <option value="news">News</option>
    <option value="news_views">News Views</option>
    <option value="ads">Ads</option>
    <option value="audit_logs">Audit Logs</option>
    <option value="active_ui_setting">Active UI Setting</option>
    <option value="ui_themes">UI Themes</option>

    <!-- Security / Support -->
    <option value="otp_verifications">OTP Verifications</option>
    <option value="password_resets">Password Resets</option>
    <option value="nametags">Nametags</option>
  </select>
</div>


      <!-- Date Range -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Date range</label>
        <div class="flex gap-2">
          <input type="date" id="dateFrom" class="rounded-lg border-gray-300 p-2 w-full focus:ring-blue-500 focus:border-blue-500" />
          <input type="date" id="dateTo" class="rounded-lg border-gray-300 p-2 w-full focus:ring-blue-500 focus:border-blue-500" />
        </div>
      </div>

      <!-- Group By -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Group results</label>
        <select id="groupBy" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
          <option value="">None</option>
          <option value="day">By day</option>
          <option value="month">By month</option>
          <option value="year">By year</option>
          <option value="field">By a field</option>
        </select>
      </div>
    </div>

    <!-- Second row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
      <!-- Fields -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fields to include</label>
        <select id="fieldsSelect" multiple class="block w-full rounded-lg border-gray-300 shadow-sm h-36 focus:ring-blue-500 focus:border-blue-500"></select>
        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple fields.</p>
      </div>

      <!-- Group Field -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Group field (if applicable)</label>
        <input id="groupField" type="text" class="block w-full rounded-lg border-gray-300 p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. event_id, product_id" />
      </div>

      <!-- Actions -->
      <div class="flex flex-col justify-end">
        <div class="flex flex-wrap gap-3">
          <button id="previewBtn" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
            <i class="fa-solid fa-eye"></i> Preview
          </button>
          <button id="exportCsv" class="px-3 py-2 rounded-lg bg-gray-700 text-white hover:bg-gray-800">CSV</button>
          <button id="exportExcel" class="px-3 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">Excel</button>
          <button id="exportPdf" class="px-3 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">PDF</button>
        </div>
        <label class="inline-flex items-center mt-3">
          <input id="includeHeader" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked />
          <span class="ml-2 text-sm text-gray-700">Include header row</span>
        </label>
      </div>
    </div>
  </section>

  <!-- Preview Section -->
  <section id="previewSection" class="hidden bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <i class="fa-solid fa-table"></i> Data Preview
      </h3>
      <div class="flex items-center gap-3">
        <span id="previewCount" class="text-sm text-gray-500"></span>
        <button id="refreshPreview" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">Refresh</button>
      </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
      <table id="previewTable" class="min-w-full text-sm text-left">
        <thead id="previewHead" class="bg-gray-50 text-gray-700"></thead>
        <tbody id="previewBody" class="divide-y divide-gray-100"></tbody>
      </table>
    </div>

    <!-- Chart -->
    <div class="mt-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
      <canvas id="reportChart" height="120"></canvas>
    </div>
  </section>

  <!-- Empty/Error State -->
  <section id="emptyState" class="hidden flex flex-col items-center justify-center text-center py-16">
    <img src="/assets/img/no_data.svg" alt="No Data" class="w-40 mb-4 opacity-80">
    <h3 class="text-lg font-semibold text-gray-800">No data to display yet</h3>
    <p class="text-gray-500 max-w-md mt-1">
      Select a data source and click <strong>Preview</strong> to generate your first report.
    </p>
  </section>

   </main>

  <script src="/assets/js/reports.js"></script>
</div>


<?php require_once __DIR__ . '/partials/footer.php'; ?>