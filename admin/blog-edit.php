<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/image_upload.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: blogs.php'); exit; }

$blog = Blog::getById($id);
if (!$blog) { header('Location: blogs.php?success=' . urlencode('Blog not found')); exit; }

$currentPage = 'blogs';
$pageTitle = 'Edit Blog';
$mode = 'edit';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/_blog_save.php';
    if (!$error) {
        $blog = Blog::getById($id);
    }
}

require_once __DIR__ . '/views/layouts/header.php';
require __DIR__ . '/_blog_form.php';
require_once __DIR__ . '/views/layouts/footer.php';
