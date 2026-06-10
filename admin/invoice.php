<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models/Order.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) { echo "Invalid order."; exit; }

// Fetch order
$stmt = db()->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) { echo "Order not found."; exit; }

// Fetch items
$stmt2 = db()->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt2->execute([$orderId]);
$items = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Build invoice number
$invoiceNumber = 'INV-' . strtoupper($order['order_number']);
$invoiceDate   = date('d M Y', strtotime($order['created_at']));
$invoiceTime   = date('h:i A', strtotime($order['created_at']));

// Address parse
$shippingAddr = $order['shipping_address'] ?? '';
$city         = $order['city'] ?? '';
$state        = $order['state'] ?? '';
$pincode      = $order['pincode'] ?? '';
$fullAddress  = trim($shippingAddr . ($city ? ', ' . $city : '') . ($state ? ', ' . $state : '') . ($pincode ? ' - ' . $pincode : ''));

// Amounts
$subtotal       = floatval($order['subtotal'] ?? $order['total_amount']);
$shippingFee    = floatval($order['shipping_fee'] ?? 0);
$discountAmount = floatval($order['discount_amount'] ?? 0);
$totalAmount    = floatval($order['total_amount'] ?? 0);
$promoCode      = $order['promo_code'] ?? '';
$paymentMethod  = strtoupper($order['payment_method'] ?? 'COD');
$paymentStatus  = strtoupper($order['payment_status'] ?? 'PENDING');
$orderStatus    = strtoupper($order['order_status'] ?? 'PROCESSING');

