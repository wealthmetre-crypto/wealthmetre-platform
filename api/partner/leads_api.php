<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/partner_auth.php';

$partner = requireAuth();
$pid     = (int)$partner['id'];
$pdo     = getDB();
$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = $body['action'] ?? $_GET['action'] ?? '';

if ($action === 'update_lead') {
    $lead_id = (int)($body['lead_id'] ?? 0);
    if (!$lead_id) { echo json_encode(['success'=>false,'message'=>'lead_id required']); exit; }

    // Verify this lead belongs to this partner
    $check = $pdo->prepare("SELECT id FROM leads WHERE id=? AND partner_id=?");
    $check->execute([$lead_id, $pid]);
    if (!$check->fetch()) {
        echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
    }

    $pdo->prepare("UPDATE leads SET
        customer_name=?, customer_mobile=?, city=?, loan_type=?,
        amount=?, valuation=?, cibil=?, monthly_income=?,
        income_type=?, property_title=?, occupation=?, remarks=?,
        updated_at=NOW()
        WHERE id=? AND partner_id=?")
    ->execute([
        trim($body['customer_name'] ?? ''),
        trim($body['customer_mobile'] ?? ''),
        trim($body['city'] ?? ''),
        trim($body['loan_type'] ?? ''),
        (int)($body['amount'] ?? 0) ?: null,
        (int)($body['valuation'] ?? 0) ?: null,
        (int)($body['cibil'] ?? 0) ?: null,
        (int)($body['monthly_income'] ?? 0) ?: null,
        trim($body['income_type'] ?? ''),
        trim($body['property_title'] ?? ''),
        trim($body['occupation'] ?? ''),
        trim($body['remarks'] ?? ''),
        $lead_id, $pid
    ]);

    echo json_encode(['success'=>true,'message'=>'Lead updated']);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action']);
