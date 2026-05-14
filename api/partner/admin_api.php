<?php
/**
 * WealthMetre – admin_api.php
 * Admin dashboard APIs.
 * GET /api/partner/admin_api.php?action=...
 * Requires admin token header: X-Admin-Token
 */
require_once __DIR__ . '/../config.php';

// Simple admin token auth (replace with proper admin login in production)
define('ADMIN_TOKEN', 'WM_ADMIN_' . md5('wealthmetre_admin_2024'));

function requireAdmin(): void {
    $token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? $_GET['admin_token'] ?? '';
    if ($token !== ADMIN_TOKEN) jsonError('Admin access required', 401);
}

$action = sanitize($_GET['action'] ?? getJsonBody()['action'] ?? '');
requireAdmin();

match ($action) {
    'dashboard'     => getDashboard(),
    'leads'         => getAdminLeads(),
    'partners'      => getPartners(),
    'alerts'        => getAlerts(),
    'mark_read'     => markAlertsRead(),
    'create_partner'=> createPartner(),
    'stats'         => getStats(),
    default         => jsonError('Invalid action')
};


// ── Dashboard Overview ────────────────────────────────────
function getDashboard(): void {
    $pdo = getDB();

    $stats = [
        'total_leads'     => (int)$pdo->query("SELECT COUNT(*) FROM customer_leads")->fetchColumn(),
        'new_today'       => (int)$pdo->query("SELECT COUNT(*) FROM customer_leads WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'new_this_week'   => (int)$pdo->query("SELECT COUNT(*) FROM customer_leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
        'total_partners'  => (int)$pdo->query("SELECT COUNT(*) FROM partners WHERE status='active'")->fetchColumn(),
        'unread_alerts'   => (int)$pdo->query("SELECT COUNT(*) FROM admin_alerts WHERE is_read=0")->fetchColumn(),
        'pending_tasks'   => (int)$pdo->query("SELECT COUNT(*) FROM followup_tasks WHERE status='pending'")->fetchColumn(),
    ];

    // Status breakdown
    $statusQuery = $pdo->query("
        SELECT status, COUNT(*) as cnt FROM customer_leads GROUP BY status
    ");
    $byStatus = [];
    foreach ($statusQuery->fetchAll() as $row) {
        $byStatus[$row['status']] = (int)$row['cnt'];
    }

    // Loan type breakdown
    $ltQuery = $pdo->query("
        SELECT loan_type, COUNT(*) as cnt, SUM(amount) as total_amount
        FROM customer_leads GROUP BY loan_type ORDER BY cnt DESC
    ");
    $byLoanType = $ltQuery->fetchAll();

    // City breakdown
    $cityQuery = $pdo->query("
        SELECT city, COUNT(*) as cnt FROM customer_leads
        WHERE city != '' GROUP BY city ORDER BY cnt DESC LIMIT 10
    ");

    // Top partners
    $partnerQuery = $pdo->query("
        SELECT p.partner_name, p.partner_code, p.branch_city,
               COUNT(cl.lead_id) as lead_count,
               SUM(cl.amount) as total_amount
        FROM partners p
        LEFT JOIN customer_leads cl ON p.id = cl.partner_id
        GROUP BY p.id ORDER BY lead_count DESC LIMIT 10
    ");

    // Recent leads
    $recentQuery = $pdo->query("
        SELECT cl.*, p.partner_name, p.partner_code
        FROM customer_leads cl
        JOIN partners p ON cl.partner_id = p.id
        ORDER BY cl.created_at DESC LIMIT 10
    ");

    jsonResponse([
        'status'       => 'ok',
        'stats'        => $stats,
        'by_status'    => $byStatus,
        'by_loan_type' => $byLoanType,
        'by_city'      => $cityQuery->fetchAll(),
        'top_partners' => $partnerQuery->fetchAll(),
        'recent_leads' => $recentQuery->fetchAll(),
    ]);
}


// ── Admin Lead List ───────────────────────────────────────
function getAdminLeads(): void {
    $pdo    = getDB();
    $status = sanitize($_GET['status']   ?? '');
    $city   = sanitize($_GET['city']     ?? '');
    $loan   = sanitize($_GET['loan_type']?? '');
    $partner= sanitize($_GET['partner']  ?? '');
    $limit  = min(100, intSafe($_GET['limit'] ?? 25));
    $offset = intSafe($_GET['offset'] ?? 0);

    $where  = ['1=1'];
    $params = [];
    if ($status)  { $where[] = 'cl.status = ?';         $params[] = $status; }
    if ($city)    { $where[] = 'cl.city LIKE ?';         $params[] = "%{$city}%"; }
    if ($loan)    { $where[] = 'cl.loan_type = ?';       $params[] = $loan; }
    if ($partner) { $where[] = 'p.partner_code LIKE ?';  $params[] = "%{$partner}%"; }

    $whereStr = implode(' AND ', $where);
    $stmt = $pdo->prepare("
        SELECT cl.*, p.partner_name, p.partner_code, p.branch_city as partner_city,
               (SELECT COUNT(*) FROM documents d WHERE d.lead_id = cl.lead_id) as doc_count,
               (SELECT COUNT(*) FROM lender_results lr WHERE lr.lead_id = cl.lead_id) as lender_count
        FROM customer_leads cl
        JOIN partners p ON cl.partner_id = p.id
        WHERE {$whereStr}
        ORDER BY cl.created_at DESC LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customer_leads cl JOIN partners p ON cl.partner_id = p.id WHERE {$whereStr}");
    $countStmt->execute(array_slice($params, 0, -2));

    jsonResponse([
        'status' => 'ok',
        'leads'  => $stmt->fetchAll(),
        'total'  => (int)$countStmt->fetchColumn(),
    ]);
}


// ── Partners List ─────────────────────────────────────────
function getPartners(): void {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT p.*,
               COUNT(cl.lead_id) as total_leads,
               SUM(CASE WHEN cl.status='sanctioned' THEN 1 ELSE 0 END) as sanctioned,
               SUM(CASE WHEN cl.status='disbursed'  THEN 1 ELSE 0 END) as disbursed,
               SUM(cl.amount) as total_pipeline
        FROM partners p
        LEFT JOIN customer_leads cl ON p.id = cl.partner_id
        GROUP BY p.id ORDER BY total_leads DESC
    ");
    jsonResponse(['status' => 'ok', 'partners' => $stmt->fetchAll()]);
}


// ── Alerts ────────────────────────────────────────────────
function getAlerts(): void {
    $pdo   = getDB();
    $limit = intSafe($_GET['limit'] ?? 20);
    $stmt  = $pdo->prepare("
        SELECT aa.*, p.partner_name
        FROM admin_alerts aa
        LEFT JOIN partners p ON aa.partner_id = p.id
        ORDER BY aa.created_at DESC LIMIT ?
    ");
    $stmt->execute([$limit]);

    $unread = (int)$pdo->query("SELECT COUNT(*) FROM admin_alerts WHERE is_read = 0")->fetchColumn();

    jsonResponse(['status' => 'ok', 'alerts' => $stmt->fetchAll(), 'unread' => $unread]);
}

function markAlertsRead(): void {
    getDB()->query("UPDATE admin_alerts SET is_read = 1 WHERE is_read = 0");
    jsonResponse(['status' => 'ok']);
}


// ── Create Partner ────────────────────────────────────────
function createPartner(): void {
    $b    = getJsonBody();
    $pdo  = getDB();
    $code = 'WM-' . strtoupper(substr(preg_replace('/\D/', '', $b['mobile'] ?? ''), -4)) . '-' . rand(100, 999);

    $stmt = $pdo->prepare("
        INSERT INTO partners (partner_name, mobile, email, company_name, branch_city, partner_code)
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->execute([
        sanitize($b['partner_name']  ?? ''),
        sanitize($b['mobile']        ?? ''),
        sanitize($b['email']         ?? ''),
        sanitize($b['company_name']  ?? ''),
        sanitize($b['branch_city']   ?? 'Jaipur'),
        sanitize($b['partner_code']  ?? $code),
    ]);

    jsonResponse(['status' => 'ok', 'partner_id' => $pdo->lastInsertId(), 'partner_code' => $code]);
}


// ── Stats for charts ─────────────────────────────────────
function getStats(): void {
    $pdo = getDB();

    // Daily leads for last 30 days
    $daily = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count, SUM(amount) as amount
        FROM customer_leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at) ORDER BY date ASC
    ")->fetchAll();

    // Lender hit rate
    $lenders = $pdo->query("
        SELECT lender_name, COUNT(*) as shortlisted, AVG(score) as avg_score
        FROM lender_results GROUP BY lender_name ORDER BY shortlisted DESC LIMIT 15
    ")->fetchAll();

    jsonResponse(['status' => 'ok', 'daily_leads' => $daily, 'lender_stats' => $lenders]);
}