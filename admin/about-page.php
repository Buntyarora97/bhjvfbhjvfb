<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models/Setting.php';

if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle   = 'About Page Manager';
$currentPage = 'about-page';
$success = '';
$error   = '';

function aboutSectionsGet() {
    $raw = Setting::get('about_page_sections', '');
    if (!$raw) return [];
    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : [];
}
function aboutSectionsSave($arr) {
    Setting::set('about_page_sections', json_encode(array_values($arr), JSON_UNESCAPED_UNICODE));
}
function makeId() {
    return 's' . substr(md5(uniqid(rand(), true)), 0, 8);
}

// Handle AJAX delete / reorder / toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    $sections = aboutSectionsGet();
    $act = $_POST['ajax_action'];

    if ($act === 'delete') {
        $id = $_POST['section_id'] ?? '';
        $sections = array_filter($sections, fn($s) => $s['id'] !== $id);
        aboutSectionsSave($sections);
        echo json_encode(['ok' => true]); exit;
    }
    if ($act === 'toggle') {
        $id = $_POST['section_id'] ?? '';
        foreach ($sections as &$s) {
            if ($s['id'] === $id) $s['enabled'] = !($s['enabled'] ?? true);
        }
        aboutSectionsSave($sections);
        echo json_encode(['ok' => true]); exit;
    }
    if ($act === 'reorder') {
        $order = json_decode($_POST['order'] ?? '[]', true);
        $map = [];
        foreach ($sections as $s) $map[$s['id']] = $s;
        $new = [];
        foreach ($order as $id) { if (isset($map[$id])) $new[] = $map[$id]; }
        aboutSectionsSave($new);
        echo json_encode(['ok' => true]); exit;
    }
    if ($act === 'add_section') {
        $type    = $_POST['type'] ?? 'text';
        $content = $_POST['content'] ?? '';
        $title   = trim($_POST['title'] ?? '');
        $bg      = $_POST['bg_color'] ?? '#ffffff';
        $textcol = $_POST['text_color'] ?? '#1f3e35';

        // Handle file upload
        if (in_array($type, ['image', 'video']) && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
            $ext  = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed = ($type === 'image') ? ['jpg','jpeg','png','webp','gif'] : ['mp4','webm','mov'];
            if (in_array($ext, $allowed)) {
                $fname = 'about_' . makeId() . '.' . $ext;
                $dest  = __DIR__ . '/../uploads/about/' . $fname;
                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                    $content = 'uploads/about/' . $fname;
                }
            }
        }

        $sections[] = [
            'id'         => makeId(),
            'type'       => $type,
            'title'      => $title,
            'content'    => $content,
            'bg_color'   => $bg,
            'text_color' => $textcol,
            'enabled'    => true,
        ];
        aboutSectionsSave($sections);
        echo json_encode(['ok' => true]); exit;
    }
    if ($act === 'edit_section') {
        $id      = $_POST['section_id'] ?? '';
        $content = $_POST['content'] ?? '';
        $title   = trim($_POST['title'] ?? '');
        $bg      = $_POST['bg_color'] ?? '#ffffff';
        $textcol = $_POST['text_color'] ?? '#1f3e35';

        foreach ($sections as &$s) {
            if ($s['id'] === $id) {
                // Handle file upload
                if (in_array($s['type'], ['image','video']) && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
                    $ext  = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                    $allowed = ($s['type'] === 'image') ? ['jpg','jpeg','png','webp','gif'] : ['mp4','webm','mov'];
                    if (in_array($ext, $allowed)) {
                        $fname = 'about_' . makeId() . '.' . $ext;
                        $dest  = __DIR__ . '/../uploads/about/' . $fname;
                        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                            $content = 'uploads/about/' . $fname;
                        }
                    }
                }
                $s['title']      = $title;
                $s['content']    = $content;
                $s['bg_color']   = $bg;
                $s['text_color'] = $textcol;
            }
        }
        aboutSectionsSave($sections);
        echo json_encode(['ok' => true]); exit;
    }
    echo json_encode(['ok' => false]); exit;
}

