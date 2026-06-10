<?php
/**
 * One-time script: Update Kumkumadi Beauty Oil long_description in DB
 * Run once from CLI: php scripts/update-kumkumadi-desc.php
 * Or visit: /scripts/update-kumkumadi-desc.php?secret=livvra2025
 */

// Web access guard
if (php_sapi_name() !== 'cli') {
    if (($_GET['secret'] ?? '') !== 'livvra2025') {
        http_response_code(403);
        die('Access denied.');
    }
}

require_once __DIR__ . '/../includes/config.php';

$descHtml = file_get_contents(__DIR__ . '/../assets/descriptions/kumkumadi-beauty-oil.html');
if (!$descHtml) {
    die("ERROR: Could not read description HTML file.\n");
}

// Find Kumkumadi product by name or slug
$stmt = db()->prepare("SELECT id, name, slug FROM products WHERE LOWER(name) LIKE ? OR LOWER(slug) LIKE ? LIMIT 5");
$stmt->execute(['%kumkumadi%', '%kumkumadi%']);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo "No Kumkumadi product found in database.\n";
    echo "Available products:\n";
    $all = db()->query("SELECT id, name, slug FROM products ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $p) echo "  ID {$p['id']}: {$p['name']} ({$p['slug']})\n";
    exit;
}

echo "Found " . count($products) . " product(s):\n";
foreach ($products as $p) {
    echo "  ID {$p['id']}: {$p['name']} ({$p['slug']})\n";
}

// Update the first match
$product = $products[0];
$updateStmt = db()->prepare("UPDATE products SET long_description = ? WHERE id = ?");
$result = $updateStmt->execute([$descHtml, $product['id']]);

if ($result) {
    echo "\n✅ SUCCESS: Updated long_description for '{$product['name']}' (ID: {$product['id']})\n";
    echo "🔗 View product: https://livvra.in/{$product['slug']}\n";
} else {
    echo "\n❌ FAILED: Could not update product.\n";
}
