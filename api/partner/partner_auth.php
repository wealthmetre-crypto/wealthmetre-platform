<?php
/**
 * WealthMetre – partner_auth.php  v3.2
 * FIX: WhatsApp plain text OTP (no template needed) + SMS fallback
 */

require_once __DIR__ . '/../config.php';

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $body   = getJsonBody();
    $action = sanitize($body['action'] ?? $_GET['action'] ?? '');

    match ($action) {
        'send_otp'       => actionSendOtp($body),
        'verify_otp'     => actionVerifyOtp($body),
        'register'       => actionRegister($body),
        'login_password' => actionLoginPassword($body),
        'forgot_start'   => actionForgotStart($body),
        'forgot_verify'  => actionForgotVerify($body),
        'reset_password' => actionResetPassword($body),
        'logout'         => actionLogout(),
        'me'             => actionMe(),
        default          => jsonError('Invalid action', 400),
    };
}

// ─────────────────────────────────────────────────────────
// SEND OTP
// ─────────────────────────────────────────────────────────
function actionSendOtp(array $b): void {
    $mobile = normMobile($b['mobile'] ?? '');
    if (!$mobile) jsonError('Enter a valid 10-digit mobile number');

    $pdo = getDB();
    $otp = generateOtp();
    saveOtpLocal($pdo, $mobile, $otp);

    $delivery = sendViaMSG91($mobile, $otp);

    $resp = [
        'status'  => 'ok',
        'message' => 'OTP sent to ' . maskMobile($mobile),
        'channel' => $delivery['channel'],
    ];
    if (DEBUG_MODE) {
        $resp['_otp']      = $otp;
        $resp['_delivery'] = $delivery;
    }
    jsonResponse($resp);
}

// ─────────────────────────────────────────────────────────
// VERIFY OTP
// ─────────────────────────────────────────────────────────
function actionVerifyOtp(array $b): void {
    $mobile = normMobile($b['mobile'] ?? '');
    $otp    = preg_replace('/\D/', '', (string)($b['otp'] ?? ''));

    if (!$mobile)           jsonError('Invalid mobile');
    if (strlen($otp) !== 6) jsonError('Enter 6-digit OTP');

    $pdo = getDB();
    verifyOtpLocal($pdo, $mobile, $otp);

    $stmt = $pdo->prepare("SELECT id, is_registered, status FROM partners WHERE mobile = ?");
    $stmt->execute([$mobile]);
    $partner = $stmt->fetch();

    if ($partner && (int)$partner['is_registered'] === 1 && ($partner['status'] ?? 'active') === 'active') {
        $token = createSession($pdo, (int)$partner['id']);
        updateLastLogin($pdo, (int)$partner['id']);
        logAct(null, (int)$partner['id'], 'login_otp', 'OTP login');

        jsonResponse([
            'status'  => 'ok',
            'flow'    => 'login_success',
            'token'   => $token,
            'partner' => loadPartnerPublic($pdo, (int)$partner['id']),
        ]);
    }

    jsonResponse([
        'status'    => 'ok',
        'flow'      => 'needs_registration',
        'mobile'    => $mobile,
        'otp_token' => encryptOtpToken($mobile),
    ]);
}

