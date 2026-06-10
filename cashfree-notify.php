<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/models/Order.php';

/* ============================================
   READ RAW INPUT
============================================ */

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

/* ============================================
   HANDLE EMPTY OR TEST WEBHOOK
============================================ */

if (empty($data) || (isset($data['type']) && $data['type'] === 'WEBHOOK_TEST')) {
    http_response_code(200);
    echo "Webhook OK";
    exit;
}

/* ============================================
   VALIDATE STRUCTURE
============================================ */

if (
    !isset($data['type']) ||
    !isset($data['data']['order']['order_id'])
) {
    http_response_code(200);
    echo "Invalid payload";
    exit;
}

$type = $data['type'];
$cf_order_id = $data['data']['order']['order_id'];

/* ============================================
   EXTRACT MAIN ORDER NUMBER
   (REMOVES TIMESTAMP IF ADDED)
============================================ */

$parts = explode('_', $cf_order_id);
$order_number = $parts[0];

/* ============================================
   FETCH ORDER FROM DATABASE
============================================ */

$stmt = db()->prepare("SELECT id, payment_status FROM orders WHERE order_number = ?");
$stmt->execute([$order_number]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(200);
    echo "Order not found";
    exit;
}

/* ============================================
   PREVENT DUPLICATE SUCCESS UPDATE
============================================ */

if ($order['payment_status'] === 'paid') {
    http_response_code(200);
    echo "Already processed";
    exit;
}

/* ============================================
   GET PAYMENT STATUS SAFELY
============================================ */

$payment_status = $data['data']['payment']['payment_status'] ?? '';

/* ============================================
   UPDATE BASED ON WEBHOOK TYPE
============================================ */

if ($type === 'PAYMENT_SUCCESS_WEBHOOK' && $payment_status === 'SUCCESS') {

    Order::updatePaymentStatus($order['id'], 'paid');

    // Idempotent promo usage recording — only if not already logged for this order.
    try {
        $chk = db()->prepare("SELECT id FROM promo_code_usage WHERE order_id = ? LIMIT 1");
        $chk->execute([$order['id']]);
        if (!$chk->fetch()) {
            $ord = db()->prepare("SELECT * FROM orders WHERE id = ?");
            $ord->execute([$order['id']]);
            $orow = $ord->fetch(PDO::FETCH_ASSOC);
            $disc = (float)($orow['discount_amount'] ?? 0);
            if ($disc > 0) {
                // Try to associate by the most recently used active promo
                // matching the discount amount on this order's subtotal.
                // Best-effort — keeps schema additions to zero.
                $sub = (float)($orow['subtotal'] ?? 0);
                if ($sub > 0) {
                    $pct = round(($disc / $sub) * 100, 2);
                    $pStmt = db()->prepare("SELECT id, code FROM promo_codes WHERE discount_type = 'percentage' AND ABS(discount_value - ?) < 0.5 ORDER BY id DESC LIMIT 1");
                    $pStmt->execute([$pct]);
                    $pRow = $pStmt->fetch(PDO::FETCH_ASSOC);
                    if ($pRow) {
                        db()->prepare("UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?")
                            ->execute([$pRow['id']]);
                        db()->prepare("INSERT INTO promo_code_usage 
                            (promo_code_id, order_id, order_number, customer_name, customer_phone, order_total, discount_given, commission_earned)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 0)")
                        ->execute([
                            $pRow['id'], $order['id'], $orow['order_number'],
                            $orow['customer_name'], $orow['customer_phone'],
                            $orow['total_amount'], $disc
                        ]);
                    }
                }
            }
        }
    } catch (Exception $e) { error_log("[CASHFREE NOTIFY] promo log err: " . $e->getMessage()); }

    echo "Payment confirmed";

} elseif ($type === 'PAYMENT_FAILED_WEBHOOK' && $payment_status === 'FAILED') {

    Order::updatePaymentStatus($order['id'], 'failed');
    echo "Payment failed";

} elseif ($payment_status === 'USER_DROPPED' || $payment_status === 'CANCELLED') {

    Order::updatePaymentStatus($order['id'], 'failed');
    echo "Payment cancelled";

} else {

    echo "Webhook received";
}

http_response_code(200);
exit;