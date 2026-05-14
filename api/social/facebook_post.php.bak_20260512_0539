<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/social_config.php';

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);

$input = [];

if (is_array($json)) {
    $input = $json;
} elseif (!empty($_POST)) {
    $input = $_POST;
} else {
    parse_str($raw, $parsed);
    if (is_array($parsed)) {
        $input = $parsed;
    }
}

$message = trim($input['message'] ?? $input['caption'] ?? '');

if ($message === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Message is required'
    ]);
    exit;
}

$url = 'https://graph.facebook.com/' . FB_GRAPH_VERSION . '/' . FB_PAGE_ID . '/feed';

$postData = [
    'message' => $message,
    'access_token' => FB_PAGE_ACCESS_TOKEN
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $curlError ?: 'Facebook cURL failed'
    ]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300 && isset($data['id'])) {
    echo json_encode([
        'ok' => true,
        'post_id' => $data['id'],
        'facebook_post_id' => $data['id'],
        'response' => $data
    ]);
    exit;
}

http_response_code($httpCode ?: 500);
echo json_encode([
    'ok' => false,
    'error' => $data['error']['message'] ?? 'Facebook API failed',
    'api_response' => $data,
    '_http_code' => $httpCode
]);
