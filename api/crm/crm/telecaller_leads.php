<?php
/**
 * WealthMetre — Telecaller Leads API
 * Path: /api/crm/telecaller_leads.php
 *
 * Actions:
 *   get_my_leads      → paginated lead list for telecaller
 *   get_lead          → single lead detail
 *   start_call        → log call start time
 *   end_call          → log call end, force status update
 *   update_status     → update lead status + notes
 *   save_answers      → save questionnaire answers
 *   get_followups     → leads with followup due today
 *   dashboard         → telecaller dashboard stats
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/telecaller_auth.php';

// Authenticate every request
$tc     = authenticateTelecaller();
$tcId   = (int)$tc['id'];
$partId = (int)$tc['partner_id'];

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_my_leads':    getMyLeads($tcId, $partId);          break;
    case 'get_lead':        getLead($body, $tcId, $partId);      break;
    case 'start_call':      startCall($body, $tcId, $partId);    break;
    case 'end_call':        endCall($body, $tcId, $partId);      break;
    case 'update_status':   updateStatus($body, $tcId, $partId); break;
    case 'save_answers':    saveAnswers($body, $tcId, $partId);  break;
    case 'get_followups':   getFollowups($tcId, $partId);        break;
    case 'dashboard':       getDashboard($tcId, $partId);        break;
    default: jsonErr('Invalid action');
}


// ════════════════════════════════════════════════════════
// GET MY LEADS
// ════════════════════════════════════════════════════════
function getMyLeads(int $tcId, int $partId): void {
    $pdo    = getDB();
    $filter = sanitize($_GET['filter'] ?? 'all');
    $search = sanitize($_GET['search'] ?? '');
    $limit  = min(50, (int)($_GET['limit'] ?? 30));
    $offset = (int)($_GET['offset'] ?? 0);

    // Priority order: follow_up today first, then pending, then rest
    $where  = ['cl.assigned_telecaller_id = ?', 'cl.partner_id = ?'];
    $params = [$tcId, $partId];

    if ($filter === 'pending') {
        $where[] = "cl.current_status = 'pending'";
    } elseif ($filter === 'followup_today') {
        $where[] = "DATE(cl.followup_at) = CURDATE()";
        $where[] = "cl.followup_done = 0";
    } elseif ($filter === 'interested') {
        $where[] = "cl.current_status = 'interested'";
    } elseif ($filter === 'not_reachable') {
        $where[] = "cl.current_status = 'not_reachable'";
    } elseif ($filter === 'converted') {
        $where[] = "cl.current_status = 'converted'";
    } elseif ($filter !== 'all') {
        $where[] = "cl.current_status = ?";
        $params[] = $filter;
    }

    if ($search) {
        $where[] = "(cl.customer_name LIKE ? OR cl.mobile LIKE ? OR cl.city LIKE ?)";
        $like     = '%'.$search.'%';
        $params   = array_merge($params, [$like, $like, $like]);
    }

    $whereStr = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT
            cl.id, cl.customer_name, cl.mobile, cl.city, cl.state,
            cl.loan_type, cl.loan_amount, cl.income, cl.cibil,
            cl.property_type, cl.current_status, cl.call_count,
            cl.last_called_at, cl.followup_at, cl.followup_done,
            cl.priority_score, cl.uploaded_remarks, cl.call_notes,
            cl.is_dnd, cl.consent_given,
            b.batch_name
        FROM calling_leads cl
        LEFT JOIN calling_batches b ON b.id = cl.batch_id
        WHERE $whereStr
        ORDER BY
            CASE
                WHEN DATE(cl.followup_at) = CURDATE() AND cl.followup_done = 0 THEN 0
                WHEN cl.current_status = 'pending' AND cl.call_count = 0 THEN 1
                WHEN cl.current_status = 'pending' THEN 2
                ELSE 3
            END,
            cl.priority_score DESC,
            cl.created_at ASC
        LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $cStmt = $pdo->prepare("
        SELECT COUNT(*) FROM calling_leads cl WHERE $whereStr
    ");
    $cParams = array_slice($params, 0, -2);
    $cStmt->execute($cParams);
    $total = (int)$cStmt->fetchColumn();

    jsonOk(['leads' => $leads, 'total' => $total, 'offset' => $offset]);
}


// ════════════════════════════════════════════════════════
// GET SINGLE LEAD
// ════════════════════════════════════════════════════════
function getLead(array $b, int $tcId, int $partId): void {
    $leadId = (int)($b['lead_id'] ?? $_GET['lead_id'] ?? 0);
    if (!$leadId) jsonErr('lead_id required');

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT cl.*, b.batch_name
        FROM calling_leads cl
        LEFT JOIN calling_batches b ON b.id = cl.batch_id
        WHERE cl.id = ? AND cl.assigned_telecaller_id = ? AND cl.partner_id = ?
    ");
    $stmt->execute([$leadId, $tcId, $partId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lead) jsonErr('Lead not found', 404);

    // Get call history
    $logs = $pdo->prepare("
        SELECT * FROM call_logs
        WHERE lead_id = ? AND telecaller_id = ?
        ORDER BY created_at DESC LIMIT 10
    ");
    $logs->execute([$leadId, $tcId]);

    // Get questionnaire answers
    $answers = $pdo->prepare("
        SELECT qa.*, qq.question_text, qq.question_type
        FROM questionnaire_answers qa
        JOIN questionnaire_questions qq ON qq.id = qa.question_id
        WHERE qa.lead_id = ?
    ");
    $answers->execute([$leadId]);

    // Get active questionnaire for this partner
    $qStmt = $pdo->prepare("
        SELECT q.*, GROUP_CONCAT(
            JSON_OBJECT(
                'id', qq.id,
                'text', qq.question_text,
                'type', qq.question_type,
                'options', qq.options_json,
                'required', qq.is_required
            ) ORDER BY qq.sort_order
        ) AS questions_json
        FROM questionnaires q
        JOIN questionnaire_questions qq ON qq.questionnaire_id = q.id
        WHERE q.partner_id = ? AND q.status = 'active'
        GROUP BY q.id LIMIT 1
    ");
    $qStmt->execute([$partId]);
    $questionnaire = $qStmt->fetch(PDO::FETCH_ASSOC);

    // Get call script
    $scriptStmt = $pdo->prepare("
        SELECT * FROM call_scripts
        WHERE partner_id = ? AND status = 'active'
          AND (loan_type = ? OR loan_type IS NULL)
        ORDER BY loan_type DESC LIMIT 1
    ");
    $scriptStmt->execute([$partId, $lead['loan_type'] ?? '']);

    jsonOk([
        'lead'          => $lead,
        'call_logs'     => $logs->fetchAll(PDO::FETCH_ASSOC),
        'answers'       => $answers->fetchAll(PDO::FETCH_ASSOC),
        'questionnaire' => $questionnaire,
        'call_script'   => $scriptStmt->fetch(PDO::FETCH_ASSOC) ?: null,
    ]);
}


// ════════════════════════════════════════════════════════
// START CALL
// ════════════════════════════════════════════════════════
function startCall(array $b, int $tcId, int $partId): void {
    $leadId = (int)($b['lead_id'] ?? 0);
    if (!$leadId) jsonErr('lead_id required');

    $pdo = getDB();

    // Verify ownership
    $chk = $pdo->prepare("SELECT id FROM calling_leads WHERE id=? AND assigned_telecaller_id=? AND partner_id=?");
    $chk->execute([$leadId, $tcId, $partId]);
    if (!$chk->fetch()) jsonErr('Lead not found', 404);

    // Insert call log
    $pdo->prepare("
        INSERT INTO call_logs (lead_id, partner_id, telecaller_id, call_start_time)
        VALUES (?, ?, ?, NOW())
    ")->execute([$leadId, $partId, $tcId]);
    $callLogId = $pdo->lastInsertId();

    // Update lead
    $pdo->prepare("
        UPDATE calling_leads
        SET last_called_at   = NOW(),
            first_called_at  = COALESCE(first_called_at, NOW()),
            call_count       = call_count + 1
        WHERE id = ?
    ")->execute([$leadId]);

    // Log activity
    $pdo->prepare("
        INSERT INTO telecaller_activity (telecaller_id, partner_id, activity_type, notes)
        VALUES (?, ?, 'call_start', ?)
    ")->execute([$tcId, $partId, 'Lead #'.$leadId]);

    jsonOk(['call_log_id' => $callLogId, 'started_at' => date('Y-m-d H:i:s')]);
}


// ════════════════════════════════════════════════════════
// END CALL — Must provide outcome
// ════════════════════════════════════════════════════════
function endCall(array $b, int $tcId, int $partId): void {
    $leadId    = (int)($b['lead_id'] ?? 0);
    $callLogId = (int)($b['call_log_id'] ?? 0);
    $outcome   = sanitize($b['outcome'] ?? '');

    if (!$leadId)    jsonErr('lead_id required');
    if (!$callLogId) jsonErr('call_log_id required');
    if (!$outcome)   jsonErr('outcome is required before ending call');

    $validOutcomes = ['interested','not_interested','follow_up','not_reachable',
                      'wrong_number','callback_later','loan_taken','documents_pending',
                      'eligible','not_eligible','converted','rejected'];
    if (!in_array($outcome, $validOutcomes)) jsonErr('Invalid outcome');

    $pdo = getDB();

    // Update call log
    $pdo->prepare("
        UPDATE call_logs
        SET call_end_time    = NOW(),
            call_duration_sec = TIMESTAMPDIFF(SECOND, call_start_time, NOW()),
            outcome          = ?,
            notes            = ?,
            followup_at      = ?,
            rejection_reason = ?,
            consent_given    = ?
        WHERE id = ? AND telecaller_id = ?
    ")->execute([
        $outcome,
        sanitize($b['notes'] ?? ''),
        $b['followup_at'] ?? null,
        sanitize($b['rejection_reason'] ?? ''),
        (int)($b['consent_given'] ?? 0),
        $callLogId,
        $tcId
    ]);

    // Map outcome to lead status
    $statusMap = [
        'interested'        => 'interested',
        'not_interested'    => 'not_interested',
        'follow_up'         => 'follow_up',
        'not_reachable'     => 'not_reachable',
        'wrong_number'      => 'wrong_number',
        'callback_later'    => 'follow_up',
        'loan_taken'        => 'loan_taken',
        'documents_pending' => 'documents_pending',
        'eligible'          => 'eligible',
        'not_eligible'      => 'not_eligible',
        'converted'         => 'converted',
        'rejected'          => 'rejected',
    ];

    $newStatus = $statusMap[$outcome] ?? 'pending';

    $pdo->prepare("
        UPDATE calling_leads
        SET current_status   = ?,
            call_notes       = ?,
            followup_at      = ?,
            followup_done    = 0,
            consent_given    = ?,
            rejection_reason = ?
        WHERE id = ? AND assigned_telecaller_id = ?
    ")->execute([
        $newStatus,
        sanitize($b['notes'] ?? ''),
        $b['followup_at'] ?? null,
        (int)($b['consent_given'] ?? 0),
        sanitize($b['rejection_reason'] ?? ''),
        $leadId, $tcId
    ]);

    // Update today's score
    updateDailyScore($tcId, $partId, $outcome, $pdo);

    jsonOk([
        'message'    => 'Call logged successfully',
        'new_status' => $newStatus,
        'lead_id'    => $leadId
    ]);
}


// ════════════════════════════════════════════════════════
// UPDATE STATUS (without call log)
// ════════════════════════════════════════════════════════
function updateStatus(array $b, int $tcId, int $partId): void {
    $leadId = (int)($b['lead_id'] ?? 0);
    if (!$leadId) jsonErr('lead_id required');

    $pdo = getDB();
    $chk = $pdo->prepare("SELECT id FROM calling_leads WHERE id=? AND assigned_telecaller_id=?");
    $chk->execute([$leadId, $tcId]);
    if (!$chk->fetch()) jsonErr('Lead not found', 404);

    $pdo->prepare("
        UPDATE calling_leads
        SET current_status   = ?,
            call_notes       = ?,
            followup_at      = ?,
            rejection_reason = ?
        WHERE id = ?
    ")->execute([
        sanitize($b['status'] ?? ''),
        sanitize($b['notes'] ?? ''),
        $b['followup_at'] ?? null,
        sanitize($b['rejection_reason'] ?? ''),
        $leadId
    ]);

    jsonOk(['message' => 'Status updated']);
}


// ════════════════════════════════════════════════════════
// SAVE QUESTIONNAIRE ANSWERS
// ════════════════════════════════════════════════════════
function saveAnswers(array $b, int $tcId, int $partId): void {
    $leadId  = (int)($b['lead_id'] ?? 0);
    $answers = $b['answers'] ?? [];

    if (!$leadId)       jsonErr('lead_id required');
    if (empty($answers)) jsonErr('No answers provided');

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO questionnaire_answers
            (lead_id, questionnaire_id, question_id, answer, answered_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE answer = VALUES(answer), answered_by = VALUES(answered_by)
    ");

    foreach ($answers as $ans) {
        $stmt->execute([
            $leadId,
            (int)($ans['questionnaire_id'] ?? 0),
            (int)($ans['question_id'] ?? 0),
            sanitize($ans['answer'] ?? ''),
            $tcId
        ]);
    }

    jsonOk(['message' => 'Answers saved', 'count' => count($answers)]);
}


// ════════════════════════════════════════════════════════
// GET TODAY'S FOLLOWUPS
// ════════════════════════════════════════════════════════
function getFollowups(int $tcId, int $partId): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT id, customer_name, mobile, city, loan_type,
               current_status, followup_at, call_count, call_notes
        FROM calling_leads
        WHERE assigned_telecaller_id = ?
          AND partner_id = ?
          AND followup_done = 0
          AND followup_at IS NOT NULL
          AND DATE(followup_at) <= CURDATE()
        ORDER BY followup_at ASC
        LIMIT 50
    ");
    $stmt->execute([$tcId, $partId]);
    jsonOk(['followups' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}


// ════════════════════════════════════════════════════════
// TELECALLER DASHBOARD
// ════════════════════════════════════════════════════════
function getDashboard(int $tcId, int $partId): void {
    $pdo   = getDB();
    $today = date('Y-m-d');

    // Lead counts
    $counts = $pdo->prepare("
        SELECT
            COUNT(*)                                   AS total_assigned,
            SUM(current_status = 'pending')            AS pending,
            SUM(current_status = 'interested')         AS interested,
            SUM(current_status = 'follow_up')          AS followup,
            SUM(current_status = 'converted')          AS converted,
            SUM(current_status = 'not_reachable')      AS not_reachable,
            SUM(current_status = 'not_interested')     AS not_interested,
            SUM(followup_done = 0
                AND followup_at IS NOT NULL
                AND DATE(followup_at) = CURDATE())     AS followups_today
        FROM calling_leads
        WHERE assigned_telecaller_id = ? AND partner_id = ?
    ");
    $counts->execute([$tcId, $partId]);
    $leadStats = $counts->fetch(PDO::FETCH_ASSOC);

    // Today's calls
    $todayCalls = $pdo->prepare("
        SELECT
            COUNT(*)                              AS calls_today,
            SUM(outcome = 'interested')           AS interested_today,
            SUM(outcome = 'converted')            AS converted_today,
            AVG(call_duration_sec)                AS avg_duration_sec
        FROM call_logs
        WHERE telecaller_id = ? AND DATE(created_at) = ?
    ");
    $todayCalls->execute([$tcId, $today]);
    $callStats = $todayCalls->fetch(PDO::FETCH_ASSOC);

    // Today's score
    $scoreStmt = $pdo->prepare("
        SELECT score, total_calls, rank_in_team
        FROM telecaller_scores
        WHERE telecaller_id = ? AND score_date = ?
    ");
    $scoreStmt->execute([$tcId, $today]);
    $score = $scoreStmt->fetch(PDO::FETCH_ASSOC);

    // Break time today
    $breakStmt = $pdo->prepare("
        SELECT COALESCE(SUM(break_duration), 0) AS total_break_secs
        FROM break_logs
        WHERE telecaller_id = ? AND break_date = ?
    ");
    $breakStmt->execute([$tcId, $today]);
    $breaks = $breakStmt->fetch(PDO::FETCH_ASSOC);

    // Next lead to call (highest priority pending)
    $nextLead = $pdo->prepare("
        SELECT id, customer_name, city, loan_type, cibil, loan_amount,
               priority_score, call_count
        FROM calling_leads
        WHERE assigned_telecaller_id = ?
          AND partner_id = ?
          AND current_status IN ('pending','not_reachable')
          AND is_dnd = 0
        ORDER BY
            CASE WHEN DATE(followup_at) = CURDATE() THEN 0 ELSE 1 END,
            priority_score DESC
        LIMIT 1
    ");
    $nextLead->execute([$tcId, $partId]);

    jsonOk([
        'lead_stats'   => $leadStats,
        'call_stats'   => $callStats,
        'today_score'  => $score,
        'break_time'   => $breaks,
        'next_lead'    => $nextLead->fetch(PDO::FETCH_ASSOC) ?: null,
    ]);
}


// ════════════════════════════════════════════════════════
// SCORING ENGINE
// ════════════════════════════════════════════════════════
function updateDailyScore(int $tcId, int $partId, string $outcome, PDO $pdo): void {
    $today = date('Y-m-d');
    $week  = date('Y-\WW');

    // Score points per outcome
    $points = [
        'interested'        => 5,
        'follow_up'         => 3,
        'converted'         => 20,
        'not_interested'    => 0,
        'not_reachable'     => 0.5,
        'wrong_number'      => 0,
        'callback_later'    => 1,
        'documents_pending' => 2,
        'eligible'          => 3,
        'not_eligible'      => 0,
        'loan_taken'        => 0,
        'rejected'          => 0,
    ];
    $outcomePoints = $points[$outcome] ?? 0;

    $pdo->prepare("
        INSERT INTO telecaller_scores
            (partner_id, telecaller_id, score_date, report_week,
             total_calls, interested_count, followup_count,
             converted_count, score)
        VALUES (?, ?, ?, ?, 1,
            ?,  -- interested
            ?,  -- followup
            ?,  -- converted
            1 + ?  -- base call + outcome points
        )
        ON DUPLICATE KEY UPDATE
            total_calls      = total_calls + 1,
            interested_count = interested_count + IF(? = 'interested', 1, 0),
            followup_count   = followup_count   + IF(? IN ('follow_up','callback_later'), 1, 0),
            converted_count  = converted_count  + IF(? = 'converted', 1, 0),
            score            = score + 1 + ?
    ")->execute([
        $partId, $tcId, $today, $week,
        ($outcome === 'interested' ? 1 : 0),
        (in_array($outcome, ['follow_up','callback_later']) ? 1 : 0),
        ($outcome === 'converted' ? 1 : 0),
        $outcomePoints,
        $outcome, $outcome, $outcome, $outcomePoints
    ]);
}

function sanitize(string $val, int $max = 500): string {
    return mb_substr(htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8'), 0, $max);
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