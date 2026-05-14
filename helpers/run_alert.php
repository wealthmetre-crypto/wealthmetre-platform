<?php

/**
 * WealthMetre — Async Alert Runner
 * 
 * This script is called in the background by sendAlertAsync().
 * It receives base64-encoded lead data as a CLI argument and
 * runs sendLeadAlert() without blocking the main API response.
 * 
 * Called via:
 *   php run_alert.php <base64_encoded_payload>
 * 
 * DO NOT call this file directly from a browser.
 * It is a CLI-only background worker.
 */

// Block browser access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

// Validate argument
if (empty($argv[1])) {
    exit('No payload provided');
}

// Decode payload
$raw = base64_decode($argv[1]);
if (!$raw) {
    exit('Invalid payload encoding');
}

$payload = json_decode($raw, true);
if (!$payload || !isset($payload['lead'], $payload['source'])) {
    exit('Invalid payload structure');
}

// Load dependencies
require_once __DIR__ . '/../config/alert_config.php';
require_once __DIR__ . '/LeadAlert.php';

// Run the alert
$result = sendLeadAlert($payload['lead'], $payload['source']);

// Log result for debugging
if (defined('ALERT_DEBUG_MODE') && ALERT_DEBUG_MODE) {
    file_put_contents(
        ALERT_LOG_PATH,
        '[' . date('Y-m-d H:i:s') . '] ASYNC RESULT: ' . json_encode($result) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

exit(0);
