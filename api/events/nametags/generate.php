<?php
// --- STRONGLY RECOMMENDED: Robust Error Handling & Output Control ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__, 3) . '/error.log');

if (ob_get_level()) {
    ob_end_clean();
}

require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

// --- Helper Functions ---
function color_to_rgb(string $color_string): array {
    // This handles hex, rgb, and rgba strings
    if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)/', $color_string, $matches)) {
        return [(int)($matches[1] ?? 0), (int)($matches[2] ?? 0), (int)($matches[3] ?? 0)];
    }

    $hex = str_replace('#', '', $color_string);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return [$r, $g, $b];
}

function render_design_to_image(array $design, int $width_px, int $height_px): GdImage|bool {
    $image = imagecreatetruecolor($width_px, $height_px);
    imagesavealpha($image, true);

    $bgColor = $design['background'] ?? 'transparent';
    if ($bgColor === 'transparent') {
        $backgroundColor = imagecolorallocatealpha($image, 0, 0, 0, 127);
    } else {
        [$r, $g, $b] = color_to_rgb($bgColor);
        $backgroundColor = imagecolorallocate($image, $r, $g, $b);
    }
    imagefill($image, 0, 0, $backgroundColor);
    
    foreach ($design['objects'] as $obj) {
        if ($obj['type'] === 'image' && isset($obj['src'])) {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $obj['src']));
            $srcImage = imagecreatefromstring($imageData);
            if ($srcImage) {
                imagecopyresampled(
                    $image, $srcImage,
                    (int)$obj['left'], // CHANGED
                    (int)$obj['top'],  // CHANGED
                    0, 0,
                    (int)($obj['width'] * $obj['scaleX']),  // CHANGED
                    (int)($obj['height'] * $obj['scaleY']), // CHANGED
                    imagesx($srcImage), imagesy($srcImage)
                );
                imagedestroy($srcImage);
            }
        }
        elseif ($obj['type'] === 'textbox') {
            [$r, $g, $b] = color_to_rgb($obj['fill'] ?? '#000000');
            $color = imagecolorallocate($image, $r, $g, $b);
            $fontFile = dirname(__DIR__, 3) . '/public/assets/fonts/Roboto-Regular.ttf';
            if (file_exists($fontFile)) {
                imagettftext($image, $obj['fontSize'], 0, (int)$obj['left'], (int)($obj['top'] + $obj['fontSize']), $color, $fontFile, $obj['text']); // CHANGED
            } else {
                imagestring($image, 5, (int)$obj['left'], (int)$obj['top'], $obj['text'], $color); // CHANGED
            }
        }
        elseif ($obj['type'] === 'rect') {
            [$r, $g, $b] = color_to_rgb($obj['fill'] ?? '#000000');
            $color = imagecolorallocate($image, $r, $g, $b);
            imagefilledrectangle(
                $image,
                (int)$obj['left'], // CHANGED
                (int)$obj['top'],  // CHANGED
                (int)($obj['left'] + ($obj['width'] * $obj['scaleX'])),  // CHANGED
                (int)($obj['top'] + ($obj['height'] * $obj['scaleY'])), // CHANGED
                $color
            );
        }
    }
    return $image;
}

// --- Main Script Logic ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$designs = $input['designs'] ?? [];
$event_id = $input['event_id'] ?? 0;
$width_cm = $input['width_cm'] ?? 7;
$height_cm = $input['height_cm'] ?? 13;

if (empty($designs) || empty($event_id)) {
    http_response_code(400);
    error_log('Nametag Generation: Missing required data.');
    exit(json_encode(['message' => 'Missing required data.']));
}

try {
    $pixels_per_cm = 37.795;
    $width_px = (int)($width_cm * $pixels_per_cm);
    $height_px = (int)($height_cm * $pixels_per_cm);

    $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => [$width_cm * 10, $height_cm * 10]]);
    $mpdf->SetDisplayMode('fullpage');

    foreach ($designs as $designData) {
        $front_design = json_decode($designData['front_design_json'], true);
        $back_design = json_decode($designData['back_design_json'], true);

        // Render Front
        $front_image_gd = render_design_to_image($front_design, $width_px, $height_px);
        ob_start();
        imagepng($front_image_gd);
        $front_image_data = ob_get_clean();
        imagedestroy($front_image_gd);

        $mpdf->AddPage();
        $mpdf->Image('data:image/png;base64,' . base64_encode($front_image_data), 0, 0, $width_cm * 10, $height_cm * 10, 'png', '', true, false);

        // Render Back if it has objects
        if (!empty($back_design['objects'])) {
            $back_image_gd = render_design_to_image($back_design, $width_px, $height_px);
            ob_start();
            imagepng($back_image_gd);
            $back_image_data = ob_get_clean();
            imagedestroy($back_image_gd);

            $mpdf->AddPage();
            $mpdf->Image('data:image/png;base64,' . base64_encode($back_image_data), 0, 0, $width_cm * 10, $height_cm * 10, 'png', '', true, false);
        }
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="nametags.pdf"');
    $mpdf->Output('', Destination::INLINE);

} catch (Throwable $e) {
    http_response_code(500);
    error_log('PDF Generation Failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    exit(json_encode(['message' => 'An internal server error occurred.']));
}