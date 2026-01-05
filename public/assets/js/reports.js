// reports.js — Enhanced Smart Reports Page Controller
(function () {
  // ==== ELEMENTS ====
  const tableSelect = document.getElementById('tableSelect');
  const fieldsSelect = document.getElementById('fieldsSelect');
  const previewBtn = document.getElementById('previewBtn');
  const previewSection = document.getElementById('previewSection');
  const previewHead = document.getElementById('previewHead');
  const previewBody = document.getElementById('previewBody');
  const previewCount = document.getElementById('previewCount');
  const dateFrom = document.getElementById('dateFrom');
  const dateTo = document.getElementById('dateTo');
  const groupBy = document.getElementById('groupBy');
  const groupField = document.getElementById('groupField');
  const refreshPreview = document.getElementById('refreshPreview');
  const includeHeader = document.getElementById('includeHeader');
  const exportCsv = document.getElementById('exportCsv');
  const exportExcel = document.getElementById('exportExcel');
  const exportPdf = document.getElementById('exportPdf');
  const emptyState = document.getElementById('emptyState');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || window.csrfToken || '';

  let chart = null;
  let isLoading = false;

  // ==== DEFAULT FIELDS MAP ====
  const defaultFields = {
        // users table (fields from dump)
        'users' : [
            'id','full_name','email','phone','password_hash','remember_token_hash',
            'gender','is_employed','company_id','position','role','is_active','last_login','created_at'
        ],

        // companies table (exact columns from dump — note: no 'industry')
        'companies' : [
            'id','name','email','phone','password_hash','address','allow_login','is_active','last_login','created_at','updated_at'
        ],

        // events
        'events' : [
            'id','title','description','start_datetime','end_datetime','location','main_image','status','created_by','created_at'
        ],

        // event ticket types
        'event_ticket_types' : ['id','event_id','name','price','member_type_id'],

        // event tickets (table exists in dump as event_tickets)
        'event_tickets' : [
            'id','event_id','ticket_type_id','company_id','user_id','price','status','created_at'
        ],

        // event_attendance (columns per dump)
        'event_attendance' : ['id','ticket_id','item_given','checked_in','checked_in_at'],

        // event schedules
        'event_schedules' : ['id','event_id','type','start_datetime','end_datetime','title','description', 'status'],

        // marketplace orders & items
        'marketplace_orders' : ['id','user_id','company_id','status','total_amount','paid_amount','balance_due','shipping_address','created_at'],
        'marketplace_order_items' : ['id','order_id','variant_id','quantity','unit_price','total_price'],
        // product_variants (from dump)
        'product_variants' : ['id','product_id','variant_sku','name','attributes','price','created_at'],

        // membership tables (names as in dump)
        'membership_types' : ['id','name','description','renewal_month','grace_period_months','fee','created_at'],
        'membership_subscriptions' : ['id','user_id','company_id','membership_type_id','start_at','end_at','membership_card_number','balance_due','balance_due','status','created_at'],

        // meal cards
        'meal_cards' : ['id','schedule_id','ticket_id','status','updated_at'],
        // election-related 
        'election_candidates' : ['id','seat_id','name','description','image_url'],
        'election_nominations' : ['id','seat_id','nominated_by_user_id','nominee_user_id','nominee_company_id','nomination_text','created_at'],
        'election_seats' : ['id','schedule_id','name','description','nominee_type'],
        'votes' : ['id','candidate_id','user_id','seat_id','voted_at'],

        // audit/logs/ads/ui
        'audit_logs' : ['id','actor_type','actor_id','action','object_type','object_id','meta','created_at'],
        'ads' : ['id','title','body','media_url','url_target','created_by','start_at','end_at','status','created_at'],
        'active_ui_setting' : ['id','theme_id','updated_at'],
        'ui_themes' : ['id','name','is_default','config','created_by','created_at'],

        // payments/invoices/receipts (some columns from dump)
        'payments' : ['id','user_id','company_id','gateway_id','payment_type','reference_id','amount','method','status','gateway_transaction_id','transaction_date'],
        'invoices' : ['id','user_id','company_id','related_type','related_id','total_amount','paid_amount','balance_due','status','issued_at','due_date','meta'],

        // other smaller tables (otp, password_resets, news etc.)
        'otp_verifications' : ['id','target_type','target_id','channel','code','purpose','used','expires_at','created_at'],
        
        'news' : ['id','title','content','media_url','scheduled_date','created_at','created_by'],
        'news_views' : ['id','news_id','user_id','viewed_at'],
        'nametags' : ['id','ticket_id','pdf_url','qr_code','created_at'],
  };

  // ==== HELPER FUNCTIONS ====

  function setLoading(state) {
    isLoading = state;
    previewBtn.disabled = state;
    previewBtn.innerHTML = state
      ? `<svg class="animate-spin h-5 w-5 text-white inline mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Loading...`
      : `<i class="fa-solid fa-eye"></i> Preview`;
  }

  function showError(message) {
    const existing = document.getElementById('errorBanner');
    if (existing) existing.remove();

    const banner = document.createElement('div');
    banner.id = 'errorBanner';
    banner.className = 'bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg mt-4';
    banner.innerHTML = `<strong>Error:</strong> ${message}`;
    document.querySelector('main').prepend(banner);

    setTimeout(() => banner.remove(), 6000);
  }

  function showSuccess(message) {
    const existing = document.getElementById('successBanner');
    if (existing) existing.remove();

    const banner = document.createElement('div');
    banner.id = 'successBanner';
    banner.className = 'bg-green-50 border border-green-200 text-green-800 p-3 rounded-lg mt-4';
    banner.innerHTML = `<i class="fa-solid fa-circle-check mr-2"></i>${message}`;
    document.querySelector('main').prepend(banner);

    setTimeout(() => banner.remove(), 4000);
  }

  function populateFieldsForTable(table) {
    fieldsSelect.innerHTML = '';
    if (!table) return;
    const fields = defaultFields[table] || [];
    fields.forEach(f => {
      const opt = document.createElement('option');
      opt.value = f;
      opt.textContent = f;
      fieldsSelect.appendChild(opt);
    });
    for (let i = 0; i < Math.min(3, fieldsSelect.options.length); i++) {
      fieldsSelect.options[i].selected = true;
    }
  }

  function buildParams() {
    const table = tableSelect.value;
    if (!table) {
      showError('Please choose a data source (table).');
      throw new Error('no-table');
    }

    const fields = Array.from(fieldsSelect.selectedOptions).map(o => o.value);
    return {
      table,
      fields,
      dateFrom: dateFrom.value || null,
      dateTo: dateTo.value || null,
      groupBy: groupBy.value || null,
      groupField: groupField.value || null,
      includeHeader: includeHeader.checked ? 1 : 0,
      csrf: csrfToken
    };
  }

  // ==== FETCH PREVIEW ====
  async function fetchPreview() {
    setLoading(true);
    emptyState.classList.add('hidden');
    previewSection.classList.add('hidden');

    try {
      const params = buildParams();
      const resp = await fetch('/api/reports/report_api.php?action=preview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(params),
        credentials: 'same-origin'
      });
      
      // const text = await resp.text();
       // See the raw output

      if (!resp.ok) {
        const txt = await resp.text();
        throw new Error(txt || `HTTP ${resp.status}`);
      }

      const json = await resp.json();
      console.log("Response from API: " + JSON.stringify(json));
      if (!json.rows || json.rows.length === 0) {
        showSuccess('No data found for the selected criteria.');
        emptyState.classList.remove('hidden');
        return;
      }
     
     // const json = JSON.parse(text);


      renderPreview(json);
      showSuccess('Report preview generated successfully.');
    } catch (err) {
      console.error(err);
      showError('Failed to generate preview. ' + err.message);
      emptyState.classList.remove('hidden');
    } finally {
      setLoading(false);
    }
  }

  // ==== RENDER PREVIEW ====
  function renderPreview({ columns = [], rows = [], chart: chartData = null }) {
    previewSection.classList.remove('hidden');
    previewHead.innerHTML = '';
    previewBody.innerHTML = '';
    previewCount.textContent = `Rows: ${rows.length}`;

    // Header
    const trHead = document.createElement('tr');
    columns.forEach(c => {
      const th = document.createElement('th');
      th.className = 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100';
      th.textContent = c;
      trHead.appendChild(th);
    });
    previewHead.appendChild(trHead);

    // Body (limit 500)
    rows.slice(0, 500).forEach(row => {
      const tr = document.createElement('tr');
      columns.forEach(c => {
        const td = document.createElement('td');
        td.className = 'px-4 py-2 text-sm text-gray-700 border-t';
        td.textContent = row[c] ?? '';
        tr.appendChild(td);
      });
      previewBody.appendChild(tr);
    });

    // Animate chart
    if (chartData && chartData.labels && chartData.data) {
      const ctx = document.getElementById('reportChart');
      if (chart) chart.destroy();
      chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: chartData.labels,
          datasets: [{
            label: chartData.label || 'Count',
            data: chartData.data,
            backgroundColor: 'rgba(37, 99, 235, 0.6)',
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: { y: { beginAtZero: true } },
          animation: { duration: 800 }
        }
      });
    }
  }

  // ==== EXPORT ACTIONS ====
  async function exportAction(format) {
    try {
      const params = buildParams();
      params.format = format;
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '/api/reports/report_api.php?action=export';
      form.style.display = 'none';

      addHidden(form, 'payload', JSON.stringify(params));
      addHidden(form, 'csrf', csrfToken);
      document.body.appendChild(form);
      form.submit();
      showSuccess(`Exporting report as ${format.toUpperCase()}...`);
      form.remove();
    } catch (err) {
      showError(err.message);
    }
  }

  function addHidden(form, name, value) {
    const inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = name;
    inp.value = value;
    form.appendChild(inp);
  }

  // ==== EVENT LISTENERS ====
  tableSelect.addEventListener('change', e => populateFieldsForTable(e.target.value));
  previewBtn.addEventListener('click', e => { e.preventDefault(); fetchPreview(); });
  refreshPreview.addEventListener('click', e => { e.preventDefault(); fetchPreview(); });
  exportCsv.addEventListener('click', e => { e.preventDefault(); exportAction('csv'); });
  exportExcel.addEventListener('click', e => { e.preventDefault(); exportAction('excel'); });
  exportPdf.addEventListener('click', e => { e.preventDefault(); exportAction('pdf'); });

  // ==== INIT ====
  if (tableSelect.value) populateFieldsForTable(tableSelect.value);
  emptyState.classList.remove('hidden');
})();
