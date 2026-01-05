<?php
// view_material.php
// Secure file proxy to force download of training materials.

set_time_limit(0);
require_once dirname(__DIR__, 3) . '/core/initialize.php';

$material_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$material_id) {
    http_response_code(400);
    echo "Bad Request: No material ID provided.";
    exit;
}

try {
    $db = (new Database())->connect();

    // 1. Fetch material details
    $stmt = $db->prepare("SELECT title, type, url FROM training_materials WHERE id = :id");
    $stmt->execute([':id' => $material_id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$material || empty($material['url'])) {
        http_response_code(404);
        echo "Not Found: The requested material does not exist or has no associated file.";
        exit;
    }
    
    // Quick guard: this script is not for links.
    if ($material['type'] === 'link') {
        http_response_code(400);
        echo "Bad Request: This material is a link and not a downloadable file.";
        exit;
    }

    // 2. Build server filesystem path robustly
    $public_base = dirname(__DIR__, 4) . '/public';
    $storedUrl = ltrim($material['url'], "/");
    $file_path = $public_base . '/' . $storedUrl;

    if (!file_exists($file_path) || !is_file($file_path)) {
        http_response_code(404);
        echo "Not Found: The file could not be found on the server.";
        exit;
    }

    // 3. Determine MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);
    if (!$mime_type) {
        $mime_type = 'application/octet-stream'; // Generic fallback
    }
    
    // ============= CHANGE IS HERE =============
    // 4. Prepare for download
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    // Sanitize the material title to create a safe, user-friendly filename.
    $sanitized_title = preg_replace('/[^a-zA-Z0-9\s\._-]+/', '', $material['title']);
    $download_filename = $sanitized_title . '.' . $extension;


    // 5. Support for HTTP Range (so large files can be paused/resumed)
    $filesize = filesize($file_path);
    $start = 0;
    $length = $filesize;
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
            $start = $matches[1] !== '' ? (int)$matches[1] : 0;
            $end = $matches[2] !== '' ? (int)$matches[2] : $filesize - 1;
            if ($end > $filesize - 1) $end = $filesize - 1;
            if ($start > $end) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                exit;
            }
            $length = $end - $start + 1;
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes {$start}-{$end}/{$filesize}");
        }
    }

    // 6. Send headers to trigger download
    header_remove(); 
    header('Content-Type: ' . $mime_type);
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $length);
    header('Content-Disposition: attachment; filename="' . $download_filename . '"'); // Force download
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');


    // 7. Stream the file (efficiently)
    $fp = fopen($file_path, 'rb');
    if ($fp === false) {
        http_response_code(500);
        echo "Server Error: Could not open file for reading.";
        exit;
    }
    // Seek to the starting position for ranged requests
    if ($start > 0) fseek($fp, $start);

    $bufferSize = 8192; // 8KB chunks
    $bytesSent = 0;
    while (!feof($fp) && $bytesSent < $length) {
        $read = min($bufferSize, $length - $bytesSent);
        echo fread($fp, $read);
        flush();
        $bytesSent += $read;
    }
    fclose($fp);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    // Log $e->getMessage() in production
    echo "Server Error: " . $e->getMessage();
    exit;
}