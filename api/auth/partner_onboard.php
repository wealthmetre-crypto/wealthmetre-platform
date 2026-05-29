<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);

$body   = getJsonBody();
$token  = trim($body['onboard_token'] ?? '');
$mobile = trim($body['mobile'] ?? '');
$first  = trim($body['first_name'] ?? '');
$last   = trim($body['last_name'] ?? '');
$firm   = trim($body['firm_name'] ?? '');
$dob    = trim($body['dob'] ?? '');
$pin    = trim($body['pin_code'] ?? '');
$city   = trim($body['city'] ?? '');
$state  = trim($body['state'] ?? '');

// Validate required fields
if (!$token || !$mobile)      jsonError('Invalid request', 422);
if (!$first || !$last)        jsonError('First and last name are required', 422);
if (!$dob)                    jsonError('Date of birth is required', 422);
if (!$pin || strlen($pin)!=6) jsonError('Valid 6-digit PIN code is required', 422);
if (!$city)                   jsonError('City is required', 422);

$db = getDB();

// Verify onboard token
$stmt = $db->prepare("SELECT id, mobile FROM partner_onboard_tokens WHERE token=? AND used=0 AND expires_at > NOW() LIMIT 1");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row)                    jsonError('Session expired. Please login again.', 401);
if ($row['mobile'] !== $mobile) jsonError('Mobile mismatch. Please login again.', 401);

// Check if partner already exists (race condition guard)
$chk = $db->prepare("SELECT id FROM partners WHERE mobile=? OR phone=? LIMIT 1");
$chk->execute([$mobile, $mobile]);
if ($chk->fetch()) jsonError('Account already exists. Please login normally.', 409);

// Create partner record
$partnerName = trim($first . ' ' . $last) . ($firm ? ' — ' . $firm : '');
$address     = $city . ($state ? ', ' . $state : '') . ' — ' . $pin;

$ins = $db->prepare("
    INSERT INTO partners
        (partner_name, first_name, last_name, firm_name, mobile, dob, pin_code, branch_city, state, address, status, created_at, updated_at)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
");
$ins->execute([$partnerName, $first, $last, $firm ?: null, $mobile, $dob ?: null, $pin, $city, $state ?: null, $address]);
$partnerId = (int)$db->lastInsertId();

// Mark onboard token as used
$db->prepare("UPDATE partner_onboard_tokens SET used=1 WHERE token=?")->execute([$token]);

// Create session
$sessionToken = bin2hex(random_bytes(32));
$sessionId    = bin2hex(random_bytes(16));
$userAgent    = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);
$ipAddress    = $_SERVER['REMOTE_ADDR'] ?? '';

$db->prepare("
    INSERT INTO partner_sessions (partner_id, token, expires_at, created_at, session_id, user_agent, ip_address, is_active)
    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW(), ?, ?, ?, 1)
")->execute([$partnerId, $sessionToken, $sessionId, $userAgent, $ipAddress]);

jsonResponse([
    'success'  => true,
    'message'  => 'Welcome to WealthMetre! Your account has been created.',
    'token'    => $sessionToken,
    'session_token' => $sessionToken,
    'is_new_partner' => true,
    'partner'  => [
        'id'           => $partnerId,
        'partner_name' => $partnerName,
        'first_name'   => $first,
        'last_name'    => $last,
        'mobile'       => $mobile,
    ],
]);
