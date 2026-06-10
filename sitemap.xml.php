<?php
/**
 * Dynamic XML Sitemap Generator
 * - Auto-includes every published blog from the DB (URL: /blog/{slug})
 * - Includes all key static content pages
 * - Tries to include products & categories from the DB if those tables exist
 *
 * Served at /sitemap.xml via .htaccess rewrite.
 */

require_once __DIR__ . '/includes/config.php';

// Always emit valid XML, even on error
header('Content-Type: application/xml; charset=UTF-8');

// Build absolute URL prefix from current request
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'www.livvra.in';
$base   = $scheme . '://' . $host;

// ---------- Build URL list ----------
$urls = [];

// 1. Homepage
$urls[] = ['loc' => $base . '/', 'changefreq' => 'daily',  'priority' => '1.0'];

// 2. Key static pages (curated list — anything that's real public content)
$staticPages = [
    '/blog.php'                                                  => ['changefreq' => 'daily',   'priority' => '0.9'],
    '/products.php'                                              => ['changefreq' => 'daily',   'priority' => '0.9'],
    '/about.php'                                                 => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/contact.php'                                               => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/benefits.php'                                              => ['changefreq' => 'monthly', 'priority' => '0.6'],
    '/livvrareview.php'                                          => ['changefreq' => 'weekly',  'priority' => '0.6'],
    '/track-order.php'                                           => ['changefreq' => 'monthly', 'priority' => '0.5'],
    '/privacy-policy.php'                                        => ['changefreq' => 'yearly',  'priority' => '0.3'],
    '/terms-conditions.php'                                      => ['changefreq' => 'yearly',  'priority' => '0.3'],
    '/terms-and-conditions.php'                                  => ['changefreq' => 'yearly',  'priority' => '0.3'],
    '/shipping-policy.php'                                       => ['changefreq' => 'yearly',  'priority' => '0.3'],
    '/refund-cancellation.php'                                   => ['changefreq' => 'yearly',  'priority' => '0.3'],
    '/Cancellation-Returns-Refunds-Policy.php'                   => ['changefreq' => 'yearly',  'priority' => '0.3'],

    // Existing hard-coded article pages (kept for backward compatibility)
    '/7-step-daily-ayurvedic-routine-optimal-health.php'         => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/ayurveda-2026-modern-preventive-healthcare.php'            => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/ayurveda-modern-diets-balanced-weight-management.php'      => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/benefits-of-kumkumadi-beauty-oil.php'                      => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/core-principles-of-ayurveda-modern-life.php'               => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/daily-energy-nutrition-and-skin-care-problems-explained.php'=> ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/daily-immunity-boost-ayurvedic-herbs-modern-wellness.php'  => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/dy-b-fuel-ras-ayurvedic-support.php'                       => ['changefreq' => 'monthly', 'priority' => '0.7'],
    '/is-yeast-based-protein-safe-for-daily-consumption.php'     => ['changefreq' => 'monthly', 'priority' => '0.7'],
];

foreach ($staticPages as $path => $opts) {
    $abs = __DIR__ . $path;
    // Skip pages that don't exist on this install
    if (!file_exists($abs)) continue;
    $urls[] = [
        'loc'        => $base . $path,
        'lastmod'    => date('c', filemtime($abs)),
        'changefreq' => $opts['changefreq'],
        'priority'   => $opts['priority'],
    ];
}

// 3. Published blogs from DB
try {
    if (class_exists('Blog')) {
        $blogs = Blog::getPublished(500);
        foreach ($blogs as $b) {
            $lastmod = !empty($b['update_date']) ? $b['update_date']
                     : (!empty($b['publish_date']) ? $b['publish_date']
                     : (!empty($b['updated_at'])  ? $b['updated_at']
                     : (!empty($b['created_at'])  ? $b['created_at']
                     : date('Y-m-d'))));
            $urls[] = [
                'loc'        => $base . '/blog/' . rawurlencode($b['slug']),
                'lastmod'    => date('c', strtotime($lastmod)),
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ];
        }
    }
} catch (Exception $e) { /* table missing — ignore */ }

// 4. Products from DB (if products table exists)
try {
    $db  = Database::getInstance();
    $pdo = $db->getConnection();
    $stmt = $pdo->query("SELECT id, slug, updated_at FROM products WHERE is_active = 1 ORDER BY id");
    if ($stmt) {
        while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $loc = !empty($p['slug'])
                 ? $base . '/' . rawurlencode($p['slug'])
                 : $base . '/product-detail.php?id=' . (int)$p['id'];
            $urls[] = [
                'loc'        => $loc,
                'lastmod'    => !empty($p['updated_at']) ? date('c', strtotime($p['updated_at'])) : date('c'),
                'changefreq' => 'weekly',
                'priority'   => '0.7',
            ];
        }
    }
} catch (Exception $e) { /* products table not present in this env */ }

// 5. Categories from DB (if categories table exists)
try {
    if (!isset($pdo)) {
        $db  = Database::getInstance();
        $pdo = $db->getConnection();
    }
    $stmt = $pdo->query("SELECT id, slug FROM categories WHERE is_active = 1 ORDER BY id");
    if ($stmt) {
        while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $urls[] = [
                'loc'        => $base . '/products.php?category=' . (!empty($c['slug']) ? rawurlencode($c['slug']) : (int)$c['id']),
                'changefreq' => 'weekly',
                'priority'   => '0.6',
            ];
        }
    }
} catch (Exception $e) { /* categories table not present */ }

// ---------- Emit XML ----------
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    if (!empty($u['lastmod']))    echo "    <lastmod>"    . htmlspecialchars($u['lastmod'])    . "</lastmod>\n";
    if (!empty($u['changefreq'])) echo "    <changefreq>" . htmlspecialchars($u['changefreq']) . "</changefreq>\n";
    if (!empty($u['priority']))   echo "    <priority>"   . htmlspecialchars($u['priority'])   . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
