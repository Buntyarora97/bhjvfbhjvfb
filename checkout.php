<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/models/Order.php';
require_once __DIR__ . '/includes/models/Product.php';

// DEBUG: Log all POST data for troubleshooting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== CHECKOUT DEBUG ===");
    error_log("POST Data: " . print_r($_POST, true));
    error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'Guest'));
}

$cartItems = $_SESSION['cart'] ?? [];
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Calculate totals with discount tracking
$subtotal = 0;
$totalOriginal = 0;
$totalSavings = 0;

foreach ($cartItems as $item) {
    $itemTotal = $item['price'] * $item['quantity'];
    $itemOriginal = ($item['original_price'] ?? $item['price']) * $item['quantity'];
    
    $subtotal += $itemTotal;
    $totalOriginal += $itemOriginal;
    $totalSavings += ($itemOriginal - $itemTotal);
}

$shipping = getShippingFee($subtotal);

// =============================================================
// PROMO CODE preview (read session-applied promo set on cart.php)
// This is JUST the live preview shown in the order summary.
// On POST, we re-validate against DB to be the source of truth.
// =============================================================
$preview_promo_discount = 0;
$preview_applied_promo  = $_SESSION['applied_promo'] ?? null;
if ($preview_applied_promo && !empty($preview_applied_promo['code'])) {
    try {
        $stmt = db()->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE) AND (usage_limit = 0 OR used_count < usage_limit)");
        $stmt->execute([$preview_applied_promo['code']]);
        $_p = $stmt->fetch();
        if ($_p && $subtotal >= (float)$_p['min_order_amount']) {
            if ($_p['discount_type'] === 'percentage') {
                $preview_promo_discount = ($subtotal * (float)$_p['discount_value']) / 100;
                if ((float)$_p['max_discount'] > 0) $preview_promo_discount = min($preview_promo_discount, (float)$_p['max_discount']);
            } else {
                $preview_promo_discount = (float)$_p['discount_value'];
            }
            $preview_promo_discount = min($preview_promo_discount, $subtotal);
        } else {
            $preview_applied_promo = null;
        }
    } catch (Exception $e) { $preview_applied_promo = null; }
}

$total = $subtotal + $shipping - $preview_promo_discount;
if ($total < 0) $total = 0;

// ── PREPAID 5% DISCOUNT PREVIEW ──
$prepaid_discount_pct = 5;
$preview_prepaid_discount = 0;
$preview_payment = $_POST['payment_method'] ?? ($formData['payment_method'] ?? 'cashfree');
// Preview is only for GET display; actual calculation happens on POST below

$success = false;
$orderNumber = '';
$error = '';

// ✅ FIX: Initialize variables to prevent undefined index errors
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'address_2' => '',
    'landmark' => '',
    'city' => '',
    'state' => '',
    'pincode' => '',
    'payment_method' => 'cashfree'
];

