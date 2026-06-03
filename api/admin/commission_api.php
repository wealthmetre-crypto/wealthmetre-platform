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

// Monthly disbursement summary
if ($action === 'monthly_summary') {
    $pid   = (int)($_GET['partner_id'] ?? 0);
    $month = $_GET['month'] ?? date('Y-m');
    if (!$pid) jsonError('partner_id required');

    [$year, $mon] = explode('-', $month);

    // Disbursed leads from calling_leads
    $stmt = $pdo->prepare("
        SELECT cl.id, cl.customer_name as name, cl.loan_type, cl.loan_amount,
               cl.disbursal_amount, cl.disbursal_date, cl.disbursed_lender,
               cl.current_status as status
        FROM calling_leads cl
        LEFT JOIN calling_batches cb ON cb.id = cl.batch_id
        WHERE cb.partner_id = ?
          AND cl.current_status = 'disbursed'
          AND MONTH(cl.disbursal_date) = ?
          AND YEAR(cl.disbursal_date) = ?
        UNION ALL
        SELECT l.id, l.customer_name as name, l.loan_type, l.loan_amount,
               l.disbursal_amount, l.disbursal_date, l.disbursed_lender,
               l.status as status
        FROM leads l
        WHERE l.partner_id = ?
          AND l.status = 'disbursed'
          AND MONTH(l.disbursal_date) = ?
          AND YEAR(l.disbursal_date) = ?
        ORDER BY disbursal_date ASC
    ");
    $stmt->execute([$pid, $mon, $year, $pid, $mon, $year]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get commission rates for this partner
    $rStmt = $pdo->prepare("SELECT product_type, sub_partner_pct, override_pct, wm_pct FROM partner_commission_rates WHERE partner_id=?");
    $rStmt->execute([$pid]);
    $rates = [];
    foreach ($rStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $rates[$r['product_type']] = $r;
    }

    // Andro base payout rates
    $basePayout = ['home_loan'=>1.0,'Home Loan'=>1.0,'lap'=>1.5,'Loan Against Property (LAP)'=>1.5,'business_loan'=>3.0,'Business Loan'=>3.0,'personal_loan'=>3.0,'Personal Loan'=>3.0];

    $summary = [];
    $totalDisbursed = 0;
    $totalWM = 0;
    $totalPartner = 0;

    foreach ($leads as &$lead) {
        $amt = (float)($lead['disbursal_amount'] ?: $lead['loan_amount'] ?: 0);
        $ltype = $lead['loan_type'] ?? '';
        $payout = $basePayout[$ltype] ?? 1.0;
        $gross = $amt * $payout / 100;

        // Map loan type to product key
        $prodKey = strtolower(str_replace([' ','(',')','-'], ['_','','','_'], $ltype));
        $prodKey = str_replace('loan_against_property_lap', 'lap', $prodKey);
        $prodKey = str_replace('home_loan', 'home_loan', $prodKey);

        $r = $rates[$prodKey] ?? ['sub_partner_pct'=>50,'override_pct'=>15,'wm_pct'=>35];
        $wmShare = $gross * 0.10; // WM base cut (10%)
        $partnerShare = $gross * 0.90 * ((float)$r['sub_partner_pct'] / 100);
        $wmTotal = $gross - $partnerShare;

        $lead['calc_gross']   = round($gross);
        $lead['calc_partner'] = round($partnerShare);
        $lead['calc_wm']      = round($wmTotal);
        $lead['payout_rate']  = $payout;

        $totalDisbursed += $amt;
        $totalWM        += $wmTotal;
        $totalPartner   += $partnerShare;
    }

    jsonResponse(['success'=>true,'leads'=>$leads,'summary'=>[
        'total_disbursed'  => $totalDisbursed,
        'total_wm'         => round($totalWM),
        'total_partner'    => round($totalPartner),
        'lead_count'       => count($leads),
        'month'            => $month
    ],'rates'=>$rates]);
}
