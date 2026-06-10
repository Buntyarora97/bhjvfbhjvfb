<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/image_upload.php';
require_once '../includes/models/Product.php';
require_once '../includes/models/Category.php';
require_once '../includes/models/ProductImage.php';
require_once '../includes/models/Setting.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Edit Product';
$currentPage = 'products';

$id = $_GET['id'] ?? 0;
$product = Product::getById($id);

if (!$product) {
    header('Location: products.php');
    exit;
}

$categories = Category::getAll(true);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = $_POST;
        $data['is_featured'] = isset($_POST['is_featured']) ? (int)$_POST['is_featured'] : 0;
        $data['is_active']   = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $data['price']       = (float)($_POST['price'] ?? 0);
        $data['mrp']         = (float)($_POST['mrp'] ?? $data['price']);
        $data['stock_qty']   = (int)($_POST['stock_qty'] ?? 0);
        $data['stock_status'] = $_POST['stock_status'] ?? 'in_stock';
        $data['category_id'] = (int)($_POST['category_id'] ?? 0);
        
        // 🚀 SHIPROCKET DIMENSIONS - Capture from form
        $data['weight_kg'] = (float)($_POST['weight_kg'] ?? 0.5);
        $data['length_cm'] = (float)($_POST['length_cm'] ?? 15);
        $data['width_cm'] = (float)($_POST['width_cm'] ?? 10);
        $data['height_cm'] = (float)($_POST['height_cm'] ?? 8);

        // 🔥 AUTO STOCK CONTROL LOGIC
        if ($data['stock_status'] === 'out_of_stock') {
            $data['stock_qty'] = 0;
        }

        if ($data['stock_qty'] <= 0) {
            $data['stock_status'] = 'out_of_stock';
        } else {
            $data['stock_status'] = 'in_stock';
        }

        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!empty($_FILES['image']['name'])) {
            $saved = saveUploadedMedia($_FILES['image'], $uploadDir, uniqid('img_', true));
            if ($saved) $data['image'] = $saved;
        }

        if (!empty($_FILES['video']['name'])) {
            $saved = saveUploadedMedia($_FILES['video'], $uploadDir, uniqid('vid_', true));
            if ($saved) $data['video'] = $saved;
        }

        Product::update($id, $data);

        if (!empty($_FILES['gallery_media']['name'][0])) {
            foreach ($_FILES['gallery_media']['name'] as $key => $name) {
                if ($_FILES['gallery_media']['error'][$key] !== 0) continue;
                $entry = [
                    'name'     => $name,
                    'type'     => $_FILES['gallery_media']['type'][$key],
                    'tmp_name' => $_FILES['gallery_media']['tmp_name'][$key],
                    'error'    => $_FILES['gallery_media']['error'][$key],
                ];
                $saved = saveUploadedMedia($entry, $uploadDir, uniqid('gallery_', true));
                if (!$saved) continue;
                $type = (strpos($entry['type'], 'video') !== false) ? 'video' : 'image';
                db()->prepare("INSERT INTO product_media (product_id, media_url, media_type) VALUES (?, ?, ?)")->execute([$id, $saved, $type]);
            }
        }

        $success = 'Product updated successfully!';
        $product = Product::getById($id);
    } catch (Exception $e) {
        $error = 'Failed to update product: ' . $e->getMessage();
    }
}

if (isset($_GET['delete_media'])) {
    try {
        $mediaId = (int)$_GET['delete_media'];
        db()->prepare("DELETE FROM product_media WHERE id = ?")->execute([$mediaId]);
        $success = 'Media deleted successfully!';
    } catch (Exception $e) {
        $error = 'Failed to delete media: ' . $e->getMessage();
    }
}

