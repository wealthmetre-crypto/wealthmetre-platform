<?php
/**
 * WealthMetre - agreement_admin_api.php
 * Admin: agreement version manager + acceptance proof
 */
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../helpers/AgreementHelper.php';

header('Content-Type: application/json; charset=utf-8');

// Admin auth check
function requireAdminAuth(): void {
    session_start();
    $token = $_SERVER['HTTP_X_ADMIN_TOKEN']
          ?? $_SERVER['HTTP_AUTHORIZATION']
          ?? $_COOKIE['wm_admin_token']
          ?? '';
    $token = str_replace('Bearer ', '', trim($token));

    if (!$token) jsonError('Admin authentication required.', 401);

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT id FROM owner_users
        WHERE session_token = ? AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    if (!$stmt->fetch()) jsonError('Admin session expired. Please login again.', 401);
}

requireAdminAuth();

$body   = getJsonBody();
$action = sanitize($body['action'] ?? $_GET['action'] ?? '');

match($action) {
    'list_versions'    => actionListVersions(),
    'get_version'      => actionGetVersion($body),
    'add_version'      => actionAddVersion($body),
    'activate_version' => actionActivateVersion($body),
    'preview_version'  => actionPreviewVersion($body),
    'get_proof'        => actionGetProof($body),
    'list_partners'    => actionListPartners($body),
    'export_csv'       => actionExportCsv($body),
    default            => jsonError('Invalid action', 400),
};

