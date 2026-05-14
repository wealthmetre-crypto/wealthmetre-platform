<?php
/**
 * WealthMetre — Questionnaire API
 * Path: /api/crm/questionnaire_api.php
 *
 * Partner Actions:
 *   create_questionnaire   → create new questionnaire
 *   list_questionnaires    → list partner's questionnaires
 *   get_questionnaire      → get with all questions
 *   update_questionnaire   → edit name/status
 *   delete_questionnaire   → remove
 *   add_question           → add question to questionnaire
 *   update_question        → edit a question
 *   delete_question        → remove a question
 *   reorder_questions      → drag-and-drop reorder
 *
 * Telecaller Actions:
 *   get_for_lead           → get active questionnaire + existing answers
 *   save_answers           → save/update answers for a lead
 *   get_answers            → get saved answers for a lead
 *
 * Export:
 *   export_answers         → all answers for a campaign (CSV format)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = $body['action'] ?? $_GET['action'] ?? '';
$isTC    = false;
$actorId = 0;
$partnerId = 0;

// Detect if partner or telecaller is calling
$auth  = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
$token = $auth ?: ($_GET['token'] ?? '');

if ($token) {
    // Try partner first
    $partner = authenticatePartner();
    if ($partner) {
        $partnerId = (int)($partner['id'] ?? $partner['user_id'] ?? 0);
        $actorId   = $partnerId;
    } else {
        // Try telecaller
        $pdo  = getDB();
        $stmt = $pdo->prepare("
            SELECT t.*, p.id AS partner_id
            FROM telecaller_sessions ts
            JOIN telecallers t ON t.id = ts.telecaller_id
            JOIN partners    p ON p.id = t.partner_id
            WHERE ts.token = ? AND ts.expires_at > NOW() AND t.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $tc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tc) {
            $isTC      = true;
            $actorId   = (int)$tc['id'];
            $partnerId = (int)$tc['partner_id'];
        }
    }
}

if (!$actorId) jsonErr('Unauthorized', 401);

switch ($action) {
    // Partner actions
    case 'create_questionnaire': createQuestionnaire($body, $partnerId); break;
    case 'list_questionnaires':  listQuestionnaires($partnerId);          break;
    case 'get_questionnaire':    getQuestionnaire($body, $partnerId);     break;
    case 'update_questionnaire': updateQuestionnaire($body, $partnerId);  break;
    case 'delete_questionnaire': deleteQuestionnaire($body, $partnerId);  break;
    case 'add_question':         addQuestion($body, $partnerId);          break;
    case 'update_question':      updateQuestion($body, $partnerId);       break;
    case 'delete_question':      deleteQuestion($body, $partnerId);       break;
    case 'reorder_questions':    reorderQuestions($body, $partnerId);     break;
    case 'export_answers':       exportAnswers($body, $partnerId);        break;

    // Telecaller + partner
    case 'get_for_lead':  getForLead($body, $partnerId);                  break;
    case 'save_answers':  saveAnswers($body, $partnerId, $actorId, $isTC);break;
    case 'get_answers':   getAnswers($body, $partnerId);                  break;

    default: jsonErr('Invalid action');
}


// ════════════════════════════════════════════════════════
// QUESTIONNAIRE CRUD
// ════════════════════════════════════════════════════════

function createQuestionnaire(array $b, int $pid): void {
    $title    = sanitize($b['title'] ?? '');
    $loanType = sanitize($b['loan_type'] ?? '');
    if (!$title) jsonErr('Title required');

    $pdo = getDB();
    $pdo->prepare("
        INSERT INTO questionnaires (partner_id, title, loan_type, status)
        VALUES (?, ?, ?, 'active')
    ")->execute([$pid, $title, $loanType]);
    $qid = $pdo->lastInsertId();

    // Add default questions based on loan type
    if ($b['add_defaults'] ?? false) {
        addDefaultQuestions($qid, $loanType, $pdo);
    }

    jsonOk(['questionnaire_id' => $qid, 'message' => 'Questionnaire created']);
}

function addDefaultQuestions(int $qid, string $loanType, PDO $pdo): void {
    $defaults = [
        ['Loan kitna chahiye? (Amount in ₹)', 'number', null, 1, 1],
        ['Property ka type kya hai?', 'dropdown',
         '["JDA Approved","Society Patta","Registry","Under Construction","Agricultural","Other"]', 1, 2],
        ['Monthly income kitni hai? (₹ mein)', 'number', null, 1, 3],
        ['CIBIL score approximately?', 'dropdown',
         '["750+","700-749","650-699","600-649","Below 600","Nahi pata"]', 1, 4],
        ['Koi existing loan ya EMI chal rahi hai?', 'yes_no', null, 1, 5],
        ['Existing EMI amount (agar hai to)?', 'number', null, 0, 6],
        ['Property ki location / area?', 'text', null, 0, 7],
        ['Loan kab chahiye?', 'dropdown',
         '["Abhi urgent (1 hafte mein)","1 mahine mein","2-3 mahine mein","Sirf jankari chahiye"]', 1, 8],
        ['Income proof available hai?', 'dropdown',
         '["Sab documents hain","Kuch hain","Abhi nahi hain","Sirf bank statement hai"]', 0, 9],
        ['Balance transfer mein interest hai?', 'yes_no', null, 0, 10],
        ['Additional notes ya special requirements', 'remarks', null, 0, 11],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO questionnaire_questions
            (questionnaire_id, question_text, question_type, options_json, is_required, sort_order)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($defaults as $d) {
        $stmt->execute(array_merge([$qid], $d));
    }
}

function listQuestionnaires(int $pid): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT q.*,
               COUNT(qq.id) AS question_count,
               (SELECT COUNT(*) FROM questionnaire_answers qa
                JOIN questionnaire_questions qqs ON qqs.id = qa.question_id
                WHERE qqs.questionnaire_id = q.id) AS total_answers
        FROM questionnaires q
        LEFT JOIN questionnaire_questions qq ON qq.questionnaire_id = q.id
        WHERE q.partner_id = ?
        GROUP BY q.id
        ORDER BY q.created_at DESC
    ");
    $stmt->execute([$pid]);
    jsonOk(['questionnaires' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getQuestionnaire(array $b, int $pid): void {
    $qid = intSafe($b['questionnaire_id'] ?? $_GET['questionnaire_id'] ?? 0);
    if (!$qid) jsonErr('questionnaire_id required');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM questionnaires WHERE id=? AND partner_id=?");
    $stmt->execute([$qid, $pid]);
    $q = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$q) jsonErr('Questionnaire not found', 404);

    $qStmt = $pdo->prepare("
        SELECT * FROM questionnaire_questions
        WHERE questionnaire_id = ?
        ORDER BY sort_order ASC
    ");
    $qStmt->execute([$qid]);
    $q['questions'] = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse options_json for each question
    foreach ($q['questions'] as &$question) {
        if ($question['options_json']) {
            $question['options'] = json_decode($question['options_json'], true) ?? [];
        } else {
            $question['options'] = [];
        }
    }

    jsonOk(['questionnaire' => $q]);
}

function updateQuestionnaire(array $b, int $pid): void {
    $qid = intSafe($b['questionnaire_id'] ?? 0);
    if (!$qid) jsonErr('questionnaire_id required');

    $pdo = getDB();
    $own = $pdo->prepare("SELECT id FROM questionnaires WHERE id=? AND partner_id=?");
    $own->execute([$qid, $pid]);
    if (!$own->fetch()) jsonErr('Not found', 404);

    $fields = []; $vals = [];
    if (!empty($b['title']))    { $fields[] = 'title = ?';     $vals[] = sanitize($b['title']); }
    if (isset($b['status']))    { $fields[] = 'status = ?';    $vals[] = $b['status'] === 'active' ? 'active' : 'inactive'; }
    if (!empty($b['loan_type'])){ $fields[] = 'loan_type = ?'; $vals[] = sanitize($b['loan_type']); }
    if (empty($fields)) jsonErr('Nothing to update');

    $vals[] = $qid;
    $pdo->prepare("UPDATE questionnaires SET ".implode(',',$fields)." WHERE id=?")->execute($vals);
    jsonOk(['message' => 'Updated']);
}

function deleteQuestionnaire(array $b, int $pid): void {
    $qid = intSafe($b['questionnaire_id'] ?? 0);
    $pdo = getDB();
    $pdo->prepare("DELETE FROM questionnaires WHERE id=? AND partner_id=?")->execute([$qid, $pid]);
    jsonOk(['message' => 'Deleted']);
}


// ════════════════════════════════════════════════════════
// QUESTION CRUD
// ════════════════════════════════════════════════════════

function addQuestion(array $b, int $pid): void {
    $qid  = intSafe($b['questionnaire_id'] ?? 0);
    $text = sanitize($b['question_text'] ?? '', 500);
    $type = $b['question_type'] ?? 'text';
    if (!$qid || !$text) jsonErr('questionnaire_id and question_text required');

    $validTypes = ['text','number','dropdown','yes_no','date','remarks','mobile'];
    if (!in_array($type, $validTypes)) jsonErr('Invalid question type');

    // Verify questionnaire belongs to partner
    $pdo = getDB();
    $own = $pdo->prepare("SELECT id FROM questionnaires WHERE id=? AND partner_id=?");
    $own->execute([$qid, $pid]);
    if (!$own->fetch()) jsonErr('Questionnaire not found', 404);

    // Get next sort order
    $sortStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM questionnaire_questions WHERE questionnaire_id=?");
    $sortStmt->execute([$qid]);
    $sortOrder = (int)$sortStmt->fetchColumn();

    // Validate and encode options
    $options = null;
    if ($type === 'dropdown' && !empty($b['options'])) {
        $opts = array_filter(array_map('trim', (array)$b['options']));
        if (count($opts) < 2) jsonErr('Dropdown needs at least 2 options');
        $options = json_encode(array_values($opts));
    }

    $pdo->prepare("
        INSERT INTO questionnaire_questions
            (questionnaire_id, question_text, question_type, options_json,
             placeholder, is_required, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $qid, $text, $type, $options,
        sanitize($b['placeholder'] ?? ''),
        (int)($b['is_required'] ?? 0),
        $sortOrder
    ]);

    jsonOk(['question_id' => $pdo->lastInsertId(), 'message' => 'Question added']);
}

function updateQuestion(array $b, int $pid): void {
    $qqId = intSafe($b['question_id'] ?? 0);
    if (!$qqId) jsonErr('question_id required');

    $pdo = getDB();
    // Verify ownership via questionnaire → partner
    $own = $pdo->prepare("
        SELECT qq.id FROM questionnaire_questions qq
        JOIN questionnaires q ON q.id = qq.questionnaire_id
        WHERE qq.id=? AND q.partner_id=?
    ");
    $own->execute([$qqId, $pid]);
    if (!$own->fetch()) jsonErr('Question not found', 404);

    $fields = []; $vals = [];
    if (!empty($b['question_text']))  { $fields[] = 'question_text = ?';  $vals[] = sanitize($b['question_text'], 500); }
    if (isset($b['is_required']))     { $fields[] = 'is_required = ?';    $vals[] = (int)$b['is_required']; }
    if (!empty($b['placeholder']))    { $fields[] = 'placeholder = ?';    $vals[] = sanitize($b['placeholder']); }
    if (!empty($b['options']) && is_array($b['options'])) {
        $opts = array_filter(array_map('trim', $b['options']));
        $fields[] = 'options_json = ?';
        $vals[]   = json_encode(array_values($opts));
    }
    if (empty($fields)) jsonErr('Nothing to update');

    $vals[] = $qqId;
    $pdo->prepare("UPDATE questionnaire_questions SET ".implode(',',$fields)." WHERE id=?")->execute($vals);
    jsonOk(['message' => 'Question updated']);
}

function deleteQuestion(array $b, int $pid): void {
    $qqId = intSafe($b['question_id'] ?? 0);
    $pdo  = getDB();
    $pdo->prepare("
        DELETE qq FROM questionnaire_questions qq
        JOIN questionnaires q ON q.id = qq.questionnaire_id
        WHERE qq.id=? AND q.partner_id=?
    ")->execute([$qqId, $pid]);
    jsonOk(['message' => 'Question deleted']);
}

function reorderQuestions(array $b, int $pid): void {
    $order = $b['order'] ?? []; // [{id:1, sort:1}, {id:2, sort:2}]
    $pdo   = getDB();
    $stmt  = $pdo->prepare("
        UPDATE questionnaire_questions qq
        JOIN questionnaires q ON q.id = qq.questionnaire_id
        SET qq.sort_order = ?
        WHERE qq.id = ? AND q.partner_id = ?
    ");
    foreach ($order as $item) {
        $stmt->execute([(int)$item['sort'], (int)$item['id'], $pid]);
    }
    jsonOk(['message' => 'Order saved']);
}


// ════════════════════════════════════════════════════════
// ANSWERS
// ════════════════════════════════════════════════════════

function getForLead(array $b, int $pid): void {
    $leadId = intSafe($b['lead_id'] ?? $_GET['lead_id'] ?? 0);
    if (!$leadId) jsonErr('lead_id required');

    $pdo = getDB();

    // Get lead's loan type for matching questionnaire
    $lead = $pdo->prepare("SELECT loan_type FROM calling_leads WHERE id=? AND partner_id=?");
    $lead->execute([$leadId, $pid]);
    $leadRow = $lead->fetch(PDO::FETCH_ASSOC);
    if (!$leadRow) jsonErr('Lead not found', 404);

    // Get active questionnaire (loan_type specific first, then general)
    $q = $pdo->prepare("
        SELECT q.*, 
               GROUP_CONCAT(
                   JSON_OBJECT(
                       'id',          qq.id,
                       'text',        qq.question_text,
                       'type',        qq.question_type,
                       'options',     qq.options_json,
                       'placeholder', qq.placeholder,
                       'required',    qq.is_required
                   )
                   ORDER BY qq.sort_order
               ) AS questions_raw
        FROM questionnaires q
        JOIN questionnaire_questions qq ON qq.questionnaire_id = q.id
        WHERE q.partner_id = ? AND q.status = 'active'
          AND (q.loan_type = ? OR q.loan_type IS NULL OR q.loan_type = '')
        GROUP BY q.id
        ORDER BY CASE WHEN q.loan_type = ? THEN 0 ELSE 1 END
        LIMIT 1
    ");
    $q->execute([$pid, $leadRow['loan_type'], $leadRow['loan_type']]);
    $questionnaire = $q->fetch(PDO::FETCH_ASSOC);

    if (!$questionnaire) jsonOk(['questionnaire' => null, 'answers' => []]);

    // Parse questions
    $questions = [];
    if ($questionnaire['questions_raw']) {
        $rawQ = '['.$questionnaire['questions_raw'].']';
        $parsed = json_decode($rawQ, true) ?? [];
        foreach ($parsed as &$pq) {
            if ($pq['options']) $pq['options'] = json_decode($pq['options'], true) ?? [];
        }
        $questions = $parsed;
    }
    $questionnaire['questions'] = $questions;
    unset($questionnaire['questions_raw']);

    // Get existing answers for this lead
    $ans = $pdo->prepare("
        SELECT qa.question_id, qa.answer, qa.answered_at
        FROM questionnaire_answers qa
        WHERE qa.lead_id = ? AND qa.questionnaire_id = ?
    ");
    $ans->execute([$leadId, $questionnaire['id']]);
    $answers = [];
    foreach ($ans->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $answers[$row['question_id']] = $row['answer'];
    }

    jsonOk(['questionnaire' => $questionnaire, 'answers' => $answers]);
}

function saveAnswers(array $b, int $pid, int $actorId, bool $isTC): void {
    $leadId  = intSafe($b['lead_id'] ?? 0);
    $qid     = intSafe($b['questionnaire_id'] ?? 0);
    $answers = $b['answers'] ?? [];

    if (!$leadId) jsonErr('lead_id required');
    if (!$qid)    jsonErr('questionnaire_id required');
    if (empty($answers)) jsonErr('No answers provided');

    $pdo = getDB();

    // Verify lead belongs to partner
    $chk = $pdo->prepare("SELECT id FROM calling_leads WHERE id=? AND partner_id=?");
    $chk->execute([$leadId, $pid]);
    if (!$chk->fetch()) jsonErr('Lead not found', 404);

    $stmt = $pdo->prepare("
        INSERT INTO questionnaire_answers
            (lead_id, questionnaire_id, question_id, answer, answered_by, answered_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            answer      = VALUES(answer),
            answered_by = VALUES(answered_by),
            answered_at = NOW()
    ");

    $saved = 0;
    foreach ($answers as $questionId => $answer) {
        $stmt->execute([
            $leadId, $qid, (int)$questionId,
            sanitize((string)$answer, 1000),
            $actorId
        ]);
        $saved++;
    }

    // Update lead with key answers (loan amount, income, CIBIL if provided)
    updateLeadFromAnswers($leadId, $pid, $answers, $qid, $pdo);

    jsonOk(['saved' => $saved, 'message' => 'Answers saved']);
}

function updateLeadFromAnswers(int $leadId, int $pid, array $answers, int $qid, PDO $pdo): void {
    // Get question texts to identify key fields
    $qs = $pdo->prepare("
        SELECT id, question_text, question_type FROM questionnaire_questions
        WHERE questionnaire_id = ?
    ");
    $qs->execute([$qid]);
    $questions = $qs->fetchAll(PDO::FETCH_ASSOC);

    $updates = []; $vals = [];

    foreach ($questions as $q) {
        $answer = $answers[$q['id']] ?? null;
        if (!$answer) continue;
        $text = strtolower($q['question_text']);

        if (str_contains($text, 'amount') || str_contains($text, 'loan kitna')) {
            $amt = (int)preg_replace('/\D/', '', $answer);
            if ($amt > 0) { $updates[] = 'loan_amount = ?'; $vals[] = $amt; }
        }
        if (str_contains($text, 'income') || str_contains($text, 'salary')) {
            $inc = (int)preg_replace('/\D/', '', $answer);
            if ($inc > 0) { $updates[] = 'income = ?'; $vals[] = $inc; }
        }
        if (str_contains($text, 'cibil')) {
            preg_match('/\d{3}/', $answer, $m);
            if ($m) { $updates[] = 'cibil = ?'; $vals[] = (int)$m[0]; }
        }
        if (str_contains($text, 'emi') && str_contains($text, 'amount')) {
            $emi = (int)preg_replace('/\D/', '', $answer);
            if ($emi > 0) { $updates[] = 'existing_emi = ?'; $vals[] = $emi; }
        }
    }

    if (!empty($updates)) {
        $vals[] = $leadId; $vals[] = $pid;
        $pdo->prepare("UPDATE calling_leads SET ".implode(',',$updates)." WHERE id=? AND partner_id=?")->execute($vals);
    }
}

function getAnswers(array $b, int $pid): void {
    $leadId = intSafe($b['lead_id'] ?? $_GET['lead_id'] ?? 0);
    if (!$leadId) jsonErr('lead_id required');

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT
            qa.question_id, qa.answer, qa.answered_at,
            qq.question_text, qq.question_type,
            q.title AS questionnaire_title
        FROM questionnaire_answers qa
        JOIN questionnaire_questions qq ON qq.id = qa.question_id
        JOIN questionnaires q ON q.id = qa.questionnaire_id
        WHERE qa.lead_id = ? AND q.partner_id = ?
        ORDER BY qq.sort_order
    ");
    $stmt->execute([$leadId, $pid]);
    jsonOk(['answers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function exportAnswers(array $b, int $pid): void {
    $cid = intSafe($b['campaign_id'] ?? 0);
    $pdo = getDB();

    // Get all leads in campaign with their answers
    $where  = ['cl.partner_id = ?'];
    $params = [$pid];
    if ($cid) { $where[] = 'cl.batch_id = ?'; $params[] = $cid; }

    $stmt = $pdo->prepare("
        SELECT
            cl.customer_name,
            cl.mobile,
            cl.city,
            cl.loan_type,
            cl.current_status,
            t.name AS telecaller,
            qq.question_text,
            qa.answer,
            qa.answered_at
        FROM calling_leads cl
        LEFT JOIN questionnaire_answers qa ON qa.lead_id = cl.id
        LEFT JOIN questionnaire_questions qq ON qq.id = qa.question_id
        LEFT JOIN telecallers t ON t.id = cl.assigned_telecaller_id
        WHERE " . implode(' AND ', $where) . "
          AND qa.id IS NOT NULL
        ORDER BY cl.id, qq.sort_order
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pivot: lead → questions as columns
    $leads = [];
    $allQuestions = [];
    foreach ($rows as $row) {
        $key = $row['mobile'];
        if (!isset($leads[$key])) {
            $leads[$key] = [
                'Customer Name' => $row['customer_name'],
                'Mobile'        => $row['mobile'],
                'City'          => $row['city'],
                'Loan Type'     => $row['loan_type'],
                'Status'        => $row['current_status'],
                'Telecaller'    => $row['telecaller'],
            ];
        }
        $q = $row['question_text'];
        $allQuestions[$q] = true;
        $leads[$key][$q] = $row['answer'];
    }

    jsonOk(['data' => array_values($leads), 'count' => count($leads)]);
}


// ════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════
function sanitize(string $val, int $max = 255): string {
    return mb_substr(htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8'), 0, $max);
}
function intSafe($v): int { return (int)preg_replace('/\D/', '', (string)$v); }
function jsonOk(array $d): void { echo json_encode(array_merge(['status'=>'ok'], $d), JSON_UNESCAPED_UNICODE); exit; }
function jsonErr(string $m, int $c = 400): void { http_response_code($c); echo json_encode(['status'=>'error','message'=>$m]); exit; }