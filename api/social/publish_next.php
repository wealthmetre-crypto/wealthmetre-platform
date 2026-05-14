<?php

if (!function_exists('wm_http_post_json')) {
    function wm_http_post_json(string $url, array $params): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode((string)$raw, true);
        return [
            'ok' => $err === '' && $code >= 200 && $code < 300 && is_array($json),
            'code' => $code,
            'raw' => $raw,
            'json' => is_array($json) ? $json : [],
            'error' => $err,
        ];
    }
}

if (!function_exists('wm_abs_url')) {
    function wm_abs_url(?string $url): string {
        $url = trim((string)$url);
        if ($url === '') return '';
        if (stripos($url, 'https://') === 0) return $url;
        if (strpos($url, '/') === 0) return 'https://wealthmetre.com' . $url;
        return $url;
    }
}


if (!function_exists('wm_creative_is_publishable')) {
    function wm_creative_is_publishable(array $row): bool {
        $status = strtolower(trim((string)($row['creative_status'] ?? '')));
        if (in_array($status, ['uploaded', 'approved_generated'], true)) return true;

        $img = trim((string)($row['creative_image_url'] ?? ''));
        $media = trim((string)($row['creative_media_url'] ?? ''));

        if ($img !== '' || $media !== '') return true;

        return false;
    }
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/social_config.php';

function out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_SLASHES);
    exit;
}

function db_conn() {
    global $conn, $mysqli, $db, $pdo;

    if (isset($pdo) && $pdo instanceof PDO) return $pdo;
    if (isset($conn) && $conn instanceof mysqli) return $conn;
    if (isset($mysqli) && $mysqli instanceof mysqli) return $mysqli;
    if (isset($db) && $db instanceof mysqli) return $db;

    if (function_exists('getDBConnection')) {
        $x = getDBConnection();
        if ($x instanceof PDO || $x instanceof mysqli) return $x;
    }

    if (function_exists('getDbConnection')) {
        $x = getDbConnection();
        if ($x instanceof PDO || $x instanceof mysqli) return $x;
    }

    if (function_exists('getConnection')) {
        $x = getConnection();
        if ($x instanceof PDO || $x instanceof mysqli) return $x;
    }

    $host = defined('DB_HOST') ? DB_HOST : null;
    $user = defined('DB_USER') ? DB_USER : (defined('DB_USERNAME') ? DB_USERNAME : null);
    $pass = defined('DB_PASS') ? DB_PASS : (defined('DB_PASSWORD') ? DB_PASSWORD : null);
    $name = defined('DB_NAME') ? DB_NAME : (defined('DB_DATABASE') ? DB_DATABASE : null);
    $port = defined('DB_PORT') ? (int)DB_PORT : 3306;

    if ($host && $user && $name) {
        $m = @new mysqli($host, $user, $pass ?? '', $name, $port);
        if ($m->connect_error) out(['ok'=>false,'error'=>'DB connect failed: '.$m->connect_error], 500);
        $m->set_charset('utf8mb4');
        return $m;
    }

    out(['ok'=>false,'error'=>'No supported DB connection found'], 500);
}

function fetch_one($sql, $params = []) {
    $db = db_conn();

    if ($db instanceof PDO) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) out(['ok'=>false,'error'=>'Prepare failed: '.$db->error], 500);

    if ($params) {
        $types = '';
        foreach ($params as $p) $types .= is_int($p) ? 'i' : 's';
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) out(['ok'=>false,'error'=>'Execute failed: '.$stmt->error], 500);
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

function exec_sql($sql, $params = []) {
    $db = db_conn();

    if ($db instanceof PDO) {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) out(['ok'=>false,'error'=>'Prepare failed: '.$db->error], 500);

    if ($params) {
        $types = '';
        foreach ($params as $p) $types .= is_int($p) ? 'i' : 's';
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) out(['ok'=>false,'error'=>'Execute failed: '.$stmt->error], 500);
    return true;
}

function call_api($url, $postData) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_TIMEOUT => 90,
    ]);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok'=>false, 'http_code'=>$http, 'error'=>$err ?: 'cURL failed'];
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ['ok'=>false, 'http_code'=>$http, 'error'=>'Invalid JSON response', 'raw'=>$raw];
    }

    $json['_http_code'] = $http;
    return $json;
}

