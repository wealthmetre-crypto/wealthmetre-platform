<?php
/**
 * WealthMetre — Email Reports System
 * Path: /api/crm/email_reports.php
 *
 * Run via cron job OR manually from partner portal.
 *
 * Cron setup (add to server crontab):
 *   0 20 * * *  php /var/www/wealthmetre/public_html/api/crm/email_reports.php daily
 *   0 9  * * 1  php /var/www/wealthmetre/public_html/api/crm/email_reports.php weekly
 *
 * Manual trigger via API:
 *   POST /api/crm/email_reports.php
 *   { action: 'send_daily', partner_id: 1 }
 *   { action: 'send_weekly', partner_id: 1 }
 *   { action: 'send_test', partner_id: 1 }
 *
 * Requires: composer require phpmailer/phpmailer
 * Install:  cd /var/www/wealthmetre && composer require phpmailer/phpmailer
 */

// Handle cron vs HTTP request
$isCron = (php_sapi_name() === 'cli');

if (!$isCron) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
}

require_once __DIR__ . '/../../config/db.php';

// Load PHPMailer
$composerAutoload = __DIR__ . '/../../../vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    // PHPMailer not installed yet
    if ($isCron) {
        echo "PHPMailer not installed. Run: composer require phpmailer/phpmailer\n";
        exit(1);
    }
    jsonErr('Email system not configured. Run: composer require phpmailer/phpmailer');
}
require_once $composerAutoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// ── Email Config (move to .env in production) ──────────
define('SMTP_HOST',     getenv('SMTP_HOST')     ?: 'smtp.gmail.com');
define('SMTP_USER',     getenv('SMTP_USER')     ?: 'reports@wealthmetre.com');
define('SMTP_PASS',     getenv('SMTP_PASS')     ?: 'YOUR_APP_PASSWORD');
define('SMTP_PORT',     (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_FROM',     getenv('SMTP_FROM')     ?: 'reports@wealthmetre.com');
define('SMTP_FROM_NAME',getenv('SMTP_FROM_NAME')?: 'WealthMetre Reports');


// ════════════════════════════════════════════════════════
// ENTRY POINT
// ════════════════════════════════════════════════════════

if ($isCron) {
    $type = $argv[1] ?? 'daily';
    if ($type === 'daily')  sendDailyToAll();
    if ($type === 'weekly') sendWeeklyToAll();
    exit(0);
}

// HTTP mode — require auth
require_once __DIR__ . '/../../config/auth.php';
$partner = authenticatePartner();
if (!$partner) jsonErr('Unauthorized', 401);
$pid = (int)($partner['id'] ?? $partner['user_id'] ?? 0);

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'send_daily':   sendDailyReport($pid);   break;
    case 'send_weekly':  sendWeeklyReport($pid);  break;
    case 'send_test':    sendTestEmail($pid);      break;
    default: jsonErr('Invalid action');
}


// ════════════════════════════════════════════════════════
// SEND TO ALL PARTNERS (cron)
// ════════════════════════════════════════════════════════

function sendDailyToAll(): void {
    $pdo  = getDB();
    $stmt = $pdo->query("SELECT id FROM partners WHERE status = 'active'");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
        try { sendDailyReport((int)$pid); }
        catch (Exception $e) { echo "Error partner $pid: " . $e->getMessage() . "\n"; }
        sleep(2); // Rate limit
    }
}

function sendWeeklyToAll(): void {
    $pdo  = getDB();
    $stmt = $pdo->query("SELECT id FROM partners WHERE status = 'active'");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
        try { sendWeeklyReport((int)$pid); }
        catch (Exception $e) { echo "Error partner $pid: " . $e->getMessage() . "\n"; }
        sleep(3);
    }
}


// ════════════════════════════════════════════════════════
// DAILY REPORT
// ════════════════════════════════════════════════════════

