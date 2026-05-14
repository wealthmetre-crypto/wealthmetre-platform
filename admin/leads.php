<?php
/**
 * WealthMetre — Admin Lead Dashboard v2
 * File: public_html/admin/leads.php
 */

require_once __DIR__ . '/../api/config.php';

$pdo = getDB();

// ── Filters ───────────────────────────────────────────────
$filterSource  = $_GET['source']  ?? 'all';
$filterProduct = $_GET['product'] ?? 'all';
$filterDate    = $_GET['date']    ?? 'all';
$filterAge     = $_GET['age']     ?? 'all';
$search        = trim($_GET['q']  ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 20;
$offset        = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($filterDate === 'today') {
    $where[] = 'DATE(lead_date) = CURDATE()';
} elseif ($filterDate === 'week') {
    $where[] = 'lead_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
} elseif ($filterDate === 'month') {
    $where[] = 'lead_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
}

if ($filterAge === 'hot') {
    $where[] = 'lead_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)';
} elseif ($filterAge === 'warm') {
    $where[] = 'lead_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)';
}

if ($filterSource !== 'all') {
    $where[] = 'source = ?';
    $params[] = $filterSource;
}

if ($filterProduct !== 'all') {
    $where[] = 'product LIKE ?';
    $params[] = '%' . $filterProduct . '%';
}

if ($search !== '') {
    $where[] = '(name LIKE ? OR mobile LIKE ? OR city LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Query ─────────────────────────────────────────────────
$leads      = [];
$totalCount = 0;
$dbError    = null;

try {
    // Count
    $countSQL = "SELECT COUNT(*) FROM all_leads $whereSQL";
    $countStmt = $pdo->prepare($countSQL);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();

    // Data with pagination
    $dataSQL  = "SELECT * FROM all_leads $whereSQL ORDER BY lead_date DESC LIMIT $perPage OFFSET $offset";
    $stmt     = $pdo->prepare($dataSQL);
    $stmt->execute($params);
    $leads    = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $dbError = $e->getMessage();
}

// ── Stats (always full, ignore filters) ──────────────────
$stats = ['total' => 0, 'today' => 0, 'diva' => 0, 'partner' => 0, 'week' => 0, 'hot' => 0];
try {
    $allStats = $pdo->query("SELECT source, lead_date FROM all_leads")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allStats as $r) {
        $stats['total']++;
        $ts = strtotime($r['lead_date'] ?? '');
        if ($ts && date('Y-m-d', $ts) === date('Y-m-d')) $stats['today']++;
        if ($ts && $ts >= strtotime('-7 days'))           $stats['week']++;
        if ($ts && $ts >= strtotime('-1 hour'))           $stats['hot']++;
        $src = strtolower($r['source'] ?? '');
        if (str_contains($src, 'diva'))    $stats['diva']++;
        if (str_contains($src, 'partner')) $stats['partner']++;
    }
} catch (\Throwable $e) {}

$totalPages = max(1, (int)ceil($totalCount / $perPage));

// ── Helpers ───────────────────────────────────────────────
function fmtAmount($v): string {
    if (!$v || (float)$v == 0) return '—';
    $v = (float)$v;
    if ($v >= 10000000) return '₹' . number_format($v/10000000, 1) . ' Cr';
    if ($v >= 100000)   return '₹' . number_format($v/100000, 1) . ' L';
    return '₹' . number_format($v);
}

function leadAge(?string $dt): array {
    if (!$dt) return ['—', 'age-old', 'Cold'];
    $diff = time() - strtotime($dt);
    if ($diff < 3600)   return [floor($diff/60) . 'm ago',  'age-hot',  '🔥 Hot'];
    if ($diff < 86400)  return [floor($diff/3600) . 'h ago', 'age-warm', '⚡ Warm'];
    if ($diff < 604800) return [floor($diff/86400) . 'd ago','age-cold', '❄️ Cold'];
    return [date('d M', strtotime($dt)), 'age-old', '❄️ Cold'];
}

function srcClass(string $s): string {
    $s = strtolower($s);
    if (str_contains($s, 'diva'))    return 'badge-diva';
    if (str_contains($s, 'partner')) return 'badge-partner';
    return 'badge-web';
}

function srcLabel(string $s): string {
    $s = strtolower($s);
    if (str_contains($s, 'diva'))    return 'Diva AI';
    if (str_contains($s, 'partner')) return 'Partner';
    return ucfirst($s ?: 'Web');
}

function cibilColor(int $c): string {
    if ($c >= 750) return '#10B981';
    if ($c >= 650) return '#F59E0B';
    if ($c > 0)    return '#EF4444';
    return '#6B7280';
}

$pageParams = $_GET;
function pageUrl(int $p, array $base): string {
    $base['page'] = $p;
    return '?' . http_build_query($base);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lead Command Centre — WealthMetre</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root {
    --bg:       #070B13;
    --s1:       #0D1220;
    --s2:       #111827;
    --s3:       #1C2537;
    --border:   #1E2D45;
    --border2:  #243350;
    --blue:     #3B82F6;
    --blue2:    #60A5FA;
    --green:    #10B981;
    --amber:    #F59E0B;
    --red:      #EF4444;
    --purple:   #8B5CF6;
    --teal:     #14B8A6;
    --text:     #E2E8F0;
    --muted:    #64748B;
    --muted2:   #94A3B8;
    --hot:      #FF6B35;
    --warm:     #F59E0B;
    --cold:     #64748B;
}

body {
    font-family: 'Outfit', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    font-size: 14px;
    -webkit-font-smoothing: antialiased;
}

/* ── TOPBAR ── */
.topbar {
    background: var(--s1);
    border-bottom: 1px solid var(--border);
    height: 56px;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 200;
    backdrop-filter: blur(12px);
}

.logo-mark {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo-icon {
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, var(--blue), var(--teal));
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 800;
    color: #fff;
}

.logo-text {
    font-weight: 700;
    font-size: 16px;
    letter-spacing: -0.3px;
}

.logo-text span { color: var(--blue2); }

.topbar-center {
    font-size: 12px;
    color: var(--muted);
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 0.5px;
}

.topbar-right { display: flex; align-items: center; gap: 10px; }

.live-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.2);
    color: var(--green);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.live-dot {
    width: 6px;
    height: 6px;
    background: var(--green);
    border-radius: 50%;
    animation: blink 2s infinite;
}

@keyframes blink {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:0.4; transform:scale(0.8); }
}

