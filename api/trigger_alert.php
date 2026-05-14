<?php
/**
 * WealthMetre — trigger_alert.php
 *
 * Standalone alert endpoint called by diva_v3.php via async HTTP.
 * Handles email + Google Sheet for Diva AI leads.
 *
 * Upload to: /public_html/api/trigger_alert.php
 *
 * This file has NO impact on Diva — it runs separately in background.
 * If it fails, Diva still works perfectly.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../config/alert_config.php';
require_once __DIR__ . '/../helpers/LeadAlert.php';

header('Content-Type: application/json');

// ── Security: simple secret check ────────────────────────
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$secret = $body['secret'] ?? '';

if ($secret !== 'wm_alert_2024') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// ── Extract lead data ─────────────────────────────────────
$source = $body['source'] ?? 'Diva AI';

$leadData = [
    'id'             => $body['id']             ?? 0,
    'session_id'     => $body['session_id']     ?? '',
    'name'           => $body['name']           ?? '',
    'mobile'         => $body['mobile']         ?? '',
    'city'           => $body['city']           ?? '',
    'loan_type'      => $body['loan_type']      ?? '',
    'loan_amount'    => $body['loan_amount']    ?? 0,
    'property_value' => $body['property_value'] ?? 0,
    'monthly_income' => $body['monthly_income'] ?? 0,
    'cibil_score'    => $body['cibil_score']    ?? 0,
    'employment_type'=> $body['employment_type']?? '',
    'property_type'  => $body['property_type']  ?? '',
    'alert_time'     => $body['alert_time']     ?? date('d M Y, h:i A'),
];

// ── Fire alert ────────────────────────────────────────────
$result = sendLeadAlert($leadData, $source);

echo json_encode(['ok' => true, 'result' => $result]);
exit;