// ─────────────────────────────────────────────────────────
// REGISTER
// ─────────────────────────────────────────────────────────
function actionRegister(array $b): void {
    $mobile    = normMobile($b['mobile'] ?? '');
    $otpToken  = sanitize($b['otp_token'] ?? '');
    $firstName = trim(sanitize($b['first_name'] ?? ''));
    $lastName  = trim(sanitize($b['last_name'] ?? ''));
    $password  = $b['password'] ?? '';
    $confirm   = $b['confirm_password'] ?? '';

    if (!$mobile) jsonError('Invalid mobile');

    if (!verifyOtpToken($otpToken, $mobile)) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("
            SELECT id FROM partner_otps
            WHERE mobile = ? AND used = 1
            AND expires_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$mobile]);
        if (!$stmt->fetch()) jsonError('Session expired. Please request a new OTP.');
    }

    if (!$firstName)            jsonError('First name is required');
    if (!$lastName)             jsonError('Last name is required');
    if (strlen($password) < 6)  jsonError('Password must be at least 6 characters');
    if ($password !== $confirm) jsonError('Passwords do not match');

    $pdo          = getDB();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $fullName     = $firstName . ' ' . $lastName;

    $stmt = $pdo->prepare("SELECT id, is_registered FROM partners WHERE mobile = ?");
    $stmt->execute([$mobile]);
    $existing = $stmt->fetch();

    if ($existing && (int)$existing['is_registered'] === 1) {
        jsonError('Already registered. Please use login instead.');
    }

    $userId = generateUserId($pdo);

    if ($existing) {
        $pdo->prepare("
            UPDATE partners
            SET first_name=?, last_name=?, partner_name=?, password_hash=?, is_registered=1,
                login_method='both', user_id=?, updated_at=NOW()
            WHERE id=?
        ")->execute([$firstName, $lastName, $fullName, $passwordHash, $userId, $existing['id']]);
        $partnerId = (int)$existing['id'];
    } else {
        $partnerCode = 'WM-' . strtoupper(substr($mobile, -4)) . '-' . rand(100, 999);
        $pdo->prepare("
            INSERT INTO partners
            (partner_name, first_name, last_name, mobile, user_id, partner_code,
             password_hash, is_registered, login_method, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'both', 'active')
        ")->execute([$fullName, $firstName, $lastName, $mobile, $userId, $partnerCode, $passwordHash]);
        $partnerId = (int)$pdo->lastInsertId();
    }

    $token = createSession($pdo, $partnerId);
    updateLastLogin($pdo, $partnerId);
    logAct(null, $partnerId, 'registered', "New partner: {$fullName}");

    jsonResponse([
        'status'  => 'ok',
        'flow'    => 'registered',
        'user_id' => $userId,
        'token'   => $token,
        'partner' => loadPartnerPublic($pdo, $partnerId),
        'message' => "Welcome {$firstName}! Your Partner ID is {$userId}. Please save it.",
    ]);
}

// ─────────────────────────────────────────────────────────
// LOGIN WITH PASSWORD
// ─────────────────────────────────────────────────────────
function actionLoginPassword(array $b): void {
    $userId   = strtoupper(trim($b['user_id'] ?? ''));
    $password = $b['password'] ?? '';

    if (!$userId)   jsonError('Enter your User ID');
    if (!$password) jsonError('Enter your password');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM partners WHERE user_id=? AND is_registered=1 AND status='active'");
    $stmt->execute([$userId]);
    $partner = $stmt->fetch();

    if (!$partner)                                              jsonError('User ID not found');
    if (!$partner['password_hash'])                             jsonError('Password not set. Use OTP login first.');
    if (!password_verify($password, $partner['password_hash'])) jsonError('Incorrect password');

    $token = createSession($pdo, (int)$partner['id']);
    updateLastLogin($pdo, (int)$partner['id']);
    logAct(null, (int)$partner['id'], 'login_password', 'Credential login');

    jsonResponse([
        'status'  => 'ok',
        'flow'    => 'login_success',
        'token'   => $token,
        'partner' => loadPartnerPublic($pdo, (int)$partner['id']),
    ]);
}

// ─────────────────────────────────────────────────────────
// FORGOT PASSWORD
// ─────────────────────────────────────────────────────────
function actionForgotStart(array $b): void {
    $mobile = normMobile($b['mobile'] ?? '');
    if (!$mobile) jsonError('Enter registered mobile number');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id FROM partners WHERE mobile=? AND is_registered=1 AND status='active'");
    $stmt->execute([$mobile]);
    if (!$stmt->fetch()) jsonError('Mobile not registered. Please sign up first.');

    $otp      = generateOtp();
    saveOtpLocal($pdo, $mobile, $otp);
    $delivery = sendViaMSG91($mobile, $otp);

    $resp = ['status' => 'ok', 'message' => 'OTP sent to ' . maskMobile($mobile), 'channel' => $delivery['channel']];
    if (DEBUG_MODE) { $resp['_otp'] = $otp; $resp['_delivery'] = $delivery; }
    jsonResponse($resp);
}

function actionForgotVerify(array $b): void {
    $mobile = normMobile($b['mobile'] ?? '');
    $otp    = preg_replace('/\D/', '', (string)($b['otp'] ?? ''));
    if (!$mobile)           jsonError('Invalid mobile');
    if (strlen($otp) !== 6) jsonError('Enter 6-digit OTP');
    $pdo = getDB();
    verifyOtpLocal($pdo, $mobile, $otp);
    jsonResponse(['status' => 'ok', 'flow' => 'reset_password', 'otp_token' => encryptOtpToken($mobile)]);
}

function actionResetPassword(array $b): void {
    $mobile   = normMobile($b['mobile'] ?? '');
    $otpToken = sanitize($b['otp_token'] ?? '');
    $password = $b['password'] ?? '';
    $confirm  = $b['confirm_password'] ?? '';
    if (!$mobile)                            jsonError('Invalid mobile');
    if (!verifyOtpToken($otpToken, $mobile)) jsonError('Session expired. Start again.');
    if (strlen($password) < 6)               jsonError('Password must be at least 6 characters');
    if ($password !== $confirm)              jsonError('Passwords do not match');

    $pdo  = getDB();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE partners SET password_hash=?, login_method='both', updated_at=NOW() WHERE mobile=?")->execute([$hash, $mobile]);
    $stmt = $pdo->prepare("SELECT id FROM partners WHERE mobile=?");
    $stmt->execute([$mobile]);
    $p = $stmt->fetch();
    if (!$p) jsonError('Partner not found');
    $token = createSession($pdo, (int)$p['id']);
    updateLastLogin($pdo, (int)$p['id']);
    logAct(null, (int)$p['id'], 'password_reset', 'Password reset');
    jsonResponse(['status' => 'ok', 'flow' => 'login_success', 'token' => $token,
                  'partner' => loadPartnerPublic($pdo, (int)$p['id']), 'message' => 'Password updated. You are now logged in.']);
}

// ─────────────────────────────────────────────────────────
// LOGOUT / ME
// ─────────────────────────────────────────────────────────
function actionLogout(): void {
    $t = getBearerToken();
    if ($t) getDB()->prepare("UPDATE partner_sessions SET is_active=0, logout_time=NOW() WHERE token=?")->execute([$t]);
    jsonResponse(['status' => 'ok']);
}

function actionMe(): void {
    jsonResponse(['status' => 'ok', 'partner' => requireAuth()]);
}

// ─────────────────────────────────────────────────────────
// AUTH MIDDLEWARE
// ─────────────────────────────────────────────────────────
function requireAuth(): array {
    $token = getBearerToken();
    if (!$token) jsonError('Unauthorised', 401);
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT p.id, p.partner_name, p.first_name, p.last_name, p.mobile, p.email,
               p.company_name, p.branch_city, p.partner_code, p.user_id,
               p.is_registered, p.status, ps.session_id
        FROM partner_sessions ps
        JOIN partners p ON ps.partner_id = p.id
        WHERE ps.token = ? AND ps.is_active = 1 AND p.status = 'active'
    ");
    $stmt->execute([$token]);
    $p = $stmt->fetch();
    if (!$p) jsonError('Session expired. Please login again.', 401);
    return $p;
}

function getBearerToken(): ?string {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) return trim($m[1]);
    return null;
}

