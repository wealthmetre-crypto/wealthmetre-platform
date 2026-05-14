<?php
/**
 * WealthMetre – document_api.php
 * Document checklist tracker for channel partners.
 * POST/GET /api/partner/document_api.php
 * action = get | update
 */
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/partner_auth.php';

$body    = getJsonBody();
$action  = sanitize($body['action'] ?? $_GET['action'] ?? '');
$partner = requireAuth();

match ($action) {
    'get'    => getDocs($partner),
    'update' => updateDoc($body, $partner),
    default  => jsonError('Invalid action')
};

// Total doc types — used for completion %
const TOTAL_DOCS = 12;

// ── Get Documents for a Lead ──────────────────────────────
function getDocs(array $partner): void {
    $leadId = intSafe($_GET['lead_id'] ?? 0);
    if (!$leadId) jsonError('lead_id required');

    $pdo = getDB();

    // Verify ownership
    $chk = $pdo->prepare("SELECT lead_id FROM customer_leads WHERE lead_id = ? AND partner_id = ?");
    $chk->execute([$leadId, $partner['id']]);
    if (!$chk->fetch()) jsonError('Lead not found', 404);

    $stmt = $pdo->prepare("
        SELECT * FROM documents
        WHERE lead_id = ?
        ORDER BY document_type ASC
    ");
    $stmt->execute([$leadId]);
    $docs = $stmt->fetchAll();

    // Normalise field name for frontend (frontend expects 'doc_type')
    foreach ($docs as &$d) {
        $d['doc_type'] = $d['document_type'];
    }

    jsonResponse(['status' => 'ok', 'documents' => $docs]);
}

// ── Update / Insert Document Status ──────────────────────
function updateDoc(array $b, array $partner): void {
    $leadId  = intSafe($b['lead_id']  ?? 0);
    $docType = sanitize($b['doc_type'] ?? '');   // frontend sends 'doc_type'
    $status  = sanitize($b['status']   ?? 'pending');
    $remarks = sanitize($b['remarks']  ?? '');

    if (!$leadId)  jsonError('lead_id required');
    if (!$docType) jsonError('doc_type required');

    $validStatus = ['pending', 'received', 'verified', 'rejected'];
    if (!in_array($status, $validStatus)) jsonError('Invalid status');

    $pdo = getDB();

    // Verify ownership
    $chk = $pdo->prepare("SELECT lead_id FROM customer_leads WHERE lead_id = ? AND partner_id = ?");
    $chk->execute([$leadId, $partner['id']]);
    if (!$chk->fetch()) jsonError('Lead not found', 404);

    // INSERT or UPDATE if row already exists for this lead + doc_type
    $stmt = $pdo->prepare("
        INSERT INTO documents (lead_id, partner_id, document_type, status, remarks)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status     = VALUES(status),
            remarks    = VALUES(remarks),
            updated_at = NOW()
    ");
    $stmt->execute([$leadId, $partner['id'], $docType, $status, $remarks]);

    // Recalculate completion % and update lead
    $cnt = $pdo->prepare("
        SELECT COUNT(*) FROM documents
        WHERE lead_id = ? AND status IN ('received', 'verified')
    ");
    $cnt->execute([$leadId]);
    $received = (int)$cnt->fetchColumn();
    $pct      = round($received / TOTAL_DOCS * 100);

    try {
        $pdo->prepare("UPDATE customer_leads SET doc_completion_pct = ? WHERE lead_id = ?")
            ->execute([$pct, $leadId]);
    } catch (\Throwable $e) {
        // Column not yet added — safe to skip
    }

    if ($status !== 'pending') {
        logAct($leadId, (int)$partner['id'], 'doc_updated', "{$docType} → {$status}");
    }

    jsonResponse([
        'status'         => 'ok',
        'message'        => 'Document updated',
        'completion_pct' => $pct,
    ]);
}