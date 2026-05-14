<?php
/**
 * WealthMetre Google Sheet Sync
 * Appends every new lead to a Google Sheet via Apps Script webhook.
 */

if (!function_exists('syncToGoogleSheet')):
function syncToGoogleSheet(array $leadData): array
{
    if (!defined('GOOGLE_SHEET_WEBHOOK_URL') || empty(GOOGLE_SHEET_WEBHOOK_URL)) {
        return ['success' => false, 'error' => 'Google Sheet URL not configured'];
    }

    $url = GOOGLE_SHEET_WEBHOOK_URL;

    if ($url === 'YOUR_GOOGLE_APPS_SCRIPT_URL_HERE') {
        return ['success' => false, 'error' => 'Google Sheet URL is placeholder'];
    }

    $payload = json_encode([
        'alert_time'      => $leadData['alert_time']      ?? date('d M Y, h:i A'),
        'source'          => $leadData['source']           ?? 'Unknown',
        'lead_id'         => $leadData['id']               ?? $leadData['session_id'] ?? '',
        'session_id'      => $leadData['session_id']       ?? '',
        'name'            => $leadData['name']             ?? '',
        'mobile'          => $leadData['mobile']           ?? $leadData['phone'] ?? '',
        'city'            => $leadData['city']             ?? '',
        'product'         => $leadData['loan_type']        ?? $leadData['product'] ?? '',
        'loan_amount'     => $leadData['loan_amount']      ?? '',
        'property_value'  => $leadData['property_value']  ?? '',
        'property_type'   => $leadData['property_type']   ?? '',
        'monthly_income'  => $leadData['monthly_income']  ?? '',
        'cibil_score'     => $leadData['cibil_score']     ?? '',
        'employment_type' => $leadData['employment_type'] ?? '',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,        // hardcoded — no ALERT_TIMEOUT dependency
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        error_log('[GoogleSheetAlert] curl error: ' . $error);
        return ['success' => false, 'error' => $error];
    }

    $decoded = json_decode($response, true);

    if (($decoded['status'] ?? '') === 'success') {
        return ['success' => true, 'message' => 'Row added to Google Sheet'];
    }

    error_log('[GoogleSheetAlert] failed: ' . $response . ' HTTP:' . $httpCode);
    return [
        'success'   => false,
        'error'     => $decoded['message'] ?? 'Unknown error',
        'http_code' => $httpCode,
        'response'  => $response,
    ];
}
endif;
