<?php
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/models/Order.php';

/* =========================
   ORDER CHECK
========================= */

$orderId = $_GET['order_id'] ?? ($_SESSION['pending_order_id'] ?? 0);

if (!$orderId) {
    header('Location: cart.php');
    exit;
}

$order = Order::getById($orderId);

if (!$order) {
    header('Location: cart.php');
    exit;
}

/* =========================
   CASHFREE CONFIG
========================= */

$client_id     = trim(CASHFREE_CLIENT_ID);
$client_secret = trim(CASHFREE_CLIENT_SECRET);
$mode          = CASHFREE_ENV;

$api_url = ($mode === 'PROD')
    ? "https://api.cashfree.com/pg/orders"
    : "https://sandbox.cashfree.com/pg/orders";

/* =========================
   ORDER DATA
   IMPORTANT: total_amount in DB already = subtotal + shipping - discount.
   We send THAT to Cashfree so the gateway charges the discounted amount.
   We also pass an order_note + order_tags so the discount is visible
   in the Cashfree dashboard / receipt.
========================= */

$subtotal      = (float)($order['subtotal'] ?? 0);
$shipping_fee  = (float)($order['shipping_fee'] ?? 0);
$discount_amt  = (float)($order['discount_amount'] ?? 0);
$db_total      = (float)$order['total_amount'];

// Defensive: re-derive expected total and prefer the smaller of the two
// (so we never charge the customer more than what their summary showed).
$expected_total = max(0, round($subtotal + $shipping_fee - $discount_amt, 2));
$charge_amount  = ($expected_total > 0 && $expected_total < $db_total) ? $expected_total : $db_total;
$order_amount   = number_format($charge_amount, 2, '.', '');

$cf_order_id  = $order['order_number'] . "_" . time();

$note_parts = [];
$note_parts[] = "Subtotal ₹" . number_format($subtotal, 2);
if ($shipping_fee > 0) $note_parts[] = "Shipping ₹" . number_format($shipping_fee, 2);
if ($discount_amt > 0) $note_parts[] = "Discount -₹" . number_format($discount_amt, 2);
$note_parts[] = "Payable ₹" . $order_amount;
$order_note = implode(' | ', $note_parts);

$order_tags = [
    "order_number" => (string)$order['order_number'],
    "subtotal"     => (string)number_format($subtotal, 2, '.', ''),
    "shipping"     => (string)number_format($shipping_fee, 2, '.', ''),
    "discount"     => (string)number_format($discount_amt, 2, '.', ''),
    "site"         => "livvra.in",
];

$order_data = [
    "order_id"       => $cf_order_id,
    "order_amount"   => $order_amount,
    "order_currency" => "INR",
    "order_note"     => substr($order_note, 0, 150),
    "order_tags"     => $order_tags,
    "customer_details" => [
        "customer_id"    => "CUST_" . $orderId,
        "customer_name"  => $order['customer_name'],
        "customer_email" => $order['customer_email'] ?: "customer@example.com",
        "customer_phone" => $order['customer_phone']
    ],
    "order_meta" => [
        "return_url" => "https://livvra.in/cashfree-return.php?order_id={$orderId}&cf_order_id={$cf_order_id}",
        "notify_url" => "https://livvra.in/cashfree-notify.php"
    ]
];

error_log("[CASHFREE INIT] order=" . $order['order_number']
    . " subtotal=" . $subtotal
    . " ship=" . $shipping_fee
    . " disc=" . $discount_amt
    . " db_total=" . $db_total
    . " expected=" . $expected_total
    . " charged=" . $order_amount);

/* =========================
   CURL REQUEST
========================= */

$headers = [
    "Content-Type: application/json",
    "x-api-version: 2022-09-01",
    "x-client-id: {$client_id}",
    "x-client-secret: {$client_secret}"
];

$ch = curl_init($api_url);

curl_setopt_array($ch, [
    CURLOPT_POST            => true,
    CURLOPT_POSTFIELDS      => json_encode($order_data),
    CURLOPT_HTTPHEADER      => $headers,
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_TIMEOUT         => 30,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error     = curl_error($ch);

curl_close($ch);

/* =========================
   DEBUG HANDLING
========================= */

$result = json_decode($response, true);

if ($http_code !== 200 || empty($result['payment_session_id'])) {

    echo "<pre>";
    echo "HTTP CODE: " . $http_code . "\n\n";
    echo "CURL ERROR: " . $error . "\n\n";
    echo "RESPONSE: " . $response;
    exit;
}

$payment_session_id = $result['payment_session_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to Payment</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
</head>

<body style="font-family:Arial;text-align:center;padding-top:120px;background:#f7f7f7;">

<h2>Redirecting to Secure Payment...</h2>

<button id="payBtn" style="
    padding:15px 30px;
    background:#C9A227;
    color:#fff;
    border:none;
    border-radius:6px;
    font-size:16px;
    cursor:pointer;
">
Proceed to Payment
</button>

<script>

const cashfree = Cashfree({
    mode: "<?php echo ($mode === 'PROD') ? 'production' : 'sandbox'; ?>"
});

function startPayment() {
    cashfree.checkout({
        paymentSessionId: "<?php echo $payment_session_id; ?>",
        redirectTarget: "_self"
    });
}

setTimeout(startPayment, 600);
document.getElementById("payBtn").onclick = startPayment;

</script>

</body>
</html>