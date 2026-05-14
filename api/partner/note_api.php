<?php
/**
 * WealthMetre – note_api.php
 * Lead notes management for channel partners.
 * POST/GET /api/partner/note_api.php
 * action = add | get | delete
 */
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/partner_auth.php';

$body    = getJsonBody();
$action  = sanitize($body['action'] ?? $_GET['action'] ?? '');
$partner = requireAuth();

match ($action) {
    'add'    => addNote($body, $partner),
    'get'    => getNotes($partner),
    'delete' => deleteNote($body, $partner),
    default  => jsonError('Invalid action')
};

// ── Add Note ──────────────────────────────────────────────
function addNote(array $b, array $partner): void {
    $leadId  = intSafe($b['lead_id'] ?? 0);
    $content = sanitize($b['content'] ?? '');

    if (!$leadId)  jsonError('lead_id required');
    if (!$content) jsonError('content required');

    $pdo = getDB();

    // Verify lead belongs to this partner
    $chk = $pdo->prepare("SELECT lead_id FROM customer_leads WHERE lead_id = ? AND partner_id = ?");
    $chk->execute([$leadId, $partner['id']]);
    if (!$chk->fetch()) jsonError('Lead not found', 404);

    $stmt = $pdo->prepare("
        INSERT INTO notes (lead_id, partner_id, content)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$leadId, $partner['id'], $content]);
    $noteId = (int)$pdo->lastInsertId();

    logAct($leadId, (int)$partner['id'], 'note_added', 'Note added');
    jsonResponse(['status' => 'ok', 'id' => $noteId, 'message' => 'Note saved']);
}

// ── Get Notes for a Lead ──────────────────────────────────
function getNotes(array $partner): void {
    $leadId = intSafe($_GET['lead_id'] ?? 0);
    if (!$leadId) jsonError('lead_id required');

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT * FROM notes
        WHERE lead_id = ? AND partner_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$leadId, $partner['id']]);
    jsonResponse(['status' => 'ok', 'notes' => $stmt->fetchAll()]);
}

// ── Delete Note ───────────────────────────────────────────
function deleteNote(array $b, array $partner): void {
    $id = intSafe($b['id'] ?? 0);
    if (!$id) jsonError('id required');

    $pdo  = getDB();
    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND partner_id = ?");
    $stmt->execute([$id, $partner['id']]);

    if (!$stmt->rowCount()) jsonError('Note not found', 404);
    jsonResponse(['status' => 'ok', 'message' => 'Note deleted']);
}