<?php
/**
 * WealthMetre — WhatsApp Message System
 * Path: /api/crm/whatsapp_api.php
 *
 * No WhatsApp API needed — uses wa.me deep links.
 * Future-ready for WhatsApp Cloud API.
 *
 * Actions:
 *   get_templates         → list partner's WhatsApp templates
 *   save_template         → create/update template
 *   delete_template       → remove template
 *   get_message           → generate message for a lead + status
 *   log_message           → log that message was sent
 *   get_message_history   → messages sent for a lead
 *   get_wa_link           → full wa.me URL ready to open
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

$partner = authenticatePartner();
if (!$partner) jsonErr('Unauthorized', 401);
$pid = (int)($partner['id'] ?? $partner['user_id'] ?? 0);

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_templates':       getTemplates($pid);                  break;
    case 'save_template':       saveTemplate($body, $pid);           break;
    case 'delete_template':     deleteTemplate($body, $pid);         break;
    case 'get_message':         getMessage($body, $pid);             break;
    case 'log_message':         logMessage($body, $pid);             break;
    case 'get_message_history': getMessageHistory($body, $pid);      break;
    case 'get_wa_link':         getWaLink($body, $pid);              break;
    default: jsonErr('Invalid action');
}


// ════════════════════════════════════════════════════════
// TEMPLATES
// ════════════════════════════════════════════════════════

function getTemplates(int $pid): void {
    $pdo  = getDB();

    // Get partner's custom templates
    $stmt = $pdo->prepare("
        SELECT * FROM wa_templates
        WHERE partner_id = ? OR partner_id = 0
        ORDER BY partner_id DESC, trigger_status ASC
    ");
    $stmt->execute([$pid]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by status
    $grouped = [];
    foreach ($templates as $t) {
        $status = $t['trigger_status'];
        // Partner template overrides system default
        if (!isset($grouped[$status]) || $t['partner_id'] == $pid) {
            $grouped[$status] = $t;
        }
    }

    jsonOk([
        'templates' => array_values($grouped),
        'variables' => [
            '{name}'           => 'Customer ka naam',
            '{loan_type}'      => 'Loan ka type',
            '{city}'           => 'Customer ki city',
            '{amount}'         => 'Loan amount',
            '{telecaller}'     => 'Telecaller ka naam',
            '{partner_name}'   => 'Partner/Branch ka naam',
            '{followup_date}'  => 'Follow-up ki date',
            '{wm_phone}'       => 'WealthMetre phone number',
        ]
    ]);
}

function saveTemplate(array $b, int $pid): void {
    $status   = sanitize($b['trigger_status'] ?? '');
    $template = sanitize($b['template_text'] ?? '', 2000);
    $id       = intSafe($b['id'] ?? 0);

    $validStatuses = [
        'interested','follow_up','not_reachable','callback_later',
        'documents_pending','eligible','converted','custom_1','custom_2'
    ];
    if (!in_array($status, $validStatuses)) jsonErr('Invalid trigger status');
    if (!$template) jsonErr('Template text required');

    $pdo = getDB();
    if ($id) {
        // Update existing
        $pdo->prepare("
            UPDATE wa_templates SET template_text=?, is_active=?
            WHERE id=? AND partner_id=?
        ")->execute([$template, (int)($b['is_active'] ?? 1), $id, $pid]);
    } else {
        // Insert or update for this status
        $pdo->prepare("
            INSERT INTO wa_templates (partner_id, trigger_status, template_text, is_active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE template_text=VALUES(template_text), is_active=1
        ")->execute([$pid, $status, $template]);
    }

    jsonOk(['message' => 'Template saved']);
}

function deleteTemplate(array $b, int $pid): void {
    $id = intSafe($b['id'] ?? 0);
    $pdo = getDB();
    $pdo->prepare("DELETE FROM wa_templates WHERE id=? AND partner_id=?")->execute([$id, $pid]);
    jsonOk(['message' => 'Template deleted']);
}


// ════════════════════════════════════════════════════════
// MESSAGE GENERATION
// ════════════════════════════════════════════════════════

function getMessage(array $b, int $pid): void {
    $leadId = intSafe($b['lead_id'] ?? 0);
    $status = sanitize($b['status'] ?? '');
    if (!$leadId || !$status) jsonErr('lead_id and status required');

    $pdo = getDB();

    // Get lead details
    $lead = $pdo->prepare("
        SELECT cl.*, t.name AS telecaller_name, p.partner_name, p.phone AS partner_phone
        FROM calling_leads cl
        LEFT JOIN telecallers t ON t.id = cl.assigned_telecaller_id
        LEFT JOIN partners p ON p.id = cl.partner_id
        WHERE cl.id = ? AND cl.partner_id = ?
    ");
    $lead->execute([$leadId, $pid]);
    $l = $lead->fetch(PDO::FETCH_ASSOC);
    if (!$l) jsonErr('Lead not found', 404);

    // Get template — partner specific first, then system default
    $tmpl = $pdo->prepare("
        SELECT template_text FROM wa_templates
        WHERE (partner_id = ? OR partner_id = 0)
          AND trigger_status = ?
          AND is_active = 1
        ORDER BY partner_id DESC
        LIMIT 1
    ");
    $tmpl->execute([$pid, $status]);
    $templateText = $tmpl->fetchColumn();

    if (!$templateText) {
        $templateText = getDefaultTemplate($status);
    }

    // Replace variables
    $followupDate = '';
    if ($l['followup_at']) {
        $followupDate = date('d M Y h:i A', strtotime($l['followup_at']));
    }

    $amount = '';
    if ($l['loan_amount']) {
        $n = (int)$l['loan_amount'];
        if ($n >= 10000000)    $amount = '₹' . round($n/10000000, 1) . ' Crore';
        elseif ($n >= 100000)  $amount = '₹' . round($n/100000, 1) . ' Lakh';
        else                   $amount = '₹' . number_format($n);
    }

    $message = str_replace([
        '{name}', '{loan_type}', '{city}', '{amount}',
        '{telecaller}', '{partner_name}', '{followup_date}', '{wm_phone}'
    ], [
        $l['customer_name'] ?? '—',
        $l['loan_type'] ?? '—',
        $l['city'] ?? '—',
        $amount ?: '—',
        $l['telecaller_name'] ?? 'WealthMetre Team',
        $l['partner_name'] ?? 'WealthMetre',
        $followupDate ?: '—',
        '7976218596'
    ], $templateText);

    jsonOk([
        'message'    => $message,
        'customer'   => $l['customer_name'],
        'mobile'     => $l['mobile'],
        'wa_link'    => 'https://wa.me/91' . preg_replace('/\D/', '', $l['mobile']) .
                        '?text=' . rawurlencode($message),
    ]);
}

function getDefaultTemplate(string $status): string {
    $defaults = [
        'interested' =>
            "Namaste {name}! 🙏\nWealthMetre se {telecaller} bol rahe hain.\n\nAapne {loan_type} ke liye interest dikhaya — bahut badhiya! 🎉\n\nHamne aapki profile ke liye kuch best lender options dhundhe hain. Main aapko shortly details share karunga/karoungi.\n\nKoi bhi sawaal ho to iss number pe WhatsApp kar sakte hain.\n\n_WealthMetre Finserve | wealthmetre.com_",

        'follow_up' =>
            "Namaste {name}! 👋\nWealthMetre se {telecaller} bol rahe hain.\n\nAapke {loan_type} ke baare mein baat karni thi.\n📅 Follow-up: {followup_date}\n\nKya tab baat ho sakti hai?\n\n_WealthMetre Finserve_",

        'documents_pending' =>
            "Namaste {name}! 📋\nAapke {loan_type} ke liye kuch documents chahiye:\n\n✅ Aadhaar Card\n✅ PAN Card\n✅ Income Proof (Salary Slip / ITR)\n✅ Bank Statement (6 months)\n✅ Property Papers\n\nJitni jaldi documents milenge, utni jaldi loan process hoga! 🚀\n\n_WealthMetre Finserve_",

        'eligible' =>
            "🎊 Badhai ho {name}!\nAap {loan_type} ke liye eligible hain!\n\n💰 Loan Amount: {amount}\n\nHamara team aapse jald contact karega details ke liye.\n\n_WealthMetre Finserve | wealthmetre.com_",

        'converted' =>
            "🎉 Congratulations {name}!\nAapka {loan_type} approve ho gaya!\n\nHamari poori team ki taraf se bahut bahut badhai! 🎊\n\nKoi bhi sawaal ho: wa.me/91{wm_phone}\n\n_WealthMetre Finserve_",

        'not_reachable' =>
            "Namaste {name}! 📞\nWealthMetre se call ki thi, par connect nahi ho saka.\n\nAapke {loan_type} ke liye kuch important information share karni thi.\n\nKripya is number pe call ya WhatsApp karein.\n\n_WealthMetre Finserve_",

        'callback_later' =>
            "Namaste {name}! ⏰\nWealthMetre se {telecaller} bol rahe hain.\n\nAapne {followup_date} ko callback request ki thi.\nKya main abhi call kar sakta/sakti hoon?\n\n_WealthMetre Finserve_",
    ];

    return $defaults[$status] ?? "Namaste {name}! WealthMetre se aapke {loan_type} ke baare mein baat karni thi. Kripya contact karein. _WealthMetre Finserve_";
}

function logMessage(array $b, int $pid): void {
    // Log that WhatsApp was sent — for tracking
    $leadId = intSafe($b['lead_id'] ?? 0);
    $status = sanitize($b['status'] ?? '');
    if (!$leadId) jsonErr('lead_id required');

    $pdo = getDB();

    // Update last_wa_sent on lead (add column if not exists)
    try {
        $pdo->prepare("
            UPDATE calling_leads
            SET call_notes = CONCAT(COALESCE(call_notes,''), '\n[WA Sent: ', NOW(), ' - ', ?, ']')
            WHERE id=? AND partner_id=?
        ")->execute([$status, $leadId, $pid]);
    } catch (Exception $e) {}

    jsonOk(['logged' => true]);
}

function getMessageHistory(array $b, int $pid): void {
    // Basic implementation — reads from call_notes
    $leadId = intSafe($b['lead_id'] ?? $_GET['lead_id'] ?? 0);
    if (!$leadId) jsonErr('lead_id required');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT call_notes FROM calling_leads WHERE id=? AND partner_id=?");
    $stmt->execute([$leadId, $pid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $history = [];
    if ($row && $row['call_notes']) {
        preg_match_all('/\[WA Sent: ([^\]]+)\]/', $row['call_notes'], $matches);
        foreach ($matches[1] as $m) {
            $history[] = $m;
        }
    }
    jsonOk(['history' => $history]);
}

function getWaLink(array $b, int $pid): void {
    $leadId = intSafe($b['lead_id'] ?? 0);
    $status = sanitize($b['status'] ?? 'interested');
    if (!$leadId) jsonErr('lead_id required');

    // Reuse getMessage
    $b['lead_id'] = $leadId;
    $b['status']  = $status;
    getMessage($b, $pid);
}


// ════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════
function sanitize(string $v, int $max=255): string {
    return mb_substr(htmlspecialchars(trim($v), ENT_QUOTES,'UTF-8'), 0, $max);
}
function intSafe($v): int { return (int)preg_replace('/\D/','', (string)$v); }
function jsonOk(array $d): void { echo json_encode(array_merge(['status'=>'ok'],$d), JSON_UNESCAPED_UNICODE); exit; }
function jsonErr(string $m, int $c=400): void { http_response_code($c); echo json_encode(['status'=>'error','message'=>$m]); exit; }