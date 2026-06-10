<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models/FAQ.php';

if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle = 'FAQ Management';
$message = '';

// Handle DELETE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    FAQ::delete((int)$_GET['delete']);
    header('Location: faqs.php?msg=deleted');
    exit;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id'])) {
        FAQ::update((int)$_POST['id'], $_POST);
        $message = 'FAQ updated successfully!';
    } else {
        FAQ::create($_POST);
        $message = 'FAQ added successfully!';
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') $message = 'FAQ deleted.';

$faqs = FAQ::getAll();
$editFaq = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editFaq = FAQ::getById((int)$_GET['edit']);
}

require_once 'views/layouts/header.php';
?>
<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1><i class="fas fa-question-circle"></i> FAQ Management</h1>
    <a href="home-page.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php if ($message): ?><div style="padding:12px;background:#d4edda;color:#155724;border-radius:6px;margin-bottom:20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;"><?= $editFaq ? 'Edit FAQ' : 'Add New FAQ' ?></h3>
    <form method="POST">
        <?php if ($editFaq): ?><input type="hidden" name="id" value="<?= $editFaq['id'] ?>"><?php endif; ?>
        <div style="display:grid;gap:16px;">
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Question *</label>
                <input type="text" name="question" class="form-control" required value="<?= htmlspecialchars($editFaq['question'] ?? '') ?>">
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:6px;">Answer *</label>
                <textarea name="answer" class="form-control" rows="4" required><?= htmlspecialchars($editFaq['answer'] ?? '') ?></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label style="font-weight:600;display:block;margin-bottom:6px;">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?= (int)($editFaq['display_order'] ?? 0) ?>" min="0">
                </div>
                <div>
                    <label style="font-weight:600;display:block;margin-bottom:6px;">Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?= (!$editFaq || $editFaq['is_active']) ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($editFaq && !$editFaq['is_active']) ? 'selected' : '' ?>>Inactive (hidden)</option>
                    </select>
                </div>
            </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editFaq ? 'Update FAQ' : 'Add FAQ' ?></button>
            <?php if ($editFaq): ?><a href="faqs.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-bottom:16px;">All FAQs (<?= count($faqs) ?>)</h3>
    <?php if (empty($faqs)): ?>
        <p style="text-align:center;color:#999;padding:30px;">No FAQs yet. Add your first one above.</p>
    <?php else: ?>
    <div style="display:grid;gap:12px;">
    <?php foreach ($faqs as $f): ?>
    <div style="background:#f8faf9;border-radius:10px;padding:16px 20px;border:1px solid #e0ece4;display:flex;align-items:flex-start;gap:16px;">
        <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <span style="background:#0f3d2e;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;"><?= $f['display_order'] ?: $f['id'] ?></span>
                <strong style="color:#1f3d2b;font-size:15px;"><?= htmlspecialchars($f['question']) ?></strong>
                <span style="padding:2px 10px;border-radius:20px;font-size:11px;background:<?= $f['is_active'] ? '#d4edda' : '#f8d7da' ?>;color:<?= $f['is_active'] ? '#155724' : '#721c24' ?>;"><?= $f['is_active'] ? 'Active' : 'Hidden' ?></span>
            </div>
            <p style="color:#5a6b5f;font-size:14px;margin:0;padding-left:34px;line-height:1.6;"><?= htmlspecialchars(substr($f['answer'], 0, 120)) ?>...</p>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0;">
            <a href="faqs.php?edit=<?= $f['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Edit</a>
            <a href="faqs.php?delete=<?= $f['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this FAQ?')"><i class="fas fa-trash"></i></a>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'views/layouts/footer.php'; ?>
