<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$pageTitle = 'Home Page Management';
require_once 'views/layouts/header.php';
?>
<div class="admin-header">
    <h1><i class="fas fa-home"></i> Home Page Management</h1>
    <p style="color:#666;margin-top:4px;">Edit all sections of your home page from here.</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">

    <a href="home-banners.php" style="text-decoration:none;">
        <div class="admin-card" style="cursor:pointer;transition:all 0.2s;border:2px solid transparent;" onmouseover="this.style.borderColor='#4A7C59'" onmouseout="this.style.borderColor='transparent'">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#0f3d2e,#1a5f4a);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-images" style="color:#fff;font-size:22px;"></i>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;color:#1f3d2b;">A. Hero Banner</h3>
                    <p style="margin:0;color:#666;font-size:14px;">Upload desktop & mobile banner images for the top slider</p>
                </div>
                <i class="fas fa-chevron-right" style="color:#ccc;margin-left:auto;"></i>
            </div>
        </div>
    </a>

    <a href="trust-section.php" style="text-decoration:none;">
        <div class="admin-card" style="cursor:pointer;transition:all 0.2s;border:2px solid transparent;" onmouseover="this.style.borderColor='#4A7C59'" onmouseout="this.style.borderColor='transparent'">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#0f3d2e,#1a5f4a);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-leaf" style="color:#fff;font-size:22px;"></i>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;color:#1f3d2b;">B. Ayurveda Trust Section</h3>
                    <p style="margin:0;color:#666;font-size:14px;">Edit headline, stats, badges, and iPhone frame video</p>
                </div>
                <i class="fas fa-chevron-right" style="color:#ccc;margin-left:auto;"></i>
            </div>
        </div>
    </a>

    <a href="google-reviews.php" style="text-decoration:none;">
        <div class="admin-card" style="cursor:pointer;transition:all 0.2s;border:2px solid transparent;" onmouseover="this.style.borderColor='#4A7C59'" onmouseout="this.style.borderColor='transparent'">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#0f3d2e,#1a5f4a);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fab fa-google" style="color:#fff;font-size:22px;"></i>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;color:#1f3d2b;">C. Google Reviews</h3>
                    <p style="margin:0;color:#666;font-size:14px;">Add, edit and manage customer reviews shown in the marquee</p>
                </div>
                <i class="fas fa-chevron-right" style="color:#ccc;margin-left:auto;"></i>
            </div>
        </div>
    </a>

    <a href="faqs.php" style="text-decoration:none;">
        <div class="admin-card" style="cursor:pointer;transition:all 0.2s;border:2px solid transparent;" onmouseover="this.style.borderColor='#4A7C59'" onmouseout="this.style.borderColor='transparent'">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#0f3d2e,#1a5f4a);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-question-circle" style="color:#fff;font-size:22px;"></i>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;color:#1f3d2b;">D. FAQ Section</h3>
                    <p style="margin:0;color:#666;font-size:14px;">Add, edit and reorder frequently asked questions</p>
                </div>
                <i class="fas fa-chevron-right" style="color:#ccc;margin-left:auto;"></i>
            </div>
        </div>
    </a>

    <a href="unlock-section.php" style="text-decoration:none;grid-column:span 2;">
        <div class="admin-card" style="cursor:pointer;transition:all 0.2s;border:2px solid transparent;" onmouseover="this.style.borderColor='#4A7C59'" onmouseout="this.style.borderColor='transparent'">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#0f3d2e,#1a5f4a);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-gift" style="color:#fff;font-size:22px;"></i>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;color:#1f3d2b;">E. Unlock Offers & Subscribe</h3>
                    <p style="margin:0;color:#666;font-size:14px;">Change background image, title, links and social media URLs for the bottom section</p>
                </div>
                <i class="fas fa-chevron-right" style="color:#ccc;margin-left:auto;"></i>
            </div>
        </div>
    </a>

</div>

<?php require_once 'views/layouts/footer.php'; ?>
