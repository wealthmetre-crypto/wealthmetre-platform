<?php
/**
 * WealthMetre - agreement_api.php
 * Partner Agreement API: get_active, accept, log_audit
 */
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../helpers/AgreementHelper.php';

header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204); exit;
}

$body   = getJsonBody();
$action = sanitize($body['action'] ?? $_GET['action'] ?? '');

match($action) {
    'get_active'  => actionGetActive(),
    'accept'      => actionAccept($body),
    'log_audit'   => actionLogAudit($body),
    'check_status'=> actionCheckStatus(),
    default       => jsonError('Invalid action', 400),
};

// ── GET ACTIVE AGREEMENT ──────────────────────────────────────
function actionGetActive(): void {
    $pdo       = getDB();
    $partner   = getPartnerFromSession($pdo);
    if (!$partner) jsonError('Session expired. Please login again.', 401);

    $agreement = getActiveAgreement($pdo);
    if (!$agreement) {
        jsonError('Agreement not available. Please contact WealthMetre support at +91 79762 18596.', 503);
    }

    // Log viewed event
    logAgreementAudit($pdo, (int)$partner['id'], 'viewed', $agreement['version']);

    // Update onboarding step
    $pdo->prepare("UPDATE partners SET onboarding_step='agreement' WHERE id=?")
        ->execute([(int)$partner['id']]);

    jsonResponse([
        'success'   => true,
        'agreement' => [
            'version'       => $agreement['version'],
            'title'         => $agreement['title'],
            'summary'       => $agreement['summary'],
            'full_text'     => $agreement['full_text'],
            'checkbox_text' => $agreement['checkbox_text'],
            'text_hash'     => $agreement['text_hash'],
            'effective_from'=> $agreement['effective_from'],
        ],
        'csrf_token' => generateCsrfToken(),
    ]);
}

