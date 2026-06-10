<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models/Setting.php';

if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle   = 'Header / Footer / Menu Settings';
$currentPage = 'site-settings';
$saved = false;

// ── Save handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    // Announcement bar
    if ($act === 'save_announcement') {
        Setting::set('site_announcement_enabled', (int)($_POST['announcement_enabled'] ?? 0));
        Setting::set('site_announcement_text',    trim($_POST['announcement_text'] ?? ''));
        Setting::set('site_announcement_bg',      trim($_POST['announcement_bg'] ?? '#1a252f'));
        Setting::set('site_announcement_link',    trim($_POST['announcement_link'] ?? ''));
        $saved = 'announcement';
    }

    // Menu – SHOP items (JSON)
    if ($act === 'save_shop_menu') {
        $names  = $_POST['shop_name']  ?? [];
        $urls   = $_POST['shop_url']   ?? [];
        $icons  = $_POST['shop_icon']  ?? [];
        $bgs    = $_POST['shop_bg']    ?? [];
        $icolors = $_POST['shop_icon_color'] ?? [];
        $items = [];
        foreach ($names as $i => $n) {
            if (trim($n) === '') continue;
            $items[] = [
                'name'       => trim($n),
                'url'        => trim($urls[$i] ?? '/products.php'),
                'icon'       => trim($icons[$i] ?? 'leaf'),
                'bg'         => trim($bgs[$i] ?? '#e9f0df'),
                'icon_color' => trim($icolors[$i] ?? '#5d7234'),
            ];
        }
        Setting::set('menu_shop_items', json_encode($items, JSON_UNESCAPED_UNICODE));
        $saved = 'shop_menu';
    }

    // Menu – Other Links (JSON)
    if ($act === 'save_other_links') {
        $names = $_POST['link_name'] ?? [];
        $urls  = $_POST['link_url']  ?? [];
        $items = [];
        foreach ($names as $i => $n) {
            if (trim($n) === '') continue;
            $items[] = ['name' => trim($n), 'url' => trim($urls[$i] ?? '/')];
        }
        Setting::set('menu_other_links', json_encode($items, JSON_UNESCAPED_UNICODE));
        $saved = 'other_links';
    }

    // Footer – Contact info
    if ($act === 'save_footer_contact') {
        Setting::set('footer_phone',   trim($_POST['footer_phone'] ?? ''));
        Setting::set('footer_email',   trim($_POST['footer_email'] ?? ''));
        Setting::set('footer_address', trim($_POST['footer_address'] ?? ''));
        $saved = 'footer_contact';
    }

    // Footer – Shop links
    if ($act === 'save_footer_shop') {
        $names = $_POST['fshop_name'] ?? [];
        $urls  = $_POST['fshop_url']  ?? [];
        $items = [];
        foreach ($names as $i => $n) {
            if (trim($n) === '') continue;
            $items[] = ['name' => trim($n), 'url' => trim($urls[$i] ?? '/')];
        }
        Setting::set('footer_shop_links', json_encode($items, JSON_UNESCAPED_UNICODE));
        $saved = 'footer_shop';
    }

    // Footer – Info links
    if ($act === 'save_footer_info') {
        $names = $_POST['finfo_name'] ?? [];
        $urls  = $_POST['finfo_url']  ?? [];
        $items = [];
        foreach ($names as $i => $n) {
            if (trim($n) === '') continue;
            $items[] = ['name' => trim($n), 'url' => trim($urls[$i] ?? '/')];
        }
        Setting::set('footer_info_links', json_encode($items, JSON_UNESCAPED_UNICODE));
        $saved = 'footer_info';
    }

    // Custom Code Injection
    if ($act === 'save_custom_code') {
        Setting::set('custom_code_head',       $_POST['custom_code_head']       ?? '');
        Setting::set('custom_code_body_start', $_POST['custom_code_body_start'] ?? '');
        Setting::set('custom_code_body_end',   $_POST['custom_code_body_end']   ?? '');
        $saved = 'custom_code';
    }

    // Footer – Social links
    if ($act === 'save_footer_social') {
        Setting::set('footer_follow_heading',  trim($_POST['follow_heading']  ?? 'FOLLOW US'));
        Setting::set('footer_mailing_heading', trim($_POST['mailing_heading'] ?? 'JOIN OUR MAILING LIST'));
        Setting::set('footer_instagram_url',   trim($_POST['instagram_url']   ?? ''));
        Setting::set('footer_facebook_url',    trim($_POST['facebook_url']    ?? ''));
        Setting::set('footer_youtube_url',     trim($_POST['youtube_url']     ?? ''));
        Setting::set('footer_twitter_url',     trim($_POST['twitter_url']     ?? ''));
        Setting::set('footer_social_extra',    trim($_POST['social_extra']    ?? ''));
        $saved = 'footer_social';
    }
}

