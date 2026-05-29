<?php
/**
 * WealthMetre — Partner KYC Upload API
 * POST /api/partner/kyc_upload_api.php
 *
 * Auth   : requireAuth() from partner_auth.php (exits automatically if not logged in)
 * DB     : getDB() from config.php
 * Notify : sendWhatsAppAlert() from helpers/WhatsAppAlert.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/partner_auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// requireAuth() exits with 401 automatically if session invalid
// Returns partner array on success — partner_id always from session, never POST
$partner     = requireAuth();
$partnerId   = (int) $partner['id'];
$partnerName = $partner['first_name'] ?? $partner['partner_name'] ?? 'Partner';
$partnerMob  = $partner['mobile'] ?? '';

// ── Config ────────────────────────────────────────────────────────────────────
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];
const ALLOWED_MIMES      = ['image/jpeg', 'image/png', 'application/pdf'];
const MAX_FILE_BYTES     = 5 * 1024 * 1024; // 5 MB
const UPLOAD_BASE = '/var/www/wealthmetre/private_uploads/kyc/';

$requiredFields = [
    'pan_card'         => 'PAN Card',
    'aadhaar_card'     => 'Aadhaar Card',
    'cancelled_cheque' => 'Cancelled Cheque',
];

// ── Validate each file ────────────────────────────────────────────────────────
$validatedFiles = [];

foreach ($requiredFields as $fieldName => $label) {

    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'message' => "{$label} is required."]);
        exit;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => "Upload error for {$label}. Please try again."]);
        exit;
    }

    if ($file['size'] > MAX_FILE_BYTES) {
        echo json_encode(['success' => false, 'message' => "{$label} must be under 5 MB."]);
        exit;
    }

    $ext = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        echo json_encode(['success' => false, 'message' => "{$label}: only JPG, PNG, PDF files are allowed."]);
        exit;
    }

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, ALLOWED_MIMES, true)) {
        echo json_encode(['success' => false, 'message' => "{$label}: invalid file type detected."]);
        exit;
    }

    $mimeExtMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

    $validatedFiles[$fieldName] = [
        'tmp_name'  => $file['tmp_name'],
        'size'      => $file['size'],
        'mime_type' => $mimeType,
        'safe_ext'  => $mimeExtMap[$mimeType],
    ];
}

// ── Prepare upload directory ──────────────────────────────────────────────────
$uploadDir = UPLOAD_BASE . $partnerId . '/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0750, true)) {
        error_log("[KYC] Failed to create directory: {$uploadDir}");
        echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
        exit;
    }
}

// Block direct URL access (belt-and-suspenders if inside webroot)
$htaccess = $uploadDir . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

// ── Move files ────────────────────────────────────────────────────────────────
$timestamp  = time();
$savedFiles = [];

foreach ($validatedFiles as $fieldName => $fileData) {
    $safeFileName = "{$partnerId}_{$fieldName}_{$timestamp}.{$fileData['safe_ext']}";
    $destination  = $uploadDir . $safeFileName;

    if (!move_uploaded_file($fileData['tmp_name'], $destination)) {
        foreach ($savedFiles as $saved) { @unlink($saved['file_path']); }
        error_log("[KYC] Failed to move file for partner {$partnerId}: {$fieldName}");
        echo json_encode(['success' => false, 'message' => 'File save failed. Please try again.']);
        exit;
    }

    $savedFiles[$fieldName] = [
        'file_name' => $safeFileName,
        'file_path' => $destination,
        'file_size' => $fileData['size'],
        'mime_type' => $fileData['mime_type'],
    ];
}

// ── Database transaction ──────────────────────────────────────────────────────
try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $stmtDoc = $pdo->prepare("
        INSERT INTO partner_kyc_documents
            (partner_id, document_type, file_name, file_path, file_size, mime_type, status, uploaded_at)
        VALUES
            (:partner_id, :doc_type, :file_name, :file_path, :file_size, :mime_type, 'pending', NOW())
    ");

    foreach ($savedFiles as $fieldName => $fileInfo) {
        $stmtDoc->execute([
            ':partner_id' => $partnerId,
            ':doc_type'   => $fieldName,
            ':file_name'  => $fileInfo['file_name'],
            ':file_path'  => $fileInfo['file_path'],
            ':file_size'  => $fileInfo['file_size'],
            ':mime_type'  => $fileInfo['mime_type'],
        ]);
    }

    $pdo->prepare("
        UPDATE partners
        SET    kyc_submitted_at = NOW(),
               kyc_status       = 'submitted',
               onboarding_step  = 'kyc_submitted'
        WHERE  id = :id
    ")->execute([':id' => $partnerId]);

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    foreach ($savedFiles as $saved) { @unlink($saved['file_path']); }
    error_log("[KYC] DB error for partner {$partnerId}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    exit;
}

// ── WhatsApp notification — never block response on failure ──────────────────
try {
    if (function_exists('sendWhatsAppAlert')) sendWhatsAppAlert([
        'source'  => 'KYC Upload',
        'name'    => $partnerName,
        'mobile'  => $partnerMob,
        'product' => 'Partner KYC',
        'amount'  => 'PAN + Aadhaar + Cheque',
        'city'    => 'Partner Portal',
        'time'    => date('d M Y H:i'),
    ]);
} catch (Throwable $e) {
    error_log("[KYC] WhatsApp alert failed for partner {$partnerId}: " . $e->getMessage());
}

echo json_encode([
    'success'  => true,
    'message'  => 'Documents uploaded successfully.',
    'redirect' => '/partner_agreement.html',
]);
exit;
