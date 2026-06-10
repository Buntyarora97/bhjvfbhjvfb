<?php
require_once 'includes/config.php';
require_once 'includes/models/Setting.php';

$pageTitle       = 'About Livvra | India\'s Trusted Premium Ayurvedic Brand';
$metaDescription = 'Learn about Livvra — India\'s most trusted ayurvedic wellness brand. We craft pure, natural herbal supplements backed by ancient ayurveda science for modern health.';
$metaKeywords    = 'about livvra, trusted ayurvedic brand india, herbal wellness company, best ayurvedic brand, livvra story, natural health products india';
$canonicalUrl    = 'https://livvra.in/about.php';

require_once 'includes/header.php';

// Load sections from DB
$sectionsRaw = Setting::get('about_page_sections', '');
$sections    = [];
if ($sectionsRaw) {
    $decoded = json_decode($sectionsRaw, true);
    if (is_array($decoded)) $sections = $decoded;
}

// Fallback to hardcoded if DB empty
if (empty($sections)) {
    $sections = [
        ['type' => 'image', 'content' => 'assets/images/about/1.jpeg', 'enabled' => true],
        ['type' => 'video', 'content' => 'assets/images/about/2.mp4',  'enabled' => true],
        ['type' => 'image', 'content' => 'assets/images/about/3.jpeg', 'enabled' => true],
        ['type' => 'image', 'content' => 'assets/images/about/4.jpeg', 'enabled' => true],
        ['type' => 'video', 'content' => 'assets/images/about/5.mp4',  'enabled' => true],
        ['type' => 'image', 'content' => 'assets/images/about/6.jpeg', 'enabled' => true],
    ];
}
?>

<style>
/* ===== ABOUT PAGE ===== */
.about-sections-wrap {
    width: 100%;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}
hr { display: none !important; }

/* Image / Video sections */
.about-sec-image,
.about-sec-video {
    width: 100%;
    overflow: hidden;
    line-height: 0;
}
.about-sec-image img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: contain;
}
.about-sec-video video {
    width: 100%;
    height: auto;
    display: block;
    object-fit: contain;
}

/* Rich Text section */
.about-sec-text {
    width: 100%;
    padding: 56px 20px;
    background: linear-gradient(135deg, #f4fdf0 0%, #eaf7f2 60%, #f0faf5 100%);
    position: relative;
    overflow: hidden;
}
.about-sec-text::before {
    content: '';
    position: absolute;
    top: -60px; left: -60px;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(118,163,58,0.10) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.about-text-inner {
    max-width: 860px;
    margin: 0 auto;
    text-align: center;
}
.about-text-title {
    font-size: 28px;
    font-weight: 800;
    color: #1f3e35;
    margin-bottom: 16px;
    font-family: Arial, Helvetica, sans-serif;
}
.about-text-title::after {
    content: '';
    display: block;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #76a33a, #bcded0);
    border-radius: 3px;
    margin: 12px auto 0;
}
.about-text-body {
    background: #fff;
    border-radius: 16px;
    padding: 36px 44px;
    box-shadow: 0 6px 32px rgba(31,62,53,0.08);
    border: 1px solid rgba(118,163,58,0.12);
    text-align: left;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 15.5px;
    line-height: 1.8;
    color: #333;
    position: relative;
}
.about-text-body::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #76a33a, #1f3e35, #76a33a);
    border-radius: 16px 16px 0 0;
}
.about-text-body h1 { font-size:26px; font-weight:800; color:#1f3e35; margin-bottom:12px; }
.about-text-body h2 { font-size:22px; font-weight:700; color:#1f3e35; margin-bottom:10px; }
.about-text-body h3 { font-size:18px; font-weight:700; color:#76a33a; margin-bottom:8px; }
.about-text-body p  { margin-bottom:12px; }
.about-text-body strong { color:#1f3e35; }
.about-text-body ul, .about-text-body ol { padding-left:24px; margin-bottom:12px; }
.about-text-body ul li, .about-text-body ol li { margin-bottom:5px; }
.about-text-body a  { color:#76a33a; font-weight:600; }

/* Banner section */
.about-sec-banner {
    width: 100%;
    padding: 60px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.about-sec-banner::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 300px; height: 300px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
    pointer-events: none;
}
.banner-inner {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}
.banner-title {
    font-size: 34px;
    font-weight: 900;
    letter-spacing: 0.5px;
    margin-bottom: 16px;
    font-family: Arial, Helvetica, sans-serif;
    line-height: 1.2;
}
.banner-body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 16px;
    line-height: 1.8;
    opacity: 0.92;
}
.banner-body p { margin-bottom: 10px; }
.banner-divider {
    width: 60px;
    height: 3px;
    background: rgba(255,255,255,0.5);
    border-radius: 3px;
    margin: 0 auto 24px;
}

@media (max-width: 768px) {
    .about-sec-text { padding: 36px 16px; }
    .about-text-body { padding: 24px 18px; }
    .about-text-title { font-size: 22px; }
    .about-sec-banner { padding: 40px 16px; }
    .banner-title { font-size: 24px; }
}
</style>

<div class="about-sections-wrap">
<?php foreach ($sections as $sec):
    if (!($sec['enabled'] ?? true)) continue;
    $type    = $sec['type'] ?? 'image';
    $content = $sec['content'] ?? '';
    $title   = $sec['title'] ?? '';
    $bg      = $sec['bg_color'] ?? '#1f3e35';
    $tc      = $sec['text_color'] ?? '#ffffff';
?>

    <?php if ($type === 'image' && $content): ?>
        <div class="about-sec-image">
            <img src="<?= htmlspecialchars($content) ?>" alt="<?= htmlspecialchars($title ?: 'About LIVVRA') ?>">
        </div>

    <?php elseif ($type === 'video' && $content): ?>
        <div class="about-sec-video">
            <video src="<?= htmlspecialchars($content) ?>" autoplay muted loop playsinline></video>
        </div>

    <?php elseif ($type === 'text' && !empty(trim(strip_tags($content)))): ?>
        <div class="about-sec-text">
            <div class="about-text-inner">
                <?php if ($title): ?>
                    <div class="about-text-title"><?= htmlspecialchars($title) ?></div>
                <?php endif; ?>
                <div class="about-text-body">
                    <?= $content ?>
                </div>
            </div>
        </div>

    <?php elseif ($type === 'banner'): ?>
        <div class="about-sec-banner" style="background:<?= htmlspecialchars($bg) ?>;color:<?= htmlspecialchars($tc) ?>;">
            <div class="banner-inner">
                <?php if ($title): ?>
                    <div class="banner-title"><?= htmlspecialchars($title) ?></div>
                    <div class="banner-divider"></div>
                <?php endif; ?>
                <?php if (!empty(trim(strip_tags($content)))): ?>
                    <div class="banner-body"><?= $content ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

<?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
