<?php
/**
 * AJAX endpoint: WebP Converter
 * Scans assets/ and uploads/, converts JPG/JPEG/PNG → WebP (GD), moves originals to unused_images_backup/
 */
@set_time_limit(300);          // 5 min per batch
@ini_set('memory_limit','256M');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

// Must be logged in as admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ─── SCAN: count images pending conversion ───────────────────────────────────
if ($action === 'scan') {
    $pending = scanPendingImages();
    echo json_encode([
        'total'   => count($pending),
        'files'   => array_slice($pending, 0, 5), // preview
        'message' => count($pending) . ' images ready to convert'
    ]);
    exit;
}

// ─── CONVERT BATCH: convert N images at a time ───────────────────────────────
if ($action === 'convert_batch') {
    $offset = (int)($_POST['offset'] ?? 0);
    $batchSize = 8;

    $pending = scanPendingImages();
    $batch   = array_slice($pending, $offset, $batchSize);

    $results = [];
    foreach ($batch as $srcPath) {
        $results[] = convertToWebP($srcPath);
    }

    $done  = $offset + count($batch);
    $total = count($pending);

    echo json_encode([
        'done'     => $done,
        'total'    => $total,
        'finished' => $done >= $total,
        'results'  => $results,
    ]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
exit;

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: Scan for images pending conversion
// ─────────────────────────────────────────────────────────────────────────────
function scanPendingImages(): array {
    $root    = dirname(__DIR__);
    $dirs    = ['assets', 'uploads'];
    $exts    = ['jpg', 'jpeg', 'png'];
    $skipDir = 'unused_images_backup';
    $files   = [];

    foreach ($dirs as $dir) {
        $dirPath = $root . '/' . $dir;
        if (!is_dir($dirPath)) continue;

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            if (!$file->isFile()) continue;
            $path = $file->getRealPath();

            // Skip backup folder
            if (strpos($path, DIRECTORY_SEPARATOR . $skipDir . DIRECTORY_SEPARATOR) !== false) continue;
            if (strpos($path, '/' . $skipDir . '/') !== false) continue;

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $exts)) continue;

            // Skip if WebP already exists at same path
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);
            if (file_exists($webpPath)) continue;

            $files[] = $path;
        }
    }

    return $files;
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: Convert single file to WebP, move original to unused_images_backup/
// ─────────────────────────────────────────────────────────────────────────────
function convertToWebP(string $srcPath): array {
    $root    = dirname(__DIR__);
    $backupBase = $root . '/unused_images_backup';

    // Destination WebP path (same location as source)
    $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $srcPath);

    // Backup path preserving directory structure
    $relative  = str_replace($root . '/', '', $srcPath);
    $backupPath = $backupBase . '/' . $relative;

    try {
        // Load image via GD
        $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
        $img = null;

        if ($ext === 'png') {
            $img = @imagecreatefrompng($srcPath);
            if ($img) {
                // Preserve transparency
                imagealphablending($img, false);
                imagesavealpha($img, true);
            }
        } elseif (in_array($ext, ['jpg', 'jpeg'])) {
            $img = @imagecreatefromjpeg($srcPath);
        }

        if (!$img) {
            return ['file' => basename($srcPath), 'status' => 'skip', 'reason' => 'Cannot read image'];
        }

        // Create WebP
        $webpDir = dirname($webpPath);
        if (!is_dir($webpDir)) mkdir($webpDir, 0755, true);

        $ok = imagewebp($img, $webpPath, 80);
        imagedestroy($img);

        if (!$ok || !file_exists($webpPath)) {
            return ['file' => basename($srcPath), 'status' => 'error', 'reason' => 'WebP write failed'];
        }

        // Move original to backup
        $backupDir = dirname($backupPath);
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

        // If backup already exists, delete it first
        if (file_exists($backupPath)) unlink($backupPath);

        if (rename($srcPath, $backupPath)) {
            $origSize = filesize($backupPath);
            $webpSize = filesize($webpPath);
            $saved    = $origSize > 0 ? round(($origSize - $webpSize) / $origSize * 100) : 0;
            return [
                'file'      => basename($srcPath),
                'status'    => 'ok',
                'orig_kb'   => round($origSize / 1024),
                'webp_kb'   => round($webpSize / 1024),
                'saved_pct' => $saved
            ];
        } else {
            // rename failed (cross-device) — try copy+unlink
            copy($srcPath, $backupPath);
            unlink($srcPath);
            return ['file' => basename($srcPath), 'status' => 'ok', 'note' => 'copied'];
        }

    } catch (Throwable $e) {
        return ['file' => basename($srcPath), 'status' => 'error', 'reason' => $e->getMessage()];
    }
}
