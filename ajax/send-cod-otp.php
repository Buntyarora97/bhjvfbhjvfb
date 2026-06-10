<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$phone = trim($_POST['phone'] ?? '');
$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');

if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Kripya sahi 10-digit mobile number dalein.']);
    exit;
}

// Generate 6-digit OTP
$otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

// Store in session (10 minutes)
$_SESSION['cod_otp'] = [
    'otp'     => $otp,
    'phone'   => $phone,
    'expires' => time() + 600,
];

error_log("COD OTP [{$phone}]: {$otp}");

$delivery_channel = [];

// ── 1. WhatsApp OTP via Fast2SMS ──────────────────────────────────
try {
    $fast2sms_key = class_exists('Setting') ? Setting::get('fast2sms_api_key', '') : '';

    if (!empty($fast2sms_key)) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://www.fast2sms.com/dev/bulkV2',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'authorization' => $fast2sms_key,
                'route'         => 'whatsapp',
                'message'       => "Your LIVVRA order OTP is *{$otp}*. Valid for 10 minutes. Do NOT share this with anyone. - LIVVRA Team",
                'numbers'       => $phone,
            ]),
            CURLOPT_HTTPHEADER => ['cache-control: no-cache'],
        ]);
        $resp = curl_exec($curl);
        $err  = curl_error($curl);
        curl_close($curl);

        error_log("Fast2SMS WhatsApp [{$phone}] resp: {$resp} err: {$err}");

        if ($resp && !$err) {
            $data = json_decode($resp, true);
            if (!empty($data['return'])) {
                $delivery_channel[] = 'whatsapp';
            } else {
                error_log("Fast2SMS WhatsApp failed: " . $resp);
            }
        }
    }
} catch (Exception $e) {
    error_log('Fast2SMS WhatsApp error: ' . $e->getMessage());
}

// ── 2. Email OTP — always send ────────────────────────────────────
if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $subj = 'LIVVRA — Your OTP for Cash on Delivery Order';
    $body = "Namaste {$name},\n\n"
          . "Aapka LIVVRA COD order verify karne ke liye OTP:\n\n"
          . "  ==========================\n"
          . "         OTP:  {$otp}\n"
          . "  ==========================\n\n"
          . "Yeh OTP 10 minute tak valid hai.\n"
          . "Kisi ke saath share MAT karein.\n\n"
          . "Dhanyawad LIVVRA choose karne ke liye!\n"
          . "— LIVVRA Team | www.livvra.in";

    $headers  = "From: LIVVRA <noreply@livvra.in>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (@mail($email, $subj, $body, $headers)) {
        $delivery_channel[] = 'email';
    } else {
        error_log("Email OTP failed for: {$email}");
    }
}

// ── Response ──────────────────────────────────────────────────────
$hasWA    = in_array('whatsapp', $delivery_channel);
$hasEmail = in_array('email',    $delivery_channel);

if ($hasWA && $hasEmail) {
    $note = 'OTP aapke WhatsApp aur email dono pe bhej diya gaya hai.';
} elseif ($hasWA) {
    $note = 'OTP aapke WhatsApp pe bhej diya gaya hai.';
} elseif ($hasEmail) {
    $note = 'OTP aapki email pe bhej diya gaya hai.';
} else {
    $note = 'OTP generate ho gaya hai. Email check karein.';
}

echo json_encode([
    'success'     => true,
    'message'     => $note,
    'phone_last4' => substr($phone, -4),
    'expires_in'  => 600,
    'channels'    => $delivery_channel,
]);
