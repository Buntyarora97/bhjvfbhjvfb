<?php
require_once __DIR__ . '/includes/config.php';

$slug = $_GET['slug'] ?? '';
$slug = trim($slug, '/ ');

if ($slug === '') {
    header('Location: /blog.php');
    exit;
}

$blog = Blog::getBySlug($slug);

if (!$blog || ($blog['status'] !== 'published' && empty($_SESSION['admin_id']))) {
    http_response_code(404);
    $pageTitle = 'Blog not found';
    $metaDescription = 'The requested blog post was not found.';
    $header_path = __DIR__ . '/includes/header.php';
    if (file_exists($header_path)) include $header_path;
    echo '<div style="max-width:700px; margin:80px auto; padding:40px; text-align:center;"><h1>404 — Blog not found</h1><p>The post you are looking for does not exist or has been removed.</p><p><a href="/blog.php" style="color:#27ae60;">← Back to all blogs</a></p></div>';
    $footer_path = __DIR__ . '/includes/footer.php';
    if (file_exists($footer_path)) include $footer_path;
    exit;
}

// Track view
Blog::incrementViews($blog['id']);

// SEO meta values for header.php
$pageTitle       = $blog['meta_title'] ?: $blog['title'];
$metaDescription = $blog['meta_description'] ?: $blog['excerpt'];
$metaKeywords    = $blog['primary_keyword'] ?? '';
$canonicalUrl    = $blog['canonical_url'] ?: ('https://' . ($_SERVER['HTTP_HOST'] ?? 'livvra.in') . '/blog/' . $blog['slug']);
$robotsTag       = $blog['robots'] ?: 'index,follow';

$ogImage = !empty($blog['featured_image']) ? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'livvra.in') . '/uploads/blogs/' . $blog['featured_image']) : '';

$faqs = Blog::decodeFaqs($blog['faq_json'] ?? '');
$internalLinks = Blog::decodeLinks($blog['internal_links'] ?? '');
$externalLinks = Blog::decodeLinks($blog['external_links'] ?? '');

