<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/image_upload.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$currentPage = 'blogs';
$pageTitle = 'Add New Blog';
$mode = 'add';
$blog = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/_blog_save.php';
}

require_once __DIR__ . '/views/layouts/header.php';
require __DIR__ . '/_blog_form.php';
require_once __DIR__ . '/views/layouts/footer.php';
