<?php
/**
 * Shared blog form for add and edit. Expects $mode, $blog (array, may be empty), $error.
 */
$b = $blog ?? [];
$saved = isset($_GET['saved']);
$faqs = !empty($b['faq_json']) ? Blog::decodeFaqs($b['faq_json']) : [];
function bv($b, $k, $d='') { return htmlspecialchars($b[$k] ?? $d, ENT_QUOTES); }
?>
<div class="admin-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1><?php echo $mode === 'edit' ? 'Edit Blog' : 'Add New Blog'; ?></h1>
    <a href="blogs.php" style="color:#666; text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Blogs</a>
</div>

<?php if (!empty($error)): ?>
    <div style="background:#f8d7da; color:#721c24; padding:12px; border-radius:5px; margin-bottom:15px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($saved): ?>
    <div style="background:#d4edda; color:#155724; padding:12px; border-radius:5px; margin-bottom:15px;">Blog updated successfully.</div>
<?php endif; ?>

<style>
.blog-form-section { background:#fff; padding:25px; border-radius:8px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05);}
.blog-form-section h3 { margin:0 0 18px; color:#2C3E50; padding-bottom:10px; border-bottom:2px solid #C9A227; font-size:1.1rem;}
.blog-form-section .row { display:grid; grid-template-columns: 1fr 1fr; gap:18px; margin-bottom:15px;}
.blog-form-section .full { grid-column: 1 / -1; }
.blog-form-section label { display:block; font-weight:600; color:#444; margin-bottom:6px; font-size:0.9rem;}
.blog-form-section input[type=text], .blog-form-section input[type=url], .blog-form-section input[type=date],
.blog-form-section input[type=datetime-local], .blog-form-section select, .blog-form-section textarea {
    width:100%; padding:10px 12px; border:1.5px solid #e1e8ed; border-radius:6px; font-size:0.95rem; box-sizing:border-box; font-family:inherit;
}
.blog-form-section textarea { min-height:90px; resize:vertical; }
.blog-form-section .hint { color:#888; font-size:0.8rem; margin-top:4px; }
.faq-row { display:grid; grid-template-columns: 1fr 2fr auto; gap:10px; margin-bottom:10px; }
.btn-add-faq { background:#6c757d; color:#fff; padding:6px 14px; border:none; border-radius:5px; cursor:pointer; font-size:0.85rem;}
.btn-remove-faq { background:#dc3545; color:#fff; padding:0 12px; border:none; border-radius:5px; cursor:pointer;}
.preview-img { max-width:200px; border-radius:6px; margin-bottom:8px; display:block; }
.submit-bar { position:sticky; bottom:0; background:#fff; padding:18px 25px; border-radius:8px; display:flex; justify-content:flex-end; gap:10px; box-shadow:0 -2px 8px rgba(0,0,0,0.08); margin-top:20px; z-index:5;}
.btn-save { background:#C9A227; color:#fff; padding:12px 28px; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-size:0.95rem;}
.btn-save:hover { background:#b08d20; }
.btn-cancel { background:#fff; color:#666; padding:12px 28px; border:1.5px solid #ddd; border-radius:6px; font-weight:600; text-decoration:none; cursor:pointer;}
.status-tile { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:6px; font-size:0.85rem; font-weight:600;}
.status-ok { background:#e8f5e9; color:#256029;}
.status-miss { background:#fff4e5; color:#8a5a00;}
@media (max-width:768px) { .blog-form-section .row { grid-template-columns: 1fr; } .faq-row { grid-template-columns:1fr; } }
</style>

<style>
/* ── Custom WYSIWYG Editor (no CDN) ─────────────────────────── */
#lv-toolbar {
    display:flex; flex-wrap:wrap; gap:3px; padding:8px 10px;
    background:#f5f5f5; border:1.5px solid #e1e8ed;
    border-bottom:none; border-radius:6px 6px 0 0;
}
#lv-toolbar button, #lv-toolbar select {
    padding:4px 8px; border:1px solid #ccc; border-radius:4px;
    background:#fff; cursor:pointer; font-size:0.85rem; line-height:1.4;
    color:#333; font-family:inherit;
}
#lv-toolbar button:hover { background:#e8e8e8; }
#lv-toolbar button.active { background:#C9A227; color:#fff; border-color:#C9A227; }
#lv-toolbar .sep { width:1px; background:#ddd; margin:2px 4px; }
#lv-editor {
    min-height:320px; padding:14px 16px;
    border:1.5px solid #e1e8ed; border-radius:0 0 6px 6px;
    font-size:0.95rem; font-family:inherit; line-height:1.7;
    outline:none; background:#fff; overflow-y:auto;
}
#lv-editor:focus { border-color:#C9A227; }
#lv-editor h2 { font-size:1.4rem; margin:16px 0 8px; }
#lv-editor h3 { font-size:1.2rem; margin:14px 0 6px; }
#lv-editor h4 { font-size:1.05rem; margin:12px 0 5px; }
#lv-editor blockquote { border-left:4px solid #C9A227; margin:12px 0; padding:8px 16px; background:#fffbf0; color:#555; }
#lv-editor ul, #lv-editor ol { padding-left:24px; margin:8px 0; }
#lv-editor img { max-width:100%; height:auto; border-radius:4px; margin:6px 0; }
#lv-editor a { color:#C9A227; }
#lv-img-upload { display:none; }
</style>

<form method="POST" enctype="multipart/form-data" id="blog-form">

<div class="blog-form-section">
    <h3>1. Basic Content</h3>
    <div class="row">
        <div class="full">
            <label>Blog Title (H1) *</label>
            <input type="text" name="title" required value="<?php echo bv($b,'title'); ?>" placeholder="e.g. 7 Ayurvedic Tips for Better Sleep">
        </div>
        <div>
            <label>URL / Slug</label>
            <input type="text" name="slug" value="<?php echo bv($b,'slug'); ?>" placeholder="auto-generated from title">
            <div class="hint">Final URL: <strong>https://<?php echo defined('SITE_DOMAIN')?SITE_DOMAIN:'yourdomain.com'; ?>/blog/&lt;slug&gt;</strong></div>
        </div>
        <div>
            <label>Status</label>
            <select name="status">
                <?php $st = $b['status'] ?? 'draft'; ?>
                <option value="draft" <?php echo $st==='draft'?'selected':''; ?>>Draft</option>
                <option value="published" <?php echo $st==='published'?'selected':''; ?>>Published</option>
                <option value="scheduled" <?php echo $st==='scheduled'?'selected':''; ?>>Scheduled</option>
            </select>
        </div>

        <!-- Date fields moved here from E-E-A-T as per client request -->
        <div>
            <label>Publish Date</label>
            <input type="date" name="publish_date" value="<?php echo bv($b,'publish_date'); ?>">
            <div class="hint">Auto-set to today when status is "Published" and left blank.</div>
        </div>
        <div>
            <label>Last Update Date</label>
            <input type="date" name="update_date" value="<?php echo bv($b,'update_date'); ?>">
        </div>
        <div class="full">
            <label>Schedule Date &amp; Time (only when status = Scheduled)</label>
            <input type="datetime-local" name="schedule_date" value="<?php echo !empty($b['schedule_date']) ? date('Y-m-d\TH:i', strtotime($b['schedule_date'])) : ''; ?>">
        </div>

        <div class="full">
            <label>Short Description (Excerpt)</label>
            <textarea name="excerpt" placeholder="A brief 1-2 line summary shown on the blog listing page and at the top of the blog post"><?php echo bv($b,'excerpt'); ?></textarea>
        </div>
        <div class="full">
            <label>Main Content *</label>
            <!-- Custom WYSIWYG toolbar — zero CDN, pure JS -->
            <div id="lv-toolbar">
                <select id="lv-heading" title="Paragraph style">
                    <option value="p">Normal</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                    <option value="h4">Heading 4</option>
                    <option value="blockquote">Blockquote</option>
                    <option value="pre">Code</option>
                </select>
                <div class="sep"></div>
                <button type="button" title="Bold" onclick="lv_cmd('bold')"><b>B</b></button>
                <button type="button" title="Italic" onclick="lv_cmd('italic')"><i>I</i></button>
                <button type="button" title="Underline" onclick="lv_cmd('underline')"><u>U</u></button>
                <button type="button" title="Strikethrough" onclick="lv_cmd('strikeThrough')"><s>S</s></button>
                <div class="sep"></div>
                <button type="button" title="Ordered list" onclick="lv_cmd('insertOrderedList')">1.</button>
                <button type="button" title="Bullet list" onclick="lv_cmd('insertUnorderedList')">&#8226;</button>
                <div class="sep"></div>
                <button type="button" title="Insert link" onclick="lv_link()">&#128279; Link</button>
                <button type="button" title="Insert image from URL or upload" onclick="document.getElementById('lv-img-upload').click()">&#128247; Image</button>
                <input type="file" id="lv-img-upload" accept="image/*" onchange="lv_uploadImg(this)">
                <div class="sep"></div>
                <button type="button" title="Remove formatting" onclick="lv_cmd('removeFormat')" style="color:#999;">Tx</button>
            </div>
            <div id="lv-editor" contenteditable="true"></div>
            <input type="hidden" name="content" id="content-hidden" value="<?php echo htmlspecialchars($b['content'] ?? '', ENT_QUOTES); ?>">
            <div class="hint">Use the toolbar — <strong>bold</strong>, headings, lists, links, images. No HTML knowledge required.</div>
        </div>
        <div>
            <label>Featured Image</label>
            <?php if (!empty($b['featured_image'])): ?>
                <img src="../uploads/blogs/<?php echo htmlspecialchars($b['featured_image']); ?>" class="preview-img" alt="">
                <input type="hidden" name="existing_featured_image" value="<?php echo htmlspecialchars($b['featured_image']); ?>">
            <?php endif; ?>
            <input type="file" name="featured_image" accept="image/*">
            <div class="hint">JPG/PNG/WebP. Recommended 1200x630px. Max 8 MB.</div>
        </div>
        <div>
            <label>Featured Image Alt Text</label>
            <input type="text" name="featured_image_alt" value="<?php echo bv($b,'featured_image_alt'); ?>" placeholder="Describe the image for SEO/accessibility">
        </div>
    </div>
</div>

<div class="blog-form-section">
    <h3>2. SEO Fields</h3>
    <div class="row">
        <div class="full">
            <label>Meta Title</label>
            <input type="text" name="meta_title" value="<?php echo bv($b,'meta_title'); ?>" placeholder="Shown in Google search results (50-60 chars)">
        </div>
        <div class="full">
            <label>Meta Description</label>
            <textarea name="meta_description" placeholder="Shown in Google search results (140-160 chars)"><?php echo bv($b,'meta_description'); ?></textarea>
        </div>
        <div>
            <label>Primary Keyword</label>
            <input type="text" name="primary_keyword" value="<?php echo bv($b,'primary_keyword'); ?>" placeholder="e.g. ayurvedic immunity">
        </div>
        <div>
            <label>Canonical URL</label>
            <input type="url" name="canonical_url" value="<?php echo bv($b,'canonical_url'); ?>" placeholder="Leave blank for default /blog/&lt;slug&gt;">
        </div>
        <div>
            <label>Robots</label>
            <select name="robots">
                <?php $r = $b['robots'] ?? 'index,follow'; ?>
                <option value="index,follow" <?php echo $r==='index,follow'?'selected':''; ?>>Index, Follow (default)</option>
                <option value="noindex,follow" <?php echo $r==='noindex,follow'?'selected':''; ?>>Noindex, Follow</option>
                <option value="index,nofollow" <?php echo $r==='index,nofollow'?'selected':''; ?>>Index, Nofollow</option>
                <option value="noindex,nofollow" <?php echo $r==='noindex,nofollow'?'selected':''; ?>>Noindex, Nofollow</option>
            </select>
        </div>
    </div>
</div>

<div class="blog-form-section">
    <h3>3. AEO (Answer Optimization)</h3>
    <div class="row">
        <div class="full">
            <label>Featured Snippet Answer (40-60 words)</label>
            <textarea name="featured_snippet" placeholder="A short, direct answer to the main question of this blog. Used by Google for featured snippets."><?php echo bv($b,'featured_snippet'); ?></textarea>
        </div>
        <div class="full">
            <label>FAQ Section (Q &amp; A)</label>
            <div id="faq-list">
                <?php if (!empty($faqs)): foreach ($faqs as $f): ?>
                    <div class="faq-row">
                        <input type="text" name="faq_q[]" placeholder="Question" value="<?php echo htmlspecialchars($f['q'] ?? ''); ?>">
                        <input type="text" name="faq_a[]" placeholder="Answer" value="<?php echo htmlspecialchars($f['a'] ?? ''); ?>">
                        <button type="button" class="btn-remove-faq" onclick="this.parentNode.remove()">×</button>
                    </div>
                <?php endforeach; else: ?>
                    <div class="faq-row">
                        <input type="text" name="faq_q[]" placeholder="Question">
                        <input type="text" name="faq_a[]" placeholder="Answer">
                        <button type="button" class="btn-remove-faq" onclick="this.parentNode.remove()">×</button>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-add-faq" onclick="addFaq()">+ Add FAQ</button>
        </div>
    </div>
</div>

<div class="blog-form-section">
    <h3>4. GEO (AI + Intent Optimization)</h3>
    <div class="row">
        <div>
            <label>Content Intent</label>
            <select name="content_intent">
                <?php $ci = $b['content_intent'] ?? ''; ?>
                <option value="">— Select —</option>
                <option value="Info" <?php echo $ci==='Info'?'selected':''; ?>>Informational</option>
                <option value="Commercial" <?php echo $ci==='Commercial'?'selected':''; ?>>Commercial</option>
                <option value="Transactional" <?php echo $ci==='Transactional'?'selected':''; ?>>Transactional</option>
                <option value="Navigational" <?php echo $ci==='Navigational'?'selected':''; ?>>Navigational</option>
            </select>
        </div>
        <div>
            <label>Target Audience</label>
            <select name="target_audience">
                <?php $ta = $b['target_audience'] ?? ''; ?>
                <option value="">— Select —</option>
                <option value="B2B" <?php echo $ta==='B2B'?'selected':''; ?>>B2B</option>
                <option value="B2C" <?php echo $ta==='B2C'?'selected':''; ?>>B2C</option>
                <option value="Both" <?php echo $ta==='Both'?'selected':''; ?>>Both</option>
            </select>
        </div>
        <div>
            <label>Region</label>
            <input type="text" name="region" value="<?php echo bv($b,'region'); ?>" placeholder="e.g. India / Global">
        </div>
        <div>
            <label>Language</label>
            <input type="text" name="language" value="<?php echo bv($b,'language','en'); ?>" placeholder="en, hi, etc.">
        </div>
        <div class="full">
            <label>Entity Tags (Topic, Brand, Product)</label>
            <input type="text" name="entity_tags" value="<?php echo bv($b,'entity_tags'); ?>" placeholder="ayurveda, livvra, kumkumadi oil">
            <div class="hint">Comma-separated topics, brand names, products mentioned in this blog.</div>
        </div>
    </div>
</div>

<div class="blog-form-section">
    <h3>5. E-E-A-T Signals</h3>
    <div class="row">
        <div>
            <label>Author Name</label>
            <input type="text" name="author_name" value="<?php echo bv($b,'author_name'); ?>" placeholder="e.g. Dr. R. Sharma">
        </div>
        <div>
            <label>&nbsp;</label>
            <div class="hint" style="padding-top:10px;">Publish &amp; Update dates are now in <strong>Section 1 — Basic Content</strong>.</div>
        </div>
        <div class="full">
            <label>Author Bio</label>
            <textarea name="author_bio" id="author_bio" placeholder="Short bio with credentials, experience, qualifications"><?php echo htmlspecialchars($b['author_bio'] ?? '', ENT_QUOTES); ?></textarea>
        </div>
    </div>
</div>

<div class="blog-form-section">
    <h3>6. Links</h3>
    <div class="row">
        <div>
            <label>Internal Links</label>
            <textarea name="internal_links" placeholder="One per line. Format:&#10;Anchor Text | /url-path&#10;Our Kumkumadi Oil | /benefits-of-kumkumadi-beauty-oil.php"><?php echo bv($b,'internal_links'); ?></textarea>
        </div>
        <div>
            <label>External Links</label>
            <textarea name="external_links" placeholder="One per line. Format:&#10;Anchor Text | https://example.com"><?php echo bv($b,'external_links'); ?></textarea>
        </div>
    </div>
</div>

<div class="blog-form-section">
    <h3>7. CTA Section</h3>
    <div class="row">
        <div>
            <label>CTA Button Text</label>
            <input type="text" name="cta_text" value="<?php echo bv($b,'cta_text'); ?>" placeholder="e.g. Shop Now">
        </div>
        <div>
            <label>CTA Button Link</label>
            <input type="url" name="cta_link" value="<?php echo bv($b,'cta_link'); ?>" placeholder="https://livvra.in/products">
        </div>
    </div>
</div>

<?php if ($mode === 'edit' && !empty($b['slug'])): ?>
<div class="blog-form-section">
    <h3>SEO &amp; Status Check (read-only)</h3>
    <p style="margin:0 0 10px; color:#666; font-size:0.9rem;">A quick health-check of what's configured for this post.</p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:10px;">
        <?php
        $checks = [
            'Meta Title'        => !empty($b['meta_title']),
            'Meta Description'  => !empty($b['meta_description']),
            'Canonical URL'     => true,
            'Featured Image'    => !empty($b['featured_image']),
            'Image Alt Text'    => !empty($b['featured_image_alt']),
            'Featured Snippet'  => !empty($b['featured_snippet']),
            'FAQs (AEO)'        => !empty($b['faq_json']),
            'Content Intent (GEO)' => !empty($b['content_intent']),
            'Target Audience (GEO)' => !empty($b['target_audience']),
            'Region (GEO)'      => !empty($b['region']),
            'Author Name (E-E-A-T)' => !empty($b['author_name']),
            'Author Bio (E-E-A-T)'  => !empty($b['author_bio']),
            'Publish Date'      => !empty($b['publish_date']),
            'CTA Configured'    => !empty($b['cta_text']) && !empty($b['cta_link']),
        ];
        foreach ($checks as $name => $ok): ?>
            <div class="status-tile <?php echo $ok ? 'status-ok' : 'status-miss'; ?>">
                <i class="fas fa-<?php echo $ok ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($name); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <p style="margin-top:14px;"><a href="../blog/<?php echo htmlspecialchars($b['slug']); ?>" target="_blank" style="color:#C9A227; font-weight:600;"><i class="fas fa-external-link-alt"></i> View live blog page</a></p>
</div>
<?php endif; ?>

<div class="submit-bar">
    <a href="blogs.php" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Blog</button>
</div>

</form>

<script>
function addFaq() {
    const list = document.getElementById('faq-list');
    const row = document.createElement('div');
    row.className = 'faq-row';
    row.innerHTML = '<input type="text" name="faq_q[]" placeholder="Question">' +
                    '<input type="text" name="faq_a[]" placeholder="Answer">' +
                    '<button type="button" class="btn-remove-faq" onclick="this.parentNode.remove()">×</button>';
    list.appendChild(row);
}

// Auto-generate slug from title (only when slug field is empty)
const titleInput = document.querySelector('input[name="title"]');
const slugInput  = document.querySelector('input[name="slug"]');
if (titleInput && slugInput) {
    titleInput.addEventListener('blur', function() {
        if (slugInput.value.trim() === '') {
            slugInput.value = titleInput.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .trim().replace(/\s+/g, '-');
        }
    });
}

// ── Custom WYSIWYG Editor (pure vanilla JS, zero CDN) ──────────
var lvEditor   = document.getElementById('lv-editor');
var lvHidden   = document.getElementById('content-hidden');
var lvHeading  = document.getElementById('lv-heading');
var lvSavedRange = null;

// Pre-load existing content (edit mode)
var lvExisting = lvHidden.value;
if (lvExisting) { lvEditor.innerHTML = lvExisting; }

// Focus editor before every toolbar action (restores cursor)
function lv_focus() {
    lvEditor.focus();
    if (lvSavedRange) {
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(lvSavedRange);
    }
}

// Save cursor position whenever user interacts with editor
lvEditor.addEventListener('mouseup',  function() { var s = window.getSelection(); if (s.rangeCount) lvSavedRange = s.getRangeAt(0).cloneRange(); });
lvEditor.addEventListener('keyup',    function() { var s = window.getSelection(); if (s.rangeCount) lvSavedRange = s.getRangeAt(0).cloneRange(); });

// Generic execCommand wrapper
function lv_cmd(cmd, val) {
    lv_focus();
    document.execCommand(cmd, false, val || null);
    lvEditor.focus();
}

// Heading / paragraph format
lvHeading.addEventListener('change', function() {
    lv_focus();
    document.execCommand('formatBlock', false, '<' + this.value + '>');
    lvEditor.focus();
    this.value = 'p';
});

// Link insertion
function lv_link() {
    var sel = window.getSelection();
    var txt = sel.toString();
    var url = prompt('Enter link URL:', 'https://');
    if (!url) return;
    lv_focus();
    if (txt) {
        document.execCommand('createLink', false, url);
    } else {
        var label = prompt('Link text:', url);
        document.execCommand('insertHTML', false,
            '<a href="' + url + '" target="_blank">' + (label || url) + '</a>');
    }
    lvEditor.focus();
}

// Image upload via AJAX → insert into editor
function lv_uploadImg(input) {
    if (!input.files || !input.files[0]) return;
    var fd = new FormData();
    fd.append('image', input.files[0]);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload-image.php', true);
    xhr.onload = function() {
        try {
            var res = JSON.parse(xhr.responseText);
            if (res.url) {
                lv_focus();
                document.execCommand('insertHTML', false,
                    '<img src="' + res.url + '" alt="" style="max-width:100%">');
                lvEditor.focus();
            } else {
                alert('Upload failed: ' + (res.error || 'Unknown error'));
            }
        } catch(e) { alert('Upload error. Check server response.'); }
    };
    xhr.onerror = function() { alert('Network error during upload.'); };
    xhr.send(fd);
    input.value = '';
}

// Sync editor HTML to hidden input before form submit
document.getElementById('blog-form').addEventListener('submit', function() {
    lvHidden.value = lvEditor.innerHTML;
});
</script>
