<?php
// api/report_api.php
require_once __DIR__ . '/../core/initialize.php'; // your bootstrap: sessions, db, helpers
require_once __DIR__ . '/report_helpers.php';
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
/*
if (!function_exists('has_permission') || !has_permission('reports_read')) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}*/

$pdo =  (new Database())->connect(); // implemented in your initialize.php (PDO)
$payloadRaw = file_get_contents('php://input');

// For preview (JSON POST) OR export (form POST with payload field)
if ($action === 'preview') {
    $data = json_decode($payloadRaw, true);
    if (!$data) { http_response_code(400); echo "Invalid JSON"; exit; }
    // CSRF check
    if (empty($data['csrf']) || !hash_equals($_SESSION['csrf_token'] ?? '', $data['csrf'])) {
        http_response_code(400); echo "Invalid CSRF"; exit;
    }
    $result = handle_preview($pdo, $data);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

if ($action === 'export') {
    // Expect payload from form POST (name=payload)
    $payload = $_POST['payload'] ?? null;
    $csrf = $_POST['csrf'] ?? '';
    if (!$payload) { http_response_code(400); echo "Missing payload"; exit; }
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        http_response_code(400); echo "Invalid CSRF"; exit;
    }
    $data = json_decode($payload, true);
    if (!$data) { http_response_code(400); echo "Invalid payload"; exit; }
    $format = $data['format'] ?? 'csv';
    // sanitize format
    if (!in_array($format, ['csv','excel','pdf'])) { http_response_code(400); echo "Invalid format"; exit; }

    // Build the query and fetch data (no limit)
    $params_out = [];
    //$sql = build_query($pdo, $input, $params_out);


    $q = build_query($pdo, $data, $params_out);
    $stmt = $pdo->prepare($q);
    $stmt->execute($params_out);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($format === 'csv') {
        export_csv_stream($rows, $data['includeHeader'] ?? 1);
    } elseif ($format === 'excel') {
        export_excel_stream($rows, $data['includeHeader'] ?? 1);
    } else {
        // PDF: render HTML then convert to PDF
        $html = render_html_for_pdf($rows, $data, $data['includeHeader'] ?? 1);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $pdfOutput = $dompdf->output();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="report.pdf"');
        echo $pdfOutput;
    }
    exit;
}

http_response_code(404);
echo "Unknown action";
exit;


// ------ Handler functions (use helpers in includes/report_helpers.php) ------

function handle_preview(PDO $pdo, array $data): array {
    // Build query with a preview limit and possibly aggregated data for chart
    $limit = 1000;
    $params_out = [];
    $data['preview'] = true; // signal to builder
    $q = build_query($pdo, $data, $params_out, $limit);
    $stmt = $pdo->prepare($q);
    $stmt->execute($params_out);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build columns (preserve order)
    $columns = [];
    if (!empty($rows)) {
        $columns = array_keys($rows[0]);
    } else {
        // if fields selected, use those
        if (!empty($data['fields'])) {
            $columns = $data['fields'];
        }
    }

    // attempt to prepare chart data for a "smart chart"
    $chartInfo = generate_smart_chart($pdo, $data);

    return ['columns' => $columns, 'rows' => $rows, 'chart' => $chartInfo];
}