// ✅ FIX: Properly capture POST data with sanitization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['address'] = trim($_POST['address'] ?? '');
    $formData['address_2'] = trim($_POST['address_2'] ?? '');
    $formData['landmark'] = trim($_POST['landmark'] ?? '');
    $formData['city'] = trim($_POST['city'] ?? '');
    $formData['state'] = trim($_POST['state'] ?? '');
    $formData['pincode'] = trim($_POST['pincode'] ?? '');
    $formData['payment_method'] = $_POST['payment_method'] ?? 'cashfree';
    
    // ✅ VALIDATION: Check all required fields
    $required_fields = ['name', 'phone', 'address', 'city', 'state', 'pincode'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($formData[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $error = 'Please fill in all required fields: ' . implode(', ', $missing_fields);

    // ── COD OTP VERIFICATION ──
    } elseif ($formData['payment_method'] === 'cod') {
        $otp_entered  = trim($_POST['cod_otp'] ?? '');
        $session_otp  = $_SESSION['cod_otp'] ?? null;

        if (empty($session_otp)) {
            $error = 'OTP verification is required for Cash on Delivery. Please request an OTP first.';
        } elseif ($session_otp['phone'] !== $formData['phone']) {
            $error = 'OTP was sent to a different phone number. Please re-enter your phone number and request a new OTP.';
        } elseif ($session_otp['expires'] < time()) {
            $error = 'OTP has expired. Please request a new OTP.';
            unset($_SESSION['cod_otp']);
        } elseif (empty($otp_entered) || $session_otp['otp'] !== $otp_entered) {
            $error = 'Incorrect OTP. Please check and try again.';
        } else {
            unset($_SESSION['cod_otp']); // OTP verified — clear
        }
        if (!empty($error)) { goto cod_error_skip; }
    }

    if (empty($error)) {
        try {
            // ==========================================================
            // PROMO CODE: SINGLE source of truth = DB.  Applied ONCE on
            // the cart subtotal (not per item, not per qty).  Read from
            // either the session (set on cart.php) or the form field.
            // We DO NOT increment used_count here — that happens after
            // the order is actually paid (cashfree-return / cashfree-notify
            // for online, or here for COD only).
            // ==========================================================
            $promo_discount = 0;
            $promoCode = '';
            $applied_promo = null;

            $pcode = '';
            if (!empty($_POST['promo_code'])) {
                $pcode = strtoupper(trim($_POST['promo_code']));
            } elseif (!empty($_SESSION['applied_promo']['code'])) {
                $pcode = strtoupper($_SESSION['applied_promo']['code']);
            }

            if ($pcode !== '') {
                $stmt = db()->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE) AND (usage_limit = 0 OR used_count < usage_limit)");
                $stmt->execute([$pcode]);
                $promo = $stmt->fetch();

                if ($promo && $subtotal >= (float)$promo['min_order_amount']) {
                    $promoCode = $pcode;
                    $applied_promo = $promo;

                    if ($promo['discount_type'] === 'percentage') {
                        // ONE discount on the cart subtotal — never per item
                        $promo_discount = ($subtotal * (float)$promo['discount_value']) / 100;
                        if ((float)($promo['max_discount'] ?? 0) > 0) {
                            $promo_discount = min($promo_discount, (float)$promo['max_discount']);
                        }
                    } else {
                        $promo_discount = (float)$promo['discount_value'];
                    }
                    // Hard cap: discount can never exceed subtotal
                    $promo_discount = min($promo_discount, $subtotal);
                    $promo_discount = round($promo_discount, 2);

                    $total = $subtotal + $shipping - $promo_discount;
                    if ($total < 0) $total = 0;
                }
            }

            // Reward Coins Logic
            $reward_discount = 0;
            if (isset($_SESSION['user_id']) && !empty($_POST['use_reward_coins'])) {
                $uid = $_SESSION['user_id'];
                $stmt = db()->prepare("SELECT balance FROM reward_coins WHERE user_id = ?");
                $stmt->execute([$uid]);
                $balance = (int)$stmt->fetchColumn();
                
                if ($balance > 0) {
                    $redeem_rate = (float)(Setting::get('reward_redeem_rate', 1) ?: 1);
                    $reward_discount = $balance * $redeem_rate;
                    $reward_discount = min($reward_discount, $total);
                    $total -= $reward_discount;
                    
                    db()->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description) VALUES (?, ?, 'redeemed', 'Used at checkout')")
                        ->execute([$uid, $balance]);
                    db()->prepare("UPDATE reward_coins SET balance = 0 WHERE user_id = ?")->execute([$uid]);
                }
            }

            // ── PREPAID 5% DISCOUNT (online payment only) ──
            $prepaid_discount = 0;
            if ($formData['payment_method'] !== 'cod') {
                $prepaid_discount = round($total * ($prepaid_discount_pct / 100), 2);
                $total = max(0, $total - $prepaid_discount);
            }

            // ✅ CRITICAL FIX: Build complete address string for Shiprocket
            $fullAddress = $formData['address'];
            if (!empty($formData['address_2'])) {
                $fullAddress .= ', ' . $formData['address_2'];
            }
            if (!empty($formData['landmark'])) {
                $fullAddress .= ' (Near: ' . $formData['landmark'] . ')';
            }

            // ✅ DEBUG: Log the actual address being used
            error_log("Shipping Address being saved: " . $fullAddress);
            error_log("City: " . $formData['city'] . ", State: " . $formData['state'] . ", Pin: " . $formData['pincode']);

            $orderData = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'customer_name' => $formData['name'],
                'customer_email' => $formData['email'],
                'customer_phone' => $formData['phone'],
                'shipping_address' => $fullAddress,
                'address_line_1' => $formData['address'],
                'address_line_2' => $formData['address_2'],
                'landmark' => $formData['landmark'],
                'pincode' => $formData['pincode'],
                'city' => $formData['city'],
                'state' => $formData['state'],
                'country' => 'India',
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'discount_amount' => $promo_discount + $reward_discount + $prepaid_discount,
                'total_amount' => $total,
                'payment_method' => $formData['payment_method'],
                'order_note' => ($prepaid_discount > 0 ? "5% Prepaid Discount Applied: -₹{$prepaid_discount}. " : ''),
                'order_tags' => ($prepaid_discount > 0 ? 'prepaid-discount' : ($formData['payment_method'] === 'cod' ? 'cod' : '')),
                'items' => array_values($cartItems),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // ✅ DEBUG: Log complete order data
            error_log("Complete Order Data: " . print_r($orderData, true));
            
            $orderId = Order::create($orderData);
            
            if ($orderId) {
                $order = Order::getById($orderId);
                $orderNumber = $order['order_number'];

                // ====================================================
                // Promo accounting:
                //   - COD orders: increment used_count + log usage NOW
                //     (the order is real, customer commits to it)
                //   - Online orders (cashfree/instamojo/payu/upi):
                //     stash the promo info in session and let the
                //     payment-return / webhook record usage AFTER the
                //     payment actually succeeds.  Prevents fake usage.
                // ====================================================
                if ($applied_promo && $promo_discount > 0) {
                    $commission = 0;
                    $cType = $applied_promo['commission_type'] ?? 'percentage';
                    $cVal  = (float)($applied_promo['commission_value'] ?? 0);
                    if ($cType === 'percentage') {
                        $commission = ($total * $cVal) / 100;
                    } else {
                        $commission = $cVal;
                    }
                    $commission = round($commission, 2);

                    if ($formData['payment_method'] === 'cod') {
                        try {
                            db()->prepare("UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?")
                                ->execute([$applied_promo['id']]);
                            db()->prepare("INSERT INTO promo_code_usage 
                                (promo_code_id, order_id, order_number, customer_name, customer_phone, order_total, discount_given, commission_earned)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                            ->execute([
                                $applied_promo['id'], $orderId, $orderNumber,
                                $formData['name'], $formData['phone'],
                                $total, $promo_discount, $commission
                            ]);
                        } catch (Exception $e) { error_log("Promo COD usage save error: " . $e->getMessage()); }
                    } else {
                        // For online payments — record AFTER payment succeeds
                        $_SESSION['order_promo_pending'][$orderId] = [
                            'promo_id'        => $applied_promo['id'],
                            'code'            => $applied_promo['code'],
                            'discount'        => $promo_discount,
                            'commission'      => $commission,
                            'customer_name'   => $formData['name'],
                            'customer_phone'  => $formData['phone'],
                            'order_total'     => $total,
                        ];
                    }
                }

                // Promo applied successfully → drop from cart session so
                // it can't accidentally be re-used on the next order.
                unset($_SESSION['applied_promo']);

                // ✅ DEBUG: Verify what was actually saved
                error_log("Order Created Successfully - Order ID: " . $orderId . ", Order No: " . $orderNumber);
                error_log("Saved Address in DB: " . ($order['shipping_address'] ?? 'NOT FOUND'));
                error_log("Promo applied: " . ($promoCode ?: '(none)') . " | Discount: ₹" . $promo_discount . " | Final total: ₹" . $total);
                
                // ✅ CRITICAL: Clear any cached address data in session
                unset($_SESSION['temp_shipping_address']);
                unset($_SESSION['last_used_address']);
                if (isset($_SESSION['pending_order_id'])) {
                    unset($_SESSION['pending_order_id']);
                }
                
                if ($formData['payment_method'] === 'cod') {
                    // Store order details in session for the thank you page
                    $_SESSION['cod_order_success'] = [
                        'order_number'    => $orderNumber,
                        'order_id'        => $orderId,
                        'customer_name'   => $formData['name'],
                        'customer_email'  => $formData['email'],
                        'customer_phone'  => $formData['phone'],
                        'shipping_address'=> $fullAddress,
                        'city'            => $formData['city'],
                        'state'           => $formData['state'],
                        'pincode'         => $formData['pincode'],
                        'items'           => array_values($cartItems),
                        'subtotal'        => $subtotal,
                        'shipping'        => $shipping,
                        'promo_discount'  => $promo_discount,
                        'reward_discount' => $reward_discount,
                        'total'           => $total,
                        'payment_method'  => 'Cash on Delivery',
                        'time'            => date('Y-m-d H:i:s'),
                    ];
                    unset($_SESSION['cart']);
                    header('Location: order-success.php?order=' . urlencode($orderNumber));
                    exit;
                } else if ($formData['payment_method'] === 'cashfree') {
                    $_SESSION['pending_order_id'] = $orderId;
                    header('Location: cashfree-init.php?order_id=' . $orderId);
                    exit;
                } else if ($formData['payment_method'] === 'instamojo') {
                    $_SESSION['pending_order_id'] = $orderId;
                    header('Location: instamojo-init.php?order_id=' . $orderId);
                    exit;
                } else if ($formData['payment_method'] === 'payu') {
                    $_SESSION['pending_order_id'] = $orderId;
                    header('Location: payu-init.php?order_id=' . $orderId);
                    exit;
                } else if ($formData['payment_method'] === 'upi_manual') {
                    $_SESSION['pending_order_id'] = $orderId;
                    header('Location: upi-payment.php?order_id=' . $orderId);
                    exit;
                }
            } else {
                $error = 'Failed to create order. Please try again.';
                error_log("ERROR: Order::create returned false");
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            error_log("EXCEPTION in checkout: " . $e->getMessage());
        }
    }
    cod_error_skip:
}

$pageTitle = 'Checkout';
require_once 'includes/header.php';

// ── Meta Pixel: InitiateCheckout fires immediately on page load ──────────
?>
<script>
(function() {
    var fireInitCheckout = function() {
        if (typeof fbq === 'function') {
            fbq('track', 'InitiateCheckout', {
                value: <?php echo json_encode((float)($total ?? 0)); ?>,
                currency: 'INR'
            });
        }
    };
    // Fire right away if fbq ready, else queue via setTimeout fallback
    if (typeof fbq === 'function') {
        fireInitCheckout();
    } else {
        var t = setInterval(function() {
            if (typeof fbq === 'function') { fireInitCheckout(); clearInterval(t); }
        }, 100);
    }
})();
</script>
<?php

// Fetch best products for bottom section
$bestProducts = Product::getFeatured(8); 
?>

<style>
/* ===== CSS VARIABLES ===== */
:root {
    --primary: #0f3d2e;
    --primary-light: #1a5f4a;
    --accent: #C9A227;
    --accent-light: #d4b43a;
    --purple: #6B21A8;
    --purple-light: #7C3AED;
    --danger: #e53935;
    --success: #10b981;
    --bg: #f4f4f8;
    --card-bg: #ffffff;
    --text: #1a1a1a;
    --text-muted: #6b7280;
    --border: #e5e7eb;
    --shadow: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
    --radius: 16px;
    --radius-sm: 10px;
    --transition: all 0.25s ease;
}

/* ===== BASE STYLES ===== */
.checkout-wrapper {
    background: var(--bg);
    padding: 0 0 80px;
    min-height: 100vh;
}

/* Prepaid top banner */
.prepaid-top-banner {
    background: linear-gradient(90deg, #5B21B6, #7C3AED);
    color: #fff;
    text-align: center;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.02em;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 16px;
}

/* ===== PROGRESS STEPS ===== */
.checkout-progress {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 28px;
    padding-top: 28px;
    gap: 16px;
}

.progress-step {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-muted);
}

.progress-step.active { color: var(--purple); }
.progress-step.completed { color: var(--success); }

.step-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    transition: var(--transition);
}

.progress-step.active .step-number {
    background: var(--purple);
    color: white;
    box-shadow: 0 0 0 3px rgba(107,33,168,0.2);
}

.progress-step.completed .step-number {
    background: var(--success);
    color: white;
}

.step-line {
    width: 50px;
    height: 2px;
    background: var(--border);
    border-radius: 1px;
}
.step-line.completed { background: var(--success); }

/* ===== CHECKOUT GRID ===== */
.checkout-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 24px;
    align-items: start;
}

/* ===== CHECKOUT CARDS ===== */
.checkout-card {
    background: var(--card-bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 24px;
    margin-bottom: 16px;
    border: 1px solid var(--border);
}

.checkout-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

/* ===== FORM STYLES ===== */
.form-group-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

.form-group { margin-bottom: 16px; }

.form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 7px;
}
.form-label .required { color: var(--danger); margin-left: 3px; }

.form-control {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 0.9375rem;
    transition: var(--transition);
    background: #fafafa;
    font-family: inherit;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: var(--purple);
    background: white;
    box-shadow: 0 0 0 3px rgba(107,33,168,0.1);
}
.form-control::placeholder { color: #adb5bd; }
.form-control.error { border-color: var(--danger); background: #fef2f2; }
.form-control.success { border-color: var(--success); background: #f0fdf4; }

.validation-message { font-size: 0.75rem; margin-top: 5px; display: flex; align-items: center; gap: 4px; }
.validation-message.error { color: var(--danger); }
.validation-message.success { color: var(--success); }

/* ===== GOODMONK-STYLE PAYMENT METHOD ROWS ===== */
.payment-methods-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 16px;
}

.pm-row {
    position: relative;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 14px 16px;
    cursor: pointer;
    transition: var(--transition);
    background: white;
    display: flex;
    align-items: center;
    gap: 14px;
}

.pm-row:hover { border-color: #a78bfa; background: #faf5ff; }

.pm-row.active {
    border-color: var(--purple);
    background: #faf5ff;
    box-shadow: 0 0 0 3px rgba(107,33,168,0.08);
}

.pm-row input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.pm-row-radio {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: var(--transition);
}
.pm-row.active .pm-row-radio {
    border-color: var(--purple);
    background: var(--purple);
}
.pm-row.active .pm-row-radio::after {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: white;
}

.pm-row-icon {
    width: 44px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 22px;
}

.pm-row-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.pm-row-info {
    flex: 1;
    min-width: 0;
}

.pm-row-name {
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 2px;
}

.pm-row-desc {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.pm-discount-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #065f46;
    background: #d1fae5;
    padding: 2px 8px;
    border-radius: 20px;
}

.pm-row-right {
    text-align: right;
    flex-shrink: 0;
}

.pm-row-price {
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--text);
}
.pm-row-price.discounted { color: var(--purple); }

.pm-row-arrow {
    color: #9ca3af;
    font-size: 18px;
    margin-left: 6px;
}

/* UPI icon row images */
.upi-icons {
    display: flex;
    gap: 3px;
    margin-top: 4px;
    align-items: center;
}
.upi-icon-dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}

/* Manual UPI panel */
.manual-upi-panel {
    display: none;
    background: #f5f3ff;
    border: 1.5px dashed #a78bfa;
    border-radius: 12px;
    padding: 18px;
    margin-top: 8px;
}
.manual-upi-panel.active { display: block; }

.upi-id-display {
    background: white;
    border: 1.5px solid #e9d5ff;
    border-radius: 10px;
    padding: 14px 16px;
    text-align: center;
    margin-bottom: 14px;
}
.upi-id-label { font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
.upi-id-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--purple);
    letter-spacing: 0.03em;
}
.upi-holder-name { font-size: 13px; color: var(--text-muted); margin-top: 2px; }

