<?php
/**
 * WealthMetre — Content Studio
 * File: public_html/admin/content-studio.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/social_config.php';

$pdo = getDB();

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function shortText($v, int $limit = 180): string {
    $v = trim((string)$v);
    if ($v === '') return '—';
    return mb_strlen($v) > $limit ? mb_substr($v, 0, $limit) . '...' : $v;
}

function badgeClass($status): string {
    return match ($status) {
        'approved' => 'badge green',
        'published' => 'badge blue',
        'rejected', 'failed' => 'badge red',
        'needs_review' => 'badge orange',
        default => 'badge gray',
    };
}

function typeLabel($type): string {
    return match ($type) {
        'blog' => 'Blog',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'linkedin' => 'LinkedIn',
        'gbp_post' => 'GBP',
        'local_seo_page' => 'Local SEO',
        default => ucfirst((string)$type),
    };
}

function typeIcon($type): string {
    return match ($type) {
        'blog' => '📝',
        'instagram' => '📸',
        'facebook' => '📘',
        'linkedin' => '💼',
        'gbp_post' => '📍',
        'local_seo_page' => '🌐',
        default => '📄',
    };
}

function pomelliBrief(array $row): string {
    $type = typeLabel($row['content_type'] ?? '');
    $topic = $row['topic'] ?: $row['title'];
    $title = $row['title'] ?: $row['topic'];
    $cta = $row['cta'] ?: 'Use Diva AI Loan Advisor on WealthMetre to compare suitable loan options.';
    $content = $row['content_short'] ?: $row['meta_description'] ?: $row['content_long'];

    return trim("Create a {$type} creative for WealthMetre.

Brand:
WealthMetre is an AI-powered loan advisory platform in Jaipur/Rajasthan.
It helps users compare 140+ banks and NBFCs for Home Loan, LAP, Business Loan, Personal Loan and Working Capital.

Topic:
{$topic}

Creative title/message:
{$title}

Main content angle:
" . shortText($content, 650) . "

CTA:
{$cta}

Visual direction:
Clean, modern finance + property + AI advisory theme.
Use blue, orange, white and dark navy style.
Make it suitable for Indian borrowers, especially Jaipur/Rajasthan audience.

Compliance:
Do not promise guaranteed approval.
Do not say lowest rate guaranteed.
Do not show fake lender approval.
Mention that eligibility depends on lender policy, CIBIL, income, property/legal/technical checks and documentation.

Output needed:
1. One strong creative headline
2. One short subheading
3. Suggested visual layout
4. Caption idea
5. Hashtag ideas");
}


function publishToFacebookPage(string $message): array {
    $configPath = __DIR__ . '/../config/social_config.php';

    if (!file_exists($configPath)) {
        return ['ok' => false, 'error' => 'Facebook config file missing.'];
    }

    require_once $configPath;

    if (!defined('FB_PAGE_ID') || !defined('FB_PAGE_ACCESS_TOKEN') || !defined('FB_GRAPH_VERSION')) {
        return ['ok' => false, 'error' => 'Facebook config constants missing.'];
    }

    $message = trim($message);
    if ($message === '') {
        return ['ok' => false, 'error' => 'Facebook post message is empty.'];
    }

    $url = 'https://graph.facebook.com/' . FB_GRAPH_VERSION . '/' . FB_PAGE_ID . '/feed';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'message' => $message,
            'access_token' => FB_PAGE_ACCESS_TOKEN
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return ['ok' => false, 'error' => $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300 || empty($data['id'])) {
        return [
            'ok' => false,
            'error' => $data['error']['message'] ?? 'Facebook publish failed.',
            'raw' => $data
        ];
    }

    $postId = $data['id'];
    $permalink = '';

    $readUrl = 'https://graph.facebook.com/' . FB_GRAPH_VERSION . '/' . rawurlencode($postId)
        . '?fields=id,permalink_url,is_published'
        . '&access_token=' . rawurlencode(FB_PAGE_ACCESS_TOKEN);

    $ch = curl_init($readUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);

    $readResponse = curl_exec($ch);
    $readHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($readHttpCode >= 200 && $readHttpCode < 300) {
        $readData = json_decode($readResponse, true);
        $permalink = $readData['permalink_url'] ?? '';
    }

    return [
        'ok' => true,
        'post_id' => $postId,
        'permalink_url' => $permalink
    ];
}


function publishToInstagram(string $caption, string $imageUrl = ''): array {
    $configPath = __DIR__ . '/../config/social_config.php';

    if (!file_exists($configPath)) {
        return ['ok' => false, 'error' => 'Instagram config file missing.'];
    }

    require_once $configPath;

    if (!defined('IG_USER_ID') || !defined('FB_PAGE_ACCESS_TOKEN') || !defined('FB_GRAPH_VERSION')) {
        return ['ok' => false, 'error' => 'Instagram config constants missing.'];
    }

    $caption = trim($caption);
    if ($caption === '') {
        return ['ok' => false, 'error' => 'Instagram caption is empty.'];
    }

    $imageUrl = trim($imageUrl);
    if ($imageUrl === '') {
        return ['ok' => false, 'error' => 'No uploaded media found. Use Universal Media Upload or add creative_media_url.'];
    }

    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'error' => 'Invalid Instagram image URL.'];
    }

    $graphBase = 'https://graph.facebook.com/' . FB_GRAPH_VERSION;

    // Step 1: Create Instagram media container
    $createUrl = $graphBase . '/' . IG_USER_ID . '/media';

    $ch = curl_init($createUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => FB_PAGE_ACCESS_TOKEN
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
    ]);

    $createResponse = curl_exec($ch);
    $createError = curl_error($ch);
    $createHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($createError) {
        return ['ok' => false, 'error' => $createError];
    }

    $createData = json_decode($createResponse, true);

    if ($createHttpCode < 200 || $createHttpCode >= 300 || empty($createData['id'])) {
        return [
            'ok' => false,
            'error' => $createData['error']['message'] ?? 'Instagram media container creation failed.',
            'raw' => $createData
        ];
    }

    $creationId = $createData['id'];

    // Step 2: Poll media container until FINISHED
    $lastStatus = '';
    $lastPollRaw = null;

    for ($i = 1; $i <= 12; $i++) {
        $statusUrl = $graphBase . '/' . rawurlencode($creationId)
            . '?fields=id,status_code,status'
            . '&access_token=' . rawurlencode(FB_PAGE_ACCESS_TOKEN);

        $ch = curl_init($statusUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);

        $statusResponse = curl_exec($ch);
        $statusHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $statusData = json_decode((string)$statusResponse, true);
        $lastPollRaw = $statusData;

        if ($statusHttpCode >= 200 && $statusHttpCode < 300) {
            $lastStatus = (string)($statusData['status_code'] ?? $statusData['status'] ?? '');

            if (strtoupper($lastStatus) === 'FINISHED') {
                break;
            }

            if (in_array(strtoupper($lastStatus), ['ERROR', 'EXPIRED'], true)) {
                return [
                    'ok' => false,
                    'error' => 'Instagram media container failed with status: ' . $lastStatus,
                    'creation_id' => $creationId,
                    'raw' => $statusData
                ];
            }
        }

        sleep(3);
    }

    // Step 3: Publish Instagram media container with retries
    $publishUrl = $graphBase . '/' . IG_USER_ID . '/media_publish';
    $publishData = null;
    $lastPublishError = '';

    for ($i = 1; $i <= 10; $i++) {
        $ch = curl_init($publishUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'creation_id' => $creationId,
                'access_token' => FB_PAGE_ACCESS_TOKEN
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
        ]);

        $publishResponse = curl_exec($ch);
        $publishError = curl_error($ch);
        $publishHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($publishError) {
            $lastPublishError = $publishError;
            sleep(3);
            continue;
        }

        $publishData = json_decode((string)$publishResponse, true);

        if ($publishHttpCode >= 200 && $publishHttpCode < 300 && !empty($publishData['id'])) {
            $mediaId = $publishData['id'];

            // Step 4: Fetch permalink
            $permalink = '';
            $readUrl = $graphBase . '/' . rawurlencode($mediaId)
                . '?fields=id,permalink'
                . '&access_token=' . rawurlencode(FB_PAGE_ACCESS_TOKEN);

            $ch = curl_init($readUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
            ]);

            $readResponse = curl_exec($ch);
            $readHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($readHttpCode >= 200 && $readHttpCode < 300) {
                $readData = json_decode((string)$readResponse, true);
                $permalink = $readData['permalink'] ?? '';
            }

            return [
                'ok' => true,
                'creation_id' => $creationId,
                'media_id' => $mediaId,
                'permalink_url' => $permalink,
                'image_url' => $imageUrl
            ];
        }

        $lastPublishError = $publishData['error']['message'] ?? 'Instagram publish failed.';

        // Meta often returns "Media ID is not available" while container is still processing.
        if (
            stripos($lastPublishError, 'media id is not available') !== false ||
            stripos($lastPublishError, 'not ready') !== false ||
            stripos($lastPublishError, 'temporarily') !== false
        ) {
            sleep(4);
            continue;
        }

        // For other real errors, fail fast.
        return [
            'ok' => false,
            'error' => $lastPublishError,
            'creation_id' => $creationId,
            'last_status' => $lastStatus,
            'raw' => $publishData
        ];
    }

    return [
        'ok' => false,
        'error' => $lastPublishError ?: 'Instagram publish failed after retries.',
        'creation_id' => $creationId,
        'last_status' => $lastStatus,
        'last_poll_raw' => $lastPollRaw,
        'raw' => $publishData
    ];
}


/* WM_MANUAL_SOCIAL_MEDIA_FIX */
if (!function_exists('wm_cs_abs_url')) {
    function wm_cs_abs_url($url) {
        $url = trim((string)$url);
        if ($url === '') return '';
        if (stripos($url, 'https://') === 0) return $url;
        if (strpos($url, '/') === 0) return 'https://wealthmetre.com' . $url;
        return $url;
    }
}

