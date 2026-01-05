<?php
// includes/report_helpers.php
declare(strict_types=1);

/**
 * NOTE:
 * - get_db_connection() must exist and return PDO.
 * - This file was updated to reflect the exact schema in immadmin_membersync.sql (uploaded).
 *   See: immadmin_membersync.sql. :contentReference[oaicite:1]{index=1}
 */

// ===============================================================
// 🔹 UPDATED: Allowed Tables & Fields (mirrors immadmin_membersync.sql)
// ===============================================================
function allowed_tables_and_fields(): array {
    return [
        // users table (fields from dump)
        'users' => [
            'id','full_name','email','phone','password_hash','remember_token_hash',
            'gender','is_employed','company_id','position','role','is_active','last_login','created_at'
        ],

        // companies table (exact columns from dump — note: no 'industry')
        'companies' => [
            'id','name','email','phone','password_hash','address','allow_login','is_active','last_login','created_at','updated_at'
        ],

        // events
        'events' => [
            'id','title','description','start_datetime','end_datetime','location','main_image','status','created_by','created_at'
        ],

        // event ticket types
        'event_ticket_types' => ['id','event_id','name','price','member_type_id'],

        // event tickets (table exists in dump as event_tickets)
        'event_tickets' => [
            'id','event_id','ticket_type_id','company_id','user_id','price','status','created_at'
        ],

        // event_attendance (columns per dump)
        'event_attendance' => ['id','ticket_id','item_given','checked_in','checked_in_at'],

        // event schedules
        'event_schedules' => ['id','event_id','type','start_datetime','end_datetime','title','description', 'status'],

        // marketplace orders & items
        'marketplace_orders' => ['id','user_id','company_id','status','total_amount','paid_amount','balance_due','shipping_address','created_at'],
        'marketplace_order_items' => ['id','order_id','variant_id','quantity','unit_price','total_price'],

        // product_variants (from dump)
        'product_variants' => ['id','product_id','variant_sku','name','attributes','price','created_at'],

        // membership tables (names as in dump)
        'membership_types' => ['id','name','description','renewal_month','grace_period_months','fee','created_at'],
        'membership_subscriptions' => ['id','user_id','company_id','membership_type_id','start_at','end_at','membership_card_number','balance_due','balance_due','status','created_at'],

        // meal cards
        'meal_cards' => ['id','schedule_id','ticket_id','status','updated_at'],

        // election-related 
        'election_candidates' => ['id','seat_id','name','description','image_url'],
        'election_nominations' => ['id','seat_id','nominated_by_user_id','nominee_user_id','nominee_company_id','nomination_text','created_at'],
        'election_seats' => ['id','schedule_id','name','description','nominee_type'],
        'votes' => ['id','candidate_id','user_id','seat_id','voted_at'],

        // audit/logs/ads/ui
        'audit_logs' => ['id','actor_type','actor_id','action','object_type','object_id','meta','created_at'],
        'ads' => ['id','title','body','media_url','url_target','created_by','start_at','end_at','status','created_at'],
        'active_ui_setting' => ['id','theme_id','updated_at'],
        'ui_themes' => ['id','name','is_default','config','created_by','created_at'],

        // payments/invoices/receipts (some columns from dump)
        'payments' => ['id','user_id','company_id','gateway_id','payment_type','reference_id','amount','method','status','gateway_transaction_id','transaction_date'],
        'invoices' => ['id','user_id','company_id','related_type','related_id','total_amount','paid_amount','balance_due','status','issued_at','due_date','meta'],

        // other smaller tables (otp, password_resets, news etc.)
        'otp_verifications' => ['id','target_type','target_id','channel','code','purpose','used','expires_at','created_at'],
        
        'news' => ['id','title','content','media_url','scheduled_date','created_at','created_by'],
        'news_views' => ['id','news_id','user_id','viewed_at'],
        'nametags' => ['id','ticket_id','pdf_url','qr_code','created_at'],
    ];
}