// ─────────────────────────────────────────────────────────
// LOCAL OTP
// ─────────────────────────────────────────────────────────
function generateOtp(): string {
    return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

function saveOtpLocal(\PDO $pdo, string $mobile, string $otp): void {
    $pdo->prepare("DELETE FROM partner_otps WHERE mobile = ?")->execute([$mobile]);
    $pdo->prepare("INSERT INTO partner_otps (mobile, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))")
        ->execute([$mobile, $otp]);
}

function verifyOtpLocal(\PDO $pdo, string $mobile, string $otp): void {
    $stmt = $pdo->prepare("SELECT id FROM partner_otps WHERE mobile=? AND otp=? AND used=0 AND expires_at>NOW()");
    $stmt->execute([$mobile, $otp]);
    if (!$stmt->fetch()) jsonError('Invalid or expired OTP. Please request a new one.');
    $pdo->prepare("UPDATE partner_otps SET used=1 WHERE mobile=? AND otp=?")->execute([$mobile, $otp]);
}

// ─────────────────────────────────────────────────────────
// MSG91 DELIVERY — v3.2 FIX
// Plain text WhatsApp (no template approval needed)
// Falls back to SMS if WhatsApp fails
// ─────────────────────────────────────────────────────────
function sendViaMSG91(string $mobile, string $otp): array {
    if (DEBUG_MODE) {
        error_log("[OTP DEBUG] mobile={$mobile} otp={$otp}");
        return ['channel' => 'debug', 'success' => true, 'note' => "OTP={$otp}"];
    }
    if (!defined('MSG91_AUTH_KEY') || !MSG91_AUTH_KEY || str_contains(MSG91_AUTH_KEY, 'YOUR_')) {
        return ['channel' => 'not_configured', 'success' => false, 'error' => 'MSG91 auth key not set'];
    }
    return sendWhatsAppOtp($mobile, $otp);
}

function sendWhatsAppOtp(string $mobile, string $otp): array {
    $from = defined('MSG91_WHATSAPP_FROM_NUMBER') ? MSG91_WHATSAPP_FROM_NUMBER : '';
    if (!$from) {
        error_log('[WA-OTP] FROM number not configured');
        return ['channel' => 'whatsapp', 'success' => false, 'error' => 'From number not set'];
    }

    // ── Plain text — NO template approval required ────────
    $message = "🔐 *WealthMetre OTP*\n\nYour login OTP is: *{$otp}*\n\nValid for 10 minutes. Do not share.\n\n— WealthMetre Team";

    $payload = json_encode([
        'integrated_number' => $from,
        'content_type'      => 'text',
        'payload'           => [
            'to'   => '91' . $mobile,
            'type' => 'text',
            'text' => ['body' => $message],
        ],
    ]);

    $ch = curl_init('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'authkey: ' . MSG91_AUTH_KEY,
        ],
    ]);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("[WA-OTP] cURL error: {$curlErr}");
        return sendSmsFallback($mobile, $otp, $curlErr);
    }

    $json = json_decode($raw, true) ?? [];
    $type = strtolower($json['type'] ?? '');

    if ($httpCode >= 200 && $httpCode < 300 && $type !== 'error') {
        error_log("[WA-OTP] Sent to 91{$mobile}");
        return ['channel' => 'whatsapp', 'success' => true];
    }

    // WhatsApp failed → try SMS
    $errMsg = $json['message'] ?? $json['type'] ?? "HTTP {$httpCode}";
    error_log("[WA-OTP] Failed: {$errMsg} | Trying SMS fallback...");
    return sendSmsFallback($mobile, $otp, $errMsg);
}

