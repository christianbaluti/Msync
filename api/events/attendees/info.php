<?php
/**
 * server-info.php
 *
 * Usage: place in a web-accessible folder and open from the browser.
 * SECURITY: remove this file from production / public servers after use.
 */

/* ---------- Helper functions ---------- */

/**
 * Safe wrapper to run a command if shell_exec is available and not disabled.
 */
function safe_shell_exec($cmd) {
    $disabled = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));
    if (!function_exists('shell_exec') || in_array('shell_exec', $disabled) || empty($cmd)) {
        return null;
    }
    // escape the command slightly (still be careful)
    $safe = escapeshellcmd($cmd);
    $out = @shell_exec($safe . ' 2>&1');
    return ($out === null) ? null : trim($out);
}

/**
 * Nicely format bytes
 */
function human_filesize($bytes, $decimals = 2) {
    $sz = ['B','KB','MB','GB','TB','PB'];
    $factor = (int) floor((strlen($bytes) - 1) / 3);
    if ($factor == 0) return $bytes . ' ' . $sz[0];
    return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $sz[$factor]);
}

/**
 * Safe get ini value with fallback.
 */
function ini_val($key) {
    $val = ini_get($key);
    if ($val === false || $val === '') return '<em>not set</em>';
    return htmlspecialchars($val);
}

/* ---------- Gather information ---------- */

$phpVersion = PHP_VERSION;
$sapi = php_sapi_name();
$extensions = get_loaded_extensions();
sort($extensions, SORT_NATURAL | SORT_FLAG_CASE);

$commonIniKeys = [
    'memory_limit',
    'post_max_size',
    'upload_max_filesize',
    'max_execution_time',
    'max_input_time',
    'display_errors',
    'error_reporting',
    'log_errors',
    'error_log',
    'date.timezone',
    'session.save_path',
    'open_basedir',
    'disable_functions',
    'expose_php'
];

$serverVars = $_SERVER;
$envVars = getenv() ? getenv() : null; // getenv() returns string on some envs — we will use $_ENV too
$envList = $_ENV;

/* Disk usage for document root & root */
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
$diskDocTotal = @disk_total_space($docRoot);
$diskDocFree = @disk_free_space($docRoot);
$diskRootTotal = @disk_total_space('/');
$diskRootFree = @disk_free_space('/');

/* Shell info (if allowed) */
$uname = safe_shell_exec('uname -a');
$uptime = safe_shell_exec('uptime');
$whoami = safe_shell_exec('whoami');

/* MySQL / mysqli client info if available */
$mysqli_info = null;
if (extension_loaded('mysqli')) {
    $mysqli_info = mysqli_get_client_info();
}

/* PDO drivers */
$pdo_drivers = extension_loaded('pdo') ? PDO::getAvailableDrivers() : [];

/* Safe list of superglobals to display (avoid raw POST bodies with secret tokens) */
$superglobals = [
    'GET' => $_GET,
    'POST' => (array) array_map(function($v){ return is_scalar($v) ? $v : '[complex data]'; }, $_POST),
    'COOKIE' => $_COOKIE,
    'FILES' => array_keys($_FILES ?? []),
    'ENV (PHP $_ENV)' => $envList,
];

