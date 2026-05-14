<?php
require_once __DIR__ . '/config.php';
$partner = requireAuth();
$pid     = (int)$partner['id'];
$body    = getBody();
$act     = $_GET['action'] ?? ($body['action'] ?? '');
$db      = getDB();

if ($act === 'list') {
    $s = $db->prepare("SELECT id, name, email, summary, created_at FROM email_reports WHERE partner_id=? ORDER BY created_at DESC");
    $s->bind_param('i', $pid); $s->execute();
    jsonOk(['reports' => $s->get_result()->fetch_all(MYSQLI_ASSOC)]);
}
if ($act === 'save') {
    $name = trim($body['name'] ?? ''); $email = trim($body['email'] ?? ''); $summary = trim($body['summary'] ?? '');
    if (!$name) jsonError('Name required');
    $s = $db->prepare("INSERT INTO email_reports (partner_id, name, email, summary) VALUES (?,?,?,?)");
    $s->bind_param('isss', $pid, $name, $email, $summary); $s->execute();
    jsonOk(['message' => 'Email report saved', 'id' => $db->insert_id]);
}
if ($act === 'delete') {
    $id = (int)($body['id'] ?? 0);
    $s = $db->prepare("DELETE FROM email_reports WHERE id=? AND partner_id=?");
    $s->bind_param('ii', $id, $pid); $s->execute();
    jsonOk(['message' => 'Deleted']);
}
jsonOk(['reports' => [], 'message' => 'ok']);