function sendSmsFallback(string $mobile, string $otp, string $waError): array {
    $tmplId = defined('MSG91_SMS_TEMPLATE_ID') ? MSG91_SMS_TEMPLATE_ID : '';
    $sender = defined('MSG91_SMS_SENDER_ID')   ? MSG91_SMS_SENDER_ID   : 'WLTHMT';

    if (!$tmplId) {
        // Both channels unavailable — log OTP for admin
        error_log("[OTP-FALLBACK] WA failed: {$waError} | mobile={$mobile} otp={$otp}");
        return ['channel' => 'log_only', 'success' => false, 'error' => 'Delivery failed — OTP logged'];
    }

    $url = 'https://api.msg91.com/api/v5/otp'
         . '?template_id=' . urlencode($tmplId)
         . '&mobile='      . urlencode('91' . $mobile)
         . '&authkey='     . urlencode(MSG91_AUTH_KEY)
         . '&otp='         . urlencode($otp)
         . '&sender='      . urlencode($sender);

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($raw, true) ?? [];
    if ($code >= 200 && $code < 300 && ($json['type'] ?? '') !== 'error') {
        error_log("[SMS-OTP] Sent via SMS to 91{$mobile}");
        return ['channel' => 'sms', 'success' => true];
    }

    error_log("[OTP-FALLBACK] All failed | mobile={$mobile} otp={$otp}");
    return ['channel' => 'failed', 'success' => false, 'error' => "HTTP {$code}"];
}

