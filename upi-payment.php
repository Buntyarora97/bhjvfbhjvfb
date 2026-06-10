<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/models/Order.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$orderId = $_GET['order_id'] ?? ($_SESSION['pending_order_id'] ?? 0);
if (!$orderId) {
    header('Location: checkout.php');
    exit;
}

$order = Order::getById($orderId);
if (!$order) {
    header('Location: checkout.php');
    exit;
}

$uploadError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utr = trim($_POST['utr_reference'] ?? '');
    $screenshotPath = '';

    // Handle screenshot upload
    if (!empty($_FILES['upi_screenshot']['tmp_name'])) {
        $file = $_FILES['upi_screenshot'];
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed)) {
            $uploadError = 'Only JPG, PNG, WebP images are allowed.';
        } elseif ($file['size'] > $maxSize) {
            $uploadError = 'File size must be under 5MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/upi_screenshots/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'upi_' . $orderId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $screenshotPath = 'uploads/upi_screenshots/' . $filename;
            } else {
                $uploadError = 'Failed to upload screenshot. Please try again.';
            }
        }
    }

    if (!$uploadError && (!empty($utr) || !empty($screenshotPath))) {
        $note = 'Manual UPI payment';
        if ($utr) $note .= ' | UTR: ' . $utr;
        if ($screenshotPath) $note .= ' | Screenshot: ' . $screenshotPath;

        Order::updatePaymentStatus($orderId, 'pending_verification', [
            'transaction_id' => $utr ?: ('screenshot_' . $orderId)
        ]);
        Order::updateStatus($orderId, 'processing');

        // Store screenshot path in session for admin reference if needed
        if ($screenshotPath) {
            $_SESSION['upi_screenshot_' . $orderId] = $screenshotPath;
        }

        unset($_SESSION['cart']);
        unset($_SESSION['pending_order_id']);
        header('Location: order-success.php?order_id=' . $orderId);
        exit;
    } elseif (!$uploadError) {
        $uploadError = 'Please enter a UTR number or upload a payment screenshot.';
    }
}

$pageTitle = 'UPI Payment';
require_once 'includes/header.php';

$amountToPay = $order['total_amount'];
$upiId       = defined('UPI_ID')          ? UPI_ID          : '9953835017@ybl';
$upiName     = defined('UPI_HOLDER_NAME') ? UPI_HOLDER_NAME : 'Livvra';
?>

