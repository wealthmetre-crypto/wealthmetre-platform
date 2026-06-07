<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/partner_auth.php';

$partner = requireAuth();
$pid     = (int)$partner['id'];
$action  = $_GET['action'] ?? '';
$pdo     = getDB();

if ($action === 'team_summary') {
    // Get sub-partners
    $members = $pdo->prepare("
        SELECT p.id, p.partner_name, p.mobile,
               DATE_FORMAT(p.created_at, '%d %b %Y') as joined,
               COUNT(l.id) as total_leads,
               SUM(CASE WHEN l.status='disbursed' THEN 1 ELSE 0 END) as disbursed,
               SUM(CASE WHEN l.status='interested' THEN 1 ELSE 0 END) as interested
        FROM partners p
        LEFT JOIN leads l ON l.partner_id = p.id
        WHERE p.parent_partner_id = ?
        GROUP BY p.id
    ");
    $members->execute([$pid]);
    $memberList = $members->fetchAll(PDO::FETCH_ASSOC);

    // Overall stats
    $subIds = array_column($memberList, 'id');
    $totalLeads = 0; $totalDisbursed = 0; $totalInterested = 0;
    foreach ($memberList as $m) {
        $totalLeads      += (int)$m['total_leads'];
        $totalDisbursed  += (int)$m['disbursed'];
        $totalInterested += (int)$m['interested'];
    }

    echo json_encode([
        'success' => true,
        'members' => $memberList,
        'stats'   => [
            'total_leads' => $totalLeads,
            'disbursed'   => $totalDisbursed,
            'interested'  => $totalInterested,
        ]
    ]);
    exit;
}

if ($action === 'member_leads') {
    $memberId = (int)($_GET['member_id'] ?? 0);
    // Verify this member belongs to current partner
    $check = $pdo->prepare("SELECT id FROM partners WHERE id=? AND parent_partner_id=?");
    $check->execute([$memberId, $pid]);
    if (!$check->fetch()) {
        echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
    }
    $leads = $pdo->prepare("
        SELECT id, customer_name, loan_type, amount, city, status, created_at
        FROM leads WHERE partner_id=?
        ORDER BY created_at DESC
    ");
    $leads->execute([$memberId]);
    echo json_encode(['success'=>true,'leads'=>$leads->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'gen_invite') {
    $code = $pdo->prepare("SELECT partner_code FROM partners WHERE id=?");
    $code->execute([$pid]);
    $row = $code->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true,'code'=>$row['partner_code']??'WM'.$pid]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action']);