// ─────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────
function normMobile(mixed $v): string {
    $m = preg_replace('/\D/', '', (string)$v);
    if (strlen($m) === 12 && str_starts_with($m, '91')) $m = substr($m, 2);
    return strlen($m) === 10 ? $m : '';
}

function maskMobile(string $m): string {
    return substr($m, 0, 2) . 'xxxxxx' . substr($m, -2);
}

function encryptOtpToken(string $mobile): string {
    $data = json_encode(['m' => $mobile, 't' => time(), 'x' => time() + 1800]);
    $key  = DB_PASS . DB_NAME;
    return base64_encode($data . '|' . hash_hmac('sha256', $data, $key));
}

function verifyOtpToken(string $token, string $mobile): bool {
    try {
        $decoded = base64_decode($token, true);
        if (!$decoded) return false;
        $parts = explode('|', $decoded, 2);
        if (count($parts) !== 2) return false;
        [$data, $sig] = $parts;
        $key = DB_PASS . DB_NAME;
        if (!hash_equals($sig, hash_hmac('sha256', $data, $key))) return false;
        $p = json_decode($data, true);
        return is_array($p) && ($p['m'] ?? '') === $mobile && time() <= ($p['x'] ?? 0);
    } catch (\Throwable $e) { return false; }
}

function generateUserId(\PDO $pdo): string {
    do {
        $id = 'WM' . str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $e  = $pdo->prepare("SELECT id FROM partners WHERE user_id=?");
        $e->execute([$id]);
    } while ($e->fetch());
    return $id;
}

function createSession(\PDO $pdo, int $partnerId): string {
    $token = bin2hex(random_bytes(32));
    $sid   = bin2hex(random_bytes(16));
    $pdo->prepare("INSERT INTO partner_sessions (session_id, partner_id, token, ip_address) VALUES (?,?,?,?)")
        ->execute([$sid, $partnerId, $token, $_SERVER['REMOTE_ADDR'] ?? '']);
    return $token;
}

function updateLastLogin(\PDO $pdo, int $pid): void {
    $pdo->prepare("UPDATE partners SET last_login_at=NOW() WHERE id=?")->execute([$pid]);
}

function loadPartnerPublic(\PDO $pdo, int $pid): array {
    $s = $pdo->prepare("SELECT id,partner_name,first_name,last_name,mobile,email,company_name,branch_city,partner_code,user_id,is_registered FROM partners WHERE id=?");
    $s->execute([$pid]);
    return $s->fetch() ?: [];
}

function logAct(?int $lid, ?int $pid, string $act, string $det = ''): void {
    try {
        getDB()->prepare("INSERT INTO activity_log (lead_id,partner_id,action,details,ip_address) VALUES (?,?,?,?,?)")
               ->execute([$lid, $pid, $act, $det, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (\Throwable $e) {}
}