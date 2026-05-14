<?php
/**
 * WealthMetre – lender_share_api.php
 * Track which lenders a case was shared with, and their quotes.
 * POST/GET /api/partner/lender_share_api.php
 * action = get | share | save_quote | update_status
 */
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/partner_auth.php';

$body    = getJsonBody();
$action  = sanitize($body['action'] ?? $_GET['action'] ?? '');
$partner = requireAuth();

match ($action) {
    'get'           => getShares($partner),
    'share'         => shareWithLender($body, $partner),
    'save_quote'    => saveQuote($body, $partner),
    'update_status' => updateShareStatus($body, $partner),
    default         => jsonError('Invalid action')
};

// ── Get All Lender Shares for a Lead ──────────────────────
function getShares(array $partner): void {
    $leadId = intSafe($_GET['lead_id'] ?? 0);
    if (!$leadId) jsonError('lead_id required');

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT * FROM lender_shares
        WHERE lead_id = ? AND partner_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$leadId, $partner['id']]);
    jsonResponse(['status' => 'ok', 'shares' => $stmt->fetchAll()]);
}

// ── Share Case with a Lender ──────────────────────────────
function shareWithLender(array $b, array $partner): void {
    $leadId     = intSafe($b['lead_id']    ?? 0);
    $lenderName = sanitize($b['lender_name'] ?? '');

    if (!$leadId)     jsonError('lead_id required');
    if (!$lenderName) jsonError('lender_name required');

    $pdo = getDB();

    // Verify ownership
    $chk = $pdo->prepare("SELECT id FROM leads WHERE id = ? AND partner_id = ?");
    $chk->execute([$leadId, $partner['id']]);
    if (!$chk->fetch()) jsonError('Lead not found', 404);

    // Prevent duplicate share
    $dup = $pdo->prepare("SELECT id FROM lender_shares WHERE lead_id = ? AND lender_name = ?");
    $dup->execute([$leadId, $lenderName]);
    if ($dup->fetch()) {
        jsonResponse(['status' => 'ok', 'message' => 'Already shared with this lender']);
    }

    $stmt = $pdo->prepare("
        INSERT INTO lender_shares (lead_id, partner_id, lender_name, status, shared_at)
        VALUES (?, ?, ?, 'shared', NOW())
    ");
    $stmt->execute([$leadId, $partner['id'], $lenderName]);
    $shareId = (int)$pdo->lastInsertId();

    // Move lead status forward if still early
    $pdo->prepare("
        UPDATE customer_leads
        SET status = 'lender_shortlisted'
        WHERE lead_id = ?
          AND status NOT IN ('docs_uploaded','bureau_done','sanctioned','disbursed','rejected')
    ")->execute([$leadId]);

    logAct($leadId, (int)$partner['id'], 'lender_shared', "Case shared with {$lenderName}");
    jsonResponse(['status' => 'ok', 'id' => $shareId, 'message' => "Shared with {$lenderName}"]);
}

// ── Save Quote from a Lender ──────────────────────────────
function saveQuote(array $b, array $partner): void {
    $leadId     = intSafe($b['lead_id']     ?? 0);
    $lenderName = sanitize($b['lender_name'] ?? '');
    $roi        = $b['offered_roi'] ? floatSafe($b['offered_roi']) : null;
    $ltv        = $b['offered_ltv'] ? floatSafe($b['offered_ltv']) : null;
    $remarks    = sanitize($b['remarks']   ?? '');

    if (!$leadId)     jsonError('lead_id required');
    if (!$lenderName) jsonError('lender_name required');

    $pdo = getDB();

    // Verify ownership
    $chk = $pdo->prepare("SELECT id FROM leads WHERE id = ? AND partner_id = ?");
    $chk->execute([$leadId, $partner['id']]);
    if (!$chk->fetch()) jsonError('Lead not found', 404);

    // Upsert — insert or update if already exists
    $stmt = $pdo->prepare("
        INSERT INTO lender_shares
            (lead_id, partner_id, lender_name, status, offered_roi, offered_ltv, remarks, shared_at)
        VALUES (?, ?, ?, 'quote_received', ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            status      = 'quote_received',
            offered_roi = VALUES(offered_roi),
            offered_ltv = VALUES(offered_ltv),
            remarks     = VALUES(remarks),
            shared_at   = NOW()
    ");
    $stmt->execute([$leadId, $partner['id'], $lenderName, $roi, $ltv, $remarks]);

    // Move lead status forward
    $pdo->prepare("
        UPDATE leads
        SET status = 'follow_up'
        WHERE id = ?
          AND status NOT IN ('sanctioned','disbursed','rejected')
    ")->execute([$leadId]);

    $roiNote = $roi ? " @ {$roi}%" : '';
    logAct($leadId, (int)$partner['id'], 'quote_received', "Quote received from {$lenderName}{$roiNote}");
    jsonResponse(['status' => 'ok', 'message' => 'Quote saved']);
}

// ── Update Lender Interaction Status ─────────────────────
function updateShareStatus(array $b, array $partner): void {
    $id      = intSafe($b['id']     ?? 0);
    $status  = sanitize($b['status']  ?? '');
    $remarks = sanitize($b['remarks'] ?? '');

    if (!$id || !$status) jsonError('id and status required');

    $valid = ['pending','shared','quote_received','login_done','sanctioned','declined'];
    if (!in_array($status, $valid)) jsonError('Invalid status');

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        UPDATE lender_shares
        SET status = ?, remarks = ?, updated_at = NOW()
        WHERE id = ? AND partner_id = ?
    ");
    $stmt->execute([$status, $remarks, $id, $partner['id']]);

    if (!$stmt->rowCount()) jsonError('Share record not found', 404);
    jsonResponse(['status' => 'ok', 'message' => 'Lender status updated']);
}