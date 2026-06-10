<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle   = 'Image Optimizer';
$currentPage = 'image-optimizer';
require_once __DIR__ . '/views/layouts/header.php';
?>

<style>
.opt-card {
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,.07);
    margin-bottom: 24px;
    max-width: 860px;
}
.opt-title {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}
.opt-sub {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 28px;
    line-height: 1.6;
}
.opt-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.opt-stat {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
}
.opt-stat .num {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    display: block;
    line-height: 1;
    margin-bottom: 4px;
}
.opt-stat .lbl {
    font-size: 11px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 600;
}
.opt-progress-wrap {
    background: #f1f5f9;
    border-radius: 999px;
    height: 14px;
    overflow: hidden;
    margin-bottom: 8px;
}
.opt-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #22c55e, #16a34a);
    border-radius: 999px;
    width: 0%;
    transition: width .35s ease;
}
.opt-progress-label {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 20px;
    min-height: 20px;
}
.btn-convert {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 14px 36px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: opacity .2s, transform .15s;
    box-shadow: 0 4px 16px rgba(34,197,94,.3);
}
.btn-convert:hover:not(:disabled) { transform: translateY(-2px); opacity: .92; }
.btn-convert:disabled { opacity: .5; cursor: not-allowed; transform: none; }
.btn-scan {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-right: 12px;
}
.btn-scan:hover { background: #e2e8f0; }

.log-box {
    background: #0f172a;
    color: #94a3b8;
    border-radius: 12px;
    padding: 20px;
    font-family: monospace;
    font-size: 12px;
    line-height: 1.7;
    max-height: 300px;
    overflow-y: auto;
    display: none;
    margin-top: 20px;
}
.log-ok   { color: #4ade80; }
.log-skip { color: #fb923c; }
.log-err  { color: #f87171; }

.info-box {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 18px 20px;
    font-size: 13px;
    color: #166534;
    margin-bottom: 24px;
    line-height: 1.7;
}
.warn-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 18px 20px;
    font-size: 13px;
    color: #92400e;
    margin-bottom: 24px;
    line-height: 1.7;
}
.done-banner {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    border-radius: 12px;
    padding: 20px 24px;
    font-size: 16px;
    font-weight: 700;
    margin-top: 20px;
    display: none;
    align-items: center;
    gap: 12px;
}
</style>

<div class="admin-header">
    <h1>🖼️ Image Optimizer</h1>
</div>

<div class="opt-card">
    <div class="opt-title">WebP Converter — One Click, All Images</div>
    <p class="opt-sub">
        Scans <code>assets/</code> and <code>uploads/</code> for JPG / JPEG / PNG files.<br>
        Converts each to <strong>WebP (80% quality)</strong> — saving 40–70% file size on average.<br>
        Original files are safely moved to <code>unused_images_backup/</code> — nothing is deleted.<br>
        Site images won't break: the server automatically serves WebP via <code>.htaccess</code> rewrite rules.
    </p>

    <div class="info-box">
        ✅ <strong>Safe to run:</strong> Originals move to <code>unused_images_backup/</code> — you can download and delete that folder anytime.<br>
        ✅ <strong>No images will go missing</strong> — .htaccess already rewrites all image requests to WebP.<br>
        ✅ <strong>Already converted images are skipped</strong> — safe to run multiple times.
    </div>

    <div class="opt-stats">
        <div class="opt-stat">
            <span class="num" id="statPending">—</span>
            <span class="lbl">Images to Convert</span>
        </div>
        <div class="opt-stat">
            <span class="num" id="statDone" style="color:#22c55e;">0</span>
            <span class="lbl">Converted</span>
        </div>
        <div class="opt-stat">
            <span class="num" id="statErrors" style="color:#ef4444;">0</span>
            <span class="lbl">Errors</span>
        </div>
        <div class="opt-stat">
            <span class="num" id="statSaved">—</span>
            <span class="lbl">Avg Size Saved</span>
        </div>
    </div>

    <div class="opt-progress-wrap">
        <div class="opt-progress-bar" id="progressBar"></div>
    </div>
    <div class="opt-progress-label" id="progressLabel">Click "Scan" to count images, then "Convert All"</div>

    <div>
        <button class="btn-scan" id="btnScan" onclick="doScan()">
            <i class="fas fa-search"></i> Scan Images
        </button>
        <button class="btn-convert" id="btnConvert" onclick="doConvert()" disabled>
            <i class="fas fa-bolt"></i> Convert All to WebP
        </button>
    </div>

    <div class="done-banner" id="doneBanner">
        🎉 All done! Images converted. You can now delete <code style="background:rgba(255,255,255,.2);padding:2px 6px;border-radius:4px;">unused_images_backup/</code> from Hostinger to free up space.
    </div>

    <div class="log-box" id="logBox"></div>
</div>

<div class="opt-card" style="background:#f8fafc;">
    <div style="font-size:16px;font-weight:700;margin-bottom:12px;">How It Works</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;font-size:13px;color:#475569;">
        <div style="background:#fff;border-radius:12px;padding:16px;border:1px solid #e2e8f0;">
            <div style="font-size:24px;margin-bottom:8px;">1️⃣</div>
            <strong>Scan</strong><br>Finds all JPG/PNG in assets/ and uploads/ that don't yet have a WebP version.
        </div>
        <div style="background:#fff;border-radius:12px;padding:16px;border:1px solid #e2e8f0;">
            <div style="font-size:24px;margin-bottom:8px;">2️⃣</div>
            <strong>Convert</strong><br>Each image is converted to WebP (quality 80) using PHP GD — no external service needed.
        </div>
        <div style="background:#fff;border-radius:12px;padding:16px;border:1px solid #e2e8f0;">
            <div style="font-size:24px;margin-bottom:8px;">3️⃣</div>
            <strong>Backup</strong><br>Original JPG/PNG moves to unused_images_backup/ — preserving folder structure. Nothing deleted.
        </div>
        <div style="background:#fff;border-radius:12px;padding:16px;border:1px solid #e2e8f0;">
            <div style="font-size:24px;margin-bottom:8px;">4️⃣</div>
            <strong>Auto-Serve</strong><br>.htaccess rewrites all image requests to WebP automatically — site looks identical.
        </div>
    </div>
</div>

<script>
let totalImages   = 0;
let doneCount     = 0;
let errorCount    = 0;
let savedPcts     = [];
let isRunning     = false;
const BATCH_SIZE  = 8;

function logLine(msg, cls) {
    const box = document.getElementById('logBox');
    box.style.display = 'block';
    const line = document.createElement('div');
    if (cls) line.className = cls;
    line.textContent = msg;
    box.appendChild(line);
    box.scrollTop = box.scrollHeight;
}

async function doScan() {
    document.getElementById('btnScan').disabled = true;
    document.getElementById('progressLabel').textContent = 'Scanning...';
    try {
        const res  = await fetch('../ajax/webp-convert.php?action=scan');
        const data = await res.json();
        totalImages = data.total;
        document.getElementById('statPending').textContent = totalImages;
        document.getElementById('progressLabel').textContent = totalImages + ' image(s) found — ready to convert';
        document.getElementById('btnConvert').disabled = (totalImages === 0);
        if (totalImages === 0) {
            document.getElementById('progressLabel').textContent = '✅ All images are already WebP! Nothing to do.';
        }
    } catch (e) {
        document.getElementById('progressLabel').textContent = 'Scan failed: ' + e.message;
    }
    document.getElementById('btnScan').disabled = false;
}

async function doConvert() {
    if (isRunning) return;
    isRunning = true;
    doneCount = 0;
    errorCount = 0;
    savedPcts = [];
    document.getElementById('btnConvert').disabled = true;
    document.getElementById('btnScan').disabled = true;
    document.getElementById('doneBanner').style.display = 'none';
    document.getElementById('logBox').innerHTML = '';
    document.getElementById('logBox').style.display = 'block';

    let offset = 0;
    let finished = false;

    while (!finished) {
        try {
            const fd = new FormData();
            fd.append('action', 'convert_batch');
            fd.append('offset', offset);

            const res  = await fetch('../ajax/webp-convert.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.error) { logLine('❌ Server error: ' + data.error, 'log-err'); break; }

            // Process results
            (data.results || []).forEach(r => {
                if (r.status === 'ok') {
                    doneCount++;
                    if (r.saved_pct !== undefined) savedPcts.push(r.saved_pct);
                    logLine('✓ ' + r.file + ' — ' + (r.orig_kb||'?') + 'KB → ' + (r.webp_kb||'?') + 'KB (saved ' + (r.saved_pct||0) + '%)', 'log-ok');
                } else if (r.status === 'skip') {
                    logLine('⟳ ' + r.file + ' — skipped (' + (r.reason||'') + ')', 'log-skip');
                } else {
                    errorCount++;
                    logLine('✗ ' + r.file + ' — ' + (r.reason||'error'), 'log-err');
                }
            });

            // Update UI
            const total = data.total || totalImages;
            const pct   = total > 0 ? Math.min(100, Math.round(data.done / total * 100)) : 0;
            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('progressLabel').textContent = 'Converting... ' + data.done + ' / ' + total + ' (' + pct + '%)';
            document.getElementById('statDone').textContent = doneCount;
            document.getElementById('statErrors').textContent = errorCount;
            if (savedPcts.length > 0) {
                const avg = Math.round(savedPcts.reduce((a,b) => a+b, 0) / savedPcts.length);
                document.getElementById('statSaved').textContent = avg + '%';
            }

            finished = data.finished;
            offset   = data.done;

            if (finished) {
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('progressLabel').textContent = '✅ Done! ' + doneCount + ' converted, ' + errorCount + ' errors.';
                document.getElementById('statPending').textContent = 0;
                const banner = document.getElementById('doneBanner');
                banner.style.display = 'flex';
                logLine('─── Completed! ───', '');
            }
        } catch (e) {
            logLine('❌ Fetch error: ' + e.message, 'log-err');
            break;
        }
    }

    isRunning = false;
    document.getElementById('btnConvert').disabled = true;
    document.getElementById('btnScan').disabled = false;
}

// Auto-scan on page load
window.addEventListener('DOMContentLoaded', doScan);
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
