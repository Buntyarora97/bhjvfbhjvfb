<?php
// Preview page for Kumkumadi description
// Visit: /kumkumadi-preview.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Preview — Kumkumadi Beauty Oil Description</title>
<link rel="stylesheet" href="/assets/css/tailwind.min.css">
<style>
body { background: #f5f5f5; margin: 0; padding: 0; }
.preview-bar {
    position: sticky; top: 0; z-index: 999;
    background: #5c0a2a; color: #fff;
    padding: 12px 24px;
    display: flex; align-items: center; justify-content: space-between;
    font-family: sans-serif; font-size: 13px;
    box-shadow: 0 2px 12px rgba(0,0,0,.2);
}
.preview-bar strong { color: #c5a059; }
.preview-bar a {
    background: #c5a059; color: #5c0a2a;
    padding: 6px 16px; border-radius: 20px;
    font-weight: 700; text-decoration: none; font-size: 12px;
}
.preview-content {
    max-width: 1280px; margin: 0 auto;
    background: #fff;
    box-shadow: 0 0 40px rgba(0,0,0,.08);
}
</style>
</head>
<body>
<div class="preview-bar">
    <div>👁️ Preview — <strong>Kumkumadi Beauty Oil</strong> — Long Description</div>
    <a href="/admin/product-edit.php" target="_blank">Open Admin to Save →</a>
</div>
<div class="preview-content">
<?php include __DIR__ . '/assets/descriptions/kumkumadi-beauty-oil.html'; ?>
</div>
</body>
</html>