.btn-refresh {
    background: var(--s3);
    border: 1px solid var(--border2);
    color: var(--muted2);
    padding: 5px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-family: 'Outfit', sans-serif;
    font-weight: 500;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
}
.btn-refresh:hover { border-color: var(--blue); color: var(--blue2); }

/* ── LAYOUT ── */
.wrap { max-width: 1500px; margin: 0 auto; padding: 20px 24px; }

/* ── STATS GRID ── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.stat {
    background: var(--s1);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.2s, transform 0.15s;
}

.stat:hover { border-color: var(--border2); transform: translateY(-1px); }
.stat.active { border-color: var(--blue); }

.stat::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    opacity: 0.6;
}
.stat.s-total::after   { background: var(--blue); }
.stat.s-today::after   { background: var(--green); }
.stat.s-hot::after     { background: var(--hot); }
.stat.s-diva::after    { background: var(--purple); }
.stat.s-partner::after { background: var(--teal); }
.stat.s-week::after    { background: var(--amber); }

.stat-val {
    font-family: 'Outfit', sans-serif;
    font-size: 26px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 4px;
}
.stat.s-total .stat-val   { color: var(--blue2); }
.stat.s-today .stat-val   { color: var(--green); }
.stat.s-hot .stat-val     { color: var(--hot); }
.stat.s-diva .stat-val    { color: var(--purple); }
.stat.s-partner .stat-val { color: var(--teal); }
.stat.s-week .stat-val    { color: var(--amber); }

.stat-label {
    font-size: 10px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 600;
}

.stat-icon {
    position: absolute;
    right: 12px;
    top: 12px;
    font-size: 18px;
    opacity: 0.3;
}

/* ── TOOLBAR ── */
.toolbar {
    background: var(--s1);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 240px;
}

