<?php

// Output Buffering with gzip compression for faster delivery
if (!ob_get_level()) {
    if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && extension_loaded('zlib') && !headers_sent()) {
        ob_start('ob_gzhandler');
    } else {
        ob_start();
    }
}

// Secure session settings
if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

require_once __DIR__ . '/database.php';

// Include all model classes
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Admin.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/ContactInquiry.php';
require_once __DIR__ . '/models/Review.php';
require_once __DIR__ . '/models/ProductImage.php';
require_once __DIR__ . '/models/Setting.php';
require_once __DIR__ . '/models/Hero.php';
require_once __DIR__ . '/models/Story.php';
require_once __DIR__ . '/models/Blog.php';
require_once __DIR__ . '/models/HomeBanner.php';
require_once __DIR__ . '/models/GoogleReview.php';
require_once __DIR__ . '/models/FAQ.php';

// Constants
define('SITE_NAME', 'Dr Tridosha Herbotech Pvt Ltd');
define('SITE_DOMAIN', 'livvra.in');
define('SITE_EMAIL', 'livvraindia@gmail.com');
define('SITE_PHONE', '8958489684');
define('SITE_ADDRESS', 'Bathinda 151001');
define('SITE_TAGLINE', 'Live Better Live Strong');
define('CURRENCY', '₹');
define('FREE_SHIPPING_ABOVE', 499);

// Payment keys — loaded from environment secrets
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: '');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: '');

define('SHIPROCKET_BASE_URL', 'https://apiv2.shiprocket.in/v1/external');
define('SHIPROCKET_API_EMAIL', getenv('SHIPROCKET_API_EMAIL') ?: '');
define('SHIPROCKET_API_PASSWORD', getenv('SHIPROCKET_API_PASSWORD') ?: '');
define('SHIPROCKET_PICKUP_LOCATION', 'Warehouse Office');

/* ======================
   PAYU CONFIG
====================== */
define('PAYU_MERCHANT_KEY', getenv('PAYU_MERCHANT_KEY') ?: '');
define('PAYU_SALT', getenv('PAYU_SALT') ?: '');
define('PAYU_BASE_URL', 'https://secure.payu.in/_payment');
define('PAYU_SUCCESS_URL', (getenv('REPLIT_DEV_DOMAIN') ? 'https://' . getenv('REPLIT_DEV_DOMAIN') : 'https://livvra.in') . '/payu-success.php');
define('PAYU_FAILURE_URL', (getenv('REPLIT_DEV_DOMAIN') ? 'https://' . getenv('REPLIT_DEV_DOMAIN') : 'https://livvra.in') . '/payu-failed.php');

define('IM_API_KEY', getenv('IM_API_KEY') ?: '');
define('IM_AUTH_TOKEN', getenv('IM_AUTH_TOKEN') ?: '');

// Dynamic site URL — uses Replit dev domain when available
define('SITE_URL', getenv('REPLIT_DEV_DOMAIN') ? 'https://' . getenv('REPLIT_DEV_DOMAIN') : 'https://livvra.in');

define('CASHFREE_CLIENT_ID', getenv('CASHFREE_CLIENT_ID') ?: '');
define('CASHFREE_CLIENT_SECRET', getenv('CASHFREE_CLIENT_SECRET') ?: '');
define('CASHFREE_ENV', getenv('CASHFREE_ENV') ?: 'PROD');


function getCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += (($item['price'] ?? 0) * ($item['quantity'] ?? 0));
        }
    }
    return $total;
}

function getCartMrpTotal() {
    $total = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += (($item['mrp'] ?? $item['price']) * ($item['quantity'] ?? 0));
        }
    }
    return $total;
}

function getGlobalOfferPercent() {
    try {
        return (float)(Setting::get('global_offer_percent', 10) ?: 10);
    } catch (Exception $e) {
        return 10;
    }
}

function getShippingFee($subtotal) {
    if ($subtotal >= FREE_SHIPPING_ABOVE) {
        return 0;
    }
    return 50;
}

// Security Helper
function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Clean product URL using slug
function productUrl($product) {
    $slug = $product['slug'] ?? '';
    if ($slug) {
        return '/' . ltrim($slug, '/');
    }
    return '/product-detail.php?id=' . ($product['id'] ?? 0);
}

function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += ($item['quantity'] ?? 0);
        }
    }
    return $count;
}

define('UPI_ID', Setting::get('UPI_ID', '9953835017@ybl'));
define('UPI_HOLDER_NAME', Setting::get('UPI_HOLDER_NAME', 'Livvra'));

function checkDelivery($pincode) {
    $available_pincodes = ['151001', '110001', '400001'];
    return in_array($pincode, $available_pincodes);
}