// ── Load current values ───────────────────────────────────────────────────────
$ann_enabled = Setting::get('site_announcement_enabled', '1');
$ann_text    = Setting::get('site_announcement_text', 'ADDITIONAL 10% OFF WITH LIVVRA COINS');
$ann_bg      = Setting::get('site_announcement_bg', '#1a252f');
$ann_link    = Setting::get('site_announcement_link', '');

$shopItemsRaw   = Setting::get('menu_shop_items', '');
$shopItems = $shopItemsRaw ? json_decode($shopItemsRaw, true) : [
    ['name'=>'Summer Essentials','url'=>'/products.php','icon'=>'sun',      'bg'=>'#fce8e4','icon_color'=>'#b57d62'],
    ['name'=>'All Products',     'url'=>'/products.php','icon'=>'leaf',     'bg'=>'#e9f0df','icon_color'=>'#5d7234'],
    ['name'=>'Ingredients',      'url'=>'/blog.php',    'icon'=>'database', 'bg'=>'#f3f4f6','icon_color'=>'#6b7280'],
    ['name'=>'Track Order',      'url'=>'/track-order.php','icon'=>'package','bg'=>'#fce8e4','icon_color'=>'#b57d62'],
];
if (!$shopItemsRaw) Setting::set('menu_shop_items', json_encode($shopItems, JSON_UNESCAPED_UNICODE));

$otherLinksRaw = Setting::get('menu_other_links', '');
$otherLinks = $otherLinksRaw ? json_decode($otherLinksRaw, true) : [
    ['name'=>'Blog',              'url'=>'/blog.php'],
    ['name'=>'About Us',          'url'=>'/about.php'],
    ['name'=>'Contact Us',        'url'=>'/contact.php'],
    ['name'=>'Shipping Policy',   'url'=>'/shipping-policy.php'],
    ['name'=>'Terms & Conditions','url'=>'/terms-and-conditions.php'],
    ['name'=>'Refund Policy',     'url'=>'/refund-cancellation.php'],
];
if (!$otherLinksRaw) Setting::set('menu_other_links', json_encode($otherLinks, JSON_UNESCAPED_UNICODE));

$footer_phone   = Setting::get('footer_phone',   '8958489684');
$footer_email   = Setting::get('footer_email',   'livvraindia@gmail.com');
$footer_address = Setting::get('footer_address', "DR TRIDOSHA HERBOTECH PRIVATE LIMITED\nCIN: U21003PB2025PTC066121\nGST: 03AAMCD1391C1Z1\n20062 C, Street No. 4, Jujhar Nagar, Bathinda, Punjab – 151001, India");

$fShopRaw = Setting::get('footer_shop_links', '');
$fShop = $fShopRaw ? json_decode($fShopRaw, true) : [
    ['name'=>'SHOP ALL',    'url'=>'/products.php'],
    ['name'=>'MY ACCOUNT',  'url'=>'/account.php'],
    ['name'=>'TRACK ORDER', 'url'=>'/track-order.php'],
    ['name'=>'VIEW CART',   'url'=>'/cart.php'],
];
if (!$fShopRaw) Setting::set('footer_shop_links', json_encode($fShop, JSON_UNESCAPED_UNICODE));