$header_path = __DIR__ . '/includes/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    include 'header.php';
}
?>
<!-- Per-page SEO overrides (in case header.php doesn't print them) -->
<meta name="robots" content="<?php echo htmlspecialchars($robotsTag); ?>">
<?php if ($ogImage): ?>
<meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">
<?php endif; ?>
<meta property="og:type" content="article">
<meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($metaDescription ?? ''); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
<?php if (!empty($blog['author_name'])): ?>
<meta name="author" content="<?php echo htmlspecialchars($blog['author_name']); ?>">
<?php endif; ?>

<style>
.lv-post-wrap { max-width: 860px; margin: 0 auto; padding: 40px 20px 80px; color:#2C3E50; font-family:'Segoe UI', Tahoma, Verdana, sans-serif; }
.lv-breadcrumb { font-size:0.85rem; color:#888; margin-bottom:16px; }
.lv-breadcrumb a { color:#6B8E23; text-decoration:none; }
.lv-post-title { font-size:2.4rem; line-height:1.2; margin:8px 0 14px; color:#2C3E50; font-weight:700; }
.lv-post-meta { display:flex; flex-wrap:wrap; gap:18px; color:#666; font-size:0.9rem; margin-bottom:28px; padding-bottom:20px; border-bottom:1px solid #eee; }
.lv-post-meta span { display:inline-flex; align-items:center; gap:6px; }
.lv-excerpt { font-size:1.15rem; line-height:1.65; color:#4a5568; font-weight:500; margin:0 0 28px; padding:18px 22px; background:#f9f9f5; border-radius:10px; border-left:4px solid #C9A227; }
.lv-featured-img { width:100%; max-height:480px; object-fit:cover; border-radius:14px; margin-bottom:30px; box-shadow:0 8px 24px rgba(0,0,0,0.08);}
.lv-featured-snippet { background:#f5f9ed; border-left:4px solid #6B8E23; padding:18px 22px; border-radius:8px; margin-bottom:28px; font-style:italic; color:#3a4a1f;}
.lv-content { font-size:1.05rem; line-height:1.8; color:#333; }
.lv-content h2 { color:#556B2F; margin-top:40px; font-size:1.7rem; }
.lv-content h3 { color:#6B8E23; margin-top:28px; font-size:1.35rem; }
.lv-content p { margin: 1em 0; }
.lv-content ul, .lv-content ol { padding-left:1.5em; margin:1em 0; }
.lv-content img { max-width:100%; height:auto; border-radius:10px; margin:18px 0; }
.lv-content a { color:#27ae60; text-decoration:underline; }
.lv-content blockquote { border-left:4px solid #FFD700; padding:12px 18px; margin:20px 0; background:#FDF6E3; border-radius:6px; }
.lv-cta { margin-top:40px; padding:30px; text-align:center; background:linear-gradient(135deg,#556B2F,#6B8E23); color:#fff; border-radius:14px; }
.lv-cta a { display:inline-block; background:#FFD700; color:#2C3E50; padding:14px 32px; border-radius:30px; text-decoration:none; font-weight:700; margin-top:14px; }
.lv-faq { margin-top:50px; }
.lv-faq h2 { color:#556B2F; }
.lv-faq-item { background:#fff; border:1px solid #eee; border-radius:8px; margin-bottom:12px; overflow:hidden; }
.lv-faq-q { padding:16px 20px; font-weight:600; color:#2C3E50; cursor:pointer; display:flex; justify-content:space-between; align-items:center; }
.lv-faq-q::after { content:"+"; font-size:1.4rem; color:#6B8E23; transition:transform 0.2s; }
.lv-faq-item.open .lv-faq-q::after { transform:rotate(45deg); }
.lv-faq-a { padding:0 20px; max-height:0; overflow:hidden; transition:max-height 0.25s ease, padding 0.25s ease; color:#444; }
.lv-faq-item.open .lv-faq-a { padding:0 20px 18px; max-height:600px; }
.lv-author { background:#FDF6E3; padding:22px; border-radius:10px; margin-top:40px; display:flex; gap:18px; align-items:flex-start; }
.lv-author .avatar { width:60px; height:60px; border-radius:50%; background:#6B8E23; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.6rem; font-weight:700; flex-shrink:0;}
.lv-author h4 { margin:0 0 6px; color:#2C3E50;}
.lv-author p { margin:0; color:#555; font-size:0.95rem; line-height:1.6;}
.lv-link-section { margin-top:40px; }
.lv-link-section h3 { color:#556B2F; font-size:1.1rem; margin-bottom:10px;}
.lv-link-section ul { list-style:disc; padding-left:1.4em; margin:0; }
.lv-tags { margin-top:30px; display:flex; flex-wrap:wrap; gap:8px; }
.lv-tag { background:#eef5e0; color:#556B2F; padding:6px 14px; border-radius:20px; font-size:0.82rem; }
@media (max-width:600px) { .lv-post-title { font-size:1.7rem; } .lv-content { font-size:1rem; } .lv-featured-img { border-radius:10px; }}
</style>

<article class="lv-post-wrap">
    <nav class="lv-breadcrumb">
        <a href="/">Home</a> &nbsp;›&nbsp; <a href="/blog.php">Blog</a> &nbsp;›&nbsp; <span><?php echo htmlspecialchars($blog['title']); ?></span>
    </nav>

    <h1 class="lv-post-title"><?php echo htmlspecialchars($blog['title']); ?></h1>

    <div class="lv-post-meta">
        <?php if (!empty($blog['author_name'])): ?>
            <span>✍️ <?php echo htmlspecialchars($blog['author_name']); ?></span>
        <?php endif; ?>
        <?php if (!empty($blog['publish_date'])): ?>
            <span>📅 Published: <?php echo date('d M Y', strtotime($blog['publish_date'])); ?></span>
        <?php endif; ?>
        <?php if (!empty($blog['update_date'])): ?>
            <span>🔄 Updated: <?php echo date('d M Y', strtotime($blog['update_date'])); ?></span>
        <?php endif; ?>
        <?php if (!empty($blog['region'])): ?>
            <span>📍 <?php echo htmlspecialchars($blog['region']); ?></span>
        <?php endif; ?>
        <?php if (!empty($blog['target_audience'])): ?>
            <span>👥 <?php echo htmlspecialchars($blog['target_audience']); ?></span>
        <?php endif; ?>
    </div>

    <?php if (!empty($blog['excerpt'])): ?>
        <p class="lv-excerpt"><?php echo nl2br(htmlspecialchars($blog['excerpt'])); ?></p>
    <?php endif; ?>

    <?php if (!empty($blog['featured_image'])): ?>
        <img src="/uploads/blogs/<?php echo htmlspecialchars($blog['featured_image']); ?>"
             alt="<?php echo htmlspecialchars($blog['featured_image_alt'] ?: $blog['title']); ?>"
             class="lv-featured-img" loading="eager">
    <?php endif; ?>

    <?php if (!empty($blog['featured_snippet'])): ?>
        <div class="lv-featured-snippet">
            <strong>Quick Answer:</strong> <?php echo htmlspecialchars($blog['featured_snippet']); ?>
        </div>
    <?php endif; ?>

    <div class="lv-content">
        <?php
        $content = $blog['content'] ?? '';
        // If content has no HTML tags, convert plain newlines to <br>
        if ($content !== '' && strip_tags($content) === $content) {
            echo nl2br(htmlspecialchars($content));
        } else {
            echo $content;
        }
        ?>
    </div>

    <?php if (!empty($internalLinks)): ?>
        <div class="lv-link-section">
            <h3>Related Reads on Our Site</h3>
            <ul>
                <?php foreach ($internalLinks as $l): ?>
                    <li><a href="<?php echo htmlspecialchars($l['url']); ?>"><?php echo htmlspecialchars($l['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($externalLinks)): ?>
        <div class="lv-link-section">
            <h3>References</h3>
            <ul>
                <?php foreach ($externalLinks as $l): ?>
                    <li><a href="<?php echo htmlspecialchars($l['url']); ?>" target="_blank" rel="noopener nofollow"><?php echo htmlspecialchars($l['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($blog['cta_text']) && !empty($blog['cta_link'])): ?>
        <div class="lv-cta">
            <h3 style="margin:0 0 6px; color:#fff;">Take the Next Step</h3>
            <a href="<?php echo htmlspecialchars($blog['cta_link']); ?>"><?php echo htmlspecialchars($blog['cta_text']); ?> →</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($faqs)): ?>
        <section class="lv-faq">
            <h2>Frequently Asked Questions</h2>
            <?php foreach ($faqs as $f): ?>
                <div class="lv-faq-item">
                    <div class="lv-faq-q" onclick="this.parentNode.classList.toggle('open')"><?php echo htmlspecialchars($f['q']); ?></div>
                    <div class="lv-faq-a"><?php echo nl2br(htmlspecialchars($f['a'])); ?></div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($blog['author_name']) || !empty($blog['author_bio'])): ?>
        <div class="lv-author">
            <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($blog['author_name'] ?? 'A', 0, 1))); ?></div>
            <div>
                <h4><?php echo htmlspecialchars($blog['author_name'] ?? 'Author'); ?></h4>
                <p><?php echo nl2br(htmlspecialchars($blog['author_bio'] ?? '')); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($blog['entity_tags'])): ?>
        <div class="lv-tags">
            <?php foreach (array_filter(array_map('trim', explode(',', $blog['entity_tags']))) as $tag): ?>
                <span class="lv-tag">#<?php echo htmlspecialchars($tag); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</article>

<!-- JSON-LD: Article schema -->
<script type="application/ld+json">
<?php
$entityArr = !empty($blog['entity_tags'])
    ? array_filter(array_map('trim', explode(',', $blog['entity_tags'])))
    : [];

$ld = [
    "@context" => "https://schema.org",
    "@type" => "BlogPosting",
    "headline" => $blog['title'],
    "description" => $metaDescription,
    "image" => $ogImage ?: null,
    "author" => $blog['author_name'] ? [
        "@type" => "Person",
        "name"  => $blog['author_name'],
        "description" => $blog['author_bio'] ?? null,
    ] : null,
    "datePublished" => $blog['publish_date'] ?? null,
    "dateModified"  => $blog['update_date'] ?? $blog['publish_date'] ?? null,
    "mainEntityOfPage" => ["@type" => "WebPage", "@id" => $canonicalUrl],
    "publisher" => [
        "@type" => "Organization",
        "name" => defined('SITE_NAME') ? SITE_NAME : 'LIVVRA',
    ],
    "keywords" => $blog['primary_keyword'] ?? null,
    // GEO signals
    "inLanguage" => $blog['language'] ?? 'en',
    "contentLocation" => !empty($blog['region']) ? [
        "@type" => "Place", "name" => $blog['region']
    ] : null,
    "audience" => !empty($blog['target_audience']) ? [
        "@type" => "Audience", "audienceType" => $blog['target_audience']
    ] : null,
    "about" => $entityArr ? array_map(function($t){
        return ["@type" => "Thing", "name" => $t];
    }, array_values($entityArr)) : null,
];
$ld = array_filter($ld, function($v){ return $v !== null && $v !== ''; });
echo json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
</script>

<?php if (!empty($faqs)): ?>
<!-- JSON-LD: FAQ schema -->
<script type="application/ld+json">
<?php
$faqLd = [
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => array_map(function($f){
        return [
            "@type" => "Question",
            "name"  => $f['q'],
            "acceptedAnswer" => ["@type" => "Answer", "text" => $f['a']]
        ];
    }, $faqs)
];
echo json_encode($faqLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
</script>
<?php endif; ?>

<?php
$footer_path = __DIR__ . '/includes/footer.php';
if (file_exists($footer_path)) include $footer_path;
?>
