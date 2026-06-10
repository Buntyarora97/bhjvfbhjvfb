<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models/Setting.php';

if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle   = 'Home Content Block';
$currentPage = 'home-page';
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $html    = $_POST['block_html']    ?? '';
    $enabled = isset($_POST['block_enabled']) ? '1' : '0';

    Setting::set('home_content_block_html',    $html);
    Setting::set('home_content_block_enabled', $enabled);
    $success = 'Content block saved successfully!';
}

$settings     = Setting::getAll();
$blockHtml    = $settings['home_content_block_html']    ?? '';
$blockEnabled = $settings['home_content_block_enabled'] ?? '1';

require_once __DIR__ . '/views/layouts/header.php';
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<div class="admin-header">
    <h1><i class="fas fa-align-left"></i> Home Content Block</h1>
    <p style="color:#666;margin-top:4px;font-size:13px;">Ye section homepage pe "Today's Best Deal" se upar dikhta hai. Yahan se text add, bold/italic/color sab kar sakte ho.</p>
</div>

<?php if ($success): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:#d4edda;color:#155724;border-radius:6px;font-size:14px;">
    ✅ <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<form method="POST" id="mainForm">
    <input type="hidden" name="block_html" id="block_html_input">

    <div class="admin-card">
        <div class="form-group" style="margin-bottom:18px;">
            <label style="display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;cursor:pointer;">
                <input type="checkbox" name="block_enabled" value="1" <?php echo ($blockEnabled === '1') ? 'checked' : ''; ?> style="width:18px;height:18px;">
                Homepage pe yeh section show karo
            </label>
        </div>

        <div class="form-group">
            <label style="font-size:14px;font-weight:600;margin-bottom:8px;display:block;">Content Editor</label>
            <p style="font-size:12px;color:#888;margin-bottom:10px;">Text type karo, bold/italic/color/heading/list/link sab set kar sako.</p>

            <!-- Quill editor container -->
            <div id="quill-editor" style="min-height:260px;font-size:15px;font-family:Arial,Helvetica,sans-serif;background:#fff;"></div>
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid #eee;display:flex;align-items:center;gap:16px;">
            <button type="submit" class="btn btn-primary" onclick="syncEditor()">
                <i class="fas fa-save"></i> Save Content Block
            </button>
            <a href="/" target="_blank" style="color:#1f3e35;font-size:13px;text-decoration:none;">
                <i class="fas fa-eye"></i> Homepage dekho
            </a>
        </div>
    </div>
</form>

<!-- Preview -->
<div class="admin-card" style="margin-top:20px;">
    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:#1f3e35;">Live Preview</h3>
    <div id="live-preview" style="padding:20px;background:#f8f9fa;border-radius:6px;border:1px solid #e0e0e0;min-height:60px;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#333;line-height:1.7;">
        <?php if ($blockEnabled === '1' && !empty($blockHtml)): ?>
            <?php echo $blockHtml; ?>
        <?php else: ?>
            <span style="color:#bbb;font-style:italic;">Editor me kuch likhoge to yahan preview dikhega...</span>
        <?php endif; ?>
    </div>
</div>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
var quill = new Quill('#quill-editor', {
    theme: 'snow',
    placeholder: 'Yahan content likhein...',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'align': [] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['link'],
            ['clean']
        ]
    }
});

// Load existing content into editor
var existingHtml = <?php echo json_encode($blockHtml); ?>;
if (existingHtml) {
    quill.clipboard.dangerouslyPasteHTML(existingHtml);
}

// Live preview update
quill.on('text-change', function() {
    document.getElementById('live-preview').innerHTML = quill.root.innerHTML;
});

function syncEditor() {
    document.getElementById('block_html_input').value = quill.root.innerHTML;
}

// Also sync before form submits
document.getElementById('mainForm').addEventListener('submit', function() {
    syncEditor();
});
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
