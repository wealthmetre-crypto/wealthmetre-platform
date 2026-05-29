<?php
require_once __DIR__ . '/config.php';
$partner = requireAuth();
$pid     = (int)$partner['id'];
$body    = getBody();
$act     = $_GET['action'] ?? ($body['action'] ?? '');

// list_telecallers
if ($act === 'list_telecallers' || $act === 'list') {
    $db = getDB();
    $s  = $db->prepare("SELECT t.id, t.name, t.mobile, t.email, t.status,
        t.target_calls, t.total_calls, t.total_interested, t.created_at,
        COUNT(DISTINCT cl.id) AS total_assigned,
        SUM(CASE WHEN cl.current_status='interested' THEN 1 ELSE 0 END) AS interested,
        COUNT(DISTINCT cal.id) AS total_called
        FROM telecallers t
        LEFT JOIN calling_leads cl ON cl.assigned_telecaller_id = t.id
        LEFT JOIN call_logs cal ON cal.telecaller_id = t.id
        WHERE t.partner_id = ?
        GROUP BY t.id ORDER BY t.name ASC");
    $s->bind_param('i', $pid);
    $s->execute();
    $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    jsonOk(['telecallers' => $rows]);
}

// add_telecaller
if ($act === 'add_telecaller' || $act === 'add' || $act === 'create_telecaller') {
    $name   = trim($body['name']     ?? '');
    $mob    = trim($body['mobile']   ?? '');
    $pass   = trim($body['password'] ?? '');
    $email  = trim($body['email']    ?? '');
    $target = (int)($body['daily_target'] ?? 50);
    if (!$name || !$mob || !$pass) jsonError('Name, mobile and password required');
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $db   = getDB();
    $chk  = $db->prepare("SELECT id FROM telecallers WHERE mobile=? AND partner_id=?");
    $chk->bind_param('si', $mob, $pid);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()) jsonError('Mobile already registered');
    $s = $db->prepare("INSERT INTO telecallers (partner_id,name,mobile,email,password_hash,status,target_calls) VALUES (?,?,?,?,?,'active',?)");
    $s->bind_param('issssi', $pid,$name,$mob,$email,$hash,$target);
    $s->execute();
    jsonOk(['telecaller_id' => $db->insert_id, 'message' => 'Telecaller added']);
}

// deactivate_telecaller
if ($act === 'deactivate_telecaller' || $act === 'deactivate' || $act === 'delete') {
    $tid = (int)($body['id'] ?? $_GET['id'] ?? 0);
    if (!$tid) jsonError('Telecaller ID required');
    $db = getDB();
    $s  = $db->prepare("UPDATE telecallers SET status='inactive' WHERE id=? AND partner_id=?");
    $s->bind_param('ii', $tid, $pid);
    $s->execute();
    jsonOk(['message' => 'Telecaller deactivated']);
}

// activate_telecaller
if ($act === 'activate_telecaller' || $act === 'activate') {
    $tid = (int)($body['id'] ?? $_GET['id'] ?? 0);
    $db  = getDB();
    $s   = $db->prepare("UPDATE telecallers SET status='active' WHERE id=? AND partner_id=?");
    $s->bind_param('ii', $tid, $pid);
    $s->execute();
    jsonOk(['message' => 'Telecaller activated']);
}

// reset_telecaller_password
if (in_array($act, ['reset_telecaller_password','reset_password','reset_tc_password'])) {
    $tid  = (int)($body['id'] ?? $body['telecaller_id'] ?? 0);
    $pass = trim($body['password'] ?? $body['new_password'] ?? '');
    if (!$tid || !$pass) jsonError('ID and password required');
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $db   = getDB();
    $s    = $db->prepare("UPDATE telecallers SET password_hash=? WHERE id=? AND partner_id=?");
    $s->bind_param('sii', $hash, $tid, $pid);
    $s->execute();
    jsonOk(['message' => 'Password reset successfully']);
}

// update_telecaller
if ($act === 'update_telecaller' || $act === 'update') {
    $tid    = (int)($body['id']           ?? 0);
    $name   = trim($body['name']          ?? '');
    $status = trim($body['status']        ?? 'active');
    $target = (int)($body['daily_target'] ?? 50);
    $db     = getDB();
    $s      = $db->prepare("UPDATE telecallers SET name=?,status=?,target_calls=? WHERE id=? AND partner_id=?");
    $s->execute([$name,$status,$target,$tid,$pid]);
    jsonOk(['message' => 'Updated']);
}

jsonError('Invalid action: ' . $act);
