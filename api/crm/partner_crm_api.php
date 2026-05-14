<?php
/**
 * WealthMetre — Partner CRM Upload & Assignment API
 * Path: /api/crm/partner_crm_api.php
 *
 * Actions:
 *   create_campaign      → create new calling campaign
 *   list_campaigns       → list partner's campaigns with stats
 *   upload_leads         → bulk upload leads from parsed Excel
 *   preview_leads        → validate before final import
 *   assign_leads         → assign leads to telecallers
 *   auto_assign          → equal distribution
 *   list_leads           → partner view of all leads
 *   get_campaign_stats   → detailed campaign analytics
 *   get_partner_dashboard→ partner home stats
 *   export_leads         → download updated Excel data
 *   recycle_leads        → re-assign old unresponsive leads
 *   score_report         → telecaller scoring report
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

// Auth
$partner = authenticatePartner();
if (!$partner) { jsonErr('Unauthorized', 401); }
$partnerId = (int)($partner['id'] ?? $partner['user_id'] ?? 0);

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create_campaign':       createCampaign($body, $partnerId);      break;
    case 'list_campaigns':        listCampaigns($partnerId);               break;
    case 'upload_leads':          uploadLeads($body, $partnerId);          break;
    case 'preview_leads':         previewLeads($body, $partnerId);         break;
    case 'assign_leads':          assignLeads($body, $partnerId);          break;
    case 'auto_assign':           autoAssign($body, $partnerId);           break;
    case 'list_leads':            listLeads($partnerId);                   break;
    case 'get_campaign_stats':    getCampaignStats($body, $partnerId);     break;
    case 'get_partner_dashboard': getPartnerDashboard($partnerId);         break;
    case 'export_leads':          exportLeads($body, $partnerId);          break;
    case 'recycle_leads':         recycleLeads($body, $partnerId);         break;
    case 'score_report':          getScoreReport($partnerId);              break;
    default: jsonErr('Invalid action');
}


// ════════════════════════════════════════════════════════
// CAMPAIGN MANAGEMENT
// ════════════════════════════════════════════════════════

function createCampaign(array $b, int $pid): void {
    $name = sanitize($b['name'] ?? '');
    if (!$name) jsonErr('Campaign name required');

    $pdo = getDB();
    $pdo->prepare("
        INSERT INTO calling_batches
            (partner_id, batch_name, status)
        VALUES (?, ?, 'active')
    ")->execute([$pid, $name]);

    jsonOk([
        'campaign_id' => $pdo->lastInsertId(),
        'message'     => 'Campaign created'
    ]);
}

function listCampaigns(int $pid): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT
            b.*,
            COUNT(cl.id)                                   AS total_leads,
            SUM(cl.assigned_telecaller_id IS NOT NULL)     AS assigned,
            SUM(cl.assigned_telecaller_id IS NULL)         AS unassigned,
            SUM(cl.current_status = 'pending')             AS pending,
            SUM(cl.current_status = 'interested')          AS interested,
            SUM(cl.current_status = 'converted')           AS converted,
            SUM(cl.current_status NOT IN ('pending'))      AS called,
            SUM(cl.current_status = 'not_interested')      AS not_interested,
            SUM(cl.current_status = 'follow_up')           AS followup,
            ROUND(
                SUM(cl.current_status='interested') /
                NULLIF(COUNT(cl.id),0) * 100, 1
            )                                              AS conversion_rate
        FROM calling_batches b
        LEFT JOIN calling_leads cl ON cl.batch_id = b.id
        WHERE b.partner_id = ?
        GROUP BY b.id
        ORDER BY b.uploaded_at DESC
    ");
    $stmt->execute([$pid]);
    jsonOk(['campaigns' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}


// ════════════════════════════════════════════════════════
// LEAD UPLOAD
// ════════════════════════════════════════════════════════

function previewLeads(array $b, int $pid): void {
    $leads     = $b['leads'] ?? [];
    $campaignId = intSafe($b['campaign_id'] ?? 0);
    if (empty($leads))   jsonErr('No leads provided');
    if (!$campaignId)    jsonErr('campaign_id required');
    if (count($leads) > 2000) jsonErr('Max 2000 leads per upload');

    $pdo      = getDB();
    $valid    = [];
    $invalid  = [];
    $dupes    = [];

    foreach ($leads as $i => $row) {
        $mob  = preg_replace('/\D/', '', $row['mobile'] ?? '');
        $name = trim($row['name'] ?? '');
        $rowNum = $i + 2; // Excel row number (1=header)

        // Validation
        if (!$name) {
            $invalid[] = ['row' => $rowNum, 'reason' => 'Name missing'];
            continue;
        }
        if (strlen($mob) !== 10 || !preg_match('/^[6-9]/', $mob)) {
            $invalid[] = ['row' => $rowNum, 'name' => $name, 'reason' => 'Invalid mobile: '.$mob];
            continue;
        }

        // Check duplicate in same campaign
        $mhash = hash('sha256', $mob);
        $dup = $pdo->prepare("
            SELECT id FROM calling_leads
            WHERE mobile_hash = ? AND batch_id = ?
            LIMIT 1
        ");
        $dup->execute([$mhash, $campaignId]);
        if ($dup->fetch()) {
            $dupes[] = ['row' => $rowNum, 'name' => $name, 'mobile' => $mob];
            continue;
        }

        // Calculate priority score
        $score = calculatePriorityScore($row);

        $valid[] = [
            'name'          => $name,
            'mobile'        => $mob,
            'mobile_hash'   => $mhash,
            'city'          => sanitize($row['city'] ?? ''),
            'loan_type'     => sanitize($row['loan_type'] ?? $row['loantype'] ?? ''),
            'loan_amount'   => cleanAmount($row['loan_amount'] ?? $row['amount'] ?? ''),
            'income'        => cleanAmount($row['income'] ?? $row['monthly_income'] ?? ''),
            'cibil'         => cleanCibil($row['cibil'] ?? ''),
            'property_type' => sanitize($row['property_type'] ?? $row['property'] ?? ''),
            'source'        => sanitize($row['source'] ?? ''),
            'uploaded_remarks' => sanitize($row['remarks'] ?? $row['notes'] ?? '', 500),
            'priority_score'=> $score,
        ];
    }

    jsonOk([
        'valid_count'   => count($valid),
        'invalid_count' => count($invalid),
        'dupe_count'    => count($dupes),
        'sample'        => array_slice($valid, 0, 5),
        'invalid'       => array_slice($invalid, 0, 10),
        'dupes'         => array_slice($dupes, 0, 10),
        'ready_to_import' => count($valid) > 0,
    ]);
}

function uploadLeads(array $b, int $pid): void {
    $leads      = $b['leads'] ?? [];
    $campaignId = intSafe($b['campaign_id'] ?? 0);
    if (empty($leads)) jsonErr('No leads provided');
    if (!$campaignId)  jsonErr('campaign_id required');

    // Verify campaign ownership
    $pdo = getDB();
    $own = $pdo->prepare("SELECT id FROM calling_batches WHERE id=? AND partner_id=?");
    $own->execute([$campaignId, $pid]);
    if (!$own->fetch()) jsonErr('Campaign not found', 404);

    $stmt = $pdo->prepare("
        INSERT INTO calling_leads
            (partner_id, batch_id, customer_name, mobile, mobile_hash,
             city, loan_type, loan_amount, income, cibil,
             property_type, source, uploaded_remarks, priority_score)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $inserted = 0; $skipped = 0;
    $pdo->beginTransaction();

    try {
        foreach ($leads as $row) {
            $mob   = preg_replace('/\D/', '', $row['mobile'] ?? '');
            $name  = trim($row['name'] ?? '');
            if (!$name || strlen($mob) !== 10) { $skipped++; continue; }

            $mhash = hash('sha256', $mob);

            // Skip dupes in same campaign
            $dup = $pdo->prepare("SELECT id FROM calling_leads WHERE mobile_hash=? AND batch_id=?");
            $dup->execute([$mhash, $campaignId]);
            if ($dup->fetch()) { $skipped++; continue; }

            $score = calculatePriorityScore($row);

            $stmt->execute([
                $pid, $campaignId, $name, $mob, $mhash,
                sanitize($row['city'] ?? ''),
                sanitize($row['loan_type'] ?? $row['loantype'] ?? ''),
                cleanAmount($row['loan_amount'] ?? $row['amount'] ?? ''),
                cleanAmount($row['income'] ?? ''),
                cleanCibil($row['cibil'] ?? ''),
                sanitize($row['property_type'] ?? ''),
                sanitize($row['source'] ?? ''),
                sanitize($row['remarks'] ?? $row['notes'] ?? '', 500),
                $score
            ]);
            $inserted++;
        }

        // Update campaign totals
        $pdo->prepare("
            UPDATE calling_batches
            SET total_records = total_records + ?
            WHERE id = ?
        ")->execute([$inserted, $campaignId]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonErr('Upload failed: ' . $e->getMessage());
    }

    jsonOk([
        'inserted' => $inserted,
        'skipped'  => $skipped,
        'message'  => $inserted . ' leads uploaded' . ($skipped ? ', ' . $skipped . ' skipped' : '')
    ]);
}


// ════════════════════════════════════════════════════════
// LEAD ASSIGNMENT
// ════════════════════════════════════════════════════════

function assignLeads(array $b, int $pid): void {
    $pdo    = getDB();
    $tcId   = intSafe($b['telecaller_id'] ?? 0);
    $leadIds = array_map('intval', $b['lead_ids'] ?? []);
    if (!$tcId)           jsonErr('telecaller_id required');
    if (empty($leadIds))  jsonErr('lead_ids required');

    // Verify telecaller belongs to partner
    $tc = $pdo->prepare("SELECT id FROM telecallers WHERE id=? AND partner_id=? AND status='active'");
    $tc->execute([$tcId, $pid]);
    if (!$tc->fetch()) jsonErr('Telecaller not found', 404);

    $placeholders = implode(',', array_fill(0, count($leadIds), '?'));
    $params = array_merge([$tcId], $leadIds, [$pid]);

    $pdo->prepare("
        UPDATE calling_leads
        SET assigned_telecaller_id = ?
        WHERE id IN ($placeholders)
          AND partner_id = ?
          AND assigned_telecaller_id IS NULL
    ")->execute($params);

    $assigned = $pdo->rowCount();

    // Update campaign assigned count
    $pdo->prepare("
        UPDATE calling_batches b
        SET assigned_count = (
            SELECT COUNT(*) FROM calling_leads
            WHERE batch_id = b.id AND assigned_telecaller_id IS NOT NULL
        )
        WHERE b.partner_id = ?
    ")->execute([$pid]);

    jsonOk(['assigned' => $assigned, 'message' => $assigned . ' leads assigned']);
}

function autoAssign(array $b, int $pid): void {
    $pdo        = getDB();
    $campaignId = intSafe($b['campaign_id'] ?? 0);
    $tcIds      = array_map('intval', $b['telecaller_ids'] ?? []);

    if (!$campaignId)    jsonErr('campaign_id required');
    if (empty($tcIds))   jsonErr('telecaller_ids required');

    // Verify all telecallers belong to partner
    $placeholders = implode(',', array_fill(0, count($tcIds), '?'));
    $tcCheck = $pdo->prepare("
        SELECT COUNT(*) FROM telecallers
        WHERE id IN ($placeholders) AND partner_id = ? AND status = 'active'
    ");
    $tcCheck->execute(array_merge($tcIds, [$pid]));
    if ((int)$tcCheck->fetchColumn() !== count($tcIds)) jsonErr('Invalid telecallers');

    // Get unassigned leads from campaign sorted by priority
    $leads = $pdo->prepare("
        SELECT id FROM calling_leads
        WHERE batch_id = ? AND partner_id = ?
          AND assigned_telecaller_id IS NULL
        ORDER BY priority_score DESC, id ASC
    ");
    $leads->execute([$campaignId, $pid]);
    $leadIds = $leads->fetchAll(PDO::FETCH_COLUMN);

    if (empty($leadIds)) jsonErr('No unassigned leads in this campaign');

    // Round-robin distribution
    $totalLeads = count($leadIds);
    $tcCount    = count($tcIds);
    $perTc      = ceil($totalLeads / $tcCount);

    $assignments = []; // tcId => [leadIds]
    foreach ($tcIds as $i => $tcId) {
        $assignments[$tcId] = array_slice($leadIds, $i * $perTc, $perTc);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE calling_leads SET assigned_telecaller_id=?, assigned_at=NOW() WHERE id=?");
        $totalAssigned = 0;
        foreach ($assignments as $tcId => $ids) {
            foreach ($ids as $lid) {
                $stmt->execute([$tcId, $lid]);
                $totalAssigned++;
            }
        }

        // Update campaign
        $pdo->prepare("
            UPDATE calling_batches
            SET assigned_count = assigned_count + ?
            WHERE id = ?
        ")->execute([$totalAssigned, $campaignId]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonErr('Assignment failed');
    }

    // Return per-telecaller breakdown
    $breakdown = [];
    foreach ($assignments as $tcId => $ids) {
        $tc = $pdo->prepare("SELECT name FROM telecallers WHERE id=?");
        $tc->execute([$tcId]);
        $breakdown[] = [
            'telecaller_id'   => $tcId,
            'telecaller_name' => $tc->fetchColumn(),
            'leads_assigned'  => count($ids)
        ];
    }

    jsonOk([
        'total_assigned' => $totalAssigned,
        'breakdown'      => $breakdown,
        'message'        => $totalAssigned . ' leads distributed equally'
    ]);
}


// ════════════════════════════════════════════════════════
// ANALYTICS & REPORTING
// ════════════════════════════════════════════════════════

function getPartnerDashboard(int $pid): void {
    $pdo   = getDB();
    $today = date('Y-m-d');

    // Overall stats
    $overall = $pdo->prepare("
        SELECT
            COUNT(*)                                  AS total_leads,
            SUM(assigned_telecaller_id IS NOT NULL)   AS assigned,
            SUM(assigned_telecaller_id IS NULL)       AS unassigned,
            SUM(current_status = 'pending')           AS pending,
            SUM(current_status = 'interested')        AS interested,
            SUM(current_status = 'converted')         AS converted,
            SUM(current_status = 'follow_up')         AS followup,
            SUM(current_status = 'not_interested')    AS not_interested,
            SUM(DATE(updated_at) = CURDATE()
                AND current_status != 'pending')      AS called_today
        FROM calling_leads
        WHERE partner_id = ?
    ");
    $overall->execute([$pid]);
    $stats = $overall->fetch(PDO::FETCH_ASSOC);

    // Today's calls
    $todayCalls = $pdo->prepare("
        SELECT COUNT(*) AS total_calls_today
        FROM call_logs
        WHERE partner_id = ? AND DATE(created_at) = ?
    ");
    $todayCalls->execute([$pid, $today]);
    $stats['total_calls_today'] = (int)$todayCalls->fetchColumn();

    // Followups due today
    $fuDue = $pdo->prepare("
        SELECT COUNT(*) FROM calling_leads
        WHERE partner_id = ?
          AND followup_done = 0
          AND followup_at IS NOT NULL
          AND DATE(followup_at) = CURDATE()
    ");
    $fuDue->execute([$pid]);
    $stats['followups_due_today'] = (int)$fuDue->fetchColumn();

    // Per-telecaller performance today
    $tcPerf = $pdo->prepare("
        SELECT
            t.id, t.name,
            COUNT(cl.id)                              AS assigned_leads,
            SUM(cl.current_status != 'pending')       AS called,
            SUM(cl.current_status = 'interested')     AS interested,
            SUM(cl.current_status = 'converted')      AS converted,
            COALESCE(ts.score, 0)                     AS today_score,
            COALESCE(ts.total_calls, 0)               AS calls_today
        FROM telecallers t
        LEFT JOIN calling_leads cl ON cl.assigned_telecaller_id = t.id
        LEFT JOIN telecaller_scores ts ON ts.telecaller_id = t.id
                                      AND ts.score_date = CURDATE()
        WHERE t.partner_id = ? AND t.status = 'active'
        GROUP BY t.id
        ORDER BY calls_today DESC
    ");
    $tcPerf->execute([$pid]);

    // Recent interested leads
    $hotLeads = $pdo->prepare("
        SELECT cl.id, cl.customer_name, cl.city, cl.loan_type,
               cl.loan_amount, cl.cibil, cl.updated_at,
               t.name AS telecaller_name
        FROM calling_leads cl
        LEFT JOIN telecallers t ON t.id = cl.assigned_telecaller_id
        WHERE cl.partner_id = ?
          AND cl.current_status = 'interested'
        ORDER BY cl.updated_at DESC LIMIT 10
    ");
    $hotLeads->execute([$pid]);

    jsonOk([
        'stats'          => $stats,
        'telecallers'    => $tcPerf->fetchAll(PDO::FETCH_ASSOC),
        'hot_leads'      => $hotLeads->fetchAll(PDO::FETCH_ASSOC),
    ]);
}

function getCampaignStats(array $b, int $pid): void {
    $pdo = getDB();
    $cid = intSafe($b['campaign_id'] ?? $_GET['campaign_id'] ?? 0);
    if (!$cid) jsonErr('campaign_id required');

    // Verify ownership
    $own = $pdo->prepare("SELECT * FROM calling_batches WHERE id=? AND partner_id=?");
    $own->execute([$cid, $pid]);
    $campaign = $own->fetch(PDO::FETCH_ASSOC);
    if (!$campaign) jsonErr('Campaign not found', 404);

    // Status breakdown
    $status = $pdo->prepare("
        SELECT current_status, COUNT(*) AS count
        FROM calling_leads
        WHERE batch_id=? AND partner_id=?
        GROUP BY current_status
    ");
    $status->execute([$cid, $pid]);

    // Per-telecaller breakdown
    $tc = $pdo->prepare("
        SELECT
            t.name, t.id,
            COUNT(cl.id)                          AS assigned,
            SUM(cl.current_status != 'pending')   AS called,
            SUM(cl.current_status = 'interested') AS interested,
            SUM(cl.current_status = 'converted')  AS converted,
            SUM(cl.current_status = 'follow_up')  AS followup,
            SUM(cl.call_count)                    AS total_calls_made
        FROM telecallers t
        JOIN calling_leads cl ON cl.assigned_telecaller_id = t.id
        WHERE cl.batch_id = ? AND cl.partner_id = ?
        GROUP BY t.id
        ORDER BY interested DESC
    ");
    $tc->execute([$cid, $pid]);

    // Daily progress (last 7 days)
    $daily = $pdo->prepare("
        SELECT
            DATE(updated_at) AS call_date,
            COUNT(*) AS calls_made,
            SUM(current_status = 'interested') AS interested
        FROM calling_leads
        WHERE batch_id = ? AND partner_id = ?
          AND current_status != 'pending'
          AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(updated_at)
        ORDER BY call_date ASC
    ");
    $daily->execute([$cid, $pid]);

    jsonOk([
        'campaign'      => $campaign,
        'status_breakdown' => $status->fetchAll(PDO::FETCH_ASSOC),
        'telecallers'   => $tc->fetchAll(PDO::FETCH_ASSOC),
        'daily_progress'=> $daily->fetchAll(PDO::FETCH_ASSOC),
    ]);
}

function listLeads(int $pid): void {
    $pdo    = getDB();
    $cid    = intSafe($_GET['campaign_id'] ?? 0);
    $tcId   = intSafe($_GET['telecaller_id'] ?? 0);
    $status = sanitize($_GET['status'] ?? '');
    $search = sanitize($_GET['search'] ?? '');
    $limit  = min(100, intSafe($_GET['limit'] ?? 50));
    $offset = intSafe($_GET['offset'] ?? 0);

    $where  = ['cl.partner_id = ?'];
    $params = [$pid];

    if ($cid)    { $where[] = 'cl.batch_id = ?';                $params[] = $cid; }
    if ($tcId)   { $where[] = 'cl.assigned_telecaller_id = ?';  $params[] = $tcId; }
    if ($status) { $where[] = 'cl.current_status = ?';          $params[] = $status; }
    if ($search) {
        $where[]  = '(cl.customer_name LIKE ? OR cl.mobile LIKE ? OR cl.city LIKE ?)';
        $like     = '%'.$search.'%';
        $params   = array_merge($params, [$like, $like, $like]);
    }

    $whereStr = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT cl.*,
               t.name AS telecaller_name,
               b.batch_name
        FROM calling_leads cl
        LEFT JOIN telecallers t ON t.id = cl.assigned_telecaller_id
        LEFT JOIN calling_batches b ON b.id = cl.batch_id
        WHERE $whereStr
        ORDER BY cl.priority_score DESC, cl.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute($params);

    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM calling_leads cl WHERE $whereStr");
    $cStmt->execute($params);

    jsonOk([
        'leads'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total'  => (int)$cStmt->fetchColumn(),
        'offset' => $offset
    ]);
}

function exportLeads(array $b, int $pid): void {
    $pdo = getDB();
    $cid = intSafe($b['campaign_id'] ?? 0);

    $where  = ['cl.partner_id = ?'];
    $params = [$pid];
    if ($cid) { $where[] = 'cl.batch_id = ?'; $params[] = $cid; }

    $stmt = $pdo->prepare("
        SELECT
            cl.customer_name      AS 'Customer Name',
            cl.mobile             AS 'Mobile',
            cl.city               AS 'City',
            cl.loan_type          AS 'Loan Type',
            cl.loan_amount        AS 'Loan Amount',
            cl.income             AS 'Monthly Income',
            cl.cibil              AS 'CIBIL',
            cl.property_type      AS 'Property Type',
            cl.remarks   AS 'Original Remarks',
            t.name                AS 'Assigned Telecaller',
            cl.current_status     AS 'Status',
            cl.call_notes         AS 'Call Notes',
            cl.call_count         AS 'Total Calls',
            cl.first_called_at    AS 'First Called',
            cl.last_called_at     AS 'Last Called',
            cl.followup_at        AS 'Follow-up Date',
            cl.created_at         AS 'Uploaded On'
        FROM calling_leads cl
        LEFT JOIN telecallers t ON t.id = cl.assigned_telecaller_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY cl.priority_score DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonOk(['data' => $rows, 'count' => count($rows)]);
}

function recycleLeads(array $b, int $pid): void {
    $pdo   = getDB();
    $cid   = intSafe($b['campaign_id'] ?? 0);
    $days  = intSafe($b['days'] ?? 30);
    $tcIds = array_map('intval', $b['telecaller_ids'] ?? []);

    if (!$cid) jsonErr('campaign_id required');

    // Find leads not responded to in X days
    $stmt = $pdo->prepare("
        SELECT id FROM calling_leads
        WHERE batch_id = ? AND partner_id = ?
          AND current_status IN ('not_reachable', 'pending')
          AND last_called_at < DATE_SUB(NOW(), INTERVAL ? DAY)
          AND recycled_count < 2
    ");
    $stmt->execute([$cid, $pid, $days]);
    $leadIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($leadIds)) jsonOk(['message' => 'No leads to recycle', 'count' => 0]);

    // Reset status and reassign
    $placeholders = implode(',', array_fill(0, count($leadIds), '?'));
    $pdo->prepare("
        UPDATE calling_leads
        SET current_status   = 'pending',
            assigned_telecaller_id = NULL,
            is_recycled      = 1,
            recycled_count   = recycled_count + 1
        WHERE id IN ($placeholders) AND partner_id = ?
    ")->execute(array_merge($leadIds, [$pid]));

    jsonOk([
        'recycled' => count($leadIds),
        'message'  => count($leadIds) . ' leads recycled and ready for reassignment'
    ]);
}

function getScoreReport(int $pid): void {
    $pdo  = getDB();
    $week = date('Y-\WW');

    $stmt = $pdo->prepare("
        SELECT
            t.id, t.name,
            SUM(ts.total_calls)      AS total_calls,
            SUM(ts.interested_count) AS total_interested,
            SUM(ts.followup_count)   AS total_followups,
            SUM(ts.converted_count)  AS total_converted,
            SUM(ts.missed_followups) AS missed_followups,
            SUM(ts.score)            AS total_score,
            COUNT(ts.id)             AS days_active
        FROM telecallers t
        LEFT JOIN telecaller_scores ts ON ts.telecaller_id = t.id
                                       AND ts.report_week = ?
        WHERE t.partner_id = ? AND t.status = 'active'
        GROUP BY t.id
        ORDER BY total_score DESC
    ");
    $stmt->execute([$week, $pid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rank them
    foreach ($rows as $i => &$row) {
        $row['rank'] = $i + 1;
        $row['badge'] = $i === 0 ? '🏆 Champion'
                      : ($i === 1 ? '🥈 Runner-up'
                      : ($i === 2 ? '🥉 Third' : ''));
    }

    jsonOk([
        'week'    => $week,
        'scores'  => $rows,
        'best'    => $rows[0] ?? null,
    ]);
}


// ════════════════════════════════════════════════════════
// PRIORITY SCORING ENGINE
// ════════════════════════════════════════════════════════
function calculatePriorityScore(array $row): int {
    $score = 0;
    $cibil  = cleanCibil($row['cibil'] ?? '');
    $amount = cleanAmount($row['loan_amount'] ?? $row['amount'] ?? '');
    $city   = strtolower(trim($row['city'] ?? ''));

    if ($cibil >= 750) $score += 30;
    elseif ($cibil >= 700) $score += 20;
    elseif ($cibil >= 650) $score += 10;
    elseif ($cibil > 0)    $score += 5;

    if ($amount >= 5000000) $score += 25;       // 50L+
    elseif ($amount >= 2000000) $score += 15;   // 20L+
    elseif ($amount >= 1000000) $score += 8;    // 10L+

    $highCities = ['jaipur','delhi','mumbai','bangalore','pune','hyderabad'];
    if (in_array($city, $highCities)) $score += 15;

    $source = strtolower($row['source'] ?? '');
    if (str_contains($source, 'referral')) $score += 10;
    if (str_contains($source, 'website'))  $score += 8;

    $loanType = strtolower($row['loan_type'] ?? $row['loantype'] ?? '');
    if (str_contains($loanType, 'home'))     $score += 10;
    if (str_contains($loanType, 'property')) $score += 10;

    return min(100, $score);
}


// ════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════
function cleanAmount($val): ?int {
    if (!$val) return null;
    $n = (int)preg_replace('/[^0-9]/', '', (string)$val);
    return $n > 0 ? $n : null;
}

function cleanCibil($val): int {
    $n = (int)preg_replace('/\D/', '', (string)$val);
    return ($n >= 300 && $n <= 900) ? $n : 0;
}

function sanitize(string $val, int $max = 255): string {
    return mb_substr(htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8'), 0, $max);
}

function intSafe($val): int {
    return (int)preg_replace('/\D/', '', (string)$val);
}

function jsonOk(array $data): void {
    echo json_encode(array_merge(['status' => 'ok'], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonErr(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}