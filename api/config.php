<?php
declare(strict_types=1);

/**
 * WealthMetre – config.php
 * Final production-ready shared config/helpers
 */

// ------------------------------------------------
// CORS
// ------------------------------------------------
$allowedOrigins = [
    'https://wealthmetre.com',
    'https://www.wealthmetre.com',
    'http://localhost',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    header("Access-Control-Allow-Origin: https://wealthmetre.com");
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ------------------------------------------------
// ENV / SETTINGS
// ------------------------------------------------
define('DB_HOST', getenv('WM_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('WM_DB_NAME') ?: 'wealthmetre');
define('DB_USER', getenv('WM_DB_USER') ?: '');
define('DB_PASS', getenv('WM_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('OPENAI_API_KEY', getenv('WM_OPENAI_KEY') ?: '');
define('ANTHROPIC_API_KEY', getenv('WM_CLAUDE_KEY') ?: '');
define('OPENAI_MODEL', 'gpt-4o-mini');

define('QDRANT_URL', getenv('WM_QDRANT_URL') ?: 'https://dc600257-dd75-474c-af45-b26149321608.us-east-1-1.aws.cloud.qdrant.io');
define('QDRANT_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhY2Nlc3MiOiJtIiwic3ViamVjdCI6ImFwaS1rZXk6OTI5NDI2ZDQtNWY4NS00NjNhLWFiNWQtNjg0ZGZhMDMyNGQ4In0.NbSaS01Wa6KdNu8WqTxd2RTWvcyTRapMVKVNZ77x3Tc');
define('QDRANT_COLLECTION', 'lender_policies');
define('DIFY_HOME_KEY', getenv('WM_DIFY_KEY') ?: '');
define('DIFY_LAP_KEY',  getenv('WM_DIFY_KEY') ?: '');
define('RAG_DIFY_FALLBACK', true);

define('MSG91_AUTH_KEY', getenv('WM_MSG91_KEY') ?: '');
define('MSG91_WIDGET_ID', getenv('WM_MSG91_WIDGET_ID') ?: '');
define('MSG91_WIDGET_TOKEN', getenv('WM_MSG91_WIDGET_TOKEN') ?: '');
define('MSG91_WIDGET_VALIDATE_TOKEN_URL', 'https://control.msg91.com/api/v5/widget/verifyAccessToken');
define('MSG91_WHATSAPP_TEMPLATE', 'wealthmetre_otp');
define('MSG91_WHATSAPP_FROM_NUMBER', '917976218596');
define('MSG91_SMS_TEMPLATE_ID', '');  // Fill when registered
define('MSG91_SMS_SENDER_ID',   'WLTHMT');

define('MAX_LENDERS_RETURN', 12);
define('DEFAULT_CITY', 'jaipur');
define('DEBUG_MODE', true);
define('DB_TABLE', 'lenders_products');

// ------------------------------------------------
// FEATURE FLAGS
// ------------------------------------------------

// ═══════════════════════════════════════════════════════════
//  GEO DISTANCE ENGINE
// ═══════════════════════════════════════════════════════════
function getRajasthanCityDistances(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    try {
        $pdo = getDB();
        $rows = $pdo->query("SELECT LOWER(COALESCE(tehsil,district)) as city, km_from_jaipur FROM rajasthan_geo WHERE km_from_jaipur IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
        $cache = [];
        foreach ($rows as $r) $cache[$r['city']] = (int)$r['km_from_jaipur'];
    } catch (\Throwable $e) { $cache = []; }
    return $cache;
}
function getDistanceFromJaipur(string $city, array $distances): ?int {
    $city = strtolower(trim($city));
    if (isset($distances[$city])) return $distances[$city];
    foreach ($distances as $key => $km) {
        if (str_contains($city,$key) || str_contains($key,$city)) return $km;
    }
    return null;
}

function ragEnabled(): bool
{
    return strlen(QDRANT_URL) > 10 && strlen(QDRANT_API_KEY) > 5;
}

function openAiEnabled(): bool
{
    $enabled = strlen(OPENAI_API_KEY) > 20;

    if (!$enabled) {
        error_log('[WealthMetre] WARNING: OpenAI API key not configured');
    }

    return $enabled;
}

// ------------------------------------------------
// DB
// ------------------------------------------------
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!DB_NAME || !DB_USER) {
        throw new \RuntimeException('DB credentials not configured');
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );

    return $pdo;
}

// ------------------------------------------------
// RESPONSE HELPERS
// ------------------------------------------------
function jsonResponse(array $data, int $status = 200): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $msg, int $status = 400): void
{
    jsonResponse([
        'success' => false,
        'message' => $msg,
    ], $status);
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');

    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// ------------------------------------------------
// SANITIZERS
// ------------------------------------------------
function sanitize(string $val, int $max = 500): string
{
    return mb_substr(trim(strip_tags($val)), 0, $max);
}

function intSafe(mixed $v, int $default = 0): int
{
    return is_numeric($v) ? (int)$v : $default;
}

function floatSafe(mixed $v, float $default = 0.0): float
{
    return is_numeric($v) ? (float)$v : $default;
}

function normalizeLoanType(string $raw): string
{
    $r = strtolower(trim($raw));

    if (str_contains($r, 'lap') || str_contains($r, 'loan against property')) return 'lap';
    if (str_contains($r, 'home') || str_contains($r, 'housing') || str_contains($r, 'hl')) return 'home';
    if (str_contains($r, 'business') || str_contains($r, 'msme') || str_contains($r, 'working capital') || str_contains($r, 'sme')) return 'business';
    if (str_contains($r, 'car') || str_contains($r, 'vehicle') || str_contains($r, 'auto')) return 'car';
    if (str_contains($r, 'personal')) return 'personal';
    if (str_contains($r, 'education')) return 'education';
    if (str_contains($r, 'equipment') || str_contains($r, 'machinery')) return 'equipment';

    return $r;
}

// ------------------------------------------------
// LOGGING
// ------------------------------------------------
function logApiRequest(string $endpoint, string $sessionId): void
{
    if (DEBUG_MODE) {
        error_log("[{$endpoint}] session={$sessionId} ip=" . ($_SERVER['REMOTE_ADDR'] ?? ''));
    }
}

// ------------------------------------------------
// OPENAI
// ------------------------------------------------
function callOpenAI(
    array $messages,
    int $maxTokens = 700,
    float $temp = 0.4,
    bool $jsonMode = true
): ?string {
    if (!openAiEnabled()) {
        return null;
    }

    $params = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => $maxTokens,
        'temperature' => $temp,
    ];

    if ($jsonMode) {
        $params['response_format'] = ['type' => 'json_object'];
    }

    $payload = json_encode($params);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
    ]);

    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($raw === false) {
        error_log('[WealthMetre] OpenAI curl error: ' . curl_error($ch));
    }

    $t0 = microtime(true);
    $raw = curl_exec($ch);
    $ms  = (int)round((microtime(true) - $t0) * 1000);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $data    = $raw ? (json_decode($raw, true) ?? []) : [];
    $usage   = $data['usage'] ?? [];
    $pTok    = (int)($usage['prompt_tokens']     ?? 0);
    $cTok    = (int)($usage['completion_tokens'] ?? 0);
    $tTok    = (int)($usage['total_tokens']      ?? 0);
    $costUsd = str_contains(OPENAI_MODEL, 'mini') ? round(($pTok * 0.00000015) + ($cTok * 0.0000006), 8) : round(($pTok * 0.0000025) + ($cTok * 0.00001), 8);
    $status  = ($code === 200 && $raw && !$curlErr) ? 'success' : ($curlErr ? 'timeout' : 'error');
    $errMsg  = $curlErr ?: ($code !== 200 ? "HTTP {$code}" : null);
    $preview = '';
    foreach ($messages as $m) {
        if (isset($m['content']) && is_string($m['content'])) { $preview = substr($m['content'], 0, 300); break; }
    }
    $bt  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    $src = basename($bt[1]['file'] ?? 'unknown', '.php');
    try {
        getDB()->prepare("INSERT INTO llm_call_logs (call_source,model,prompt_tokens,completion_tokens,total_tokens,latency_ms,status,cost_usd,prompt_preview,error_message) VALUES (?,?,?,?,?,?,?,?,?,?)")
               ->execute([$src, OPENAI_MODEL, $pTok?:null, $cTok?:null, $tTok?:null, $ms, $status, $costUsd?:null, $preview?:null, $errMsg]);
    } catch (\Throwable $e) { error_log('[LLM-LOG] ' . $e->getMessage()); }

    if ($raw === false || $curlErr) { error_log('[WealthMetre] OpenAI curl error: ' . $curlErr); return null; }
    if ($code !== 200 || !$raw)     { error_log('[WealthMetre] OpenAI non-200 response: ' . $code); return null; }
    return $data['choices'][0]['message']['content'] ?? null;
}

function chatCompletion(array $messages, int $maxTokens = 500): ?string
{
    return callOpenAI($messages, $maxTokens, 0.3, false);
}

// ------------------------------------------------
// AI RESPONSE PARSING
// ------------------------------------------------
function parseAIResponse(string $raw): array
{
    $clean = trim((string)(preg_replace('/```json|```/i', '', $raw) ?? ''));
    $data = json_decode($clean, true);
    return is_array($data) ? $data : [];
}

// ------------------------------------------------
// HYBRID QUERY CALL
// ------------------------------------------------
function callHybridQuery(array $profile): array
{
    $localPath = __DIR__ . '/hybrid_query.php';

    // Try local include first
    if (file_exists($localPath)) {
        require_once $localPath;

        if (function_exists('runHybrid')) {
            try {
                return runHybrid($profile, '');
            } catch (\Throwable $e) {
                error_log('[WealthMetre] Local hybrid error: ' . $e->getMessage());
            }
        }
    }

    // Fallback to HTTP
    $payload = json_encode(['profile' => $profile]);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'wealthmetre.com';
    $url = $scheme . '://' . $host . '/api/hybrid_query.php';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        error_log('[WealthMetre] Hybrid query curl error: ' . curl_error($ch));
    }

    curl_close($ch);

    if (!$raw) {
        return [
            'type' => 'error',
            'lenders' => [],
            'message' => 'Hybrid query unavailable',
        ];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [
        'type' => 'error',
        'lenders' => [],
        'message' => 'Invalid hybrid query response',
    ];
}

// ------------------------------------------------
// EMBEDDINGS
// ------------------------------------------------
function getEmbedding(string $text): ?array
{
    if (!openAiEnabled()) {
        return null;
    }

    $text = mb_substr(trim($text), 0, 8000);

    if ($text === '') {
        return null;
    }

    $payload = json_encode([
        'model' => 'text-embedding-3-small',
        'input' => $text,
    ]);

    $ch = curl_init('https://api.openai.com/v1/embeddings');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
    ]);

    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($raw === false) {
        error_log('[WealthMetre] Embedding curl error: ' . curl_error($ch));
    }

    curl_close($ch);

    if ($code !== 200 || !$raw) {
        return null;
    }

    $decoded = json_decode($raw, true);
    return $decoded['data'][0]['embedding'] ?? null;
}

// ------------------------------------------------
// QDRANT
// ------------------------------------------------
function qdrantSearch(array $vector, int $topK = 5): array
{
    if (!ragEnabled()) {
        return [];
    }

    $url = rtrim(QDRANT_URL, '/') . '/collections/' . QDRANT_COLLECTION . '/points/search';

    $payload = json_encode([
        'vector' => $vector,
        'limit' => $topK,
        'with_payload' => true,
        'with_vector' => false,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'api-key: ' . QDRANT_API_KEY,
        ],
    ]);

    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($raw === false) {
        error_log('[WealthMetre] Qdrant curl error: ' . curl_error($ch));
    }

    curl_close($ch);

    if ($code !== 200 || !$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return $decoded['result'] ?? [];
}
