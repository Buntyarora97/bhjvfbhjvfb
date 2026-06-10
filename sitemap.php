<?php
/**
 * Dynamic XML Sitemap Generator for Livvra.in
 * Access: https://livvra.in/sitemap.php
 */
require_once __DIR__ . '/includes/database.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$today = date('Y-m-d');
$baseUrl = 'https://livvra.in';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// Static pages
$staticPages = [
    ['loc' => '/',             'priority' => '1.00', 'changefreq' => 'daily'],
    ['loc' => '/products.php', 'priority' => '0.90', 'changefreq' => 'daily'],
    ['loc' => '/blog.php',     'priority' => '0.80', 'changefreq' => 'weekly'],
    ['loc' => '/about.php',    'priority' => '0.70', 'changefreq' => 'monthly'],
    ['loc' => '/contact.php',  'priority' => '0.70', 'changefreq' => 'monthly'],
    ['loc' => '/benefits.php', 'priority' => '0.60', 'changefreq' => 'monthly'],
];

foreach ($staticPages as $p) {
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}{$p['loc']}</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>{$p['changefreq']}</changefreq>\n";
    echo "    <priority>{$p['priority']}</priority>\n";
    echo "  </url>\n";
}

// Product pages
try {
    $stmt = db()->query("SELECT slug, name, image, updated_at FROM products WHERE is_active = 1 ORDER BY id");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $p) {
        if (empty($p['slug'])) continue;
        $loc = $baseUrl . '/' . htmlspecialchars($p['slug']);
        $lastmod = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : $today;
        echo "  <url>\n";
        echo "    <loc>{$loc}</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.85</priority>\n";
        if (!empty($p['image'])) {
            $imgUrl = $baseUrl . '/uploads/products/' . htmlspecialchars($p['image']);
            echo "    <image:image>\n";
            echo "      <image:loc>{$imgUrl}</image:loc>\n";
            echo "      <image:title>" . htmlspecialchars($p['name']) . "</image:title>\n";
            echo "    </image:image>\n";
        }
        echo "  </url>\n";
    }
} catch (Exception $e) {}

// Blog posts
try {
    $stmt = db()->query("SELECT slug, updated_at FROM blog_posts WHERE is_active = 1 ORDER BY id");
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($blogs as $b) {
        if (empty($b['slug'])) continue;
        $loc = $baseUrl . '/blog/' . htmlspecialchars($b['slug']);
        $lastmod = !empty($b['updated_at']) ? date('Y-m-d', strtotime($b['updated_at'])) : $today;
        echo "  <url>\n";
        echo "    <loc>{$loc}</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "    <changefreq>monthly</changefreq>\n";
        echo "    <priority>0.65</priority>\n";
        echo "  </url>\n";
    }
} catch (Exception $e) {}

// Category pages
try {
    $stmt = db()->query("SELECT slug, name FROM categories WHERE is_active = 1 ORDER BY id");
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cats as $c) {
        if (empty($c['slug'])) continue;
        echo "  <url>\n";
        echo "    <loc>{$baseUrl}/products.php?category=" . urlencode($c['slug']) . "</loc>\n";
        echo "    <lastmod>{$today}</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.75</priority>\n";
        echo "  </url>\n";
    }
} catch (Exception $e) {}

echo '</urlset>';
