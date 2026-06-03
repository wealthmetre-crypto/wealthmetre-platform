<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/auth.php';
requireAuth();

$body_tmp = getJsonBody(); $action = $_GET['action'] ?? ($body_tmp['action'] ?? 'summary');
$pdo = getDB();

function safeCount(PDO $pdo, string $sql, array $params=[]): int {
    try { $s=$pdo->prepare($sql); $s->execute($params); return (int)$s->fetchColumn(); }
    catch(\Throwable $e){ return 0; }
}
function safeQuery(PDO $pdo, string $sql, array $params=[]): array {
    try { $s=$pdo->prepare($sql); $s->execute($params); return $s->fetchAll(PDO::FETCH_ASSOC); }
    catch(\Throwable $e){ return []; }
}

if($action==='summary'){
    $data = [
        // Partners
        'total_partners'       => safeCount($pdo,"SELECT COUNT(*) FROM partners"),
        'active_partners'      => safeCount($pdo,"SELECT COUNT(*) FROM partners WHERE status='active'"),
        // Telecallers
        'total_telecallers'    => safeCount($pdo,"SELECT COUNT(*) FROM telecallers"),
        'active_telecallers'   => safeCount($pdo,"SELECT COUNT(*) FROM telecallers WHERE status='active'"),
        // Leads
        'total_leads'          => safeCount($pdo,"SELECT COUNT(*) FROM calling_leads"),
        'leads_today'          => safeCount($pdo,"SELECT COUNT(*) FROM calling_leads WHERE DATE(created_at)=CURDATE()"),
        'interested_leads'     => safeCount($pdo,"SELECT COUNT(*) FROM calling_leads WHERE current_status='Interested'"),
        'calls_today'          => safeCount($pdo,"SELECT COUNT(*) FROM calling_leads WHERE DATE(updated_at)=CURDATE() AND current_status!='New'"),
        // Follow-ups
        'followups_today'      => safeCount($pdo,"SELECT COUNT(*) FROM calling_leads WHERE DATE(followup_at)=CURDATE() AND current_status NOT IN ('Interested','Not Interested','Wrong Number')"),
        'followups_overdue'    => safeCount($pdo,"SELECT COUNT(*) FROM calling_leads WHERE followup_at < CURDATE() AND followup_at IS NOT NULL AND current_status NOT IN ('Interested','Not Interested','Wrong Number')"),
        // Lenders
        'active_lenders'       => safeCount($pdo,"SELECT COUNT(*) FROM lenders_products WHERE status='active'"),
        'total_lenders'        => safeCount($pdo,"SELECT COUNT(*) FROM lenders_products"),
        // Diva
        'diva_today'           => safeCount($pdo,"SELECT COUNT(*) FROM diva_leads_v2 WHERE DATE(created_at)=CURDATE()"),
        'diva_total'           => safeCount($pdo,"SELECT COUNT(*) FROM diva_leads_v2"),
        // Batches
        'total_batches'        => safeCount($pdo,"SELECT COUNT(*) FROM calling_batches"),
        'active_batches'       => safeCount($pdo,"SELECT COUNT(*) FROM calling_batches WHERE status='active'"),
    ];
    echo json_encode(['success'=>true,'data'=>$data]); exit;
}