// Logo absolute path for inline base64 embed (works in print too)
$logoPath = __DIR__ . '/../assets/images/livvra-logo.png';
$logoB64  = '';
if (file_exists($logoPath)) {
    $logoB64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?php echo htmlspecialchars($invoiceNumber); ?> — LIVVRA</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; background: #f0f0f0; color: #1a1a1a; font-size: 13px; }

    /* Print controls */
    .print-controls {
        position: fixed; top: 0; left: 0; right: 0; z-index: 999;
        background: #1f3e35; padding: 12px 24px;
        display: flex; align-items: center; justify-content: space-between;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .print-controls span { color: #fff; font-size: 15px; font-weight: bold; letter-spacing: 0.5px; }
    .print-controls .btn-group { display: flex; gap: 10px; }
    .btn-print {
        background: #76a33a; color: #fff; border: none; padding: 9px 22px;
        border-radius: 5px; font-size: 13px; font-weight: bold; cursor: pointer;
        display: flex; align-items: center; gap: 6px;
    }
    .btn-back {
        background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3);
        padding: 9px 18px; border-radius: 5px; font-size: 13px; cursor: pointer; text-decoration: none;
        display: flex; align-items: center; gap: 6px;
    }
    .btn-back:hover { background: rgba(255,255,255,0.25); }
    .btn-print:hover { background: #5e8a2a; }

    /* Invoice wrapper */
    .invoice-wrapper {
        max-width: 850px; margin: 80px auto 40px; padding: 0;
        background: #fff; box-shadow: 0 4px 30px rgba(0,0,0,0.12);
    }

    /* Header */
    .inv-header {
        background: #1f3e35;
        padding: 28px 36px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .inv-header .logo-wrap {
        background: #fff;
        border-radius: 10px;
        padding: 10px 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .inv-header .logo-wrap img { width: 90px; height: auto; display: block; }
    .inv-header .company-info { text-align: right; color: #fff; }
    .inv-header .company-info h1 { font-size: 26px; font-weight: 900; letter-spacing: 2px; color: #fff; }
    .inv-header .company-info .tagline { font-size: 9px; letter-spacing: 3px; color: #bcded0; margin-top: 1px; text-transform: uppercase; }
    .inv-header .company-info p { font-size: 11px; color: rgba(255,255,255,0.8); margin-top: 6px; line-height: 1.6; }

    /* Green divider bar */
    .inv-bar {
        background: #76a33a; height: 5px;
    }

    /* Invoice title row */
    .inv-title-row {
        background: #f8fdf5; padding: 18px 36px;
        display: flex; align-items: center; justify-content: space-between;
        border-bottom: 1px solid #e5eee0;
    }
    .inv-title-row h2 { font-size: 22px; font-weight: 900; color: #1f3e35; letter-spacing: 1px; text-transform: uppercase; }
    .inv-title-row .inv-meta { text-align: right; }
    .inv-title-row .inv-meta .inv-num { font-size: 16px; font-weight: bold; color: #1f3e35; }
    .inv-title-row .inv-meta .inv-date { font-size: 12px; color: #666; margin-top: 3px; }

    /* Addresses section */
    .inv-addresses { display: flex; gap: 0; border-bottom: 1px solid #e5eee0; }
    .inv-addr-box { flex: 1; padding: 22px 36px; }
    .inv-addr-box:first-child { border-right: 1px solid #e5eee0; }
    .inv-addr-box h4 {
        font-size: 10px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase;
        color: #76a33a; margin-bottom: 10px; padding-bottom: 6px;
        border-bottom: 2px solid #76a33a; display: inline-block;
    }
    .inv-addr-box .name { font-size: 15px; font-weight: bold; color: #1f3e35; margin-bottom: 4px; }
    .inv-addr-box p { font-size: 12px; color: #444; line-height: 1.7; }
    .inv-addr-box .badge-payment {
        display: inline-block; margin-top: 8px;
        padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: bold;
    }
    .badge-paid { background: #d4edda; color: #155724; }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-cod { background: #cfe2ff; color: #084298; }
    .badge-failed { background: #f8d7da; color: #842029; }

    /* Order status badge */
    .inv-status-bar {
        background: #f8fdf5; padding: 10px 36px;
        display: flex; align-items: center; gap: 12px;
        border-bottom: 1px solid #e5eee0; font-size: 12px;
    }
    .inv-status-bar span { font-weight: bold; color: #1f3e35; }
    .status-pill {
        padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: bold;
        background: #1f3e35; color: #fff;
    }

    /* Items table */
    .inv-items { padding: 0 36px 0; }
    .inv-items table { width: 100%; border-collapse: collapse; margin-top: 0; }
    .inv-items table thead tr { background: #1f3e35; }
    .inv-items table thead th {
        padding: 12px 14px; color: #fff; font-size: 11px;
        font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; text-align: left;
    }
    .inv-items table thead th:last-child,
    .inv-items table thead th:nth-child(3),
    .inv-items table thead th:nth-child(4) { text-align: right; }
    .inv-items table tbody tr { border-bottom: 1px solid #eee; }
    .inv-items table tbody tr:nth-child(even) { background: #f9fdf7; }
    .inv-items table tbody td { padding: 13px 14px; font-size: 12.5px; vertical-align: middle; }
    .inv-items table tbody td:last-child,
    .inv-items table tbody td:nth-child(3),
    .inv-items table tbody td:nth-child(4) { text-align: right; }
    .inv-items table tbody .prod-name { font-weight: bold; color: #1f3e35; font-size: 13px; }
    .inv-items table tbody .prod-sku { font-size: 10px; color: #999; margin-top: 2px; }
    .inv-items table tfoot tr td { padding: 10px 14px; font-size: 13px; }
    .tfoot-label { text-align: right; font-weight: 600; color: #444; }
    .tfoot-value { text-align: right; font-weight: 700; color: #1f3e35; min-width: 110px; }
    .tfoot-total-row td { background: #1f3e35 !important; color: #fff !important; font-size: 15px !important; }
    .tfoot-total-row .tfoot-label { color: #bcded0 !important; font-weight: 700; }
    .tfoot-total-row .tfoot-value { color: #fff !important; font-size: 16px !important; }
    .tfoot-discount { color: #dc3545 !important; }
    .tfoot-free { color: #76a33a !important; font-weight: bold; }

    /* Bottom section */
    .inv-bottom { display: flex; gap: 0; margin: 24px 36px; }
    .inv-stamp-box {
        flex: 0 0 220px; border: 2px dashed #c8e6c9; border-radius: 8px;
        padding: 16px 18px; text-align: center; margin-right: 24px;
        min-height: 120px; display: flex; flex-direction: column;
        align-items: center; justify-content: flex-end;
    }
    .inv-stamp-box p { font-size: 10px; color: #999; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
    .inv-stamp-box .stamp-line { width: 100%; border-top: 2px solid #1f3e35; margin-top: 40px; }
    .inv-stamp-box .stamp-label { font-size: 11px; font-weight: bold; color: #1f3e35; margin-top: 5px; }
    .inv-policy-box { flex: 1; }
    .inv-policy-box h5 { font-size: 11px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; color: #1f3e35; margin-bottom: 8px; }
    .inv-policy-box ul { list-style: none; padding: 0; }
    .inv-policy-box ul li { font-size: 11px; color: #555; line-height: 1.8; padding-left: 14px; position: relative; }
    .inv-policy-box ul li::before { content: "✓"; position: absolute; left: 0; color: #76a33a; font-weight: bold; }

    /* Footer */
    .inv-footer {
        background: #1f3e35; padding: 14px 36px;
        display: flex; align-items: center; justify-content: space-between; margin-top: 10px;
    }
    .inv-footer p { font-size: 11px; color: rgba(255,255,255,0.75); }
    .inv-footer .brand { font-size: 13px; font-weight: bold; color: #bcded0; letter-spacing: 1px; }
    .inv-footer .contact-info { text-align: right; font-size: 11px; color: rgba(255,255,255,0.8); line-height: 1.7; }

    /* Watermark for paid */
    .watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 80px; font-weight: 900; color: rgba(118,163,58,0.07);
        letter-spacing: 10px; pointer-events: none; text-transform: uppercase;
        white-space: nowrap;
    }
    .inv-page { position: relative; overflow: hidden; }

    /* Print styles — force single A4 page */
    @media print {
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { background: #fff; font-size: 10px; }
        .print-controls { display: none !important; }
        .invoice-wrapper { margin: 0; box-shadow: none; max-width: 100%; page-break-after: avoid; }
        @page { margin: 6mm; size: A4; }

        /* Tighten header */
        .inv-header { padding: 12px 20px; }
        .inv-header .logo-wrap img { width: 60px; }
        .inv-header .company-info h1 { font-size: 18px; }
        .inv-header .company-info p { font-size: 9px; }
        .inv-header .company-info .tagline { font-size: 7px; }

        /* Title row */
        .inv-title-row { padding: 8px 20px; }
        .inv-title-row h2 { font-size: 16px; }
        .inv-title-row .inv-meta .inv-num { font-size: 12px; }
        .inv-title-row .inv-meta .inv-date { font-size: 9px; }

        /* Address boxes */
        .inv-addr-box { padding: 10px 20px; }
        .inv-addr-box h4 { font-size: 8px; margin-bottom: 5px; }
        .inv-addr-box .name { font-size: 11px; margin-bottom: 2px; }
        .inv-addr-box p { font-size: 9.5px; line-height: 1.45; }

        /* Status bar */
        .inv-status-bar { padding: 6px 20px; font-size: 9.5px; }

        /* Items table */
        .inv-items { padding: 0 20px; }
        .inv-items table thead th { padding: 7px 10px; font-size: 9px; }
        .inv-items table tbody td { padding: 7px 10px; font-size: 10px; }
        .inv-items table tbody .prod-name { font-size: 10px; }
        .inv-items table tbody .prod-sku { font-size: 8px; }
        .inv-items table tfoot tr td { padding: 5px 10px; font-size: 10px; }
        .tfoot-total-row td { font-size: 11px !important; }
        .tfoot-total-row .tfoot-value { font-size: 12px !important; }

        /* Bottom section */
        .inv-bottom { margin: 10px 20px; }
        .inv-stamp-box { flex: 0 0 160px; padding: 10px 12px; min-height: 80px; margin-right: 14px; }
        .inv-stamp-box p { font-size: 8px; }
        .inv-stamp-box .stamp-label { font-size: 8.5px; }
        .inv-stamp-box .stamp-line { margin-top: 30px; }
        .inv-policy-box h5 { font-size: 8.5px; margin-bottom: 5px; }
        .inv-policy-box ul li { font-size: 8.5px; line-height: 1.6; }

        /* Footer */
        .inv-footer { padding: 8px 20px; margin-top: 6px; }
        .inv-footer .brand { font-size: 10px; }
        .inv-footer p { font-size: 8.5px; }
        .inv-footer .contact-info { font-size: 8.5px; }

        /* Prevent page breaks */
        .inv-header, .inv-title-row, .inv-addresses, .inv-status-bar,
        .inv-items, .inv-bottom, .inv-footer { page-break-inside: avoid; }
        .inv-items table tbody tr { page-break-inside: avoid; }
    }
</style>
</head>
<body>

<!-- Print Controls Bar -->
<div class="print-controls">
    <span>📄 Invoice: <?php echo htmlspecialchars($invoiceNumber); ?></span>
    <div class="btn-group">
        <a href="orders.php" class="btn-back">← Back to Orders</a>
        <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
    </div>
</div>

<!-- Invoice Page -->
<div class="invoice-wrapper inv-page">

    <?php if ($paymentStatus === 'PAID'): ?>
        <div class="watermark">PAID</div>
    <?php endif; ?>

    <!-- Header -->
    <div class="inv-header">
        <div class="logo-wrap">
            <?php if ($logoB64): ?>
                <img src="<?php echo $logoB64; ?>" alt="LIVVRA Logo">
            <?php else: ?>
                <div style="color:#fff;font-size:28px;font-weight:900;letter-spacing:2px;">LIVVRA</div>
            <?php endif; ?>
        </div>
        <div class="company-info">
            <h1>LIVVRA</h1>
            <div class="tagline">Live Better Live Strong</div>
            <p>
                Dr Tridosha Herbotech Private Limited<br>
                CIN: U21003PB2025PTC066121<br>
                GSTIN: 03AAMCD1391C1Z1<br>
                20062 C, Street No. 4, Jujhar Nagar,<br>
                Bathinda, Punjab – 151001, India
            </p>
        </div>
    </div>

    <div class="inv-bar"></div>

    <!-- Invoice Title + Number -->
    <div class="inv-title-row">
        <h2>Tax Invoice</h2>
        <div class="inv-meta">
            <div class="inv-num"><?php echo htmlspecialchars($invoiceNumber); ?></div>
            <div class="inv-date">Date: <?php echo $invoiceDate; ?> at <?php echo $invoiceTime; ?></div>
            <div class="inv-date">FSSAI Lic. No.: 12121801000083</div>
        </div>
    </div>

    <!-- Addresses -->
    <div class="inv-addresses">
        <!-- Bill From / Ship From -->
        <div class="inv-addr-box">
            <h4>Sold By / Ship From</h4>
            <div class="name">Dr Tridosha Herbotech Pvt Ltd</div>
            <p>
                Trade Name: LIVVRA<br>
                20062 C, Street No. 4, Jujhar Nagar,<br>
                Bathinda, Punjab – 151001, India<br>
                📞 +91 89584 89684<br>
                ✉ livvraindia@gmail.com<br>
                🌐 www.livvra.in
            </p>
        </div>

        <!-- Ship To / Customer -->
        <div class="inv-addr-box">
            <h4>Ship To / Bill To</h4>
            <div class="name"><?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></div>
            <p>
                <?php if ($order['customer_phone']): ?>📞 <?php echo htmlspecialchars($order['customer_phone']); ?><br><?php endif; ?>
                <?php if ($order['customer_email']): ?>✉ <?php echo htmlspecialchars($order['customer_email']); ?><br><?php endif; ?>
                <?php if ($fullAddress): ?>📍 <?php echo nl2br(htmlspecialchars($fullAddress)); ?><?php endif; ?>
            </p>
            <br>
            <p>
                <strong>Payment:</strong> <?php echo $paymentMethod; ?>
                <span class="badge-payment <?php
                    echo ($paymentStatus === 'PAID') ? 'badge-paid' : (($paymentStatus === 'FAILED') ? 'badge-failed' : 'badge-pending');
                ?>"><?php echo $paymentStatus; ?></span>
            </p>
        </div>
    </div>

    <!-- Order Status Bar -->
    <div class="inv-status-bar">
        <span>Order Status:</span>
        <span class="status-pill"><?php echo $orderStatus; ?></span>
        <span style="margin-left:auto;color:#666;">Order #: <strong><?php echo htmlspecialchars($order['order_number']); ?></strong></span>
    </div>

    <!-- Items Table -->
    <div class="inv-items">
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Product</th>
                    <th style="width:70px;">Qty</th>
                    <th style="width:100px;">Unit Price</th>
                    <th style="width:110px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:#999;">No items found for this order.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $i => $item): ?>
                        <?php
                            $unitPrice = floatval($item['unit_price'] ?? $item['price'] ?? 0);
                            $itemTotal = floatval($item['total_price'] ?? $item['total'] ?? ($unitPrice * intval($item['quantity'])));
                        ?>
                        <tr>
                            <td style="color:#999;font-size:11px;"><?php echo ($i + 1); ?></td>
                            <td>
                                <div class="prod-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></div>
                                <div class="prod-sku">Product ID: <?php echo $item['product_id'] ?? '—'; ?></div>
                            </td>
                            <td><?php echo intval($item['quantity']); ?></td>
                            <td>₹<?php echo number_format($unitPrice, 2); ?></td>
                            <td>₹<?php echo number_format($itemTotal, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <!-- Subtotal -->
                <tr>
                    <td colspan="3"></td>
                    <td class="tfoot-label">Subtotal:</td>
                    <td class="tfoot-value">₹<?php echo number_format($subtotal, 2); ?></td>
                </tr>

                <!-- Shipping -->
                <tr>
                    <td colspan="3"></td>
                    <td class="tfoot-label">Shipping:</td>
                    <td class="tfoot-value <?php echo ($shippingFee <= 0) ? 'tfoot-free' : ''; ?>">
                        <?php echo ($shippingFee <= 0) ? 'FREE' : '₹' . number_format($shippingFee, 2); ?>
                    </td>
                </tr>

                <!-- Promo Discount -->
                <?php if ($discountAmount > 0): ?>
                    <tr>
                        <td colspan="3"></td>
                        <td class="tfoot-label">
                            Discount<?php if ($promoCode): ?> <span style="font-size:10px;background:#fff3cd;color:#856404;padding:1px 6px;border-radius:3px;"><?php echo htmlspecialchars($promoCode); ?></span><?php endif; ?>:
                        </td>
                        <td class="tfoot-value tfoot-discount">−₹<?php echo number_format($discountAmount, 2); ?></td>
                    </tr>
                <?php endif; ?>

                <!-- Spacer -->
                <tr><td colspan="5" style="padding:0;height:1px;background:#ddd;"></td></tr>

                <!-- Grand Total -->
                <tr class="tfoot-total-row">
                    <td colspan="3"></td>
                    <td class="tfoot-label">GRAND TOTAL:</td>
                    <td class="tfoot-value">₹<?php echo number_format($totalAmount, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Bottom: Stamp + Policy -->
    <div class="inv-bottom">
        <!-- Authorized Stamp -->
        <div class="inv-stamp-box">
            <p>Authorized Signature</p>
            <div class="stamp-line"></div>
            <div class="stamp-label">For LIVVRA / Dr Tridosha Herbotech Pvt Ltd</div>
        </div>

        <!-- Policy Box -->
        <div class="inv-policy-box">
            <h5>Terms & Policies</h5>
            <ul>
                <li>Returns accepted within 7 days of delivery (unopened/unused products).</li>
                <li>Refunds processed within 5–7 business days after return received.</li>
                <li>This is a computer-generated invoice and does not require a physical signature.</li>
                <li>For support: livvraindia@gmail.com | +91 89584 89684</li>
                <li>All disputes subject to jurisdiction of Bathinda, Punjab courts.</li>
                <li>Prices inclusive of all applicable taxes.</li>
            </ul>
        </div>
    </div>

    <!-- Notes -->
    <?php if (!empty($order['notes'])): ?>
        <div style="margin: 0 36px 16px; padding: 12px 16px; background: #f8fdf5; border-left: 4px solid #76a33a; border-radius: 0 4px 4px 0;">
            <strong style="font-size:11px;text-transform:uppercase;color:#76a33a;">Order Notes:</strong>
            <p style="font-size:12px;color:#444;margin-top:4px;"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="inv-footer">
        <div>
            <div class="brand">LIVVRA™</div>
            <p>Dr Tridosha Herbotech Private Limited</p>
            <p>www.livvra.in</p>
        </div>
        <div class="contact-info">
            <strong style="color:#bcded0;">Thank you for your order!</strong><br>
            📞 +91 89584 89684<br>
            ✉ livvraindia@gmail.com<br>
            GSTIN: 03AAMCD1391C1Z1
        </div>
    </div>

</div><!-- /invoice-wrapper -->

</body>
</html>
