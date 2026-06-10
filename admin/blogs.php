<?php
require_once __DIR__ . '/../includes/config.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$currentPage = 'blogs';
$pageTitle = 'Manage Blogs';

$success = $_GET['success'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        Blog::delete((int)$_POST['delete_id']);
        header('Location: blogs.php?success=' . urlencode('Blog deleted successfully'));
        exit;
    } catch (Exception $e) {
        $error = 'Delete failed: ' . $e->getMessage();
    }
}

$blogs = Blog::getAll();

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="admin-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Blogs</h1>
    <a href="blog-add.php" class="btn-primary" style="background:#C9A227; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:600;">
        <i class="fas fa-plus"></i> Add New Blog
    </a>
</div>

<?php if ($success): ?>
    <div style="background:#d4edda; color:#155724; padding:12px; border-radius:5px; margin-bottom:15px;"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div style="background:#f8d7da; color:#721c24; padding:12px; border-radius:5px; margin-bottom:15px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="admin-card" style="padding:0; overflow:hidden;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Image</th>
                <th>Title / Slug</th>
                <th>Author</th>
                <th>Status</th>
                <th>Publish Date</th>
                <th>Views</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($blogs)): ?>
                <tr><td colspan="8" style="padding:40px; text-align:center; color:#999;">No blogs yet. Click "Add New Blog" to create one.</td></tr>
            <?php else: foreach ($blogs as $i => $b): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td>
                        <?php if (!empty($b['featured_image'])): ?>
                            <img src="../uploads/blogs/<?php echo htmlspecialchars($b['featured_image']); ?>" style="width:60px; height:40px; object-fit:cover; border-radius:4px;" alt="">
                        <?php else: ?>
                            <span style="color:#bbb;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($b['title']); ?></strong><br>
                        <small style="color:#888;">/blog/<?php echo htmlspecialchars($b['slug']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($b['author_name'] ?? '—'); ?></td>
                    <td>
                        <?php $st = $b['status'] ?? 'draft'; $cls = $st==='published'?'badge-success':($st==='scheduled'?'badge-warning':'badge-secondary'); ?>
                        <span class="badge <?php echo $cls; ?>" style="padding:4px 10px; border-radius:12px; font-size:0.75rem; background:<?php echo $st==='published'?'#28a745':($st==='scheduled'?'#ffc107':'#6c757d'); ?>; color:#fff;"><?php echo strtoupper($st); ?></span>
                    </td>
                    <td><?php echo $b['publish_date'] ? date('d M Y', strtotime($b['publish_date'])) : '—'; ?></td>
                    <td><?php echo (int)($b['views'] ?? 0); ?></td>
                    <td>
                        <a href="../blog/<?php echo rawurlencode($b['slug']); ?>" target="_blank" style="color:#17a2b8; margin-right:8px;" title="View"><i class="fas fa-eye"></i></a>
                        <a href="blog-edit.php?id=<?php echo $b['id']; ?>" style="color:#C9A227; margin-right:8px;" title="Edit"><i class="fas fa-edit"></i></a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this blog permanently?');">
                            <input type="hidden" name="delete_id" value="<?php echo $b['id']; ?>">
                            <button type="submit" style="background:none; border:none; color:#dc3545; cursor:pointer;" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