function sendDailyReport(int $pid): void {
    $pdo     = getDB();
    $partner = getPartner($pid, $pdo);
    if (!$partner || !$partner['email']) {
        if (php_sapi_name() !== 'cli') jsonErr('Partner has no email address');
        return;
    }

    $today = date('Y-m-d');
    $data  = getDailyData($pid, $today, $pdo);

    // Don't send if no activity
    if ($data['calls_today'] == 0 && php_sapi_name() === 'cli') return;

    $subject = "📊 WealthMetre Daily Report — " . date('d M Y');
    $html    = buildDailyHTML($partner, $data, $today);

    $sent = sendEmail($partner['email'], $subject, $html);
    logReport($pid, 'daily', $today, $partner['email'], $sent, $pdo);

    if (php_sapi_name() !== 'cli') {
        jsonOk(['sent' => $sent, 'to' => $partner['email']]);
    }
}

function getDailyData(int $pid, string $date, PDO $pdo): array {
    // Today's calling stats
    $calls = $pdo->prepare("
        SELECT
            COUNT(*)                               AS total_calls,
            SUM(outcome = 'interested')            AS interested,
            SUM(outcome = 'follow_up')             AS followup,
            SUM(outcome = 'converted')             AS converted,
            SUM(outcome = 'not_reachable')         AS not_reachable,
            SUM(outcome = 'not_interested')        AS not_interested,
            AVG(call_duration_sec)                 AS avg_duration
        FROM call_logs
        WHERE partner_id = ? AND DATE(created_at) = ?
    ");
    $calls->execute([$pid, $date]);
    $callStats = $calls->fetch(PDO::FETCH_ASSOC);

    // Pending leads
    $pending = $pdo->prepare("
        SELECT COUNT(*) FROM calling_leads
        WHERE partner_id = ? AND current_status = 'pending'
    ");
    $pending->execute([$pid]);

    // Follow-ups due tomorrow
    $fuTomorrow = $pdo->prepare("
        SELECT COUNT(*) FROM calling_leads
        WHERE partner_id = ? AND followup_done = 0
          AND DATE(followup_at) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ");
    $fuTomorrow->execute([$pid]);

    // Per-telecaller performance today
    $tcPerf = $pdo->prepare("
        SELECT t.name,
               COUNT(cl.id) AS calls,
               SUM(cl.outcome = 'interested') AS interested
        FROM call_logs cl
        JOIN telecallers t ON t.id = cl.telecaller_id
        WHERE cl.partner_id = ? AND DATE(cl.created_at) = ?
        GROUP BY t.id
        ORDER BY calls DESC
    ");
    $tcPerf->execute([$pid, $date]);

    return array_merge($callStats, [
        'pending_leads'    => (int)$pending->fetchColumn(),
        'followups_tomorrow'=> (int)$fuTomorrow->fetchColumn(),
        'telecallers'      => $tcPerf->fetchAll(PDO::FETCH_ASSOC),
        'calls_today'      => (int)($callStats['total_calls'] ?? 0),
    ]);
}

function buildDailyHTML(array $partner, array $data, string $date): string {
    $tcRows = '';
    foreach (($data['telecallers'] ?? []) as $tc) {
        $tcRows .= "<tr>
            <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb'>{$tc['name']}</td>
            <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:700;color:#1e3a8a'>{$tc['calls']}</td>
            <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:700;color:#16a34a'>{$tc['interested']}</td>
        </tr>";
    }
    if (!$tcRows) $tcRows = "<tr><td colspan='3' style='padding:12px;color:#6b7280;text-align:center'>No calls today</td></tr>";

    $avgDur = '';
    if ($data['avg_duration'] > 0) {
        $secs   = (int)$data['avg_duration'];
        $avgDur = floor($secs/60) . 'm ' . ($secs%60) . 's';
    }

    return "<!DOCTYPE html>
<html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f0f4ff;font-family:Plus Jakarta Sans,Arial,sans-serif'>
<div style='max-width:600px;margin:0 auto;padding:20px'>

  <!-- Header -->
  <div style='background:linear-gradient(135deg,#0f2060,#1e3a8a);border-radius:16px;
              padding:24px;text-align:center;margin-bottom:16px'>
    <div style='font-size:24px;font-weight:800;color:#fff'>WealthMetre</div>
    <div style='font-size:13px;color:rgba(255,255,255,.6);margin-top:4px'>Daily Report — " . date('d M Y', strtotime($date)) . "</div>
    <div style='font-size:14px;color:rgba(255,255,255,.8);margin-top:8px'>
      Namaste, " . htmlspecialchars($partner['partner_name']) . "! 🙏
    </div>
  </div>

  <!-- Stats Grid -->
  <div style='display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px'>
    " . statBox('Calls Today', $data['total_calls'] ?? 0, '#1e3a8a', '#eff4ff') . "
    " . statBox('Interested', $data['interested'] ?? 0, '#16a34a', '#f0fdf4') . "
    " . statBox('Converted', $data['converted'] ?? 0, '#7c3aed', '#faf5ff') . "
    " . statBox('Follow-ups', $data['followup'] ?? 0, '#d97706', '#fffbeb') . "
    " . statBox('Not Reachable', $data['not_reachable'] ?? 0, '#6b7280', '#f9fafb') . "
    " . statBox('Avg Duration', $avgDur ?: '—', '#0ea5e9', '#f0f9ff') . "
  </div>

  <!-- Info boxes -->
  <div style='display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px'>
    <div style='background:#fff;border-radius:12px;padding:14px;border:1px solid #e5e7eb'>
      <div style='font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:5px'>Pending Leads</div>
      <div style='font-size:28px;font-weight:800;color:#d97706'>{$data['pending_leads']}</div>
    </div>
    <div style='background:#fff;border-radius:12px;padding:14px;border:1px solid #e5e7eb'>
      <div style='font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:5px'>Follow-ups Tomorrow</div>
      <div style='font-size:28px;font-weight:800;color:#1e3a8a'>{$data['followups_tomorrow']}</div>
    </div>
  </div>

  <!-- Telecaller Table -->
  <div style='background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;margin-bottom:16px'>
    <div style='padding:14px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;
                font-size:13px;font-weight:800;color:#1a1a2e'>
      👥 Telecaller Performance
    </div>
    <table style='width:100%;border-collapse:collapse'>
      <thead>
        <tr style='background:#f0f4ff'>
          <th style='padding:10px 12px;text-align:left;font-size:11px;color:#6b7280;font-weight:700'>TELECALLER</th>
          <th style='padding:10px 12px;text-align:left;font-size:11px;color:#6b7280;font-weight:700'>CALLS</th>
          <th style='padding:10px 12px;text-align:left;font-size:11px;color:#6b7280;font-weight:700'>INTERESTED</th>
        </tr>
      </thead>
      <tbody>$tcRows</tbody>
    </table>
  </div>

  <!-- CTA -->
  <div style='text-align:center;margin-bottom:16px'>
    <a href='https://wealthmetre.com/partner_portal.html'
       style='display:inline-block;padding:14px 28px;border-radius:50px;
              background:linear-gradient(135deg,#1e3a8a,#1e4db7);
              color:#fff;text-decoration:none;font-weight:700;font-size:14px'>
      📊 Open Partner Dashboard →
    </a>
  </div>

  <!-- Footer -->
  <div style='text-align:center;font-size:11px;color:#9ca3af;padding:10px'>
    WealthMetre Finserve | wealthmetre.com | +91 7976218596<br>
    <a href='#' style='color:#9ca3af'>Unsubscribe from reports</a>
  </div>

</div>
</body></html>";
}

function statBox(string $label, $value, string $color, string $bg): string {
    return "<div style='background:$bg;border-radius:12px;padding:14px;text-align:center'>
        <div style='font-size:22px;font-weight:800;color:$color'>$value</div>
        <div style='font-size:10px;font-weight:700;color:#6b7280;margin-top:3px'>$label</div>
    </div>";
}


// ════════════════════════════════════════════════════════
// WEEKLY REPORT
// ════════════════════════════════════════════════════════

function sendWeeklyReport(int $pid): void {
    $pdo     = getDB();
    $partner = getPartner($pid, $pdo);
    if (!$partner || !$partner['email']) return;

    $week    = date('Y-\WW');
    $weekStart = date('d M', strtotime('monday this week'));
    $weekEnd   = date('d M Y', strtotime('sunday this week'));

    $data    = getWeeklyData($pid, $pdo);
    $subject = "📈 WealthMetre Weekly Report — $weekStart to $weekEnd";
    $html    = buildWeeklyHTML($partner, $data, $weekStart, $weekEnd);

    // Generate CSV attachment
    $csv  = generateWeeklyCSV($pid, $pdo);
    $sent = sendEmail($partner['email'], $subject, $html, [
        ['content' => $csv, 'name' => 'wm_weekly_report_' . date('d-M-Y') . '.csv']
    ]);

    logReport($pid, 'weekly', $week, $partner['email'], $sent, $pdo);

    if (php_sapi_name() !== 'cli') {
        jsonOk(['sent' => $sent, 'to' => $partner['email']]);
    }
}

function getWeeklyData(int $pid, PDO $pdo): array {
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd   = date('Y-m-d', strtotime('sunday this week'));

    // Overall weekly stats
    $overall = $pdo->prepare("
        SELECT
            COUNT(*)                        AS total_calls,
            SUM(outcome='interested')       AS interested,
            SUM(outcome='converted')        AS converted,
            SUM(outcome='follow_up')        AS followup,
            SUM(outcome='not_interesting')  AS not_interested
        FROM call_logs
        WHERE partner_id = ?
          AND DATE(created_at) BETWEEN ? AND ?
    ");
    $overall->execute([$pid, $weekStart, $weekEnd]);
    $stats = $overall->fetch(PDO::FETCH_ASSOC);

    // Telecaller scores this week
    $scores = $pdo->prepare("
        SELECT
            t.name,
            SUM(ts.total_calls)       AS calls,
            SUM(ts.interested_count)  AS interested,
            SUM(ts.converted_count)   AS converted,
            SUM(ts.missed_followups)  AS missed,
            SUM(ts.score)             AS score
        FROM telecallers t
        LEFT JOIN telecaller_scores ts ON ts.telecaller_id = t.id
                                      AND ts.score_date BETWEEN ? AND ?
        WHERE t.partner_id = ? AND t.status = 'active'
        GROUP BY t.id
        ORDER BY score DESC
    ");
    $scores->execute([$weekStart, $weekEnd, $pid]);
    $tcScores = $scores->fetchAll(PDO::FETCH_ASSOC);

    // Total leads in pipeline
    $pipeline = $pdo->prepare("
        SELECT current_status, COUNT(*) as count
        FROM calling_leads
        WHERE partner_id = ?
        GROUP BY current_status
    ");
    $pipeline->execute([$pid]);

    return [
        'stats'     => $stats,
        'tc_scores' => $tcScores,
        'champion'  => $tcScores[0] ?? null,
        'pipeline'  => $pipeline->fetchAll(PDO::FETCH_ASSOC),
        'week_start'=> $weekStart,
        'week_end'  => $weekEnd,
    ];
}

function buildWeeklyHTML(array $partner, array $data, string $start, string $end): string {
    $stats    = $data['stats'] ?? [];
    $champion = $data['champion'];
    $tcRows   = '';

    foreach (($data['tc_scores'] ?? []) as $i => $tc) {
        $medal = $i === 0 ? '🏆' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#'.($i+1)));
        $tcRows .= "<tr>
            <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;font-size:16px'>$medal</td>
            <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;font-weight:700'>{$tc['name']}</td>
            <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:center;color:#1e3a8a;font-weight:700'>{$tc['calls']}</td>
            <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:center;color:#16a34a;font-weight:700'>{$tc['interested']}</td>
            <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:center;color:#7c3aed;font-weight:700'>{$tc['converted']}</td>
            <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:center'>
                <span style='background:#eff4ff;color:#1e3a8a;padding:3px 10px;border-radius:50px;font-weight:800;font-size:13px'>
                    " . round((float)($tc['score'] ?? 0)) . "
                </span>
            </td>
        </tr>";
    }

    return "<!DOCTYPE html>
<html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f0f4ff;font-family:Arial,sans-serif'>
<div style='max-width:600px;margin:0 auto;padding:20px'>

  <!-- Header -->
  <div style='background:linear-gradient(135deg,#0f2060,#1e3a8a);border-radius:16px;padding:28px;text-align:center;margin-bottom:16px'>
    <div style='font-size:26px;font-weight:800;color:#fff'>WealthMetre</div>
    <div style='font-size:13px;color:rgba(255,255,255,.6);margin-top:4px'>Weekly Report — $start to $end</div>
    <div style='font-size:14px;color:rgba(255,255,255,.8);margin-top:8px'>Namaste, " . htmlspecialchars($partner['partner_name']) . "! 🙏</div>
  </div>

  " . ($champion ? "
  <!-- Champion -->
  <div style='background:linear-gradient(135deg,#ff9800,#f57c00);border-radius:12px;padding:18px;
              text-align:center;margin-bottom:16px;color:#fff'>
    <div style='font-size:28px;margin-bottom:6px'>🏆</div>
    <div style='font-size:11px;font-weight:700;opacity:.8;text-transform:uppercase;margin-bottom:4px'>Star of the Week</div>
    <div style='font-size:22px;font-weight:800'>{$champion['name']}</div>
    <div style='font-size:13px;opacity:.85;margin-top:4px'>
      {$champion['calls']} calls · {$champion['interested']} interested · " . round((float)($champion['score'] ?? 0)) . " points
    </div>
  </div>" : '') . "

  <!-- Weekly Stats -->
  <div style='display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px'>
    " . statBox('Total Calls', $stats['total_calls'] ?? 0, '#1e3a8a', '#eff4ff') . "
    " . statBox('Interested', $stats['interested'] ?? 0, '#16a34a', '#f0fdf4') . "
    " . statBox('Converted', $stats['converted'] ?? 0, '#7c3aed', '#faf5ff') . "
    " . statBox('Follow-ups', $stats['followup'] ?? 0, '#d97706', '#fffbeb') . "
  </div>

  <!-- Leaderboard -->
  <div style='background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;margin-bottom:16px'>
    <div style='padding:14px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:13px;font-weight:800'>
      📊 Team Leaderboard
    </div>
    <table style='width:100%;border-collapse:collapse'>
      <thead>
        <tr style='background:#f0f4ff'>
          <th style='padding:8px 12px;text-align:left;font-size:11px;color:#6b7280'>#</th>
          <th style='padding:8px 12px;text-align:left;font-size:11px;color:#6b7280'>TELECALLER</th>
          <th style='padding:8px 12px;text-align:center;font-size:11px;color:#6b7280'>CALLS</th>
          <th style='padding:8px 12px;text-align:center;font-size:11px;color:#16a34a'>INTEREST</th>
          <th style='padding:8px 12px;text-align:center;font-size:11px;color:#7c3aed'>CONVERT</th>
          <th style='padding:8px 12px;text-align:center;font-size:11px;color:#1e3a8a'>SCORE</th>
        </tr>
      </thead>
      <tbody>$tcRows</tbody>
    </table>
  </div>

  <!-- Note about attachment -->
  <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;
              margin-bottom:16px;font-size:13px;color:#166534'>
    📎 Complete lead data with all call details is attached as Excel/CSV.
  </div>

  <!-- CTA -->
  <div style='text-align:center;margin-bottom:16px'>
    <a href='https://wealthmetre.com/partner_portal.html'
       style='display:inline-block;padding:14px 28px;border-radius:50px;
              background:linear-gradient(135deg,#1e3a8a,#1e4db7);
              color:#fff;text-decoration:none;font-weight:700;font-size:14px'>
      View Full Dashboard →
    </a>
  </div>

  <div style='text-align:center;font-size:11px;color:#9ca3af;padding:10px'>
    WealthMetre Finserve | wealthmetre.com | +91 7976218596
  </div>

</div>
</body></html>";
}

function generateWeeklyCSV(int $pid, PDO $pdo): string {
    $weekStart = date('Y-m-d', strtotime('monday this week'));

    $stmt = $pdo->prepare("
        SELECT
            cl.customer_name     AS 'Customer Name',
            cl.mobile            AS 'Mobile',
            cl.city              AS 'City',
            cl.loan_type         AS 'Loan Type',
            cl.loan_amount       AS 'Loan Amount',
            cl.cibil             AS 'CIBIL',
            t.name               AS 'Assigned To',
            cl.current_status    AS 'Status',
            cl.call_count        AS 'Total Calls',
            cl.call_notes        AS 'Notes',
            cl.last_called_at    AS 'Last Called',
            cl.followup_at       AS 'Follow-up Date'
        FROM calling_leads cl
        LEFT JOIN telecallers t ON t.id = cl.assigned_telecaller_id
        WHERE cl.partner_id = ?
          AND cl.current_status != 'pending'
        ORDER BY cl.current_status, cl.updated_at DESC
    ");
    $stmt->execute([$pid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) return '';

    $headers = array_keys($rows[0]);
    $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    $csv .= implode(',', $headers) . "\n";
    foreach ($rows as $row) {
        $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"', $row)) . "\n";
    }
    return $csv;
}


// ════════════════════════════════════════════════════════
// SEND EMAIL
// ════════════════════════════════════════════════════════

function sendEmail(string $to, string $subject, string $html, array $attachments = []): bool {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo('noreply@wealthmetre.com', 'WealthMetre');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags($html);

        foreach ($attachments as $att) {
            if (!empty($att['content'])) {
                $mail->addStringAttachment(
                    $att['content'],
                    $att['name'] ?? 'report.csv',
                    'base64',
                    'text/csv'
                );
            }
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email failed to ' . $to . ': ' . $e->getMessage());
        return false;
    }
}


// ════════════════════════════════════════════════════════
// TEST EMAIL
// ════════════════════════════════════════════════════════

function sendTestEmail(int $pid): void {
    $pdo     = getDB();
    $partner = getPartner($pid, $pdo);
    if (!$partner || !$partner['email']) jsonErr('No email address set for partner');

    $html = "
    <div style='font-family:Arial;max-width:500px;margin:0 auto;padding:20px'>
      <div style='background:#1e3a8a;border-radius:12px;padding:20px;text-align:center;color:#fff;margin-bottom:16px'>
        <div style='font-size:20px;font-weight:800'>WealthMetre</div>
        <div style='font-size:13px;opacity:.7;margin-top:4px'>Email System Test</div>
      </div>
      <p>Namaste " . htmlspecialchars($partner['partner_name']) . "! 🙏</p>
      <p>Your WealthMetre email reports are now configured and working correctly.</p>
      <p><strong>You will receive:</strong></p>
      <ul>
        <li>📊 Daily report at 8:00 PM</li>
        <li>📈 Weekly report every Monday at 9:00 AM</li>
        <li>🏆 Weekly leaderboard with champion announcement</li>
      </ul>
      <p style='color:#6b7280;font-size:12px;margin-top:20px'>WealthMetre Finserve | wealthmetre.com</p>
    </div>";

    $sent = sendEmail($partner['email'], '✅ WealthMetre Email Test — Working!', $html);
    jsonOk(['sent' => $sent, 'to' => $partner['email'], 'message' => $sent ? 'Test email sent!' : 'Email failed — check SMTP config']);
}


// ════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════

function getPartner(int $pid, PDO $pdo): ?array {
    $stmt = $pdo->prepare("SELECT * FROM partners WHERE id=? AND status='active' LIMIT 1");
    $stmt->execute([$pid]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function logReport(int $pid, string $type, string $period, string $to, bool $sent, PDO $pdo): void {
    try {
        $pdo->prepare("
            INSERT INTO email_reports (partner_id, report_type, report_period, sent_to, status, sent_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE status=VALUES(status), sent_at=NOW()
        ")->execute([$pid, $type, $period, $to, $sent ? 'sent' : 'failed']);
    } catch (Exception $e) {}
}

function jsonOk(array $d): void { echo json_encode(array_merge(['status'=>'ok'],$d), JSON_UNESCAPED_UNICODE); exit; }
function jsonErr(string $m, int $c=400): void { http_response_code($c); echo json_encode(['status'=>'error','message'=>$m]); exit; }