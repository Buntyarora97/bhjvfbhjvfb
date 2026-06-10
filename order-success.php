<?php
require_once 'includes/config.php';
require_once 'includes/models/Product.php';

// Read COD order from session OR online order from GET param
$codData   = $_SESSION['cod_order_success'] ?? null;
$orderParam = isset($_GET['order']) ? htmlspecialchars($_GET['order']) : '';

// If neither source → redirect home
if (!$codData && !$orderParam) {
    header('Location: index.php');
    exit;
}

// Pull order number
$orderNumber = $codData['order_number'] ?? $orderParam;

// Clear session after reading (one-time display)
if ($codData) {
    unset($_SESSION['cod_order_success']);
}

// Order details from session (COD flow)
$customerName    = $codData['customer_name']    ?? 'Valued Customer';
$customerEmail   = $codData['customer_email']   ?? '';
$customerPhone   = $codData['customer_phone']   ?? '';
$shippingAddress = $codData['shipping_address'] ?? '';
$city            = $codData['city']             ?? '';
$state           = $codData['state']            ?? '';
$pincode         = $codData['pincode']          ?? '';
$orderItems      = $codData['items']            ?? [];
$subtotal        = (float)($codData['subtotal']       ?? 0);
$shipping        = (float)($codData['shipping']       ?? 0);
$promoDiscount   = (float)($codData['promo_discount'] ?? 0);
$rewardDiscount  = (float)($codData['reward_discount']?? 0);
$totalAmount     = (float)($codData['total']          ?? 0);
$paymentMethod   = $codData['payment_method']   ?? 'Cash on Delivery';
$orderTime       = $codData['time']             ?? date('Y-m-d H:i:s');
$orderDate       = date('d M Y', strtotime($orderTime));

// Estimated delivery: 5–7 business days
$estimatedDate = date('d M Y', strtotime('+7 days'));

// Recommended products (exclude items already ordered)
$orderedIds = array_column($orderItems, 'product_id');
$recommended = [];
try {
    $featuredAll = Product::getFeatured(12);
    foreach ($featuredAll as $p) {
        if (!in_array($p['id'], $orderedIds)) {
            $recommended[] = $p;
            if (count($recommended) >= 6) break;
        }
    }
    if (empty($recommended)) $recommended = array_slice($featuredAll, 0, 6);
} catch (Exception $e) {}

$pageTitle = 'Order Confirmed — ' . $orderNumber;
require_once 'includes/header.php';
?>

<!-- Confetti Canvas -->
<canvas id="confettiCanvas" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;"></canvas>

<style>
:root {
    --green:  #0f3d2e;
    --green2: #1a5f4a;
    --gold:   #C9A227;
    --gold2:  #d4b43a;
    --ok:     #10b981;
    --bg:     #f4f7f5;
    --white:  #ffffff;
    --muted:  #6b7280;
    --border: #e5e7eb;
    --radius: 18px;
    --shadow: 0 8px 32px rgba(0,0,0,0.10);
}

.ts-page { background: var(--bg); min-height: 100vh; padding: 40px 0 80px; }
.ts-container { max-width: 900px; margin: 0 auto; padding: 0 20px; }

/* ── Hero Card ── */
.ts-hero {
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    text-align: center;
    padding: 50px 40px 40px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.ts-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 5px;
    background: linear-gradient(90deg, var(--green), var(--gold), var(--ok), var(--green));
    background-size: 300% 100%;
    animation: shimmer 3s linear infinite;
}
@keyframes shimmer { 0%{background-position:0%} 100%{background-position:300%} }

.ts-check {
    width: 110px; height: 110px;
    background: linear-gradient(135deg, var(--ok) 0%, #059669 100%);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px;
    box-shadow: 0 0 0 12px rgba(16,185,129,.12), 0 8px 24px rgba(16,185,129,.3);
    animation: bounceIn .6s cubic-bezier(.36,.07,.19,.97);
}
@keyframes bounceIn {
    0%   { transform: scale(0); opacity: 0; }
    60%  { transform: scale(1.1); }
    80%  { transform: scale(0.95); }
    100% { transform: scale(1); opacity: 1; }
}
.ts-check svg { width:52px; height:52px; color:#fff; }

.ts-hero h1 { font-size: 2rem; font-weight: 800; color: var(--green); margin: 0 0 10px; }
.ts-hero p  { color: var(--muted); font-size: 1.05rem; margin: 0 0 24px; line-height: 1.6; }

.ts-badge {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, var(--green) 0%, var(--green2) 100%);
    color: #fff;
    padding: 12px 28px;
    border-radius: 50px;
    font-size: 1.15rem; font-weight: 700;
    letter-spacing: 1px;
    box-shadow: 0 4px 16px rgba(15,61,46,.25);
    margin-bottom: 28px;
}

.ts-meta-row {
    display: flex; justify-content: center; gap: 32px; flex-wrap: wrap;
    font-size: .9rem; color: var(--muted);
}
.ts-meta-row span { display: flex; align-items: center; gap: 6px; }
.ts-meta-row svg  { width:16px; height:16px; flex-shrink:0; }

/* ── Two-col grid ── */
.ts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px; }
@media(max-width:680px){ .ts-grid { grid-template-columns: 1fr; } }