if($action==='charts'){
    // Leads by partner (last 30 days)
    $byPartner = safeQuery($pdo,
        "SELECT p.partner_name as partner, COUNT(cl.id) as count
         FROM partners p LEFT JOIN calling_batches cb ON cb.partner_id=p.id
         LEFT JOIN calling_leads cl ON cl.batch_id=cb.id AND cl.created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)
         GROUP BY p.id,p.partner_name ORDER BY count DESC LIMIT 10");

    // Call status funnel
    $funnel = safeQuery($pdo,
        "SELECT current_status as status, COUNT(*) as count FROM calling_leads GROUP BY current_status ORDER BY count DESC");

    // Daily calls last 14 days
    $dailyCalls = safeQuery($pdo,
        "SELECT DATE(updated_at) as date, COUNT(*) as count
         FROM calling_leads WHERE updated_at >= DATE_SUB(NOW(),INTERVAL 14 DAY)
         AND current_status != 'New' GROUP BY DATE(updated_at) ORDER BY date ASC");

    // Lender type distribution
    $lenderTypes = safeQuery($pdo,
        "SELECT lender_type, COUNT(*) as count FROM lenders_products WHERE status='active' GROUP BY lender_type");

    // CIBIL distribution from diva leads
    $cibilDist = safeQuery($pdo,
        "SELECT
           CASE WHEN CAST(cibil_score AS UNSIGNED) >= 750 THEN '750+'
                WHEN CAST(cibil_score AS UNSIGNED) >= 700 THEN '700-749'
                WHEN CAST(cibil_score AS UNSIGNED) >= 650 THEN '650-699'
                WHEN CAST(cibil_score AS UNSIGNED) >= 600 THEN '600-649'
                ELSE 'Below 600' END as range,
           COUNT(*) as count
         FROM diva_leads_v2 WHERE cibil_score IS NOT NULL AND cibil_score != ''
         GROUP BY range ORDER BY count DESC");

    echo json_encode(['success'=>true,'data'=>compact('byPartner','funnel','dailyCalls','lenderTypes','cibilDist')]); exit;
}

if($action==='activity'){
    $feed = [];
    // Recent batches
    $batches = safeQuery($pdo,
        "SELECT cb.name, cb.created_at, p.name as partner FROM calling_batches cb
         LEFT JOIN partners p ON p.id=cb.partner_id ORDER BY cb.created_at DESC LIMIT 5");
    foreach($batches as $b)
        $feed[] = ['icon'=>'fa-file-upload','color'=>'blue','text'=>"<b>{$b['partner']}</b> uploaded batch <b>{$b['name']}</b>",'time'=>$b['created_at']];

    // Recent diva sessions
    $diva = safeQuery($pdo,"SELECT id,created_at FROM diva_conversations ORDER BY created_at DESC LIMIT 3");
    foreach($diva as $d)
        $feed[] = ['icon'=>'fa-robot','color'=>'purple','text'=>"Diva session <b>#{$d['id']}</b> completed",'time'=>$d['created_at']];

    // Recent lender updates
    $lenders = safeQuery($pdo,"SELECT lender_name,updated_at FROM lenders_products ORDER BY updated_at DESC LIMIT 3");
    foreach($lenders as $l)
        $feed[] = ['icon'=>'fa-building-columns','color'=>'green','text'=>"Lender <b>{$l['lender_name']}</b> updated",'time'=>$l['updated_at']];

    // Sort by time
    usort($feed,fn($a,$b)=>strtotime($b['time'])-strtotime($a['time']));
    $feed = array_slice($feed,0,15);

    echo json_encode(['success'=>true,'data'=>$feed]); exit;
}

if($action==='partners_list'){
    $page  = max(1,(int)($_GET['page']??1));
    $limit = 25;
    $offset= ($page-1)*$limit;
    $search= '%'.trim($_GET['search']??'').'%';

    $sVal = str_replace('%','',$search)===''?'%':$search;
    $total = safeCount($pdo,"SELECT COUNT(*) FROM partners WHERE partner_name LIKE ? OR email LIKE ? OR mobile LIKE ? OR phone LIKE ?",[$sVal,$sVal,$sVal,$sVal]);

    $rows = safeQuery($pdo,
        "SELECT p.id, p.partner_name, p.branch_city, p.email,
           COALESCE(p.mobile, p.phone) as display_mobile,
           p.status, p.created_at, p.last_login_at, p.company_name,
           (SELECT COUNT(*) FROM telecallers t WHERE t.partner_id=p.id) as telecaller_count,
           (SELECT COUNT(*) FROM calling_batches cb1 WHERE cb1.partner_id=p.id) as batch_count,
           (SELECT COUNT(*) FROM calling_leads cl1 JOIN calling_batches cb2 ON cl1.batch_id=cb2.id WHERE cb2.partner_id=p.id) as lead_count,
           (SELECT COUNT(*) FROM calling_leads cl2 JOIN calling_batches cb3 ON cl2.batch_id=cb3.id WHERE cb3.partner_id=p.id AND cl2.current_status='Interested') as interested_count
         FROM partners p WHERE p.partner_name LIKE ? OR p.email LIKE ? OR p.mobile LIKE ? OR p.phone LIKE ?
         ORDER BY p.created_at DESC LIMIT {$limit} OFFSET {$offset}",
        [$sVal,$sVal,$sVal,$sVal]);

    echo json_encode(['success'=>true,'data'=>$rows,'pagination'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>ceil($total/$limit)]]); exit;
}

if($action==='telecallers_list'){
    $page  = max(1,(int)($_GET['page']??1));
    $limit = 25;
    $offset= ($page-1)*$limit;
    $rows = safeQuery($pdo,
        "SELECT t.*, p.partner_name,
           (SELECT COUNT(*) FROM calling_leads cl WHERE cl.assigned_telecaller_id=t.id) as assigned_leads,
           (SELECT COUNT(*) FROM calling_leads cl WHERE cl.assigned_telecaller_id=t.id AND cl.current_status='Interested') as interested
         FROM telecallers t LEFT JOIN partners p ON p.id=t.partner_id
         ORDER BY t.created_at DESC LIMIT {$limit} OFFSET {$offset}");
    $total = safeCount($pdo,"SELECT COUNT(*) FROM telecallers");
    echo json_encode(['success'=>true,'data'=>$rows,'pagination'=>['page'=>1,'per_page'=>$limit,'total'=>$total,'total_pages'=>ceil($total/$limit)]]); exit;
}

if($action==='partner_leads_list'){
    $page   = max(1,(int)($_GET['page']??1));
    $limit  = 25;
    $offset = ($page-1)*$limit;
    $status  = $_GET['status']??'';
    $partner = $_GET['partner']??'';
    $search  = '%'.trim($_GET['search']??'').'%';
    $where  = ["1=1"];
    $params = [];
    if($status)  { $where[] = "l.status=?";     $params[] = $status; }
    if($partner) { $where[] = "l.partner_id=?"; $params[] = (int)$partner; }
    if(trim($_GET['search']??'')) {
        $where[] = "(l.customer_name LIKE ? OR l.mobile LIKE ? OR l.city LIKE ?)";
        $params = array_merge($params, [$search, $search, $search]);
    }
    $wSql = implode(' AND ', $where);
    $rows = safeQuery($pdo,
        "SELECT l.id, l.customer_name, COALESCE(l.customer_mobile,l.mobile) as mobile,
                l.city, l.loan_type, l.loan_amount, l.cibil, l.status,
                l.source, l.created_at, l.notes,
                l.disbursal_amount, l.disbursal_date, l.disbursed_lender,
                p.partner_name, p.first_name, p.last_name, p.mobile as partner_mobile
         FROM leads l
         LEFT JOIN partners p ON p.id = l.partner_id
         WHERE {$wSql}
         ORDER BY l.created_at DESC
         LIMIT {$limit} OFFSET {$offset}",
        $params);
    $total = safeCount($pdo,
        "SELECT COUNT(*) FROM leads l LEFT JOIN partners p ON p.id=l.partner_id WHERE {$wSql}",
        $params);
    echo json_encode(['success'=>true,'data'=>$rows,
        'pagination'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,
                       'total_pages'=>ceil($total/$limit)]]); exit;
}

if($action==='leads_list'){
    $page       = max(1,(int)($_GET['page']??1));
    $limit      = 25;
    $offset     = ($page-1)*$limit;
    $status     = $_GET['status']??'';
    $partner    = $_GET['partner']??'';
    $telecaller = intSafe($_GET['telecaller_id']??0);

    $whereCL = ["1=1"]; $paramsCL = [];
    $whereL  = ["1=1"]; $paramsL  = [];

    if($status){
        $whereCL[] = "cl.current_status=?"; $paramsCL[] = $status;
        $whereL[]  = "l.status=?";          $paramsL[]  = $status;
    }
    if($partner){
        $whereCL[] = "cb.partner_id=?"; $paramsCL[] = $partner;
        $whereL[]  = "l.partner_id=?";  $paramsL[]  = $partner;
    }
    if($telecaller){
        $whereCL[] = "cl.assigned_telecaller_id=?"; $paramsCL[] = $telecaller;
    }

    $wSqlCL = implode(' AND ',$whereCL);
    $wSqlL  = implode(' AND ',$whereL);
    $params = array_merge($paramsCL, $paramsL);

    $unionSql = "
        SELECT cl.id, cl.customer_name AS name, cl.mobile, cl.city, cl.loan_type,
               cl.loan_amount, cl.current_status AS status,
               cl.followup_at AS followup_date, cl.call_notes AS notes,
               cl.created_at, cl.cibil, cl.source,
               cl.assigned_telecaller_id,
               cl.disbursal_amount, cl.disbursal_date, cl.disbursed_lender,
               p.partner_name, t.name AS telecaller_name, 'calling' AS lead_source
        FROM calling_leads cl
        LEFT JOIN calling_batches cb ON cb.id = cl.batch_id
        LEFT JOIN partners p         ON p.id  = cb.partner_id
        LEFT JOIN telecallers t      ON t.id  = cl.assigned_telecaller_id
        WHERE {$wSqlCL}

        UNION ALL

        SELECT l.id, l.customer_name AS name,
               COALESCE(l.customer_mobile, l.mobile) AS mobile,
               l.city, l.loan_type, l.loan_amount,
               l.status AS status,
               NULL AS followup_date, l.notes AS notes,
               l.created_at, l.cibil, l.source,
               NULL AS assigned_telecaller_id,
               l.disbursal_amount, l.disbursal_date, l.disbursed_lender,
               p.partner_name, NULL AS telecaller_name, 'direct' AS lead_source
        FROM leads l
        LEFT JOIN partners p ON p.id = l.partner_id
        WHERE {$wSqlL}
    ";

    $rows  = safeQuery($pdo,
        "SELECT * FROM ({$unionSql}) AS combined ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}",
        $params);
    $total = safeCount($pdo,
        "SELECT COUNT(*) FROM ({$unionSql}) AS combined",
        $params);

    echo json_encode(['success'=>true,'data'=>$rows,
        'pagination'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,
                       'total_pages'=>ceil($total/$limit)]]); exit;
}


if($action==='lenders_list'){
    $rows = safeQuery($pdo,
        "SELECT id,lender_name,lender_type,product_type,loan_category,roi_min,roi_max,max_ltv,min_cibil,
                city_coverage,city,status,updated_at,sales_manager_name,sales_manager_mobile
         FROM lenders_products ORDER BY lender_name ASC");
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if($action==='followups_list'){
    $type = $_GET['type']??'today'; // today, overdue, upcoming
    $where = match($type){
        'today'    => "DATE(cl.followup_at)=CURDATE()",
        'overdue'  => "cl.followup_at < CURDATE() AND cl.followup_at IS NOT NULL",
        'upcoming' => "cl.followup_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)",
        default    => "DATE(cl.followup_at)=CURDATE()"
    };
    $rows = safeQuery($pdo,
        "SELECT cl.id,cl.customer_name as name,cl.mobile,cl.city,cl.loan_type,
                cl.current_status as status,cl.followup_at as followup_date,
                cl.call_notes as notes,
                p.partner_name as partner_name, t.name as telecaller_name
         FROM calling_leads cl
         LEFT JOIN calling_batches cb ON cb.id=cl.batch_id
         LEFT JOIN partners p ON p.id=cb.partner_id
         LEFT JOIN telecallers t ON t.id=cl.assigned_telecaller_id
         WHERE {$where} AND cl.current_status NOT IN ('Interested','Not_Interested','Wrong_Number')
         ORDER BY cl.followup_at ASC LIMIT 100");
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if($action==='batches_list'){
    $rows = safeQuery($pdo,
        "SELECT cb.*,p.partner_name,
           cb.batch_name as name,
           (SELECT COUNT(*) FROM calling_leads cl WHERE cl.batch_id=cb.id) as lead_count,
           cb.assigned_count as assigned,
           (SELECT COUNT(*) FROM calling_leads cl WHERE cl.batch_id=cb.id AND cl.current_status!='New') as called,
           (SELECT COUNT(*) FROM calling_leads cl WHERE cl.batch_id=cb.id AND cl.current_status='Interested') as interested
         FROM calling_batches cb LEFT JOIN partners p ON p.id=cb.partner_id
         ORDER BY cb.uploaded_at DESC LIMIT 100");
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if($action==='diva_list'){
    $rows = safeQuery($pdo,
        "SELECT * FROM diva_leads_v2 ORDER BY created_at DESC LIMIT 100");
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if($action==='audit_list'){
    $rows = safeQuery($pdo,
        "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 200");
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if($action==='data_quality'){
    $lenders = safeQuery($pdo,
        "SELECT id,lender_name,lender_type,roi_min,roi_max,max_ltv,min_cibil,
                max_foir_pct,property_types,city_coverage,income_types,
                surrogate_banking,surrogate_gst,surrogate_itr,surrogate_rtr,
                processing_fee_pct,sales_manager_name,updated_at
         FROM lenders_products WHERE status='active' ORDER BY lender_name");

    $result = [];
    foreach($lenders as $l){
        $score = 0; $missing = [];
        $checks = [
            'roi_min'=>10,'max_ltv'=>10,'min_cibil'=>10,'max_foir_pct'=>8,
            'property_types'=>8,'city_coverage'=>8,'income_types'=>8,
            'sales_manager_name'=>5,'processing_fee_pct'=>5
        ];
        foreach($checks as $field=>$weight){
            if(!empty($l[$field])&&$l[$field]!='0') $score+=$weight;
            else $missing[]=$field;
        }
        // Surrogate check (8 pts if any)
        $hasSur = $l['surrogate_banking']||$l['surrogate_gst']||$l['surrogate_itr']||$l['surrogate_rtr'];
        if($hasSur) $score+=8; else $missing[]='surrogate_programs';

        $age = $l['updated_at'] ? (int)floor((time()-strtotime($l['updated_at']))/86400) : 999;
        $result[] = [
            'id'=>$l['id'],'lender_name'=>$l['lender_name'],'lender_type'=>$l['lender_type'],
            'score'=>$score,'missing'=>$missing,'data_age_days'=>$age,
            'last_updated'=>$l['updated_at'],'review_required'=>($score<50||$age>60)?1:0
        ];
    }
    usort($result,fn($a,$b)=>$a['score']<=>$b['score']);
    echo json_encode(['success'=>true,'data'=>$result]); exit;
}

if($action==='find_lender'){
    $name    = trim($_GET['name']??'');
    $product = strtolower(trim($_GET['product']??''));
    if(!$name){ echo json_encode(['success'=>false,'message'=>'name required']); exit; }

    // Extract key words from lender name for matching (min 4 chars)
    $nameParts = array_filter(explode(' ', $name), fn($p) => strlen($p) >= 2 && !in_array(strtolower($p), ['bank','finance','limited','ltd','housing','capital','financial','services','india','home']));
    if(empty($nameParts)){
        echo json_encode(['success'=>false,'data'=>null]); exit;
    }

    // ALL significant name words must match
    $whereClauses = array_map(fn($p) => "LOWER(lender_name) LIKE ?", $nameParts);
    $params = array_map(fn($p) => '%'.strtolower($p).'%', $nameParts);

    // Try name + product match
    $rows = safeQuery($pdo,
        "SELECT id,lender_name,product_type,product FROM lenders_products
         WHERE ".implode(' AND ',$whereClauses)."
         AND (LOWER(product_type) LIKE ? OR LOWER(product) LIKE ?)
         AND status='active' ORDER BY id ASC LIMIT 1",
        array_merge($params, ['%'.$product.'%','%'.$product.'%'])
    );

    // Fallback: name only (no product filter)
    if(empty($rows)){
        $rows = safeQuery($pdo,
            "SELECT id,lender_name,product_type,product FROM lenders_products
             WHERE ".implode(' AND ',$whereClauses)."
             AND status='active' ORDER BY id ASC LIMIT 1",
            $params
        );
    }

    echo json_encode(['success'=>!empty($rows),'data'=>$rows[0]??null]); exit;
}

if($action==='get_lender'){
    $id = intSafe($_GET['id']??0);
    if(!$id){ echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    $row = safeQuery($pdo,"SELECT * FROM lenders_products WHERE id=? LIMIT 1",[$id]);
    echo json_encode(['success'=>true,'data'=>$row[0]??null]); exit;
}

if($action==='full_update'){
    $b = $body_tmp ?: getJsonBody();
    $id = intSafe($b['id']??0);
    $f  = $b['fields']??[];
    if(!$id){ echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    $intCols   = ['min_cibil','max_foir_pct','max_tenure_months','loan_min_lakh','loan_max_lakh',
                  'geo_limit_km','surrogate_banking','surrogate_gst','surrogate_itr','surrogate_rtr',
                  'surrogate_lip','surrogate_gross_profit','surrogate_low_ltv',
                  'property_sorp_allowed','property_socp_allowed','property_soip_allowed',
                  'property_rented_allowed','property_land_allowed','property_under_construction',
                  'title_jda','title_rhb','title_society_patta','title_gram_panchayat',
                  'title_nagar_nigam','title_riico','title_freehold',
                  'cash_income_allowed','nip_allowed','rental_income_considered','negative_bureau_allowed'];
    $floatCols = ['roi_min','roi_max','max_ltv','processing_fee_pct'];
    $allowed   = ['lender_name','lender_type','product_type','loan_category',
                  'roi_min','roi_max','max_ltv','min_cibil','max_foir_pct','processing_fee_pct',
                  'loan_min_lakh','loan_max_lakh','max_tenure_months','geo_limit_km',
                  'city_coverage','city','cibil_policy','status',
                  'sales_manager_name','sales_manager_mobile',
                  'property_allowed','income_types','programs','notes','special_profiles','products_supported',
                  'surrogate_banking','surrogate_gst','surrogate_itr','surrogate_rtr',
                  'surrogate_lip','surrogate_gross_profit','surrogate_low_ltv',
                  'property_sorp_allowed','property_socp_allowed','property_soip_allowed',
                  'property_rented_allowed','property_land_allowed','property_under_construction',
                  'title_jda','title_rhb','title_society_patta','title_gram_panchayat',
                  'title_nagar_nigam','title_riico','title_freehold',
                  'cash_income_allowed','nip_allowed','rental_income_considered','negative_bureau_allowed'];
    $sets=[]; $vals=[];
    foreach($allowed as $col){
        if(!array_key_exists($col,$f)) continue;
        $v = $f[$col];
        if(in_array($col,$intCols))   $v = ($v===''||$v===null) ? null : (int)$v;
        if(in_array($col,$floatCols)) $v = ($v===''||$v===null) ? null : (float)$v;
        $sets[]="$col=?"; $vals[]=$v;
    }
    if(empty($sets)){ echo json_encode(['success'=>false,'message'=>'No fields']); exit; }
    $vals[]=$id;
    try {
        $pdo->prepare("UPDATE lenders_products SET ".implode(',',$sets).",updated_at=NOW() WHERE id=?")->execute($vals);
        echo json_encode(['success'=>true,'message'=>'Lender updated']);
    } catch(\Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
    exit;
}

if($action==='quick_update'){
    $body = $body_tmp ?: getJsonBody();
    $id = intSafe($body['id']??0);
    $f  = $body['fields']??[];
    if(!$id||empty($f)){ echo json_encode(['success'=>false,'message'=>'id and fields required']); exit; }
    $sets=[]; $vals=[];
    $intCols   = ['min_cibil','max_foir_pct'];
    $floatCols = ['roi_min','roi_max','max_ltv','processing_fee_pct'];
    $allowed   = ['roi_min','roi_max','max_ltv','min_cibil','city_coverage','city',
                  'sales_manager_name','sales_manager_mobile','status','max_foir_pct',
                  'processing_fee_pct','notes','programs','lender_type'];
    foreach($allowed as $col){
        if(!isset($f[$col])) continue;
        $v = $f[$col];
        if(in_array($col,$intCols))   $v = ($v===''||$v===null) ? null : (int)$v;
        if(in_array($col,$floatCols)) $v = ($v===''||$v===null) ? null : (float)$v;
        $sets[]="$col=?"; $vals[]=$v;
    }
    if(empty($sets)){ echo json_encode(['success'=>false,'message'=>'No valid fields']); exit; }
    $vals[]=$id;
    try {
        $pdo->prepare("UPDATE lenders_products SET ".implode(',',$sets).",updated_at=NOW() WHERE id=?")->execute($vals);
        echo json_encode(['success'=>true,'message'=>'Lender updated']);
    } catch(\Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
    exit;
}


if($action==='get_all_sms'){
    $search = trim($_GET['search']??$b['search']??'');
    $filter = trim($_GET['filter']??$b['filter']??'all');
    $sql = "SELECT ls.*, lp.lender_name FROM lender_sms ls LEFT JOIN lenders_products lp ON ls.lender_id=lp.id";
    $where=[]; $params=[];
    if($search){$where[]="(ls.sm_name LIKE ? OR ls.sm_mobile LIKE ? OR lp.lender_name LIKE ?)";$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";}
    if($filter==='active'){$where[]="ls.is_active=1";}
    if($filter==='inactive'){$where[]="ls.is_active=0";}
    if($where) $sql.=" WHERE ".implode(" AND ",$where);
    $sql.=" GROUP BY ls.id ORDER BY lp.lender_name, ls.is_active DESC, ls.sm_name";
    $sms=safeQuery($pdo,$sql,$params);
    $stats=safeQuery($pdo,"SELECT COUNT(*) total, SUM(is_active) active_count, COUNT(DISTINCT lender_id) lenders FROM lender_sms",[])[0];
    $sc=safeQuery($pdo,"SELECT COUNT(*) total FROM lender_shares",[])[0];
    jsonResponse(['status'=>'ok','sms'=>$sms,'stats'=>$stats,'share_count'=>$sc['total']]);
}
if($action==='get_share_logs'){
    $rows=safeQuery($pdo,"SELECT ls.*, c.customer_name, c.mobile as cust_mobile, c.loan_type FROM lender_shares ls LEFT JOIN customer_leads c ON ls.lead_id=c.id ORDER BY ls.created_at DESC LIMIT 50",[]);
    jsonResponse(['status'=>'ok','shares'=>$rows]);
}
if($action==='get_sms'){
    $lid = intSafe($_GET['lender_id']??0);
    if(!$lid){ echo json_encode(['success'=>false,'message'=>'lender_id required']); exit; }
    $rows = safeQuery($pdo,"SELECT * FROM lender_sms WHERE lender_id=? ORDER BY is_active DESC, created_at ASC",[$lid]);
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if($action==='add_sm'){
    $b = $body_tmp ?: getJsonBody();
    $lid  = intSafe($b['lender_id']??0);
    $name = trim($b['sm_name']??'');
    if(!$lid||!$name){ echo json_encode(['success'=>false,'message'=>'lender_id and sm_name required']); exit; }
    try {
        $pdo->prepare("INSERT INTO lender_sms (lender_id,sm_name,sm_mobile,designation,program_type,territory,notes,is_active) VALUES (?,?,?,?,?,?,?,1)")
            ->execute([$lid,$name,trim($b['sm_mobile']??''),$b['designation']??'SM',trim($b['program_type']??''),trim($b['territory']??''),trim($b['notes']??'')]);
        $id = $pdo->lastInsertId();
        echo json_encode(['success'=>true,'message'=>'SM added','id'=>$id]); 
    } catch(\Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
    exit;
}

if($action==='update_sm'){
    $b = $body_tmp ?: getJsonBody();
    $id = intSafe($b['id']??0);
    if(!$id){ echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    try {
        $pdo->prepare("UPDATE lender_sms SET sm_name=?,sm_mobile=?,designation=?,program_type=?,territory=?,notes=?,updated_at=NOW() WHERE id=?")
            ->execute([trim($b['sm_name']??''),trim($b['sm_mobile']??''),$b['designation']??'SM',trim($b['program_type']??''),trim($b['territory']??''),trim($b['notes']??''),$id]);
        echo json_encode(['success'=>true,'message'=>'SM updated']);
    } catch(\Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
    exit;
}

if($action==='toggle_sm'){
    $b = $body_tmp ?: getJsonBody();
    $id = intSafe($b['id']??0);
    $reason = trim($b['reason']??'');
    if(!$id){ echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    $cur = safeQuery($pdo,"SELECT is_active FROM lender_sms WHERE id=?",[$id]);
    $isActive = (int)($cur[0]['is_active']??0);
    if($isActive){
        // Deactivating — save reason + timestamp
        $pdo->prepare("UPDATE lender_sms SET is_active=0,deactivation_reason=?,deactivated_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$reason,$id]);
    } else {
        // Reactivating — clear reason
        $pdo->prepare("UPDATE lender_sms SET is_active=1,deactivation_reason=NULL,deactivated_at=NULL,updated_at=NOW() WHERE id=?")->execute([$id]);
    }
    echo json_encode(['success'=>true,'message'=>'SM status updated']); exit;
}

if($action==='update_lead_status'){
    $b = $body_tmp ?: getJsonBody();
    $id = intSafe($b['id']??0);
    $status = trim($b['status']??'');
    if(!$id){ echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    try {
        $pdo->prepare("UPDATE calling_leads SET current_status=?,followup_done=1,updated_at=NOW() WHERE id=?")->execute([$status,$id]);
        echo json_encode(['success'=>true,'message'=>'Updated']);
    } catch(\Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
    exit;
}

if($action==='update_disbursal'){
    $b = getJsonBody();
    $id     = intSafe($b['lead_id']??0);
    $status = trim($b['status']??'');
    $amount = intSafe($b['disbursal_amount']??0);
    $date   = trim($b['disbursal_date']??'');
    $lender = trim($b['disbursed_lender']??'');
    if(!$id){ echo json_encode(['success'=>false,'message'=>'lead_id required']); exit; }
    try {
        $pdo->prepare("UPDATE calling_leads SET current_status=?, disbursal_amount=?, disbursal_date=?, disbursed_lender=?, updated_at=NOW() WHERE id=?")
            ->execute([$status, $amount?:null, $date?:null, $lender?:null, $id]);
        echo json_encode(['success'=>true,'message'=>'Disbursal updated']);
    } catch(\Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
    exit;
}
// ═══════════════════════════════════════════════════════
// LLM CALL LOGS
// ═══════════════════════════════════════════════════════
if ($action === 'llm_stats') {
    $days = intSafe($_GET['days'] ?? 30);
    $stats = [];
    // Overall
    $r = safeQuery($pdo, "SELECT COUNT(*) as calls, SUM(total_tokens) as tokens, SUM(cost_usd) as cost_usd, AVG(latency_ms) as avg_latency, SUM(CASE WHEN status='error' THEN 1 ELSE 0 END) as errors FROM llm_call_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)", [$days]);
    $stats['overall'] = $r[0] ?? [];
    // By source
    $stats['by_source'] = safeQuery($pdo, "SELECT call_source, COUNT(*) as calls, SUM(total_tokens) as tokens, SUM(cost_usd) as cost_usd FROM llm_call_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY call_source ORDER BY calls DESC", [$days]);
    // Daily trend
    $stats['daily'] = safeQuery($pdo, "SELECT DATE(created_at) as date, COUNT(*) as calls, SUM(total_tokens) as tokens, SUM(cost_usd) as cost_usd FROM llm_call_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY date ASC", [$days]);
    // Today
    $stats['today'] = safeQuery($pdo, "SELECT COUNT(*) as calls, SUM(total_tokens) as tokens, SUM(cost_usd) as cost_usd FROM llm_call_logs WHERE DATE(created_at) = CURDATE()", []);
    $stats['today'] = $stats['today'][0] ?? [];
    echo json_encode(['success' => true, 'data' => $stats]); exit;
}

if ($action === 'llm_logs') {
    $page   = max(1, intSafe($_GET['page'] ?? 1));
    $limit  = 25;
    $offset = ($page - 1) * $limit;
    $status = trim($_GET['status'] ?? '');
    $source = trim($_GET['source'] ?? '');
    $where  = 'WHERE 1=1';
    $params = [];
    if ($status) { $where .= ' AND status = ?'; $params[] = $status; }
    if ($source) { $where .= ' AND call_source = ?'; $params[] = $source; }
    $total = safeQuery($pdo, "SELECT COUNT(*) as n FROM llm_call_logs {$where}", $params)[0]['n'] ?? 0;
    $paramsPage = array_merge($params, [$limit, $offset]);
    $logs = safeQuery($pdo, "SELECT id, call_source, model, prompt_tokens, completion_tokens, total_tokens, latency_ms, status, cost_usd, prompt_preview, error_message, created_at FROM llm_call_logs {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?", $paramsPage);
    echo json_encode(['success' => true, 'logs' => $logs, 'total' => (int)$total, 'page' => $page, 'pages' => (int)ceil($total / $limit)]); exit;
}

// ═══════════════════════════════════════════════════════
// TECH COSTS
// ═══════════════════════════════════════════════════════
if ($action === 'costs_list') {
    $costs = safeQuery($pdo, "SELECT * FROM tech_costs WHERE is_active=1 ORDER BY category, name", []);
    // Monthly equivalent for each
    foreach ($costs as &$c) {
        $amt = (float)$c['amount'];
        $cur = $c['currency'] === 'USD' ? $amt * 83 : $amt; // rough INR conversion
        $c['monthly_inr'] = $c['frequency'] === 'monthly'   ? $cur
                          : ($c['frequency'] === 'yearly'   ? round($cur / 12, 2)
                          : ($c['frequency'] === 'one_time' ? 0 : $cur));
    }
    unset($c);
    $total_monthly = array_sum(array_column($costs, 'monthly_inr'));
    echo json_encode(['success' => true, 'costs' => $costs, 'total_monthly_inr' => round($total_monthly, 2)]); exit;
}

if ($action === 'costs_add') {
    $b = getJsonBody();
    $name    = trim($b['name']    ?? '');
    $cat     = trim($b['category']  ?? 'other');
    $amt     = (float)($b['amount'] ?? 0);
    $cur     = in_array($b['currency'] ?? 'INR', ['INR','USD']) ? $b['currency'] : 'INR';
    $freq    = in_array($b['frequency'] ?? 'monthly', ['monthly','yearly','one_time','usage_based']) ? $b['frequency'] : 'monthly';
    $bdate   = trim($b['billing_date'] ?? '') ?: null;
    $vendor  = trim($b['vendor']  ?? '') ?: null;
    $notes   = trim($b['notes']   ?? '') ?: null;
    if (!$name || $amt <= 0) { echo json_encode(['success'=>false,'message'=>'Name and amount required']); exit; }
    $pdo->prepare("INSERT INTO tech_costs (name,category,amount,currency,frequency,billing_date,vendor,notes) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$name,$cat,$amt,$cur,$freq,$bdate,$vendor,$notes]);
    echo json_encode(['success'=>true,'message'=>'Cost added','id'=>(int)$pdo->lastInsertId()]); exit;
}

if ($action === 'costs_update') {
    $b  = getJsonBody();
    $id = intSafe($b['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    $pdo->prepare("UPDATE tech_costs SET name=?,category=?,amount=?,currency=?,frequency=?,billing_date=?,vendor=?,notes=?,updated_at=NOW() WHERE id=?")
        ->execute([trim($b['name']??''),trim($b['category']??'other'),(float)($b['amount']??0),$b['currency']??'INR',$b['frequency']??'monthly',trim($b['billing_date']??'')?:null,trim($b['vendor']??'')?:null,trim($b['notes']??'')?:null,$id]);
    echo json_encode(['success'=>true,'message'=>'Updated']); exit;
}

if ($action === 'costs_delete') {
    $b  = getJsonBody();
    $id = intSafe($b['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    $pdo->prepare("UPDATE tech_costs SET is_active=0 WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true,'message'=>'Deleted']); exit;
}
echo json_encode(['success'=>false,'message'=>'Unknown action']);
