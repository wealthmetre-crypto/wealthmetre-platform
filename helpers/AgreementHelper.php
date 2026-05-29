<?php
/**
 * WealthMetre - AgreementHelper.php
 * Partner Agreement utility functions
 */

// ── Company constants (add to config.php if not present) ────
if (!defined('WM_COMPANY_NAME'))    define('WM_COMPANY_NAME',    'Wealthmetre Finserve');
if (!defined('WM_COMPANY_ADDRESS')) define('WM_COMPANY_ADDRESS', 'Off. No. 920, 7th Floor, Anchor Mall, Opp Civil Lines Metro Station, Jaipur, Rajasthan');
if (!defined('WM_COMPANY_EMAIL'))   define('WM_COMPANY_EMAIL',   'wealthmetre@gmail.com');
if (!defined('WM_COMPANY_PHONE'))   define('WM_COMPANY_PHONE',   '+91 79762 18596');
if (!defined('WM_COMPANY_WEBSITE')) define('WM_COMPANY_WEBSITE', 'www.wealthmetre.com');

// ── Get active agreement from DB ─────────────────────────────
function getActiveAgreement(PDO $pdo): ?array {
    $stmt = $pdo->prepare("
        SELECT * FROM agreement_versions
        WHERE agreement_type = 'partner_onboarding'
          AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['full_text'] = replaceAgreementPlaceholders($row['full_text']);
    return $row;
}

// ── Replace company placeholders ─────────────────────────────
function replaceAgreementPlaceholders(string $text): string {
    return str_replace(
        [
            '[Your Company / Proprietorship / LLP / Pvt Ltd Name]',
            '[Registered Office Address]'
        ],
        [
            WM_COMPANY_NAME,
            WM_COMPANY_ADDRESS
        ],
        $text
    );
}

// ── Validate partner session ──────────────────────────────────
function getPartnerFromSession(PDO $pdo): ?array {
    $token = $_SERVER['HTTP_X_PARTNER_TOKEN']
          ?? $_SERVER['HTTP_AUTHORIZATION']
          ?? '';
    $token = str_replace('Bearer ', '', trim($token));
    if (!$token) {
        // Try cookie fallback
        $token = $_COOKIE['wm_partner_token'] ?? '';
    }
    if (!$token) return null;

    $stmt = $pdo->prepare("
        SELECT ps.partner_id, ps.token, p.id, p.partner_name,
               p.mobile, p.email, p.status, p.is_registered,
               p.agreement_version_accepted, p.onboarding_step,
               p.first_name, p.last_name, p.firm_name
        FROM partner_sessions ps
        JOIN partners p ON p.id = ps.partner_id
        WHERE ps.token = ?
          AND ps.is_active = 1
          AND ps.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ── Log audit event ───────────────────────────────────────────
function logAgreementAudit(
    PDO    $pdo,
    int    $partnerId,
    string $action,
    ?string $version  = null,
    array  $metadata  = []
): void {
    try {
        $ip = getClientIpAddress();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $stmt = $pdo->prepare("
            INSERT INTO partner_agreement_audit
                (partner_id, action, agreement_version,
                 ip_address, user_agent, metadata)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $partnerId, $action, $version,
            $ip, $ua,
            !empty($metadata) ? json_encode($metadata) : null
        ]);
    } catch (Throwable $e) {
        error_log('[AgreementAudit] ' . $e->getMessage());
    }
}

// ── Geo lookup from IP ────────────────────────────────────────
function getGeoFromIp(string $ip): array {
    $default = ['city' => null, 'state' => null, 'country' => 'IN'];
    if (!$ip || $ip === 'unknown' || $ip === '127.0.0.1') return $default;

    try {
        $ctx = stream_context_create(['http' => ['timeout' => 1]]);
        $res = @file_get_contents(
            "http://ip-api.com/json/{$ip}?fields=city,regionName,country",
            false, $ctx
        );
        if (!$res) return $default;
        $geo = json_decode($res, true);
        return [
            'city'    => $geo['city']       ?? null,
            'state'   => $geo['regionName'] ?? null,
            'country' => $geo['country']    ?? 'IN',
        ];
    } catch (Throwable $e) {
        return $default;
    }
}

// ── Get client IP ─────────────────────────────────────────────
function getClientIpAddress(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
       ?? $_SERVER['HTTP_X_REAL_IP']
       ?? $_SERVER['REMOTE_ADDR']
       ?? 'unknown';
    // Take first IP if comma-separated
    return trim(explode(',', $ip)[0]);
}

// ── Rate limiting for agreement endpoint ─────────────────────
function checkAgreementRateLimit(int $partnerId, string $ip): void {
    $dir = sys_get_temp_dir() . '/wm_agreement_rl';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $now  = time();
    $win  = 3600; // 1 hour window

    // Per partner: max 5 attempts
    $keyP  = $dir . '/p_' . md5((string)$partnerId) . '.json';
    _checkBucket($keyP, 5, $win, $now);

    // Per IP: max 10 attempts
    $keyI  = $dir . '/i_' . md5($ip) . '.json';
    _checkBucket($keyI, 10, $win, $now);
}

function _checkBucket(string $file, int $max, int $win, int $now): void {
    $reqs = [];
    if (is_file($file)) {
        $d = json_decode((string)@file_get_contents($file), true);
        if (is_array($d)) $reqs = $d;
    }
    $reqs = array_values(array_filter($reqs, fn($t) => (int)$t > $now - $win));
    if (count($reqs) >= $max) {
        http_response_code(429);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Too many attempts. Please try after 1 hour.'
        ]);
        exit;
    }
    $reqs[] = $now;
    @file_put_contents($file, json_encode($reqs), LOCK_EX);
}

// ── CSRF token generate & validate ───────────────────────────
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['wm_csrf_agreement'])) {
        $_SESSION['wm_csrf_agreement'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['wm_csrf_agreement'];
}

function validateCsrfToken(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $valid = $_SESSION['wm_csrf_agreement'] ?? '';
    return $valid && hash_equals($valid, $token);
}

// ── Send partner WhatsApp notification ───────────────────────
function sendAgreementNotification(array $partner): void {
    try {
        require_once __DIR__ . '/WhatsAppAlert.php';
        // Admin alert
        sendWhatsAppAlert([
            'name'         => $partner['partner_name'] ?? '',
            'mobile'       => $partner['mobile'] ?? '',
            'message'      => "New partner application submitted.\nPartner: " .
                              ($partner['partner_name'] ?? '') .
                              "\nMobile: " . ($partner['mobile'] ?? '') .
                              "\nAgreement accepted at: " . date('d M Y H:i'),
        ]);
    } catch (Throwable $e) {
        error_log('[AgreementNotify] ' . $e->getMessage());
    }
}
