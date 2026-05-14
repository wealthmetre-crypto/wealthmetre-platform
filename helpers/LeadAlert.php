<?php

/**
 * WealthMetre Lead Alert System
 * 
 * Sends instant Email + WhatsApp + Google Sheet notifications
 * whenever a new lead is inserted into MySQL.
 * 
 * Usage:
 *   require_once 'helpers/LeadAlert.php';
 *   sendLeadAlert($leadData, 'Diva AI');
 *   sendLeadAlert($leadData, 'Partner Portal');
 */

require_once __DIR__ . '/../config/alert_config.php';
require_once __DIR__ . '/EmailAlert.php';
require_once __DIR__ . '/WhatsAppAlert.php';
require_once __DIR__ . '/GoogleSheetAlert.php';
require_once __DIR__ . '/AlertLogger.php';

/**
 * Master function — call this immediately after successful MySQL INSERT.
 *
 * @param array  $leadData   Associative array of lead fields
 * @param string $source     'Diva AI' or 'Partner Portal'
 * @return array             Results from each notification channel
 */
function sendLeadAlert(array $leadData, string $source): array
{
    // Normalize source
    $source = in_array($source, ['Diva AI', 'Partner Portal'])
        ? $source
        : 'Unknown';

    // Attach source and timestamp to lead data
    $leadData['source']    = $source;
    $leadData['alert_time'] = date('d M Y, h:i A');

    $results = [
        'source'       => $source,
        'lead_id'      => $leadData['id']      ?? $leadData['session_id'] ?? null,
        'email'        => null,
        'whatsapp'     => null,
        'google_sheet' => null,
    ];

    // ── 1. EMAIL ──────────────────────────────────────────────────────────
    try {
        $results['email'] = sendEmailAlert($leadData);
    } catch (Throwable $e) {
        $results['email'] = ['success' => false, 'error' => $e->getMessage()];
        AlertLogger::log('email_error', $leadData, $e->getMessage());
    }

    // ── 2. WHATSAPP ───────────────────────────────────────────────────────
    try {
        $results['whatsapp'] = sendWhatsAppAlert($leadData);
    } catch (Throwable $e) {
        $results['whatsapp'] = ['success' => false, 'error' => $e->getMessage()];
        AlertLogger::log('whatsapp_error', $leadData, $e->getMessage());
    }

    // ── 3. GOOGLE SHEET (optional) ────────────────────────────────────────
    if (ALERT_GOOGLE_SHEET_ENABLED) {
        try {
            $results['google_sheet'] = syncToGoogleSheet($leadData);
        } catch (Throwable $e) {
            $results['google_sheet'] = ['success' => false, 'error' => $e->getMessage()];
            AlertLogger::log('sheet_error', $leadData, $e->getMessage());
        }
    }

    // ── 4. LOG SUMMARY ────────────────────────────────────────────────────
    AlertLogger::log('alert_sent', $leadData, json_encode($results));

    return $results;
}
