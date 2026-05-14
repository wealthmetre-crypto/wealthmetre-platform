<?php
/**
 * WealthMetre — Diva Save API (stable version)
 * Uses website_leads as the primary lead table.
 * Avoids diva_leads/diva_leads_v2 dependency to prevent 500 errors.
 */

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../helpers/LeadAlert.php';
require_once __DIR__ . '/../config/alert_config.php';

/* -------------------------------------------------------
| Fallback helpers (only if not already defined)
-------------------------------------------------------- */
if (!function_exists('jsonResponse')) {
    function jsonResponse(array $data, int $status = 200): void {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('jsonError')) {
    function jsonError(string $message, int $status = 400, array $extra = []): void {
        http_response_code($status);
        echo json_encode(array_merge([
            'status' => 'error',
            'error'  => $message,
        ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('sanitize')) {
    function sanitize($value): string {
        if ($value === null) return '';
        if (is_array($value) || is_object($value)) return '';
        $value = trim((string)$value);
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('intSafe')) {
    function intSafe($value): int {
        if ($value === null || $value === '') return 0;
        return (int)preg_replace('/[^\d\-]/', '', (string)$value);
    }
}

if (!function_exists('getJsonBody')) {
    function getJsonBody(): array {
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}

/* -------------------------------------------------------
| Request validation
-------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('POST required', 405);
}

try {
    $b = getJsonBody();
} catch (Throwable $e) {
    jsonError('Invalid request body', 500, ['stage' => 'body', 'details' => $e->getMessage()]);
}

$action = sanitize($b['action'] ?? '');
$sid    = sanitize($b['session_id'] ?? '');

if ($action === '') {
    jsonError('action required', 422);
}

if ($sid === '') {
    jsonError('session_id required', 422);
}

try {
    switch ($action) {
        case 'start':
            actionStart($b, $sid);
            break;

        case 'save_contact':
            actionSaveContact($b, $sid);
            break;

        case 'update_profile':
            actionUpdateProfile($b, $sid);
            break;

        case 'save_results':
            actionSaveResults($b, $sid);
            break;

        case 'zero_results':
            actionZeroResults($b, $sid);
            break;

        default:
            jsonError('Invalid action', 422, ['action' => $action]);
    }
} catch (Throwable $e) {
    error_log('[diva_save] FATAL: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    jsonError('Internal server error', 500, [
        'stage'   => 'fatal',
        'details' => $e->getMessage(),
        'line'    => $e->getLine(),
    ]);
}

/* -------------------------------------------------------
| Actions
-------------------------------------------------------- */

function actionStart(array $b, string $sid): void
{
    // No DB dependency here. Just acknowledge widget start.
    jsonResponse([
        'status'     => 'ok',
        'action'     => 'start',
        'session_id' => $sid,
    ]);
}

function upsertDivaConversation(PDO $pdo, string $sid, array $data): void {
    // Check if session exists
    $stmt = $pdo->prepare("SELECT id FROM diva_conversations WHERE session_id=? LIMIT 1");
    $stmt->execute([$sid]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $sets = []; $vals = [];
        foreach ($data as $k => $v) { if($v !== null && $v !== '') { $sets[] = "$k=?"; $vals[] = $v; } }
        if (!empty($sets)) {
            $vals[] = $sid;
            $pdo->prepare("UPDATE diva_conversations SET ".implode(',',$sets).",updated_at=NOW() WHERE session_id=?")->execute($vals);
        }
    } else {
        $data['session_id'] = $sid;
        $data['created_at'] = date('Y-m-d H:i:s');
        $cols = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $pdo->prepare("INSERT INTO diva_conversations ($cols) VALUES ($placeholders)")->execute(array_values($data));
    }
}

function actionSaveContact(array $b, string $sid): void
{
    $pdo = getDB();

    $name   = sanitize($b['customer_name'] ?? $b['name'] ?? '');
    $mobile = preg_replace('/\D/', '', (string)($b['customer_mobile'] ?? $b['mobile'] ?? ''));
    $city   = sanitize($b['city'] ?? '');
    $loan   = sanitize($b['loan_type'] ?? '');
    $lang   = sanitize($b['lang'] ?? 'en');

    if ($name === '' || strlen($mobile) < 10) {
        jsonError('name and valid mobile required', 422, ['stage' => 'save_contact']);
    }

    $mobileLast10 = substr($mobile, -10);

    // Check if same mobile already exists today in website_leads
    $check = $pdo->prepare("
        SELECT id
        FROM website_leads
        WHERE phone = ?
          AND DATE(created_at) = CURDATE()
        ORDER BY id DESC
        LIMIT 1
    ");
    $check->execute([$mobileLast10]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $leadId = (int)$existing['id'];

        $update = $pdo->prepare("
            UPDATE website_leads
            SET
                name      = COALESCE(NULLIF(?, ''), name),
                loan_type = COALESCE(NULLIF(?, ''), loan_type),
                city      = COALESCE(NULLIF(?, ''), city),
                source    = 'diva_ai_v3'
            WHERE id = ?
        ");
        $update->execute([$name, $loan, $city, $leadId]);
    } else {
        $insert = $pdo->prepare("
            INSERT INTO website_leads
                (name, phone, email, loan_type, city, message, source, utm, created_at)
            VALUES
                (?, ?, '', ?, ?, ?, 'diva_ai_v3', ?, NOW())
        ");
        $message = 'Diva widget lead | SID: ' . $sid . ' | Lang: ' . $lang;
        $utm     = 'diva_widget';
        $insert->execute([$name, $mobileLast10, $loan, $city, $message, $utm]);
        $leadId = (int)$pdo->lastInsertId();
    }

    $leadData = [
        'id'         => $leadId,
        'session_id' => $sid,
        'name'       => $name,
        'mobile'     => $mobileLast10,
        'phone'      => $mobileLast10,
        'city'       => $city,
        'loan_type'  => $loan,
        'source'     => 'Diva AI',
        'alert_time' => date('d M Y, h:i A'),
    ];

    // Main alert trigger
    $alertResult = sendLeadAlert($leadData, 'Diva AI');

    error_log('[diva_save][save_contact] lead_id=' . $leadId . ' result=' . json_encode($alertResult));

    // Save to diva_conversations
    try {
        $name  = sanitize($b['name'] ?? $b['customer_name'] ?? '');
        $phone = sanitize($b['phone'] ?? $b['customer_mobile'] ?? '');
        $loan2 = sanitize($b['loan_type'] ?? '');
        $city2 = sanitize($b['city'] ?? '');
        upsertDivaConversation($pdo, $sid, [
            'customer_name'     => $name ?: null,
            'customer_mobile'   => strlen($phone)>=10 ? substr($phone,-10) : null,
            'loan_type'         => $loan2 ?: null,
            'city'              => $city2 ?: null,
            'status'            => 'contact_shared',
            'contact_shared_at' => date('Y-m-d H:i:s'),
            'source'            => 'diva_widget',
        ]);
    } catch(\Throwable $e) { error_log('[diva_save] conv_contact: '.$e->getMessage()); }
    jsonResponse([
        'status'       => 'ok',
        'action'       => 'save_contact',
        'lead_id'      => $leadId,
        'alert_result' => $alertResult,
    ]);
}

function actionUpdateProfile(array $b, string $sid): void
{
    $pdo     = getDB();
    $profile = is_array($b['profile'] ?? null) ? $b['profile'] : [];

    $mobile = preg_replace('/\D/', '', (string)($profile['mobile'] ?? $b['customer_mobile'] ?? ''));
    $city   = sanitize($profile['city'] ?? $b['city'] ?? '');
    $loan   = sanitize($profile['loan_type'] ?? $b['loan_type'] ?? '');
    $amount = intSafe($profile['loan_amount'] ?? $profile['amount'] ?? 0);
    $income = intSafe($profile['monthly_income'] ?? 0);
    $cibil  = intSafe($profile['cibil'] ?? $profile['cibil_score'] ?? 0);
    $occ    = sanitize($profile['occupation'] ?? '');
    $ptype  = sanitize($profile['property_title'] ?? $profile['property_type'] ?? '');

    // Try to update the latest matching website_leads row
    if (strlen($mobile) >= 10) {
        $mobile = substr($mobile, -10);

        $find = $pdo->prepare("
            SELECT id
            FROM website_leads
            WHERE phone = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $find->execute([$mobile]);
        $row = $find->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $extraMessage = "SID: {$sid}";
            if ($amount > 0) $extraMessage .= " | Loan Amount: {$amount}";
            if ($income > 0) $extraMessage .= " | Income: {$income}";
            if ($cibil > 0)  $extraMessage .= " | CIBIL: {$cibil}";
            if ($occ !== '') $extraMessage .= " | Occupation: {$occ}";
            if ($ptype !== '') $extraMessage .= " | Property: {$ptype}";

            $upd = $pdo->prepare("
                UPDATE website_leads
                SET
                    loan_type = COALESCE(NULLIF(?, ''), loan_type),
                    city      = COALESCE(NULLIF(?, ''), city),
                    message   = ?
                WHERE id = ?
            ");
            $upd->execute([$loan, $city, $extraMessage, (int)$row['id']]);
        }
    }

    // Update diva_conversations with profile
    try {
        upsertDivaConversation($pdo, $sid, [
            'loan_type'      => $loan ?: null,
            'city'           => $city ?: null,
            'loan_amount'    => $amount ?: null,
            'monthly_income' => $income ?: null,
            'cibil'          => $cibil ?: null,
            'occupation'     => $occ ?: null,
            'property_title' => $ptype ?: null,
            'status'         => 'in_progress',
        ]);
    } catch(\Throwable $e) { error_log('[diva_save] conv_profile: '.$e->getMessage()); }
    jsonResponse([
        'status'     => 'ok',
        'action'     => 'update_profile',
        'session_id' => $sid,
    ]);
}

function actionSaveResults(array $b, string $sid): void
{
    $total   = intSafe($b['total_matches'] ?? 0);
    $lenders = is_array($b['lenders'] ?? null) ? $b['lenders'] : [];
    $top     = $lenders[0]['lender_name'] ?? null;

    error_log('[diva_save][save_results] sid=' . $sid . ' total=' . $total . ' top=' . ($top ?? 'N/A'));

    // Save results to diva_conversations
    try {
        $pdo2 = getDB();
        upsertDivaConversation($pdo2, $sid, [
            'total_lenders_found' => $total,
            'top_lender'          => $top ?? null,
            'lender_results_json' => json_encode(array_slice($lenders, 0, 5)),
            'status'              => $total > 0 ? 'results_shown' : 'no_results',
            'results_shown_at'    => date('Y-m-d H:i:s'),
        ]);
    } catch(\Throwable $e) { error_log('[diva_save] conv_results: '.$e->getMessage()); }
    jsonResponse([
        'status'        => 'ok',
        'action'        => 'save_results',
        'session_id'    => $sid,
        'total_matches' => $total,
        'top_lender'    => $top,
    ]);
}

function actionZeroResults(array $b, string $sid): void
{
    error_log('[diva_save][zero_results] sid=' . $sid);

    jsonResponse([
        'status'     => 'ok',
        'action'     => 'zero_results',
        'session_id' => $sid,
    ]);
}