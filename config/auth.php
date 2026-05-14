<?php
if (!function_exists('authenticatePartner')) {
    function authenticatePartner(): ?array {
        $hdrs  = function_exists('getallheaders') ? getallheaders() : [];
        $auth  = $hdrs['Authorization'] ?? $hdrs['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) $token = trim($m[1]);
        if (!$token) $token = $_GET['token'] ?? '';
        if (!$token) return null;
        $pdo  = getDB();
        $stmt = $pdo->prepare("
            SELECT p.* FROM partner_sessions ps
            JOIN partners p ON ps.partner_id = p.id
            WHERE ps.token = ? AND ps.is_active = 1 AND p.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }
}
if (!function_exists('requirePartnerAuth')) {
    function requirePartnerAuth(): array {
        $p = authenticatePartner();
        if (!$p) { http_response_code(401); echo json_encode(['status'=>'error','message'=>'Unauthorised']); exit; }
        return $p;
    }
}
