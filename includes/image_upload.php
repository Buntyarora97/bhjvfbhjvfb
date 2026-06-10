<?php
/**
 * Shared image upload helper.
 *
 * Use saveUploadedMedia() instead of move_uploaded_file() in admin handlers.
 *
 * Behaviour:
 *  - For JPG/JPEG/PNG/GIF/WEBP -> converts to WEBP at the same target path
 *    (filename ka extension .webp ho jata hai). Quality default 80.
 *  - For videos/PDFs/etc. -> normal move_uploaded_file (no change).
 *
 * Returns the saved filename (only basename, not full path) on success, or
 * false on failure. The returned filename is what should be stored in the DB,
 * so DB rows pointing to the file always use the actual saved name.
 */

if (!function_exists('saveUploadedMedia')) {

    function _ensureUploadDir(string $dir): bool {
        if (is_dir($dir)) return true;
        return @mkdir($dir, 0775, true);
    }

    function _isImageMime(string $mime, string $ext): bool {
        $mime = strtolower($mime);
        $ext  = strtolower($ext);
        $imgMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $imgExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        return in_array($mime, $imgMimes, true) || in_array($ext, $imgExts, true);
    }

    /**
     * Convert an image file (JPG/PNG/GIF/WEBP) to WEBP using GD.
     * $srcPath  - source file (e.g. tmp upload)
     * $destPath - target .webp file
     * Returns true on success.
     */
    function convertToWebpFile(string $srcPath, string $destPath, int $quality = 80): bool {
        if (!function_exists('imagewebp')) {
            return false;
        }

        $info = @getimagesize($srcPath);
        if (!$info) {
            // Maybe it's already webp; try imagecreatefromwebp
            $img = @imagecreatefromwebp($srcPath);
        } else {
            switch ($info[2]) {
                case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg($srcPath); break;
                case IMAGETYPE_PNG:  $img = @imagecreatefrompng($srcPath);  break;
                case IMAGETYPE_GIF:  $img = @imagecreatefromgif($srcPath);  break;
                case IMAGETYPE_WEBP: $img = @imagecreatefromwebp($srcPath); break;
                default: $img = false;
            }
        }
        if (!$img) return false;

        // Preserve transparency for PNG/WEBP/GIF
        imagepalettetotruecolor($img);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        if (!_ensureUploadDir(dirname($destPath))) {
            imagedestroy($img);
            return false;
        }

        $ok = @imagewebp($img, $destPath, $quality);
        imagedestroy($img);
        return $ok && file_exists($destPath);
    }

    /**
     * Save an uploaded $_FILES entry to $targetDir.
     * Images are converted to WEBP, videos/others kept as-is.
     *
     * $fileEntry - one $_FILES['x'] array (name, type, tmp_name, error)
     * $targetDir - destination directory (with or without trailing slash)
     * $baseName  - desired filename WITHOUT extension (e.g. "prod_123_main")
     *
     * Returns saved filename (basename) on success, false on failure.
     */
    function saveUploadedMedia(array $fileEntry, string $targetDir, string $baseName, int $quality = 80) {
        if (!isset($fileEntry['tmp_name']) || $fileEntry['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $targetDir = rtrim($targetDir, '/\\') . '/';
        if (!_ensureUploadDir($targetDir)) return false;

        $origName = $fileEntry['name'] ?? '';
        $origExt  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $mime     = $fileEntry['type'] ?? '';

        // Sanitize base name
        $safeBase = preg_replace('/[^A-Za-z0-9._-]+/', '_', $baseName);
        if ($safeBase === '' || $safeBase === '_') {
            $safeBase = 'file_' . time();
        }

        if (_isImageMime($mime, $origExt)) {
            // Convert to webp
            $finalName = $safeBase . '.webp';
            $finalPath = $targetDir . $finalName;
            // Avoid overwriting: if exists, append suffix
            $i = 1;
            while (file_exists($finalPath)) {
                $finalName = $safeBase . '_' . $i . '.webp';
                $finalPath = $targetDir . $finalName;
                $i++;
            }

            // First move tmp to a working file (move_uploaded_file required for security)
            $workTmp = $targetDir . '._tmp_' . uniqid() . '.' . ($origExt ?: 'img');
            if (!move_uploaded_file($fileEntry['tmp_name'], $workTmp)) {
                return false;
            }
            $ok = convertToWebpFile($workTmp, $finalPath, $quality);
            @unlink($workTmp);
            if (!$ok) return false;
            return $finalName;
        }

        // Non-image (videos, pdfs etc.) -> save as-is
        $finalName = $safeBase . ($origExt ? '.' . $origExt : '');
        $finalPath = $targetDir . $finalName;
        $i = 1;
        while (file_exists($finalPath)) {
            $finalName = $safeBase . '_' . $i . ($origExt ? '.' . $origExt : '');
            $finalPath = $targetDir . $finalName;
            $i++;
        }
        if (!move_uploaded_file($fileEntry['tmp_name'], $finalPath)) {
            return false;
        }
        return $finalName;
    }
}
