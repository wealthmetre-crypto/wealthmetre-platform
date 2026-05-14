<?php
require_once __DIR__ . '/config.php';
$partner = requireAuth();
$pid     = (int)$partner['id'];
$body    = getBody();
$act     = $_GET['action'] ?? ($body['action'] ?? '');
$db      = getDB();

if ($act === 'list') {
    $s = $db->prepare("SELECT id, name, message, created_at FROM wa_templates WHERE partner_id=? ORDER BY created_at DESC");
    $s->bind_param('i', $pid); $s->execute();
    jsonOk(['templates' => $s->get_result()->fetch_all(MYSQLI_ASSOC)]);
}
if ($act === 'save') {
    $name = trim($body['name'] ?? ''); $msg = trim($body['message'] ?? '');
    if (!$name) jsonError('Name required');
    $s = $db->prepare("INSERT INTO wa_templates (partner_id, name, message) VALUES (?,?,?)");
    $s->bind_param('iss', $pid, $name, $msg); $s->execute();
    jsonOk(['message' => 'Template saved', 'id' => $db->insert_id]);
}
if ($act === 'delete') {
    $id = (int)($body['id'] ?? 0);
    $s = $db->prepare("DELETE FROM wa_templates WHERE id=? AND partner_id=?");
    $s->bind_param('ii', $id, $pid); $s->execute();
    jsonOk(['message' => 'Deleted']);
}
jsonOk(['templates' => [], 'message' => 'ok']);
