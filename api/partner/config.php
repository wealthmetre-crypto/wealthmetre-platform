<?php
// ─── Database Config ───────────────────────────────────────────────
define('DB_HOST', getenv('WM_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('WM_DB_NAME') ?: 'wealthmetre');
define('DB_USER', getenv('WM_DB_USER') ?: 'wm_user');
define('DB_PASS', getenv('WM_DB_PASS') ?: 'Chukali1234');
define('DB_CHARSET', 'utf8mb4');

define('DEBUG_MODE', true); // set false in production

// ─── CORS Headers ──────────────────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
set_exception_handler(function($e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine()]);
    exit;
});
set_error_handler(function($no,$str,$file,$line) {
    throw new ErrorException($str,$no,0,$file,$line);
});

// ─── DB Connection ─────────────────────────────────────────────────
function getDB(): mysqli {
    static $db = null;
    if ($db === null) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_error) {
            jsonError('Database connection failed', 500);
        }
        $db->set_charset('utf8mb4');
    }
    return $db;
}

// ─── Response Helpers ──────────────────────────────────────────────
function jsonOk(array $data = []): void {
    echo json_encode(array_merge(['status' => 'ok'], $data));
    exit;
}
function jsonError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// ─── Auth: verify Bearer token, return partner row ─────────────────
function requireAuth(): array {
    // Apache doesn't pass Authorization to $_SERVER — use getallheaders()
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
        jsonError('Unauthorised - missing token', 401);
    }
    $token = trim($m[1]);
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT p.* FROM partners p " .
        "JOIN partner_sessions s ON s.partner_id = p.id " .
        "WHERE s.token = ? AND s.is_active = 1 AND s.logout_time IS NULL " .
        "AND p.status = 'active' LIMIT 1"
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        jsonError('Unauthorised - invalid or expired token', 401);
    }
    return $row;
}
function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// ─── Log to activity_logs ──────────────────────────────────────────
function logActivity(int $leadId, int $partnerId, string $type, string $action, array $details = []): void {
    $db = getDB();
    $detailsJson = json_encode($details);
    $stmt = $db->prepare("INSERT INTO activity_logs (lead_id, partner_id, type, action, details) VALUES (?,?,?,?,?)");
    $stmt->bind_param('iisss', $leadId, $partnerId, $type, $action, $detailsJson);
    $stmt->execute();
}

if (!function_exists('getBody')) {
    function getBody(): array {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }
}
