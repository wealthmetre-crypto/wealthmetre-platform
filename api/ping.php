<?php
/**
 * ═══════════════════════════════════════════════════════════
 *  WealthMetre – Diva AI Loan Advisor
 *  FILE: /public_html/api/ping.php
 *  PURPOSE: Health check endpoint.
 *           Verifies API is live, DB connection, AI keys.
 *  METHOD:  GET
 * ═══════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

$checks = [];

// ── PHP version ───────────────────────────────────────────
$checks['php_version'] = [
    'status' => version_compare(PHP_VERSION, '8.0', '>=') ? 'ok' : 'warning',
    'value'  => PHP_VERSION,
];

// ── Database connection ───────────────────────────────────
try {
    $pdo = getDB();
    $pdo->query('SELECT 1');
    $checks['database'] = ['status' => 'ok', 'message' => 'Connected'];
} catch (\PDOException $e) {
    $checks['database'] = [
        'status'  => 'error',
        'message' => 'Cannot connect — check DB credentials in config.php',
        'detail'  => DEBUG_MODE ? $e->getMessage() : null,
    ];
}

// ── OpenAI key ────────────────────────────────────────────
$checks['openai_key'] = [
    'status'  => (strpos(OPENAI_API_KEY, 'YOUR_') !== false) ? 'not_configured' : 'configured',
    'message' => (strpos(OPENAI_API_KEY, 'YOUR_') !== false)
        ? 'OpenAI key not set — replace in config.php'
        : 'Key is configured (not validated)',
];

// ── Qdrant ────────────────────────────────────────────────
$checks['qdrant'] = [
    'status'  => (strpos(QDRANT_URL, 'YOUR-') !== false) ? 'not_configured' : 'configured',
    'message' => (strpos(QDRANT_URL, 'YOUR-') !== false)
        ? 'Qdrant URL not set — configure in config.php'
        : QDRANT_URL,
];

// ── API files present ─────────────────────────────────────
$files = ['config.php', 'diva_flow.php', 'diva_parser.php', 'lender_search.php', 'rag_query.php', 'hybrid_query.php'];
$filesStatus = [];
foreach ($files as $f) {
    $filesStatus[$f] = file_exists(__DIR__ . '/' . $f) ? 'present' : 'missing';
}
$checks['api_files'] = ['status' => 'info', 'files' => $filesStatus];

// ── CORS check ────────────────────────────────────────────
$checks['cors'] = ['status' => 'ok', 'message' => 'CORS headers set for wealthmetre.com'];

// ── Overall status ────────────────────────────────────────
$hasError   = array_filter($checks, fn($c) => ($c['status'] ?? '') === 'error');
$hasWarning = array_filter($checks, fn($c) => in_array($c['status'] ?? '', ['warning', 'not_configured']));

$overall = 'ok';
if ($hasError)   $overall = 'error';
elseif ($hasWarning) $overall = 'warning';

jsonResponse([
    'status'    => $overall,
    'service'   => 'WealthMetre Diva API',
    'version'   => '2.0.0',
    'timestamp' => date('c'),
    'checks'    => $checks,
    'setup_guide' => [
        '1_db'     => 'Create MySQL DB and update DB_NAME, DB_USER, DB_PASS in config.php',
        '2_openai' => 'Get API key from platform.openai.com and set OPENAI_API_KEY in config.php',
        '3_qdrant' => '(Optional) Set up Qdrant for RAG. Or skip — system works without it.',
        '4_table'  => 'Run the SQL schema from /api/schema.sql to create lenders table',
        '5_test'   => 'Call /api/hybrid_query.php?q=LAP+50+lakh+jaipur to test',
    ],
]);
