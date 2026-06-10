<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/image_upload.php';
require_once __DIR__ . '/../includes/models/GoogleReview.php';
require_once __DIR__ . '/../includes/models/Setting.php';

if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle = 'Google Reviews';
$message = '';

// Handle DELETE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    GoogleReview::delete((int)$_GET['delete']);
    header('Location: google-reviews.php?msg=deleted');
    exit;
}

// Handle POST (header settings or review add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save section header settings
    if (isset($_POST['save_header'])) {
        Setting::set('reviews_headline', $_POST['reviews_headline'] ?? '');
        Setting::set('reviews_google_count', $_POST['reviews_google_count'] ?? '');
        Setting::set('reviews_google_rating', $_POST['reviews_google_rating'] ?? '');
        $message = 'Section header updated!';
    } else {
        // Save review
        $data = $_POST;
        // Handle photo upload
        if (isset($_FILES['reviewer_photo']) && $_FILES['reviewer_photo']['error'] == 0) {
            $base = time() . '_reviewer';
            $saved = saveUploadedMedia($_FILES['reviewer_photo'], '../uploads/reviews/', $base);
            if ($saved) $data['reviewer_img'] = 'uploads/reviews/' . $saved;
        }
        if (!empty($_POST['id'])) {
            GoogleReview::update((int)$_POST['id'], $data);
            $message = 'Review updated!';
        } else {
            GoogleReview::create($data);
            $message = 'Review added!';
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') $message = 'Review deleted.';

$reviews = GoogleReview::getAll();
$editReview = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editReview = GoogleReview::getById((int)$_GET['edit']);
}
$s = Setting::getAll();

require_once 'views/layouts/header.php';
?>
<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1><i class="fab fa-google"></i> Google Reviews Section</h1>
    <a href="home-page.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php if ($message): ?><div style="padding:12px;background:#d4edda;color:#155724;border-radius:6px;margin-bottom:20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<!-- Section Header Settings -->
<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">Section Header</h3>
    <form method="POST">
        <input type="hidden" name="save_header" value="1">
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:16px;align-items:end;">
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Headline</label>
                <input type="text" name="reviews_headline" class="form-control" value="<?= htmlspecialchars($s['reviews_headline'] ?? 'Over 10 Lakh happy customers and counting') ?>">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Total Review Count</label>
                <input type="text" name="reviews_google_count" class="form-control" value="<?= htmlspecialchars($s['reviews_google_count'] ?? '493') ?>">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Rating (e.g. 4.5)</label>
                <input type="text" name="reviews_google_rating" class="form-control" value="<?= htmlspecialchars($s['reviews_google_rating'] ?? '4.5') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:16px;"><i class="fas fa-save"></i> Save Header</button>
    </form>
</div>

<!-- Add/Edit Review -->
<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;"><?= $editReview ? 'Edit Review' : 'Add New Review' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?php if ($editReview): ?><input type="hidden" name="id" value="<?= $editReview['id'] ?>"><?php endif; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Reviewer Name *</label>
                <input type="text" name="reviewer_name" class="form-control" required value="<?= htmlspecialchars($editReview['reviewer_name'] ?? '') ?>">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Date (e.g. Mar 11, 2026)</label>
                <input type="text" name="review_date" class="form-control" value="<?= htmlspecialchars($editReview['review_date'] ?? date('M d, Y')) ?>">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Rating (1-5)</label>
                <select name="rating" class="form-control">
                    <?php for ($i=5; $i>=1; $i--): ?>
                    <option value="<?= $i ?>" <?= (($editReview['rating'] ?? 5) == $i) ? 'selected' : '' ?>><?= $i ?> Stars</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Photo URL <span style="color:#999;font-weight:400;">(or upload below)</span></label>
                <input type="text" name="reviewer_img" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($editReview['reviewer_img'] ?? '') ?>">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Upload Photo</label>
                <?php if ($editReview && $editReview['reviewer_img'] && strpos($editReview['reviewer_img'], 'uploads/') !== false): ?>
                    <img src="../<?= htmlspecialchars($editReview['reviewer_img']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;margin-bottom:4px;">
                <?php endif; ?>
                <input type="file" name="reviewer_photo" class="form-control" accept="image/*">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Display Order</label>
                <input type="number" name="display_order" class="form-control" value="<?= (int)($editReview['display_order'] ?? 0) ?>" min="0">
            </div>
            <div style="grid-column:span 3;">
                <label style="font-weight:600;display:block;margin-bottom:6px;">Review Text *</label>
                <textarea name="review_text" class="form-control" rows="3" required><?= htmlspecialchars($editReview['review_text'] ?? '') ?></textarea>
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Status</label>
                <select name="is_active" class="form-control">
                    <option value="1" <?= (!$editReview || $editReview['is_active']) ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($editReview && !$editReview['is_active']) ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editReview ? 'Update' : 'Add Review' ?></button>
            <?php if ($editReview): ?><a href="google-reviews.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<!-- Reviews Table -->
<div class="admin-card">
    <h3 style="margin-bottom:16px;">All Reviews (<?= count($reviews) ?>)</h3>
    <?php if (empty($reviews)): ?>
        <p style="text-align:center;color:#999;padding:30px;">No reviews yet.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="table">
        <thead><tr><th>Photo</th><th>Name</th><th>Date</th><th>Rating</th><th>Review</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($reviews as $r): ?>
        <tr>
            <td><img src="<?= htmlspecialchars($r['reviewer_img'] ?: 'https://i.pravatar.cc/40') ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;"></td>
            <td><strong><?= htmlspecialchars($r['reviewer_name']) ?></strong></td>
            <td><?= htmlspecialchars($r['review_date']) ?></td>
            <td><?= str_repeat('★', (int)$r['rating']) ?></td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($r['review_text']) ?></td>
            <td><?= $r['display_order'] ?></td>
            <td><span style="padding:3px 10px;border-radius:20px;font-size:12px;background:<?= $r['is_active'] ? '#d4edda' : '#f8d7da' ?>;color:<?= $r['is_active'] ? '#155724' : '#721c24' ?>;"><?= $r['is_active'] ? 'Active' : 'Hidden' ?></span></td>
            <td>
                <a href="google-reviews.php?edit=<?= $r['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                <a href="google-reviews.php?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'views/layouts/footer.php'; ?>
