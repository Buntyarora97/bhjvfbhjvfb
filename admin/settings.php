<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/models/Setting.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Settings';
$currentPage = 'settings';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['settings']) && is_array($_POST['settings'])) {
        $saved = 0; $failed = 0;
        foreach ($_POST['settings'] as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if ($key === '') continue;
            if (Setting::set($key, (string)$value)) { $saved++; } else { $failed++; }
        }
        if ($failed === 0) {
            $success = "Settings saved successfully! ($saved fields updated)";
        } else {
            $error = "Saved $saved, failed $failed. Check error log.";
        }
    } else {
        $error = 'No settings submitted.';
    }
}

$settings = Setting::getAll();

require_once __DIR__ . '/views/layouts/header.php';
?>
<div class="admin-header">
    <h1>General Settings</h1>
</div>
<?php if ($success): ?><div class="badge badge-success" style="margin-bottom: 20px; display: block; padding:12px; background:#d4edda; color:#155724; border-radius:6px;"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="badge badge-danger" style="margin-bottom: 20px; display: block; padding:12px; background:#f8d7da; color:#721c24; border-radius:6px;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="admin-card">
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="settings[site_name]" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'LIVVRA'); ?>">
            </div>
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="settings[contact_email]" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Contact Phone</label>
                <input type="text" name="settings[contact_phone]" class="form-control" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Razorpay Key ID</label>
                <input type="text" name="settings[razorpay_key_id]" class="form-control" value="<?php echo htmlspecialchars($settings['razorpay_key_id'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Razorpay Key Secret</label>
                <input type="password" name="settings[razorpay_key_secret]" class="form-control" value="<?php echo htmlspecialchars($settings['razorpay_key_secret'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Shiprocket Email</label>
                <input type="text" name="settings[shiprocket_email]" class="form-control" value="<?php echo htmlspecialchars($settings['shiprocket_email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Shiprocket Password</label>
                <input type="password" name="settings[shiprocket_password]" class="form-control" value="<?php echo htmlspecialchars($settings['shiprocket_password'] ?? ''); ?>">
            </div>
        </div>

        <!-- SMS / OTP Settings -->
        <h3 style="margin: 30px 0 16px; font-size: 1rem; font-weight: 700; color: #0f3d2e; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
            📱 SMS / OTP Settings (COD Verification)
        </h3>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;margin-bottom:18px;font-size:13px;color:#166534;">
            <strong>Fast2SMS</strong> se COD order pe customer ke mobile pe OTP jayega.<br>
            Free account banao: <a href="https://www.fast2sms.com" target="_blank" style="color:#0f3d2e;font-weight:700;">fast2sms.com</a> → Login → Dev API → API Key copy karo → neeche paste karo.
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Fast2SMS API Key <span style="color:#ef4444;">*</span> <small style="color:#6b7280;font-weight:400;">(Mobile OTP ke liye)</small></label>
                <input type="text" name="settings[fast2sms_api_key]" class="form-control"
                       placeholder="Fast2SMS Dev API Key yahan daalein"
                       value="<?php echo htmlspecialchars($settings['fast2sms_api_key'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>MSG91 API Key <small style="color:#6b7280;font-weight:400;">(Optional backup)</small></label>
                <input type="text" name="settings[msg91_api_key]" class="form-control"
                       placeholder="MSG91 Auth Key (optional)"
                       value="<?php echo htmlspecialchars($settings['msg91_api_key'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>MSG91 OTP Template ID <small style="color:#6b7280;font-weight:400;">(Optional)</small></label>
                <input type="text" name="settings[msg91_otp_template_id]" class="form-control"
                       placeholder="MSG91 Template ID (optional)"
                       value="<?php echo htmlspecialchars($settings['msg91_otp_template_id'] ?? ''); ?>">
            </div>
        </div>

        <!-- Tracking / Analytics Settings -->
        <h3 style="margin: 30px 0 16px; font-size: 1rem; font-weight: 700; color: #0f3d2e; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
            📊 Tracking &amp; Analytics (GTM / GA4 / Meta Pixel)
        </h3>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 18px;margin-bottom:18px;font-size:13px;color:#1e40af;">
            Yahan apne tracking IDs daalo. Yeh sab pages pe automatically fire honge. GTM sabse powerful hai — usmein GA4 + Pixel dono manage kar sakte ho ek jagah se.
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>GTM Container ID <small style="color:#6b7280;font-weight:400;">(e.g. GTM-XXXXXX — sabse important, iske andar sab handle hota hai)</small></label>
                <input type="text" name="settings[gtm_id]" class="form-control"
                       placeholder="GTM-XXXXXX"
                       value="<?php echo htmlspecialchars($settings['gtm_id'] ?? 'GTM-5GSLXP4C'); ?>">
            </div>
            <div class="form-group">
                <label>GA4 Measurement ID <small style="color:#6b7280;font-weight:400;">(e.g. G-XXXXXXXXXX)</small></label>
                <input type="text" name="settings[ga4_id]" class="form-control"
                       placeholder="G-XXXXXXXXXX"
                       value="<?php echo htmlspecialchars($settings['ga4_id'] ?? 'G-FYB3NPJ83C'); ?>">
            </div>
            <div class="form-group">
                <label>Meta Pixel ID <small style="color:#6b7280;font-weight:400;">(e.g. 1234567890123456)</small></label>
                <input type="text" name="settings[meta_pixel_id]" class="form-control"
                       placeholder="Meta Pixel ID"
                       value="<?php echo htmlspecialchars($settings['meta_pixel_id'] ?? '1349826753146161'); ?>">
            </div>
        </div>

        <!-- EMI Settings -->
        <h3 style="margin: 30px 0 16px; font-size: 1rem; font-weight: 700; color: #0f3d2e; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
            💳 EMI Banner Settings (Product Detail Page)
        </h3>
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 18px;margin-bottom:18px;font-size:13px;color:#92400e;">
            <strong>EMI Banner</strong> product detail page pe price ke neeche dikhi jaati hai. Cashfree ke through Snapmint EMI offer kar sakte hain. Enable karo aur min amount set karo.
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>EMI Banner Enable करें</label>
                <select name="settings[emi_enabled]" class="form-control">
                    <option value="0" <?php echo (($settings['emi_enabled'] ?? '0') == '0') ? 'selected' : ''; ?>>Disable (Hidden)</option>
                    <option value="1" <?php echo (($settings['emi_enabled'] ?? '0') == '1') ? 'selected' : ''; ?>>Enable (Show on all products)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Minimum Amount for EMI (₹) <small style="color:#6b7280;">(e.g. 500 — products below this won't show EMI)</small></label>
                <input type="number" name="settings[emi_min_amount]" class="form-control" min="0" step="1"
                       value="<?php echo htmlspecialchars($settings['emi_min_amount'] ?? '500'); ?>">
            </div>
            <div class="form-group">
                <label>Down Payment % <small style="color:#6b7280;">(e.g. 20 = Pay 20% now via EMI)</small></label>
                <input type="number" name="settings[emi_down_payment_pct]" class="form-control" min="1" max="100" step="1"
                       value="<?php echo htmlspecialchars($settings['emi_down_payment_pct'] ?? '20'); ?>">
            </div>
            <div class="form-group">
                <label>EMI Partner Label <small style="color:#6b7280;">(e.g. Cashfree)</small></label>
                <input type="text" name="settings[emi_partner_label]" class="form-control"
                       value="<?php echo htmlspecialchars($settings['emi_partner_label'] ?? 'Cashfree'); ?>">
            </div>
            <div class="form-group">
                <label>EMI Sub Label <small style="color:#6b7280;">(e.g. Snapmint via Cashfree)</small></label>
                <input type="text" name="settings[emi_sub_label]" class="form-control"
                       value="<?php echo htmlspecialchars($settings['emi_sub_label'] ?? 'Snapmint via Cashfree'); ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Save Settings</button>
    </form>
</div>
<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
