<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';



function requireAdminAuth(): string {
    startAdminSession();
    if (!empty($_SESSION['admin_id'])) {
        return $_SESSION['admin_name'] ?? 'Admin';
    }
    jsonError('Admin authentication required.', 401);
}
$adminName = requireAdminAuth();

$action = $_GET['action'] ?? (json_decode(file_get_contents('php://input'), true)['action'] ?? '');
$pdo = getDB();

// Get all master partners (level 1)
if ($action === 'master_partners') {
    $stmt = $pdo->query("SELECT id, partner_name, mobile FROM partners WHERE partner_level=1 AND status='active' ORDER BY partner_name");
    jsonResponse(['success'=>true, 'partners'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// Get sub-partners of a master
if ($action === 'sub_partners') {
    $pid = (int)($_GET['partner_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, partner_name, mobile FROM partners WHERE parent_partner_id=? AND status='active'");
    $stmt->execute([$pid]);
    jsonResponse(['success'=>true, 'partners'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// Get rates for a partner
if ($action === 'get_rates') {
    $pid = (int)($_GET['partner_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT product_type, sub_partner_pct, override_pct, wm_pct FROM partner_commission_rates WHERE partner_id=?");
    $stmt->execute([$pid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rates = [];
    foreach ($rows as $r) $rates[$r['product_type']] = $r;
    jsonResponse(['success'=>true, 'rates'=>$rates]);
}

// Save rates
if ($action === 'save_rates') {
    $body = json_decode(file_get_contents('php://input'), true);
    $pid  = (int)($body['partner_id'] ?? 0);
    $rates = $body['rates'] ?? [];
    if (!$pid || empty($rates)) jsonError('Invalid data');

    $stmt = $pdo->prepare("
        INSERT INTO partner_commission_rates (partner_id, product_type, sub_partner_pct, override_pct, wm_pct)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE sub_partner_pct=VALUES(sub_partner_pct), override_pct=VALUES(override_pct), wm_pct=VALUES(wm_pct), updated_at=NOW()
    ");
    foreach ($rates as $product => $r) {
        $stmt->execute([$pid, $product, $r['sub_partner_pct'], $r['override_pct'], $r['wm_pct']]);
    }
    jsonResponse(['success'=>true, 'message'=>'Rates saved']);
}

jsonError('Invalid action');