if (!function_exists('wm_cs_media_url')) {
    function wm_cs_media_url(array $row): string {
        $media = trim((string)($row['creative_media_url'] ?? ''));
        if ($media === '') $media = trim((string)($row['creative_image_url'] ?? ''));
        return wm_cs_abs_url($media);
    }
}

if (!function_exists('wm_cs_is_video_url')) {
    function wm_cs_is_video_url(string $url): bool {
        return (bool)preg_match('/\.(mp4|mov|webm|m4v)(\?.*)?$/i', $url);
    }
}

if (!function_exists('wm_cs_graph_post')) {
    function wm_cs_graph_post(string $url, array $params): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode((string)$raw, true);
        if (!is_array($json)) $json = [];

        if ($raw === false || $http >= 400) {
            return [
                'ok' => false,
                'http_code' => $http,
                'error' => $json['error']['message'] ?? $err ?: $raw ?: 'Graph API failed',
                'raw' => $raw
            ];
        }

        return ['ok' => true, 'http_code' => $http, 'json' => $json, 'raw' => $raw];
    }
}

$allowedTypes = ['all','blog','instagram','facebook','linkedin','gbp_post','local_seo_page'];
$allowedStatuses = ['all','draft','needs_review','approved','published','rejected','failed'];

$type = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$q = trim($_GET['q'] ?? '');
$viewId = (int)($_GET['view'] ?? ($_GET['id'] ?? 0));

if (!in_array($type, $allowedTypes, true)) $type = 'all';
if (!in_array($status, $allowedStatuses, true)) $status = 'all';

