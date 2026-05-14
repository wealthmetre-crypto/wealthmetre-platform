<?php
require_once __DIR__ . '/config.php';
$partner = requireAuth();
$pid     = (int)$partner['id'];
$body    = getBody();
$act     = $_GET['action'] ?? ($body['action'] ?? '');
$db      = getDB();

if ($act === 'list' || $act === 'get' || $act === 'get_by_lead') {
    $lid  = (int)($_GET['lead_id'] ?? $body['lead_id'] ?? 0);
    if ($lid) {
        $s = $db->prepare("SELECT * FROM lead_followups WHERE lead_id=? ORDER BY due_date ASC");
        $s->bind_param('i', $lid);
    } else {
        $s = $db->prepare("SELECT lf.*, l.customer_name, l.loan_type FROM lead_followups lf LEFT JOIN leads l ON l.id=lf.lead_id WHERE lf.partner_id=? ORDER BY lf.due_date ASC LIMIT 100");
        $s->bind_param('i', $pid);
    }
    $s->execute();
    jsonOk(['followups' => $s->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

if ($act === 'add') {
    $lid  = (int)($body['lead_id'] ?? 0);
    $type = $body['type']     ?? 'call';
    $due  = $body['due_date'] ?? date('Y-m-d');
    $note = $body['notes']    ?? '';
    $s = $db->prepare("INSERT INTO lead_followups (lead_id,partner_id,type,due_date,notes,status) VALUES (?,?,?,?,?,'pending')");
    $s->bind_param('iisss', $lid,$pid,$type,$due,$note);
    $s->execute();
    jsonOk(['id' => $db->insert_id, 'message' => 'Follow-up added']);
}

if ($act === 'update') {
    $id   = (int)($body['id']     ?? 0);
    $st   = $body['status']       ?? 'pending';
    $note = $body['notes']        ?? '';
    $s = $db->prepare("UPDATE lead_followups SET status=?,notes=? WHERE id=? AND partner_id=?");
    $s->bind_param('ssii', $st,$note,$id,$pid);
    $s->execute();
    jsonOk(['message' => 'Updated']);
}

if ($act === 'delete') {
    $id = (int)($body['id'] ?? 0);
    $s  = $db->prepare("DELETE FROM lead_followups WHERE id=? AND partner_id=?");
    $s->bind_param('ii', $id, $pid);
    $s->execute();
    jsonOk(['message' => 'Deleted']);
}

jsonError('Invalid action');