// ===============================================================
// 🔹 UPDATED: Build Query with Relationship Joins and Safe Filters
// ===============================================================
function build_query(PDO $pdo, array $input, array &$params_out, ?int $limit = null): string {
    $map = allowed_tables_and_fields();
    $table = $input['table'] ?? '';
    if (!array_key_exists($table, $map)) {
        throw new RuntimeException('Invalid table selected.');
    }

    // fields validation
    $fields = $input['fields'] ?? [];
    if (empty($fields)) {
        $fields = array_slice($map[$table], 0, 10);
    }
    $fields = array_values(array_intersect($fields, $map[$table]));
    if (empty($fields)) {
        throw new RuntimeException('No valid fields requested.');
    }

    // sanitize/quote field list (these are whitelisted)
    $selectList = implode(',', array_map(fn($f) => "`$f`", $fields));
    $sql = "SELECT $selectList FROM `$table` WHERE 1=1";

    // 🔹 NEW/UPDATED: relationship-aware selects (use actual columns from dump)
    switch ($table) {
        case 'event_attendance':
            // event_attendance references event_tickets via ticket_id
            $sql = "SELECT ea.id, u.full_name AS attendee_name, t.id AS ticket_id, t.quantity AS ticket_quantity, 
                           ea.item_given, ea.checked_in, ea.checked_in_at
                    FROM event_attendance ea
                    LEFT JOIN event_tickets t ON ea.ticket_id = t.id
                    LEFT JOIN users u ON t.user_id = u.id
                    WHERE 1=1";
            break;

        case 'marketplace_orders':
            $sql = "SELECT mo.id, u.full_name AS customer, mo.total_amount, mo.paid_amount, mo.balance_due, mo.status, mo.created_at
                    FROM marketplace_orders mo
                    LEFT JOIN users u ON mo.user_id = u.id
                    WHERE 1=1";
            break;

        case 'event_tickets':
            // join ticket type and event title (using event_ticket_types & events)
            $sql = "SELECT t.id, e.title AS event_title, ett.name AS ticket_type_name, t.quantity, t.unit_price, t.status, t.created_at
                    FROM event_tickets t
                    LEFT JOIN events e ON t.event_id = e.id
                    LEFT JOIN event_ticket_types ett ON t.ticket_type_id = ett.id
                    WHERE 1=1";
            break;

        case 'votes':
            $sql = "SELECT v.id, u.full_name AS voter, c.name AS candidate, s.name AS seat, v.voted_at
                    FROM votes v
                    LEFT JOIN users u ON v.user_id = u.id
                    LEFT JOIN election_candidates c ON v.candidate_id = c.id
                    LEFT JOIN election_seats s ON v.seat_id = s.id
                    WHERE 1=1";
            break;

        case 'companies':
            // companies table is simple — select sanitized fields
            $sql = "SELECT `id`,`name`,`email`,`phone`,`address`,`allow_login`,`is_active`,`last_login`,`created_at`,`updated_at`
                    FROM companies WHERE 1=1";
            break;

        // (other tables will use default selectList)
    }

    // Apply date filters using column names discovered in dump
    $params = [];
    $dateFrom = $input['dateFrom'] ?? null;
    $dateTo = $input['dateTo'] ?? null;

    // pick a date-like column from allowed fields
    $dateCols = array_filter($map[$table], fn($c) => str_ends_with($c, 'at') || in_array($c, ['created_at','issued_at','transaction_date','started_at','ended_at','voted_at','checked_in_at','scheduled_date','sent_at']));
    $dateCol = $dateCols ? array_values($dateCols)[0] : null;

    if ($dateCol) {
        if (!empty($dateFrom)) {
            $sql .= " AND `$dateCol` >= :dateFrom";
            $params[':dateFrom'] = $dateFrom . ' 00:00:00';
        }
        if (!empty($dateTo)) {
            $sql .= " AND `$dateCol` <= :dateTo";
            $params[':dateTo'] = $dateTo . ' 23:59:59';
        }
    }

    // Grouping logic (keeps your original behavior)
    $groupBy = $input['groupBy'] ?? null;
    $groupField = $input['groupField'] ?? null;
    if ($groupBy === 'field' && $groupField) {
        if (!in_array($groupField, $map[$table])) {
            throw new RuntimeException('Invalid group field.');
        }
        $sql = "SELECT `$groupField` AS label, COUNT(*) AS value FROM `$table` WHERE 1=1";
        if (!empty($dateFrom) && $dateCol) $sql .= " AND `$dateCol` >= :dateFrom";
        if (!empty($dateTo) && $dateCol) $sql .= " AND `$dateCol` <= :dateTo";
        $sql .= " GROUP BY `$groupField` ORDER BY value DESC";
    } elseif (in_array($groupBy, ['day','month','year'])) {
        if ($dateCol) {
            $fmt = ($groupBy === 'day') ? '%Y-%m-%d' : (($groupBy === 'month') ? '%Y-%m' : '%Y');
            $sql = "SELECT DATE_FORMAT(`$dateCol`, '$fmt') AS label, COUNT(*) AS value FROM `$table` WHERE 1=1";
            if (!empty($dateFrom)) $sql .= " AND `$dateCol` >= :dateFrom";
            if (!empty($dateTo)) $sql .= " AND `$dateCol` <= :dateTo";
            $sql .= " GROUP BY label ORDER BY label ASC";
        }
    }

    if ($limit && stripos($sql, 'group by') === false) {
        $sql .= " LIMIT " . intval($limit);
    }

    $params_out = $params;
    return $sql;
}