.search-box svg {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    pointer-events: none;
}

.search-input {
    width: 100%;
    background: var(--s2);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 8px 12px 8px 34px;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'Outfit', sans-serif;
    outline: none;
    transition: border-color 0.2s;
}
.search-input:focus { border-color: var(--blue); }
.search-input::placeholder { color: var(--muted); }

.filter-sel {
    background: var(--s2);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'Outfit', sans-serif;
    outline: none;
    cursor: pointer;
    transition: border-color 0.2s;
}
.filter-sel:focus { border-color: var(--blue); }

.btn-filter {
    background: var(--blue);
    border: none;
    color: #fff;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'Outfit', sans-serif;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}
.btn-filter:hover { opacity: 0.85; }

.btn-reset {
    color: var(--muted);
    font-size: 12px;
    text-decoration: none;
    padding: 8px 4px;
    font-family: 'Outfit', sans-serif;
}
.btn-reset:hover { color: var(--text); }

.toolbar-right { display: flex; gap: 8px; margin-left: auto; }

.btn-export {
    background: transparent;
    border: 1px solid var(--border2);
    color: var(--muted2);
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-family: 'Outfit', sans-serif;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
}
.btn-export:hover { border-color: var(--green); color: var(--green); }

/* ── TABLE WRAP ── */
.table-wrap {
    background: var(--s1);
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
}

.table-head-bar {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.table-title {
    font-weight: 700;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.count-chip {
    background: var(--s3);
    border: 1px solid var(--border2);
    color: var(--muted2);
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-family: 'JetBrains Mono', monospace;
}

/* ── TABLE ── */
.tbl-scroll { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; }

thead tr { background: var(--s2); }

th {
    padding: 10px 14px;
    text-align: left;
    font-size: 10px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    white-space: nowrap;
    border-bottom: 1px solid var(--border);
}

tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.12s;
    cursor: pointer;
}
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: rgba(59,130,246,0.04); }
tbody tr.expanded { background: rgba(59,130,246,0.06); }

td {
    padding: 11px 14px;
    font-size: 13px;
    vertical-align: middle;
    white-space: nowrap;
}

/* Age indicators */
.age-hot  { color: var(--hot);  font-weight: 600; }
.age-warm { color: var(--amber); font-weight: 600; }
.age-cold { color: var(--cold); }
.age-old  { color: var(--muted); }

/* Heat dot */
.heat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
    flex-shrink: 0;
}
.heat-hot  { background: var(--hot);  box-shadow: 0 0 6px var(--hot); animation: pulse-hot 1.5s infinite; }
.heat-warm { background: var(--amber); }
.heat-cold { background: var(--cold); }
.heat-old  { background: var(--muted); }

@keyframes pulse-hot {
    0%,100% { box-shadow: 0 0 4px var(--hot); }
    50%      { box-shadow: 0 0 10px var(--hot); }
}

/* Name cell */
.name-primary { font-weight: 600; color: var(--text); }
.name-city    { font-size: 11px; color: var(--muted); margin-top: 1px; }

/* Mobile */
.mobile-val {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: var(--blue2);
}

/* Product pill */
.prod-pill {
    background: rgba(59,130,246,0.1);
    color: #93C5FD;
    border: 1px solid rgba(59,130,246,0.2);
    padding: 2px 8px;
    border-radius: 5px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Source badges */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}