.upi-amount-box {
    background: #ede9fe;
    border-radius: 8px;
    padding: 10px 14px;
    text-align: center;
    margin-bottom: 14px;
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--purple);
}

.upi-proof-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}
.upi-tab {
    flex: 1;
    padding: 8px;
    text-align: center;
    border-radius: 8px;
    border: 1.5px solid #d1d5db;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    background: white;
    color: var(--text-muted);
}
.upi-tab.active {
    border-color: var(--purple);
    background: var(--purple);
    color: white;
}

.upi-proof-utr { display: block; }
.upi-proof-screenshot { display: none; }

.screenshot-upload-area {
    border: 2px dashed #c4b5fd;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    background: white;
}
.screenshot-upload-area:hover { background: #faf5ff; border-color: var(--purple); }
.screenshot-upload-area input[type="file"] { display: none; }
.screenshot-preview { max-width: 100%; max-height: 150px; border-radius: 8px; margin-top: 10px; }

/* ===== ORDER SUMMARY SIDEBAR ===== */
.order-summary-card {
    background: var(--card-bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    position: sticky;
    top: 16px;
    overflow: hidden;
    border: 1px solid var(--border);
}

.summary-header {
    padding: 16px 20px;
    background: linear-gradient(90deg, #5B21B6, #7C3AED);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-header h3 { font-size: 1rem; font-weight: 700; margin: 0; }
.item-count { font-size: 0.8125rem; opacity: 0.9; }

.summary-items { padding: 16px 20px; }

.summary-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}
.summary-item:last-child { border-bottom: none; }

.summary-item-img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    background: #f9fafb;
    border: 1px solid var(--border);
    flex-shrink: 0;
}

.summary-item-info { flex: 1; min-width: 0; }

.summary-item-name {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text);
    margin-bottom: 3px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.summary-item-meta { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px; }

.item-price-breakdown { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.item-final-price { font-weight: 700; color: var(--primary); font-size: 0.875rem; }
.item-original-price { font-size: 0.75rem; color: var(--text-muted); text-decoration: line-through; }
.item-savings { font-size: 0.7rem; color: var(--success); font-weight: 600; background: rgba(16,185,129,0.1); padding: 2px 6px; border-radius: 20px; }

/* ===== PROMO CODE SECTION ===== */
.promo-section {
    padding: 14px 20px;
    border-top: 1px solid var(--border);
    background: #fafafa;
}

.promo-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    background: none;
    border: none;
    color: var(--purple);
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    padding: 0;
}
.promo-toggle svg { width: 18px; height: 18px; transition: var(--transition); }
.promo-toggle.active svg { transform: rotate(180deg); }

.promo-input-wrapper { display: none; gap: 8px; margin-top: 10px; }
.promo-input-wrapper.active { display: flex; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

.promo-input {
    flex: 1;
    padding: 10px 12px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 0.875rem;
    text-transform: uppercase;
    font-weight: 600;
    transition: var(--transition);
    box-sizing: border-box;
}
.promo-input:focus { outline: none; border-color: var(--purple); }

.promo-apply-btn {
    padding: 10px 16px;
    background: var(--purple);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    font-size: 0.875rem;
    white-space: nowrap;
    transition: var(--transition);
}
.promo-apply-btn:hover { background: var(--purple-light); }

/* ===== PRICE BREAKDOWN ===== */
.price-breakdown {
    padding: 14px 20px;
    border-top: 1px solid var(--border);
}
.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 0.875rem;
}
.price-row .label { color: var(--text-muted); font-weight: 500; }
.price-row .value { font-weight: 600; color: var(--text); }
.price-row.savings .value { color: var(--success); }
.price-row.discount .value { color: #059669; font-weight: 700; }
.price-divider { height: 1px; background: var(--border); margin: 12px 0; }
.price-row.total { font-size: 1.1rem; font-weight: 800; padding-top: 10px; border-top: 2px solid var(--border); }
.price-row.total .value { color: var(--primary); }

.savings-highlight {
    background: #ecfdf5;
    border-radius: 8px;
    padding: 10px 12px;
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8125rem;
    color: var(--success);
    font-weight: 600;
    border-left: 3px solid var(--success);
}

/* ===== PLACE ORDER BUTTON ===== */
.place-order-section {
    padding: 16px 20px 20px;
    background: white;
    border-top: 1px solid var(--border);
}

.place-order-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #5B21B6, #7C3AED);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 14px rgba(107,33,168,0.35);
    letter-spacing: 0.01em;
}
.place-order-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(107,33,168,0.4); }
.place-order-btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none !important; }
.place-order-btn svg { width: 18px; height: 18px; }

.security-badges {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 12px;
    flex-wrap: wrap;
}
.badge-item { display: flex; align-items: center; gap: 5px; font-size: 0.7rem; color: var(--text-muted); font-weight: 500; }
.badge-item svg { width: 14px; height: 14px; color: var(--success); }

