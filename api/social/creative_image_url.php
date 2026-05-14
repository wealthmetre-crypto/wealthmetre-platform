<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

function wm_json($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_SLASHES);
    exit;
}

function wm_get_mysqli() {
    global $conn, $mysqli, $db;

    if (isset($conn) && $conn instanceof mysqli) return $conn;
    if (isset($mysqli) && $mysqli instanceof mysqli) return $mysqli;
    if (isset($db) && $db instanceof mysqli) return $db;

    if (function_exists('getDBConnection')) {
        $x = getDBConnection();
        if ($x instanceof mysqli) return $x;
        if ($x instanceof PDO) return $x;
    }

    if (function_exists('getDbConnection')) {
        $x = getDbConnection();
        if ($x instanceof mysqli) return $x;
        if ($x instanceof PDO) return $x;
    }

    if (function_exists('getConnection')) {
        $x = getConnection();
        if ($x instanceof mysqli) return $x;
        if ($x instanceof PDO) return $x;
    }

    $host = defined('DB_HOST') ? DB_HOST : null;
    $user = defined('DB_USER') ? DB_USER : (defined('DB_USERNAME') ? DB_USERNAME : null);
    $pass = defined('DB_PASS') ? DB_PASS : (defined('DB_PASSWORD') ? DB_PASSWORD : null);
    $name = defined('DB_NAME') ? DB_NAME : (defined('DB_DATABASE') ? DB_DATABASE : null);
    $port = defined('DB_PORT') ? (int)DB_PORT : 3306;

    if ($host && $user && $name) {
        $m = @new mysqli($host, $user, $pass ?? '', $name, $port);
        if ($m->connect_error) {
            wm_json(['ok'=>false,'error'=>'DB connect failed: '.$m->connect_error], 500);
        }
        $m->set_charset('utf8mb4');
        return $m;
    }

    wm_json(['ok'=>false,'error'=>'No supported DB connection found in config/db.php'], 500);
}

function wm_fetch_one($sql, $params = []) {
    $db = wm_get_mysqli();

    if ($db instanceof PDO) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) wm_json(['ok'=>false,'error'=>'DB prepare failed: '.$db->error], 500);

    if ($params) {
        $types = '';
        foreach ($params as $p) $types .= is_int($p) ? 'i' : 's';
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) wm_json(['ok'=>false,'error'=>'DB execute failed: '.$stmt->error], 500);

    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

function wm_execute($sql, $params = []) {
    $db = wm_get_mysqli();

    if ($db instanceof PDO) {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) wm_json(['ok'=>false,'error'=>'DB prepare failed: '.$db->error], 500);

    if ($params) {
        $types = '';
        foreach ($params as $p) $types .= is_int($p) ? 'i' : 's';
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) wm_json(['ok'=>false,'error'=>'DB execute failed: '.$stmt->error], 500);

    return true;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) wm_json(['ok'=>false,'error'=>'Missing valid id'], 400);

    $row = wm_fetch_one("SELECT id, creative_image_url FROM content_queue WHERE id = ? LIMIT 1", [$id]);
    if (!$row) wm_json(['ok'=>false,'error'=>'Content not found'], 404);

    wm_json([
        'ok' => true,
        'id' => (int)$row['id'],
        'creative_image_url' => $row['creative_image_url'] ?? ''
    ]);
}

if ($method === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $url = trim($_POST['creative_image_url'] ?? '');

    if ($id <= 0) wm_json(['ok'=>false,'error'=>'Missing valid id'], 400);

    if ($url !== '' && stripos($url, 'https://') !== 0) {
        wm_json(['ok'=>false,'error'=>'Image URL must start with https://'], 400);
    }

    if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        wm_json(['ok'=>false,'error'=>'Invalid image URL'], 400);
    }

    wm_execute("UPDATE content_queue SET creative_image_url = ? WHERE id = ?", [$url, $id]);

    wm_json([
        'ok' => true,
        'id' => $id,
        'creative_image_url' => $url,
        'message' => 'Creative image URL saved'
    ]);
}

wm_json(['ok'=>false,'error'=>'Method not allowed'], 405);