/* ── Cards ── */
.ts-card {
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 28px;
}
.ts-card-title {
    font-size: .8rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: var(--muted);
    margin: 0 0 18px;
    display: flex; align-items: center; gap: 8px;
}
.ts-card-title svg { width:16px; height:16px; color: var(--green); }

/* ── Order Items ── */
.ts-item {
    display: flex; gap: 14px; align-items: flex-start;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
}
.ts-item:last-child { border-bottom: none; padding-bottom: 0; }
.ts-item-img {
    width: 64px; height: 64px;
    border-radius: 10px; object-fit: cover;
    background: #f9fafb; border: 1px solid var(--border);
    flex-shrink: 0;
}
.ts-item-name { font-weight: 700; font-size: .95rem; color: #1a1a1a; margin-bottom: 4px; }
.ts-item-qty  { font-size: .8rem; color: var(--muted); }
.ts-item-price{ font-weight: 700; color: var(--green); font-size: .95rem; margin-left: auto; white-space: nowrap; }

/* ── Price summary ── */
.ts-price-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; font-size: .9rem;
    border-bottom: 1px dashed var(--border);
    color: var(--muted);
}
.ts-price-row:last-child { border-bottom: none; }
.ts-price-row.total {
    font-size: 1.1rem; font-weight: 800; color: var(--green);
    border-top: 2px solid var(--border); border-bottom: none;
    margin-top: 8px; padding-top: 14px;
}
.ts-price-row .val { font-weight: 700; color: #1a1a1a; }
.ts-price-row.disc .val { color: #059669; }

/* ── Address ── */
.ts-address { font-size: .9rem; line-height: 1.8; color: #333; }
.ts-address strong { color: var(--green); display: block; margin-bottom: 4px; font-size: 1rem; }

/* ── Delivery Steps ── */
.ts-steps { display: flex; flex-direction: column; gap: 14px; }
.ts-step { display: flex; align-items: flex-start; gap: 14px; }
.ts-step-dot {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.ts-step-dot.done  { background: rgba(16,185,129,.15); color: #059669; }
.ts-step-dot.next  { background: rgba(201,162,39,.15);  color: var(--gold); }
.ts-step-dot.later { background: var(--bg); color: var(--muted); }
.ts-step-info strong { font-size: .9rem; color: #1a1a1a; display: block; margin-bottom: 2px; }
.ts-step-info span   { font-size: .8rem; color: var(--muted); }

/* ── CTA Buttons ── */
.ts-actions { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 28px; }
.ts-btn-primary {
    flex: 1; min-width: 180px;
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 16px 28px; border-radius: 50px;
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold2) 100%);
    color: #fff; text-decoration: none; font-weight: 700; font-size: 1rem;
    box-shadow: 0 4px 16px rgba(201,162,39,.3);
    transition: transform .2s, box-shadow .2s;
}
.ts-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(201,162,39,.4); }
.ts-btn-secondary {
    flex: 1; min-width: 180px;
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 16px 28px; border-radius: 50px;
    background: #fff; color: var(--green);
    border: 2px solid var(--green);
    text-decoration: none; font-weight: 700; font-size: 1rem;
    transition: background .2s, color .2s;
}
.ts-btn-secondary:hover { background: var(--green); color: #fff; }
.ts-btn-whatsapp {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 24px; border-radius: 50px;
    background: #25D366; color: #fff;
    text-decoration: none; font-weight: 700; font-size: .95rem;
    box-shadow: 0 4px 14px rgba(37,211,102,.3);
    transition: transform .2s;
}
.ts-btn-whatsapp:hover { transform: translateY(-2px); }

/* ── Trust Bar ── */
.ts-trust {
    display: flex; justify-content: center; gap: 36px; flex-wrap: wrap;
    padding: 24px; background: var(--white); border-radius: var(--radius);
    box-shadow: var(--shadow); margin-bottom: 36px; text-align: center;
}
.ts-trust-item { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.ts-trust-item svg { width: 30px; height: 30px; color: var(--green); }
.ts-trust-item span { font-size: .78rem; font-weight: 600; color: var(--muted); }

/* ── Recommended ── */
.ts-rec-title {
    font-size: 1.4rem; font-weight: 800; color: var(--green);
    margin: 0 0 20px;
    display: flex; align-items: center; gap: 10px;
}
.ts-rec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 40px;
}
.ts-prod-card {
    background: var(--white); border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    overflow: hidden; text-decoration: none; color: inherit;
    transition: transform .2s, box-shadow .2s;
    display: flex; flex-direction: column;
}
.ts-prod-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.ts-prod-card img { width: 100%; aspect-ratio: 1; object-fit: cover; background: #f9fafb; }
.ts-prod-info { padding: 12px; }
.ts-prod-name { font-weight: 700; font-size: .83rem; color: #1a1a1a; margin-bottom: 6px; line-height: 1.3;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ts-prod-price { font-weight: 800; font-size: .9rem; color: var(--green); }
.ts-prod-old   { font-size: .75rem; color: var(--muted); text-decoration: line-through; margin-left: 4px; }
.ts-prod-btn {
    display: block; margin: 0 12px 12px;
    padding: 8px; border-radius: 8px;
    background: var(--green); color: #fff;
    text-align: center; font-size: .78rem; font-weight: 700;
    text-decoration: none;
    transition: background .2s;
}
.ts-prod-btn:hover { background: var(--green2); }

/* ── WhatsApp float ── */
.ts-wa-float {
    position: fixed; bottom: 24px; right: 24px; z-index: 999;
    width: 58px; height: 58px; border-radius: 50%;
    background: #25D366; color: #fff;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 20px rgba(37,211,102,.5);
    text-decoration: none;
    animation: waPulse 2s infinite;
}
@keyframes waPulse { 0%,100%{box-shadow:0 4px 20px rgba(37,211,102,.5)} 50%{box-shadow:0 4px 30px rgba(37,211,102,.8)} }
.ts-wa-float svg { width:30px; height:30px; }
</style>

<div class="ts-page">
<div class="ts-container">

    <!-- ── HERO ── -->
    <div class="ts-hero" data-aos="zoom-in">
        <div class="ts-check">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h1>🎉 Order Confirmed!</h1>
        <p>Shukriya <?php echo htmlspecialchars($customerName); ?>! Aapka order successfully place ho gaya hai.<br>Hum jald hi aapka order bhej denge.</p>

        <div class="ts-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                <polyline points="9 12 12 15 15 9"></polyline>
            </svg>
            Order #<?php echo htmlspecialchars($orderNumber); ?>
        </div>

        <div class="ts-meta-row">
            <span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                <?php echo date('d M Y, g:i A', strtotime($orderTime)); ?>
            </span>
            <span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                <?php echo htmlspecialchars($paymentMethod); ?>
            </span>
            <span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Est. delivery by <?php echo $estimatedDate; ?>
            </span>
        </div>
    </div>

    <!-- ── CTA BUTTONS ── -->
    <div class="ts-actions" data-aos="fade-up">
        <a href="products.php" class="ts-btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Continue Shopping
        </a>
        <a href="track-order.php" class="ts-btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Track Order
        </a>
        <a href="https://wa.me/91<?php echo SITE_PHONE; ?>?text=Hi+LIVVRA%2C+my+order+number+is+<?php echo urlencode($orderNumber); ?>+and+I+need+help." target="_blank" class="ts-btn-whatsapp">
            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            WhatsApp Support
        </a>
    </div>

    <!-- ── ORDER ITEMS + PRICE SUMMARY ── -->
    <div class="ts-grid" data-aos="fade-up">
        <!-- Items -->
        <div class="ts-card">
            <div class="ts-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Items Ordered
            </div>
            <?php if (!empty($orderItems)): ?>
                <?php foreach ($orderItems as $item):
                    $iTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                    $iOrig  = ($item['original_price'] ?? $item['price'] ?? 0) * ($item['quantity'] ?? 1);
                ?>
                <div class="ts-item">
                    <img src="uploads/products/<?php echo htmlspecialchars($item['image'] ?? ''); ?>"
                         alt="<?php echo htmlspecialchars($item['name'] ?? ''); ?>"
                         class="ts-item-img"
                         onerror="this.src='assets/images/placeholder.jpg'">
                    <div style="flex:1;min-width:0;">
                        <div class="ts-item-name"><?php echo htmlspecialchars($item['name'] ?? ''); ?></div>
                        <div class="ts-item-qty">Qty: <?php echo (int)($item['quantity'] ?? 1); ?>
                            <?php if (!empty($item['variant'])): ?> &nbsp;|&nbsp; <?php echo htmlspecialchars($item['variant']); ?><?php endif; ?>
                        </div>
                        <?php if ($iOrig > $iTotal): ?>
                            <div style="font-size:.78rem;color:#059669;font-weight:600;margin-top:2px;">Save ₹<?php echo number_format($iOrig - $iTotal, 0); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="ts-item-price">₹<?php echo number_format($iTotal, 0); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.9rem;">Order placed successfully.</p>
            <?php endif; ?>
        </div>

        <!-- Price Summary -->
        <div class="ts-card">
            <div class="ts-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Price Breakdown
            </div>
            <div class="ts-price-row">
                <span>Subtotal</span>
                <span class="val">₹<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="ts-price-row">
                <span>Shipping</span>
                <span class="val" style="<?php echo $shipping == 0 ? 'color:#059669' : ''; ?>">
                    <?php echo $shipping == 0 ? 'FREE' : '₹' . number_format($shipping, 2); ?>
                </span>
            </div>
            <?php if ($promoDiscount > 0): ?>
            <div class="ts-price-row disc">
                <span>🏷 Promo Discount</span>
                <span class="val">-₹<?php echo number_format($promoDiscount, 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($rewardDiscount > 0): ?>
            <div class="ts-price-row disc">
                <span>🪙 Reward Coins</span>
                <span class="val">-₹<?php echo number_format($rewardDiscount, 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="ts-price-row total">
                <span>Total Paid</span>
                <span>₹<?php echo number_format($totalAmount, 2); ?></span>
            </div>

            <!-- Payment Method -->
            <div style="margin-top:20px;background:#f0fdf4;border-radius:10px;padding:14px;display:flex;align-items:center;gap:10px;">
                <div style="width:40px;height:40px;background:rgba(16,185,129,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🏠</div>
                <div>
                    <div style="font-weight:700;font-size:.9rem;color:#0f3d2e;">Cash on Delivery</div>
                    <div style="font-size:.78rem;color:var(--muted);">Pay when your order arrives</div>
                </div>
            </div>

            <!-- Delivery Estimate -->
            <div style="margin-top:14px;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:14px;">
                <div style="font-weight:700;font-size:.85rem;color:#92400e;margin-bottom:4px;">📅 Estimated Delivery</div>
                <div style="font-size:.9rem;color:#78350f;font-weight:600;"><?php echo $estimatedDate; ?></div>
                <div style="font-size:.75rem;color:#a16207;margin-top:2px;">5–7 business days</div>
            </div>
        </div>
    </div>

    <!-- ── DELIVERY ADDRESS + ORDER STATUS ── -->
    <div class="ts-grid" data-aos="fade-up">
        <!-- Address -->
        <div class="ts-card">
            <div class="ts-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Delivery Address
            </div>
            <div class="ts-address">
                <strong><?php echo htmlspecialchars($customerName); ?></strong>
                📞 <?php echo htmlspecialchars($customerPhone); ?><br>
                <?php echo nl2br(htmlspecialchars($shippingAddress)); ?>
                <?php if ($city || $state): ?>
                    <br><?php echo htmlspecialchars($city); ?><?php echo $city && $state ? ', ' : ''; ?><?php echo htmlspecialchars($state); ?><?php echo $pincode ? ' — ' . htmlspecialchars($pincode) : ''; ?>
                <?php endif; ?>
                <br>India
            </div>
            <?php if ($customerEmail): ?>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);font-size:.85rem;color:var(--muted);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="vertical-align:middle;margin-right:4px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Confirmation sent to <strong><?php echo htmlspecialchars($customerEmail); ?></strong>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Journey -->
        <div class="ts-card">
            <div class="ts-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Order Journey
            </div>
            <div class="ts-steps">
                <div class="ts-step">
                    <div class="ts-step-dot done">✓</div>
                    <div class="ts-step-info">
                        <strong>Order Placed</strong>
                        <span><?php echo date('d M Y, g:i A', strtotime($orderTime)); ?></span>
                    </div>
                </div>
                <div class="ts-step">
                    <div class="ts-step-dot next">⚡</div>
                    <div class="ts-step-info">
                        <strong>Being Packed</strong>
                        <span>Within 24–48 hours</span>
                    </div>
                </div>
                <div class="ts-step">
                    <div class="ts-step-dot later">🚚</div>
                    <div class="ts-step-info">
                        <strong>Shipped</strong>
                        <span>We'll notify you with tracking</span>
                    </div>
                </div>
                <div class="ts-step">
                    <div class="ts-step-dot later">🏠</div>
                    <div class="ts-step-info">
                        <strong>Delivered</strong>
                        <span>Est. by <?php echo $estimatedDate; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TRUST BAR ── -->
    <div class="ts-trust" data-aos="fade-up">
        <div class="ts-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>100% Authentic</span>
        </div>
        <div class="ts-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            <span>Easy Returns</span>
        </div>
        <div class="ts-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <span>Fast Delivery</span>
        </div>
        <div class="ts-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.23h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.18 6.18l1.77-1.77a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <span>24/7 Support</span>
        </div>
        <div class="ts-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span>Ayurvedic Quality</span>
        </div>
    </div>

    <?php if (!empty($recommended)): ?>
    <!-- ── RECOMMENDED PRODUCTS ── -->
    <div data-aos="fade-up">
        <div class="ts-rec-title">
            🌿 You Might Also Like
        </div>
        <div class="ts-rec-grid">
            <?php foreach ($recommended as $p):
                $pUrl   = productUrl($p);
                $pPrice = (float)($p['price'] ?? 0);
                $pOrig  = (float)($p['original_price'] ?? $p['mrp'] ?? $pPrice);
                $pImg   = 'uploads/products/' . ($p['image'] ?? '');
            ?>
            <a href="<?php echo htmlspecialchars($pUrl); ?>" class="ts-prod-card">
                <img src="<?php echo htmlspecialchars($pImg); ?>"
                     alt="<?php echo htmlspecialchars($p['name']); ?>"
                     onerror="this.src='assets/images/placeholder.jpg'">
                <div class="ts-prod-info">
                    <div class="ts-prod-name"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div>
                        <span class="ts-prod-price">₹<?php echo number_format($pPrice, 0); ?></span>
                        <?php if ($pOrig > $pPrice): ?>
                            <span class="ts-prod-old">₹<?php echo number_format($pOrig, 0); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="ts-prod-btn">Add to Cart</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<!-- WhatsApp Float Button -->
<a href="https://wa.me/91<?php echo SITE_PHONE; ?>?text=Hi+LIVVRA%2C+need+help+with+order+<?php echo urlencode($orderNumber); ?>" target="_blank" class="ts-wa-float" title="Chat on WhatsApp">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration: 600, once: true, offset: 40 });

// ── Confetti Animation ──
(function() {
    const canvas = document.getElementById('confettiCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const colors = ['#C9A227','#0f3d2e','#10b981','#f59e0b','#3b82f6','#ef4444','#8b5cf6'];
    const pieces = [];

    for (let i = 0; i < 160; i++) {
        pieces.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height - canvas.height,
            w: Math.random() * 12 + 6,
            h: Math.random() * 6 + 4,
            color: colors[Math.floor(Math.random() * colors.length)],
            vx: (Math.random() - 0.5) * 3,
            vy: Math.random() * 3 + 2,
            rot: Math.random() * Math.PI * 2,
            rspeed: (Math.random() - 0.5) * 0.15,
            opacity: 1,
        });
    }

    let frame = 0;
    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        pieces.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;
            p.rot += p.rspeed;
            if (frame > 120) p.opacity -= 0.008;
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot);
            ctx.globalAlpha = Math.max(0, p.opacity);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
        });
        frame++;
        if (frame < 220) requestAnimationFrame(draw);
        else ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    draw();
})();

// Meta Pixel – Purchase (COD order success)
if (typeof fbq === 'function') {
    fbq('track', 'Purchase', {
        value:    <?php echo json_encode((float)($totalAmount ?? 0)); ?>,
        currency: 'INR',
        content_ids: <?php
            $fbqCodIds = array_map(fn($i) => (string)($i['product_id'] ?? ''), $orderItems ?: []);
            echo json_encode(array_values(array_filter($fbqCodIds)));
        ?>,
        content_type: 'product',
        order_id: <?php echo json_encode((string)($orderNumber ?? '')); ?>
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
