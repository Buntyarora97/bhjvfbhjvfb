<?php
require_once 'includes/config.php';

$pageTitle = 'Payment Failed | Livvra';
$metaDescription = 'Your payment could not be processed. Please try again.';
$canonicalUrl = SITE_URL . '/payment-failed.php';

$orderId    = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$orderNum   = '';
$reason     = isset($_GET['reason']) ? htmlspecialchars(strip_tags($_GET['reason'])) : '';

if ($orderId) {
    try {
        $order   = Order::getById($orderId);
        $orderNum = $order['order_number'] ?? '';
    } catch (Exception $e) {
        $orderNum = '';
    }
}

require_once 'includes/header.php';
?>

<style>
.pf-wrap {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fdf8f8;
    padding: 60px 16px;
}
.pf-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.09);
    padding: 48px 40px;
    text-align: center;
    max-width: 480px;
    width: 100%;
}
.pf-icon {
    width: 80px;
    height: 80px;
    background: #fee2e2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
}
.pf-icon svg { color: #dc2626; }
.pf-title {
    font-size: 26px;
    font-weight: 800;
    color: #111;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
}
.pf-msg {
    font-size: 15px;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 8px;
}
.pf-order {
    display: inline-block;
    background: #f3f4f6;
    border-radius: 8px;
    padding: 6px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin: 12px 0 24px;
    letter-spacing: 0.5px;
}
.pf-reason {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 13px;
    color: #991b1b;
    margin-bottom: 28px;
}
.pf-btns {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.pf-btn-primary {
    background: #1f3e35;
    color: #fff;
    border: none;
    border-radius: 100px;
    padding: 15px 28px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    display: block;
    transition: background 0.2s;
}
.pf-btn-primary:hover { background: #2d5a47; color: #fff; }
.pf-btn-secondary {
    background: transparent;
    color: #1f3e35;
    border: 2px solid #1f3e35;
    border-radius: 100px;
    padding: 13px 28px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    display: block;
    transition: all 0.2s;
}
.pf-btn-secondary:hover { background: #1f3e35; color: #fff; }
.pf-help {
    margin-top: 24px;
    font-size: 13px;
    color: #9ca3af;
}
.pf-help a { color: #1f3e35; font-weight: 600; }
@media (max-width: 480px) {
    .pf-card { padding: 32px 20px; }
    .pf-title { font-size: 22px; }
}
</style>

<div class="pf-wrap">
    <div class="pf-card" data-aos="fade-up">

        <div class="pf-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>

        <h1 class="pf-title">Payment Failed</h1>
        <p class="pf-msg">We were unable to process your payment. Your cart is saved — you can retry without re-entering your details.</p>

        <?php if ($orderNum): ?>
            <div class="pf-order">Order Ref: <?= htmlspecialchars($orderNum) ?></div>
        <?php endif; ?>

        <?php if ($reason): ?>
            <div class="pf-reason">
                <strong>Reason:</strong> <?= $reason ?>
            </div>
        <?php endif; ?>

        <div class="pf-btns">
            <a href="checkout.php" class="pf-btn-primary">
                🔄 Try Again
            </a>
            <a href="cart.php" class="pf-btn-secondary">
                View Cart
            </a>
            <a href="products.php" style="font-size:14px;color:#6b7280;text-decoration:none;padding:8px 0;">
                Continue Shopping
            </a>
        </div>

        <div class="pf-help">
            Need help? <a href="https://wa.me/918958489684?text=Payment+failed+for+order+<?= urlencode($orderNum) ?>" target="_blank">Chat with us on WhatsApp</a>
        </div>

    </div>
</div>

<!-- AOS -->
<link rel="stylesheet" href="/assets/css/aos.min.css">
<script src="/assets/js/aos.min.js" defer></script>
<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof AOS!=='undefined') AOS.init({duration:600,once:true}); });</script>

<?php require_once 'includes/footer.php'; ?>