// Handle pack links save (separate action)
if (isset($_POST['save_packs'])) {
    $packs = [];
    $labels      = $_POST['pk_label']      ?? [];
    $sizeTexts   = $_POST['pk_size_text']  ?? [];
    $prices      = $_POST['pk_price']      ?? [];
    $mrps        = $_POST['pk_mrp']        ?? [];
    $productIds  = $_POST['pk_product_id'] ?? [];
    $slugs       = $_POST['pk_slug']       ?? [];
    $badges      = $_POST['pk_badge']      ?? [];
    $badgeColors = $_POST['pk_badge_color']?? [];

    foreach ($labels as $i => $lbl) {
        if (trim($lbl) === '') continue;
        $packs[] = [
            'label'      => trim($lbl),
            'size_text'  => trim($sizeTexts[$i] ?? ''),
            'price'      => (float)($prices[$i] ?? 0),
            'mrp'        => (float)($mrps[$i] ?? 0),
            'product_id' => (int)($productIds[$i] ?? 0),
            'slug'       => trim($slugs[$i] ?? ''),
            'badge'      => trim($badges[$i] ?? ''),
            'badge_color'=> trim($badgeColors[$i] ?? '#4f7c5b'),
        ];
    }
    Setting::set('product_packs_' . $id, json_encode($packs));
    $success = 'Pack links saved!';
    $product = Product::getById($id);
}

// Read existing pack links
$existing_packs_json = Setting::get('product_packs_' . $id, '');
$existing_packs = [];
if ($existing_packs_json) {
    $dec = json_decode($existing_packs_json, true);
    if (is_array($dec)) $existing_packs = $dec;
}
// Ensure at least 3 empty rows
while (count($existing_packs) < 3) {
    $existing_packs[] = ['label'=>'','size_text'=>'','price'=>'','mrp'=>'','product_id'=>'','slug'=>'','badge'=>'','badge_color'=>'#4f7c5b'];
}