$fInfoRaw = Setting::get('footer_info_links', '');
$fInfo = $fInfoRaw ? json_decode($fInfoRaw, true) : [
    ['name'=>'ABOUT US',          'url'=>'/about.php'],
    ['name'=>'BLOG',              'url'=>'/blog.php'],
    ['name'=>'SHIPPING POLICY',   'url'=>'/shipping-policy.php'],
    ['name'=>'TERMS & CONDITIONS','url'=>'/terms-and-conditions.php'],
    ['name'=>'REFUND POLICY',     'url'=>'/refund-cancellation.php'],
    ['name'=>'CONTACT US',        'url'=>'/contact.php'],
];
if (!$fInfoRaw) Setting::set('footer_info_links', json_encode($fInfo, JSON_UNESCAPED_UNICODE));

$custom_code_head       = Setting::get('custom_code_head', '');
$custom_code_body_start = Setting::get('custom_code_body_start', '');
$custom_code_body_end   = Setting::get('custom_code_body_end', '');

$follow_heading  = Setting::get('footer_follow_heading',  'FOLLOW US');
$mailing_heading = Setting::get('footer_mailing_heading', 'JOIN OUR MAILING LIST');
$instagram_url   = Setting::get('footer_instagram_url',   'https://www.instagram.com/livvraindia/');
$facebook_url    = Setting::get('footer_facebook_url',    'https://www.facebook.com/livvra');
$youtube_url     = Setting::get('footer_youtube_url',     'https://www.youtube.com/@LivvraIndia');
$twitter_url     = Setting::get('footer_twitter_url',     '');

require_once __DIR__ . '/views/layouts/header.php';
?>

