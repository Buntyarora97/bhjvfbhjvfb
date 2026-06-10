<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/image_upload.php';
require_once __DIR__ . '/../includes/models/HomeBanner.php';

if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle = 'Home Banners';
$message = '';
$error = '';

// Handle DELETE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    HomeBanner::delete((int)$_GET['delete']);
    header('Location: home-banners.php?msg=deleted');
    exit;
}

// Handle POST (create/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $uploadDir = '../uploads/banners/';

    if (isset($_FILES['desktop_image']) && $_FILES['desktop_image']['error'] == 0) {
        $base = time() . '_banner_desktop';
        $saved = saveUploadedMedia($_FILES['desktop_image'], $uploadDir, $base);
        if ($saved) $data['desktop_image'] = 'uploads/banners/' . $saved;
    }
    if (isset($_FILES['mobile_image']) && $_FILES['mobile_image']['error'] == 0) {
        $base = time() . '_banner_mobile';
        $saved = saveUploadedMedia($_FILES['mobile_image'], $uploadDir, $base);
        if ($saved) $data['mobile_image'] = 'uploads/banners/' . $saved;
    }

    if (!empty($_POST['id'])) {
        HomeBanner::update((int)$_POST['id'], $data);
        $message = 'Banner updated successfully!';
    } else {
        HomeBanner::create($data);
        $message = 'Banner added successfully!';
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $message = 'Banner deleted.';
}

$banners = HomeBanner::getAll();
$editBanner = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editBanner = HomeBanner::getById((int)$_GET['edit']);
}

require_once 'views/layouts/header.php';
?>
<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1><i class="fas fa-images"></i> Home Banners (Hero Section)</h1>
    <a href="home-page.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Home Page</a>
</div>

<?php if ($message): ?><div class="alert alert-success" style="padding:12px;background:#d4edda;color:#155724;border-radius:6px;margin-bottom:20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="admin-card" style="margin-bottom:30px;">
    <h3 style="margin-bottom:20px;"><?= $editBanner ? 'Edit Banner' : 'Add New Banner' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?php if ($editBanner): ?><input type="hidden" name="id" value="<?= $editBanner['id'] ?>"><?php endif; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Desktop Banner Image <span style="color:#999;font-weight:400;">(Landscape, e.g. 1200×400px)</span></label>
                <?php if ($editBanner && $editBanner['desktop_image']): ?>
                    <img src="../<?= htmlspecialchars($editBanner['desktop_image']) ?>" style="width:100%;max-height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
                <?php endif; ?>
                <input type="file" name="desktop_image" class="form-control" accept="image/*">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Mobile Banner Image <span style="color:#999;font-weight:400;">(Portrait, e.g. 600×400px)</span></label>
                <?php if ($editBanner && $editBanner['mobile_image']): ?>
                    <img src="../<?= htmlspecialchars($editBanner['mobile_image']) ?>" style="width:100%;max-height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
                <?php endif; ?>
                <input type="file" name="mobile_image" class="form-control" accept="image/*">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Click Link URL (optional)</label>
                <input type="text" name="link_url" class="form-control" placeholder="e.g. products.php" value="<?= htmlspecialchars($editBanner['link_url'] ?? '') ?>">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Display Order</label>
                <input type="number" name="display_order" class="form-control" value="<?= (int)($editBanner['display_order'] ?? 0) ?>" min="0">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Status</label>
                <select name="is_active" class="form-control">
                    <option value="1" <?= (!$editBanner || $editBanner['is_active']) ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($editBanner && !$editBanner['is_active']) ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editBanner ? 'Update Banner' : 'Add Banner' ?></button>
            <?php if ($editBanner): ?><a href="home-banners.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-bottom:20px;">All Banners (<?= count($banners) ?>)</h3>
    <?php if (empty($banners)): ?>
        <p style="color:#999;text-align:center;padding:30px;">No banners yet. Add your first banner above.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="table">
        <thead><tr><th>#</th><th>Desktop Preview</th><th>Mobile Preview</th><th>Link</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($banners as $b): ?>
        <tr>
            <td><?= $b['id'] ?></td>
            <td><?php if ($b['desktop_image']): ?><img src="../<?= htmlspecialchars($b['desktop_image']) ?>" style="height:50px;border-radius:4px;object-fit:cover;"><?php else: ?><span style="color:#ccc;">None</span><?php endif; ?></td>
            <td><?php if ($b['mobile_image']): ?><img src="../<?= htmlspecialchars($b['mobile_image']) ?>" style="height:50px;border-radius:4px;object-fit:cover;"><?php else: ?><span style="color:#ccc;">None</span><?php endif; ?></td>
            <td><?= htmlspecialchars($b['link_url'] ?: '-') ?></td>
            <td><?= $b['display_order'] ?></td>
            <td><span style="padding:3px 10px;border-radius:20px;font-size:12px;background:<?= $b['is_active'] ? '#d4edda' : '#f8d7da' ?>;color:<?= $b['is_active'] ? '#155724' : '#721c24' ?>;"><?= $b['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td>
                <a href="home-banners.php?edit=<?= $b['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Edit</a>
                <a href="home-banners.php?delete=<?= $b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this banner?')"><i class="fas fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'views/layouts/footer.php'; ?>