$row = fetch_one("
    SELECT 
      id, content_type, title, topic, slug, target_keyword, 
      content_short, content_long, hashtags, cta, 
      creative_image_url, creative_media_url, creative_media_type, creative_status
    FROM content_queue
    WHERE status = 'approved'
      AND content_type IN ('facebook','instagram')
    ORDER BY id ASC
    LIMIT 1
");

if (!$row) {
    out([
        'ok' => true,
        'published' => false,
        'message' => 'No approved pending Facebook/Instagram content found.'
    ]);
}

$id = (int)$row['id'];
$type = $row['content_type'];
$title = trim($row['title'] ?? '');
$body = trim(($row['content_short'] ?? '') ?: ($row['content_long'] ?? ''));
$caption = '';
$topic = trim($row['topic'] ?? '');
$creativeImage = trim(($row['creative_media_url'] ?? '') ?: ($row['creative_image_url'] ?? ''));
$creativeMediaType = strtolower(trim($row['creative_media_type'] ?? ''));

if ($creativeImage !== '' && stripos($creativeImage, 'https://') !== 0) {
    if (strpos($creativeImage, '/') === 0) {
        $creativeImage = 'https://wealthmetre.com' . $creativeImage;
    }
}

if ($creativeMediaType === 'video') {
    exec_sql("
        UPDATE content_queue
        SET status='needs_review',
            facebook_publish_status=IF(content_type='facebook','needs_video_support',facebook_publish_status),
            instagram_publish_status=IF(content_type='instagram','needs_video_support',instagram_publish_status)
        WHERE id=?
    ", [$id]);

    out([
        'ok' => false,
        'published' => false,
        'content_id' => $id,
        'message' => 'Video upload detected. Video publishing support is not enabled yet.'
    ], 200);
}

if ($creativeImage !== '' && stripos($creativeImage, 'https://') !== 0) {
    exec_sql("
        UPDATE content_queue
        SET status='needs_review',
            instagram_publish_status=IF(content_type='instagram','failed',instagram_publish_status),
            facebook_publish_status=IF(content_type='facebook','failed',facebook_publish_status),
            instagram_publish_error=IF(content_type='instagram','creative image/media URL must start with https://',instagram_publish_error),
            facebook_publish_error=IF(content_type='facebook','creative image/media URL must start with https://',facebook_publish_error)
        WHERE id=?
    ", [$id]);

    out([
        'ok' => false,
        'published' => false,
        'content_id' => $id,
        'error' => 'creative image/media URL must start with https://'
    ], 400);
}

$imageUrl = $creativeImage ?: 'https://wealthmetre.com/images/logo.png';

$hashtags = trim($row['hashtags'] ?? '');
$cta = trim($row['cta'] ?? '');

$messageParts = [];
if ($title !== '') $messageParts[] = $title;
if ($body !== '') $messageParts[] = $body;
if ($cta !== '') $messageParts[] = $cta;
if ($hashtags !== '') $messageParts[] = $hashtags;

$message = trim(implode("\n\n", $messageParts));
if ($message === '') $message = $topic ?: 'WealthMetre update';


file_put_contents(
    "/tmp/wm_publish_next_debug.log",
    date("Y-m-d H:i:s") . " | id={$id} | type={$type} | imageUrl={$imageUrl} | mediaType={$creativeMediaType} | message=" . substr($message, 0, 120) . PHP_EOL,
    FILE_APPEND
);

if ($type === 'instagram') {
    $res = call_api('https://wealthmetre.com/api/social/instagram_post.php', [
        'caption' => $message,
        'message' => $message,
        'image_url' => $imageUrl,
        'creative_image_url' => $imageUrl
    ]);

    if (!empty($res['ok']) && !empty($res['media_id'])) {
        exec_sql("
            UPDATE content_queue
            SET status='published',
                instagram_media_id=?,
                instagram_image_url=?,
                instagram_published_at=NOW(),
                instagram_publish_status='published',
                instagram_publish_error=NULL
            WHERE id=?
        ", [$res['media_id'], $imageUrl, $id]);

        out([
            'ok' => true,
            'published' => true,
            'platform' => 'instagram',
            'content_id' => $id,
            'media_id' => $res['media_id'],
            'image_url' => $imageUrl
        ]);
    }

    $err = $res['error'] ?? $res['message'] ?? json_encode($res);
    exec_sql("
        UPDATE content_queue
        SET instagram_publish_status='failed',
            instagram_publish_error=?
        WHERE id=?
    ", [$err, $id]);

    out([
        'ok' => false,
        'published' => false,
        'platform' => 'instagram',
        'content_id' => $id,
        'error' => $err,
        'api_response' => $res
    ], 500);
}

if ($type === 'facebook') {
    if ($imageUrl !== '' && $imageUrl !== 'https://wealthmetre.com/images/logo.png') {
        if (!defined('FB_GRAPH_VERSION') || !defined('FB_PAGE_ID') || !defined('FB_PAGE_ACCESS_TOKEN')) {
            out([
                'ok' => false,
                'published' => false,
                'platform' => 'facebook',
                'content_id' => $id,
                'error' => 'Facebook config missing'
            ], 500);
        }

        $fbPhotoUrl = 'https://graph.facebook.com/' . FB_GRAPH_VERSION . '/' . FB_PAGE_ID . '/photos';

        $res = call_api($fbPhotoUrl, [
            'url' => $imageUrl,
            'caption' => $message,
            'published' => 'true',
            'access_token' => FB_PAGE_ACCESS_TOKEN
        ]);

        $postId = $res['post_id'] ?? $res['id'] ?? null;

        if (!empty($postId)) {
            $publishedUrl = 'https://www.facebook.com/' . $postId;

            exec_sql("
                UPDATE content_queue
                SET status='published',
                    facebook_post_id=?,
                    facebook_published_at=NOW(),
                    facebook_publish_status='published',
                    facebook_publish_error=NULL,
                    published_url=?,
                    published_at=NOW()
                WHERE id=?
            ", [$postId, $publishedUrl, $id]);

            out([
                'ok' => true,
                'published' => true,
                'platform' => 'facebook',
                'content_id' => $id,
                'post_id' => $postId,
                'published_url' => $publishedUrl,
                'image_url' => $imageUrl
            ]);
        }

        $err = $res['error']['message'] ?? $res['error'] ?? $res['message'] ?? json_encode($res);
        exec_sql("
            UPDATE content_queue
            SET facebook_publish_status='failed',
                facebook_publish_error=?
            WHERE id=?
        ", [$err, $id]);

        out([
            'ok' => false,
            'published' => false,
            'platform' => 'facebook',
            'content_id' => $id,
            'error' => $err,
            'api_response' => $res
        ], 500);
    }

    $res = call_api('https://wealthmetre.com/api/social/facebook_post.php', [
        'message' => $message
    ]);

    $postId = $res['post_id'] ?? $res['id'] ?? null;

    if (!empty($res['ok']) && $postId) {
        $publishedUrl = 'https://www.facebook.com/' . $postId;

        exec_sql("
            UPDATE content_queue
            SET status='published',
                facebook_post_id=?,
                facebook_published_at=NOW(),
                facebook_publish_status='published',
                facebook_publish_error=NULL,
                published_url=?,
                published_at=NOW()
            WHERE id=?
        ", [$postId, $publishedUrl, $id]);

        out([
            'ok' => true,
            'published' => true,
            'platform' => 'facebook',
            'content_id' => $id,
            'post_id' => $postId,
            'published_url' => $publishedUrl
        ]);
    }

    $err = $res['error'] ?? $res['message'] ?? json_encode($res);
    exec_sql("
        UPDATE content_queue
        SET facebook_publish_status='failed',
            facebook_publish_error=?
        WHERE id=?
    ", [$err, $id]);

    out([
        'ok' => false,
        'published' => false,
        'platform' => 'facebook',
        'content_id' => $id,
        'error' => $err,
        'api_response' => $res
    ], 500);
}

out(['ok'=>false,'error'=>'Unsupported content_type'], 400);


if (!function_exists('wm_publish_facebook_photo_post')) {
    function wm_publish_facebook_photo_post(mysqli $mysqli, array $row): array {
        require_once __DIR__ . '/../../config/social_config.php';

        $id = (int)$row['id'];
        $caption = trim((string)($row['caption'] ?? ''));
        if ($caption === '') $caption = trim((string)($row['meta_text'] ?? ''));
        if ($caption === '') $caption = trim((string)($row['content'] ?? ''));
        if ($caption === '') $caption = trim((string)($row['title'] ?? ''));

        $imageUrl = wm_abs_url($row['creative_media_url'] ?? '');
        if ($imageUrl === '') $imageUrl = wm_abs_url($row['creative_image_url'] ?? '');

        if ($imageUrl === '' || stripos($imageUrl, 'https://') !== 0) {
            return ['ok' => false, 'error' => 'Facebook image URL missing or not https'];
        }

        if (!defined('FB_GRAPH_VERSION') || !defined('FB_PAGE_ID') || !defined('FB_PAGE_ACCESS_TOKEN')) {
            return ['ok' => false, 'error' => 'Facebook config missing'];
        }

        $url = "https://graph.facebook.com/" . FB_GRAPH_VERSION . "/" . FB_PAGE_ID . "/photos";
        $res = wm_http_post_json($url, [
            'url' => $imageUrl,
            'caption' => $caption,
            'published' => 'true',
            'access_token' => FB_PAGE_ACCESS_TOKEN,
        ]);

        if (!$res['ok'] || empty($res['json']['post_id'])) {
            $msg = $res['error'] ?: ($res['json']['error']['message'] ?? $res['raw'] ?? 'Facebook photo publish failed');
            $stmt = $mysqli->prepare("UPDATE content_queue SET facebook_publish_status='failed', facebook_publish_error=?, status='approved' WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("si", $msg, $id);
                $stmt->execute();
            }
            return ['ok' => false, 'error' => $msg, 'raw' => $res['raw']];
        }

        $postId = (string)$res['json']['post_id'];
        $publishedUrl = "https://www.facebook.com/" . $postId;
        $now = date("Y-m-d H:i:s");

        $stmt = $mysqli->prepare("UPDATE content_queue 
            SET status='published',
                facebook_publish_status='published',
                facebook_publish_error=NULL,
                facebook_post_id=?,
                published_url=?,
                published_at=?
            WHERE id=?");
        if (!$stmt) {
            return ['ok' => false, 'error' => 'DB prepare failed: '.$mysqli->error];
        }
        $stmt->bind_param("sssi", $postId, $publishedUrl, $now, $id);
        $stmt->execute();

        return ['ok' => true, 'post_id' => $postId, 'published_url' => $publishedUrl];
    }
}