require_once 'views/layouts/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>Edit Product: <?php echo htmlspecialchars($product['name']); ?></h2>
        <a href="products.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="editProductName" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="sku">SKU</label>
                    <input type="text" id="sku" name="sku" class="form-control" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Product URL Slug <span style="color:#888;font-weight:400;font-size:12px;">(URL mein kya dikhe) — Edit karo ya khali chhodo toh name se auto-ban jaayega</span></label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:#888;font-size:13px;white-space:nowrap;">/product/</span>
                    <input type="text" name="slug" id="editSlugInput" class="form-control" value="<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" placeholder="shilajit-gold-capsules" style="font-family:monospace;font-size:13px;" pattern="[a-z0-9-]*">
                </div>
                <small style="color:#888;">Current URL: <strong>/product/<?php echo htmlspecialchars($product['slug'] ?? ''); ?></strong> — Sirf chhote letters, numbers aur hyphen (-) use karo. Badalne pe purana URL kaam nahi karega!</small>
            </div>
            <script>
            document.getElementById('editProductName').addEventListener('input', function() {
                var slugField = document.getElementById('editSlugInput');
                if (!slugField._userEdited) {
                    slugField.value = this.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                }
            });
            document.getElementById('editSlugInput').addEventListener('input', function() { this._userEdited = true; });
            </script>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="stock_qty">Stock Quantity</label>
                    <input type="number" id="stock_qty" name="stock_qty" class="form-control" value="<?php echo $product['stock_qty']; ?>">
                </div>
                <div class="form-group">
                    <label>Stock Status</label>
                    <select name="stock_status" class="form-control">
                        <option value="in_stock" <?php echo ($product['stock_status'] ?? 'in_stock') == 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                        <option value="out_of_stock" <?php echo ($product['stock_status'] ?? 'in_stock') == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price">Selling Price (₹) *</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" value="<?php echo $product['price']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="mrp">MRP (₹)</label>
                    <input type="number" id="mrp" name="mrp" class="form-control" step="0.01" value="<?php echo $product['mrp']; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="offer_price">Offer Price (₹)</label>
                    <input type="number" id="offer_price" name="offer_price" class="form-control" step="0.01" value="<?php echo $product['offer_price'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="offer_label">Offer Label (e.g. 20% OFF)</label>
                    <input type="text" id="offer_label" name="offer_label" class="form-control" value="<?php echo htmlspecialchars($product['offer_label'] ?? ''); ?>">
                </div>
            </div>
            
            <!-- 🚀 SHIPROCKET DIMENSIONS SECTION -->
            <div style="border: 2px solid #007bff; background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 10px;">
                <h4 style="color: #007bff; margin-top: 0;">
                    <i class="fas fa-shipping-fast"></i> Shiprocket Shipping Dimensions
                </h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="weight_kg">Weight (kg) *</label>
                        <input type="number" id="weight_kg" name="weight_kg" class="form-control" step="0.001" min="0.001" 
                               value="<?php echo $product['weight_kg'] ?? 0.5; ?>" required>
                        <small style="color: #666;">Example: 0.5 for 500g, 1.2 for 1.2kg</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="length_cm">Length (cm) *</label>
                        <input type="number" id="length_cm" name="length_cm" class="form-control" step="0.1" min="1" 
                               value="<?php echo $product['length_cm'] ?? 15; ?>" required>
                        <small style="color: #666;">Package length in cm</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="width_cm">Width (cm) *</label>
                        <input type="number" id="width_cm" name="width_cm" class="form-control" step="0.1" min="1" 
                               value="<?php echo $product['width_cm'] ?? 10; ?>" required>
                        <small style="color: #666;">Package width in cm</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="height_cm">Height (cm) *</label>
                        <input type="number" id="height_cm" name="height_cm" class="form-control" step="0.1" min="1" 
                               value="<?php echo $product['height_cm'] ?? 8; ?>" required>
                        <small style="color: #666;">Package height in cm</small>
                    </div>
                </div>
                
                <!-- Volumetric Weight Display -->
                <div style="background: #e9ecef; padding: 15px; border-radius: 8px; margin-top: 15px;">
                    <h5 style="margin-top: 0; color: #495057;">📦 Shipping Weight Calculation</h5>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <strong>Dead Weight:</strong> <span id="display_weight"><?php echo $product['weight_kg'] ?? 0.5; ?></span> kg
                        </div>
                        <div>
                            <strong>Volumetric Weight:</strong> <span id="display_volumetric">
                                <?php 
                                $l = $product['length_cm'] ?? 15;
                                $w = $product['width_cm'] ?? 10;
                                $h = $product['height_cm'] ?? 8;
                                echo round(($l * $w * $h) / 5000, 3);
                                ?>
                            </span> kg
                        </div>
                        <div style="color: #007bff; font-weight: bold;">
                            <strong>Chargeable Weight:</strong> <span id="display_chargeable">
                                <?php 
                                $dead = $product['weight_kg'] ?? 0.5;
                                $vol = ($l * $w * $h) / 5000;
                                echo round(max($dead, $vol), 3);
                                ?>
                            </span> kg
                        </div>
                    </div>
                    <small style="color: #6c757d; display: block; margin-top: 10px;">
                        * Shiprocket charges based on whichever is higher: actual weight or volumetric weight (L×W×H/5000)
                    </small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="short_description">Short Description</label>
                <textarea id="short_description" name="short_description" class="form-control" rows="2"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="long_description">Long Description</label>
                <textarea id="long_description" name="long_description" class="form-control" rows="5"><?php echo htmlspecialchars($product['long_description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="benefits">Benefits (comma separated)</label>
                <input type="text" id="benefits" name="benefits" class="form-control" value="<?php echo htmlspecialchars($product['benefits'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Main Product Image</label>
                    <?php if ($product['image']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="" style="max-height: 100px; border-radius: 8px;">
                    </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Main Product Video</label>
                    <?php if (isset($product['video']) && $product['video']): ?>
                    <div style="margin-bottom: 10px;">
                        <video style="max-height: 100px; border-radius: 8px;" muted controls><source src="../uploads/products/<?php echo htmlspecialchars($product['video']); ?>"></video>
                    </div>
                    <?php endif; ?>
                    <input type="file" id="video" name="video" class="form-control" accept="video/*">
                </div>
            </div>

            <div class="form-group">
                <label>Product Gallery (Multiple Images & Videos)</label>
                <?php 
                $stmt = db()->prepare("SELECT * FROM product_media WHERE product_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$id]);
                $galleryMedia = $stmt->fetchAll();
                if (!empty($galleryMedia)): ?>
                <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                    <h4 style="margin-top: 0;">Current Gallery</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                        <?php foreach ($galleryMedia as $media): ?>
                        <div style="position: relative; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                            <?php if ($media['media_type'] === 'video'): ?>
                                <video style="width: 100%; height: 150px; object-fit: cover;" muted><source src="../uploads/products/<?php echo htmlspecialchars($media['media_url']); ?>"></video>
                            <?php else: ?>
                                <img src="../uploads/products/<?php echo htmlspecialchars($media['media_url']); ?>" alt="" style="width: 100%; height: 150px; object-fit: cover;">
                            <?php endif; ?>
                            <a href="?id=<?php echo $id; ?>&delete_media=<?php echo $media['id']; ?>" class="btn btn-danger btn-sm" style="position: absolute; top: 5px; right: 5px;" onclick="return confirm('Delete this media?');"><i class="fas fa-trash"></i></a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <input type="file" name="gallery_media[]" class="form-control" accept="image/*,video/*" multiple>
                <small style="color: #666;">Select multiple images or videos to add to gallery</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="is_featured">Featured Product</label>
                    <select id="is_featured" name="is_featured" class="form-control">
                        <option value="0" <?php echo !$product['is_featured'] ? 'selected' : ''; ?>>No</option>
                        <option value="1" <?php echo $product['is_featured'] ? 'selected' : ''; ?>>Yes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="is_active">Status</label>
                    <select id="is_active" name="is_active" class="form-control">
                        <option value="1" <?php echo $product['is_active'] ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo !$product['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
        </form>
    </div>
</div>

<!-- ===== PACK / SIZE SELECTOR CONFIGURATION ===== -->
<div class="card" style="margin-top: 30px;">
    <div class="card-header" style="background:#f0fdf4; border-bottom:2px solid #d1fae5;">
        <h2 style="color:#0f3d2e; font-size:1rem;">📦 Pack / Size Selector (Product Detail Page)</h2>
        <small style="color:#6b7280;">Yahan aap 3 pack options set karo (e.g. 1 Bottle, 2 Bottle, 4 Bottle). Yeh product detail page pe "Size" section mein automatically dikhega.</small>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="save_packs" value="1">
            <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#f9fafb; border-bottom:2px solid #e5e7eb;">
                        <th style="padding:10px 8px; text-align:left; color:#374151; font-weight:700;">#</th>
                        <th style="padding:10px 8px; text-align:left; color:#374151; font-weight:700;">Pack Label <small style="font-weight:400; color:#6b7280;">(e.g. Starter Pack)</small></th>
                        <th style="padding:10px 8px; text-align:left; color:#374151; font-weight:700;">Size Text <small style="font-weight:400; color:#6b7280;">(e.g. 500ml x 1)</small></th>
                        <th style="padding:10px 8px; text-align:left; color:#374151; font-weight:700;">Sale Price ₹</th>
                        <th style="padding:10px 8px; text-align:left; color:#374151; font-weight:700;">MRP ₹</th>
                        <th style="padding:10px 8px; text-align:left; color:#374151; font-weight:700;">Product ID</th>
                        <th style="padding:10px 8px; text-align:left; color:#374151; font-weight:700;">Slug <small style="font-weight:400; color:#6b7280;">(URL, e.g. shilajit-combo-4)</small></th>
                        <th style="padding:10px 8px; text-align:left; color:#374151; font-weight:700;">Badge <small style="font-weight:400; color:#6b7280;">(e.g. Best Seller)</small></th>
                        <th style="padding:10px 8px; text-align:left; color:#374151; font-weight:700;">Badge Color</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($existing_packs as $pi => $pk): ?>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:8px; color:#888; font-weight:600;"><?php echo $pi+1; ?></td>
                        <td style="padding:8px;"><input type="text" name="pk_label[]" class="form-control" style="min-width:130px;" placeholder="e.g. Starter Pack" value="<?php echo htmlspecialchars($pk['label']??''); ?>"></td>
                        <td style="padding:8px;"><input type="text" name="pk_size_text[]" class="form-control" style="min-width:120px;" placeholder="e.g. 500ml x 1" value="<?php echo htmlspecialchars($pk['size_text']??''); ?>"></td>
                        <td style="padding:8px;"><input type="number" name="pk_price[]" class="form-control" style="min-width:90px;" placeholder="0" step="0.01" value="<?php echo htmlspecialchars($pk['price']??''); ?>"></td>
                        <td style="padding:8px;"><input type="number" name="pk_mrp[]" class="form-control" style="min-width:90px;" placeholder="0" step="0.01" value="<?php echo htmlspecialchars($pk['mrp']??''); ?>"></td>
                        <td style="padding:8px;"><input type="number" name="pk_product_id[]" class="form-control" style="min-width:90px;" placeholder="Product ID" value="<?php echo htmlspecialchars($pk['product_id']??''); ?>"></td>
                        <td style="padding:8px;"><input type="text" name="pk_slug[]" class="form-control" style="min-width:140px;" placeholder="product-slug" value="<?php echo htmlspecialchars($pk['slug']??''); ?>"></td>
                        <td style="padding:8px;"><input type="text" name="pk_badge[]" class="form-control" style="min-width:110px;" placeholder="Best Seller" value="<?php echo htmlspecialchars($pk['badge']??''); ?>"></td>
                        <td style="padding:8px;"><input type="color" name="pk_badge_color[]" class="form-control" style="min-width:50px; height:38px; padding:2px;" value="<?php echo htmlspecialchars($pk['badge_color']??'#4f7c5b'); ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <div style="margin-top:16px; display:flex; gap:12px; align-items:center;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Pack Links</button>
                <small style="color:#6b7280;">💡 Tip: Product ID column mein wo product ka ID daalo jo us pack mein hai. Slug = URL path (e.g. <code>shilajit-combo-4</code>). Empty rows ignore ho jaate hain.</small>
            </div>
        </form>
    </div>
</div>

<!-- 🚀 JavaScript for Real-time Volumetric Weight Calculation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const weightInput = document.getElementById('weight_kg');
    const lengthInput = document.getElementById('length_cm');
    const widthInput = document.getElementById('width_cm');
    const heightInput = document.getElementById('height_cm');
    
    const displayWeight = document.getElementById('display_weight');
    const displayVolumetric = document.getElementById('display_volumetric');
    const displayChargeable = document.getElementById('display_chargeable');
    
    function calculateShippingWeight() {
        const weight = parseFloat(weightInput.value) || 0;
        const length = parseFloat(lengthInput.value) || 0;
        const width = parseFloat(widthInput.value) || 0;
        const height = parseFloat(heightInput.value) || 0;
        
        // Volumetric weight formula: (L × W × H) / 5000
        const volumetric = (length * width * height) / 5000;
        const chargeable = Math.max(weight, volumetric);
        
        displayWeight.textContent = weight.toFixed(3);
        displayVolumetric.textContent = volumetric.toFixed(3);
        displayChargeable.textContent = chargeable.toFixed(3);
    }
    
    // Add event listeners
    [weightInput, lengthInput, widthInput, heightInput].forEach(input => {
        input.addEventListener('input', calculateShippingWeight);
    });
});
</script>

<?php require_once 'views/layouts/footer.php'; ?>