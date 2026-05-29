<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$body = getJsonBody();

$accessToken = trim($body['access_token'] ?? '');

if ($accessToken === '') {
    jsonError('access_token is required', 422);
}

if (!defined('MSG91_AUTH_KEY') || MSG91_AUTH_KEY === '') {
    jsonError('MSG91 AuthKey missing on server', 500);
}

$validateUrl = defined('MSG91_WIDGET_VALIDATE_TOKEN_URL')
    ? MSG91_WIDGET_VALIDATE_TOKEN_URL
    : 'https://control.msg91.com/api/v5/widget/verifyAccessToken';

/**
 * MSG91 widget token validation.
 * AuthKey stays server-side only.
 */
$postFields = [
    'access-token' => $accessToken,
    'access_token' => $accessToken,
];

$ch = curl_init($validateUrl);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($postFields),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'authkey: ' . MSG91_AUTH_KEY,
    ],
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($responseBody === false || $curlError) {
    jsonError('MSG91 validation cURL failed', 502, [
        'curl_error' => $curlError,
    ]);
}

$decoded = json_decode($responseBody, true);

if (!is_array($decoded)) {
    jsonError('MSG91 validation returned non-JSON response', 502, [
        'http_code' => $httpCode,
        'raw_response' => $responseBody,
    ]);
}

$msg91Type = $decoded['type'] ?? '';
$msg91Message = $decoded['message'] ?? '';

if ($httpCode !== 200 || $msg91Type !== 'success' || $msg91Message === '') {
    jsonError('MSG91 access token validation failed', 401, [
        'http_code' => $httpCode,
        'msg91_response' => $decoded,
    ]);
}

$mobileWithCountryCode = preg_replace('/\D+/', '', (string)$msg91Message);

$mobile = $mobileWithCountryCode;
if (strlen($mobileWithCountryCode) === 12 && substr($mobileWithCountryCode, 0, 2) === '91') {
    $mobile = substr($mobileWithCountryCode, 2);
}