// ── ACCEPT AGREEMENT ─────────────────────────────────────────
function actionAccept(array $b): void {
    $pdo = getDB();

    // 1. Session check
    $partner = getPartnerFromSession($pdo);
    if (!$partner) jsonError('Session expired. Please login again.', 401);
    $partnerId = (int)$partner['id'];

    // 2. CSRF check
    $csrfToken = $b['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        jsonError('Security token expired. Please refresh and try again.', 403);
    }

    // 3. Rate limit
    $ip = getClientIpAddress();
    checkAgreementRateLimit($partnerId, $ip);

    // 4. Validate inputs
    $submittedVersion  = sanitize($b['agreement_version'] ?? '');
    $submittedHash     = sanitize($b['text_hash'] ?? '');
    $checkboxAccepted  = (bool)($b['checkbox_accepted'] ?? false);
    $scrollVerified    = (bool)($b['scroll_verified'] ?? false);

    if (!$checkboxAccepted) jsonError('Please accept the agreement before submitting.');
    if (!$scrollVerified)   jsonError('Please scroll through the complete agreement.');
    if (!$submittedVersion) jsonError('Agreement version missing. Please refresh and try again.');
    if (!$submittedHash)    jsonError('Agreement hash missing. Please refresh and try again.');

    // 5. Get active agreement
    $agreement = getActiveAgreement($pdo);
    if (!$agreement) jsonError('Agreement not available. Please contact WealthMetre support.', 503);

    // 6. Version + hash verify
    if ($submittedVersion !== $agreement['version']) {
        logAgreementAudit($pdo, $partnerId, 'failed', $submittedVersion,
            ['reason' => 'version_mismatch']);
        jsonError('Agreement version mismatch. Please refresh and try again.');
    }
    if ($submittedHash !== $agreement['text_hash']) {
        logAgreementAudit($pdo, $partnerId, 'failed', $submittedVersion,
            ['reason' => 'hash_mismatch']);
        jsonError('Agreement version mismatch. Please refresh and try again.');
    }

    // 7. Duplicate check
    $dup = $pdo->prepare("
        SELECT id FROM partner_agreements
        WHERE partner_id = ? AND agreement_version = ? AND status = 'accepted'
        LIMIT 1
    ");
    $dup->execute([$partnerId, $agreement['version']]);
    if ($dup->fetch()) {
        jsonResponse([
            'success'          => true,
            'message'          => 'You have already accepted this agreement.',
            'already_accepted' => true,
        ]);
    }

    // 8. Geo lookup (optional, non-blocking)
    $geo = getGeoFromIp($ip);

    // 9. Capture metadata
    $ua             = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $otpMobile      = $partner['mobile'] ?? null;
    $acceptedAt     = date('Y-m-d H:i:s'); // server-side always
    $checkboxText   = $agreement['checkbox_text'];

    // 10. DB Transaction
    try {
        $pdo->beginTransaction();

        // Supersede old accepted versions
        $pdo->prepare("
            UPDATE partner_agreements
            SET status = 'superseded',
                superseded_by_version = ?,
                superseded_at = NOW()
            WHERE partner_id = ?
              AND status = 'accepted'
              AND agreement_version != ?
        ")->execute([$agreement['version'], $partnerId, $agreement['version']]);

        // Insert new acceptance
        $ins = $pdo->prepare("
            INSERT INTO partner_agreements
                (partner_id, agreement_type, agreement_version,
                 agreement_title, agreement_text_hash, accepted_at,
                 ip_address, user_agent, otp_verified_mobile,
                 acceptance_method, checkbox_text, scroll_verified,
                 geo_city, geo_state, geo_country, status)
            VALUES
                (?, 'partner_onboarding', ?, ?, ?, ?,
                 ?, ?, ?, 'online_checkbox', ?, 1,
                 ?, ?, ?, 'accepted')
        ");
        $ins->execute([
            $partnerId,
            $agreement['version'],
            $agreement['title'],
            $agreement['text_hash'],
            $acceptedAt,
            $ip, $ua, $otpMobile,
            $checkboxText,
            $geo['city'], $geo['state'], $geo['country'],
        ]);
        $newAcceptanceId = (int)$pdo->lastInsertId();

        // Update partner record
        $pdo->prepare("
            UPDATE partners SET
                status                     = 'under_review',
                onboarding_step            = 'submitted',
                agreement_version_accepted = ?,
                agreement_accepted_at      = NOW(),
                agreement_acceptance_id    = ?,
                updated_at                 = NOW()
            WHERE id = ?
        ")->execute([$agreement['version'], $newAcceptanceId, $partnerId]);

        // Audit log
        logAgreementAudit($pdo, $partnerId, 'accepted', $agreement['version'],
            ['acceptance_id' => $newAcceptanceId, 'ip' => $ip]);

        $pdo->commit();

    } catch (Throwable $e) {
        $pdo->rollBack();
        logAgreementAudit($pdo, $partnerId, 'failed', $agreement['version'],
            ['reason' => 'db_error', 'error' => $e->getMessage()]);
        error_log('[AgreementAccept] ' . $e->getMessage());
        jsonError('Agreement acceptance failed. Please try again.', 500);
    }

    // 11. Notify (async — non-blocking)
    sendAgreementNotification((array)$partner);

    jsonResponse([
        'success' => true,
        'message' => 'Agreement accepted successfully. Your application is now under review.',
    ]);
}

// ── LOG AUDIT (from frontend scroll/checkbox events) ─────────
function actionLogAudit(array $b): void {
    $allowedFrontendActions = [
        'viewed','scrolled_start','scrolled_complete',
        'checkbox_checked','checkbox_unchecked','session_expired'
    ];
    $action = sanitize($b['action'] ?? '');
    if (!in_array($action, $allowedFrontendActions, true)) {
        jsonResponse(['success' => true]); // silent ignore
        return;
    }
    try {
        $pdo     = getDB();
        $partner = getPartnerFromSession($pdo);
        if ($partner) {
            $version = sanitize($b['agreement_version'] ?? '');
            logAgreementAudit($pdo, (int)$partner['id'], $action, $version ?: null);
        }
    } catch (Throwable $e) {
        // Silent fail for audit log
        error_log('[AuditLog] ' . $e->getMessage());
    }
    jsonResponse(['success' => true]);
}

// ── CHECK STATUS (dashboard access control) ──────────────────
function actionCheckStatus(): void {
    $pdo     = getDB();
    $partner = getPartnerFromSession($pdo);
    if (!$partner) jsonError('Session expired. Please login again.', 401);

    $agreement = getActiveAgreement($pdo);
    $activeVersion = $agreement['version'] ?? null;
    $partnerVersion = $partner['agreement_version_accepted'] ?? null;

    $needsAcceptance = !$partnerVersion
                    || !$activeVersion
                    || $partnerVersion !== $activeVersion;

    jsonResponse([
        'success'          => true,
        'needs_acceptance' => $needsAcceptance,
        'active_version'   => $activeVersion,
        'partner_version'  => $partnerVersion,
        'partner_status'   => $partner['status'],
        'onboarding_step'  => $partner['onboarding_step'],
    ]);
}
