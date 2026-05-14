<?php
declare(strict_types=1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

require_once __DIR__ . '/../../api/config.php';

$body = getJsonBody();
$action = $body['action'] ?? $_GET['action'] ?? '';

// ── Rate limiting ──────────────────────────────────────
function checkRateLimit(PDO $pdo, string $ip): bool {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE action='login_fail' AND ip_address=? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$ip]);
        return (int)$stmt->fetchColumn() < 5;
    } catch(\Throwable $e) { return true; }
}

// ── Session helpers ────────────────────────────────────
function startAdminSession(): void {
    if(session_status()===PHP_SESSION_NONE){
        session_name('wm_admin');
        session_set_cookie_params(['lifetime'=>86400,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }
}
function isLoggedIn(): bool {
    startAdminSession();
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_role']);
}
function requireAuth(): void {
    // Validate token header directly (self-contained, no session needed)
    $hToken = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if($hToken) {
        $decoded = base64_decode(strtr($hToken, '-_', '+/') . str_repeat('=', (4 - strlen($hToken) % 4) % 4));
        $parts = explode(':', $decoded, 3);
        if(count($parts)===3) {
            [$uid, $role, $hmac] = $parts;
            try {
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT * FROM owner_users WHERE id=? AND is_active=1");
                $stmt->execute([$uid]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);
                if($u) {
                    $expected = hash_hmac('sha256',$u['id'].$u['email'],'WM_CMD_2025');
                    if(hash_equals($expected,$hmac)) {
                        // Valid token — set session vars
                        startAdminSession();
                        $_SESSION['admin_id']   = $u['id'];
                        $_SESSION['admin_name'] = $u['name'];
                        $_SESSION['admin_role'] = $u['role'];
                        return;
                    }
                }
            } catch(\Throwable $e) {}
        }
    }
    startAdminSession();
    if(!isLoggedIn()){
        http_response_code(401);
        echo json_encode(['success'=>false,'message'=>'Unauthorized']);
        exit;
    }
}

// ── Actions (only when called directly) ────────────────
if(basename($_SERVER['SCRIPT_FILENAME'])==='auth.php' && $action==='login'){
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';

    if(!$email||!$password){
        echo json_encode(['success'=>false,'message'=>'Email and password required']);exit;
    }

    $pdo = getDB();

    if(!checkRateLimit($pdo,$ip)){
        echo json_encode(['success'=>false,'message'=>'Too many failed attempts. Try after 15 minutes.']);exit;
    }

    // Check owner_users table (create if not exists)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS owner_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            email VARCHAR(200) UNIQUE NOT NULL,
            mobile VARCHAR(15),
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('owner','admin','operations_manager') DEFAULT 'admin',
            is_active TINYINT(1) DEFAULT 1,
            last_login_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch(\Throwable $e){}

    // Seed default owner if table empty
    try {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM owner_users")->fetchColumn();
        if($count===0){
            $hash = password_hash('WMOwner@2025', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO owner_users (name,email,mobile,password_hash,role) VALUES (?,?,?,?,?)")
                ->execute(['WealthMetre Owner','owner@wealthmetre.com','9999999999',$hash,'owner']);
        }
    } catch(\Throwable $e){}

    $stmt = $pdo->prepare("SELECT * FROM owner_users WHERE email=? AND is_active=1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$user||!password_verify($password,$user['password_hash'])){
        try {
            $pdo->prepare("INSERT INTO activity_logs (action,entity_type,description,ip_address,created_at) VALUES ('login_fail','owner_user',?,?,NOW())")
                ->execute(["Failed login for {$email}",$ip]);
        } catch(\Throwable $e){}
        echo json_encode(['success'=>false,'message'=>'Invalid email or password']);exit;
    }

    startAdminSession();
    $_SESSION['admin_id']   = $user['id'];
    // Also store simple token for JS clients
    $token = rtrim(strtr(base64_encode($user['id'].':'.$user['role'].':'.hash_hmac('sha256',$user['id'].$user['email'],'WM_CMD_2025')), '+/', '-_'), '=');
    $_SESSION['admin_token'] = $token;
    $_SESSION['admin_name'] = $user['name'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['admin_email']= $user['email'];

    $pdo->prepare("UPDATE owner_users SET last_login_at=NOW() WHERE id=?")->execute([$user['id']]);

    try {
        $pdo->prepare("INSERT INTO activity_logs (action,entity_type,entity_id,description,ip_address,created_at) VALUES ('login','owner_user',?,?,?,NOW())")
            ->execute([$user['id'],"Login: {$user['name']} ({$user['role']})",$ip]);
    } catch(\Throwable $e){}

    echo json_encode(['success'=>true,'message'=>'Login successful','token'=>$token,'data'=>[
        'id'=>$user['id'],'name'=>$user['name'],'role'=>$user['role'],'email'=>$user['email']
    ]]);exit;
}

if(basename($_SERVER['SCRIPT_FILENAME'])==='auth.php' && $action==='check'){
    startAdminSession();
    // Accept token from header too
    $hToken = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if($hToken && isset($_SESSION['admin_token']) && hash_equals($_SESSION['admin_token'],$hToken)) {
        // valid
    }
    if(isLoggedIn()){
        echo json_encode(['success'=>true,'data'=>[
            'id'=>$_SESSION['admin_id'],
            'name'=>$_SESSION['admin_name'],
            'role'=>$_SESSION['admin_role'],
            'email'=>$_SESSION['admin_email']
        ]]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    }
    exit;
}

if(basename($_SERVER['SCRIPT_FILENAME'])==='auth.php' && $action==='logout'){
    startAdminSession();
    session_destroy();
    echo json_encode(['success'=>true,'message'=>'Logged out']);exit;
}

if(basename($_SERVER['SCRIPT_FILENAME'])==='auth.php') echo json_encode(['success'=>false,'message'=>'Invalid action']);