$loginType = trim($body['login_type'] ?? 'partner'); // partner | telecaller

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = getDB();

    if ($loginType === 'telecaller') {
        $stmt = $db->prepare("
            SELECT t.id,
                   t.partner_id,
                   t.mobile,
                   t.name,
                   t.status,
                   t.target_calls,
                   p.partner_name,
                   p.branch_city,
                   p.status AS partner_status
            FROM telecallers t
            JOIN partners p ON p.id = t.partner_id
            WHERE t.mobile IN (?, ?)
            LIMIT 1
        ");
        $stmt->execute([$mobile, $mobileWithCountryCode]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse([
                'status' => 'error',
                'success' => false,
                'message' => 'No telecaller account found for verified mobile',
                'mobile' => $mobile,
                'mobile_with_country_code' => $mobileWithCountryCode,
            ], 404);
        }

        if (($user['status'] ?? '') !== 'active') {
            jsonResponse([
                'status' => 'error',
                'success' => false,
                'message' => 'Telecaller account is not active',
                'telecaller_status' => $user['status'] ?? null,
            ], 403);
        }

        if (($user['partner_status'] ?? '') !== 'active') {
            jsonResponse([
                'status' => 'error',
                'success' => false,
                'message' => 'Partner account is inactive',
                'partner_status' => $user['partner_status'] ?? null,
            ], 403);
        }

        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 604800);

        $db->prepare("DELETE FROM telecaller_sessions WHERE telecaller_id = ?")
           ->execute([(int)$user['id']]);

        $insert = $db->prepare("
            INSERT INTO telecaller_sessions
                (telecaller_id, token, expires_at, created_at)
            VALUES
                (?, ?, ?, NOW())
        ");
        $insert->execute([(int)$user['id'], $sessionToken, $expiresAt]);

        $update = $db->prepare("
            UPDATE telecallers
            SET last_login_at = NOW()
            WHERE id = ?
        ");
        $update->execute([(int)$user['id']]);

        $_SESSION['wm_login_type'] = 'telecaller';
        $_SESSION['telecaller_id'] = (int)$user['id'];
        $_SESSION['partner_id'] = (int)$user['partner_id'];
        $_SESSION['mobile'] = $mobile;
        $_SESSION['session_token'] = $sessionToken;

        jsonResponse([
            'status' => 'ok',
            'success' => true,
            'message' => 'Telecaller login successful',
            'login_type' => 'telecaller',
            'token' => $sessionToken,
            'session_token' => $sessionToken,
            'expires_at' => $expiresAt,
            'telecaller' => [
                'id' => (int)$user['id'],
                'partner_id' => (int)$user['partner_id'],
                'name' => $user['name'],
                'mobile' => $user['mobile'],
                'partner_name' => $user['partner_name'],
                'branch_city' => $user['branch_city'],
                'target_calls' => (int)($user['target_calls'] ?? 50),
            ],
            'mobile' => $mobile,
            'mobile_with_country_code' => $mobileWithCountryCode,
        ]);
    }

    $stmt = $db->prepare("
        SELECT id, user_id, partner_name, first_name, last_name, mobile, phone, status, onboarding_step, kyc_status
        FROM partners
        WHERE mobile IN (?, ?)
           OR phone IN (?, ?)
        LIMIT 1
    ");
    $stmt->execute([$mobile, $mobileWithCountryCode, $mobile, $mobileWithCountryCode]);
    $partner = $stmt->fetch();

    if (!$partner) {
        // New user — generate onboard token valid for 30 minutes
        $onboardToken = bin2hex(random_bytes(24));
        $db->prepare("
            INSERT INTO partner_onboard_tokens (mobile, token, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))
            ON DUPLICATE KEY UPDATE token=VALUES(token), expires_at=VALUES(expires_at), used=0
        ")->execute([$mobile, $onboardToken]);
        jsonResponse([
            'success'      => true,
            'is_new_user'  => true,
            'message'      => 'New partner — complete your profile to continue',
            'onboard_token'=> $onboardToken,
            'mobile'       => $mobile,
        ]);
    }

    // Allow under_review partners to login — they will see Under Review screen
    $allowedStatuses = ['active', 'under_review'];
    if (!in_array($partner['status'] ?? '', $allowedStatuses)) {
        jsonResponse([
            'success' => false,
            'message' => 'Partner account is not active. Contact WealthMetre support.',
            'status'  => $partner['status'] ?? null,
        ], 403);
    }

    $sessionToken = bin2hex(random_bytes(32));
    $sessionId = bin2hex(random_bytes(16));
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

    $insert = $db->prepare("
        INSERT INTO partner_sessions
            (partner_id, token, expires_at, created_at, session_id, user_agent, ip_address, is_active)
        VALUES
            (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW(), ?, ?, ?, 1)
    ");
    $insert->execute([
        (int)$partner['id'],
        $sessionToken,
        $sessionId,
        $userAgent,
        $ipAddress
    ]);

    $update = $db->prepare("
        UPDATE partners
        SET last_login_at = NOW()
        WHERE id = ?
    ");
    $update->execute([(int)$partner['id']]);

    $_SESSION['wm_login_type'] = 'partner';
    $_SESSION['partner_id'] = (int)$partner['id'];
    $_SESSION['partner_user_id'] = $partner['user_id'];
    $_SESSION['partner_name'] = $partner['partner_name'];
    $_SESSION['mobile'] = $mobile;
    $_SESSION['session_token'] = $sessionToken;

    jsonResponse([
        'success' => true,
        'message' => 'Partner login successful',
        'login_type' => 'partner',
        'partner' => [
            'id' => (int)$partner['id'],
            'user_id' => $partner['user_id'],
            'partner_name'    => $partner['partner_name'],
            'first_name'      => $partner['first_name'],
            'last_name'       => $partner['last_name'],
            'mobile'          => $partner['mobile'] ?: $partner['phone'],
            'onboarding_step' => $partner['onboarding_step'] ?? null,
            'kyc_status'      => $partner['kyc_status'] ?? null,
            'status'          => $partner['status'] ?? null,
        ],
        'token' => $sessionToken,
        'session_token' => $sessionToken,
        'mobile' => $mobile,
        'mobile_with_country_code' => $mobileWithCountryCode,
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Login session creation failed',
        'error' => $e->getMessage(),
    ], 500);
}
