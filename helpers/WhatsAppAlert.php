<?php

/**
 * WealthMetre WhatsApp Alert
 * 
 * Supports two providers:
 *   1. Meta WhatsApp Cloud API (recommended — free, official)
 *   2. MSG91 (paid, simpler setup)
 * 
 * Configure provider in alert_config.php → WHATSAPP_PROVIDER
 */

/**
 * Send WhatsApp alert to admin number.
 *
 * @param array $leadData
 * @return array
 */
function sendWhatsAppAlert(array $leadData): array
{
    $provider = WHATSAPP_PROVIDER;

    return match ($provider) {
        'meta'  => sendViaMetaCloudAPI($leadData),
        'msg91' => sendViaMSG91($leadData),
        default => ['success' => false, 'error' => "Unknown provider: {$provider}"]
    };
}

// ═════════════════════════════════════════════════════════════════
// PROVIDER 1 — META WHATSAPP CLOUD API
// ═════════════════════════════════════════════════════════════════
//
// SETUP STEPS:
// 1. Go to https://developers.facebook.com
// 2. Create App → Business type
// 3. Add WhatsApp product
// 4. Get Phone Number ID and generate permanent token
// 5. Create a message template named 'lead_alert' in 
//    Meta Business Manager → WhatsApp Manager → Message Templates
// 6. Wait for template approval (usually 1-2 hours)
//
// TEMPLATE STRUCTURE for 'lead_alert':
// Header: 🚨 New Lead Alert — WealthMetre
// Body:
//   Source: {{1}}
//   Name: {{2}}
//   Mobile: {{3}}
//   Product: {{4}}
//   Amount: {{5}}
//   City: {{6}}
//   Time: {{7}}
// ═════════════════════════════════════════════════════════════════

function sendViaMetaCloudAPI(array $d): array
{
    $url = sprintf(
        'https://graph.facebook.com/%s/%s/messages',
        META_WA_API_VERSION,
        META_WA_PHONE_ID
    );

    $source     = $d['source']         ?? 'Unknown';
    $name       = $d['name']           ?? $d['full_name'] ?? 'N/A';
    $mobile     = $d['mobile']         ?? $d['phone'] ?? 'N/A';
    $product    = $d['loan_type']      ?? $d['product'] ?? 'N/A';
    $loanAmount = formatAmountWA($d['loan_amount']   ?? null);
    $city       = $d['city']           ?? 'N/A';
    $time       = $d['alert_time']     ?? date('d M Y, h:i A');

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => WHATSAPP_ADMIN_NUMBER,
        'type'              => 'template',
        'template'          => [
            'name'     => META_WA_TEMPLATE_NAME,
            'language' => ['code' => META_WA_TEMPLATE_LANG],
            'components' => [
                [
                    'type'       => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $source],
                        ['type' => 'text', 'text' => $name],
                        ['type' => 'text', 'text' => $mobile],
                        ['type' => 'text', 'text' => $product],
                        ['type' => 'text', 'text' => $loanAmount],
                        ['type' => 'text', 'text' => $city],
                        ['type' => 'text', 'text' => $time],
                    ]
                ]
            ]
        ]
    ];

    $response = makeHttpRequest($url, $payload, [
        'Authorization: Bearer ' . META_WA_TOKEN,
        'Content-Type: application/json'
    ]);

    if (isset($response['messages'][0]['id'])) {
        return [
            'success'    => true,
            'message_id' => $response['messages'][0]['id'],
            'provider'   => 'meta'
        ];
    }

    return [
        'success'  => false,
        'error'    => $response['error']['message'] ?? 'Meta API error',
        'response' => $response,
        'provider' => 'meta'
    ];
}

// ═════════════════════════════════════════════════════════════════
// PROVIDER 2 — MSG91
// ═════════════════════════════════════════════════════════════════
//
// SETUP STEPS:
// 1. Register at https://msg91.com
// 2. Go to WhatsApp → Templates → Create Template
// 3. Use the body format below
// 4. Copy your Auth Key and Template ID to alert_config.php
//
// TEMPLATE BODY:
//   🚨 New Lead Alert
//   Source: {{source}}
//   Name: {{name}}
//   Mobile: {{mobile}}
//   Product: {{product}}
//   Amount: {{amount}}
//   City: {{city}}
// ═════════════════════════════════════════════════════════════════

function sendViaMSG91(array $d): array
{
    $url = 'https://api.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/';

    $source     = $d['source']         ?? 'Unknown';
    $name       = $d['name']           ?? $d['full_name'] ?? 'N/A';
    $mobile     = $d['mobile']         ?? $d['phone'] ?? 'N/A';
    $product    = $d['loan_type']      ?? $d['product'] ?? 'N/A';
    $loanAmount = formatAmountWA($d['loan_amount']   ?? null);
    $city       = $d['city']           ?? 'N/A';

    $payload = [
        'integrated_number' => MSG91_SENDER_ID,
        'content_type'      => 'template',
        'payload'           => [
            'to'          => WHATSAPP_ADMIN_NUMBER,
            'type'        => 'template',
            'template'    => [
                'name'      => MSG91_TEMPLATE_ID,
                'language'  => ['code' => 'en'],
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $source],
                            ['type' => 'text', 'text' => $name],
                            ['type' => 'text', 'text' => $mobile],
                            ['type' => 'text', 'text' => $product],
                            ['type' => 'text', 'text' => $loanAmount],
                            ['type' => 'text', 'text' => $city],
                        ]
                    ]
                ]
            ]
        ]
    ];

    $response = makeHttpRequest($url, $payload, [
        'authkey: ' . MSG91_AUTH_KEY,
        'Content-Type: application/json'
    ]);

    if (($response['type'] ?? '') === 'success') {
        return ['success' => true, 'provider' => 'msg91', 'response' => $response];
    }

    return [
        'success'  => false,
        'error'    => $response['message'] ?? 'MSG91 error',
        'provider' => 'msg91',
        'response' => $response
    ];
}

// ═════════════════════════════════════════════════════════════════
// SHARED HTTP HELPER
// ═════════════════════════════════════════════════════════════════

function makeHttpRequest(string $url, array $payload, array $headers): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => ALERT_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => $error];
    }

    return json_decode($response, true) ?? ['raw' => $response];
}

/** Format amount for WhatsApp (plain text, no HTML) */
function formatAmountWA($amount): string
{
    if ($amount === null || $amount === '' || $amount === 0) return 'N/A';
    $amount = (float) $amount;

    if ($amount >= 10000000) return 'Rs.' . number_format($amount / 10000000, 2) . ' Cr';
    if ($amount >= 100000)   return 'Rs.' . number_format($amount / 100000, 2) . ' L';
    return 'Rs.' . number_format($amount, 0);
}