// ── LIST ALL VERSIONS ─────────────────────────────────────────
function actionListVersions(): void {
    $pdo  = getDB();
    $rows = $pdo->query("
        SELECT av.*,
            (SELECT COUNT(*) FROM partner_agreements pa
             WHERE pa.agreement_version = av.version
               AND pa.status = 'accepted') AS acceptance_count
        FROM agreement_versions av
        ORDER BY av.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $hasActive = false;
    foreach ($rows as $r) {
        if ((int)$r['is_active'] === 1) { $hasActive = true; break; }
    }

    jsonResponse([
        'success'    => true,
        'versions'   => $rows,
        'has_active' => $hasActive,
        'warning'    => !$hasActive
            ? 'No active Partner Agreement version. Partner acceptance is blocked.'
            : null,
    ]);
}

// ── GET SINGLE VERSION ────────────────────────────────────────
function actionGetVersion(array $b): void {
    $version = sanitize($b['version'] ?? $_GET['version'] ?? '');
    if (!$version) jsonError('Version required.');
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM agreement_versions WHERE version = ? LIMIT 1");
    $stmt->execute([$version]);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonError('Version not found.', 404);
    jsonResponse(['success' => true, 'version' => $row]);
}

// ── ADD NEW VERSION ───────────────────────────────────────────
function actionAddVersion(array $b): void {
    $version     = sanitize($b['version']      ?? '');
    $title       = sanitize($b['title']        ?? '');
    $summary     = trim($b['summary']          ?? '');
    $fullText    = trim($b['full_text']        ?? '');
    $checkboxTxt = trim($b['checkbox_text']    ?? '');
    $effectiveFrom = sanitize($b['effective_from'] ?? date('Y-m-d'));

    if (!$version || !$title || !$summary || !$fullText || !$checkboxTxt) {
        jsonError('All fields are required.');
    }

    $textHash = hash('sha256', $fullText);
    $pdo = getDB();

    // Check duplicate version
    $chk = $pdo->prepare("SELECT id FROM agreement_versions WHERE version = ? LIMIT 1");
    $chk->execute([$version]);
    if ($chk->fetch()) jsonError('Version ' . $version . ' already exists.');

    $stmt = $pdo->prepare("
        INSERT INTO agreement_versions
            (agreement_type, version, title, summary, full_text,
             checkbox_text, text_hash, is_active, effective_from, created_by)
        VALUES ('partner_onboarding', ?, ?, ?, ?, ?, ?, 0, ?, 'admin')
    ");
    $stmt->execute([$version, $title, $summary, $fullText,
                    $checkboxTxt, $textHash, $effectiveFrom]);

    jsonResponse([
        'success'  => true,
        'message'  => 'Version ' . $version . ' added. Activate it when ready.',
        'text_hash'=> $textHash,
        'id'       => (int)$pdo->lastInsertId(),
    ]);
}

// ── ACTIVATE VERSION ──────────────────────────────────────────
function actionActivateVersion(array $b): void {
    $version  = sanitize($b['version']  ?? '');
    $confirmed = (bool)($b['confirmed'] ?? false);

    if (!$version) jsonError('Version required.');

    // Require confirmation
    if (!$confirmed) {
        $pdo     = getDB();
        $stmt    = $pdo->prepare("SELECT * FROM agreement_versions WHERE version = ? LIMIT 1");
        $stmt->execute([$version]);
        $newVer  = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$newVer) jsonError('Version not found.', 404);

        $active = $pdo->query("
            SELECT version FROM agreement_versions
            WHERE is_active = 1 AND agreement_type = 'partner_onboarding'
            LIMIT 1
        ")->fetchColumn();

        // Count partners who will need to re-accept
        $affectedCount = $pdo->query("
            SELECT COUNT(*) FROM partners
            WHERE status NOT IN ('inactive','suspended','rejected')
              AND agreement_version_accepted IS NOT NULL
        ")->fetchColumn();

        jsonResponse([
            'success'         => false,
            'needs_confirm'   => true,
            'current_version' => $active ?: 'none',
            'new_version'     => $version,
            'affected_partners'=> (int)$affectedCount,
            'message'         => "Activating v{$version} will require all {$affectedCount} active partners to re-accept. Current active: " . ($active ?: 'none') . ". Continue?",
        ]);
    }

    // Confirmed — activate
    $pdo = getDB();
    $check = $pdo->prepare("SELECT id FROM agreement_versions WHERE version = ? LIMIT 1");
    $check->execute([$version]);
    if (!$check->fetch()) jsonError('Version not found.', 404);

    $pdo->beginTransaction();
    try {
        $pdo->prepare("
            UPDATE agreement_versions SET is_active = 0
            WHERE agreement_type = 'partner_onboarding'
        ")->execute();

        $pdo->prepare("
            UPDATE agreement_versions SET is_active = 1
            WHERE version = ?
        ")->execute([$version]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonError('Activation failed: ' . $e->getMessage(), 500);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Version ' . $version . ' is now active. Partners will be prompted to re-accept.',
    ]);
}

// ── PREVIEW VERSION ───────────────────────────────────────────
function actionPreviewVersion(array $b): void {
    $version = sanitize($b['version'] ?? $_GET['version'] ?? '');
    if (!$version) jsonError('Version required.');
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM agreement_versions WHERE version = ? LIMIT 1");
    $stmt->execute([$version]);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonError('Version not found.', 404);
    $row['full_text'] = replaceAgreementPlaceholders($row['full_text']);
    jsonResponse(['success' => true, 'preview' => $row]);
}

// ── GET ACCEPTANCE PROOF ──────────────────────────────────────
function actionGetProof(array $b): void {
    $partnerId = (int)($b['partner_id'] ?? $_GET['partner_id'] ?? 0);
    if (!$partnerId) jsonError('partner_id required.');

    $pdo = getDB();

    $partner = $pdo->prepare("
        SELECT id, partner_name, mobile, email, status,
               agreement_version_accepted, agreement_accepted_at,
               onboarding_step
        FROM partners WHERE id = ? LIMIT 1
    ");
    $partner->execute([$partnerId]);
    $partnerData = $partner->fetch(PDO::FETCH_ASSOC);
    if (!$partnerData) jsonError('Partner not found.', 404);

    $agreements = $pdo->prepare("
        SELECT pa.*, av.title as version_title
        FROM partner_agreements pa
        LEFT JOIN agreement_versions av ON av.version = pa.agreement_version
        WHERE pa.partner_id = ?
        ORDER BY pa.created_at DESC
    ");
    $agreements->execute([$partnerId]);
    $agreementRecords = $agreements->fetchAll(PDO::FETCH_ASSOC);

    $audit = $pdo->prepare("
        SELECT * FROM partner_agreement_audit
        WHERE partner_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $audit->execute([$partnerId]);
    $auditLog = $audit->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        'success'    => true,
        'partner'    => $partnerData,
        'agreements' => $agreementRecords,
        'audit_log'  => $auditLog,
    ]);
}

// ── LIST PARTNERS WITH AGREEMENT FILTER ──────────────────────
function actionListPartners(array $b): void {
    $filter  = sanitize($b['filter'] ?? 'all');
    $version = sanitize($b['version'] ?? '');
    $from    = sanitize($b['date_from'] ?? '');
    $to      = sanitize($b['date_to']   ?? '');
    $page    = max(1, (int)($b['page'] ?? 1));
    $limit   = 50;
    $offset  = ($page - 1) * $limit;

    $pdo    = getDB();
    $where  = ['1=1'];
    $params = [];

    match($filter) {
        'agreement_accepted' => ($where[] = "p.agreement_version_accepted IS NOT NULL"),
        'agreement_pending'  => ($where[] = "p.agreement_version_accepted IS NULL"),
        'under_review'       => ($where[] = "p.status = 'under_review'"),
        'approved'           => ($where[] = "p.status = 'approved'"),
        'rejected'           => ($where[] = "p.status = 'rejected'"),
        default              => null,
    };
    if ($version) {
        $where[]  = "p.agreement_version_accepted = ?";
        $params[] = $version;
    }
    if ($from) {
        $where[]  = "p.agreement_accepted_at >= ?";
        $params[] = $from . ' 00:00:00';
    }
    if ($to) {
        $where[]  = "p.agreement_accepted_at <= ?";
        $params[] = $to . ' 23:59:59';
    }

    $whereStr = implode(' AND ', $where);
    $stmt = $pdo->prepare("
        SELECT p.id, p.partner_name, p.mobile, p.email, p.status,
               p.agreement_version_accepted, p.agreement_accepted_at,
               p.onboarding_step, p.created_at,
               pa.ip_address, pa.geo_city, pa.geo_state, pa.scroll_verified
        FROM partners p
        LEFT JOIN partner_agreements pa
            ON pa.id = p.agreement_acceptance_id
        WHERE {$whereStr}
        ORDER BY p.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM partners p WHERE {$whereStr}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    jsonResponse([
        'success'  => true,
        'partners' => $partners,
        'total'    => $total,
        'page'     => $page,
        'pages'    => (int)ceil($total / $limit),
    ]);
}

// ── EXPORT CSV ────────────────────────────────────────────────
function actionExportCsv(array $b): void {
    $pdo  = getDB();
    $rows = $pdo->query("
        SELECT p.id, p.partner_name, p.mobile, p.email, p.status,
               p.agreement_version_accepted, p.agreement_accepted_at,
               p.onboarding_step, p.created_at,
               pa.ip_address, pa.geo_city, pa.geo_state, pa.geo_country,
               pa.scroll_verified, pa.otp_verified_mobile
        FROM partners p
        LEFT JOIN partner_agreements pa ON pa.id = p.agreement_acceptance_id
        ORDER BY p.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=wm_partner_agreements_' . date('Ymd') . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'ID','Name','Mobile','Email','Status',
        'Agreement Version','Accepted At','Onboarding Step',
        'IP Address','City','State','Country',
        'Scroll Verified','OTP Mobile','Registered At'
    ]);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['partner_name'], $r['mobile'], $r['email'],
            $r['status'], $r['agreement_version_accepted'],
            $r['agreement_accepted_at'], $r['onboarding_step'],
            $r['ip_address'], $r['geo_city'], $r['geo_state'],
            $r['geo_country'], $r['scroll_verified'] ? 'Yes' : 'No',
            $r['otp_verified_mobile'], $r['created_at'],
        ]);
    }
    fclose($out);
    exit;
}
