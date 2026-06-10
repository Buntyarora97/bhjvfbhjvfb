<?php
/**
 * Admin: Export ALL orders + line items to CSV / Excel-compatible CSV.
 * Streams the response so it works for thousands of orders without OOM.
 * MySQL- and Postgres-compatible (uses no DB-specific syntax).
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$format = strtolower($_GET['format'] ?? 'csv');
$today  = date('Y-m-d_His');
$filename = "livvra_orders_{$today}." . ($format === 'xls' ? 'xls' : 'csv');

// Excel-friendly: tab-separated text inside a .xls wrapper triggers Excel
// to open it as a spreadsheet on every Windows / Mac.
if ($format === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
} else {
    header('Content-Type: text/csv; charset=UTF-8');
}
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// UTF-8 BOM so Excel renders ₹ and Hindi characters correctly.
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

$columns = [
    'Order #', 'Date', 'Customer Name', 'Phone', 'Email',
    'Address', 'City', 'State', 'Pincode',
    'Items (name x qty @ price)', 'Items Count',
    'Subtotal', 'Shipping', 'Discount', 'Total Paid',
    'Payment Method', 'Payment Status', 'Order Status', 'Shipment Status',
    'Shiprocket Order', 'AWB', 'Transaction ID',
    'Promo Code', 'Promo Discount',
];
fputcsv($out, $columns);

try {
    $db = db();

    // Stream orders one by one — works for any size.
    $stmt = $db->prepare("SELECT * FROM orders ORDER BY created_at DESC");
    $stmt->execute();

    // Pre-fetch promo_code_usage map (order_id -> usage row) for speed.
    $promoMap = [];
    try {
        $pStmt = $db->query("SELECT u.*, p.code AS promo_code 
                             FROM promo_code_usage u 
                             LEFT JOIN promo_codes p ON p.id = u.promo_code_id");
        if ($pStmt) {
            foreach ($pStmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
                $promoMap[$u['order_id']] = $u;
            }
        }
    } catch (Exception $e) { /* table may not exist on very old installs */ }

    // Items lookup (per order) — single roundtrip per order to keep it simple.
    $itemStmt = $db->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");

    while ($o = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $itemStmt->execute([$o['id']]);
        $rows  = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        $itemSummary = [];
        $itemCount = 0;
        foreach ($rows as $r) {
            $itemCount += (int)$r['quantity'];
            $itemSummary[] = $r['product_name'] . ' x' . $r['quantity'] . ' @ ₹' . number_format((float)$r['price'], 2);
        }
        $itemSummaryStr = implode(' | ', $itemSummary);

        $promoCode = $promoMap[$o['id']]['promo_code'] ?? '';
        $promoDisc = $promoMap[$o['id']]['discount_given'] ?? '';

        fputcsv($out, [
            $o['order_number'] ?? '',
            !empty($o['created_at']) ? date('Y-m-d H:i', strtotime($o['created_at'])) : '',
            $o['customer_name'] ?? '',
            $o['customer_phone'] ?? '',
            $o['customer_email'] ?? '',
            $o['shipping_address'] ?? '',
            $o['city'] ?? '',
            $o['state'] ?? '',
            $o['pincode'] ?? '',
            $itemSummaryStr,
            $itemCount,
            number_format((float)($o['subtotal'] ?? 0), 2, '.', ''),
            number_format((float)($o['shipping_fee'] ?? 0), 2, '.', ''),
            number_format((float)($o['discount_amount'] ?? 0), 2, '.', ''),
            number_format((float)($o['total_amount'] ?? 0), 2, '.', ''),
            strtoupper($o['payment_method'] ?? ''),
            strtoupper($o['payment_status'] ?? ''),
            strtoupper($o['order_status'] ?? ''),
            strtoupper($o['shipment_status'] ?? ''),
            $o['shiprocket_order_id'] ?? '',
            $o['awb_code'] ?? '',
            $o['transaction_id'] ?? ($o['payment_id'] ?? ''),
            $promoCode,
            $promoDisc !== '' ? number_format((float)$promoDisc, 2, '.', '') : '',
        ]);
    }
} catch (Exception $e) {
    fputcsv($out, ['ERROR exporting orders:', $e->getMessage()]);
}

fclose($out);
exit;
