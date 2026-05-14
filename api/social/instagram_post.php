<?php

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

require_once __DIR__ . '/../../config/social_config.php';

// WM_IMAGE_URL_INPUT_FINAL
$wm_input_image_url = trim($_POST['image_url'] ?? $_POST['creative_image_url'] ?? '');
if ($wm_input_image_url !== '' && stripos($wm_input_image_url, 'https://') !== 0) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'image_url must start with https://']);
    exit;
}


// WM_CREATIVE_IMAGE_URL_PATCH
$wm_post_image_url = trim($_POST['image_url'] ?? $_POST['creative_image_url'] ?? '');
if ($wm_post_image_url !== '' && stripos($wm_post_image_url, 'https://') !== 0) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'image_url must start with https://']);
    exit;
}


$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = [];
}

// Supports JSON body + normal form-data/x-www-form-urlencoded POST
$caption = trim(
    $_POST['message']
    ?? $_POST['caption']
    ?? $input['message']
    ?? $input['caption']
    ?? ''
);

$imageUrl = trim(
    $_POST['image_url']
    ?? $_POST['creative_image_url']
    ?? $input['image_url']
    ?? $input['creative_image_url']
    ?? ''
);

if ($caption === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Caption/message is required'
    ]);
    exit;
}

if ($imageUrl === '') {
    $imageUrl = ($wm_post_image_url !== '' ? $wm_post_image_url : 'https://wealthmetre.com/images/logo.png');
}

if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Valid image_url is required'
    ]);
    exit;
}

if (!defined('IG_USER_ID') || !defined('FB_PAGE_ACCESS_TOKEN') || !defined('FB_GRAPH_VERSION')) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Instagram config constants missing'
    ]);
    exit;
}

function graphGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'error' => $error,
        'data' => json_decode($response, true),
        'raw' => $response
    ];
}

function graphPost(string $url, array $postData): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'error' => $error,
        'data' => json_decode($response, true),
        'raw' => $response
    ];
}

// Step 1: Create media container
$createUrl = 'https://graph.facebook.com/' . FB_GRAPH_VERSION . '/' . IG_USER_ID . '/media';

$create = graphPost($createUrl, [
    'image_url' => $imageUrl,
    'caption' => $caption,
    'access_token' => FB_PAGE_ACCESS_TOKEN
]);

if ($create['error']) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'stage' => 'create_container',
        'error' => $create['error']
    ]);
    exit;
}

if ($create['http_code'] < 200 || $create['http_code'] >= 300 || empty($create['data']['id'])) {
    http_response_code($create['http_code'] ?: 500);
    echo json_encode([
        'ok' => false,
        'stage' => 'create_container',
        'error' => $create['data']['error']['message'] ?? 'Instagram media container creation failed',
        'response' => $create['data']
    ]);
    exit;
}

$creationId = $create['data']['id'];

// Step 2: Wait until media container is ready
$statusUrl = 'https://graph.facebook.com/' . FB_GRAPH_VERSION . '/' . rawurlencode($creationId)
    . '?fields=id,status_code,status'
    . '&access_token=' . rawurlencode(FB_PAGE_ACCESS_TOKEN);

$statusChecks = [];

for ($i = 1; $i <= 8; $i++) {
    sleep(3);

    $status = graphGet($statusUrl);
    $statusData = $status['data'] ?? [];
    $statusCode = $statusData['status_code'] ?? '';

    $statusChecks[] = [
        'try' => $i,
        'http_code' => $status['http_code'],
        'status_code' => $statusCode,
        'status' => $statusData['status'] ?? null
    ];

    if ($statusCode === 'FINISHED') {
        break;
    }

    if (in_array($statusCode, ['ERROR', 'EXPIRED'], true)) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'stage' => 'container_status',
            'creation_id' => $creationId,
            'error' => 'Instagram media container failed or expired',
            'status_checks' => $statusChecks,
            'response' => $statusData
        ]);
        exit;
    }
}

// Step 3: Publish media with retry
$publishUrl = 'https://graph.facebook.com/' . FB_GRAPH_VERSION . '/' . IG_USER_ID . '/media_publish';

$lastPublish = null;

for ($i = 1; $i <= 5; $i++) {
    $publish = graphPost($publishUrl, [
        'creation_id' => $creationId,
        'access_token' => FB_PAGE_ACCESS_TOKEN
    ]);

    $lastPublish = $publish;

    if (!$publish['error'] && $publish['http_code'] >= 200 && $publish['http_code'] < 300 && !empty($publish['data']['id'])) {
        echo json_encode([
            'ok' => true,
            'creation_id' => $creationId,
            'media_id' => $publish['data']['id'],
            'image_url' => $imageUrl,
            'status_checks' => $statusChecks
        ]);
        exit;
    }

    $message = $publish['data']['error']['message'] ?? '';

    // Instagram sometimes says media is not ready even after status check.
    if (stripos($message, 'Media ID is not available') !== false || stripos($message, 'not ready') !== false) {
        sleep(5);
        continue;
    }

    break;
}

http_response_code($lastPublish['http_code'] ?? 500);
echo json_encode([
    'ok' => false,
    'stage' => 'publish_media',
    'creation_id' => $creationId,
    'http_code' => $lastPublish['http_code'] ?? 500,
    'status_checks' => $statusChecks,
    'response' => $lastPublish['data'] ?? null
]);
