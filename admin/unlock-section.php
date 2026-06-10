<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/image_upload.php';
require_once __DIR__ . '/../includes/models/Setting.php';

if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle = 'Unlock Offers Section';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle background image upload
    if (isset($_FILES['unlock_bg_file']) && $_FILES['unlock_bg_file']['error'] == 0) {
        $base = time() . '_unlock_bg';
        $saved = saveUploadedMedia($_FILES['unlock_bg_file'], '../uploads/banners/', $base);
        if ($saved) Setting::set('unlock_bg_image', 'uploads/banners/' . $saved);
    }
    $keys = [
        'unlock_title','unlock_mail_title','unlock_whatsapp_number',
        'unlock_instagram_url','unlock_facebook_url','unlock_youtube_url',
        'unlock_link1_text','unlock_link1_url',
        'unlock_link2_text','unlock_link2_url',
        'unlock_link3_text','unlock_link3_url'
    ];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) Setting::set($k, $_POST[$k]);
    }
    $message = 'Section updated successfully!';
}

$s = Setting::getAll();

require_once 'views/layouts/header.php';
?>
<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1><i class="fas fa-gift"></i> Unlock Offers & Subscribe Section</h1>
    <a href="home-page.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php if ($message): ?><div style="padding:12px;background:#d4edda;color:#155724;border-radius:6px;margin-bottom:20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">Background Image</h3>
    <?php $bgImg = $s['unlock_bg_image'] ?? ''; ?>
    <?php if ($bgImg): ?>
        <div style="margin-bottom:12px;border-radius:8px;overflow:hidden;max-height:160px;">
            <img src="../<?= htmlspecialchars($bgImg) ?>" style="width:100%;object-fit:cover;max-height:160px;" onerror="this.style.display='none'">
        </div>
        <p style="color:#666;font-size:13px;margin-bottom:8px;">Current: <code><?= htmlspecialchars($bgImg) ?></code></p>
    <?php endif; ?>
    <label style="font-weight:600;display:block;margin-bottom:6px;">Upload New Background Image</label>
    <input type="file" name="unlock_bg_file" class="form-control" accept="image/*">
    <p style="color:#999;font-size:13px;margin-top:6px;">Recommended: dark textured image, landscape orientation</p>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">Titles & WhatsApp</h3>
    <div style="display:grid;gap:16px;">
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Main Title</label>
            <input type="text" name="unlock_title" class="form-control" value="<?= htmlspecialchars($s['unlock_title'] ?? 'unlock offers & subscribe for content') ?>">
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Mailing List Title</label>
            <input type="text" name="unlock_mail_title" class="form-control" value="<?= htmlspecialchars($s['unlock_mail_title'] ?? 'JOIN OUR MAILING LIST') ?>">
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">WhatsApp Number <span style="color:#999;font-weight:400;">(with country code, e.g. 918958489684)</span></label>
            <input type="text" name="unlock_whatsapp_number" class="form-control" value="<?= htmlspecialchars($s['unlock_whatsapp_number'] ?? '') ?>">
        </div>
    </div>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">Navigation Links (3 links)</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <?php for ($i=1; $i<=3; $i++): ?>
        <div style="background:#f8faf9;padding:16px;border-radius:8px;grid-column:<?= $i==3 ? 'span 2' : 'span 1' ?>;">
            <label style="font-weight:600;display:block;margin-bottom:6px;">Link <?= $i ?> Text</label>
            <input type="text" name="unlock_link<?= $i ?>_text" class="form-control" value="<?= htmlspecialchars($s["unlock_link{$i}_text"] ?? '') ?>" style="margin-bottom:8px;">
            <label style="font-weight:600;display:block;margin-bottom:6px;">Link <?= $i ?> URL</label>
            <input type="text" name="unlock_link<?= $i ?>_url" class="form-control" value="<?= htmlspecialchars($s["unlock_link{$i}_url"] ?? '') ?>">
        </div>
        <?php endfor; ?>
    </div>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">Social Media Links</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;"><i class="fab fa-instagram" style="color:#E1306C;"></i> Instagram URL</label>
            <input type="text" name="unlock_instagram_url" class="form-control" value="<?= htmlspecialchars($s['unlock_instagram_url'] ?? '') ?>">
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;"><i class="fab fa-facebook" style="color:#1877F2;"></i> Facebook URL</label>
            <input type="text" name="unlock_facebook_url" class="form-control" value="<?= htmlspecialchars($s['unlock_facebook_url'] ?? '') ?>">
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;"><i class="fab fa-youtube" style="color:#FF0000;"></i> YouTube URL</label>
            <input type="text" name="unlock_youtube_url" class="form-control" value="<?= htmlspecialchars($s['unlock_youtube_url'] ?? '') ?>">
        </div>
    </div>
</div>

<div style="text-align:center;padding:20px;">
    <button type="submit" class="btn btn-primary" style="padding:14px 40px;font-size:16px;"><i class="fas fa-save"></i> Save All Changes</button>
</div>
</form>

<?php require_once 'views/layouts/footer.php'; ?>