$sections = aboutSectionsGet();

// If no sections saved yet, pre-load the existing hardcoded ones as defaults
if (empty($sections)) {
    $sections = [
        ['id' => makeId(), 'type' => 'image',  'title' => '',  'content' => 'assets/images/about/1.jpeg', 'bg_color' => '#ffffff', 'text_color' => '#1f3e35', 'enabled' => true],
        ['id' => makeId(), 'type' => 'video',  'title' => '',  'content' => 'assets/images/about/2.mp4',  'bg_color' => '#ffffff', 'text_color' => '#1f3e35', 'enabled' => true],
        ['id' => makeId(), 'type' => 'image',  'title' => '',  'content' => 'assets/images/about/3.jpeg', 'bg_color' => '#ffffff', 'text_color' => '#1f3e35', 'enabled' => true],
        ['id' => makeId(), 'type' => 'image',  'title' => '',  'content' => 'assets/images/about/4.jpeg', 'bg_color' => '#ffffff', 'text_color' => '#1f3e35', 'enabled' => true],
        ['id' => makeId(), 'type' => 'video',  'title' => '',  'content' => 'assets/images/about/5.mp4',  'bg_color' => '#ffffff', 'text_color' => '#1f3e35', 'enabled' => true],
        ['id' => makeId(), 'type' => 'image',  'title' => '',  'content' => 'assets/images/about/6.jpeg', 'bg_color' => '#ffffff', 'text_color' => '#1f3e35', 'enabled' => true],
    ];
    aboutSectionsSave($sections);
    $success = 'Existing about page sections loaded. Ab inhe edit, delete ya naye add kar sakte ho!';
}

require_once __DIR__ . '/views/layouts/header.php';

$typeLabels = ['image' => '🖼️ Image', 'video' => '🎥 Video', 'text' => '📝 Rich Text', 'banner' => '🎨 Banner'];
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.css">

