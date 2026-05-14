<?php

/**
 * WealthMetre Alert System — Configuration
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to config/alert_config.php
 * 2. Fill in your actual credentials
 * 3. NEVER commit this file to Git — add to .gitignore
 */

// ═══════════════════════════════════════════════════════════════
// EMAIL SETTINGS (using Gmail SMTP)
// ═══════════════════════════════════════════════════════════════

define('ALERT_EMAIL_FROM', 'wealthmetre@gmail.com');
define('ALERT_EMAIL_FROM_NAME', 'WealthMetre Lead Alerts');
define('ALERT_EMAIL_TO', 'info@wealthmetre.com');

// Gmail SMTP — use App Password, NOT your regular Gmail password
// Generate at: https://myaccount.google.com/apppasswords
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USERNAME', 'wealthmetre@gmail.com');
define('SMTP_PASSWORD', 'qouqsjpafkcndvfi');
define('SMTP_SECURE',   'tls');

// ═══════════════════════════════════════════════════════════════
// WHATSAPP SETTINGS
// Choose ONE provider: 'meta' or 'msg91'
// ═══════════════════════════════════════════════════════════════

define('ALERT_WHATSAPP_ENABLED', false);
define('WHATSAPP_PROVIDER', 'meta'); // 'meta' or 'msg91'


// Admin WhatsApp number to receive alerts
define('WHATSAPP_ADMIN_NUMBER', '917976218596'); // country code + number, no +

// ── Meta Cloud API ────────────────────────────────────────────
// Get from: https://developers.facebook.com → WhatsApp → API Setup
define('META_WA_TOKEN',        'YOUR_META_PERMANENT_TOKEN_HERE');
define('META_WA_PHONE_ID',     'YOUR_PHONE_NUMBER_ID_HERE');
define('META_WA_API_VERSION',  'v19.0');

// Template name for lead alerts (must be approved in Meta Business Manager)
// See WhatsAppAlert.php for template structure
define('META_WA_TEMPLATE_NAME', 'lead_alert');
define('META_WA_TEMPLATE_LANG', 'en');

// ── MSG91 (alternative to Meta) ───────────────────────────────
// Get from: https://msg91.com/dashboard
define('MSG91_AUTH_KEY',    'YOUR_MSG91_AUTH_KEY_HERE');
define('MSG91_TEMPLATE_ID', 'YOUR_MSG91_TEMPLATE_ID_HERE');
define('MSG91_SENDER_ID',   'WLTHMT');

// ═══════════════════════════════════════════════════════════════
// GOOGLE SHEET SETTINGS (optional)
// ═══════════════════════════════════════════════════════════════

define('ALERT_GOOGLE_SHEET_ENABLED', true);

// Google Apps Script Web App URL
// See GoogleSheetAlert.php for setup instructions
define('GOOGLE_SHEET_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbzNMRA-AR_8juK2DjAYOvI71NYZUFygoC2_1a9syC09BWSHdmBCUHLg4AO7KMvyNPiD/exec');

// ═══════════════════════════════════════════════════════════════
// GENERAL SETTINGS
// ═══════════════════════════════════════════════════════════════

define('ALERT_LOG_PATH',   __DIR__ . '/../logs/alerts.log');
define('ALERT_TIMEOUT',    10); // HTTP request timeout in seconds
define('ALERT_DEBUG_MODE', false); // set true during testing to log full payloads




