<?php
/**
 * WealthMetre – save_lead.php
 * Accepts POST JSON from homepage forms (hero, popup, contact)
 * Saves to DB → sends WhatsApp notification → sends email alert
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://wealthmetre.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'POST only']); exit; }

$body   = getJsonBody();
$source = sanitize($body['source']   ?? 'website');
$name   = sanitize($body['name']     ?? '');
$phone  = preg_replace('/\D/', '', $body['phone'] ?? '');
$email  = filter_var($body['email']  ?? '', FILTER_SANITIZE_EMAIL);
$loan   = sanitize($body['loan_type']?? '');
$city   = sanitize($body['city']     ?? '');
$msg    = sanitize($body['message']  ?? '');
$utm    = sanitize($body['utm']      ?? '');

// Validate
if (strlen($phone) < 10) {
    echo json_encode(['ok'=>false,'error'=>'Valid phone number required']); exit;
}

// ── 1. Save to DB ──────────────────────────────────────────
$leadId = 0;
try {
    $pdo = getDB();
    $pdo->prepare("
        INSERT INTO website_leads
          (name, phone, email, loan_type, city, message, source, utm, created_at)
        VALUES (?,?,?,?,?,?,?,?,NOW())
    ")->execute([$name, $phone, $email, $loan, $city, $msg, $source, $utm]);
    $leadId = (int)$pdo->lastInsertId();
} catch (\Throwable $e) {
    error_log('[save_lead] DB error: ' . $e->getMessage());
}

// ── 2. HTML Email + Google Sheet alert ────────────────────
// Fires async via trigger_alert.php — zero dependency here
if ($leadId > 0) {
    fireLeadAlert([
        'id'         => $leadId,
        'name'       => $name,
        'mobile'     => $phone,
        'city'       => $city,
        'loan_type'  => $loan,
        'source'     => $source,
        'alert_time' => date('d M Y, h:i A'),
    ]);
}

// ── 3. WhatsApp notification to team ──────────────────────
$waMsg = "🔔 *New Lead – WealthMetre*\n"
       . "Name: {$name}\n"
       . "Phone: {$phone}\n"
       . ($loan ? "Loan: {$loan}\n" : '')
       . ($city ? "City: {$city}\n" : '')
       . ($msg  ? "Note: {$msg}\n"  : '')
       . "Source: {$source}\n"
       . "LeadID: #{$leadId}";
sendWhatsAppText('7976218596', $waMsg);

// ── 4. Basic plain text email (fallback) ──────────────────
$subject   = "New Loan Lead: {$name} ({$loan}) – WealthMetre";
$emailBody = "New lead from website\n\n"
           . "Name:   {$name}\n"
           . "Phone:  {$phone}\n"
           . "Email:  {$email}\n"
           . "Loan:   {$loan}\n"
           . "City:   {$city}\n"
           . "Source: {$source}\n"
           . "Note:   {$msg}\n";
@mail('wealthmetre@gmail.com', $subject, $emailBody,
    "From: alerts@wealthmetre.com\r\nContent-Type: text/plain; charset=utf-8\r\n");

// ── 5. Auto-reply WhatsApp to customer ────────────────────
if (strlen($phone) >= 10) {
    $reply = "Hi {$name}! 🙏 Thank you for reaching out to *WealthMetre*.\n\n"
           . "We've received your loan enquiry for *{$loan}* in {$city}. "
           . "Our expert advisor will call you within 30 minutes.\n\n"
           . "Meanwhile, you can chat with Diva AI for instant lender matching:\n"
           . "🌐 wealthmetre.com\n\n"
           . "— Team WealthMetre\n+91 7976218596";
    sendWhatsAppText($phone, $reply);
}

echo json_encode(['ok' => true, 'lead_id' => $leadId]);


// ── HELPERS ────────────────────────────────────────────────

/**
 * Fire lead alert via trigger_alert.php (async, zero dependency)
 */
function fireLeadAlert(array $data): void
{
    try {
        $payload = json_encode(array_merge($data, [
            'secret' => 'wm_alert_2024',
            'source' => 'Partner Portal',
        ]));

        $ch = curl_init('https://wealthmetre.com/api/trigger_alert.php');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 1, // fire and forget
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);
    } catch (\Throwable $e) {
        error_log('[save_lead] fireLeadAlert: ' . $e->getMessage());
    }
}

function sendWhatsAppText(string $toMobile, string $message): void
{
    if (!defined('MSG91_AUTH_KEY') || !MSG91_AUTH_KEY) return;

    $toMobile = preg_replace('/\D/', '', $toMobile);
    if (strlen($toMobile) === 10) $toMobile = '91' . $toMobile;

    $payload = json_encode([
        'integrated_number' => defined('MSG91_WHATSAPP_FROM_NUMBER') ? MSG91_WHATSAPP_FROM_NUMBER : '917976218596',
        'content_type'      => 'text',
        'payload'           => [
            'to'   => $toMobile,
            'type' => 'text',
            'text' => ['body' => $message],
        ],
    ]);

    $ch = curl_init('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'authkey: ' . MSG91_AUTH_KEY],
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    if (!$result) error_log('[save_lead] WhatsApp send failed to ' . $toMobile);
}
