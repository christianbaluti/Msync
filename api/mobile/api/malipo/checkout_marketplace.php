<?php
// server/api/malipo/checkout_marketplace.php
// This endpoint demonstrates initiating a MALIPO payment and redirecting the user
// to MALIPO hosted checkout (or rendering a page that triggers the flow).

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/../auth_middleware.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    http_response_code(400);
    echo 'Invalid order_id';
    exit;
}

// Load order and invoice data
$stmt = $pdo->prepare("SELECT o.id, o.total_amount, i.id AS invoice_id FROM marketplace_orders o
    JOIN invoices i ON i.related_type = 'marketplace_order' AND i.related_id = o.id
    WHERE o.id = ?");
$stmt->execute([$order_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(404);
    echo 'Order not found';
    exit;
}

$amount = (float)$row['total_amount'];
$invoice_id = (int)$row['invoice_id'];

// MALIPO parameters (placeholder demo). In production, read from env/config.
$merchant_id = getenv('MALIPO_MERCHANT_ID') ?: '8f65d175c3707080e7aa1ef7b05680c8';
$callback_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/malipo/callback_marketplace.php';
$reference = 'INV-' . $invoice_id;

// In a real integration, you would make a server-side request to MALIPO
// to create a checkout session and get a redirect URL. For now, we render
// a very simple page that could POST to MALIPO or instruct the client.
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MALIPO Checkout</title>
    <style>
      body { font-family: Arial, sans-serif; padding: 24px; }
      .card { max-width: 520px; margin: 0 auto; border: 1px solid #ddd; border-radius: 12px; padding: 16px; }
      .btn { display: inline-block; background: #0D6EFD; color: #fff; padding: 12px 16px; border-radius: 8px; text-decoration: none; }
    </style>
  </head>
  <body>
    <div class="card">
      <h2>Proceed to Payment</h2>
      <p>Amount: MWK <?php echo number_format($amount, 2); ?></p>
      <p>Reference: <?php echo htmlspecialchars($reference, ENT_QUOTES, 'UTF-8'); ?></p>
      <p>This demo page represents the MALIPO hosted checkout step.</p>
      <p>Integrate per <a href="https://malipo.mw/documentation/" target="_blank" rel="noopener">MALIPO documentation</a>.</p>
      <a class="btn" href="#" onclick="alert('Integrate MALIPO redirect or JS SDK here.'); return false;">Pay with MALIPO</a>
    </div>
  </body>
</html>