<style>
.ss-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:24px; }
.ss-tab  { padding:9px 18px; border-radius:8px; border:2px solid #e0e0e0; background:#fff; color:#555; font-size:13px; font-weight:700; cursor:pointer; transition:all 0.18s; }
.ss-tab.active, .ss-tab:hover { border-color:#76a33a; background:#f4fdf0; color:#1f3e35; }
.ss-panel { display:none; } .ss-panel.active { display:block; }

.ss-card { background:#fff; border:1.5px solid #e8f0e4; border-radius:12px; padding:22px 24px; margin-bottom:18px; }
.ss-card h3 { font-size:15px; font-weight:800; color:#1f3e35; margin:0 0 16px; border-bottom:2px solid #e8f0e4; padding-bottom:10px; }
.ss-label { font-size:13px; font-weight:600; color:#1f3e35; display:block; margin-bottom:5px; }
.ss-input { width:100%; padding:9px 12px; border:1.5px solid #ddd; border-radius:6px; font-size:13px; outline:none; font-family:inherit; box-sizing:border-box; }
.ss-input:focus { border-color:#76a33a; }
.ss-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media(max-width:640px){ .ss-row { grid-template-columns:1fr; } }

/* Repeater */
.rep-item { display:flex; align-items:center; gap:8px; background:#f8fdf5; border:1px solid #e0ead8; border-radius:8px; padding:10px 12px; margin-bottom:8px; }
.rep-item input { flex:1; min-width:0; }
.rep-drag  { cursor:grab; color:#aaa; font-size:18px; flex-shrink:0; }
.rep-del   { background:#f8d7da; color:#721c24; border:none; border-radius:6px; padding:5px 10px; cursor:pointer; font-size:13px; font-weight:700; flex-shrink:0; }
.rep-del:hover { background:#f5c6cb; }
.rep-add   { background:#e8f0e4; color:#1f3e35; border:none; border-radius:6px; padding:8px 16px; cursor:pointer; font-size:13px; font-weight:700; margin-top:6px; }
.rep-add:hover { background:#d4e6cc; }

.save-btn { background:#76a33a; color:#fff; border:none; border-radius:8px; padding:10px 26px; font-size:14px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
.save-btn:hover { background:#5d7234; }

.preview-badge { display:inline-block; background:#d4edda; color:#155724; font-size:11px; font-weight:700; padding:2px 8px; border-radius:12px; margin-left:8px; }
.saved-banner  { background:#d4edda; color:#155724; border-radius:8px; padding:12px 16px; font-size:13px; font-weight:600; margin-bottom:16px; display:flex; align-items:center; gap:8px; }

.lucide-icon-hint { font-size:11px; color:#888; margin-top:3px; }
a.lucide-ref { color:#76a33a; text-decoration:underline; }
</style>

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#1f3e35;margin:0;"><i class="fas fa-sliders-h"></i> Header / Footer / Menu Settings</h1>
        <p style="font-size:13px;color:#666;margin:4px 0 0;">Yahan se header, sidebar menu, aur footer ka sab kuch control karo.</p>
    </div>
    <a href="/" target="_blank" class="btn btn-secondary" style="font-size:13px;"><i class="fas fa-eye"></i> Live Preview</a>
</div>

<?php if ($saved): ?>
<div class="saved-banner">✅ Settings save ho gayi! (<?= htmlspecialchars($saved) ?>)</div>
<?php endif; ?>

<!-- TABS -->
<div class="ss-tabs">
    <button class="ss-tab active" onclick="showTab('ann')">📢 Announcement Bar</button>
    <button class="ss-tab" onclick="showTab('menu')">☰ Sidebar Menu</button>
    <button class="ss-tab" onclick="showTab('footer_contact')">📞 Footer Contact</button>
    <button class="ss-tab" onclick="showTab('footer_links')">🔗 Footer Links</button>
    <button class="ss-tab" onclick="showTab('footer_social')">📱 Social Links</button>
    <button class="ss-tab" onclick="showTab('custom_code')" style="border-color:#e67e22;color:#e67e22;">💻 Custom Code</button>
</div>

<!-- ══════════════════════════════════════════
     TAB 1 — ANNOUNCEMENT BAR
════════════════════════════════════════════ -->
<div class="ss-panel active" id="tab-ann">
    <div class="ss-card">
        <h3>📢 Announcement Bar (Header ke upar ki strip)</h3>
        <form method="POST">
            <input type="hidden" name="act" value="save_announcement">
            <div style="margin-bottom:14px;">
                <label class="ss-label">
                    <input type="checkbox" name="announcement_enabled" value="1" <?= $ann_enabled ? 'checked' : '' ?> style="margin-right:6px;">
                    Announcement Bar Show Karo
                </label>
            </div>
            <div class="ss-row" style="margin-bottom:14px;">
                <div>
                    <label class="ss-label">Bar Text *</label>
                    <input type="text" name="announcement_text" class="ss-input" value="<?= htmlspecialchars($ann_text) ?>" placeholder="ADDITIONAL 10% OFF WITH LIVVRA COINS">
                </div>
                <div>
                    <label class="ss-label">Click karne pe kahan jaaye (URL, optional)</label>
                    <input type="text" name="announcement_link" class="ss-input" value="<?= htmlspecialchars($ann_link) ?>" placeholder="/products.php">
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label class="ss-label">Background Color</label>
                <input type="color" name="announcement_bg" class="ss-input" style="height:42px;max-width:160px;padding:4px;" value="<?= htmlspecialchars($ann_bg) ?>">
            </div>
            <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Announcement</button>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════
     TAB 2 — SIDEBAR MENU
════════════════════════════════════════════ -->
<div class="ss-panel" id="tab-menu">

    <div class="ss-card">
        <h3>🛍️ SHOP Section Items</h3>
        <p style="font-size:12px;color:#888;margin-bottom:16px;">Icon name ke liye <a href="https://lucide.dev/icons/" target="_blank" class="lucide-ref">lucide.dev/icons</a> dekho — jaise: leaf, sun, package, star, heart</p>
        <form method="POST">
            <input type="hidden" name="act" value="save_shop_menu">
            <div id="shopRep">
            <?php foreach ($shopItems as $idx => $item): ?>
            <div class="rep-item" draggable="true">
                <span class="rep-drag">⠿</span>
                <input type="text" name="shop_name[]" class="ss-input" value="<?= htmlspecialchars($item['name']) ?>" placeholder="Menu Item Name" style="max-width:180px;">
                <input type="text" name="shop_url[]" class="ss-input" value="<?= htmlspecialchars($item['url']) ?>" placeholder="/products.php" style="max-width:180px;">
                <input type="text" name="shop_icon[]" class="ss-input" value="<?= htmlspecialchars($item['icon']) ?>" placeholder="leaf" style="max-width:100px;" title="Lucide icon name">
                <input type="color" name="shop_bg[]" class="ss-input" value="<?= htmlspecialchars($item['bg']) ?>" style="height:36px;max-width:50px;padding:3px;">
                <input type="color" name="shop_icon_color[]" class="ss-input" value="<?= htmlspecialchars($item['icon_color']) ?>" style="height:36px;max-width:50px;padding:3px;" title="Icon color">
                <button type="button" class="rep-del" onclick="this.closest('.rep-item').remove()">✕</button>
            </div>
            <?php endforeach; ?>
            </div>
            <button type="button" class="rep-add" onclick="addShopItem()">+ Add Shop Item</button>
            <div style="margin-top:16px;">
                <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Shop Menu</button>
            </div>
        </form>
    </div>

    <div class="ss-card">
        <h3>🔗 Other Menu Links (Blog, About, Contact...)</h3>
        <form method="POST">
            <input type="hidden" name="act" value="save_other_links">
            <div id="otherRep">
            <?php foreach ($otherLinks as $item): ?>
            <div class="rep-item">
                <span class="rep-drag">⠿</span>
                <input type="text" name="link_name[]" class="ss-input" value="<?= htmlspecialchars($item['name']) ?>" placeholder="Link Name">
                <input type="text" name="link_url[]"  class="ss-input" value="<?= htmlspecialchars($item['url']) ?>"  placeholder="/page.php">
                <button type="button" class="rep-del" onclick="this.closest('.rep-item').remove()">✕</button>
            </div>
            <?php endforeach; ?>
            </div>
            <button type="button" class="rep-add" onclick="addOtherLink()">+ Add Link</button>
            <div style="margin-top:16px;">
                <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Other Links</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════
     TAB 3 — FOOTER CONTACT
════════════════════════════════════════════ -->
<div class="ss-panel" id="tab-footer_contact">
    <div class="ss-card">
        <h3>📞 Footer Contact Info</h3>
        <form method="POST">
            <input type="hidden" name="act" value="save_footer_contact">
            <div class="ss-row" style="margin-bottom:14px;">
                <div>
                    <label class="ss-label">Phone Number</label>
                    <input type="text" name="footer_phone" class="ss-input" value="<?= htmlspecialchars($footer_phone) ?>" placeholder="8958489684">
                </div>
                <div>
                    <label class="ss-label">Email Address</label>
                    <input type="text" name="footer_email" class="ss-input" value="<?= htmlspecialchars($footer_email) ?>" placeholder="livvraindia@gmail.com">
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label class="ss-label">Company Address (puri company info)</label>
                <textarea name="footer_address" class="ss-input" rows="5" placeholder="Company name, address..."><?= htmlspecialchars($footer_address) ?></textarea>
            </div>
            <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Contact Info</button>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════
     TAB 4 — FOOTER LINKS
════════════════════════════════════════════ -->
<div class="ss-panel" id="tab-footer_links">

    <div class="ss-card">
        <h3>🛒 Footer Shop Links (left column)</h3>
        <form method="POST">
            <input type="hidden" name="act" value="save_footer_shop">
            <div id="fshopRep">
            <?php foreach ($fShop as $item): ?>
            <div class="rep-item">
                <span class="rep-drag">⠿</span>
                <input type="text" name="fshop_name[]" class="ss-input" value="<?= htmlspecialchars($item['name']) ?>" placeholder="SHOP ALL">
                <input type="text" name="fshop_url[]"  class="ss-input" value="<?= htmlspecialchars($item['url']) ?>"  placeholder="/products.php">
                <button type="button" class="rep-del" onclick="this.closest('.rep-item').remove()">✕</button>
            </div>
            <?php endforeach; ?>
            </div>
            <button type="button" class="rep-add" onclick="addFshop()">+ Add Link</button>
            <div style="margin-top:16px;"><button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Shop Links</button></div>
        </form>
    </div>

    <div class="ss-card">
        <h3>ℹ️ Footer Info Links (middle column)</h3>
        <form method="POST">
            <input type="hidden" name="act" value="save_footer_info">
            <div id="finfoRep">
            <?php foreach ($fInfo as $item): ?>
            <div class="rep-item">
                <span class="rep-drag">⠿</span>
                <input type="text" name="finfo_name[]" class="ss-input" value="<?= htmlspecialchars($item['name']) ?>" placeholder="ABOUT US">
                <input type="text" name="finfo_url[]"  class="ss-input" value="<?= htmlspecialchars($item['url']) ?>"  placeholder="/about.php">
                <button type="button" class="rep-del" onclick="this.closest('.rep-item').remove()">✕</button>
            </div>
            <?php endforeach; ?>
            </div>
            <button type="button" class="rep-add" onclick="addFinfo()">+ Add Link</button>
            <div style="margin-top:16px;"><button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Info Links</button></div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════
     TAB 5 — SOCIAL LINKS (footer above social)
════════════════════════════════════════════ -->
<div class="ss-panel" id="tab-footer_social">
    <div class="ss-card">
        <h3>📱 "FOLLOW US" Section — Footer ke upar wala hissa</h3>
        <form method="POST">
            <input type="hidden" name="act" value="save_footer_social">
            <div class="ss-row" style="margin-bottom:14px;">
                <div>
                    <label class="ss-label">"FOLLOW US" Heading Text</label>
                    <input type="text" name="follow_heading" class="ss-input" value="<?= htmlspecialchars($follow_heading) ?>" placeholder="FOLLOW US">
                </div>
                <div>
                    <label class="ss-label">"JOIN OUR MAILING LIST" Heading Text</label>
                    <input type="text" name="mailing_heading" class="ss-input" value="<?= htmlspecialchars($mailing_heading) ?>" placeholder="JOIN OUR MAILING LIST">
                </div>
            </div>
            <div class="ss-row" style="margin-bottom:14px;">
                <div>
                    <label class="ss-label"><img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Instagram_logo_2016.svg" style="height:16px;vertical-align:middle;margin-right:4px;">Instagram URL</label>
                    <input type="text" name="instagram_url" class="ss-input" value="<?= htmlspecialchars($instagram_url) ?>" placeholder="https://www.instagram.com/livvraindia/">
                </div>
                <div>
                    <label class="ss-label"><img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" style="height:16px;vertical-align:middle;margin-right:4px;">Facebook URL</label>
                    <input type="text" name="facebook_url" class="ss-input" value="<?= htmlspecialchars($facebook_url) ?>" placeholder="https://www.facebook.com/livvra">
                </div>
            </div>
            <div class="ss-row" style="margin-bottom:14px;">
                <div>
                    <label class="ss-label"><img src="https://upload.wikimedia.org/wikipedia/commons/0/09/YouTube_full-color_icon_%282017%29.svg" style="height:16px;vertical-align:middle;margin-right:4px;">YouTube URL</label>
                    <input type="text" name="youtube_url" class="ss-input" value="<?= htmlspecialchars($youtube_url) ?>" placeholder="https://www.youtube.com/@LivvraIndia">
                </div>
                <div>
                    <label class="ss-label">🐦 Twitter / X URL (optional)</label>
                    <input type="text" name="twitter_url" class="ss-input" value="<?= htmlspecialchars($twitter_url) ?>" placeholder="https://twitter.com/livvra">
                </div>
            </div>
            <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Social Links</button>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════
     TAB 6 — CUSTOM CODE INJECTION
════════════════════════════════════════════ -->
<div class="ss-panel" id="tab-custom_code">
    <div class="ss-card" style="border-color:#f0ad4e;">
        <h3 style="color:#8a5700;">💻 Custom Code Injection</h3>
        <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#5d4037;line-height:1.6;">
            <strong>⚠️ Important:</strong> Yahan raw HTML/JS/CSS code paste karo — Google Search Console verification tag, Facebook Pixel, Google Ads, any tracking script, etc.<br>
            <strong>Galat code site todh sakta hai</strong> — sirf trusted scripts paste karo.
        </div>
        <form method="POST">
            <input type="hidden" name="act" value="save_custom_code">

            <div style="margin-bottom:22px;">
                <label class="ss-label" style="font-size:14px;display:flex;align-items:center;gap:8px;">
                    <span style="background:#2196F3;color:#fff;font-size:11px;padding:2px 8px;border-radius:4px;font-weight:700;">&lt;HEAD&gt;</span>
                    Head Tag ke andar (meta tags, Google Console, CSS links)
                </label>
                <p style="font-size:12px;color:#888;margin:4px 0 8px;">Example: Google Search Console verification, og:image, custom CSS</p>
                <textarea name="custom_code_head" class="ss-input" rows="6"
                    style="font-family:monospace;font-size:12px;background:#1e1e1e;color:#d4d4d4;border-color:#333;border-radius:6px;resize:vertical;"
                    placeholder='&lt;meta name="google-site-verification" content="XXXXX" /&gt;&#10;&lt;link rel="stylesheet" href="..." /&gt;'><?= htmlspecialchars($custom_code_head) ?></textarea>
            </div>

            <div style="margin-bottom:22px;">
                <label class="ss-label" style="font-size:14px;display:flex;align-items:center;gap:8px;">
                    <span style="background:#4CAF50;color:#fff;font-size:11px;padding:2px 8px;border-radius:4px;font-weight:700;">&lt;BODY&gt; Start</span>
                    Body tag ke bilkul baad (Google Tag Manager noscript, etc.)
                </label>
                <p style="font-size:12px;color:#888;margin:4px 0 8px;">Example: GTM noscript iframe, Facebook Pixel noscript</p>
                <textarea name="custom_code_body_start" class="ss-input" rows="5"
                    style="font-family:monospace;font-size:12px;background:#1e1e1e;color:#d4d4d4;border-color:#333;border-radius:6px;resize:vertical;"
                    placeholder='&lt;noscript&gt;&lt;iframe src="https://..."&gt;&lt;/iframe&gt;&lt;/noscript&gt;'><?= htmlspecialchars($custom_code_body_start) ?></textarea>
            </div>

            <div style="margin-bottom:22px;">
                <label class="ss-label" style="font-size:14px;display:flex;align-items:center;gap:8px;">
                    <span style="background:#9C27B0;color:#fff;font-size:11px;padding:2px 8px;border-radius:4px;font-weight:700;">&lt;/BODY&gt; End</span>
                    Body close hone se pehle (tracking scripts, analytics, ads pixels)
                </label>
                <p style="font-size:12px;color:#888;margin:4px 0 8px;">Example: Facebook Pixel, Google Ads conversion tracking, Clarity, Hotjar</p>
                <textarea name="custom_code_body_end" class="ss-input" rows="6"
                    style="font-family:monospace;font-size:12px;background:#1e1e1e;color:#d4d4d4;border-color:#333;border-radius:6px;resize:vertical;"
                    placeholder='&lt;script&gt;&#10;  // Facebook Pixel&#10;  !function(f,b,e,v...&#10;&lt;/script&gt;'><?= htmlspecialchars($custom_code_body_end) ?></textarea>
            </div>

            <button type="submit" class="save-btn" style="background:#e67e22;">
                <i class="fas fa-save"></i> Save Custom Code
            </button>
        </form>
    </div>

    <div class="ss-card" style="border-color:#e8e0f5;">
        <h3 style="color:#6a4c9c;">📋 Common Code Examples</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div style="background:#f8f5ff;border:1px solid #d4bfff;border-radius:8px;padding:14px;">
                <strong style="font-size:13px;color:#5e35b1;">Google Search Console</strong>
                <p style="font-size:12px;color:#555;margin:6px 0;">HEAD mein paste karo:</p>
                <code style="font-size:11px;background:#e8e0ff;padding:6px 8px;border-radius:4px;display:block;word-break:break-all;">&lt;meta name="google-site-verification" content="YOUR_CODE" /&gt;</code>
            </div>
            <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:14px;">
                <strong style="font-size:13px;color:#f57f17;">Google Ads Tracking</strong>
                <p style="font-size:12px;color:#555;margin:6px 0;">BODY END mein paste karo:</p>
                <code style="font-size:11px;background:#fff3e0;padding:6px 8px;border-radius:4px;display:block;word-break:break-all;">&lt;script async src="https://www.googletagmanager.com/gtag/js?id=AW-XXXXX"&gt;&lt;/script&gt;</code>
            </div>
            <div style="background:#e3f2fd;border:1px solid #90caf9;border-radius:8px;padding:14px;">
                <strong style="font-size:13px;color:#1565c0;">Facebook Pixel</strong>
                <p style="font-size:12px;color:#555;margin:6px 0;">BODY END mein paste karo:</p>
                <code style="font-size:11px;background:#e1f0fa;padding:6px 8px;border-radius:4px;display:block;word-break:break-all;">&lt;script&gt; !function(f,b,e,v,n,t,s) ... fbq('init', 'YOUR_PIXEL_ID'); &lt;/script&gt;</code>
            </div>
            <div style="background:#e8f5e9;border:1px solid #a5d6a7;border-radius:8px;padding:14px;">
                <strong style="font-size:13px;color:#2e7d32;">Microsoft Clarity / Hotjar</strong>
                <p style="font-size:12px;color:#555;margin:6px 0;">HEAD ya BODY END mein paste karo:</p>
                <code style="font-size:11px;background:#e0f2e9;padding:6px 8px;border-radius:4px;display:block;word-break:break-all;">&lt;script&gt; (function(c,l,a,r,i,t,y){ ... clarity("set","projectId","XXXX"); &lt;/script&gt;</code>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
function showTab(id) {
    document.querySelectorAll('.ss-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.ss-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    event.target.classList.add('active');
}

// Make repeaters sortable
['shopRep','otherRep','fshopRep','finfoRep'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) Sortable.create(el, { handle:'.rep-drag', animation:150 });
});

function addShopItem() {
    var html = '<div class="rep-item" draggable="true">' +
        '<span class="rep-drag">⠿</span>' +
        '<input type="text" name="shop_name[]" class="ss-input" placeholder="Item Name" style="max-width:180px;">' +
        '<input type="text" name="shop_url[]" class="ss-input" placeholder="/products.php" style="max-width:180px;">' +
        '<input type="text" name="shop_icon[]" class="ss-input" placeholder="leaf" style="max-width:100px;">' +
        '<input type="color" name="shop_bg[]" class="ss-input" value="#e9f0df" style="height:36px;max-width:50px;padding:3px;">' +
        '<input type="color" name="shop_icon_color[]" class="ss-input" value="#5d7234" style="height:36px;max-width:50px;padding:3px;">' +
        '<button type="button" class="rep-del" onclick="this.closest(\'.rep-item\').remove()">✕</button>' +
    '</div>';
    document.getElementById('shopRep').insertAdjacentHTML('beforeend', html);
}
function addOtherLink() {
    var html = '<div class="rep-item">' +
        '<span class="rep-drag">⠿</span>' +
        '<input type="text" name="link_name[]" class="ss-input" placeholder="Link Name">' +
        '<input type="text" name="link_url[]" class="ss-input" placeholder="/page.php">' +
        '<button type="button" class="rep-del" onclick="this.closest(\'.rep-item\').remove()">✕</button>' +
    '</div>';
    document.getElementById('otherRep').insertAdjacentHTML('beforeend', html);
}
function addFshop() {
    var html = '<div class="rep-item">' +
        '<span class="rep-drag">⠿</span>' +
        '<input type="text" name="fshop_name[]" class="ss-input" placeholder="SHOP ALL">' +
        '<input type="text" name="fshop_url[]" class="ss-input" placeholder="/products.php">' +
        '<button type="button" class="rep-del" onclick="this.closest(\'.rep-item\').remove()">✕</button>' +
    '</div>';
    document.getElementById('fshopRep').insertAdjacentHTML('beforeend', html);
}
function addFinfo() {
    var html = '<div class="rep-item">' +
        '<span class="rep-drag">⠿</span>' +
        '<input type="text" name="finfo_name[]" class="ss-input" placeholder="ABOUT US">' +
        '<input type="text" name="finfo_url[]" class="ss-input" placeholder="/about.php">' +
        '<button type="button" class="rep-del" onclick="this.closest(\'.rep-item\').remove()">✕</button>' +
    '</div>';
    document.getElementById('finfoRep').insertAdjacentHTML('beforeend', html);
}
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
