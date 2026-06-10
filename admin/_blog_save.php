<?php
/**
 * Shared save logic for blog-add.php and blog-edit.php.
 * Expects: $mode ('add'|'edit'), $id (for edit), $blog (for edit, may be reset).
 */

try {
    $title = trim($_POST['title'] ?? '');
    if ($title === '') throw new Exception('Title is required');

    // Slug
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') {
        $slug = Blog::generateSlug($title, $mode === 'edit' ? $id : null);
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug), '-'));
        $slug = Blog::generateSlug($slug, $mode === 'edit' ? $id : null);
    }

    // FAQs -> JSON
    $faqs = [];
    if (!empty($_POST['faq_q']) && is_array($_POST['faq_q'])) {
        $qs = $_POST['faq_q'];
        $as = $_POST['faq_a'] ?? [];
        foreach ($qs as $i => $q) {
            $q = trim($q);
            $a = trim($as[$i] ?? '');
            if ($q !== '' && $a !== '') {
                $faqs[] = ['q' => $q, 'a' => $a];
            }
        }
    }
    $faqJson = $faqs ? json_encode($faqs, JSON_UNESCAPED_UNICODE) : null;

    // Featured image upload
    $imageName = $_POST['existing_featured_image'] ?? ($blog['featured_image'] ?? null);
    if (!empty($_FILES['featured_image']['name']) && isset($_FILES['featured_image']['error']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/blogs/';
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new Exception('Could not create uploads/blogs/ folder. Please create it manually with permission 0775.');
            }
        }
        if (!is_writable($uploadDir)) {
            throw new Exception('uploads/blogs/ folder is not writable. Please set permission to 0775.');
        }

        $newBase = 'blog_' . uniqid();
        $saved = false;

        if (function_exists('saveUploadedMedia')) {
            $saved = saveUploadedMedia($_FILES['featured_image'], $uploadDir, $newBase);
        }

        if (!$saved) {
            // Fallback: plain upload with original extension
            $origExt = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($origExt, $allowed, true)) {
                throw new Exception('Image type not allowed. Use JPG, PNG, GIF, or WebP.');
            }
            $finalName = $newBase . '.' . $origExt;
            if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $uploadDir . $finalName)) {
                throw new Exception('Failed to save uploaded image. Please try again.');
            }
            $saved = $finalName;
        }
        $imageName = $saved;
    } elseif (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE => 'Image is larger than the server limit (php.ini).',
            UPLOAD_ERR_FORM_SIZE => 'Image is larger than the form limit.',
            UPLOAD_ERR_PARTIAL  => 'Image upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder missing. Contact hosting support.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the file. Check folder permissions.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by a PHP extension.',
        ];
        $code = $_FILES['featured_image']['error'];
        throw new Exception($errMap[$code] ?? 'Image upload failed (error code ' . $code . ').');
    }

    $status = $_POST['status'] ?? 'draft';
    if (!in_array($status, ['draft', 'published', 'scheduled'], true)) $status = 'draft';

    $publishDate = trim($_POST['publish_date'] ?? '');
    $updateDate  = trim($_POST['update_date'] ?? '');
    $scheduleDate = trim($_POST['schedule_date'] ?? '');

    if ($status === 'published' && $publishDate === '') {
        $publishDate = date('Y-m-d');
    }

    $data = [
        'title'              => $title,
        'slug'               => $slug,
        'excerpt'            => trim($_POST['excerpt'] ?? ''),
        'content'            => $_POST['content'] ?? '',
        'featured_image'     => $imageName,
        'featured_image_alt' => trim($_POST['featured_image_alt'] ?? ''),

        'meta_title'         => trim($_POST['meta_title'] ?? ''),
        'meta_description'   => trim($_POST['meta_description'] ?? ''),
        'primary_keyword'    => trim($_POST['primary_keyword'] ?? ''),
        'canonical_url'      => trim($_POST['canonical_url'] ?? ''),
        'robots'             => trim($_POST['robots'] ?? 'index,follow'),

        'faq_json'           => $faqJson,
        'featured_snippet'   => trim($_POST['featured_snippet'] ?? ''),

        'content_intent'     => trim($_POST['content_intent'] ?? ''),
        'target_audience'    => trim($_POST['target_audience'] ?? ''),
        'region'             => trim($_POST['region'] ?? ''),
        'language'           => trim($_POST['language'] ?? 'en'),
        'entity_tags'        => trim($_POST['entity_tags'] ?? ''),

        'author_name'        => trim($_POST['author_name'] ?? ''),
        'author_bio'         => trim($_POST['author_bio'] ?? ''),
        'publish_date'       => $publishDate ?: null,
        'update_date'        => $updateDate ?: null,

        'internal_links'     => trim($_POST['internal_links'] ?? ''),
        'external_links'     => trim($_POST['external_links'] ?? ''),

        'cta_text'           => trim($_POST['cta_text'] ?? ''),
        'cta_link'           => trim($_POST['cta_link'] ?? ''),

        'status'             => $status,
        'schedule_date'      => $scheduleDate ?: null,
    ];

    if ($mode === 'add') {
        $newId = Blog::create($data);
        header('Location: blogs.php?success=' . urlencode('Blog created successfully'));
        exit;
    } else {
        Blog::update($id, $data);
        header('Location: blog-edit.php?id=' . $id . '&saved=1');
        exit;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    // Repopulate form with submitted data
    $blog = array_merge($blog ?? [], $_POST);
}
