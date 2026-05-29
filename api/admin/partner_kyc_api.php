<?php
/**
 * WealthMetre — Admin KYC Documents API
 *
 * GET  ?partner_id=X          → list all KYC docs for a partner
 * GET  ?action=file&doc_id=X  → stream file securely
 * POST action/document_id/notes → approve or reject a document
 *
 * Auth: Uses same session_token pattern as agreement_admin_api.php
 * DB  : getDB() from config.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// ── Admin auth — session-based, matches dashboard credentials:include ────────
function requireAdminAuth(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('wm_admin');
        session_set_cookie_params(['lifetime'=>86400,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }
    // Session auth (dashboard uses credentials:include)
    if (!empty($_SESSION['admin_id'])) {
        return $_SESSION['admin_name'] ?? 'Admin';
    }
    // Token fallback
    $token = $_SERVER['HTTP_X_ADMIN_TOKEN']
          ?? $_SERVER['HTTP_AUTHORIZATION']
          ?? $_COOKIE['wm_admin_token']
          ?? '';
    $token = str_replace('Bearer ', '', trim($token));
    if ($token) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id, name FROM owner_users WHERE session_token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row['name'] ?? 'Admin';
    }
    jsonError('Admin authentication required.', 401);
}

$adminName = requireAdminAuth();
$method    = $_SERVER['REQUEST_METHOD'];

// ── Route ─────────────────────────────────────────────────────────────────────

if ($method === 'GET' && ($_GET['action'] ?? '') === 'file') {
    streamKycFile((int)($_GET['doc_id'] ?? 0));
    exit;
}

if ($method === 'GET' && isset($_GET['partner_id'])) {
    listPartnerDocs((int)$_GET['partner_id']);
    exit;
}

if ($method === 'POST') {
    $body       = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action     = trim($body['action']       ?? '');
    $documentId = (int)($body['document_id'] ?? 0);
    $notes      = trim($body['notes']        ?? '');

    if (!in_array($action, ['approve', 'reject'], true) || $documentId < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
        exit;
    }

    if ($action === 'reject' && $notes === '') {
        echo json_encode(['success' => false, 'message' => 'Please provide a reason for rejection.']);
        exit;
    }

    reviewDocument($documentId, $action, $notes, $adminName);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
exit;


// ── Functions ─────────────────────────────────────────────────────────────────

function listPartnerDocs(int $partnerId): void
{
    if ($partnerId < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid partner_id.']);
        return;
    }

    $pdo = getDB();

    $stmtP = $pdo->prepare("
        SELECT id, partner_name AS name, first_name, last_name, mobile, email,
               kyc_status, kyc_submitted_at, onboarding_step
        FROM   partners
        WHERE  id = :id
        LIMIT  1
    ");
    $stmtP->execute([':id' => $partnerId]);
    $partner = $stmtP->fetch(PDO::FETCH_ASSOC);

    if (!$partner) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Partner not found.']);
        return;
    }

    $stmtD = $pdo->prepare("
        SELECT id, document_type, file_name, file_size, mime_type,
               status, uploaded_at, reviewed_at, reviewed_by, notes
        FROM   partner_kyc_documents
        WHERE  partner_id = :pid
        ORDER  BY uploaded_at DESC
    ");
    $stmtD->execute([':pid' => $partnerId]);
    $docs = $stmtD->fetchAll(PDO::FETCH_ASSOC);

    foreach ($docs as &$doc) {
        // Safe authenticated URL — never expose file_path
        $doc['download_url'] = "/api/admin/partner_kyc_api.php?action=file&doc_id={$doc['id']}";
    }
    unset($doc);

    echo json_encode([
        'success'   => true,
        'partner'   => $partner,
        'documents' => $docs,
    ]);
}

function reviewDocument(int $docId, string $action, string $notes, string $reviewerName): void
{
    $pdo = getDB();

    $stmtFetch = $pdo->prepare("
        SELECT id, partner_id FROM partner_kyc_documents WHERE id = :id LIMIT 1
    ");
    $stmtFetch->execute([':id' => $docId]);
    $doc = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Document not found.']);
        return;
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $partnerId = (int)$doc['partner_id'];

    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            UPDATE partner_kyc_documents
            SET    status      = :status,
                   reviewed_at = NOW(),
                   reviewed_by = :reviewer,
                   notes       = :notes
            WHERE  id = :id
        ")->execute([
            ':status'   => $newStatus,
            ':reviewer' => $reviewerName,
            ':notes'    => $notes,
            ':id'       => $docId,
        ]);

        // Sync partner-level kyc_status based on all docs
        $stmtS = $pdo->prepare("
            SELECT
                COUNT(*)                                        AS total,
                SUM(CASE WHEN status='approved' THEN 1 END)   AS approved_count,
                SUM(CASE WHEN status='rejected' THEN 1 END)   AS rejected_count
            FROM partner_kyc_documents
            WHERE partner_id = :pid
        ");
        $stmtS->execute([':pid' => $partnerId]);
        $s = $stmtS->fetch(PDO::FETCH_ASSOC);

        $total    = (int)$s['total'];
        $approved = (int)$s['approved_count'];
        $rejected = (int)$s['rejected_count'];

        if ($rejected > 0) {
            $partnerKycStatus = 'rejected';
        } elseif ($approved === $total && $total > 0) {
            $partnerKycStatus = 'approved';
        } else {
            $partnerKycStatus = 'submitted';
        }

        // Auto-activate partner when all KYC docs approved
        if ($partnerKycStatus === 'approved') {
            // Fully activate partner on KYC approval
            $pdo->prepare("UPDATE partners SET kyc_status = :ks, onboarding_step = 'approved', status = 'active' WHERE id = :id")
                ->execute([':ks' => $partnerKycStatus, ':id' => $partnerId]);
        } elseif ($partnerKycStatus === 'rejected') {
            $pdo->prepare("UPDATE partners SET kyc_status = :ks, onboarding_step = 'submitted', status = 'under_review' WHERE id = :id")
                ->execute([':ks' => $partnerKycStatus, ':id' => $partnerId]);
        } else {
            $pdo->prepare("UPDATE partners SET kyc_status = :ks WHERE id = :id")
                ->execute([':ks' => $partnerKycStatus, ':id' => $partnerId]);
        }

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("[KYC Admin] DB error on doc {$docId}: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
        return;
    }

    echo json_encode([
        'success'            => true,
        'message'            => "Document {$newStatus} successfully.",
        'document_id'        => $docId,
        'new_status'         => $newStatus,
        'partner_kyc_status' => $partnerKycStatus,
    ]);
}

function streamKycFile(int $docId): void
{
    if ($docId < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid document ID.']);
        return;
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT file_path, file_name, mime_type
        FROM   partner_kyc_documents
        WHERE  id = :id
        LIMIT  1
    ");
    $stmt->execute([':id' => $docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc || !file_exists($doc['file_path'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'File not found.']);
        return;
    }

    // Clear any JSON header set above, stream the file
    header_remove('Content-Type');
    header('Content-Type: '        . $doc['mime_type']);
    header('Content-Disposition: inline; filename="' . basename($doc['file_name']) . '"');
    header('Content-Length: '      . filesize($doc['file_path']));
    header('Cache-Control: no-store, no-cache');
    header('X-Content-Type-Options: nosniff');
    readfile($doc['file_path']);
}