/* ===== SUCCESS MESSAGE ===== */
.success-container {
    text-align: center;
    padding: 60px 20px;
    background: var(--card-bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    max-width: 580px;
    margin: 40px auto;
}
.success-icon {
    width: 100px; height: 100px;
    background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px;
    box-shadow: 0 8px 24px rgba(16,185,129,0.3);
}
.success-icon svg { width: 50px; height: 50px; color: white; }
.success-title { font-size: 1.75rem; font-weight: 700; color: var(--text); margin-bottom: 12px; }
.success-message { font-size: 1rem; color: var(--text-muted); margin-bottom: 24px; line-height: 1.6; }
.order-number {
    display: inline-block;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white; padding: 10px 24px; border-radius: 50px;
    font-weight: 700; font-size: 1.125rem; margin: 16px 0;
}
.continue-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 36px;
    background: linear-gradient(135deg, #5B21B6, #7C3AED);
    color: white; text-decoration: none; border-radius: 50px;
    font-weight: 700; transition: var(--transition);
}
.continue-btn:hover { transform: translateY(-2px); }

/* ===== ERROR MESSAGE ===== */
.error-alert {
    background: #fef2f2; border: 1px solid #fecaca; color: var(--danger);
    padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px; font-weight: 600;
}
.error-alert svg { width: 22px; height: 22px; flex-shrink: 0; }

/* ===== TRUST BADGES ===== */
.trust-section {
    margin-top: 12px; padding: 16px; background: white;
    border-radius: var(--radius); text-align: center; border: 1px solid var(--border);
}
.trust-title { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.trust-icons { display: flex; justify-content: center; gap: 24px; flex-wrap: wrap; }
.trust-icon { display: flex; flex-direction: column; align-items: center; gap: 6px; font-size: 0.7rem; color: var(--text-muted); font-weight: 500; }
.trust-icon svg { width: 26px; height: 26px; color: var(--primary); }

/* ===== STICKY MOBILE SUMMARY ===== */
.sticky-mobile-summary {
    display: none; position: fixed; bottom: 0; left: 0; right: 0;
    background: white; padding: 14px 20px;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.1); z-index: 1000;
    border-top: 3px solid var(--purple);
    transform: translateY(100%); transition: transform 0.3s ease;
}
.sticky-mobile-summary.visible { transform: translateY(0); }
.sticky-content { display: flex; justify-content: space-between; align-items: center; max-width: 1240px; margin: 0 auto; }
.sticky-price { display: flex; flex-direction: column; }
.sticky-total { font-size: 1.125rem; font-weight: 800; color: var(--purple); }
.sticky-text { font-size: 0.8rem; color: var(--text-muted); }
.sticky-btn { padding: 12px 28px; background: linear-gradient(135deg, #5B21B6, #7C3AED); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 0.9rem; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .checkout-container { grid-template-columns: 1fr; }
    .order-summary-card { position: static; }
}
@media (max-width: 768px) {
    .checkout-progress { gap: 8px; }
    .step-line { width: 24px; }
    .progress-step span:not(.step-number) { display: none; }
    .form-group-grid { grid-template-columns: 1fr; }
    .checkout-card { padding: 18px; }
    .sticky-mobile-summary { display: block; }
    .place-order-section { display: none; }
}
@media (max-width: 480px) {
    .container { padding: 0 12px; }
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: #f1f1f1; }
::-webkit-scrollbar-thumb { background: var(--purple); border-radius: 3px; }

/* ===== LOADING STATE ===== */
.btn-loading { position: relative; color: transparent !important; pointer-events: none; }
.btn-loading::after {
    content: ''; position: absolute;
    width: 20px; height: 20px; top: 50%; left: 50%;
    margin-left: -10px; margin-top: -10px;
    border: 2px solid #ffffff; border-radius: 50%;
    border-top-color: transparent;
    animation: spinner 0.8s linear infinite;
}
@keyframes spinner { to { transform: rotate(360deg); } }

/* ===== ADDRESS PREVIEW ===== */
.address-preview {
    background: #f5f3ff; border: 1.5px solid #ddd6fe;
    border-radius: var(--radius-sm); padding: 14px; margin-top: 16px;
    font-size: 0.875rem; line-height: 1.6;
}
.address-preview strong { color: var(--purple); display: block; margin-bottom: 6px; font-size: 0.9rem; }

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; } to { opacity: 1; }
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-8px); }
    75% { transform: translateX(8px); }
}
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<div class="prepaid-top-banner">
        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        Additional 5% Off On Prepaid Orders
        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    </div>

    <div class="checkout-wrapper">
    <div class="container">
        <?php if ($success): ?>
            <div class="success-container">
                <div class="success-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <h2 class="success-title">Order Placed Successfully!</h2>
                <p class="success-message">Thank you for your purchase. Your order has been confirmed and will be processed shortly.</p>
                <div class="order-number">#<?php echo htmlspecialchars($orderNumber); ?></div>
                <a href="index.php" class="continue-btn">
                    Continue Shopping
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        <?php else: ?>

            <!-- Progress Steps -->
            <div class="checkout-progress">
                <div class="progress-step completed"><span class="step-number">✓</span><span>Cart</span></div>
                <div class="step-line completed"></div>
                <div class="progress-step active"><span class="step-number">2</span><span>Checkout</span></div>
                <div class="step-line"></div>
                <div class="progress-step"><span class="step-number">3</span><span>Confirmation</span></div>
            </div>

            <?php if ($error): ?>
                <div class="error-alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="checkout-form" class="checkout-container" novalidate enctype="multipart/form-data">
                <div class="checkout-main">

                    <!-- Contact Details -->
                    <div class="checkout-card">
                        <h3 class="checkout-section-title">
                            <span class="section-icon">📞</span>
                            Contact Details
                        </h3>
                        <div class="form-group-grid">
                            <div class="form-group">
                                <label class="form-label">Phone Number <span class="required">*</span></label>
                                <input type="tel" name="phone" class="form-control" placeholder="Enter 10-digit mobile number"
                                       value="<?php echo htmlspecialchars($formData['phone']); ?>"
                                       required pattern="[0-9]{10}" maxlength="10" inputmode="numeric" id="phone">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="your@email.com"
                                       value="<?php echo htmlspecialchars($formData['email']); ?>" id="email">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Enter your full name"
                                   value="<?php echo htmlspecialchars($formData['name']); ?>" required id="name">
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="checkout-card">
                        <h3 class="checkout-section-title">
                            <span class="section-icon">📍</span>
                            Delivery Address
                        </h3>
                        <div class="form-group">
                            <label class="form-label">House No., Building, Street <span class="required">*</span></label>
                            <input type="text" name="address" class="form-control" placeholder="House No., Building Name, Street"
                                   value="<?php echo htmlspecialchars($formData['address']); ?>" required id="address">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apartment / Floor (Optional)</label>
                            <input type="text" name="address_2" class="form-control" placeholder="Apartment, Suite, Floor, etc."
                                   value="<?php echo htmlspecialchars($formData['address_2']); ?>" id="address_2">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Landmark (Optional)</label>
                            <input type="text" name="landmark" class="form-control" placeholder="Nearby landmark"
                                   value="<?php echo htmlspecialchars($formData['landmark']); ?>" id="landmark">
                        </div>
                        <div class="form-group-grid">
                            <div class="form-group">
                                <label class="form-label">PIN Code <span class="required">*</span></label>
                                <input type="text" name="pincode" class="form-control" placeholder="6-digit PIN"
                                       value="<?php echo htmlspecialchars($formData['pincode']); ?>"
                                       required pattern="[0-9]{6}" maxlength="6" inputmode="numeric" id="pincode">
                            </div>
                            <div class="form-group">
                                <label class="form-label">City <span class="required">*</span></label>
                                <input type="text" name="city" class="form-control" placeholder="City name"
                                       value="<?php echo htmlspecialchars($formData['city']); ?>" required id="city">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">State <span class="required">*</span></label>
                            <input type="text" name="state" class="form-control" placeholder="State name"
                                   value="<?php echo htmlspecialchars($formData['state']); ?>" required id="state">
                        </div>
                        <div class="address-preview" id="addressPreview" style="display:none;">
                            <strong>📦 Delivery Address Preview:</strong>
                            <div id="previewContent"></div>
                        </div>
                    </div>

                    <!-- Payment Methods — GoodMonk Style -->
                    <div class="checkout-card">
                        <h3 class="checkout-section-title">
                            <span class="section-icon">💳</span>
                            Payment Method
                        </h3>

                        <div id="prepaidBadge" style="background:#ecfdf5;border:1.5px solid #6ee7b7;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:13px;font-weight:700;color:#065f46;display:flex;align-items:center;gap:8px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            Pay Online &amp; get <strong style="color:#059669;">5% Prepaid Discount</strong> automatically applied!
                        </div>

                        <div class="payment-methods-list">

                            <!-- UPI -->
                            <label class="pm-row active payment-method-item" id="pm-upi" onclick="selectPm(this,'cashfree')">
                                <input type="radio" name="payment_method" value="cashfree" checked onchange="updatePaymentUI(this)">
                                <span class="pm-row-radio"></span>
                                <span class="pm-row-icon">📲</span>
                                <div class="pm-row-info">
                                    <div class="pm-row-name">Pay via UPI</div>
                                    <div class="pm-row-desc">PayTM, PhonePe, GPay, WhatsApp &amp; More</div>
                                    <span class="pm-discount-badge">⚡ Extra 5% off</span>
                                </div>
                                <span class="pm-row-arrow">›</span>
                            </label>

                            <!-- COD -->
                            <label class="pm-row payment-method-item" id="pm-cod" onclick="selectPm(this,'cod')">
                                <input type="radio" name="payment_method" value="cod" onchange="updatePaymentUI(this)">
                                <span class="pm-row-radio"></span>
                                <span class="pm-row-icon">🏠</span>
                                <div class="pm-row-info">
                                    <div class="pm-row-name">Cash on Delivery</div>
                                    <div class="pm-row-desc">Pay cash when your order arrives</div>
                                    <span style="font-size:11px;color:#9ca3af;">OTP verification required</span>
                                </div>
                                <span class="pm-row-arrow">›</span>
                            </label>

                            <!-- Wallets -->
                            <label class="pm-row payment-method-item" id="pm-wallets" onclick="selectPm(this,'cashfree')">
                                <input type="radio" name="payment_method" value="cashfree" onchange="updatePaymentUI(this)">
                                <span class="pm-row-radio"></span>
                                <span class="pm-row-icon">👛</span>
                                <div class="pm-row-info">
                                    <div class="pm-row-name">Wallets</div>
                                    <div class="pm-row-desc">Amazon Pay, Paytm, PayZapp &amp; more</div>
                                    <span class="pm-discount-badge">⚡ Extra 5% off</span>
                                </div>
                                <span class="pm-row-arrow">›</span>
                            </label>

                            <!-- Cards -->
                            <label class="pm-row payment-method-item" id="pm-cards" onclick="selectPm(this,'cashfree')">
                                <input type="radio" name="payment_method" value="cashfree" onchange="updatePaymentUI(this)">
                                <span class="pm-row-radio"></span>
                                <span class="pm-row-icon">💳</span>
                                <div class="pm-row-info">
                                    <div class="pm-row-name">Debit / Credit Card</div>
                                    <div class="pm-row-desc">Visa, Mastercard, RuPay &amp; all cards</div>
                                    <span class="pm-discount-badge">⚡ Extra 5% off</span>
                                </div>
                                <span class="pm-row-arrow">›</span>
                            </label>

                            <!-- Netbanking -->
                            <label class="pm-row payment-method-item" id="pm-nb" onclick="selectPm(this,'cashfree')">
                                <input type="radio" name="payment_method" value="cashfree" onchange="updatePaymentUI(this)">
                                <span class="pm-row-radio"></span>
                                <span class="pm-row-icon">🏦</span>
                                <div class="pm-row-info">
                                    <div class="pm-row-name">Netbanking</div>
                                    <div class="pm-row-desc">All major banks supported</div>
                                    <span class="pm-discount-badge">⚡ Extra 5% off</span>
                                </div>
                                <span class="pm-row-arrow">›</span>
                            </label>

                            <!-- EMI -->
                            <label class="pm-row payment-method-item" id="pm-emi" onclick="selectPm(this,'cashfree')">
                                <input type="radio" name="payment_method" value="cashfree" onchange="updatePaymentUI(this)">
                                <span class="pm-row-radio"></span>
                                <span class="pm-row-icon">📅</span>
                                <div class="pm-row-info">
                                    <div class="pm-row-name">EMI</div>
                                    <div class="pm-row-desc">No-cost EMI on cards &amp; Bajaj Finserv</div>
                                    <span class="pm-discount-badge">⚡ Extra 5% off</span>
                                </div>
                                <span class="pm-row-arrow">›</span>
                            </label>

                            <!-- Manual UPI -->
                            <label class="pm-row payment-method-item" id="pm-upi-manual" onclick="selectPm(this,'upi_manual')">
                                <input type="radio" name="payment_method" value="upi_manual" onchange="updatePaymentUI(this)">
                                <span class="pm-row-radio"></span>
                                <span class="pm-row-icon" style="font-size:18px;">🔖</span>
                                <div class="pm-row-info">
                                    <div class="pm-row-name">Pay via UPI ID (Manual)</div>
                                    <div class="pm-row-desc">Pay directly to our UPI ID &amp; share reference</div>
                                    <span class="pm-discount-badge">⚡ Extra 5% off</span>
                                </div>
                                <span class="pm-row-arrow">›</span>
                            </label>

                        </div><!-- /payment-methods-list -->

                        <!-- Manual UPI panel (shown when upi_manual is selected) -->
                        <div class="manual-upi-panel" id="manualUpiPanel">
                            <div class="upi-id-display">
                                <div class="upi-id-label">Pay to this UPI ID</div>
                                <div class="upi-id-value"><?php echo htmlspecialchars(UPI_ID); ?></div>
                                <div class="upi-holder-name"><?php echo htmlspecialchars(UPI_HOLDER_NAME); ?></div>
                            </div>
                            <div class="upi-amount-box" id="upiAmountDisplay">
                                ₹<?php echo number_format($total, 2); ?>
                            </div>
                            <p style="font-size:12px;color:#6b7280;text-align:center;margin:0 0 12px;">After paying, share your payment proof below</p>

                            <div class="upi-proof-tabs">
                                <button type="button" class="upi-tab active" onclick="switchUpiTab('utr',this)">📝 Enter UTR / Ref ID</button>
                                <button type="button" class="upi-tab" onclick="switchUpiTab('screenshot',this)">📷 Upload Screenshot</button>
                            </div>

                            <div class="upi-proof-utr" id="upiProofUtr">
                                <label class="form-label" style="font-size:13px;">Transaction / UTR Reference Number</label>
                                <input type="text" name="upi_reference" id="upiReference" class="form-control"
                                    placeholder="e.g. 123456789012"
                                    style="font-size:1rem;font-weight:700;text-align:center;letter-spacing:2px;">
                            </div>

                            <div class="upi-proof-screenshot" id="upiProofScreenshot">
                                <div class="screenshot-upload-area" onclick="document.getElementById('upiScreenshotFile').click()">
                                    <input type="file" name="upi_screenshot" id="upiScreenshotFile" accept="image/*" onchange="previewScreenshot(this)">
                                    <div id="screenshotPlaceholder">
                                        <div style="font-size:32px;margin-bottom:8px;">📷</div>
                                        <div style="font-weight:600;color:#6b7280;font-size:14px;">Click to upload payment screenshot</div>
                                        <div style="font-size:12px;color:#9ca3af;margin-top:4px;">JPG, PNG or WebP — max 5MB</div>
                                    </div>
                                    <img id="screenshotPreviewImg" class="screenshot-preview" src="" alt="" style="display:none;">
                                </div>
                            </div>
                        </div>

                    </div><!-- /checkout-card payment -->

                    <!-- Trust Badges -->
                    <div class="trust-section">
                        <div class="trust-title">Secure Checkout Guaranteed</div>
                        <div class="trust-icons">
                            <div class="trust-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                <span>SSL Secure</span>
                            </div>
                            <div class="trust-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                <span>100% Safe</span>
                            </div>
                            <div class="trust-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                <span>Fast Delivery</span>
                            </div>
                        </div>
                    </div>

                </div><!-- /checkout-main -->

                <!-- Order Summary Sidebar -->
                <div class="checkout-sidebar">
                    <div class="order-summary-card">
                        <div class="summary-header">
                            <h3>Order Summary</h3>
                            <span class="item-count"><?php echo count($cartItems); ?> item<?php echo count($cartItems) > 1 ? 's' : ''; ?></span>
                        </div>

                        <div class="summary-items">
                            <?php foreach ($cartItems as $item):
                                $itemTotal    = $item['price'] * $item['quantity'];
                                $itemOriginal = ($item['original_price'] ?? $item['price']) * $item['quantity'];
                                $itemSavings  = $itemOriginal - $itemTotal;
                                $hasDiscount  = $itemSavings > 0;
                            ?>
                            <div class="summary-item">
                                <img src="uploads/products/<?php echo htmlspecialchars($item['image']); ?>"
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="summary-item-img"
                                     onerror="this.src='assets/images/placeholder.jpg'">
                                <div class="summary-item-info">
                                    <div class="summary-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <?php if (!empty($item['variant_name'])): ?>
                                        <div class="summary-item-meta"><?php echo htmlspecialchars($item['variant_name']); ?></div>
                                    <?php endif; ?>
                                    <div class="summary-item-meta">Qty: <?php echo $item['quantity']; ?></div>
                                    <div class="item-price-breakdown">
                                        <span class="item-final-price">₹<?php echo number_format($itemTotal, 2); ?></span>
                                        <?php if ($hasDiscount): ?>
                                            <span class="item-original-price">₹<?php echo number_format($itemOriginal, 2); ?></span>
                                            <span class="item-savings">-₹<?php echo number_format($itemSavings, 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Promo Code -->
                        <div class="promo-section">
                            <button type="button" class="promo-toggle" onclick="togglePromo()">
                                <span><?php echo $preview_applied_promo ? '🏷 Promo: ' . htmlspecialchars($preview_applied_promo['code']) . ' (change?)' : '🏷 Have a promo code?'; ?></span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                            <div class="promo-input-wrapper<?php echo $preview_applied_promo ? ' active' : ''; ?>" id="promoWrapper">
                                <input type="text" name="promo_code" class="promo-input" placeholder="Enter code" maxlength="20"
                                       value="<?php echo htmlspecialchars($preview_applied_promo['code'] ?? ''); ?>">
                                <button type="button" class="promo-apply-btn" onclick="applyPromo()"><?php echo $preview_applied_promo ? '✓ Applied' : 'Apply'; ?></button>
                            </div>
                            <?php if ($preview_applied_promo): ?>
                                <div class="promo-msg" style="margin-top:8px;padding:8px 12px;background:#d1fae5;color:#065f46;border-radius:6px;font-size:12px;font-weight:600;">
                                    🎉 <strong><?php echo htmlspecialchars($preview_applied_promo['code']); ?></strong> — saving ₹<?php echo number_format($preview_promo_discount, 2); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="price-breakdown" data-subtotal="<?php echo $subtotal; ?>">
                            <div class="price-row">
                                <span class="label">Subtotal</span>
                                <span class="value">₹<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <?php if ($totalSavings > 0): ?>
                            <div class="price-row savings">
                                <span class="label">Product Savings</span>
                                <span class="value" style="color:#059669;">-₹<?php echo number_format($totalSavings, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="price-row">
                                <span class="label">Shipping</span>
                                <span class="value" style="<?php echo $shipping == 0 ? 'color:var(--success);font-weight:700;' : ''; ?>">
                                    <?php echo $shipping == 0 ? '🚚 FREE' : '₹' . number_format($shipping, 2); ?>
                                </span>
                            </div>
                            <div class="price-row discount" id="promo-discount-row" style="<?php echo $preview_promo_discount > 0 ? '' : 'display:none;'; ?>">
                                <span class="label">🏷 Promo<?php echo $preview_applied_promo ? ' (' . htmlspecialchars($preview_applied_promo['code']) . ')' : ''; ?></span>
                                <span class="value" id="promo-discount-display" style="color:#059669;font-weight:700;"><?php echo $preview_promo_discount > 0 ? '-₹' . number_format($preview_promo_discount, 2) : ''; ?></span>
                            </div>
                            <div class="price-row discount" id="prepaid-discount-row" style="display:none;">
                                <span class="label">⚡ 5% Prepaid Discount</span>
                                <span class="value" id="prepaid-discount-display" style="color:#059669;font-weight:700;"></span>
                            </div>
                            <div class="price-divider"></div>
                            <div class="price-row total">
                                <span class="label">Total Payable</span>
                                <span class="value" id="order-total-display">₹<?php echo number_format($total, 2); ?></span>
                            </div>
                            <?php if ($totalSavings > 0): ?>
                            <div class="savings-highlight">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                                <span>You saved ₹<?php echo number_format($totalSavings, 2); ?> on this order!</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Place Order Button -->
                        <div class="place-order-section">
                            <button type="submit" class="place-order-btn" id="placeOrderBtn">
                                <span>Place Order</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            </button>
                            <div class="security-badges">
                                <div class="badge-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    <span>Secure Payment</span>
                                </div>
                                <div class="badge-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                    <span>Data Protected</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </form>

            <!-- ═══════════════════════════════════════════════════════════
                 COD OTP VERIFICATION MODAL
            ═══════════════════════════════════════════════════════════ -->
            <div id="codOtpOverlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
                <div id="codOtpModal" style="background:#fff;border-radius:20px;padding:32px 28px;max-width:400px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,0.25);position:relative;animation:slideUpModal .3s ease;">
                    <button onclick="closeCodOtpModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:22px;color:#888;cursor:pointer;">✕</button>

                    <!-- Step 1: Send OTP -->
                    <div id="codOtpStep1">
                        <div style="text-align:center;margin-bottom:20px;">
                            <div style="width:64px;height:64px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px;">🔒</div>
                            <h3 style="margin:0;color:#0f3d2e;font-size:20px;">Verify Your Order</h3>
                            <p style="margin:8px 0 0;color:#6b7280;font-size:14px;">OTP aapke <strong>WhatsApp</strong> aur <strong>email</strong> dono pe bheja jayega</p>
                        </div>
                        <div style="background:#f8faf9;border-radius:12px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:10px;">
                            <span style="font-size:18px;">📱</span>
                            <div>
                                <div style="font-size:12px;color:#6b7280;">Mobile Number</div>
                                <div id="codOtpPhoneDisplay" style="font-weight:700;color:#0f3d2e;font-size:15px;"></div>
                            </div>
                        </div>
                        <div id="codOtpEmailDisplay" style="background:#f8faf9;border-radius:12px;padding:10px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
                            <span style="font-size:18px;">📧</span>
                            <div>
                                <div style="font-size:12px;color:#6b7280;">Email</div>
                                <div id="codOtpEmailText" style="font-weight:700;color:#0f3d2e;font-size:14px;">—</div>
                            </div>
                        </div>
                        <button onclick="sendCodOtp()" id="sendOtpBtn" style="width:100%;background:linear-gradient(135deg,#0f3d2e,#1a5f4a);color:#fff;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:700;cursor:pointer;transition:.2s;box-shadow:0 4px 14px rgba(15,61,46,.25);">
                            💬 WhatsApp + Email pe OTP Bhejo
                        </button>
                        <div id="otpSendMsg" style="margin-top:12px;text-align:center;font-size:13px;display:none;"></div>
                    </div>

                    <!-- Step 2: Enter OTP -->
                    <div id="codOtpStep2" style="display:none;">
                        <div style="text-align:center;margin-bottom:20px;">
                            <div style="width:64px;height:64px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px;">🔐</div>
                            <h3 style="margin:0;color:#0f3d2e;font-size:20px;">Enter OTP</h3>
                            <p id="otpSentInfo" style="margin:8px 0 0;color:#6b7280;font-size:13px;"></p>
                        </div>
                        <div style="margin-bottom:16px;">
                            <input type="text" id="codOtpInput" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                                placeholder="Enter 6-digit OTP"
                                style="width:100%;border:2px solid #e5e7eb;border-radius:12px;padding:14px 16px;font-size:20px;text-align:center;letter-spacing:8px;font-weight:700;color:#0f3d2e;box-sizing:border-box;outline:none;transition:.2s;"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)">
                        </div>
                        <button onclick="verifyCodOtp()" id="verifyOtpBtn" style="width:100%;background:#0f3d2e;color:#fff;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:700;cursor:pointer;">
                            ✅ Verify &amp; Place Order
                        </button>
                        <div style="text-align:center;margin-top:12px;">
                            <button onclick="resendCodOtp()" id="resendOtpBtn" style="background:none;border:none;color:#0f3d2e;font-size:13px;cursor:pointer;text-decoration:underline;">Resend OTP</button>
                            <span id="resendTimer" style="font-size:12px;color:#888;display:none;"></span>
                        </div>
                        <div id="otpVerifyMsg" style="margin-top:10px;text-align:center;font-size:13px;display:none;"></div>
                    </div>
                </div>
            </div>

            <style>
            @keyframes slideUpModal { from { transform:translateY(30px); opacity:0; } to { transform:translateY(0); opacity:1; } }
            #codOtpInput:focus { border-color:#0f3d2e; box-shadow:0 0 0 3px rgba(15,61,46,.1); }
            #sendOtpBtn:hover, #verifyOtpBtn:hover { background:#1a5f4a; }
            </style>

            <!-- ✅ CRITICAL FIX: Sticky Mobile Summary - onclick fixed -->
            <div class="sticky-mobile-summary" id="stickySummary">
                <div class="sticky-content">
                    <div class="sticky-price">
                        <span class="sticky-total">₹<?php echo number_format($total, 2); ?></span>
                        <span class="sticky-text">Total Payable</span>
                    </div>
                    <!-- ✅ FIXED: Direct form submit instead of function call -->
                    <button type="button" class="sticky-btn" id="mobilePlaceOrderBtn">
                        Place Order
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ✅ CRITICAL FIX: Wrap everything in DOMContentLoaded and use unique namespace
document.addEventListener('DOMContentLoaded', function() {
    
    // ✅ CRITICAL FIX: Check if main.js has validateForm, if yes, override it for checkout page
    if (typeof window.validateForm !== 'undefined') {
        console.log('main.js validateForm detected, overriding for checkout page');
    }
    
    // ✅ UNIQUE NAMESPACE for checkout functions to avoid conflicts
    window.CheckoutApp = {
        
        // Form validation function
        validateCheckoutForm: function() {
            const requiredFields = ['name', 'phone', 'address', 'city', 'state', 'pincode'];
            let isValid = true;
            let firstError = null;
            
            // Remove all previous error states
            document.querySelectorAll('.form-control').forEach(field => {
                field.classList.remove('error');
            });
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element) return;
                
                const value = element.value.trim();
                if (!value) {
                    element.classList.add('error');
                    isValid = false;
                    if (!firstError) firstError = element;
                }
            });
            
            // Phone validation
            const phone = document.getElementById('phone');
            if (phone && phone.value) {
                if (!/^[0-9]{10}$/.test(phone.value)) {
                    phone.classList.add('error');
                    isValid = false;
                    if (!firstError) firstError = phone;
                }
            }
            
            // PIN validation
            const pincode = document.getElementById('pincode');
            if (pincode && pincode.value) {
                if (!/^[0-9]{6}$/.test(pincode.value)) {
                    pincode.classList.add('error');
                    isValid = false;
                    if (!firstError) firstError = pincode;
                }
            }
            
            if (!isValid && firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                alert('Please fill in all required fields correctly.');
                return false;
            }
            
            return true;
        },
        
        // Submit form function
        submitCheckoutForm: function() {
            const form = document.getElementById('checkout-form');
            const btn = document.getElementById('placeOrderBtn');
            const mobileBtn = document.getElementById('mobilePlaceOrderBtn');
            
            if (!form) {
                console.error('Checkout form not found!');
                return false;
            }
            
            // Validate form first
            if (!this.validateCheckoutForm()) {
                return false;
            }
            
            // Show loading state on both buttons
            if (btn) {
                btn.classList.add('btn-loading');
                btn.disabled = true;
            }
            if (mobileBtn) {
                mobileBtn.textContent = 'Processing...';
                mobileBtn.disabled = true;
            }
            
            // Meta Pixel – AddPaymentInfo (mobile path — delay submit so pixel can fire)
            if (typeof fbq === 'function') {
                var totalEl = document.getElementById('order-total-display');
                var orderTotal = totalEl
                    ? parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0
                    : <?php echo json_encode((float)($total ?? 0)); ?>;
                fbq('track', 'AddPaymentInfo', {
                    value: orderTotal,
                    currency: 'INR'
                });
                var _mobileForm = form;
                setTimeout(function() { _mobileForm.submit(); }, 400);
            } else {
                form.submit();
            }
        },
        
        // Live Address Preview Update
        updateAddressPreview: function() {
            const getVal = (id) => {
                const el = document.getElementById(id);
                return el ? el.value : '';
            };
            
            const name = getVal('name');
            const phone = getVal('phone');
            const address = getVal('address');
            const address2 = getVal('address_2');
            const landmark = getVal('landmark');
            const city = getVal('city');
            const state = getVal('state');
            const pincode = getVal('pincode');
            
            const previewBox = document.getElementById('addressPreview');
            const previewContent = document.getElementById('previewContent');
            
            if (!previewBox || !previewContent) return;
            
            if (address || city || pincode) {
                let html = '';
                if (name) html += `<strong>${this.escapeHtml(name)}</strong><br>`;
                if (phone) html += `📞 ${this.escapeHtml(phone)}<br><br>`;
                if (address) html += `${this.escapeHtml(address)}<br>`;
                if (address2) html += `${this.escapeHtml(address2)}<br>`;
                if (landmark) html += `<em>Near: ${this.escapeHtml(landmark)}</em><br>`;
                if (city || state || pincode) {
                    html += `<br>${this.escapeHtml(city)}${city && state ? ', ' : ''}${this.escapeHtml(state)}${(city || state) && pincode ? ' - ' : ''}${this.escapeHtml(pincode)}`;
                }
                
                previewContent.innerHTML = html;
                previewBox.style.display = 'block';
            } else {
                previewBox.style.display = 'none';
            }
        },
        
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        // Payment Method UI Update + prepaid discount recalc
        updatePaymentUI: function(radio) {
            document.querySelectorAll('.payment-method-item').forEach(item => {
                item.classList.remove('active');
            });
            if (radio && radio.closest) {
                radio.closest('.payment-method-item').classList.add('active');
            }

            // Recalculate total with/without prepaid discount
            const isCod = radio && radio.value === 'cod';
            const subtotalEl = document.querySelector('[data-subtotal]');
            if (!subtotalEl) return;

            const subtotal = parseFloat(subtotalEl.getAttribute('data-subtotal')) || 0;
            const shipping = <?php echo (int)$shipping; ?>;
            const promoDiscount = parseFloat(document.getElementById('promo-discount-display')?.textContent?.replace(/[^0-9.]/g,'')) || 0;
            const afterPromo = Math.max(0, subtotal + shipping - promoDiscount);
            const prepaidAmt  = isCod ? 0 : Math.round(afterPromo * 5) / 100;
            const newTotal    = Math.max(0, afterPromo - prepaidAmt);

            // Prepaid badge & row
            const badge      = document.getElementById('prepaidBadge');
            const discRow    = document.getElementById('prepaid-discount-row');
            const discDisplay = document.getElementById('prepaid-discount-display');
            if (badge) badge.style.opacity = isCod ? '.5' : '1';
            if (discRow) discRow.style.display = (!isCod && prepaidAmt > 0) ? '' : 'none';
            if (discDisplay) discDisplay.textContent = prepaidAmt > 0 ? '-₹' + prepaidAmt.toLocaleString('en-IN', {minimumFractionDigits:2}) : '';

            // Update total displays
            const fmt = (n) => '₹' + n.toLocaleString('en-IN', {minimumFractionDigits:2});
            const totalEl = document.getElementById('order-total-display');
            const stickyEl = document.querySelector('.sticky-total');
            if (totalEl) totalEl.textContent = fmt(newTotal);
            if (stickyEl) stickyEl.textContent = fmt(newTotal);

            // Store current total for place order button label
            window._codCurrent = isCod;
            const placeBtn = document.getElementById('placeOrderBtn');
            if (placeBtn) {
                placeBtn.querySelector('span').textContent = isCod ? 'Verify & Place Order (COD)' : 'Place Order';
            }
        },
        
        // Promo Code Toggle
        togglePromo: function() {
            const wrapper = document.getElementById('promoWrapper');
            const toggle = document.querySelector('.promo-toggle');
            if (wrapper && toggle) {
                wrapper.classList.toggle('active');
                toggle.classList.toggle('active');
            }
        },
        
        // Apply Promo Code via AJAX
        applyPromo: function() {
            const input = document.querySelector('.promo-input');
            const btn = document.querySelector('.promo-apply-btn');
            const promoSection = document.querySelector('.promo-section');
            
            if (!input || !btn) return;
            
            const code = input.value.trim().toUpperCase();
            if (!code) {
                input.style.borderColor = '#ef4444';
                input.placeholder = 'Code daalo pehle!';
                setTimeout(() => { input.style.borderColor = ''; input.placeholder = 'Enter code'; }, 2000);
                return;
            }

            // Remove previous messages
            const existing = promoSection ? promoSection.querySelector('.promo-msg') : null;
            if (existing) existing.remove();

            btn.textContent = 'Checking...';
            btn.disabled = true;
            input.disabled = true;

            // Get current cart subtotal from page
            const subtotalEl = document.querySelector('[data-subtotal]');
            const cartTotal = subtotalEl ? parseFloat(subtotalEl.getAttribute('data-subtotal')) : 0;

            const formData = new FormData();
            formData.append('code', code);
            formData.append('cart_total', cartTotal);
            formData.append('persist', '1');

            fetch('ajax/apply_promo.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                const msgDiv = document.createElement('div');
                msgDiv.className = 'promo-msg';
                
                if (data.success) {
                    input.value = code;
                    btn.textContent = '✓ Applied';
                    btn.style.background = '#059669';
                    btn.style.color = '#fff';
                    input.style.borderColor = '#059669';
                    input.disabled = true;
                    btn.disabled = true;
                    
                    msgDiv.style.cssText = 'margin-top:8px;padding:8px 12px;background:#d1fae5;color:#065f46;border-radius:6px;font-size:13px;font-weight:600;';
                    msgDiv.innerHTML = data.message;

                    // Show promo discount row and update amount
                    const discountRow = document.getElementById('promo-discount-row');
                    const discountEl = document.getElementById('promo-discount-display');
                    if (discountRow && discountEl) {
                        discountEl.textContent = '-₹' + parseFloat(data.discount_amount).toLocaleString('en-IN', {minimumFractionDigits:2});
                        discountRow.style.display = '';
                    }
                    // Update total display (main + sticky mobile)
                    const totalEl = document.getElementById('order-total-display');
                    const newTotalFormatted = '₹' + parseFloat(data.new_total).toLocaleString('en-IN', {minimumFractionDigits:2});
                    if (totalEl) {
                        totalEl.textContent = newTotalFormatted;
                    }
                    // Update sticky mobile total if present
                    const stickyTotal = document.querySelector('.sticky-total');
                    if (stickyTotal) {
                        stickyTotal.textContent = newTotalFormatted;
                    }
                } else {
                    btn.textContent = 'Apply';
                    btn.disabled = false;
                    input.disabled = false;
                    input.style.borderColor = '#ef4444';
                    
                    msgDiv.style.cssText = 'margin-top:8px;padding:8px 12px;background:#fee2e2;color:#991b1b;border-radius:6px;font-size:13px;font-weight:600;';
                    msgDiv.textContent = data.message;
                    
                    setTimeout(() => { input.style.borderColor = ''; }, 3000);
                }
                
                if (promoSection) promoSection.appendChild(msgDiv);
            })
            .catch(() => {
                btn.textContent = 'Apply';
                btn.disabled = false;
                input.disabled = false;
                const msgDiv = document.createElement('div');
                msgDiv.className = 'promo-msg';
                msgDiv.style.cssText = 'margin-top:8px;padding:8px 12px;background:#fee2e2;color:#991b1b;border-radius:6px;font-size:13px;';
                msgDiv.textContent = 'Network error. Dobara try karo.';
                if (promoSection) promoSection.appendChild(msgDiv);
            });
        },
        
        // Form Validation on blur
        validateField: function(field) {
            const value = field.value.trim();
            const pattern = field.getAttribute('pattern');
            const required = field.hasAttribute('required');
            
            let isValid = true;
            
            if (required && !value) {
                isValid = false;
            } else if (pattern && value) {
                const regex = new RegExp(pattern);
                isValid = regex.test(value);
            }
            
            const existingMessage = field.parentElement.querySelector('.validation-message');
            if (existingMessage) existingMessage.remove();
            
            if (isValid) {
                field.classList.remove('error');
                field.classList.add('success');
            } else {
                field.classList.remove('success');
                field.classList.add('error');
            }
            
            return isValid;
        },
        
        // Sticky Mobile Summary
        initStickySummary: function() {
            const stickySummary = document.getElementById('stickySummary');
            if (!stickySummary) return;
            
            window.addEventListener('scroll', () => {
                const scrollY = window.scrollY;
                const formContainer = document.querySelector('.checkout-container');
                if (!formContainer) return;
                
                const formHeight = formContainer.offsetHeight;
                
                if (scrollY > 300 && scrollY < formHeight - 800) {
                    stickySummary.classList.add('visible');
                } else {
                    stickySummary.classList.remove('visible');
                }
            });
        },
        
        // Input Masks
        initInputMasks: function() {
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    this.value = this.value.replace(/\D/g, '').slice(0, 10);
                });
            }
            
            const pincodeInput = document.getElementById('pincode');
            if (pincodeInput) {
                pincodeInput.addEventListener('input', function(e) {
                    this.value = this.value.replace(/\D/g, '').slice(0, 6);
                });
                
                // Auto-fill city/state from PIN
                pincodeInput.addEventListener('blur', function() {
                    const pin = this.value;
                    if (pin.length === 6) {
                        fetch(`https://api.postalpincode.in/pincode/${pin}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data[0].Status === 'Success' && data[0].PostOffice.length > 0) {
                                    const postOffice = data[0].PostOffice[0];
                                    const cityInput = document.getElementById('city');
                                    const stateInput = document.getElementById('state');
                                    
                                    if (cityInput && (!cityInput.value || cityInput.dataset.autoFilled === 'true')) {
                                        cityInput.value = postOffice.District;
                                        cityInput.dataset.autoFilled = 'true';
                                        cityInput.style.background = '#f0fdf4';
                                        setTimeout(() => cityInput.style.background = '', 1000);
                                    }
                                    
                                    if (stateInput && (!stateInput.value || stateInput.dataset.autoFilled === 'true')) {
                                        stateInput.value = postOffice.State;
                                        stateInput.dataset.autoFilled = 'true';
                                        stateInput.style.background = '#f0fdf4';
                                        setTimeout(() => stateInput.style.background = '', 1000);
                                    }
                                    
                                    window.CheckoutApp.updateAddressPreview();
                                }
                            })
                            .catch(err => console.log('PIN lookup failed:', err));
                    }
                });
            }
        },
        
        // Initialize all event listeners
        init: function() {
            // Form submit handler — COD intercept for OTP
            const form = document.getElementById('checkout-form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    if (!this.validateCheckoutForm()) {
                        e.preventDefault();
                        return false;
                    }

                    // If COD selected and OTP not yet verified — show modal
                    const pm = form.querySelector('[name="payment_method"]:checked');
                    if (pm && pm.value === 'cod' && !window._codOtpVerified) {
                        e.preventDefault();
                        window.openCodOtpModal();
                        return false;
                    }

                    // Meta Pixel – AddPaymentInfo (desktop path — prevent default, delay submit)
                    e.preventDefault();
                    const btn = document.getElementById('placeOrderBtn');
                    if (btn) { btn.classList.add('btn-loading'); btn.disabled = true; }
                    var _desktopForm = form;
                    if (typeof fbq === 'function') {
                        const totalEl = document.getElementById('order-total-display');
                        const orderTotal = totalEl
                            ? parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0
                            : <?php echo json_encode((float)($total ?? 0)); ?>;
                        fbq('track', 'AddPaymentInfo', {
                            value: orderTotal,
                            currency: 'INR'
                        });
                        setTimeout(function() { _desktopForm.submit(); }, 400);
                    } else {
                        _desktopForm.submit();
                    }
                });
            }

            // Trigger prepaid discount display on load (cashfree is default)
            const defaultPm = document.querySelector('[name="payment_method"]:checked');
            if (defaultPm) this.updatePaymentUI(defaultPm);
            
            // Mobile button click handler - ✅ CRITICAL FIX
            const mobileBtn = document.getElementById('mobilePlaceOrderBtn');
            if (mobileBtn) {
                mobileBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.submitCheckoutForm();
                });
            }
            
            // Address preview listeners
            ['name', 'phone', 'address', 'address_2', 'landmark', 'city', 'state', 'pincode'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('input', () => this.updateAddressPreview());
                    element.addEventListener('blur', () => this.updateAddressPreview());
                }
            });
            
            // Form validation on blur
            document.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        window.CheckoutApp.validateField(this);
                    }
                });
            });
            
            this.initStickySummary();
            this.initInputMasks();
            this.updateAddressPreview();
        }
    };
    
    // Initialize the checkout app
    window.CheckoutApp.init();
    
    // ✅ GLOBAL FUNCTIONS for inline onclick handlers
    window.validateForm = function() {
        return window.CheckoutApp.validateCheckoutForm();
    };
    
    window.submitCheckoutForm = function() {
        return window.CheckoutApp.submitCheckoutForm();
    };
    
    window.updatePaymentUI = function(radio) {
        return window.CheckoutApp.updatePaymentUI(radio);
    };
    
    window.togglePromo = function() {
        return window.CheckoutApp.togglePromo();
    };
    
    window.applyPromo = function() {
        return window.CheckoutApp.applyPromo();
    };

    // ── GoodMonk pm-row selector ──────────────────────────────
    window.selectPm = function(labelEl, value) {
        document.querySelectorAll('.pm-row').forEach(r => r.classList.remove('active'));
        labelEl.classList.add('active');
        // find and check the radio inside
        const radio = labelEl.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            window.updatePaymentUI(radio);
        }
        // Manual UPI panel toggle
        const panel = document.getElementById('manualUpiPanel');
        if (panel) panel.classList.toggle('active', value === 'upi_manual');
        // Update UPI amount display
        if (value === 'upi_manual') {
            const totalEl = document.getElementById('order-total-display');
            const upiAmt  = document.getElementById('upiAmountDisplay');
            if (upiAmt && totalEl) upiAmt.textContent = totalEl.textContent;
        }
    };

    // ── UPI proof tab switcher ────────────────────────────────
    window.switchUpiTab = function(tab, btn) {
        document.querySelectorAll('.upi-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        const utrDiv   = document.getElementById('upiProofUtr');
        const ssDiv    = document.getElementById('upiProofScreenshot');
        if (utrDiv && ssDiv) {
            utrDiv.style.display  = tab === 'utr'        ? 'block' : 'none';
            ssDiv.style.display   = tab === 'screenshot' ? 'block' : 'none';
        }
    };

    // ── Screenshot preview ────────────────────────────────────
    window.previewScreenshot = function(input) {
        const preview = document.getElementById('screenshotPreviewImg');
        const placeholder = document.getElementById('screenshotPlaceholder');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
                if (placeholder) placeholder.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    };

    // ══════════════════════════════════════════════════════════
    //  COD OTP MODAL FUNCTIONS
    // ══════════════════════════════════════════════════════════
    window._codOtpVerified = false;
    let _resendTimer = null;

    window.openCodOtpModal = function() {
        const phoneInput = document.getElementById('phone');
        const emailInput = document.getElementById('email');
        const phone = phoneInput ? phoneInput.value.trim() : '';
        const email = emailInput ? emailInput.value.trim() : '';
        if (!phone || phone.length < 10) {
            alert('Pehle apna 10-digit mobile number daalein.');
            phoneInput && phoneInput.focus();
            return;
        }
        document.getElementById('codOtpPhoneDisplay').textContent = '+91 ' + phone;
        // Show email in modal
        const emailText = document.getElementById('codOtpEmailText');
        const emailBox  = document.getElementById('codOtpEmailDisplay');
        if (emailText && emailBox) {
            if (email) {
                emailText.textContent = email;
                emailBox.style.display = 'flex';
            } else {
                emailBox.style.display = 'none';
            }
        }
        document.getElementById('codOtpStep1').style.display = '';
        document.getElementById('codOtpStep2').style.display = 'none';
        document.getElementById('otpSendMsg').style.display = 'none';
        const overlay = document.getElementById('codOtpOverlay');
        overlay.style.display = 'flex';
    };

    window.closeCodOtpModal = function() {
        document.getElementById('codOtpOverlay').style.display = 'none';
        window._codOtpVerified = false;
    };

    window.sendCodOtp = function() {
        const btn  = document.getElementById('sendOtpBtn');
        const msg  = document.getElementById('otpSendMsg');
        const phone = document.getElementById('phone')?.value.trim() || '';
        const name  = document.getElementById('name')?.value.trim() || '';
        const email = document.getElementById('email')?.value.trim() || '';

        btn.disabled = true;
        btn.textContent = 'Sending…';
        msg.style.display = 'none';

        const fd = new FormData();
        fd.append('phone', phone);
        fd.append('name', name);
        fd.append('email', email);

        fetch('ajax/send-cod-otp.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Switch to step 2
                document.getElementById('codOtpStep1').style.display = 'none';
                document.getElementById('codOtpStep2').style.display = '';
                document.getElementById('otpSentInfo').textContent =
                    'OTP sent to ****' + data.phone_last4 + '. ' + data.message;
                document.getElementById('codOtpInput').focus();
                startResendTimer(30);
            } else {
                btn.disabled = false;
                btn.textContent = '📲 Send OTP';
                msg.style.display = '';
                msg.style.color = '#dc2626';
                msg.textContent = data.message || 'Failed to send OTP. Please try again.';
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = '📲 Send OTP';
            msg.style.display = '';
            msg.style.color = '#dc2626';
            msg.textContent = 'Network error. Please try again.';
        });
    };

    window.resendCodOtp = function() {
        document.getElementById('codOtpStep1').style.display = '';
        document.getElementById('codOtpStep2').style.display = 'none';
        const btn = document.getElementById('sendOtpBtn');
        btn.disabled = false;
        btn.textContent = '📲 Resend OTP';
        window.sendCodOtp();
    };

    function startResendTimer(seconds) {
        const resendBtn   = document.getElementById('resendOtpBtn');
        const resendTimer = document.getElementById('resendTimer');
        if (resendBtn) resendBtn.style.display = 'none';
        if (resendTimer) resendTimer.style.display = '';
        let remaining = seconds;
        if (_resendTimer) clearInterval(_resendTimer);
        _resendTimer = setInterval(() => {
            remaining--;
            if (resendTimer) resendTimer.textContent = ' Resend in ' + remaining + 's';
            if (remaining <= 0) {
                clearInterval(_resendTimer);
                if (resendBtn) resendBtn.style.display = '';
                if (resendTimer) resendTimer.style.display = 'none';
            }
        }, 1000);
    }

    window.verifyCodOtp = function() {
        const otp  = document.getElementById('codOtpInput').value.trim();
        const btn  = document.getElementById('verifyOtpBtn');
        const msg  = document.getElementById('otpVerifyMsg');

        if (otp.length < 6) {
            msg.style.display = '';
            msg.style.color = '#dc2626';
            msg.textContent = 'Please enter the 6-digit OTP.';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Verifying…';
        msg.style.display = 'none';

        // OTP will be verified server-side — inject it into form and submit
        const form = document.getElementById('checkout-form');
        let hiddenOtp = form.querySelector('[name="cod_otp"]');
        if (!hiddenOtp) {
            hiddenOtp = document.createElement('input');
            hiddenOtp.type  = 'hidden';
            hiddenOtp.name  = 'cod_otp';
            form.appendChild(hiddenOtp);
        }
        hiddenOtp.value = otp;

        window._codOtpVerified = true;
        document.getElementById('codOtpOverlay').style.display = 'none';

        // Meta Pixel – AddPaymentInfo (COD path — delay submit so pixel can fire)
        const placeBtn = document.getElementById('placeOrderBtn');
        if (placeBtn) { placeBtn.classList.add('btn-loading'); placeBtn.disabled = true; }
        if (typeof fbq === 'function') {
            var totalEl = document.getElementById('order-total-display');
            var orderTotal = totalEl
                ? parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0
                : 0;
            fbq('track', 'AddPaymentInfo', {
                value: orderTotal,
                currency: 'INR'
            });
            var _codForm = form;
            setTimeout(function() { _codForm.submit(); }, 400);
        } else {
            form.submit();
        }
    };

    // Close modal when clicking overlay background
    document.getElementById('codOtpOverlay')?.addEventListener('click', function(e) {
        if (e.target === this) window.closeCodOtpModal();
    });

});
</script>

<!-- AOS Animation Library -->
<link rel="stylesheet" href="/assets/css/aos.min.css">
<script src="/assets/js/aos.min.js" defer></script>
<script>
    AOS.init({
        duration: 600,
        once: true,
        offset: 50
    });
</script>

<?php require_once 'includes/footer.php'; ?>