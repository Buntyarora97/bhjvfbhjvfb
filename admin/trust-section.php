<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/image_upload.php';
require_once __DIR__ . '/../includes/models/Setting.php';

if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle = 'Ayurveda Trust Section';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle video upload
    if (isset($_FILES['trust_video_file']) && $_FILES['trust_video_file']['error'] == 0) {
        $uploadDir = '../uploads/reels/';
        $base = time() . '_trust_video';
        $saved = saveUploadedMedia($_FILES['trust_video_file'], $uploadDir, $base);
        if ($saved) {
            Setting::set('trust_video_url', 'uploads/reels/' . $saved);
        }
    }
    // Save all text settings
    $keys = [
        'trust_badge_text','trust_headline','trust_headline_highlight','trust_subheadline',
        'trust_stat1_num','trust_stat1_label','trust_stat2_num','trust_stat2_label',
        'trust_stat3_num','trust_stat3_label','trust_stat4_num','trust_stat4_label',
        'trust_badge1_title','trust_badge1_desc','trust_badge2_title','trust_badge2_desc',
        'trust_badge3_title','trust_badge3_desc','trust_badge4_title','trust_badge4_desc',
        'trust_float1_num','trust_float1_label','trust_float2_num','trust_float2_label',
        'trust_float3_num','trust_float3_label','trust_explore_link','trust_reviews_link'
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
    <h1><i class="fas fa-leaf"></i> Ayurveda Trust Section</h1>
    <a href="home-page.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Home Page</a>
</div>

<?php if ($message): ?><div style="padding:12px;background:#d4edda;color:#155724;border-radius:6px;margin-bottom:20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;color:#1f3d2b;">Brand Badge & Headlines</h3>
    <div style="display:grid;grid-template-columns:1fr;gap:16px;">
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Badge Text</label>
            <input type="text" name="trust_badge_text" class="form-control" value="<?= htmlspecialchars($s['trust_badge_text'] ?? '') ?>">
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Main Headline</label>
            <input type="text" name="trust_headline" class="form-control" value="<?= htmlspecialchars($s['trust_headline'] ?? '') ?>">
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Headline Green Highlight Text <span style="color:#999;font-weight:400;">(must appear inside headline)</span></label>
            <input type="text" name="trust_headline_highlight" class="form-control" value="<?= htmlspecialchars($s['trust_headline_highlight'] ?? '') ?>">
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Sub-headline / Description</label>
            <textarea name="trust_subheadline" class="form-control" rows="3"><?= htmlspecialchars($s['trust_subheadline'] ?? '') ?></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Explore Products Link</label>
                <input type="text" name="trust_explore_link" class="form-control" value="<?= htmlspecialchars($s['trust_explore_link'] ?? 'products.php') ?>">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Read Reviews Link</label>
                <input type="text" name="trust_reviews_link" class="form-control" value="<?= htmlspecialchars($s['trust_reviews_link'] ?? 'livvrareview.php') ?>">
            </div>
        </div>
    </div>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;color:#1f3d2b;">Stats (4 Numbers)</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:16px;">
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <div style="background:#f8faf9;padding:16px;border-radius:8px;">
            <label style="font-weight:600;display:block;margin-bottom:6px;">Stat <?= $i ?> Number</label>
            <input type="text" name="trust_stat<?= $i ?>_num" class="form-control" value="<?= htmlspecialchars($s["trust_stat{$i}_num"] ?? '') ?>" style="margin-bottom:8px;">
            <label style="font-weight:600;display:block;margin-bottom:6px;">Stat <?= $i ?> Label</label>
            <input type="text" name="trust_stat<?= $i ?>_label" class="form-control" value="<?= htmlspecialchars($s["trust_stat{$i}_label"] ?? '') ?>">
        </div>
        <?php endfor; ?>
    </div>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;color:#1f3d2b;">Trust Badges (Certifications)</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <?php
        $badgeLabels = ['GMP Certified','100% Natural','FSSAI Approved','Ayush Certified'];
        for ($i = 1; $i <= 4; $i++): ?>
        <div style="background:#f8faf9;padding:16px;border-radius:8px;">
            <label style="font-weight:600;display:block;margin-bottom:6px;">Badge <?= $i ?> Title</label>
            <input type="text" name="trust_badge<?= $i ?>_title" class="form-control" value="<?= htmlspecialchars($s["trust_badge{$i}_title"] ?? '') ?>" style="margin-bottom:8px;">
            <label style="font-weight:600;display:block;margin-bottom:6px;">Badge <?= $i ?> Description</label>
            <input type="text" name="trust_badge<?= $i ?>_desc" class="form-control" value="<?= htmlspecialchars($s["trust_badge{$i}_desc"] ?? '') ?>">
        </div>
        <?php endfor; ?>
    </div>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;color:#1f3d2b;">iPhone Frame Video</h3>
    <p style="color:#666;margin-bottom:16px;">Upload the video that plays inside the iPhone frame on the right side of this section.</p>
    <?php if (!empty($s['trust_video_url'])): ?>
        <div style="margin-bottom:12px;">
            <strong>Current video:</strong> <code><?= htmlspecialchars($s['trust_video_url']) ?></code>
            <video src="../<?= htmlspecialchars($s['trust_video_url']) ?>" style="display:block;margin-top:8px;width:160px;border-radius:8px;" controls muted></video>
        </div>
    <?php endif; ?>
    <input type="file" name="trust_video_file" class="form-control" accept="video/*">
    <p style="color:#999;font-size:13px;margin-top:6px;">Recommended: vertical video (9:16), MP4 format, max 50MB</p>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;color:#1f3d2b;">Floating Badges (3 floating cards)</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
        <?php
        $floatLabels = ['Top-left (Star/Rating)','Top-right (Reels)','Bottom-left (Likes)'];
        for ($i = 1; $i <= 3; $i++): ?>
        <div style="background:#f8faf9;padding:16px;border-radius:8px;">
            <p style="color:#999;font-size:12px;margin-bottom:8px;"><?= $floatLabels[$i-1] ?></p>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Number / Value</label>
            <input type="text" name="trust_float<?= $i ?>_num" class="form-control" value="<?= htmlspecialchars($s["trust_float{$i}_num"] ?? '') ?>" style="margin-bottom:8px;">
            <label style="font-weight:600;display:block;margin-bottom:6px;">Label</label>
            <input type="text" name="trust_float<?= $i ?>_label" class="form-control" value="<?= htmlspecialchars($s["trust_float{$i}_label"] ?? '') ?>">
        </div>
        <?php endfor; ?>
    </div>
</div>

<div style="text-align:center;padding:20px;">
    <button type="submit" class="btn btn-primary" style="padding:14px 40px;font-size:16px;"><i class="fas fa-save"></i> Save All Changes</button>
</div>
</form>

<?php require_once 'views/layouts/footer.php'; ?>
