<?php
/**
 * PHP built-in server router.
 *
 * 1. Implements transparent WebP serving:
 *    Agar request kisi .jpg/.jpeg/.png ki hai aur browser webp accept karta hai
 *    aur same path par .webp file mojood hai, toh webp serve karte hain.
 *    Agar original file delete bhi ho gayi ho lekin .webp mojood hai, tab bhi
 *    .webp serve hota hai.
 *
 * 2. Apache .htaccess ki kuch zaruri rules ka equivalent yahan hai
 *    (php -S .htaccess honor nahi karta).
 *
 * Production (Hostinger Apache) par .htaccess ye sab handle kar leta hai;
 * router sirf dev ke liye hai.
 */

$root = __DIR__;
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
// URL-decode for filesystem lookups (e.g. %20 -> space, %28 -> ( )
$decodedUri = rawurldecode($uri);
$path = $root . $decodedUri;

// ---------- 0. Static asset caching headers ----------
if (preg_match('/\.(css|js|woff2?|ttf|eot|svg|ico)$/i', $uri)) {
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Vary: Accept-Encoding');
}
if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $uri)) {
    header('Cache-Control: public, max-age=2592000');
    header('Vary: Accept');
}

// ---------- 1. WebP transparent serve ----------
if (preg_match('/\.(jpe?g|png)$/i', $uri)) {
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $browserAcceptsWebp = stripos($accept, 'image/webp') !== false;

    if ($browserAcceptsWebp && is_file($webp)) {
        header('Content-Type: image/webp');
        header('Vary: Accept');
        header('Content-Length: ' . filesize($webp));
        readfile($webp);
        return true;
    }

    // Original delete ho gayi ho aur webp ho — fallback
    if (!is_file($path) && is_file($webp)) {
        header('Content-Type: image/webp');
        header('Content-Length: ' . filesize($webp));
        readfile($webp);
        return true;
    }
}

// ---------- 2. Existing file/folder serve as-is ----------
if ($uri !== '/' && file_exists($path) && !is_dir($path)) {
    return false; // built-in server will serve it directly
}

// ---------- 3. blogs.php -> blog.php redirect ----------
if (preg_match('#^/blogs\.php$#', $uri)) {
    header('Location: /blog.php', true, 301);
    return true;
}

// ---------- 4. Product slug routing ----------
if (preg_match('#^/([a-z0-9-]+)$#', $uri, $m) && !is_dir($path)) {
    $slug = $m[1];
    // Don't intercept things like /admin which are real dirs (handled above).
    $_GET['slug'] = $slug;
    $_SERVER['SCRIPT_NAME']    = '/product-detail.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/product-detail.php';
    require $root . '/product-detail.php';
    return true;
}

// ---------- 5. Default to index.php ----------
if (is_dir($path)) {
    foreach (['index.php', 'index.html'] as $idx) {
        if (is_file($path . '/' . $idx)) {
            $_SERVER['SCRIPT_NAME']    = rtrim($uri, '/') . '/' . $idx;
            $_SERVER['SCRIPT_FILENAME'] = $path . '/' . $idx;
            require $path . '/' . $idx;
            return true;
        }
    }
}

if (!file_exists($path)) {
    require $root . '/index.php';
    return true;
}

return false;