.badge-diva    { background: rgba(139,92,246,0.15); color: #C4B5FD; border: 1px solid rgba(139,92,246,0.3); }
.badge-partner { background: rgba(20,184,166,0.15); color: #5EEAD4; border: 1px solid rgba(20,184,166,0.3); }
.badge-web     { background: rgba(59,130,246,0.15);  color: #93C5FD; border: 1px solid rgba(59,130,246,0.3); }

/* Actions */
.actions {
    display: flex;
    gap: 5px;
    align-items: center;
}

.act-btn {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    border: 1px solid var(--border2);
    background: var(--s3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
    color: var(--muted2);
}
.act-btn:hover { transform: scale(1.1); }
.act-btn.call:hover  { border-color: var(--green);  background: rgba(16,185,129,0.12); }
.act-btn.wa:hover    { border-color: #22C55E;       background: rgba(34,197,94,0.12); }
.act-btn.expand:hover{ border-color: var(--blue);   background: rgba(59,130,246,0.12); }

/* Expanded row */
.expand-row td {
    padding: 0;
    border-bottom: 2px solid var(--blue);
}

.expand-content {
    padding: 16px 20px;
    background: rgba(59,130,246,0.03);
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

.detail-item label {
    display: block;
    font-size: 10px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 3px;
    font-weight: 600;
}

.detail-item span {
    font-size: 13px;
    color: var(--text);
    font-weight: 500;
}

.expand-actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 8px;
    padding-top: 8px;
    border-top: 1px solid var(--border);
    margin-top: 4px;
}

.ea-btn {
    padding: 7px 16px;
    border-radius: 7px;
    font-size: 12px;
    font-family: 'Outfit', sans-serif;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s;
}
.ea-call    { border-color: var(--green);  color: var(--green);  background: rgba(16,185,129,0.08); }
.ea-wa      { border-color: #22C55E;       color: #22C55E;       background: rgba(34,197,94,0.08); }
.ea-mark    { border-color: var(--blue);   color: var(--blue2);  background: rgba(59,130,246,0.08); }
.ea-btn:hover { opacity: 0.8; transform: translateY(-1px); }

/* Empty */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-icon { font-size: 40px; opacity: 0.3; margin-bottom: 14px; }
.empty-title { font-weight: 700; color: var(--muted); font-size: 15px; margin-bottom: 6px; }
.empty-sub { color: var(--muted); font-size: 13px; }

/* Pagination */
.pagination {
    padding: 14px 18px;
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.page-info { font-size: 12px; color: var(--muted); font-family: 'JetBrains Mono', monospace; }

.page-btns { display: flex; gap: 4px; }

.page-btn {
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    background: var(--s3);
    border: 1px solid var(--border2);
    color: var(--muted2);
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-family: 'JetBrains Mono', monospace;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.15s;
}
.page-btn:hover { border-color: var(--blue); color: var(--blue2); }
.page-btn.active { background: var(--blue); border-color: var(--blue); color: #fff; }
.page-btn:disabled, .page-btn.disabled { opacity: 0.3; pointer-events: none; }

/* Error */
.err-box {
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.3);
    color: #FCA5A5;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 13px;
}

/* Animations */
tbody tr { animation: fadeSlide 0.2s ease both; }
@keyframes fadeSlide {
    from { opacity: 0; transform: translateY(3px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-row { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 640px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); }
    .wrap { padding: 12px; }
    .topbar { padding: 0 12px; }
    .topbar-center { display: none; }
    .expand-content { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="logo-mark">
        <div class="logo-icon">W</div>
        <div class="logo-text">Wealth<span>Metre</span></div>
        <span style="color:var(--muted);font-size:12px;margin-left:4px;">/ Leads</span>
    </div>
    <div class="topbar-center">
        <?= date('D, d M Y • H:i') ?> IST
    </div>
    <div class="topbar-right">
        <div class="live-pill"><div class="live-dot"></div>LIVE</div>
        <button class="btn-refresh" onclick="location.reload()">↻ Refresh</button>
    </div>
</div>

<div class="wrap">

<?php if ($dbError): ?>
<div class="err-box">⚠️ DB Error: <?= htmlspecialchars($dbError) ?></div>
<?php endif; ?>

<!-- STATS -->
<div class="stats-row">
    <div class="stat s-total" onclick="window.location='/admin/leads.php'">
        <div class="stat-icon">📊</div>
        <div class="stat-val"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Leads</div>
    </div>
    <div class="stat s-today">
        <div class="stat-icon">📅</div>
        <div class="stat-val"><?= $stats['today'] ?></div>
        <div class="stat-label">Today</div>
    </div>
    <div class="stat s-hot">
        <div class="stat-icon">🔥</div>
        <div class="stat-val"><?= $stats['hot'] ?></div>
        <div class="stat-label">Hot (1hr)</div>
    </div>
    <div class="stat s-diva">
        <div class="stat-icon">🤖</div>
        <div class="stat-val"><?= $stats['diva'] ?></div>
        <div class="stat-label">Diva AI</div>
    </div>
    <div class="stat s-partner">
        <div class="stat-icon">🤝</div>
        <div class="stat-val"><?= $stats['partner'] ?></div>
        <div class="stat-label">Partner</div>
    </div>
    <div class="stat s-week">
        <div class="stat-icon">📆</div>
        <div class="stat-val"><?= $stats['week'] ?></div>
        <div class="stat-label">This Week</div>
    </div>
</div>

<!-- TOOLBAR -->
<form method="GET" action="" id="filterForm">
<div class="toolbar">
    <div class="search-box">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="text" name="q" id="searchInput" class="search-input"
            placeholder="Search name, mobile, city…"
            value="<?= htmlspecialchars($search) ?>"
            autocomplete="off">
    </div>

    <select name="source" class="filter-sel">
        <option value="all"            <?= $filterSource==='all'?'selected':'' ?>>All Sources</option>
        <option value="Diva AI"        <?= $filterSource==='Diva AI'?'selected':'' ?>>Diva AI</option>
        <option value="Partner Portal" <?= $filterSource==='Partner Portal'?'selected':'' ?>>Partner</option>
    </select>

    <select name="product" class="filter-sel">
        <option value="all"           <?= $filterProduct==='all'?'selected':'' ?>>All Products</option>
        <option value="LAP"           <?= $filterProduct==='LAP'?'selected':'' ?>>LAP</option>
        <option value="Home Loan"     <?= $filterProduct==='Home Loan'?'selected':'' ?>>Home Loan</option>
        <option value="Business Loan" <?= $filterProduct==='Business Loan'?'selected':'' ?>>Business Loan</option>
        <option value="Personal Loan" <?= $filterProduct==='Personal Loan'?'selected':'' ?>>Personal Loan</option>
        <option value="Car Loan"      <?= $filterProduct==='Car Loan'?'selected':'' ?>>Car Loan</option>
    </select>

    <select name="age" class="filter-sel">
        <option value="all"  <?= $filterAge==='all'?'selected':'' ?>>All Time</option>
        <option value="hot"  <?= $filterAge==='hot'?'selected':'' ?>>🔥 Hot (1hr)</option>
        <option value="warm" <?= $filterAge==='warm'?'selected':'' ?>>⚡ Warm (24hr)</option>
    </select>

    <select name="date" class="filter-sel">
        <option value="all"   <?= $filterDate==='all'?'selected':'' ?>>Any Date</option>
        <option value="today" <?= $filterDate==='today'?'selected':'' ?>>Today</option>
        <option value="week"  <?= $filterDate==='week'?'selected':'' ?>>This Week</option>
        <option value="month" <?= $filterDate==='month'?'selected':'' ?>>This Month</option>
    </select>

    <button type="submit" class="btn-filter">Filter</button>
    <a href="/admin/leads.php" class="btn-reset">✕ Reset</a>

    <div class="toolbar-right">
        <?php
        $exportParams = $_GET;
        $exportParams['export'] = 'csv';
        unset($exportParams['page']);
        ?>
        <a href="?<?= http_build_query($exportParams) ?>" class="btn-export">↓ CSV</a>
    </div>
</div>
</form>

<!-- CSV Export -->
<?php
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="wm-leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','Mobile','City','Product','Loan Amt','Income','CIBIL','Source','Date']);
    // Re-fetch all for export (no pagination)
    try {
        $expSQL  = "SELECT * FROM all_leads $whereSQL ORDER BY lead_date DESC";
        $expStmt = $pdo->prepare($expSQL);
        $expStmt->execute($params);
        foreach ($expStmt->fetchAll(PDO::FETCH_ASSOC) as $l) {
            fputcsv($out, [$l['id'],$l['name'],$l['mobile'],$l['city'],$l['product'],$l['loan_amount'],$l['monthly_income'],$l['cibil_score'],$l['source'],date('d M Y H:i',strtotime($l['lead_date']))]);
        }
    } catch (\Throwable $e) {}
    fclose($out);
    exit;
}
?>

<!-- TABLE -->
<div class="table-wrap">
    <div class="table-head-bar">
        <div class="table-title">
            All Leads
            <span class="count-chip"><?= $totalCount ?> results</span>
        </div>
        <div style="font-size:12px;color:var(--muted);">
            Page <?= $page ?> of <?= $totalPages ?>
        </div>
    </div>

    <div class="tbl-scroll">
        <?php if (empty($leads) && !$dbError): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <div class="empty-title">No leads found</div>
            <div class="empty-sub">Try adjusting filters or submit a test lead</div>
        </div>
        <?php else: ?>
        <table id="leadsTable">
            <thead>
                <tr>
                    <th style="width:32px"></th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Product</th>
                    <th>Loan Amt</th>
                    <th>Income</th>
                    <th>CIBIL</th>
                    <th>Source</th>
                    <th>Received</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($leads as $i => $lead):
                [$timeStr, $ageClass, $heatLabel] = leadAge($lead['lead_date'] ?? null);
                $heatDot   = str_replace('age-', 'heat-', $ageClass);
                $srcClass  = srcClass($lead['source'] ?? '');
                $srcLabel  = srcLabel($lead['source'] ?? '');
                $cibil     = (int)($lead['cibil_score'] ?? 0);
                $mobile    = htmlspecialchars($lead['mobile'] ?? '');
                $name      = htmlspecialchars($lead['name'] ?? '—');
                $city      = htmlspecialchars($lead['city'] ?? '');
                $product   = htmlspecialchars($lead['product'] ?? '');
                $leadId    = (int)($lead['id'] ?? 0);
                $wa        = 'https://wa.me/91' . preg_replace('/\D/', '', $mobile) . '?text=' . urlencode("Hi, I'm calling from WealthMetre regarding your loan enquiry.");
                $delay     = min($i * 25, 400);
            ?>
            <tr style="animation-delay:<?= $delay ?>ms" onclick="toggleExpand(<?= $leadId ?>)" id="row-<?= $leadId ?>">
                <td>
                    <span class="heat-dot <?= $heatDot ?>"></span>
                </td>
                <td>
                    <div class="name-primary"><?= $name ?></div>
                    <div class="name-city"><?= $city ?: '—' ?></div>
                </td>
                <td>
                    <span class="mobile-val"><?= $mobile ?: '—' ?></span>
                </td>
                <td>
                    <?php if ($product): ?>
                        <span class="prod-pill"><?= $product ?></span>
                    <?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?>
                </td>
                <td style="font-size:13px;font-weight:500;"><?= fmtAmount($lead['loan_amount'] ?? null) ?></td>
                <td style="color:var(--muted2);"><?= (int)($lead['monthly_income']??0) > 0 ? '₹'.number_format((int)$lead['monthly_income']) : '—' ?></td>
                <td>
                    <?php if ($cibil > 0): ?>
                        <span style="color:<?= cibilColor($cibil) ?>;font-weight:700;font-family:'JetBrains Mono',monospace;"><?= $cibil ?></span>
                    <?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?>
                </td>
                <td><span class="badge <?= $srcClass ?>"><?= $srcLabel ?></span></td>
                <td class="<?= $ageClass ?>" title="<?= htmlspecialchars($lead['lead_date'] ?? '') ?>"><?= $timeStr ?></td>
                <td onclick="event.stopPropagation()">
                    <div class="actions">
                        <?php if ($mobile): ?>
                        <a href="tel:<?= $mobile ?>" class="act-btn call" title="Call">📞</a>
                        <a href="<?= $wa ?>" target="_blank" class="act-btn wa" title="WhatsApp">💬</a>
                        <?php endif; ?>
                        <button class="act-btn expand" title="Details" onclick="toggleExpand(<?= $leadId ?>)">⤵</button>
                    </div>
                </td>
            </tr>
            <tr class="expand-row" id="expand-<?= $leadId ?>" style="display:none">
                <td colspan="10">
                    <div class="expand-content">
                        <div class="detail-item">
                            <label>Lead ID</label>
                            <span>#<?= $leadId ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Full Name</label>
                            <span><?= $name ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Mobile</label>
                            <span style="font-family:'JetBrains Mono',monospace;"><?= $mobile ?: '—' ?></span>
                        </div>
                        <div class="detail-item">
                            <label>City</label>
                            <span><?= $city ?: '—' ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Product</label>
                            <span><?= $product ?: '—' ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Loan Amount</label>
                            <span><?= fmtAmount($lead['loan_amount'] ?? null) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Property Value</label>
                            <span><?= fmtAmount($lead['property_value'] ?? null) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Monthly Income</label>
                            <span><?= (int)($lead['monthly_income']??0) > 0 ? '₹'.number_format((int)$lead['monthly_income']) : '—' ?></span>
                        </div>
                        <div class="detail-item">
                            <label>CIBIL Score</label>
                            <span style="color:<?= cibilColor($cibil) ?>;font-weight:700;"><?= $cibil ?: '—' ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Employment</label>
                            <span><?= htmlspecialchars($lead['employment_type'] ?? '—') ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Source</label>
                            <span><?= $srcLabel ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Received</label>
                            <span><?= $lead['lead_date'] ? date('d M Y, H:i', strtotime($lead['lead_date'])) : '—' ?></span>
                        </div>
                        <div class="expand-actions">
                            <?php if ($mobile): ?>
                            <a href="tel:<?= $mobile ?>" class="ea-btn ea-call">📞 Call Now</a>
                            <a href="<?= $wa ?>" target="_blank" class="ea-btn ea-wa">💬 WhatsApp</a>
                            <?php endif; ?>
                            <button class="ea-btn ea-mark" onclick="markContacted(<?= $leadId ?>, this)">✅ Mark Contacted</button>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <div class="page-info"><?= (($page-1)*$perPage)+1 ?>–<?= min($page*$perPage, $totalCount) ?> of <?= $totalCount ?></div>
        <div class="page-btns">
            <a href="<?= pageUrl($page-1, $pageParams) ?>" class="page-btn <?= $page<=1?'disabled':'' ?>">←</a>
            <?php
            $start = max(1, $page-2);
            $end   = min($totalPages, $page+2);
            for ($p=$start; $p<=$end; $p++):
            ?>
            <a href="<?= pageUrl($p, $pageParams) ?>" class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="<?= pageUrl($page+1, $pageParams) ?>" class="page-btn <?= $page>=$totalPages?'disabled':'' ?>">→</a>
        </div>
    </div>
    <?php endif; ?>
</div>

</div><!-- /wrap -->

<script>
// ── Expand/collapse lead detail ──────────────────────────
function toggleExpand(id) {
    const row    = document.getElementById('expand-' + id);
    const parent = document.getElementById('row-' + id);
    const isOpen = row.style.display !== 'none';
    // Close all others
    document.querySelectorAll('.expand-row').forEach(r => r.style.display = 'none');
    document.querySelectorAll('tbody tr:not(.expand-row)').forEach(r => r.classList.remove('expanded'));
    if (!isOpen) {
        row.style.display = 'table-row';
        parent.classList.add('expanded');
    }
}

// ── Mark as contacted ────────────────────────────────────
function markContacted(id, btn) {
    btn.textContent = '✓ Contacted';
    btn.style.borderColor = '#10B981';
    btn.style.color = '#10B981';
    btn.disabled = true;
    // You can add an AJAX call here to update lead status in DB
}

// ── Live search (debounced) ───────────────────────────────
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 600);
});

// ── Auto refresh every 90 seconds ────────────────────────
setTimeout(() => location.reload(), 90000);

// ── Keyboard shortcuts ────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === 'r' && !e.ctrlKey && !e.metaKey && document.activeElement.tagName !== 'INPUT') location.reload();
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
});
</script>

</body>
</html>