// ===============================================================
// 🔹 NEW: Normalize Boolean Columns for Readable Exports
// ===============================================================
function normalize_booleans(array &$rows): void {
    foreach ($rows as &$r) {
        foreach ($r as $k => $v) {
            // Only convert true DB boolean-like columns (0/1). Keep strings as-is.
            if ($v === 0 || $v === 1 || $v === '0' || $v === '1') {
                // Heuristic: column names that usually represent booleans
                if (preg_match('/^(is_|has_|allow_|allow|active|checked|status|allow_login)$/i', $k)) {
                    // For status enums we don't blindly flip; if value is numeric 0/1 then convert
                    if ($v === 1 || $v === '1') {
                        $r[$k] = 'Yes';
                    } elseif ($v === 0 || $v === '0') {
                        $r[$k] = 'No';
                    }
                }
            }
        }
    }
}

// ===============================================================
// 🔹 No structural changes below — boolean normalization integrated
// ===============================================================
function export_csv_stream(array $rows, $includeHeader = 1) {
    normalize_booleans($rows); // 🔹 UPDATED
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report.csv"');
    $fh = fopen('php://output', 'w');
    if ($includeHeader && count($rows) > 0) {
        fputcsv($fh, array_keys($rows[0]));
    }
    foreach ($rows as $r) {
        fputcsv($fh, array_values($r));
    }
    fclose($fh);
    exit;
}

function export_excel_stream(array $rows, $includeHeader = 1) {
    normalize_booleans($rows); // 🔹 UPDATED
    require_once __DIR__ . '/../../vendor/autoload.php';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $rowIdx = 1;
    if ($includeHeader && count($rows) > 0) {
        $col = 'A';
        foreach (array_keys($rows[0]) as $h) {
            $sheet->setCellValue($col.$rowIdx, $h);
            $col++;
        }
        $rowIdx++;
    }
    foreach ($rows as $r) {
        $col = 'A';
        foreach ($r as $val) {
            $sheet->setCellValue($col.$rowIdx, $val);
            $col++;
        }
        $rowIdx++;
    }
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="report.xlsx"');
    $writer->save('php://output');
    exit;
}

function render_html_for_pdf(array $rows, array $input, $includeHeader = 1): string {
    normalize_booleans($rows); // 🔹 UPDATED
    $html = '<!doctype html><html><head><meta charset="utf-8"><style>
      table{width:100%;border-collapse:collapse;font-family: Arial, Helvetica, sans-serif;font-size:12px}
      th,td{border:1px solid #ddd;padding:6px}
      th{background:#f4f4f4}
      </style></head><body>';
    $html .= '<h2>Report</h2>';
    $html .= '<p>Table: ' . htmlspecialchars($input['table'] ?? '') . '</p>';
    $html .= '<table><thead>';
    if ($includeHeader && count($rows) > 0) {
        $html .= '<tr>';
        foreach (array_keys($rows[0]) as $h) {
            $html .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr>';
    }
    $html .= '</thead><tbody>';
    foreach ($rows as $r) {
        $html .= '<tr>';
        foreach ($r as $v) {
            $html .= '<td>' . htmlspecialchars((string)$v) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '</body></html>';
    return $html;
}

// ===============================================================
// 🔹 Chart generation (uses build_query with corrected schema)
// ===============================================================
function generate_smart_chart(PDO $pdo, array $input): ?array {
    try {
        if (isset($input['groupBy']) && ($input['groupBy'] === 'field' || in_array($input['groupBy'], ['day','month','year']))) {
            $sql = build_query($pdo, $input, $params_out);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params_out);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $labels = [];
            $data = [];
            foreach ($rows as $r) {
                $labels[] = (string)($r['label'] ?? array_values($r)[0]);
                $data[] = (int)($r['value'] ?? array_values($r)[1] ?? 0);
            }
            return ['labels' => $labels, 'data' => $data, 'label' => 'Count'];
        }

        $table = $input['table'] ?? '';
        if ($table === 'event_ticket_types') {
            $q = "SELECT e.title AS label, COUNT(t.id) AS value
                  FROM event_ticket_types t
                  LEFT JOIN events e ON e.id = t.event_id
                  GROUP BY e.title ORDER BY value DESC LIMIT 10";
            $stmt = $pdo->query($q);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'labels' => array_column($rows, 'label'),
                'data' => array_map('intval', array_column($rows, 'value')),
                'label' => 'Tickets sold'
            ];
        }

        if ($table === 'marketplace_orders') {
            $q = "SELECT DATE(created_at) as label, COUNT(*) as value FROM marketplace_orders GROUP BY label ORDER BY label DESC LIMIT 30";
            $stmt = $pdo->query($q);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'labels' => array_column($rows, 'label'),
                'data' => array_map('intval', array_column($rows, 'value')),
                'label' => 'Orders/day'
            ];
        }
    } catch (Exception $e) {
        return null;
    }
    return null;
}