<style>
.upi-pay-wrapper {
    min-height: 80vh;
    background: #f4f4f8;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 40px 16px 80px;
}
.upi-pay-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 520px;
    overflow: hidden;
}
.upi-pay-header {
    background: linear-gradient(90deg, #5B21B6, #7C3AED);
    color: white;
    padding: 28px 32px;
    text-align: center;
}
.upi-pay-header h2 { margin: 0 0 4px; font-size: 1.5rem; }
.upi-pay-header p { margin: 0; opacity: .85; font-size: 14px; }
.upi-pay-body { padding: 28px 32px; }

.upi-id-box {
    background: #f5f3ff;
    border: 2px solid #e9d5ff;
    border-radius: 14px;
    padding: 20px;
    text-align: center;
    margin-bottom: 20px;
}
.upi-id-label { font-size: 12px; color: #9ca3af; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .05em; }
.upi-id-value { font-size: 1.3rem; font-weight: 800; color: #6B21A8; letter-spacing: .04em; margin-bottom: 4px; }
.upi-copy-btn {
    background: none; border: 1px solid #a78bfa; color: #7C3AED;
    padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
    cursor: pointer; margin-top: 8px; transition: .2s;
}
.upi-copy-btn:hover { background: #7C3AED; color: #fff; }
.upi-holder { font-size: 14px; color: #6b7280; margin-top: 2px; }

.upi-amount {
    background: #ede9fe;
    border-radius: 12px;
    padding: 14px;
    text-align: center;
    font-size: 2rem;
    font-weight: 800;
    color: #5B21B6;
    margin-bottom: 24px;
    letter-spacing: .02em;
}
.upi-amount small { font-size: 1rem; font-weight: 600; }

.proof-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
.proof-tab {
    flex: 1; padding: 10px; text-align: center;
    border-radius: 10px; border: 1.5px solid #e5e7eb;
    font-size: 14px; font-weight: 600; cursor: pointer;
    transition: .2s; background: white; color: #6b7280;
}
.proof-tab.active { border-color: #7C3AED; background: #7C3AED; color: white; }

.proof-utr { display: block; }
.proof-screenshot { display: none; }

.form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #374151; }
.form-input {
    width: 100%; padding: 14px 16px; border: 1.5px solid #e5e7eb;
    border-radius: 10px; font-size: 1rem; font-weight: 700;
    text-align: center; letter-spacing: 2px; font-family: monospace;
    transition: .2s; box-sizing: border-box; outline: none;
}
.form-input:focus { border-color: #7C3AED; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }

.screenshot-area {
    border: 2px dashed #c4b5fd; border-radius: 12px;
    padding: 30px; text-align: center; cursor: pointer;
    transition: .2s; background: #faf5ff;
}
.screenshot-area:hover { border-color: #7C3AED; background: #f5f3ff; }
.screenshot-area input[type="file"] { display: none; }
.screenshot-preview { max-width: 100%; max-height: 200px; border-radius: 10px; margin-top: 12px; }

.error-box {
    background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
    padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;
    font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px;
}

.submit-btn {
    width: 100%; padding: 16px;
    background: linear-gradient(135deg, #5B21B6, #7C3AED);
    color: white; border: none; border-radius: 12px;
    font-size: 1rem; font-weight: 700; cursor: pointer;
    transition: .2s; margin-top: 20px;
    box-shadow: 0 4px 14px rgba(91,33,182,.3);
}
.submit-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(91,33,182,.4); }
.submit-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }

.security-note {
    text-align: center; font-size: 12px; color: #9ca3af;
    margin-top: 16px; display: flex; align-items: center; justify-content: center; gap: 5px;
}
</style>

<div class="upi-pay-wrapper">
    <div class="upi-pay-card">
        <div class="upi-pay-header">
            <h2>🔖 UPI Payment</h2>
            <p>Pay to the UPI ID below and submit your payment proof</p>
        </div>

        <div class="upi-pay-body">

            <?php if ($uploadError): ?>
                <div class="error-box">
                    ⚠ <?php echo htmlspecialchars($uploadError); ?>
                </div>
            <?php endif; ?>

            <div class="upi-id-box">
                <div class="upi-id-label">Pay to this UPI ID</div>
                <div class="upi-id-value" id="upiIdText"><?php echo htmlspecialchars($upiId); ?></div>
                <div class="upi-holder"><?php echo htmlspecialchars($upiName); ?></div>
                <button type="button" class="upi-copy-btn" onclick="copyUpiId()">📋 Copy UPI ID</button>
            </div>

            <div class="upi-amount">
                <small>₹</small><?php echo number_format($amountToPay, 2); ?>
            </div>

            <p style="text-align:center;font-size:13px;color:#6b7280;margin:0 0 20px;">
                After paying, share your payment proof below
            </p>

            <form method="POST" enctype="multipart/form-data">

                <div class="proof-tabs">
                    <button type="button" class="proof-tab active" onclick="switchTab('utr',this)">📝 Enter UTR / Ref ID</button>
                    <button type="button" class="proof-tab" onclick="switchTab('screenshot',this)">📷 Upload Screenshot</button>
                </div>

                <div class="proof-utr" id="proofUtr">
                    <label class="form-label">Transaction / UTR Reference Number</label>
                    <input type="text" name="utr_reference" class="form-input" id="utrInput"
                           placeholder="e.g. 123456789012"
                           value="<?php echo htmlspecialchars($_POST['utr_reference'] ?? ''); ?>">
                    <p style="font-size:12px;color:#9ca3af;text-align:center;margin-top:8px;">
                        Find it in your UPI app under payment history
                    </p>
                </div>

                <div class="proof-screenshot" id="proofScreenshot">
                    <div class="screenshot-area" onclick="document.getElementById('ssFile').click()">
                        <input type="file" name="upi_screenshot" id="ssFile" accept="image/*" onchange="previewSs(this)">
                        <div id="ssPlaceholder">
                            <div style="font-size:36px;margin-bottom:8px;">📷</div>
                            <div style="font-weight:600;color:#7C3AED;font-size:15px;">Click to upload screenshot</div>
                            <div style="font-size:12px;color:#9ca3af;margin-top:6px;">JPG, PNG or WebP · Max 5MB</div>
                        </div>
                        <img id="ssPreview" class="screenshot-preview" src="" alt="" style="display:none;">
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    ✅ Confirm Payment &amp; Place Order
                </button>

            </form>

            <div class="security-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Your payment info is secure and verified by our team
            </div>

        </div>
    </div>
</div>

<script>
function switchTab(tab, btn) {
    document.querySelectorAll('.proof-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('proofUtr').style.display       = tab === 'utr'        ? 'block' : 'none';
    document.getElementById('proofScreenshot').style.display = tab === 'screenshot' ? 'block' : 'none';
}

function previewSs(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('ssPreview');
            const placeholder = document.getElementById('ssPlaceholder');
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function copyUpiId() {
    const text = document.getElementById('upiIdText').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('.upi-copy-btn');
        btn.textContent = '✓ Copied!';
        btn.style.background = '#7C3AED';
        btn.style.color = '#fff';
        setTimeout(() => { btn.textContent = '📋 Copy UPI ID'; btn.style.background = ''; btn.style.color = ''; }, 2000);
    });
}

document.getElementById('submitBtn').addEventListener('click', function(e) {
    const utrVisible = document.getElementById('proofUtr').style.display !== 'none';
    const utr = document.getElementById('utrInput').value.trim();
    const ssFile = document.getElementById('ssFile').files.length;

    if (utrVisible && !utr) {
        e.preventDefault();
        document.getElementById('utrInput').style.borderColor = '#ef4444';
        document.getElementById('utrInput').placeholder = 'UTR number required!';
        setTimeout(() => {
            document.getElementById('utrInput').style.borderColor = '';
            document.getElementById('utrInput').placeholder = 'e.g. 123456789012';
        }, 2500);
        return;
    }
    if (!utrVisible && !ssFile) {
        e.preventDefault();
        alert('Please upload your payment screenshot.');
        return;
    }
    this.disabled = true;
    this.textContent = 'Submitting...';
});
</script>

<?php require_once 'includes/footer.php'; ?>
