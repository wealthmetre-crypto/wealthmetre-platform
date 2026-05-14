<?php
/**
 * WealthMetre — otp_test.php
 * DIAGNOSTIC TOOL: Tests every step of the OTP flow.
 * Upload to /public_html/api/partner/ → visit in browser
 * DELETE after debugging.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';

$mobile = preg_replace('/\D/', '', $_GET['mobile'] ?? '9999999999');
echo "WealthMetre OTP Diagnostic\n";
echo "===========================\n";
echo "Test mobile: {$mobile}\n\n";

// ── 1. CONFIG CHECK ──────────────────────────────────────
echo "1. CONFIG VALUES\n";
echo "   DB_HOST:                  " . DB_HOST . "\n";
echo "   DB_NAME:                  " . DB_NAME . "\n";
echo "   DB_USER:                  " . DB_USER . "\n";
echo "   DB_PASS:                  " . (DB_PASS ? str_repeat('*', strlen(DB_PASS)) . " (" . strlen(DB_PASS) . " chars)" : "EMPTY") . "\n";
echo "   MSG91_AUTH_KEY:           " . (defined('MSG91_AUTH_KEY') ? (MSG91_AUTH_KEY ? substr(MSG91_AUTH_KEY,0,8)."..." : "EMPTY") : "NOT DEFINED") . "\n";
echo "   MSG91_WHATSAPP_TEMPLATE:  " . (defined('MSG91_WHATSAPP_TEMPLATE') ? MSG91_WHATSAPP_TEMPLATE : "NOT DEFINED") . "\n";
echo "   MSG91_WA_FROM_NUMBER:     " . (defined('MSG91_WHATSAPP_FROM_NUMBER') ? MSG91_WHATSAPP_FROM_NUMBER : "NOT DEFINED") . "\n";
echo "   MSG91_SMS_TEMPLATE_ID:    " . (defined('MSG91_SMS_TEMPLATE_ID') ? MSG91_SMS_TEMPLATE_ID : "NOT DEFINED") . "\n";
echo "   MSG91_SMS_SENDER_ID:      " . (defined('MSG91_SMS_SENDER_ID') ? MSG91_SMS_SENDER_ID : "NOT DEFINED") . "\n";
echo "   DEBUG_MODE:               " . (DEBUG_MODE ? 'true' : 'false') . "\n\n";

// ── 2. DATABASE CONNECTION ───────────────────────────────
echo "2. DATABASE CONNECTION\n";
try {
    $pdo = getDB();
    echo "   ✅ DB connected\n";
} catch (\Exception $e) {
    echo "   ❌ DB FAILED: " . $e->getMessage() . "\n";
    echo "\n→ Cannot continue. Fix DB connection first.\n";
    exit;
}

// ── 3. PARTNER_OTPS TABLE ────────────────────────────────
echo "\n3. PARTNER_OTPS TABLE\n";
try {
    $cnt = $pdo->query("SELECT COUNT(*) FROM partner_otps")->fetchColumn();
    echo "   ✅ Table exists ({$cnt} rows)\n";
} catch (\Exception $e) {
    echo "   ❌ TABLE MISSING: " . $e->getMessage() . "\n";
    echo "   → Run this SQL in phpMyAdmin:\n\n";
    echo "   CREATE TABLE IF NOT EXISTS `partner_otps` (\n";
    echo "     `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n";
    echo "     `mobile`     VARCHAR(15) NOT NULL,\n";
    echo "     `otp`        VARCHAR(6)  NOT NULL,\n";
    echo "     `expires_at` TIMESTAMP   NOT NULL,\n";
    echo "     `used`       TINYINT(1)  DEFAULT 0,\n";
    echo "     `created_at` TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,\n";
    echo "     INDEX idx_mobile (`mobile`)\n";
    echo "   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
}

// ── 4. SAVE OTP TO DB ────────────────────────────────────
echo "\n4. OTP GENERATION & SAVE\n";
$otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
echo "   Generated OTP: {$otp}\n";
try {
    $pdo->prepare("DELETE FROM partner_otps WHERE mobile = ?")->execute([$mobile]);
    $pdo->prepare("INSERT INTO partner_otps (mobile, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))")
        ->execute([$mobile, $otp]);
    echo "   ✅ OTP saved to DB\n";
} catch (\Exception $e) {
    echo "   ❌ SAVE FAILED: " . $e->getMessage() . "\n";
}

// ── 5. VERIFY OTP FROM DB ────────────────────────────────
echo "\n5. OTP VERIFY FROM DB\n";
try {
    $stmt = $pdo->prepare("SELECT id FROM partner_otps WHERE mobile=? AND otp=? AND used=0 AND expires_at>NOW()");
    $stmt->execute([$mobile, $otp]);
    if ($stmt->fetch()) {
        echo "   ✅ OTP verify works correctly\n";
    } else {
        echo "   ❌ OTP verify failed (just saved it but can't find it)\n";
    }
} catch (\Exception $e) {
    echo "   ❌ VERIFY ERROR: " . $e->getMessage() . "\n";
}

// ── 6. WHATSAPP API TEST ─────────────────────────────────
echo "\n6. WHATSAPP API TEST\n";
if (!defined('MSG91_AUTH_KEY') || !MSG91_AUTH_KEY) {
    echo "   ❌ MSG91_AUTH_KEY not set\n";
} elseif (!defined('MSG91_WHATSAPP_FROM_NUMBER') || str_contains(MSG91_WHATSAPP_FROM_NUMBER, 'XXXX')) {
    echo "   ❌ MSG91_WHATSAPP_FROM_NUMBER not set properly\n";
} else {
    $payload = json_encode([
        'integrated_number' => MSG91_WHATSAPP_FROM_NUMBER,
        'content_type'      => 'template',
        'payload'           => [
            'messaging_product' => 'whatsapp',
            'type'              => 'template',
            'template'          => [
                'name'       => MSG91_WHATSAPP_TEMPLATE,
                'language'   => ['code' => 'en'],
                'components' => [[
                    'type'       => 'body',
                    'parameters' => [['type' => 'text', 'text' => $otp]],
                ]],
            ],
            'to' => '91' . $mobile,
        ],
    ]);

    $ch = curl_init('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'authkey: ' . MSG91_AUTH_KEY],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    echo "   HTTP Code:    {$code}\n";
    echo "   cURL Error:   " . ($err ?: 'none') . "\n";
    echo "   Raw Response: {$raw}\n";
    $j = json_decode($raw, true);
    $type = strtolower($j['type'] ?? '');
    if ($code >= 200 && $code < 300 && $type !== 'error') {
        echo "   ✅ WhatsApp API SUCCESS — OTP should arrive on WhatsApp\n";
    } else {
        echo "   ❌ WhatsApp API FAILED\n";
        echo "   Reason: " . ($j['message'] ?? $j['type'] ?? 'unknown') . "\n";
    }
}

// ── 7. SMS API TEST ──────────────────────────────────────
echo "\n7. SMS API TEST\n";
$tmpl = defined('MSG91_SMS_TEMPLATE_ID') ? MSG91_SMS_TEMPLATE_ID : '';
if (str_contains($tmpl, '0000000')) {
    echo "   ⚠️  Template ID '1707160000000000000' is a PLACEHOLDER\n";
    echo "   → This causes Error 418 (Template not found)\n";
    echo "   → Register OTP template at msg91.com → SMS → Templates\n";
    echo "   → Get real 18-digit DLT Template ID → update config.php\n";
} else {
    echo "   Template ID looks real: {$tmpl}\n";
    $url = 'https://api.msg91.com/api/v5/otp'
         . '?template_id=' . urlencode($tmpl)
         . '&mobile='      . urlencode('91' . $mobile)
         . '&authkey='     . urlencode(MSG91_AUTH_KEY)
         . '&otp='         . urlencode($otp)
         . '&sender='      . urlencode(MSG91_SMS_SENDER_ID ?? 'WLTHMT');
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_HTTPHEADER=>['authkey: '.MSG91_AUTH_KEY]]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "   HTTP Code:    {$code}\n";
    echo "   Response:     {$raw}\n";
}

// ── 8. PARTNERS TABLE ────────────────────────────────────
echo "\n8. PARTNERS TABLE\n";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM partners")->fetchAll(PDO::FETCH_COLUMN);
    $required = ['id','mobile','is_registered','user_id','password_hash','first_name','last_name'];
    foreach ($required as $c) {
        echo "   " . (in_array($c, $cols) ? "✅" : "❌ MISSING") . " {$c}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ partners table error: " . $e->getMessage() . "\n";
}

// ── SUMMARY ──────────────────────────────────────────────
echo "\n===========================\n";
echo "TEST OTP: {$otp} (stored in DB for mobile {$mobile})\n";
echo "You can verify this OTP at: POST /api/partner/partner_auth.php\n";
echo "Body: {\"action\":\"verify_otp\",\"mobile\":\"{$mobile}\",\"otp\":\"{$otp}\"}\n";
echo "\nDELETE this file after debugging!\n";