<style>
.about-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.section-card { background:#fff; border:1.5px solid #e8f0e4; border-radius:12px; margin-bottom:14px; overflow:hidden; transition:box-shadow 0.2s; }
.section-card:hover { box-shadow:0 4px 20px rgba(31,62,53,0.10); }
.section-card.disabled-card { opacity:0.5; }
.section-card-header { display:flex; align-items:center; gap:12px; padding:14px 18px; background:#f8fdf5; border-bottom:1px solid #e8f0e4; cursor:grab; }
.section-card-header:active { cursor:grabbing; }
.drag-handle { color:#aaa; font-size:20px; cursor:grab; flex-shrink:0; }
.section-type-badge { font-size:12px; font-weight:700; padding:3px 10px; border-radius:20px; background:#1f3e35; color:#bcded0; flex-shrink:0; }
.section-title-text { flex:1; font-size:13px; font-weight:600; color:#1f3e35; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.section-actions { display:flex; gap:8px; flex-shrink:0; }
.btn-sm { font-size:12px; padding:5px 12px; border-radius:6px; border:none; cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
.btn-edit  { background:#e8f0e4; color:#1f3e35; }
.btn-edit:hover { background:#d4e6cc; }
.btn-toggle-on  { background:#d4edda; color:#155724; }
.btn-toggle-off { background:#fff3cd; color:#856404; }
.btn-delete { background:#f8d7da; color:#721c24; }
.btn-delete:hover { background:#f5c6cb; }
.section-preview { padding:14px 18px; max-height:120px; overflow:hidden; position:relative; }
.section-preview img { max-height:100px; border-radius:6px; max-width:200px; object-fit:cover; }
.section-preview video { max-height:100px; border-radius:6px; max-width:200px; }
.section-preview .text-preview { font-size:12px; color:#666; line-height:1.5; max-height:60px; overflow:hidden; }

/* Add section panel */
.add-panel { background:#fff; border:1.5px solid #76a33a; border-radius:12px; padding:24px; margin-bottom:20px; display:none; }
.add-panel.open { display:block; }
.type-tabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.type-tab { padding:8px 16px; border:2px solid #e0e0e0; border-radius:8px; cursor:pointer; font-size:13px; font-weight:600; background:#fff; color:#666; transition:all 0.2s; }
.type-tab.active { border-color:#76a33a; background:#f4fdf0; color:#1f3e35; }
.type-content { display:none; }
.type-content.active { display:block; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media(max-width:600px){ .form-row { grid-template-columns:1fr; } }
.form-label { font-size:13px; font-weight:600; color:#1f3e35; margin-bottom:5px; display:block; }
.form-input { width:100%; padding:9px 12px; border:1.5px solid #ddd; border-radius:6px; font-size:13px; outline:none; font-family:inherit; }
.form-input:focus { border-color:#76a33a; }
.upload-zone { border:2px dashed #76a33a; border-radius:8px; padding:24px; text-align:center; cursor:pointer; background:#f4fdf0; transition:background 0.2s; }
.upload-zone:hover { background:#e8f5e9; }
.upload-zone input { display:none; }
.upload-zone p { font-size:13px; color:#76a33a; font-weight:600; margin:0; }
.upload-preview { margin-top:10px; display:none; }
.upload-preview img, .upload-preview video { max-height:120px; max-width:100%; border-radius:6px; }

/* Modal */
.edit-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9000; align-items:center; justify-content:center; }
.edit-modal-overlay.open { display:flex; }
.edit-modal { background:#fff; border-radius:14px; padding:28px; max-width:680px; width:94%; max-height:90vh; overflow-y:auto; box-shadow:0 12px 60px rgba(0,0,0,0.2); }
.edit-modal h3 { font-size:18px; font-weight:700; color:#1f3e35; margin-bottom:18px; }

.sortable-ghost { opacity:0.4; background:#f0fdf4; }
</style>

<div class="about-header">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#1f3e35;margin:0;"><i class="fas fa-user-circle"></i> About Page Manager</h1>
        <p style="font-size:13px;color:#666;margin:4px 0 0;">Sections ko drag karke reorder karo. Add, Edit, Delete sab yahan se.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="/about.php" target="_blank" class="btn btn-secondary" style="font-size:13px;"><i class="fas fa-eye"></i> Preview</a>
        <button class="btn btn-primary" onclick="toggleAddPanel()"><i class="fas fa-plus"></i> Add New Section</button>
    </div>
</div>

<?php if ($success): ?>
<div style="margin-bottom:14px;padding:12px 16px;background:#d4edda;color:#155724;border-radius:8px;font-size:13px;">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- ADD SECTION PANEL -->
<div class="add-panel" id="addPanel">
    <h3 style="font-size:16px;font-weight:700;color:#1f3e35;margin:0 0 16px;">➕ New Section Add Karo</h3>
    <div class="type-tabs">
        <div class="type-tab active" onclick="switchType('image')">🖼️ Image</div>
        <div class="type-tab" onclick="switchType('video')">🎥 Video</div>
        <div class="type-tab" onclick="switchType('text')">📝 Rich Text</div>
        <div class="type-tab" onclick="switchType('banner')">🎨 Banner</div>
    </div>

    <!-- IMAGE -->
    <div class="type-content active" id="tc-image">
        <form id="addImageForm" enctype="multipart/form-data">
            <input type="hidden" name="ajax_action" value="add_section">
            <input type="hidden" name="type" value="image">
            <div class="upload-zone" onclick="document.getElementById('imgFile').click()">
                <input type="file" name="file" id="imgFile" accept="image/*" onchange="previewFile(this,'imgPreview','image')">
                <p>📂 Click karke image upload karo (JPG, PNG, WebP)</p>
                <p style="font-size:11px;color:#aaa;margin-top:4px;">Ya neeche URL paste karo</p>
            </div>
            <div class="upload-preview" id="imgPreview"></div>
            <div style="margin-top:12px;">
                <label class="form-label">Ya Image URL paste karo (optional)</label>
                <input type="text" name="content" id="imgUrl" class="form-input" placeholder="https://... ya assets/images/about/1.jpeg">
            </div>
            <button type="button" class="btn btn-primary" style="margin-top:14px;" onclick="submitAdd('addImageForm')"><i class="fas fa-plus"></i> Add Image Section</button>
        </form>
    </div>

    <!-- VIDEO -->
    <div class="type-content" id="tc-video">
        <form id="addVideoForm" enctype="multipart/form-data">
            <input type="hidden" name="ajax_action" value="add_section">
            <input type="hidden" name="type" value="video">
            <div class="upload-zone" onclick="document.getElementById('vidFile').click()">
                <input type="file" name="file" id="vidFile" accept="video/*" onchange="previewFile(this,'vidPreview','video')">
                <p>📂 Click karke video upload karo (MP4, WebM)</p>
            </div>
            <div class="upload-preview" id="vidPreview"></div>
            <div style="margin-top:12px;">
                <label class="form-label">Ya Video URL paste karo (optional)</label>
                <input type="text" name="content" id="vidUrl" class="form-input" placeholder="uploads/reels/video.mp4">
            </div>
            <button type="button" class="btn btn-primary" style="margin-top:14px;" onclick="submitAdd('addVideoForm')"><i class="fas fa-plus"></i> Add Video Section</button>
        </form>
    </div>

    <!-- RICH TEXT -->
    <div class="type-content" id="tc-text">
        <form id="addTextForm">
            <input type="hidden" name="ajax_action" value="add_section">
            <input type="hidden" name="type" value="text">
            <input type="hidden" name="content" id="textContent">
            <div style="margin-bottom:12px;">
                <label class="form-label">Section Title (optional)</label>
                <input type="text" name="title" class="form-input" placeholder="e.g. Our Story">
            </div>
            <label class="form-label">Content</label>
            <div id="addTextEditor" style="height:200px;background:#fff;"></div>
            <button type="button" class="btn btn-primary" style="margin-top:14px;" onclick="submitTextAdd()"><i class="fas fa-plus"></i> Add Text Section</button>
        </form>
    </div>

    <!-- BANNER -->
    <div class="type-content" id="tc-banner">
        <form id="addBannerForm">
            <input type="hidden" name="ajax_action" value="add_section">
            <input type="hidden" name="type" value="banner">
            <input type="hidden" name="content" id="bannerContent">
            <div style="margin-bottom:12px;">
                <label class="form-label">Heading / Title</label>
                <input type="text" name="title" id="bannerTitle" class="form-input" placeholder="e.g. Our Mission">
            </div>
            <label class="form-label">Description / Text</label>
            <div id="addBannerEditor" style="height:160px;background:#fff;margin-bottom:14px;"></div>
            <div class="form-row">
                <div>
                    <label class="form-label">Background Color</label>
                    <input type="color" name="bg_color" value="#1f3e35" class="form-input" style="height:42px;padding:4px;">
                </div>
                <div>
                    <label class="form-label">Text Color</label>
                    <input type="color" name="text_color" value="#ffffff" class="form-input" style="height:42px;padding:4px;">
                </div>
            </div>
            <button type="button" class="btn btn-primary" style="margin-top:14px;" onclick="submitBannerAdd()"><i class="fas fa-plus"></i> Add Banner Section</button>
        </form>
    </div>
</div>

<!-- SECTIONS LIST -->
<div class="admin-card" style="padding:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <strong style="font-size:14px;color:#1f3e35;">📋 All Sections (<?= count($sections) ?>) — Drag to Reorder</strong>
        <span style="font-size:12px;color:#999;">⬆️ Drag karo order change karne ke liye</span>
    </div>

    <?php if (empty($sections)): ?>
        <p style="text-align:center;color:#aaa;padding:40px;font-size:14px;">Koi section nahi hai. "Add New Section" se add karo.</p>
    <?php else: ?>
    <div id="sectionsList">
        <?php foreach ($sections as $idx => $sec): ?>
        <div class="section-card <?= ($sec['enabled'] ?? true) ? '' : 'disabled-card' ?>" data-id="<?= htmlspecialchars($sec['id']) ?>">
            <div class="section-card-header">
                <span class="drag-handle">⠿</span>
                <span class="section-type-badge"><?= $typeLabels[$sec['type']] ?? $sec['type'] ?></span>
                <span class="section-title-text">
                    <?php
                        if ($sec['title']) echo htmlspecialchars($sec['title']);
                        elseif (in_array($sec['type'], ['image','video'])) echo htmlspecialchars($sec['content']);
                        else echo strip_tags(substr($sec['content'] ?? '', 0, 60));
                    ?>
                </span>
                <div class="section-actions">
                    <button class="btn-sm btn-edit" onclick="openEdit('<?= htmlspecialchars($sec['id']) ?>')"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn-sm <?= ($sec['enabled'] ?? true) ? 'btn-toggle-on' : 'btn-toggle-off' ?>" onclick="toggleSection('<?= htmlspecialchars($sec['id']) ?>', this)">
                        <?= ($sec['enabled'] ?? true) ? '👁️ On' : '🚫 Off' ?>
                    </button>
                    <button class="btn-sm btn-delete" onclick="deleteSection('<?= htmlspecialchars($sec['id']) ?>', this)"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <div class="section-preview">
                <?php if ($sec['type'] === 'image'): ?>
                    <img src="/<?= htmlspecialchars(ltrim($sec['content'],'/')) ?>" alt="preview" onerror="this.style.display='none'">
                    <span style="font-size:11px;color:#999;margin-left:8px;"><?= htmlspecialchars($sec['content']) ?></span>
                <?php elseif ($sec['type'] === 'video'): ?>
                    <video src="/<?= htmlspecialchars(ltrim($sec['content'],'/')) ?>" style="max-height:80px;max-width:160px;border-radius:6px;" muted></video>
                    <span style="font-size:11px;color:#999;margin-left:8px;"><?= htmlspecialchars($sec['content']) ?></span>
                <?php elseif ($sec['type'] === 'text'): ?>
                    <div class="text-preview"><?= strip_tags($sec['content'] ?? '') ?></div>
                <?php elseif ($sec['type'] === 'banner'): ?>
                    <div style="background:<?= htmlspecialchars($sec['bg_color'] ?? '#1f3e35') ?>;color:<?= htmlspecialchars($sec['text_color'] ?? '#fff') ?>;padding:10px 16px;border-radius:6px;font-size:13px;max-width:400px;">
                        <?php if ($sec['title']): ?><strong><?= htmlspecialchars($sec['title']) ?></strong><br><?php endif; ?>
                        <span><?= strip_tags(substr($sec['content'] ?? '', 0, 80)) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- EDIT MODAL -->
<div class="edit-modal-overlay" id="editModal">
    <div class="edit-modal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h3 style="margin:0;">✏️ Section Edit Karo</h3>
            <button onclick="closeEdit()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#aaa;">✕</button>
        </div>
        <form id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="ajax_action" value="edit_section">
            <input type="hidden" name="section_id" id="editId">
            <input type="hidden" name="type" id="editType">
            <input type="hidden" name="content" id="editContent">

            <div id="editImageFields" style="display:none;">
                <label class="form-label">New Image Upload (optional — current image rakhna ho to khaali chhodo)</label>
                <div class="upload-zone" onclick="document.getElementById('editImgFile').click()">
                    <input type="file" name="file" id="editImgFile" accept="image/*" onchange="previewFile(this,'editImgPreview','image')">
                    <p>📂 Click karke naya image upload karo</p>
                </div>
                <div class="upload-preview" id="editImgPreview"></div>
                <div style="margin-top:12px;">
                    <label class="form-label">Ya URL paste karo</label>
                    <input type="text" id="editImgUrl" class="form-input" placeholder="assets/images/about/1.jpeg">
                </div>
            </div>

            <div id="editVideoFields" style="display:none;">
                <label class="form-label">New Video Upload (optional)</label>
                <div class="upload-zone" onclick="document.getElementById('editVidFile').click()">
                    <input type="file" name="file" id="editVidFile" accept="video/*" onchange="previewFile(this,'editVidPreview','video')">
                    <p>📂 Click karke naya video upload karo</p>
                </div>
                <div class="upload-preview" id="editVidPreview"></div>
                <div style="margin-top:12px;">
                    <label class="form-label">Ya URL paste karo</label>
                    <input type="text" id="editVidUrl" class="form-input" placeholder="uploads/about/video.mp4">
                </div>
            </div>

            <div id="editTextFields" style="display:none;">
                <div style="margin-bottom:12px;">
                    <label class="form-label">Section Title (optional)</label>
                    <input type="text" id="editTitleText" class="form-input" placeholder="Our Story">
                </div>
                <label class="form-label">Content</label>
                <div id="editTextEditor" style="height:200px;background:#fff;"></div>
            </div>

            <div id="editBannerFields" style="display:none;">
                <div style="margin-bottom:12px;">
                    <label class="form-label">Heading</label>
                    <input type="text" id="editTitleBanner" class="form-input" placeholder="Our Mission">
                </div>
                <label class="form-label">Description</label>
                <div id="editBannerEditor" style="height:160px;background:#fff;margin-bottom:14px;"></div>
                <div class="form-row">
                    <div>
                        <label class="form-label">Background Color</label>
                        <input type="color" id="editBgColor" value="#1f3e35" class="form-input" style="height:42px;padding:4px;">
                    </div>
                    <div>
                        <label class="form-label">Text Color</label>
                        <input type="color" id="editTextColor" value="#ffffff" class="form-input" style="height:42px;padding:4px;">
                    </div>
                </div>
            </div>

            <div style="margin-top:20px;display:flex;gap:10px;">
                <button type="button" class="btn btn-primary" onclick="submitEdit()"><i class="fas fa-save"></i> Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="closeEdit()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Quill + Sortable JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
var quillConfig = {
    theme: 'snow',
    modules: { toolbar: [
        [{ 'header': [1,2,3,false] }],
        ['bold','italic','underline'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'align': [] }],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        ['link','clean']
    ]}
};

var addTextQ   = new Quill('#addTextEditor', quillConfig);
var addBannerQ = new Quill('#addBannerEditor', quillConfig);
var editTextQ  = null;
var editBannerQ = null;

// Sortable drag-reorder
var el = document.getElementById('sectionsList');
if (el) {
    Sortable.create(el, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: function() {
            var order = Array.from(el.querySelectorAll('.section-card')).map(c => c.dataset.id);
            fetch('', { method:'POST', body: new URLSearchParams({ ajax_action:'reorder', order: JSON.stringify(order) }) });
        }
    });
}

// Type tabs
var currentType = 'image';
function switchType(type) {
    currentType = type;
    document.querySelectorAll('.type-tab').forEach((t,i) => t.classList.remove('active'));
    event.target.classList.add('active');
    document.querySelectorAll('.type-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tc-' + type).classList.add('active');
}

function toggleAddPanel() {
    document.getElementById('addPanel').classList.toggle('open');
}

// File preview
function previewFile(input, previewId, type) {
    var file = input.files[0];
    if (!file) return;
    var preview = document.getElementById(previewId);
    preview.style.display = 'block';
    var url = URL.createObjectURL(file);
    if (type === 'image') {
        preview.innerHTML = '<img src="' + url + '">';
    } else {
        preview.innerHTML = '<video src="' + url + '" controls></video>';
    }
}

// Submit add image
function submitAdd(formId) {
    var form = document.getElementById(formId);
    var fd = new FormData(form);
    // If URL field filled but no file, set content to URL
    if (formId === 'addImageForm' && document.getElementById('imgUrl').value && !document.getElementById('imgFile').files.length) {
        fd.set('content', document.getElementById('imgUrl').value);
    }
    if (formId === 'addVideoForm' && document.getElementById('vidUrl').value && !document.getElementById('vidFile').files.length) {
        fd.set('content', document.getElementById('vidUrl').value);
    }
    fetch('', { method:'POST', body: fd })
        .then(r => r.json()).then(d => { if(d.ok) location.reload(); else alert('Error saving'); });
}

function submitTextAdd() {
    document.getElementById('textContent').value = addTextQ.root.innerHTML;
    var fd = new FormData(document.getElementById('addTextForm'));
    fetch('', { method:'POST', body: fd })
        .then(r => r.json()).then(d => { if(d.ok) location.reload(); });
}

function submitBannerAdd() {
    document.getElementById('bannerContent').value = addBannerQ.root.innerHTML;
    var fd = new FormData(document.getElementById('addBannerForm'));
    fetch('', { method:'POST', body: fd })
        .then(r => r.json()).then(d => { if(d.ok) location.reload(); });
}

// Delete
function deleteSection(id, btn) {
    if (!confirm('Is section ko delete karna chahte ho?')) return;
    btn.closest('.section-card').style.opacity = '0.4';
    fetch('', { method:'POST', body: new URLSearchParams({ ajax_action:'delete', section_id: id }) })
        .then(r => r.json()).then(d => { if(d.ok) location.reload(); });
}

// Toggle
function toggleSection(id, btn) {
    fetch('', { method:'POST', body: new URLSearchParams({ ajax_action:'toggle', section_id: id }) })
        .then(r => r.json()).then(d => { if(d.ok) location.reload(); });
}

// Edit modal
var sectionsData = <?= json_encode(array_values($sections), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;

function openEdit(id) {
    var sec = sectionsData.find(s => s.id === id);
    if (!sec) return;

    document.getElementById('editId').value   = id;
    document.getElementById('editType').value = sec.type;
    document.getElementById('editContent').value = sec.content || '';

    ['editImageFields','editVideoFields','editTextFields','editBannerFields'].forEach(f => {
        document.getElementById(f).style.display = 'none';
    });

    if (sec.type === 'image') {
        document.getElementById('editImageFields').style.display = 'block';
        document.getElementById('editImgUrl').value = sec.content || '';
    }
    if (sec.type === 'video') {
        document.getElementById('editVideoFields').style.display = 'block';
        document.getElementById('editVidUrl').value = sec.content || '';
    }
    if (sec.type === 'text') {
        document.getElementById('editTextFields').style.display = 'block';
        document.getElementById('editTitleText').value = sec.title || '';
        if (!editTextQ) editTextQ = new Quill('#editTextEditor', quillConfig);
        editTextQ.clipboard.dangerouslyPasteHTML(sec.content || '');
    }
    if (sec.type === 'banner') {
        document.getElementById('editBannerFields').style.display = 'block';
        document.getElementById('editTitleBanner').value = sec.title || '';
        document.getElementById('editBgColor').value    = sec.bg_color || '#1f3e35';
        document.getElementById('editTextColor').value  = sec.text_color || '#ffffff';
        if (!editBannerQ) editBannerQ = new Quill('#editBannerEditor', quillConfig);
        editBannerQ.clipboard.dangerouslyPasteHTML(sec.content || '');
    }

    document.getElementById('editModal').classList.add('open');
}

function closeEdit() {
    document.getElementById('editModal').classList.remove('open');
}

function submitEdit() {
    var type = document.getElementById('editType').value;
    var fd = new FormData(document.getElementById('editForm'));

    if (type === 'image') {
        var urlVal = document.getElementById('editImgUrl').value;
        var fileEl = document.getElementById('editImgFile');
        if (!fileEl.files.length && urlVal) fd.set('content', urlVal);
    }
    if (type === 'video') {
        var urlVal = document.getElementById('editVidUrl').value;
        var fileEl = document.getElementById('editVidFile');
        if (!fileEl.files.length && urlVal) fd.set('content', urlVal);
    }
    if (type === 'text') {
        fd.set('content', editTextQ ? editTextQ.root.innerHTML : '');
        fd.set('title', document.getElementById('editTitleText').value);
    }
    if (type === 'banner') {
        fd.set('content', editBannerQ ? editBannerQ.root.innerHTML : '');
        fd.set('title', document.getElementById('editTitleBanner').value);
        fd.set('bg_color', document.getElementById('editBgColor').value);
        fd.set('text_color', document.getElementById('editTextColor').value);
    }

    fetch('', { method:'POST', body: fd })
        .then(r => r.json()).then(d => { if(d.ok) location.reload(); else alert('Save error'); });
}

// Close modal on overlay click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
