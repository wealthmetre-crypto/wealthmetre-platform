<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

function wm_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function wm_db(): mysqli {
    foreach (["mysqli","conn","db","connection"] as $v) {
        if (isset($GLOBALS[$v]) && $GLOBALS[$v] instanceof mysqli) {
            $GLOBALS[$v]->set_charset("utf8mb4");
            return $GLOBALS[$v];
        }
    }

    if (defined("DB_HOST")) {
        $m = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($m->connect_error) {
            wm_json(["ok" => false, "error" => $m->connect_error], 500);
        }
        $m->set_charset("utf8mb4");
        return $m;
    }

    wm_json(["ok" => false, "error" => "DB connection not found"], 500);
}

function wm_cols(mysqli $db): array {
    $out = [];
    $res = $db->query("SHOW COLUMNS FROM content_queue");
    while ($r = $res->fetch_assoc()) {
        $out[$r["Field"]] = true;
    }
    return $out;
}

$db = wm_db();
$cols = wm_cols($db);

$select = [
    "id",
    "content_type",
    "status",
    "title",
    "slug",
    "created_at"
];

foreach ([
    "creative_image_url",
    "creative_media_url",
    "creative_media_type",
    "creative_status",
    "facebook_publish_status",
    "facebook_publish_error",
    "instagram_publish_status",
    "instagram_publish_error",
    "facebook_post_id",
    "instagram_media_id",
    "published_url",
    "published_at",
    "approved_by",
    "approved_at"
] as $c) {
    if (isset($cols[$c])) $select[] = $c;
}

$sql = "SELECT " . implode(",", array_map(fn($c) => "`$c`", $select)) . "
        FROM content_queue
        WHERE content_type IN ('facebook','instagram')
        ORDER BY id DESC
        LIMIT 120";

$res = $db->query($sql);
if (!$res) {
    wm_json(["ok" => false, "error" => $db->error], 500);
}

$rows = [];
while ($r = $res->fetch_assoc()) {
    $id = (int)$r["id"];

    $mediaUrl = trim((string)($r["creative_media_url"] ?? ""));
    if ($mediaUrl === "") $mediaUrl = trim((string)($r["creative_image_url"] ?? ""));

    if ($mediaUrl !== "" && str_starts_with($mediaUrl, "/")) {
        $mediaUrl = "https://wealthmetre.com" . $mediaUrl;
    }

    $publishedUrl = trim((string)($r["published_url"] ?? ""));

    if ($publishedUrl === "" && ($r["content_type"] ?? "") === "facebook" && !empty($r["facebook_post_id"])) {
        $publishedUrl = "https://www.facebook.com/" . $r["facebook_post_id"];
    }

    $rows[$id] = [
        "id" => $id,
        "content_type" => $r["content_type"] ?? "",
        "status" => $r["status"] ?? "",
        "title" => $r["title"] ?? "",
        "slug" => $r["slug"] ?? "",
        "created_at" => $r["created_at"] ?? "",
        "media_url" => $mediaUrl,
        "media_type" => $r["creative_media_type"] ?? "",
        "creative_status" => $r["creative_status"] ?? "",
        "facebook_publish_status" => $r["facebook_publish_status"] ?? "",
        "facebook_publish_error" => $r["facebook_publish_error"] ?? "",
        "instagram_publish_status" => $r["instagram_publish_status"] ?? "",
        "instagram_publish_error" => $r["instagram_publish_error"] ?? "",
        "facebook_post_id" => $r["facebook_post_id"] ?? "",
        "instagram_media_id" => $r["instagram_media_id"] ?? "",
        "published_url" => $publishedUrl,
        "published_at" => $r["published_at"] ?? "",
        "approved_by" => $r["approved_by"] ?? "",
        "approved_at" => $r["approved_at"] ?? "",
    ];
}

wm_json([
    "ok" => true,
    "rows" => $rows
]);