/* ---------- Output HTML ---------- */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Server Configuration Report</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
    body{font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:#f7f9fb; color:#111; padding:24px;}
    .card{background:#fff;border-radius:10px;padding:18px;margin-bottom:18px;box-shadow:0 6px 18px rgba(20,24,30,0.06);}
    h1{margin:0 0 10px;font-size:20px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px 10px;border-bottom:1px solid #eee;text-align:left;font-size:13px;}
    th{width:280px;color:#444;font-weight:600;background:#fbfcfd}
    .muted{color:#666;font-size:13px}
    .small{font-size:12px;color:#666}
    .pill{display:inline-block;padding:4px 8px;background:#eef5ff;border-radius:999px;font-size:12px;color:#1a5fb4}
    pre{background:#0b1220;color:#e6f0ff;padding:12px;border-radius:8px;overflow:auto;font-size:13px}
    .warn{color:#8b2b2b;background:#fff0f0;padding:8px;border-radius:6px;border:1px solid #f2c6c6}
    a.btn{display:inline-block;margin-top:8px;padding:8px 12px;background:#1a73e8;color:white;border-radius:8px;text-decoration:none}
</style>
</head>
<body>

<div class="card">
    <h1>Server Configuration Report</h1>
    <div class="small muted">Generated: <?php echo date('Y-m-d H:i:s T'); ?> — remove this file after use.</div>
    <div style="margin-top:12px">
        <a class="btn" href="?full=1">Open phpinfo()</a>
        <a class="btn" href="?download=1" style="background:#2b8a3e">Download as text</a>
    </div>
</div>

<div class="card">
    <h2>PHP & Server Basics</h2>
    <table>
        <tr><th>PHP Version</th><td><?php echo htmlspecialchars($phpVersion); ?> <span class="pill"><?php echo htmlspecialchars($sapi); ?></span></td></tr>
        <tr><th>Loaded extensions</th><td><?php echo count($extensions); ?> — <?php echo htmlspecialchars(implode(', ', array_slice($extensions, 0, 12))); ?><?php if(count($extensions) > 12) echo ' ...'; ?></td></tr>
        <tr><th>mysqli client</th><td><?php echo $mysqli_info ? htmlspecialchars($mysqli_info) : '<em>mysqli not available</em>'; ?></td></tr>
        <tr><th>PDO drivers</th><td><?php echo $pdo_drivers ? htmlspecialchars(implode(', ', $pdo_drivers)) : '<em>pdo not loaded</em>'; ?></td></tr>
        <tr><th>Document root</th><td><?php echo htmlspecialchars($docRoot); ?></td></tr>
        <tr><th>Filesystem (doc root)</th>
            <td>
                <?php
                if ($diskDocTotal !== false && $diskDocFree !== false) {
                    echo human_filesize($diskDocFree) . ' free / ' . human_filesize($diskDocTotal) . ' total';
                } else {
                    echo '<em>unavailable</em>';
                }
                ?>
            </td>
        </tr>
        <tr><th>Filesystem (root /)</th>
            <td>
                <?php
                if ($diskRootTotal !== false && $diskRootFree !== false) {
                    echo human_filesize($diskRootFree) . ' free / ' . human_filesize($diskRootTotal) . ' total';
                } else {
                    echo '<em>unavailable</em>';
                }
                ?>
            </td>
        </tr>
    </table>
</div>

<div class="card">
    <h2>Important php.ini values</h2>
    <table>
    <?php foreach ($commonIniKeys as $k): ?>
        <tr><th><?php echo htmlspecialchars($k); ?></th><td><?php echo ini_val($k); ?></td></tr>
    <?php endforeach; ?>
    </table>
</div>

<div class="card">
    <h2>Environment & Server Variables</h2>
    <table>
        <tr><th>Server name</th><td><?php echo htmlspecialchars($_SERVER['SERVER_NAME'] ?? php_uname('n')); ?></td></tr>
        <tr><th>Server IP</th><td><?php echo htmlspecialchars($_SERVER['SERVER_ADDR'] ?? ($_SERVER['LOCAL_ADDR'] ?? 'unknown')); ?></td></tr>
        <tr><th>Remote IP</th><td><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'cli'); ?></td></tr>
        <tr><th>Script</th><td><?php echo htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? __FILE__); ?></td></tr>
        <tr><th>Document root</th><td><?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? ''); ?></td></tr>
    </table>
    <div class="small muted" style="margin-top:8px">Full <code>$_SERVER</code> dump below (use for debugging).</div>
    <pre><?php echo htmlspecialchars(print_r($serverVars, true)); ?></pre>
</div>

<div class="card">
    <h2>Superglobals (safe preview)</h2>
    <?php foreach ($superglobals as $name => $val): ?>
        <h3 style="margin:8px 0 6px;font-size:15px"><?php echo htmlspecialchars($name); ?></h3>
        <pre><?php echo htmlspecialchars(print_r($val, true)); ?></pre>
    <?php endforeach; ?>
</div>

<div class="card">
    <h2>Loaded PHP extensions (full)</h2>
    <pre><?php echo htmlspecialchars(implode("\n", $extensions)); ?></pre>
</div>

<div class="card">
    <h2>Optional shell info (if allowed)</h2>
    <?php if ($uname !== null || $uptime !== null || $whoami !== null): ?>
        <table>
            <tr><th>uname -a</th><td><code><?php echo htmlspecialchars($uname ?? '<not allowed>'); ?></code></td></tr>
            <tr><th>uptime</th><td><?php echo htmlspecialchars($uptime ?? '<not allowed>'); ?></td></tr>
            <tr><th>whoami</th><td><?php echo htmlspecialchars($whoami ?? '<not allowed>'); ?></td></tr>
        </table>
    <?php else: ?>
        <div class="warn">Shell execution functions are disabled in this PHP configuration (safe).</div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Quick security notes</h2>
    <ul class="small">
        <li>Do not keep this file publicly accessible. Remove it when finished.</li>
        <li>Check <code>disable_functions</code> and <code>open_basedir</code> for hardening.</li>
        <li>Consider restricting access via IP allowlist or HTTP auth while diagnosing.</li>
    </ul>
</div>

<footer class="small muted" style="margin-top:12px">Server info viewer — generated by server-info.php</footer>

<?php
// If user requested phpinfo or download, handle now (after HTML header)
if (isset($_GET['full']) && $_GET['full'] == '1') {
    // display phpinfo separately (it will output its own HTML)
    echo "<div style='margin:20px 0' class='card'><h2>phpinfo()</h2></div>";
    phpinfo();
    exit;
}

if (isset($_GET['download']) && $_GET['download'] == '1') {
    // build a text dump and force-download
    ob_start();
    echo "Server Configuration Report\nGenerated: " . date('c') . "\n\n";
    echo "PHP Version: " . PHP_VERSION . " (" . php_sapi_name() . ")\n\n";
    echo "php.ini values:\n";
    foreach ($commonIniKeys as $k) {
        echo str_pad($k, 25) . " : " . ini_get($k) . "\n";
    }
    echo "\nLoaded extensions:\n" . implode(", ", $extensions) . "\n\n";
    echo "Document root: " . $docRoot . "\n";
    if ($diskDocTotal !== false) {
        echo "Disk (doc root) free: " . human_filesize($diskDocFree) . " / " . human_filesize($diskDocTotal) . "\n";
    }
    if ($uname !== null) echo "uname -a: " . $uname . "\n";
    if ($uptime !== null) echo "uptime: " . $uptime . "\n";
    if ($whoami !== null) echo "whoami: " . $whoami . "\n";
    $content = ob_get_clean();
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="server-info.txt"');
    echo $content;
    exit;
}
?>

</body>
</html>
