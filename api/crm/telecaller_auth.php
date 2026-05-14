<?php
/**
 * WealthMetre — Telecaller Auth API
 * Path: /api/crm/telecaller_auth.php
 *
 * Actions:
 *   login           → validate credentials, return token
 *   logout          → invalidate token
 *   verify_token    → check token validity
 *   update_activity → log login/logout/break events
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/db.php';

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? $_GET['action'] ?? '';

if (basename($_SERVER['SCRIPT_FILENAME']) === 'telecaller_auth.php') {
    switch ($action) {
        case 'login':           tcLogin($body);         break;
        case 'logout':          tcLogout();             break;
        case 'verify_token':    tcVerify();             break;
        case 'update_activity': tcActivity($body);      break;
        default: tcJsonErr('Invalid action');
    }
}


// ════════════════════════════════════════════════════════
// LOGIN
// ════════════════════════════════════════════════════════
function tcLogin(array $b): void {
    $mobile = preg_replace('/\D/', '', $b['mobile'] ?? '');
    $pass   = $b['password'] ?? '';

    if (strlen($mobile) !== 10) tcJsonErr('Valid mobile required');
    if (strlen($pass) < 6)      tcJsonErr('Password required');

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT t.*, p.partner_name, p.branch_city, p.status AS partner_status
        FROM telecallers t
        JOIN partners p ON p.id = t.partner_id
        WHERE t.mobile = ? AND t.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$mobile]);
    $tc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tc)                                    tcJsonErr('Mobile not registered or account inactive');
    if ($tc['partner_status'] !== 'active')      tcJsonErr('Partner account is inactive');
    if (!password_verify($pass, $tc['password_hash'])) tcJsonErr('Incorrect password');

    // Create session token
    $token     = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 hours

    // Remove old sessions for this telecaller
    $pdo->prepare("DELETE FROM telecaller_sessions WHERE telecaller_id = ?")->execute([$tc['id']]);

    // Insert new session
    $pdo->prepare("
        INSERT INTO telecaller_sessions (telecaller_id, token, expires_at)
        VALUES (?, ?, ?)
    ")->execute([$tc['id'], $token, $expiresAt]);

    // Update last login
    $pdo->prepare("UPDATE telecallers SET last_login_at = NOW() WHERE id = ?")->execute([$tc['id']]);

    // Log activity
    logActivity($tc['id'], $tc['partner_id'], 'login', $pdo);

    // Get today's stats
    $today = date('Y-m-d');
    $stats = getTodayStats($tc['id'], $tc['partner_id'], $today, $pdo);

    tcJsonOk([
        'token'      => $token,
        'expires_at' => $expiresAt,
        'telecaller' => [
            'id'           => $tc['id'],
            'name'         => $tc['name'],
            'mobile'       => $tc['mobile'],
            'partner_id'   => $tc['partner_id'],
            'partner_name' => $tc['partner_name'],
            'branch_city'  => $tc['branch_city'],
            'target_calls' => $tc['target_calls'],
        ],
        'today_stats' => $stats
    ]);
}


// ════════════════════════════════════════════════════════
// LOGOUT
// ════════════════════════════════════════════════════════
function tcLogout(): void {
    $tc  = authenticateTelecaller();
    $pdo = getDB();
    $pdo->prepare("DELETE FROM telecaller_sessions WHERE telecaller_id = ?")->execute([$tc['id']]);
    logActivity($tc['id'], $tc['partner_id'], 'logout', $pdo);
    tcJsonOk(['message' => 'Logged out successfully']);
}


// ════════════════════════════════════════════════════════
// VERIFY TOKEN
// ════════════════════════════════════════════════════════
function tcVerify(): void {
    $tc = authenticateTelecaller();
    tcJsonOk(['valid' => true, 'telecaller' => $tc]);
}


// ════════════════════════════════════════════════════════
// LOG ACTIVITY (break, call events)
// ════════════════════════════════════════════════════════
function tcActivity(array $b): void {
    $tc   = authenticateTelecaller();
    $type = $b['type'] ?? '';

    $allowed = ['break_start','break_end','call_start','call_end','lead_update'];
    if (!in_array($type, $allowed)) tcJsonErr('Invalid activity type');

    $pdo = getDB();

    // Handle break tracking
    if ($type === 'break_start') {
        $pdo->prepare("
            INSERT INTO break_logs (telecaller_id, partner_id, break_start, break_type, break_date)
            VALUES (?, ?, NOW(), ?, CURDATE())
        ")->execute([$tc['id'], $tc['partner_id'], $b['break_type'] ?? 'other']);
    }

    if ($type === 'break_end') {
        $pdo->prepare("
            UPDATE break_logs
            SET break_end = NOW(),
                break_duration = TIMESTAMPDIFF(SECOND, break_start, NOW())
            WHERE telecaller_id = ?
              AND break_end IS NULL
            ORDER BY id DESC LIMIT 1
        ")->execute([$tc['id']]);
    }

    logActivity($tc['id'], $tc['partner_id'], $type, $pdo, $b['notes'] ?? null);
    tcJsonOk(['logged' => true]);
}


// ════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════

/**
 * Authenticate telecaller from Bearer token
 * Returns telecaller array or exits with 401
 */
function authenticateTelecaller(): array {
    $auth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $auth);
    if (!$token) {
        $token = $_GET['token'] ?? '';
    }
    if (!$token) tcJsonErr('Unauthorized', 401);

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT t.*, ts.expires_at,
               p.partner_name, p.branch_city, p.status AS partner_status
        FROM telecaller_sessions ts
        JOIN telecallers t ON t.id = ts.telecaller_id
        JOIN partners    p ON p.id = t.partner_id
        WHERE ts.token = ?
          AND ts.expires_at > NOW()
          AND t.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $tc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tc) tcJsonErr('Session expired. Please login again.', 401);
    return $tc;
}

function getTodayStats(int $tcId, int $partnerId, string $date, PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*)                                   AS total_calls,
            SUM(outcome = 'interested')                AS interested,
            SUM(outcome = 'follow_up')                 AS followups,
            SUM(outcome = 'converted')                 AS converted,
            SUM(outcome = 'not_reachable')             AS not_reachable,
            AVG(call_duration_sec)                     AS avg_duration
        FROM call_logs
        WHERE telecaller_id = ? AND partner_id = ?
          AND DATE(created_at) = ?
    ");
    $stmt->execute([$tcId, $partnerId, $date]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
}

function logActivity(int $tcId, int $partnerId, string $type, PDO $pdo, ?string $notes = null): void {
    try {
        $pdo->prepare("
            INSERT INTO telecaller_activity (telecaller_id, partner_id, activity_type, ip_address, notes)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$tcId, $partnerId, $type, getClientIP(), $notes]);
    } catch (Exception $e) {}
}

function getClientIP(): string {
    foreach (['HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $h) {
        if (!empty($_SERVER[$h])) return trim(explode(',', $_SERVER[$h])[0]);
    }
    return '0.0.0.0';
}

function tcJsonOk(array $data): void {
    echo json_encode(array_merge(['status' => 'ok'], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function tcJsonErr(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}