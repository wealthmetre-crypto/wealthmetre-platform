<?php
/**
 * WealthMetre – activity_api.php
 * Read activity log for a lead.
 * GET /api/partner/activity_api.php?action=get&lead_id=X
 */
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/partner_auth.php';

$action  = sanitize($_GET['action'] ?? getJsonBody()['action'] ?? '');
$partner = requireAuth();

match ($action) {
    'get'  => getActivity($partner),
    default => jsonError('Invalid action')
};

// ── Get Activity Log for a Lead ───────────────────────────
function getActivity(array $partner): void {
    $leadId = intSafe($_GET['lead_id'] ?? 0);
    if (!$leadId) jsonError('lead_id required');

    $pdo = getDB();

    // Verify lead ownership
    $chk = $pdo->prepare("SELECT lead_id FROM customer_leads WHERE lead_id = ? AND partner_id = ?");
    $chk->execute([$leadId, $partner['id']]);
    if (!$chk->fetch()) jsonError('Lead not found', 404);

    // Try activity_logs first (main table), fall back to activity_log
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM activity_logs
            WHERE lead_id = ? AND partner_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$leadId, $partner['id']]);
        $activities = $stmt->fetchAll();
    } catch (\Throwable $e) {
        // Fallback to activity_log (singular)
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM activity_log
                WHERE lead_id = ?
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$leadId]);
            $activities = $stmt->fetchAll();
        } catch (\Throwable $e2) {
            $activities = [];
        }
    }

    // Decode JSON details field if present
    foreach ($activities as &$a) {
        if (!empty($a['details']) && is_string($a['details'])) {
            $a['details'] = json_decode($a['details'], true) ?? $a['details'];
        }
    }

    jsonResponse(['status' => 'ok', 'activities' => $activities]);
}