$message = '';
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($id <= 0) throw new Exception('Invalid content ID.');

        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE content_queue SET status='approved', approved_by = 1, approved_at = NOW(), updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $message = "Content #{$id} approved.";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE content_queue SET status='rejected', updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $message = "Content #{$id} rejected.";
        } elseif ($action === 'review') {
            $stmt = $pdo->prepare("UPDATE content_queue SET status='needs_review', updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $message = "Content #{$id} moved to review.";
        } elseif ($action === 'publish_facebook') {
            $stmt = $pdo->prepare("SELECT * FROM content_queue WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception('Content not found.');
            }

            if (($row['content_type'] ?? '') !== 'facebook') {
                throw new Exception('Manual Facebook publish is allowed only for facebook rows.');
            }

            if (($row['status'] ?? '') === 'published' || ($row['facebook_publish_status'] ?? '') === 'published' || !empty($row['facebook_post_id'])) {
                throw new Exception('This Facebook row is already published. Duplicate publish blocked.');
            }
            // WM_EMPTY_CAPTION_GUARD_FB
            $wmRealCaption = trim((string)($row['content_long'] ?? '') ?: (string)($row['content_short'] ?? '') ?: (string)($row['meta_description'] ?? ''));
            if ($wmRealCaption === '') {
                throw new Exception('This post has no caption. Add content_short before publishing to Facebook.');
            }

            $caption = trim($row['content_long'] ?: $row['content_short'] ?: $row['meta_description'] ?: $row['title'] ?: $row['topic']);
            $hashtags = trim((string)($row['hashtags'] ?? ''));

            if ($hashtags !== '' && strpos($caption, $hashtags) === false) {
                $caption .= "\n\n" . $hashtags;
            }

            $mediaUrl = wm_cs_media_url($row);

            if ($mediaUrl === '') {
                throw new Exception('No uploaded media found. Use Universal Media Upload or add creative_media_url.');
            }

            if (stripos($mediaUrl, 'https://') !== 0) {
                throw new Exception('Media URL must start with https://');
            }

            if (wm_cs_is_video_url($mediaUrl) || strtolower((string)($row['creative_media_type'] ?? '')) === 'video') {
                $upd = $pdo->prepare("
                    UPDATE content_queue
                    SET facebook_publish_status='needs_video_support',
                        facebook_publish_error='Video publishing support pending.',
                        updated_at=NOW()
                    WHERE id=? LIMIT 1
                ");
                $upd->execute([$id]);

                throw new Exception('Video support pending. Facebook video publishing is not enabled yet.');
            }

            if (!defined('FB_GRAPH_VERSION') || !defined('FB_PAGE_ID') || !defined('FB_PAGE_ACCESS_TOKEN')) {
                throw new Exception('Facebook config missing.');
            }

            $fbPhotoUrl = 'https://graph.facebook.com/' . FB_GRAPH_VERSION . '/' . FB_PAGE_ID . '/photos';

            // WM_FB_DIRECT_UPLOAD: upload from disk to avoid Facebook URL-fetch failures
            $fbUrlPath  = parse_url($mediaUrl, PHP_URL_PATH);
            $fbFilePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $fbUrlPath;
            if (file_exists($fbFilePath) && is_readable($fbFilePath)) {
                $fbMime = mime_content_type($fbFilePath) ?: 'image/png';
                $fb = wm_cs_graph_post($fbPhotoUrl, [
                    'source'       => new CURLFile($fbFilePath, $fbMime, basename($fbFilePath)),
                    'caption'      => $caption,
                    'published'    => 'true',
                    'access_token' => FB_PAGE_ACCESS_TOKEN
                ]);
            } else {
                $fb = wm_cs_graph_post($fbPhotoUrl, [
                    'url'          => $mediaUrl,
                    'caption'      => $caption,
                    'published'    => 'true',
                    'access_token' => FB_PAGE_ACCESS_TOKEN
                ]);
            }

            if (!($fb['ok'] ?? false)) {
                $err = $fb['error'] ?? 'Facebook photo publish failed.';

                $upd = $pdo->prepare("
                    UPDATE content_queue
                    SET facebook_publish_status='failed',
                        facebook_publish_error=?,
                        updated_at=NOW()
                    WHERE id=? LIMIT 1
                ");
                $upd->execute([$err, $id]);

                throw new Exception($err);
            }

            $postId = $fb['json']['post_id'] ?? $fb['json']['id'] ?? '';
            if ($postId === '') {
                throw new Exception('Facebook photo published but post_id missing.');
            }

            $publishedUrl = 'https://www.facebook.com/' . $postId;

            $upd = $pdo->prepare("
                UPDATE content_queue
                SET status='published',
                    published_url=?,
                    published_at=NOW(),
                    facebook_post_id=?,
                    facebook_published_at=NOW(),
                    facebook_publish_status='published',
                    facebook_publish_error=NULL,
                    updated_at=NOW()
                WHERE id=? LIMIT 1
            ");
            $upd->execute([$publishedUrl, $postId, $id]);

            $message = "Content #{$id} published to Facebook with image. Post ID: {$postId}";

        } elseif ($action === 'publish_instagram') {
            $stmt = $pdo->prepare("SELECT * FROM content_queue WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception('Content not found.');
            }

            if (($row['content_type'] ?? '') !== 'instagram') {
                throw new Exception('Manual Instagram publish is allowed only for instagram rows.');
            }

            if (($row['status'] ?? '') === 'published' || ($row['instagram_publish_status'] ?? '') === 'published' || !empty($row['instagram_media_id'])) {
                throw new Exception('This Instagram row is already published. Duplicate publish blocked.');
            }
            // WM_EMPTY_CAPTION_GUARD_IG
            $wmRealCaption = trim((string)($row['content_long'] ?? '') ?: (string)($row['content_short'] ?? '') ?: (string)($row['meta_description'] ?? ''));
            if ($wmRealCaption === '') {
                throw new Exception('This post has no caption. Add content_short before publishing to Instagram.');
            }

            $caption = trim($row['content_long'] ?: $row['content_short'] ?: $row['meta_description'] ?: $row['title'] ?: $row['topic']);
            $hashtags = trim((string)($row['hashtags'] ?? ''));

            if ($hashtags !== '' && strpos($caption, $hashtags) === false) {
                $caption .= "\n\n" . $hashtags;
            }

            $imageUrl = wm_cs_media_url($row);

            if ($imageUrl === '') {
                throw new Exception('No uploaded media found. Use Universal Media Upload or add creative_media_url.');
            }

            if (stripos($imageUrl, 'https://') !== 0) {
                throw new Exception('Media URL must start with https://');
            }

            if (wm_cs_is_video_url($imageUrl) || strtolower((string)($row['creative_media_type'] ?? '')) === 'video') {
                $upd = $pdo->prepare("
                    UPDATE content_queue
                    SET instagram_publish_status='needs_video_support',
                        instagram_publish_error='Video publishing support pending.',
                        updated_at=NOW()
                    WHERE id=? LIMIT 1
                ");
                $upd->execute([$id]);

                throw new Exception('Video support pending. Instagram video publishing is not enabled yet.');
            }

            $ig = publishToInstagram($caption, $imageUrl);

            if (!($ig['ok'] ?? false)) {
                $err = $ig['error'] ?? 'Instagram publish failed.';

                $upd = $pdo->prepare("
                    UPDATE content_queue
                    SET instagram_publish_status='failed',
                        instagram_publish_error=?,
                        updated_at=NOW()
                    WHERE id=? LIMIT 1
                ");
                $upd->execute([$err, $id]);

                throw new Exception($err);
            }

            $mediaId = $ig['media_id'] ?? '';
            $permalink = $ig['permalink_url'] ?? '';
            $usedImageUrl = $ig['image_url'] ?? $imageUrl;

            $upd = $pdo->prepare("
                UPDATE content_queue
                SET status='published',
                    published_url = COALESCE(NULLIF(published_url, ''), NULLIF(?, '')),
                    published_at=COALESCE(published_at, NOW()),
                    instagram_media_id=?,
                    instagram_image_url=?,
                    instagram_published_at=NOW(),
                    instagram_publish_status='published',
                    instagram_publish_error=NULL,
                    updated_at=NOW()
                WHERE id=? LIMIT 1
            ");
            $upd->execute([$permalink, $mediaId, $usedImageUrl, $id]);

            $message = "Content #{$id} published to Instagram with uploaded image. Media ID: {$mediaId}";

        } elseif ($action === 'published') {
            $publishedUrl = trim($_POST['published_url'] ?? '');

            if ($publishedUrl !== '' && !filter_var($publishedUrl, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid published URL. Please enter a valid URL starting with https://');
            }

            $stmt = $pdo->prepare("
                UPDATE content_queue
                SET status='published',
                    published_url = NULLIF(?, ''),
                    published_at=NOW(),
                    updated_at=NOW()
                WHERE id=? LIMIT 1
            ");
            $stmt->execute([$publishedUrl, $id]);
            $message = "Content #{$id} marked as published.";
        } else {
            throw new Exception('Invalid action.');
        }
    }
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}

$where = [];
$params = [];

if ($type !== 'all') {
    $where[] = 'content_type = ?';
    $params[] = $type;
}
if ($status !== 'all') {
    $where[] = 'status = ?';
    $params[] = $status;
} else {
    $where[] = "status != 'rejected'";
}
if ($q !== '') {
    $where[] = '(topic LIKE ? OR title LIKE ? OR slug LIKE ? OR target_keyword LIKE ? OR city LIKE ? OR loan_product LIKE ?)';
    for ($i=0; $i<6; $i++) $params[] = "%{$q}%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$rows = [];
$selected = null;
$stats = [
    'total' => 0,
    'blog' => 0,
    'instagram' => 0,
    'facebook' => 0,
    'linkedin' => 0,
    'gbp_post' => 0,
    'needs_review' => 0,
    'approved' => 0,
    'published' => 0,
];

try {
    $stmt = $pdo->prepare("
        SELECT id, content_type, topic, target_keyword, city, loan_product, title, meta_title,
               meta_description, slug, content_short, cta, status, created_at, updated_at, published_at,
               creative_image_url, creative_media_url, published_url, facebook_post_id,
               facebook_publish_status, instagram_publish_status
          FROM content_queue
        $whereSql
        ORDER BY
          CASE status
            WHEN 'needs_review' THEN 1
            WHEN 'approved'     THEN 2
            WHEN 'draft'        THEN 3
            WHEN 'failed'       THEN 4
            WHEN 'published'    THEN 5
            ELSE 6
          END ASC,
          created_at DESC, id DESC
        LIMIT 150
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $typeStats = $pdo->query("SELECT content_type, COUNT(*) total FROM content_queue GROUP BY content_type")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($typeStats as $r) {
        $stats['total'] += (int)$r['total'];
        if (isset($stats[$r['content_type']])) $stats[$r['content_type']] = (int)$r['total'];
    }

    $statusStats = $pdo->query("SELECT status, COUNT(*) total FROM content_queue GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statusStats as $r) {
        if (isset($stats[$r['status']])) $stats[$r['status']] = (int)$r['total'];
    }

    if ($viewId > 0) {
        $detail = $pdo->prepare("SELECT * FROM content_queue WHERE id=? LIMIT 1");
        $detail->execute([$viewId]);
        $selected = $detail->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}

$baseUrl = '/admin/content-studio.php?type=' . urlencode($type) . '&status=' . urlencode($status) . '&q=' . urlencode($q);

/* WM_CONTENT_STUDIO_UI_HELPERS */
if (!function_exists('wm_media_url')) {
  function wm_media_url($r) {
    return trim((string)($r['creative_media_url'] ?? $r['creative_image_url'] ?? ''));
  }
}
if (!function_exists('wm_is_video_media')) {
  function wm_is_video_media($url) {
    return (bool)preg_match('/\.(mp4|mov|webm|m4v)(\?.*)?$/i', (string)$url);
  }
}
if (!function_exists('wm_is_image_media')) {
  function wm_is_image_media($url) {
    return (bool)preg_match('/\.(png|jpe?g|webp|gif)(\?.*)?$/i', (string)$url);
  }
}
if (!function_exists('wm_view_post_url')) {
  function wm_view_post_url($r) {
    $published = trim((string)($r['published_url'] ?? ''));
    if ($published !== '') return $published;

    $fbid = trim((string)($r['facebook_post_id'] ?? ''));
    if ($fbid !== '') return 'https://www.facebook.com/' . $fbid;

    return '';
  }
}
if (!function_exists('wm_badge_class')) {
  function wm_badge_class($status) {
    $status = strtolower(trim((string)$status));

    if ($status === 'published') return 'wm-badge wm-badge-green';
    if ($status === 'approved') return 'wm-badge wm-badge-blue';
    if ($status === 'needs_review') return 'wm-badge wm-badge-orange';
    if ($status === 'rejected' || $status === 'failed') return 'wm-badge wm-badge-red';
    if ($status === 'not_published') return 'wm-badge wm-badge-muted';

    return 'wm-badge wm-badge-muted';
  }
}
if (!function_exists('wm_render_social_badges')) {
  function wm_render_social_badges($r) {
    $out = [];

    $status = (string)($r['status'] ?? '');
    if ($status !== '') {
      $out[] = '<span class="' . e(wm_badge_class($status)) . '">' . e($status) . '</span>';
    }

    $media = wm_media_url($r);
    if ($media !== '') {
      if (wm_is_video_media($media)) {
        $out[] = '<span class="wm-badge wm-badge-purple">video support pending</span>';
      } elseif (wm_is_image_media($media)) {
        $out[] = '<span class="wm-badge wm-badge-teal">image attached</span>';
      } else {
        $out[] = '<span class="wm-badge wm-badge-muted">media attached</span>';
      }
    }

    $fb = (string)($r['facebook_publish_status'] ?? '');
    if ($fb !== '') {
      $out[] = '<span class="' . e(wm_badge_class($fb)) . '">facebook ' . e($fb) . '</span>';
    }

    $ig = (string)($r['instagram_publish_status'] ?? '');
    if ($ig !== '') {
      $out[] = '<span class="' . e(wm_badge_class($ig)) . '">instagram ' . e($ig) . '</span>';
    }

    return implode(' ', $out);
  }
}

?>
<!doctype html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8">
<title>Content Studio | WealthMetre</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
<script id="tailwind-config">
tailwind.config={darkMode:"class",theme:{extend:{colors:{"on-surface-variant":"#c2c6d6","primary":"#adc6ff","tertiary-container":"#df7412","secondary-fixed":"#dce2f7","primary-fixed-dim":"#adc6ff","on-tertiary-container":"#461f00","surface-container-lowest":"#090e1c","on-surface":"#dee1f7","on-primary-fixed":"#001a42","on-background":"#dee1f7","background":"#0e1322","surface-container":"#1a1f2f","surface-container-low":"#161b2b","surface":"#0e1322","secondary-container":"#404758","on-error":"#690005","inverse-surface":"#dee1f7","on-tertiary-fixed-variant":"#723600","on-primary-fixed-variant":"#004395","outline":"#8c909f","error-container":"#93000a","on-tertiary":"#502400","on-secondary-container":"#aeb5c9","surface-tint":"#adc6ff","tertiary":"#ffb786","on-tertiary-fixed":"#311400","surface-variant":"#2f3445","on-secondary-fixed":"#141b2b","on-primary":"#002e6a","surface-bright":"#343949","inverse-primary":"#005ac2","surface-container-highest":"#2f3445","secondary":"#c0c6db","inverse-on-surface":"#2b3040","secondary-fixed-dim":"#c0c6db","on-error-container":"#ffdad6","on-secondary":"#293040","primary-fixed":"#d8e2ff","primary-container":"#4d8eff","outline-variant":"#424754","surface-container-high":"#25293a","tertiary-fixed":"#ffdcc6","on-primary-container":"#00285d","surface-dim":"#0e1322","error":"#ffb4ab","on-secondary-fixed-variant":"#404758","tertiary-fixed-dim":"#ffb786"}}}}
</script>
<style>
body{background-color:#0a0f1e;background-image:linear-gradient(rgba(255,255,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px);background-size:32px 32px;font-family:'Geist',sans-serif;color:#dee1f7}
.glass-card{background:rgba(17,24,39,0.7);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08)}
.instagram-gradient{background:linear-gradient(45deg,#833AB4,#FD1D1D,#FCB045)}
.instagram-glow:hover{box-shadow:0 10px 40px -10px rgba(253,29,29,0.4)}
.mesh-gradient{background-color:#0e1322;background-image:radial-gradient(at 0% 0%,rgba(77,142,255,0.15) 0px,transparent 50%),radial-gradient(at 100% 100%,rgba(173,198,255,0.1) 0px,transparent 50%)}
.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
.scrollbar-hide::-webkit-scrollbar{display:none}
.scrollbar-hide{-ms-overflow-style:none;scrollbar-width:none}
.wm-badge{display:inline-flex;align-items:center;border-radius:999px;padding:3px 8px;font-size:10.5px;font-weight:700;text-transform:lowercase;letter-spacing:.01em;white-space:nowrap}
.wm-badge-green{background:rgba(16,185,129,0.15);color:#10b981;border:1px solid rgba(16,185,129,0.2)}
.wm-badge-blue{background:rgba(77,142,255,0.15);color:#adc6ff;border:1px solid rgba(77,142,255,0.2)}
.wm-badge-orange{background:rgba(251,146,60,0.15);color:#ffb786;border:1px solid rgba(251,146,60,0.2)}
.wm-badge-red{background:rgba(239,68,68,0.15);color:#ef4444;border:1px solid rgba(239,68,68,0.2)}
.wm-badge-purple{background:rgba(167,139,250,0.15);color:#c4b5fd;border:1px solid rgba(167,139,250,0.2)}
.wm-badge-teal{background:rgba(20,184,166,0.15);color:#2dd4bf;border:1px solid rgba(20,184,166,0.2)}
.wm-badge-muted{background:rgba(156,163,175,0.15);color:#9ca3af;border:1px solid rgba(156,163,175,0.2)}
</style>
</head>
<body class="h-screen overflow-hidden flex">

<!-- LEFT SIDEBAR -->
<aside class="fixed left-0 top-0 h-full w-[220px] bg-background/80 backdrop-blur-xl border-r border-outline-variant/10 shadow-2xl flex flex-col py-8 z-50">
  <div class="px-6 mb-8 flex items-center gap-3">
    <div class="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center">
      <span class="material-symbols-outlined text-primary text-[18px]">auto_awesome</span>
    </div>
    <div>
      <h1 class="text-[18px] font-bold text-on-surface leading-tight">WealthMetre</h1>
      <p class="text-[10px] text-primary tracking-widest uppercase">Content Studio</p>
    </div>
  </div>
  <nav class="flex-1 space-y-0.5 px-2">
    <?php
    $navItems=[
      ['all','queue','All Queue',$stats['total']],
      ['blog','article','Blog',$stats['blog']],
      ['instagram','photo_camera','Instagram',$stats['instagram']],
      ['facebook','thumb_up','Facebook',$stats['facebook']],
      ['linkedin','share','LinkedIn',$stats['linkedin']],
      ['gbp_post','storefront','GBP',$stats['gbp_post']],
    ];
    foreach($navItems as [$ntype,$nicon,$nlabel,$ncount]):
      $isNav=($type===$ntype);
    ?>
    <a href="/admin/content-studio.php?type=<?= e($ntype) ?>&status=<?= e($status) ?>"
       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all <?= $isNav ? 'bg-primary/10 text-primary border-r-2 border-primary shadow-[0_0_15px_rgba(173,198,255,0.3)]' : 'text-on-surface-variant hover:bg-surface-container-highest/50' ?>">
      <span class="material-symbols-outlined text-[20px]"><?= e($nicon) ?></span>
      <span class="text-[14px] flex-1"><?= e($nlabel) ?></span>
      <?php if($ncount>0): ?><span class="bg-primary/20 text-primary text-[10px] px-1.5 rounded-full font-bold"><?= (int)$ncount ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
    <div class="pt-3 mt-3 border-t border-outline-variant/10">
      <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-highest/50 transition-all">
        <span class="material-symbols-outlined text-[20px]">cloud_upload</span>
        <span class="text-[14px]">Upload</span>
      </a>
    </div>
  </nav>
  <div class="px-6 mt-auto">
    <div class="glass-card p-4 rounded-xl space-y-3 mb-3">
      <div class="flex justify-between items-center">
        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Published</span>
        <span class="text-[14px] font-bold text-primary"><?= (int)$stats['published'] ?></span>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Review</span>
        <span class="text-[14px] font-bold text-tertiary"><?= (int)$stats['needs_review'] ?></span>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Approved</span>
        <span class="text-[14px] font-bold" style="color:#10b981"><?= (int)$stats['approved'] ?></span>
      </div>
    </div>
    <a href="/admin/owner_dashboard.html" target="_top" class="flex items-center gap-2 px-3 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-highest/50 transition-all text-[13px]">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back
    </a>
  </div>
</aside>

<!-- MAIN CONTENT -->
<main class="ml-[220px] mr-[340px] flex-1 h-screen overflow-y-auto scrollbar-hide">
  <!-- Top bar -->
  <header class="h-16 flex items-center justify-between px-6 bg-background/60 backdrop-blur-md sticky top-0 z-40 border-b border-outline-variant/10">
    <form class="flex items-center flex-1 max-w-xl gap-2" method="get">
      <input type="hidden" name="type" value="<?= e($type) ?>">
      <input type="hidden" name="status" value="<?= e($status) ?>">
      <div class="relative flex-1">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
        <input name="q" value="<?= e($q) ?>" class="w-full bg-surface-container-low border border-outline-variant/20 rounded-full py-2 pl-9 pr-4 text-[13px] text-on-surface focus:outline-none focus:border-primary/50 transition-all" placeholder="Search title, topic, keyword, city...">
      </div>
      <button type="submit" class="bg-primary/10 border border-primary/20 text-primary px-4 py-2 rounded-full text-[11px] font-bold">Search</button>
      <a href="/admin/content-studio.php" class="text-on-surface-variant text-[11px] hover:text-primary transition-colors">Reset</a>
    </form>
    <div class="flex items-center gap-3 ml-4">
      <?php if($stats['needs_review']>0): ?>
      <div class="relative cursor-pointer">
        <span class="material-symbols-outlined text-tertiary">notifications</span>
        <span class="absolute -top-1 -right-1 w-4 h-4 bg-tertiary rounded-full flex items-center justify-center text-[9px] font-bold text-on-tertiary animate-pulse"><?= (int)$stats['needs_review'] ?></span>
      </div>
      <?php endif; ?>
    </div>
  </header>

  <div class="p-8 space-y-6">
    <?php if($message): ?><div class="bg-green-500/10 border border-green-500/20 text-green-400 px-4 py-3 rounded-xl text-[14px]"><?= e($message) ?></div><?php endif; ?>
    <?php if($error): ?><div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl text-[14px]"><?= e($error) ?></div><?php endif; ?>

    <!-- Studio Banner -->
    <section class="mesh-gradient rounded-3xl p-8 border border-outline-variant/10 overflow-hidden">
      <h2 class="text-4xl font-bold text-on-surface mb-2">Content Studio</h2>
      <p class="text-[16px] text-on-surface-variant mb-6">Create · Review · Approve · Publish</p>
      <div class="flex flex-wrap gap-3">
        <span class="bg-primary/10 border border-primary/20 text-primary px-3 py-1.5 rounded-full text-[11px] font-bold tracking-widest uppercase flex items-center gap-2"><span class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>AI Powered</span>
        <span class="bg-secondary/10 border border-secondary/20 text-secondary px-3 py-1.5 rounded-full text-[11px] font-bold tracking-widest uppercase flex items-center gap-2"><span class="w-1.5 h-1.5 bg-secondary rounded-full animate-pulse"></span>Social Ready</span>
        <span class="bg-tertiary/10 border border-tertiary/20 text-tertiary px-3 py-1.5 rounded-full text-[11px] font-bold tracking-widest uppercase flex items-center gap-2"><span class="w-1.5 h-1.5 bg-tertiary rounded-full animate-pulse"></span>Approval Queue</span>
        <span class="bg-on-surface-variant/10 border border-on-surface-variant/20 text-on-surface-variant px-3 py-1.5 rounded-full text-[11px] font-bold tracking-widest uppercase flex items-center gap-2"><span class="w-1.5 h-1.5 bg-on-surface-variant rounded-full animate-pulse"></span>Multi-Channel</span>
      </div>
    </section>

    <!-- Platform Cards -->
    <div class="grid grid-cols-5 gap-4">
      <?php
      $pCards=[
        ['article','blog','Blog Posts',$stats['blog'],'hover:border-blue-500/40','bg-blue-600/20','text-blue-400',''],
        ['photo_camera','instagram','Instagram',$stats['instagram'],'instagram-glow hover:border-pink-500/40','instagram-gradient','text-white',''],
        ['thumb_up','facebook','Facebook',$stats['facebook'],'hover:border-blue-500/40','bg-blue-700/20','text-blue-500',''],
        ['share','linkedin','LinkedIn',$stats['linkedin'],'hover:border-sky-400/40','bg-sky-600/20','text-sky-400',''],
        ['storefront','gbp_post','Google GBP',$stats['gbp_post'],'hover:border-amber-400/40','bg-amber-600/20','text-amber-400',''],
      ];
      foreach($pCards as [$picon,$ptype,$plabel,$pcount,$phover,$picbg,$piccolor,$_]):
      ?>
      <a href="/admin/content-studio.php?type=<?= e($ptype) ?>&status=<?= e($status) ?>"
         class="glass-card p-5 rounded-2xl flex flex-col items-center gap-4 <?= $phover ?> transition-all cursor-pointer <?= $type===$ptype ? 'border-primary/40 bg-primary/5' : '' ?>">
        <div class="w-10 h-10 rounded-full <?= $picbg ?> flex items-center justify-center <?= $piccolor ?>">
          <span class="material-symbols-outlined"><?= e($picon) ?></span>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold"><?= (int)$pcount ?></div>
          <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest"><?= e($plabel) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Filter chips -->
    <div class="flex items-center flex-wrap gap-2">
      <?php
      $sChips=['all'=>'All','needs_review'=>'Review','approved'=>'Approved','published'=>'Published','draft'=>'Draft','failed'=>'Failed'];
      foreach($sChips as $sv=>$sl):
        $sa=($status===$sv);
      ?>
      <a href="/admin/content-studio.php?type=<?= e($type) ?>&status=<?= e($sv) ?>&q=<?= e($q) ?>"
         class="px-4 py-1.5 rounded-full text-[11px] font-bold tracking-wide transition-all <?= $sa ? 'bg-primary text-on-primary' : 'glass-card text-on-surface-variant hover:bg-surface-container-highest' ?>">
        <?= e($sl) ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Content Pipeline -->
    <div class="glass-card rounded-2xl overflow-hidden">
      <div class="p-5 border-b border-outline-variant/10 flex justify-between items-center">
        <h3 class="text-[18px] font-bold">Content Pipeline</h3>
        <span class="text-[13px] text-on-surface-variant"><?= count($rows) ?> items</span>
      </div>
      <?php if(!$rows): ?>
      <div class="p-16 text-center text-on-surface-variant">
        <span class="material-symbols-outlined text-[48px] block mb-4 opacity-20">inbox</span>
        <p class="text-[16px]">No content found.</p>
        <p class="text-[12px] opacity-50 mt-1">Try adjusting your filters.</p>
      </div>
      <?php else: ?>
      <table class="w-full text-left">
        <thead>
          <tr class="bg-surface-container-low/50 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">
            <th class="px-5 py-4">Creative</th>
            <th class="px-5 py-4">Channel</th>
            <th class="px-5 py-4">Title</th>
            <th class="px-5 py-4">Status</th>
            <th class="px-5 py-4">Date</th>
            <th class="px-5 py-4">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant/10">
          <?php foreach($rows as $row):
            $isSel=($selected&&(int)$selected['id']===(int)$row['id']);
            $rowMedia=wm_cs_media_url($row);
            $rowSt=$row['status']??'draft';
            $stCls=match($rowSt){
              'needs_review'=>'bg-amber-500/15 text-amber-400 border-amber-500/20',
              'approved'=>'bg-green-500/15 text-green-400 border-green-500/20',
              'published'=>'bg-blue-500/15 text-blue-400 border-blue-500/20',
              'rejected','failed'=>'bg-red-500/15 text-red-400 border-red-500/20',
              default=>'bg-gray-500/15 text-gray-400 border-gray-500/20',
            };
            $stPulse=($rowSt==='needs_review'?'animate-pulse':'');
            $chData=match($row['content_type']??''){
              'instagram'=>['photo_camera','text-pink-500'],
              'facebook'=>['thumb_up','text-blue-500'],
              'linkedin'=>['share','text-sky-400'],
              'gbp_post'=>['storefront','text-amber-400'],
              default=>['article','text-blue-400'],
            };
            $isImg=wm_cs_is_video_url($rowMedia)?false:(bool)$rowMedia;
          ?>
          <tr onclick="window.location='/admin/content-studio.php?id=<?= (int)$row['id'] ?>&type=<?= e($type) ?>&status=<?= e($status) ?>&q=<?= e($q) ?>'"
              class="hover:bg-surface-container-highest/30 transition-colors cursor-pointer <?= $isSel ? 'bg-primary/5 border-l-4 border-primary' : '' ?>">
            <td class="px-5 py-4">
              <div class="w-12 h-12 rounded-xl bg-surface-container-highest overflow-hidden flex items-center justify-center flex-shrink-0">
                <?php if($rowMedia&&$isImg): ?>
                  <img src="<?= e($rowMedia) ?>" class="w-full h-full object-cover" alt="">
                <?php else: ?>
                  <span class="material-symbols-outlined <?= e($chData[1]) ?> text-[22px]"><?= e($chData[0]) ?></span>
                <?php endif; ?>
              </div>
            </td>
            <td class="px-5 py-4">
              <span class="material-symbols-outlined <?= e($chData[1]) ?>"><?= e($chData[0]) ?></span>
            </td>
            <td class="px-5 py-4 max-w-[180px]">
              <div class="text-[14px] font-medium truncate"><?= e(mb_substr($row['title']?:$row['topic']?:'',0,55)) ?></div>
              <?php if($row['city']??''): ?><div class="text-[11px] text-on-surface-variant mt-0.5"><?= e($row['city']) ?></div><?php endif; ?>
            </td>
            <td class="px-5 py-4">
              <span class="px-3 py-1 rounded-full text-[10px] font-bold border <?= $stCls ?> <?= $stPulse ?>"><?= e(ucwords(str_replace('_',' ',$rowSt))) ?></span>
            </td>
            <td class="px-5 py-4 text-[12px] text-on-surface-variant whitespace-nowrap"><?= e(date('M d, Y',strtotime($row['created_at']))) ?></td>
            <td class="px-5 py-4">
              <div class="flex items-center gap-1.5" onclick="event.stopPropagation()">
                <?php if(($row['status']??'')==='published'): ?>
                  <span class="material-symbols-outlined text-blue-400 text-[18px]">check_circle</span>
                <?php else: ?>
                  <form method="post" style="display:contents">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="w-7 h-7 rounded-full bg-green-500/10 border border-green-500/20 text-green-400 hover:bg-green-500/20 flex items-center justify-center transition-colors" title="Approve">
                      <span class="material-symbols-outlined text-[14px]">check</span>
                    </button>
                  </form>
                  <form method="post" style="display:contents">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="w-7 h-7 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 flex items-center justify-center transition-colors" title="Reject">
                      <span class="material-symbols-outlined text-[14px]">close</span>
                    </button>
                  </form>
                <?php endif; ?>
                <a href="/admin/content-studio.php?id=<?= (int)$row['id'] ?>&type=<?= e($type) ?>&status=<?= e($status) ?>&q=<?= e($q) ?>"
                   class="w-7 h-7 rounded-full bg-primary/10 border border-primary/20 text-primary hover:bg-primary/20 flex items-center justify-center transition-colors" title="Preview">
                  <span class="material-symbols-outlined text-[14px]">visibility</span>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- RIGHT PANEL -->
<aside class="fixed right-0 top-0 h-full w-[340px] bg-surface-container-low border-l border-outline-variant/10 flex flex-col z-50 overflow-y-auto scrollbar-hide">
  <div class="p-5 border-b border-outline-variant/10 bg-surface-container-low/50 sticky top-0 backdrop-blur-md z-10">
    <h3 class="text-[17px] font-bold">Content Preview</h3>
    <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mt-0.5">
      <?= $selected ? 'Reviewing: '.e(typeLabel($selected['content_type'])) : 'Select item from queue' ?>
    </p>
  </div>
  <div class="p-5 space-y-4">
    <?php if(!$selected): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center text-on-surface-variant">
      <span class="material-symbols-outlined text-[52px] opacity-20 mb-4">preview</span>
      <p class="text-[14px] font-medium">Nothing selected</p>
      <p class="text-[12px] opacity-50 mt-1">Click any row to preview, approve or publish</p>
    </div>
    <?php else:
      $mainContent=$selected['content_long']?:$selected['content_short']?:$selected['meta_description'];
      $wmSelectedMediaUrl=wm_cs_media_url($selected);
      $wmSelectedViewUrl=wm_cs_abs_url($selected['published_url']??'');
      $wmSelectedIsVideo=wm_cs_is_video_url($wmSelectedMediaUrl);
      $wmSelectedIsImage=!$wmSelectedIsVideo&&(bool)$wmSelectedMediaUrl;
      $wmCaption=trim((string)($selected['content_short']?:$selected['meta_description']?:$selected['content_long']?:''));
      $wmPrompt=trim((string)($selected['image_prompt']??''));
      $selectedType=(string)($selected['content_type']??'');
      $selectedStatus=(string)($selected['status']??'');
      $isSocialPost=in_array($selectedType,['instagram','facebook','linkedin','gbp_post'],true);
      $alreadyPublished=$selectedStatus==='published'||(($selected['facebook_publish_status']??'')==='published')||(($selected['instagram_publish_status']??'')==='published')||!empty($selected['facebook_post_id'])||!empty($selected['instagram_media_id']);
      $canPublishFacebook=!$alreadyPublished&&!$wmSelectedIsVideo&&$selectedType==='facebook'&&(($selected['facebook_publish_status']??'')<>'published')&&empty($selected['facebook_post_id']);
      $canPublishInstagram=!$alreadyPublished&&!$wmSelectedIsVideo&&$selectedType==='instagram'&&(($selected['instagram_publish_status']??'')<>'published')&&empty($selected['instagram_media_id']);
    ?>
    <!-- Media -->
    <?php if($wmSelectedMediaUrl): ?>
    <div class="rounded-2xl overflow-hidden border border-outline-variant/10">
      <?php if($wmSelectedIsImage): ?>
        <img src="<?= e($wmSelectedMediaUrl) ?>" class="w-full h-44 object-cover" alt="">
      <?php elseif($wmSelectedIsVideo): ?>
        <video src="<?= e($wmSelectedMediaUrl) ?>" controls class="w-full h-44 object-cover"></video>
        <div class="px-3 py-2 bg-purple-500/10 border-t border-purple-500/20 text-purple-400 text-[11px] font-bold">Video publishing support pending.</div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Title card -->
    <div class="glass-card rounded-2xl p-4 space-y-2">
      <div class="flex items-start justify-between gap-2">
        <h4 class="text-[15px] font-bold text-on-surface leading-snug"><?= e($selected['title']?:$selected['topic']) ?></h4>
        <?php if($wmSelectedViewUrl): ?>
        <a href="<?= e($wmSelectedViewUrl) ?>" target="_blank" class="text-primary flex-shrink-0">
          <span class="material-symbols-outlined text-[18px]">open_in_new</span>
        </a>
        <?php endif; ?>
      </div>
      <div class="text-[11px] text-on-surface-variant">ID #<?= (int)$selected['id'] ?> · <?= e(typeLabel($selectedType)) ?><?= ($selected['city']??'') ? ' · '.e($selected['city']) : '' ?></div>
      <div class="flex flex-wrap gap-1.5"><?= wm_render_social_badges($selected) ?></div>
    </div>

    <!-- Caption -->
    <?php if($wmCaption!==''): ?>
    <div class="glass-card rounded-xl p-4">
      <div class="flex justify-between items-center mb-2">
        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Caption</span>
        <button class="wm-cap-copy-btn text-[11px] px-3 py-1 rounded-full border border-primary/20 text-primary hover:bg-primary/10 transition-colors font-bold" data-cap="<?= e($wmCaption) ?>">Copy</button>
      </div>
      <p class="text-[13px] text-on-surface-variant leading-relaxed max-h-24 overflow-auto"><?= e(mb_substr($wmCaption,0,280)) ?><?= mb_strlen($wmCaption)>280?'…':'' ?></p>
    </div>
    <?php endif; ?>

    <!-- Upload image -->
    <form id="changeImgForm" method="post" enctype="multipart/form-data" class="glass-card rounded-xl p-4">
      <input type="hidden" name="existing_queue_id" value="<?= (int)$selected['id'] ?>">
      <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-3">Creative Image</div>
      <label class="flex items-center gap-3 cursor-pointer border border-dashed border-outline-variant/30 rounded-xl p-3 hover:border-primary/40 transition-colors">
        <span class="material-symbols-outlined text-primary text-[22px]">cloud_upload</span>
        <div>
          <div class="text-[13px] font-medium"><?= $wmSelectedMediaUrl ? 'Change Image' : 'Upload Image' ?></div>
          <div class="text-[11px] text-on-surface-variant" id="cif-name-<?= $selected['id'] ?>">JPG, PNG, MP4 accepted</div>
        </div>
        <input type="file" name="media" accept="image/*,video/mp4,video/quicktime,video/webm" class="hidden"
          onchange="document.getElementById('cif-name-<?= $selected['id'] ?>').textContent=this.files[0]?.name||'';document.getElementById('cif-btn-<?= $selected['id'] ?>').style.display='flex';">
      </label>
      <button id="cif-btn-<?= $selected['id'] ?>" type="submit" class="mt-2 w-full py-2 rounded-full bg-primary/10 border border-primary/20 text-primary text-[12px] font-bold hidden items-center justify-center gap-2">
        <span class="material-symbols-outlined text-[15px]">upload</span>Upload
      </button>
      <span id="cif-status-<?= $selected['id'] ?>" class="text-[11px] text-on-surface-variant mt-1 block"></span>
    </form>

    <!-- Action buttons -->
    <div class="space-y-2">
      <?php if(!$alreadyPublished): ?>
      <div class="grid grid-cols-2 gap-2">
        <form method="post" style="display:contents">
          <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
          <input type="hidden" name="action" value="approve">
          <button class="py-3 rounded-full bg-green-500 text-white text-[12px] font-bold hover:bg-green-600 transition-colors shadow-lg shadow-green-500/20">✓ Approve</button>
        </form>
        <form method="post" style="display:contents">
          <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
          <input type="hidden" name="action" value="reject">
          <button class="py-3 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 text-[12px] font-bold hover:bg-red-500/20 transition-colors">✕ Reject</button>
        </form>
      </div>
      <form method="post" style="display:contents">
        <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
        <input type="hidden" name="action" value="review">
        <button class="w-full py-2.5 rounded-full glass-card text-on-surface-variant text-[12px] font-bold hover:bg-surface-container-highest transition-colors">↩ Send to Review</button>
      </form>
      <?php else: ?>
      <div class="py-3 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[12px] font-bold text-center">✓ Published</div>
      <?php endif; ?>

      <?php if($canPublishFacebook): ?>
      <form method="post" onsubmit="return confirm('Publish to Facebook now?');">
        <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
        <input type="hidden" name="action" value="publish_facebook">
        <button class="w-full py-3 rounded-full text-white text-[12px] font-bold hover:opacity-90 transition-opacity" style="background:#1877F2;box-shadow:0 4px 20px rgba(24,119,242,0.3)">Publish to Facebook</button>
      </form>
      <?php endif; ?>

      <?php if($canPublishInstagram): ?>
      <form method="post" onsubmit="return confirm('Publish to Instagram now?');">
        <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
        <input type="hidden" name="action" value="publish_instagram">
        <button class="w-full py-3 rounded-full text-white text-[12px] font-bold instagram-gradient hover:opacity-90 transition-opacity" style="box-shadow:0 4px 20px rgba(193,53,132,0.3)">Publish to Instagram</button>
      </form>
      <?php endif; ?>

      <?php if(!$alreadyPublished&&$isSocialPost): ?>
      <form method="post" class="space-y-2">
        <input type="hidden" name="id" value="<?= (int)$selected['id'] ?>">
        <input type="hidden" name="action" value="published">
        <input type="url" name="published_url" value="<?= e($selected['published_url']??'') ?>" placeholder="Published URL (optional)"
          class="w-full bg-surface-container-lowest border border-outline-variant/20 rounded-xl px-4 py-2.5 text-[13px] text-on-surface focus:outline-none focus:border-primary/50">
        <button class="w-full py-2.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-[12px] font-bold hover:bg-primary/20 transition-colors">✓ Mark as Published</button>
      </form>
      <?php endif; ?>

      <?php if($selectedType==='blog'): ?>
      <button type="button" onclick="generateSocialPack(<?= (int)$selected['id'] ?>)"
        class="w-full py-3 rounded-full text-white text-[12px] font-bold hover:opacity-90 transition-opacity"
        style="background:linear-gradient(135deg,#7c3aed,#4d8eff);box-shadow:0 4px 20px rgba(124,58,237,0.3)">
        ⚡ Generate Social Pack
      </button>
      <?php endif; ?>
    </div>

    <!-- Creative Tools -->
    <details class="glass-card rounded-xl overflow-hidden">
      <summary class="px-4 py-3 cursor-pointer flex items-center justify-between hover:bg-surface-container-highest/30 transition-colors list-none select-none">
        <span class="text-[13px] font-bold flex items-center gap-3">
          <span class="material-symbols-outlined text-primary text-[18px]">build</span>Creative Tools
        </span>
        <span class="material-symbols-outlined text-on-surface-variant text-[18px]">expand_more</span>
      </summary>
      <div class="p-4 space-y-4 border-t border-outline-variant/10">
        <?php if($wmPrompt!==''): ?>
        <div>
          <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-2">Image Prompt</div>
          <textarea id="wmImagePromptBox" readonly class="w-full bg-surface-container-lowest border border-outline-variant/10 rounded-xl p-3 text-[12px] text-on-surface-variant min-h-[70px] resize-none"><?= e($wmPrompt) ?></textarea>
          <button onclick="navigator.clipboard.writeText(document.getElementById('wmImagePromptBox').value);this.textContent='Copied ✓';setTimeout(()=>this.textContent='Copy Prompt',1300);" class="mt-1.5 text-[11px] px-3 py-1.5 rounded-full border border-primary/20 text-primary font-bold hover:bg-primary/10">Copy Prompt</button>
        </div>
        <?php endif; ?>
        <div>
          <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-2">Content Copy</div>
          <textarea id="copyContent" readonly class="w-full bg-surface-container-lowest border border-outline-variant/10 rounded-xl p-3 text-[12px] text-on-surface-variant min-h-[90px] resize-none"><?= e($mainContent?:'') ?></textarea>
          <button onclick="copyText('copyContent')" class="mt-1.5 text-[11px] px-3 py-1.5 rounded-full border border-primary/20 text-primary font-bold hover:bg-primary/10">Copy Content</button>
        </div>
        <div>
          <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-2">Pomelli Brief</div>
          <textarea id="pomelliBrief" readonly class="w-full bg-surface-container-lowest border border-outline-variant/10 rounded-xl p-3 text-[12px] text-on-surface-variant min-h-[70px] resize-none"><?= e(pomelliBrief($selected)) ?></textarea>
          <button onclick="copyText('pomelliBrief')" class="mt-1.5 text-[11px] px-3 py-1.5 rounded-full border border-primary/20 text-primary font-bold hover:bg-primary/10">Copy Brief</button>
        </div>
      </div>
    </details>
    <?php endif; ?>
  </div>
</aside>

<script>
function copyText(id){const el=document.getElementById(id);if(!el)return;el.select();document.execCommand('copy');alert('Copied');}
async function generateSocialPack(blogId){
  if(!blogId){alert('Blog ID missing.');return;}
  if(!confirm('Generate social pack for this blog?'))return;
  try{
    const res=await fetch('/api/social/generate_social_pack.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:blogId,blog_id:blogId})});
    let data={};try{data=await res.json();}catch(e){}
    if(!res.ok){alert('Social pack generation failed. HTTP '+res.status);return;}
    alert('Social pack generation started!');
    setTimeout(()=>window.location.reload(),1500);
  }catch(err){alert('Error: '+err.message);}
}
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.wm-cap-copy-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      var text=this.getAttribute('data-cap')||'';
      navigator.clipboard.writeText(text).then(()=>{
        this.textContent='Copied ✓';this.style.color='#10b981';
        setTimeout(()=>{this.textContent='Copy';this.style.color='';},1500);
      }).catch(()=>{this.textContent='Failed';});
    });
  });
});
</script>
<?php if($selected): ?>
<script>
(function(){
  var form=document.getElementById('changeImgForm');
  var status=document.getElementById('cif-status-<?= $selected['id'] ?>');
  if(!form||!status)return;
  form.addEventListener('submit',async function(e){
    e.preventDefault();status.textContent='Uploading...';
    try{
      var fd=new FormData(form);
      var res=await fetch('/api/social/universal_media_upload.php',{method:'POST',body:fd,credentials:'same-origin'});
      var data=await res.json().catch(()=>({}));
      if(!res.ok||!data.ok)throw new Error(data.error||'Upload failed');
      status.textContent='Done! Reloading...';
      setTimeout(()=>window.location.reload(),600);
    }catch(err){status.style.color='#ef4444';status.textContent=err.message||'Failed';}
  });
})();
</script>
<?php endif; ?>
</body>
</html>
