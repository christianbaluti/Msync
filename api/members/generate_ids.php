<?php
// --- STRONGLY RECOMMENDED: Robust Error Handling & Output Control ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to the user
ini_set('log_errors', 1);
// Make sure this path is correct and writable by the server
ini_set('error_log', dirname(__DIR__, 2) . '/logs/php_error.log');

// Clean any previous output buffering
if (ob_get_level()) {
    ob_end_clean();
}

require_once dirname(__DIR__) . '/core/initialize.php';
// This assumes you have a vendor directory in your project root
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

// --- Helper Functions ---

function color_to_rgb(string $color_string): array {
    if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)/', $color_string, $matches)) {
        return [(int)($matches[1] ?? 0), (int)($matches[2] ?? 0), (int)($matches[3] ?? 0)];
    }
    $hex = str_replace('#', '', $color_string);
    $r = 0; $g = 0; $b = 0;
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } elseif (strlen($hex) == 6) {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return [$r, $g, $b];
}

function render_design_to_image(array $design, int $width_px, int $height_px): GdImage|bool {
    $image = imagecreatetruecolor($width_px, $height_px);
    if (!$image) return false;

    imagesavealpha($image, true);
    $bgColor = $design['backgroundColor'] ?? '#ffffff';
    [$r, $g, $b] = color_to_rgb($bgColor);
    $backgroundColor = imagecolorallocate($image, $r, $g, $b);
    imagefill($image, 0, 0, $backgroundColor);
    
    foreach ($design['objects'] as $obj) {
        $obj_type = $obj['type'] ?? '';
        
        // **FIX:** Make property access safer with null coalescing operator
        $scaleX = $obj['scaleX'] ?? 1;
        $scaleY = $obj['scaleY'] ?? 1;

        if ($obj_type === 'image' && isset($obj['src'])) {
            $imageData = @base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $obj['src']));
            if (!$imageData) continue;
            $srcImage = @imagecreatefromstring($imageData);
            if ($srcImage) {
                imagecopyresampled(
                    $image, $srcImage,
                    (int)$obj['left'], (int)$obj['top'],
                    0, 0,
                    (int)($obj['width'] * $scaleX), (int)($obj['height'] * $scaleY),
                    imagesx($srcImage), imagesy($srcImage)
                );
                imagedestroy($srcImage);
            }
        } elseif ($obj_type === 'textbox' || $obj_type === 'text') {
            [$r, $g, $b] = color_to_rgb($obj['fill'] ?? '#000000');
            $color = imagecolorallocate($image, $r, $g, $b);
            $fontFile = dirname(__DIR__, 3) . '/assets/fonts/Roboto-Regular.ttf'; 
            $text = $obj['text'] ?? '';
            $fontSize = $obj['fontSize'] ?? 12;

            if (file_exists($fontFile)) {
                imagettftext($image, $fontSize, 0, (int)$obj['left'], (int)($obj['top'] + $fontSize), $color, $fontFile, $text);
            } else {
                imagestring($image, 5, (int)$obj['left'], (int)$obj['top'], $text, $color);
            }
        } elseif ($obj_type === 'rect') {
             [$r, $g, $b] = color_to_rgb($obj['fill'] ?? '#000000');
             $color = imagecolorallocate($image, $r, $g, $b);
             imagefilledrectangle(
                 $image,
                 (int)$obj['left'], (int)$obj['top'],
                 (int)($obj['left'] + ($obj['width'] * $scaleX)),
                 (int)($obj['top'] + ($obj['height'] * $scaleY)),
                 $color
             );
        }
    }
    return $image;
}

// --- Main Script Logic ---
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.", 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON received.", 400);
    }

    $designs = $input['designs'] ?? [];
    $width_cm = (float)($input['width_cm'] ?? 8.56);
    $height_cm = (float)($input['height_cm'] ?? 5.4);

    if (empty($designs)) {
        throw new Exception('Missing design data.', 400);
    }

    $pixels_per_cm = 37.795; // 96 DPI
    $width_px = (int)($width_cm * $pixels_per_cm);
    $height_px = (int)($height_cm * $pixels_per_cm);

    $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => [$width_cm * 10, $height_cm * 10]]);
    $mpdf->SetDisplayMode('fullpage');

    foreach ($designs as $designData) {
        $front_design = json_decode($designData['front_design_json'], true);
        $back_design = json_decode($designData['back_design_json'], true);

        // **FIX:** Check if json_decode was successful before proceeding
        if (is_array($front_design)) {
            $front_image_gd = render_design_to_image($front_design, $width_px, $height_px);
            if ($front_image_gd) {
                ob_start();
                imagepng($front_image_gd);
                $front_image_data = ob_get_clean();
                imagedestroy($front_image_gd);
                $mpdf->AddPage();
                $mpdf->Image('data:image/png;base64,' . base64_encode($front_image_data), 0, 0, $width_cm * 10, $height_cm * 10, 'png', '', true, false);
            }
        }

        if (is_array($back_design) && !empty($back_design['objects'])) {
            $back_image_gd = render_design_to_image($back_design, $width_px, $height_px);
            if ($back_image_gd) {
                ob_start();
                imagepng($back_image_gd);
                $back_image_data = ob_get_clean();
                imagedestroy($back_image_gd);
                $mpdf->AddPage();
                $mpdf->Image('data:image/png;base64,' . base64_encode($back_image_data), 0, 0, $width_cm * 10, $height_cm * 10, 'png', '', true, false);
            }
        }
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="membership-cards.pdf"');
    $mpdf->Output('', Destination::INLINE);

} catch (Throwable $e) {
    $code = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($code);
    error_log('PDF Generation Failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred during PDF generation.']);
}