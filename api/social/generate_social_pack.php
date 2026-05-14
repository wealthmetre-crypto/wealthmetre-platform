<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/social_creative_helper.php';

function get_pdo_connection() {
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return $GLOBALS['pdo'];

    if (function_exists('getDBConnection')) {
        $db = getDBConnection();
        if ($db instanceof PDO) return $db;
    }

    if (function_exists('getDbConnection')) {
        $db = getDbConnection();
        if ($db instanceof PDO) return $db;
    }

    if (function_exists('getConnection')) {
        $db = getConnection();
        if ($db instanceof PDO) return $db;
    }

    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        return new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }

    throw new Exception('No supported PDO DB connection found.');
}

function input_data() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    if (is_array($json)) return $json;
    if (!empty($_POST)) return $_POST;

    parse_str($raw, $parsed);
    return is_array($parsed) ? $parsed : [];
}

function clean_text($s, $limit = 500) {
    $s = trim((string)$s);
    $s = preg_replace('/\s+/', ' ', $s);
    if (mb_strlen($s) > $limit) {
        $s = mb_substr($s, 0, $limit - 3) . '...';
    }
    return $s;
}

function make_hashtags($city, $loanProduct) {
    $tags = ['#WealthMetre', '#LoanAdvisor'];
    if ($city) $tags[] = '#' . preg_replace('/[^A-Za-z0-9]/', '', $city);
    if ($loanProduct) $tags[] = '#' . preg_replace('/[^A-Za-z0-9]/', '', $loanProduct);
    $tags[] = '#Loan';
    return implode(' ', array_unique($tags));
}

try {
    $pdo = get_pdo_connection();
    $input = input_data();

    $id = (int)($input['id'] ?? $input['blog_id'] ?? $input['content_id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'id/blog_id/content_id is required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM content_queue WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $source = $stmt->fetch();

    if (!$source) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Source content_queue row not found']);
        exit;
    }

    $topic = clean_text($source['topic'] ?? $source['title'] ?? 'WealthMetre Loan Update', 255);
    $targetKeyword = clean_text($source['target_keyword'] ?? '', 255);
    $city = clean_text($source['city'] ?? 'Jaipur', 100);
    $loanProduct = clean_text($source['loan_product'] ?? 'Loan', 100);
    $title = clean_text($source['title'] ?? $topic, 255);

    $long = trim((string)($source['content_long'] ?? ''));
    $short = trim((string)($source['content_short'] ?? ''));

    $baseText = $short ?: $long;
    if ($baseText === '') {
        $baseText = "Looking for {$loanProduct} options in {$city}? WealthMetre helps borrowers compare suitable banks and NBFCs based on profile, income, property type and lender policy.";
    }

    $summary = clean_text(strip_tags($baseText), 520);
    $hashtags = make_hashtags($city, $loanProduct);

    $cta = clean_text($source['cta'] ?? 'Use Diva AI Loan Advisor on wealthmetre.com to check suitable loan options.', 255);
    if ($cta === '') {
        $cta = 'Use Diva AI Loan Advisor on wealthmetre.com to check suitable loan options.';
    }

    $imagePrompt = "Create a clean professional finance social media creative for WealthMetre about {$loanProduct} in {$city}. Theme: trust, loan advisory, modern fintech, Jaipur/Rajasthan audience. No fake bank logos.";

    $igTitle = clean_text($title, 180);
    $fbTitle = clean_text($title, 180);

    $igContent = clean_text(
        "🏦 " . $title . "\n\n" .
        $summary . "\n\n" .
        "Confused about which bank/NBFC may fit your profile? Diva AI Loan Advisor can help you compare options based on property, income, CIBIL and lender policy.",
        900
    );

    $fbContent = clean_text(
        $title . "\n\n" .
        $summary . "\n\n" .
        "At WealthMetre, we help borrowers compare suitable loan options from 140+ banks and NBFCs. Final eligibility depends on lender policy, income, credit profile, property type and documentation.",
        1200
    );

    $creativeUrl = trim((string)($source['creative_image_url'] ?? ''));
    if ($creativeUrl === '') {
        $creativeUrl = null;
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare("
        INSERT INTO content_queue
        (content_type, topic, target_keyword, city, loan_product, title, slug,
         content_short, hashtags, cta, image_prompt, creative_image_url,
         status, created_at, updated_at)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'needs_review', NOW(), NOW())
    ");

    $slugBase = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $topic), '-'));
    if ($slugBase === '') $slugBase = 'wealthmetre-social-post';

    $insert->execute([
        'instagram',
        $topic,
        $targetKeyword,
        $city,
        $loanProduct,
        $igTitle,
        $slugBase . '-instagram-' . time(),
        $igContent,
        $hashtags,
        $cta,
        $imagePrompt,
        $creativeUrl
    ]);
    $instagramId = (int)$pdo->lastInsertId();

    try {
        $generatedCreativeUrl = wm_create_social_creative($instagramId, $igTitle, $topic, $loanProduct, $city);
        $updCreative = $pdo->prepare("UPDATE content_queue SET creative_image_url=?, updated_at=NOW() WHERE id=? LIMIT 1");
        $updCreative->execute([$generatedCreativeUrl, $instagramId]);
        $creativeUrl = $generatedCreativeUrl;
    } catch (Throwable $creativeError) {
        $creativeUrl = 'https://wealthmetre.com/images/logo.png';
        $updCreative = $pdo->prepare("UPDATE content_queue SET creative_image_url=?, instagram_publish_error=?, updated_at=NOW() WHERE id=? LIMIT 1");
        $updCreative->execute([$creativeUrl, 'Creative generation failed: ' . $creativeError->getMessage(), $instagramId]);
    }

    $insert->execute([
        'facebook',
        $topic,
        $targetKeyword,
        $city,
        $loanProduct,
        $fbTitle,
        $slugBase . '-facebook-' . time(),
        $fbContent,
        $hashtags,
        $cta,
        $imagePrompt,
        null
    ]);
    $facebookId = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Social pack generated successfully',
        'source_id' => $id,
        'created' => [
            'instagram_id' => $instagramId,
            'facebook_id' => $facebookId
        ]
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
