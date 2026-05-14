<?php
require_once __DIR__ . '/config.php';
$partner = requireAuth();
$pid     = (int)$partner['id'];
$body    = getBody();
$act     = $_GET['action'] ?? ($body['action'] ?? '');
$db      = getDB();

if ($act === 'list') {
    $s = $db->prepare("SELECT id, title, description, questions_json, created_at FROM questionnaires WHERE partner_id=? ORDER BY created_at DESC");
    $s->bind_param('i', $pid); $s->execute();
    $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) { $r['questions'] = json_decode($r['questions_json'] ?? '[]', true) ?? []; unset($r['questions_json']); }
    jsonOk(['questionnaires' => $rows]);
}
if ($act === 'save') {
    $title  = trim($body['title'] ?? '');
    $desc   = trim($body['description'] ?? '');
    $qs     = $body['questions'] ?? [];
    if (!$title) jsonError('Title required');
    $qJson  = json_encode(array_values(array_filter(array_map('trim', (array)$qs))));
    $s = $db->prepare("INSERT INTO questionnaires (partner_id, title, description, questions_json, status) VALUES (?,?,?,?,'active') ON DUPLICATE KEY UPDATE title=VALUES(title)");
    $s->bind_param('isss', $pid, $title, $desc, $qJson); $s->execute();
    jsonOk(['message' => 'Questionnaire saved', 'id' => $db->insert_id]);
}
if ($act === 'delete') {
    $id = (int)($body['id'] ?? 0);
    $s = $db->prepare("DELETE FROM questionnaires WHERE id=? AND partner_id=?");
    $s->bind_param('ii', $id, $pid); $s->execute();
    jsonOk(['message' => 'Deleted']);
}
jsonOk(['questionnaires' => [], 'message' => 'ok']);
