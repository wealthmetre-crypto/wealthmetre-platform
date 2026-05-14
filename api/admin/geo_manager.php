<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Key');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/auth.php';
requireAuth();
$pdo    = getDB();
$body   = getJsonBody();
$action = $_GET['action'] ?? $body['action'] ?? '';
match($action) {
    'list'            => listDistricts($pdo),
    'update_km'       => updateKm($pdo, $body),
    'add_city'        => addCity($pdo, $body),
    'delete_city'     => deleteCity($pdo, $body),
    'unknown_list'    => listUnknown($pdo),
    'resolve_unknown' => resolveUnknown($pdo, $body),
    'dismiss_unknown' => dismissUnknown($pdo, $body),
    'stats'           => getStats($pdo),
    default           => jsonError('Invalid action'),
};
function listDistricts(PDO $pdo): void {
    $rows = $pdo->query("SELECT id,district,tehsil,km_from_jaipur,is_district_hq,verified,notes FROM rajasthan_geo ORDER BY district ASC,is_district_hq DESC,tehsil ASC")->fetchAll(PDO::FETCH_ASSOC);
    $grouped = [];
    foreach ($rows as $r) {
        $d = $r['district'];
        if (!isset($grouped[$d])) $grouped[$d] = ['district'=>$d,'hq'=>null,'tehsils'=>[]];
        if ($r['is_district_hq']) $grouped[$d]['hq'] = $r;
        else $grouped[$d]['tehsils'][] = $r;
    }
    jsonResponse(['districts'=>array_values($grouped),'total_districts'=>count($grouped),
        'total_tehsils'=>count(array_filter($rows,fn($r)=>!$r['is_district_hq'])),
        'filled'=>count(array_filter($rows,fn($r)=>$r['km_from_jaipur']!==null)),
        'unfilled'=>count(array_filter($rows,fn($r)=>$r['km_from_jaipur']===null&&!$r['is_district_hq'])),
    ]);
}
function updateKm(PDO $pdo, array $b): void {
    $id = intval($b['id']??0); $km = isset($b['km'])&&$b['km']!==''?intval($b['km']):null;
    if (!$id) jsonError('id required');
    $pdo->prepare("UPDATE rajasthan_geo SET km_from_jaipur=?,verified=1,updated_at=NOW() WHERE id=?")->execute([$km,$id]);
    jsonResponse(['message'=>'Updated','id'=>$id,'km'=>$km]);
}
function addCity(PDO $pdo, array $b): void {
    $district = trim($b['district']??''); $tehsil = trim($b['tehsil']??'');
    $km = isset($b['km'])&&$b['km']!==''?intval($b['km']):null;
    if (!$district) jsonError('district required');
    $exists = $pdo->prepare("SELECT id FROM rajasthan_geo WHERE district=? AND is_district_hq=1");
    $exists->execute([$district]);
    if (!$exists->fetch()) {
        $pdo->prepare("INSERT INTO rajasthan_geo (district,tehsil,km_from_jaipur,is_district_hq,verified) VALUES (?,NULL,?,1,?)")->execute([$district,$km,$km?1:0]);
    }
    if ($tehsil) {
        $dup = $pdo->prepare("SELECT id FROM rajasthan_geo WHERE district=? AND tehsil=?");
        $dup->execute([$district,$tehsil]);
        if ($dup->fetch()) jsonError("$tehsil already exists in $district");
        $pdo->prepare("INSERT INTO rajasthan_geo (district,tehsil,km_from_jaipur,is_district_hq,verified) VALUES (?,?,?,0,?)")->execute([$district,$tehsil,$km,$km?1:0]);
        jsonResponse(['message'=>"Added $tehsil to $district",'id'=>$pdo->lastInsertId()]);
    }
    jsonResponse(['message'=>"District $district added"]);
}
function deleteCity(PDO $pdo, array $b): void {
    $id = intval($b['id']??0); if (!$id) jsonError('id required');
    $row = $pdo->prepare("SELECT is_district_hq FROM rajasthan_geo WHERE id=?"); $row->execute([$id]);
    $r = $row->fetch(PDO::FETCH_ASSOC);
    if (!$r) jsonError('Not found',404);
    if ($r['is_district_hq']) jsonError('Cannot delete district HQ');
    $pdo->prepare("DELETE FROM rajasthan_geo WHERE id=?")->execute([$id]);
    jsonResponse(['message'=>'Deleted']);
}
function listUnknown(PDO $pdo): void {
    $rows = $pdo->query("SELECT id,city_name,detected_district,seen_count,last_seen FROM geo_unknown_cities WHERE resolved=0 ORDER BY seen_count DESC,last_seen DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    $districts = $pdo->query("SELECT DISTINCT district FROM rajasthan_geo WHERE is_district_hq=1 ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);
    jsonResponse(['unknown'=>$rows,'districts'=>$districts]);
}
function resolveUnknown(PDO $pdo, array $b): void {
    $unknownId=intval($b['unknown_id']??0); $district=trim($b['district']??'');
    $tehsil=trim($b['tehsil']??''); $km=isset($b['km'])&&$b['km']!==''?intval($b['km']):null;
    if (!$unknownId||!$district) jsonError('unknown_id and district required');
    $cityName=$tehsil?:($b['city_name']??'');
    if ($cityName) {
        $dup=$pdo->prepare("SELECT id FROM rajasthan_geo WHERE district=? AND tehsil=?"); $dup->execute([$district,$cityName]);
        if (!$dup->fetch()) $pdo->prepare("INSERT INTO rajasthan_geo (district,tehsil,km_from_jaipur,is_district_hq,verified) VALUES (?,?,?,0,?)")->execute([$district,$cityName,$km,$km?1:0]);
        elseif ($km) $pdo->prepare("UPDATE rajasthan_geo SET km_from_jaipur=?,verified=1 WHERE district=? AND tehsil=?")->execute([$km,$district,$cityName]);
    }
    $pdo->prepare("UPDATE geo_unknown_cities SET resolved=1,resolved_km=? WHERE id=?")->execute([$km,$unknownId]);
    jsonResponse(['message'=>"Resolved: $cityName added to $district"]);
}
function dismissUnknown(PDO $pdo, array $b): void {
    $id=intval($b['id']??0); if (!$id) jsonError('id required');
    $pdo->prepare("UPDATE geo_unknown_cities SET resolved=1 WHERE id=?")->execute([$id]);
    jsonResponse(['message'=>'Dismissed']);
}
function getStats(PDO $pdo): void {
    $total=(int)$pdo->query("SELECT COUNT(*) FROM rajasthan_geo WHERE tehsil IS NOT NULL")->fetchColumn();
    $filled=(int)$pdo->query("SELECT COUNT(*) FROM rajasthan_geo WHERE tehsil IS NOT NULL AND km_from_jaipur IS NOT NULL")->fetchColumn();
    $districts=(int)$pdo->query("SELECT COUNT(*) FROM rajasthan_geo WHERE is_district_hq=1")->fetchColumn();
    $unknown=(int)$pdo->query("SELECT COUNT(*) FROM geo_unknown_cities WHERE resolved=0")->fetchColumn();
    jsonResponse(['districts'=>$districts,'total_tehsils'=>$total,'filled_tehsils'=>$filled,
        'unfilled'=>$total-$filled,'fill_pct'=>$total>0?round(($filled/$total)*100):0,'unknown_queue'=>$unknown]);
}
