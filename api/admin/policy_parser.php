<?php
/**
ini_set("display_errors", 1); error_reporting(E_ALL);
 * WealthMetre — Lender Policy Parser API
 * Path: /api/admin/policy_parser.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Key');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../api/config.php';

$adminKey = $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';
$body     = getJsonBody();
if (!$adminKey) $adminKey = $body['admin_key'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if ($adminKey !== 'WM_ADMIN_2025' && !in_array($ip, ['127.0.0.1','::1'])) {
    jsonError('Unauthorized', 401);
}

$action = $body['action'] ?? $_GET['action'] ?? '';

match($action) {
    'parse'  => parsePolicy($body),
    'save'   => savePolicy($body),
    'list'   => listLenders(),
    'get'    => getLender($body),
    'update' => updateLender($body),
    'delete' => deleteLender($body),
    'export' => exportLenders(),
    default  => jsonError('Invalid action')
};

function postProcessResult(array $r): array {
    // Fix 1: SOIP vs Industrial
    // If industrial has value but soip is empty → move to soip (self-occupied assumed)
    $ltv = $r['ltv_matrix'] ?? [];
    if (!empty($ltv['industrial']) && empty($ltv['soip'])) {
        $r['ltv_matrix']['soip'] = $ltv['industrial'];
        $r['ltv_matrix']['industrial'] = null;
    }

    // Fix 2: Builder must not be in property_allowed
    if (!empty($r['property_allowed'])) {
        $propArr = array_map('trim', explode(',', $r['property_allowed']));
        $builderFound = false;
        $cleanProps = [];
        foreach ($propArr as $p) {
            if (stripos($p, 'builder') !== false) {
                $builderFound = true;
            } else {
                $cleanProps[] = $p;
            }
        }
        $r['property_allowed'] = implode(', ', array_filter($cleanProps));
        if ($builderFound) {
            $existing = $r['special_profiles'] ?? '';
            $r['special_profiles'] = trim($existing . ', Builder profile SORP/SOCP funding available', ', ');
        }
    }

    // Fix 3: Normalize RIICO spelling
    if (!empty($r['property_allowed'])) {
        $r['property_allowed'] = preg_replace('/Ricco|Rico|RICCO/i', 'RIICO', $r['property_allowed']);
    }

    // Fix 4: Clear hallucinated tenure (144 months land is a common hallucination)
    // Only keep tenure if it was explicitly confirmed by AI with high confidence
    if (!empty($r['tenure_matrix'])) {
        $tm = $r['tenure_matrix'];
        // If all tenure values are exactly the hallucination pattern (180+144+180) = clear
        if (($tm['default_months'] == 180) && ($tm['land_months'] == 144) && ($tm['commercial_months'] == 180)) {
            // Check if tenure was actually in the message by looking at confidence
            if (($r['confidence_score'] ?? 0) < 85) {
                $r['tenure_matrix'] = ['default_months' => null, 'land_months' => null, 'commercial_months' => null];
                $r['max_tenure_months'] = null;
            }
        }
    }

    // Fix 5: Clear hallucinated city (Jaipur+Jodhpur+Udaipur pattern when not mentioned)
    if (!empty($r['city_allowed'])) {
        $city = strtolower($r['city_allowed']);
        // This exact 3-city combo is a common hallucination for Rajasthan lenders
        $hallucinated = ['jaipur, jodhpur, udaipur', 'jaipur,jodhpur,udaipur'];
        foreach ($hallucinated as $h) {
            if (trim($city) === $h) {
                $r['risk_flags'][] = 'City may be hallucinated — not found in message. Please verify.';
                $r['human_review_required'] = true;
                break;
            }
        }
    }

    // Fix 5: Correct AI lender match — if matched_lender_id points to wrong lender, fix it
    file_put_contents("/var/www/wealthmetre/public_html/uploads/fix5.txt", json_encode(["mid"=>($r["matched_lender_id"]??null),"name"=>($r["lender_name"]??null)])."\n", FILE_APPEND);
    if (!empty($r['matched_lender_id']) && !empty($r['lender_name'])) {
        try {
            $pdo = getDB();
            $chk = $pdo->prepare("SELECT lender_name FROM lenders_products WHERE id=? LIMIT 1");
            $chk->execute([$r['matched_lender_id']]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $dbWords  = explode(' ', strtolower(trim($row['lender_name'])));
                $newWords = array_filter(explode(' ', strtolower(trim($r['lender_name']))),
                    fn($w) => strlen($w) >= 2 && !in_array($w, ['bank','finance','limited','ltd','housing','capital','financial','services','india','home']));
                $match = false;
                foreach ($newWords as $w) { if (in_array($w, $dbWords)) { $match = true; break; } }
                if (!$match) {
                    // Find correct record by name + product
                    $fix = $pdo->prepare("SELECT id FROM lenders_products WHERE LOWER(lender_name) LIKE ? AND (product=? OR product_type=?) AND status='active' LIMIT 1");
                    $fix->execute(['%'.strtolower($r['lender_name']).'%', $r['product']??'', $r['product_type']??'']);
                    $correct = $fix->fetch(PDO::FETCH_ASSOC);
                    if ($correct) {
                        $r['matched_lender_id'] = (int)$correct['id'];
                        $r['decision'] = 'policy_update';
                        $r['_match_corrected'] = true;
                    } else {
                        $r['matched_lender_id'] = null;
                        $r['decision'] = 'new_lender';
                        $r['_match_corrected'] = true;
                    }
                }
            }
        } catch (\Throwable $e) { /* non-blocking */ }
    }
    return $r;
}

function parsePolicy(array $b): void {
    $rawText = trim($b['raw_text'] ?? '');
    if (!$rawText) jsonError('raw_text required');
    $pdo  = getDB();
    $stmt = $pdo->query("SELECT id, lender_name, product FROM lenders_products WHERE status='active' ORDER BY lender_name");
    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $lenderList = array_map(fn($l) => ['id'=>$l['id'],'name'=>$l['lender_name'],'product'=>$l['product']], $existing);
    $systemPrompt = buildSystemPrompt($lenderList);
    // Inject owner-confirmed fields
    $body2 = json_decode(file_get_contents('php://input'),true)??[];
    $confirmedFields = json_decode($body2['confirmed_fields']??'{}',true)??[];
    if(!empty($confirmedFields)){
        $prefix = "=== OWNER CONFIRMED — USE EXACTLY, NEVER OVERRIDE ===\n";
        foreach($confirmedFields as $fld=>$val){
            $prefix .= strtoupper($fld).': '.($val===null?'NULL (leave blank/null)':$val)."\n";
        }
        $prefix .= "=== END CONFIRMED ===\n\n";
        $rawText = $prefix.$rawText;
    }
    $result = callOpenAIStructured($systemPrompt, $rawText);
    if (!$result) jsonError('AI parsing failed'); 

    // POST-PROCESS: Fix common AI mistakes
    $result = postProcessResult($result);

    jsonResponse(['type'=>'parsed','result'=>$result]);
}

function savePolicy(array $b): void {
    $fields = $b['fields'] ?? [];
    try {
    if (empty($fields['lender_name']) && !empty($body['lender_name_hint'])) {
        $fields['lender_name'] = trim((string)$body['lender_name_hint']);
    }
    if (empty($fields['lender_name'])) jsonError('lender_name required — please type the lender name in the Lender Name field');
    $pdo = getDB();
    $key = makeSlug($fields['lender_name'].' '.($fields['product']??''));
    $existingId = intSafe($b['existing_id'] ?? 0);

    // Validate: if existing_id given, verify lender name actually matches DB record
    if ($existingId > 0) {
        $stmt = $pdo->prepare("SELECT lender_name FROM lenders_products WHERE id=?");
        $stmt->execute([$existingId]);
        $dbRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dbRow) {
            $dbName   = strtolower(trim($dbRow['lender_name']));
            $newName  = strtolower(trim($fields['lender_name']));
            // Generic words to exclude from matching
            $genericWords = ['bank','finance','limited','ltd','pvt','private','housing',
                             'capital','financial','services','india','home','investment'];
            // Extract significant unique words (5+ chars, not generic)
            $newWords = array_filter(explode(' ', $newName), fn($w) =>
                strlen($w) >= 5 && !in_array($w, $genericWords)
            );
            if (!empty($newWords)) {
                $dbWords = explode(' ', $dbName);
                $matchCount = 0;
                foreach ($newWords as $word) {
                    // Exact word match (not substring) — prevents 'au' matching 'auto'
                    if (in_array($word, $dbWords)) $matchCount++;
                }
                if ($matchCount === 0) {
                    // Also try: look for correct record by name similarity
                    $stmt2 = $pdo->prepare("SELECT id FROM lenders_products WHERE LOWER(lender_name) LIKE ? AND (product=? OR product_type=?) LIMIT 1");
                    $stmt2->execute(['%'.strtolower($fields['lender_name']).'%', $fields['product']??'', $fields['product_type']??'']);
                    $correct = $stmt2->fetch(PDO::FETCH_ASSOC);
                    $hint = $correct ? " Correct record may be ID: {$correct['id']}." : '';
                    jsonError("Lender name mismatch: DB record is '{$dbRow['lender_name']}' but policy is for '{$fields['lender_name']}'.{$hint} Use Save as New instead.", 409);
                }
            }
        }
    }
    if ($existingId) {
        updateLenderRecord($pdo, $existingId, $fields, $key);
        saveSpecialConditions($pdo, $existingId, $fields['confirmed_special_conditions'] ?? []);
        $qdrantResult = syncToQdrant($pdo, $existingId);
        jsonResponse(['type'=>'updated','id'=>$existingId,'message'=>'Lender policy updated','qdrant_synced'=>$qdrantResult]);
    } else {
        $dupCheck = $pdo->prepare("SELECT id, lender_name FROM lenders_products WHERE lender_key=? LIMIT 1");
        $dupCheck->execute([$key]);
        $dupRow = $dupCheck->fetch(PDO::FETCH_ASSOC);
        if ($dupRow) {
            http_response_code(409);
            jsonError("'{$fields['lender_name']}' already exists (ID: {$dupRow['id']}). Use 'Update Existing' to modify it.", 409);
        }
        $id = insertLenderRecord($pdo, $fields, $key);
        saveSpecialConditions($pdo, $id, $fields['confirmed_special_conditions'] ?? []);
        // Return response immediately — don't make user wait for Qdrant sync
        $response = json_encode(['type'=>'created','id'=>$id,'message'=>'New lender policy saved','qdrant_synced'=>'pending']);
        header('Content-Length: ' . strlen($response));
        header('Connection: close');
        echo $response;
        if (ob_get_level()) { ob_end_flush(); }
        flush();
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        // Sync to Qdrant in background after response sent
        syncToQdrant($pdo, $id);
        exit;
    }
    } catch (\Throwable $e) {
        jsonError('Save failed: ' . $e->getMessage() . ' at line ' . $e->getLine(), 500);
    }
}

function insertLenderRecord(PDO $pdo, array $f, string $key): int {
    $roiMin=$f['roi_min']?floatval($f['roi_min']):null;
    $roiMax=$f['roi_max']?floatval($f['roi_max']):null;
    $maxLtv=$f['max_ltv']?floatval($f['max_ltv']):null;
    $minCibil=$f['min_cibil']?intval($f['min_cibil']):null;
    $loanMin=$f['loan_min_lakh']?floatval($f['loan_min_lakh']):null;
    $loanMax=$f['loan_max_lakh']?floatval($f['loan_max_lakh']):null;
    $tenure=$f['max_tenure_months']?intval($f['max_tenure_months']):null;
    $product=$f['product']??'';
    $ltvMatrix   = isset($f['ltv_matrix']) ? json_encode($f['ltv_matrix']) : null;
    $tenureMatrix= isset($f['tenure_matrix']) ? json_encode($f['tenure_matrix']) : null;
    $pdo->prepare("
        INSERT INTO lenders_products (
            lender_name,lender_key,product,product_type,products_supported,
            roi_min,roi_max,min_roi,max_roi,interest_rate_start,
            max_ltv,min_cibil,loan_min_lakh,loan_max_lakh,max_loan_amount,
            max_tenure_months,city_allowed,city,geo_limit,
            property_allowed,property_types,income_types,profile_allowed,
            special_profiles,programs,notes,sales_manager_name,
            sales_manager_mobile,foreclosure_charges,prepayment_charges,
            guarantor_required,dsa_payout,ltv_matrix,tenure_matrix,
            loan_category,lender_type,processing_fee_pct,processing_fee_flat,
            imd_charges_text,max_foir_pct,foir_relaxation_available,foir_relaxed_pct,
            cibil_policy,negative_bureau_allowed,max_dpd_allowed,
            salaried_allowed,senp_allowed,sep_allowed,nip_allowed,
            cash_income_allowed,co_applicant_income_clubbing,
            surrogate_banking,surrogate_gst,surrogate_itr,surrogate_rtr,
            surrogate_gross_profit,surrogate_low_ltv,surrogate_lip,
            city_coverage,geo_limit_km,
            ltv_sorp,ltv_socp,ltv_soip,ltv_rented,ltv_land,ltv_max_overall,
            property_sorp_allowed,property_socp_allowed,property_soip_allowed,
            property_rented_allowed,property_land_allowed,property_under_construction,
            title_jda,title_rhb,title_society_patta,title_society_patta_year,
            title_gram_panchayat,title_nagar_nigam,title_riico,title_agriculture,
            title_freehold,rental_income_considered,
            min_business_vintage_years,gst_registration_required,
            min_annual_turnover_lakh,min_avg_bank_balance,min_bank_statement_months,
            unsecured_max_foir,unsecured_bureau_min_cibil,
            overdraft_available,working_capital_available,typical_tat_days,
            status,created_at
        ) VALUES (
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
            'active',NOW()
        )
    ")->execute([
        $f['lender_name'],$key,$product,$product,$product,
        $roiMin,$roiMax,$roiMin,$roiMax,$roiMin,
        $maxLtv,$minCibil,$loanMin,$loanMax,$loanMax?$loanMax*100000:null,
        $tenure,$f['city_allowed']??'',$f['city_allowed']??'',$f['geo_limit']??'',
        $f['property_allowed']??'',$f['property_allowed']??'',$f['income_types']??'',
        $f['profile_allowed']??'',$f['special_profiles']??'',$f['programs']??'',
        $f['notes']??'',$f['sales_manager_name']??'',$f['sales_manager_mobile']??'',
        $f['foreclosure_charges']??'Nil',$f['prepayment_charges']??'Nil',
        $f['guarantor_required']??'Case to Case',$f['dsa_payout']??null,$ltvMatrix,$tenureMatrix,
        $f['loan_category']??'secured',$f['lender_type']??'nbfc',
        isset($f['processing_fee_pct'])?floatval($f['processing_fee_pct']):null,
        isset($f['processing_fee_flat'])?intval($f['processing_fee_flat']):null,
        $f['imd_charges_text']??null,
        isset($f['max_foir_pct'])?intval($f['max_foir_pct']):null,
        intval($f['foir_relaxation_available']??0),
        isset($f['foir_relaxed_pct'])?intval($f['foir_relaxed_pct']):null,
        $f['cibil_policy']??'case_to_case',
        intval($f['negative_bureau_allowed']??0),
        isset($f['max_dpd_allowed'])?intval($f['max_dpd_allowed']):null,
        intval($f['salaried_allowed']??1),intval($f['senp_allowed']??1),
        intval($f['sep_allowed']??1),intval($f['nip_allowed']??0),
        intval($f['cash_income_allowed']??0),intval($f['co_applicant_income_clubbing']??0),
        intval($f['surrogate_banking']??0),intval($f['surrogate_gst']??0),
        intval($f['surrogate_itr']??0),intval($f['surrogate_rtr']??0),
        intval($f['surrogate_gross_profit']??0),intval($f['surrogate_low_ltv']??0),
        intval($f['surrogate_lip']??0),
        $f['city_coverage']??'rajasthan',
        isset($f['geo_limit_km'])?intval($f['geo_limit_km']):null,
        isset($f['ltv_sorp'])?floatval($f['ltv_sorp']):null,
        isset($f['ltv_socp'])?floatval($f['ltv_socp']):null,
        isset($f['ltv_soip'])?floatval($f['ltv_soip']):null,
        isset($f['ltv_rented'])?floatval($f['ltv_rented']):null,
        isset($f['ltv_land'])?floatval($f['ltv_land']):null,
        isset($f['ltv_max_overall'])?floatval($f['ltv_max_overall']):null,
        intval($f['property_sorp_allowed']??1),intval($f['property_socp_allowed']??0),
        intval($f['property_soip_allowed']??0),intval($f['property_rented_allowed']??0),
        intval($f['property_land_allowed']??0),intval($f['property_under_construction']??0),
        intval($f['title_jda']??0),intval($f['title_rhb']??0),
        intval($f['title_society_patta']??0),
        isset($f['title_society_patta_year'])?intval($f['title_society_patta_year']):null,
        intval($f['title_gram_panchayat']??0),intval($f['title_nagar_nigam']??0),
        intval($f['title_riico']??0),intval($f['title_agriculture']??0),
        intval($f['title_freehold']??0),intval($f['rental_income_considered']??0),
        isset($f['min_business_vintage_years'])?intval($f['min_business_vintage_years']):null,
        isset($f['gst_registration_required'])?intval($f['gst_registration_required']):null,
        isset($f['min_annual_turnover_lakh'])?floatval($f['min_annual_turnover_lakh']):null,
        isset($f['min_avg_bank_balance'])?intval($f['min_avg_bank_balance']):null,
        isset($f['min_bank_statement_months'])?intval($f['min_bank_statement_months']):null,
        isset($f['unsecured_max_foir'])?intval($f['unsecured_max_foir']):null,
        isset($f['unsecured_bureau_min_cibil'])?intval($f['unsecured_bureau_min_cibil']):null,
        intval($f['overdraft_available']??0),intval($f['working_capital_available']??0),
        isset($f['typical_tat_days'])?intval($f['typical_tat_days']):null
    ]);
    return (int)$pdo->lastInsertId();
}

function updateLenderRecord(PDO $pdo, int $id, array $f, string $key): void {
    $roiMin=$f['roi_min']?floatval($f['roi_min']):null;
    $roiMax=$f['roi_max']?floatval($f['roi_max']):null;
    $product=$f['product']??'';
    $pdo->prepare("
        UPDATE lenders_products SET
        lender_name=?,lender_key=?,product=?,product_type=?,
        roi_min=?,roi_max=?,min_roi=?,max_roi=?,interest_rate_start=?,
        max_ltv=?,min_cibil=?,loan_min_lakh=?,loan_max_lakh=?,max_loan_amount=?,
        max_tenure_months=?,city_allowed=?,city=?,geo_limit=?,
        property_allowed=?,property_types=?,income_types=?,profile_allowed=?,
        special_profiles=?,programs=?,notes=?,sales_manager_name=?,
        sales_manager_mobile=?,foreclosure_charges=?,prepayment_charges=?,
        guarantor_required=?,dsa_payout=?,ltv_matrix=?,tenure_matrix=?,
        loan_category=?,lender_type=?,processing_fee_pct=?,processing_fee_flat=?,
        imd_charges_text=?,max_foir_pct=?,foir_relaxation_available=?,foir_relaxed_pct=?,
        cibil_policy=?,negative_bureau_allowed=?,max_dpd_allowed=?,
        salaried_allowed=?,senp_allowed=?,sep_allowed=?,nip_allowed=?,
        cash_income_allowed=?,co_applicant_income_clubbing=?,
        surrogate_banking=?,surrogate_gst=?,surrogate_itr=?,surrogate_rtr=?,
        surrogate_gross_profit=?,surrogate_low_ltv=?,surrogate_lip=?,
        city_coverage=?,geo_limit_km=?,
        ltv_sorp=?,ltv_socp=?,ltv_soip=?,ltv_rented=?,ltv_land=?,ltv_max_overall=?,
        property_sorp_allowed=?,property_socp_allowed=?,property_soip_allowed=?,
        property_rented_allowed=?,property_land_allowed=?,property_under_construction=?,
        title_jda=?,title_rhb=?,title_society_patta=?,title_society_patta_year=?,
        title_gram_panchayat=?,title_nagar_nigam=?,title_riico=?,title_agriculture=?,
        title_freehold=?,rental_income_considered=?,
        min_business_vintage_years=?,gst_registration_required=?,
        min_annual_turnover_lakh=?,min_avg_bank_balance=?,min_bank_statement_months=?,
        unsecured_max_foir=?,unsecured_bureau_min_cibil=?,
        overdraft_available=?,working_capital_available=?,typical_tat_days=?,
        updated_at=NOW() WHERE id=?
    ")->execute([
        $f['lender_name'],$key,$product,$product,
        $roiMin,$roiMax,$roiMin,$roiMax,$roiMin,
        $f['max_ltv']?floatval($f['max_ltv']):null,
        $f['min_cibil']?intval($f['min_cibil']):null,
        $f['loan_min_lakh']?floatval($f['loan_min_lakh']):null,
        $f['loan_max_lakh']?floatval($f['loan_max_lakh']):null,
        $f['loan_max_lakh']?floatval($f['loan_max_lakh'])*100000:null,
        $f['max_tenure_months']?intval($f['max_tenure_months']):null,
        $f['city_allowed']??'',$f['city_allowed']??'',$f['geo_limit']??'',
        $f['property_allowed']??'',$f['property_allowed']??'',$f['income_types']??'',
        $f['profile_allowed']??'',$f['special_profiles']??'',$f['programs']??'',
        $f['notes']??'',$f['sales_manager_name']??'',$f['sales_manager_mobile']??'',
        $f['foreclosure_charges']??'Nil',$f['prepayment_charges']??'Nil',
        $f['guarantor_required']??'Case to Case',
        $f['dsa_payout']??null,
        isset($f['ltv_matrix'])?json_encode($f['ltv_matrix']):null,
        isset($f['tenure_matrix'])?json_encode($f['tenure_matrix']):null,
        $f['loan_category']??'secured',$f['lender_type']??'nbfc',
        isset($f['processing_fee_pct'])?floatval($f['processing_fee_pct']):null,
        isset($f['processing_fee_flat'])?intval($f['processing_fee_flat']):null,
        $f['imd_charges_text']??null,
        isset($f['max_foir_pct'])?intval($f['max_foir_pct']):null,
        intval($f['foir_relaxation_available']??0),
        isset($f['foir_relaxed_pct'])?intval($f['foir_relaxed_pct']):null,
        $f['cibil_policy']??'case_to_case',
        intval($f['negative_bureau_allowed']??0),
        isset($f['max_dpd_allowed'])?intval($f['max_dpd_allowed']):null,
        intval($f['salaried_allowed']??1),intval($f['senp_allowed']??1),
        intval($f['sep_allowed']??1),intval($f['nip_allowed']??0),
        intval($f['cash_income_allowed']??0),intval($f['co_applicant_income_clubbing']??0),
        intval($f['surrogate_banking']??0),intval($f['surrogate_gst']??0),
        intval($f['surrogate_itr']??0),intval($f['surrogate_rtr']??0),
        intval($f['surrogate_gross_profit']??0),intval($f['surrogate_low_ltv']??0),
        intval($f['surrogate_lip']??0),
        $f['city_coverage']??'rajasthan',
        isset($f['geo_limit_km'])?intval($f['geo_limit_km']):null,
        isset($f['ltv_sorp'])?floatval($f['ltv_sorp']):null,
        isset($f['ltv_socp'])?floatval($f['ltv_socp']):null,
        isset($f['ltv_soip'])?floatval($f['ltv_soip']):null,
        isset($f['ltv_rented'])?floatval($f['ltv_rented']):null,
        isset($f['ltv_land'])?floatval($f['ltv_land']):null,
        isset($f['ltv_max_overall'])?floatval($f['ltv_max_overall']):null,
        intval($f['property_sorp_allowed']??1),intval($f['property_socp_allowed']??0),
        intval($f['property_soip_allowed']??0),intval($f['property_rented_allowed']??0),
        intval($f['property_land_allowed']??0),intval($f['property_under_construction']??0),
        intval($f['title_jda']??0),intval($f['title_rhb']??0),
        intval($f['title_society_patta']??0),
        isset($f['title_society_patta_year'])?intval($f['title_society_patta_year']):null,
        intval($f['title_gram_panchayat']??0),intval($f['title_nagar_nigam']??0),
        intval($f['title_riico']??0),intval($f['title_agriculture']??0),
        intval($f['title_freehold']??0),intval($f['rental_income_considered']??0),
        isset($f['min_business_vintage_years'])?intval($f['min_business_vintage_years']):null,
        isset($f['gst_registration_required'])?intval($f['gst_registration_required']):null,
        isset($f['min_annual_turnover_lakh'])?floatval($f['min_annual_turnover_lakh']):null,
        isset($f['min_avg_bank_balance'])?intval($f['min_avg_bank_balance']):null,
        isset($f['min_bank_statement_months'])?intval($f['min_bank_statement_months']):null,
        isset($f['unsecured_max_foir'])?intval($f['unsecured_max_foir']):null,
        isset($f['unsecured_bureau_min_cibil'])?intval($f['unsecured_bureau_min_cibil']):null,
        intval($f['overdraft_available']??0),intval($f['working_capital_available']??0),
        isset($f['typical_tat_days'])?intval($f['typical_tat_days']):null,
        $id
    ]);
}


function saveSpecialConditions(PDO $pdo, int $lenderProductId, array $conditions): void {
    if (empty($conditions)) return;
    $pdo->prepare("DELETE FROM lender_special_conditions WHERE lender_product_id = ? AND is_confirmed = 0")->execute([$lenderProductId]);
    $stmt = $pdo->prepare("
        INSERT INTO lender_special_conditions
            (lender_product_id, category, flag_name, condition_text, verbatim_text, is_confirmed)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    $valid = ['property_flexibility','income_flexibility','credit_flexibility','structural_flexibility','profile_flexibility'];
    foreach ($conditions as $sc) {
        $verbatim = trim($sc['verbatim'] ?? $sc['verbatim_text'] ?? '');
        if (empty($sc['flag_name']) || empty($verbatim)) continue;
        $cat = in_array($sc['category'] ?? '', $valid) ? $sc['category'] : 'structural_flexibility';
        $stmt->execute([
            $lenderProductId,
            $cat,
            substr(sanitize($sc['flag_name']), 0, 100),
            substr(sanitize($sc['condition_text'] ?? 'as per policy'), 0, 255),
            $verbatim
        ]);
    }
}

function listLenders(): void {
    $pdo    = getDB();
    $search = sanitize($_GET['search'] ?? '');
    $product= sanitize($_GET['product'] ?? '');
    $where  = ["status='active'"]; $params=[];
    if ($search){ $where[]="lender_name LIKE ?"; $params[]="%$search%"; }
    if ($product){ $where[]="product LIKE ?"; $params[]="%$product%"; }
    $stmt = $pdo->prepare("SELECT id,lender_name,product,roi_min,roi_max,max_ltv,min_cibil,loan_min_lakh,loan_max_lakh,city_allowed,sales_manager_name,sales_manager_mobile,programs,created_at FROM lenders_products WHERE ".implode(' AND ',$where)." ORDER BY lender_name ASC");
    $stmt->execute($params);
    jsonResponse(['lenders'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getLender(array $b): void {
    $id=intSafe($b['id']??$_GET['id']??0);
    if(!$id) jsonError('id required');
    $stmt=$pdo=getDB(); $stmt=$pdo->prepare("SELECT * FROM lenders_products WHERE id=?");
    $stmt->execute([$id]); $l=$stmt->fetch(PDO::FETCH_ASSOC);
    if(!$l) jsonError('Not found',404);
    jsonResponse(['lender'=>$l]);
}

function exportLenders(): void {
    $pdo  = getDB();
    $stmt = $pdo->query("
        SELECT id, lender_name, product, roi_min, roi_max, max_ltv, min_cibil,
               loan_min_lakh, loan_max_lakh, max_tenure_months,
               city_allowed, geo_limit, property_allowed, income_types,
               profile_allowed, special_profiles, programs, notes,
               dsa_payout, sales_manager_name, sales_manager_mobile,
               foreclosure_charges, prepayment_charges, guarantor_required,
               status, created_at
        FROM lenders_products
        WHERE status='active'
        ORDER BY lender_name ASC
    ");
    jsonResponse(['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function deleteLender(array $b): void {
    $id=intSafe($b['id']??0); if(!$id) jsonError('id required');
    getDB()->prepare("UPDATE lenders_products SET status='inactive' WHERE id=?")->execute([$id]);
    jsonResponse(['message'=>'Deactivated']);
}

function updateLender(array $b): void {
    $id=intSafe($b['id']??0); if(!$id) jsonError('id required');
    $key=makeSlug(($b['fields']['lender_name']??'').' '.($b['fields']['product']??''));
    updateLenderRecord(getDB(),$id,$b['fields']??[],$key);
    jsonResponse(['message'=>'Updated','id'=>$id]);
}

function buildSystemPrompt(array $lenders): string {
    $lj = json_encode($lenders, JSON_UNESCAPED_UNICODE);
    $prompt = <<<PROMPT
You are WealthMetre's Lender Intelligence Parser for Indian NBFC/Bank loan policies.
You extract structured data from unstructured WhatsApp/email lender policy messages.

EXISTING LENDERS IN DB (id | lender_name | product):
{$lj}

LENDER MATCHING RULE — CRITICAL:
Match ONLY on lender_name. Name must match EXACTLY or near-exactly.
"DCB Bank" → find name="DCB Bank" NOT "IDFC Bank". "AU Bank" → name="AU Bank" only.
Short names like DCB, AU, SBI, PNB must match letter-for-letter — never guess.
If unsure, set matched_lender_id=null and decision="new_lender".

═══════════════════════════════════════════════
ABSOLUTE RULES — NEVER VIOLATE THESE
═══════════════════════════════════════════════

RULE 1 — NEVER HALLUCINATE:
If a field is NOT explicitly written in the message return null for that field.
Do NOT fill from memory, general knowledge, or previous examples.
This applies especially to: LTV values, city names, tenure, CIBIL, ROI.

RULE 2 — LTV ONLY IF STATED:
If LTV is not mentioned max_ltv = null, ALL ltv_matrix fields = null.
Never invent LTV values.

RULE 3 — CITY ONLY IF STATED:
If city/location is not mentioned city_allowed = null, geo_limit = null.
Never assume geography.

RULE 4 — TENURE ONLY IF STATED:
If tenure is not mentioned max_tenure_months = null, ALL tenure_matrix fields = null.
Never invent tenure.

RULE 5 — NULL BEATS GUESS:
A null value is always better than a wrong value.
RULE 6 — SM DESIGNATION:
When extracting sales manager details, also extract their designation.
Map as follows:
- "Relationship Manager" or "RM" → "RM"
- "Area Sales Manager" or "ASM" → "ASM"
- "Regional Sales Manager" or "RSM" → "RSM"
- "Branch Manager" or "BM" → "BM"
- "Zonal Manager" or "ZM" → "ZM"
- "Senior Manager" or "Senior SM" → "Senior SM"
- "DSA" → "DSA"
- Not mentioned → "SM" (default)
Also extract sm_territory if location is mentioned near SM name.
Also extract sm_program if specific program is mentioned for that SM.

═══════════════════════════════════════════════
OUTPUT FORMAT — RETURN ONLY THIS JSON
═══════════════════════════════════════════════

{
  "lender_name": "official company name only",
  "product": "LAP|Home Loan|Business Loan - Unsecured|Business Loan - Secured|Working Capital - CC/OD|CGTMSE - Unsecured|Cash Credit|Overdraft|Personal Loan|Car Loan|Machinery Loan|Balance Transfer|Institutional Loan",
  "roi_min": null,
  "roi_max": null,
  "max_ltv": null,
  "min_cibil": null,
  "loan_min_lakh": null,
  "loan_max_lakh": null,
  "max_tenure_months": null,
  "city_allowed": null,
  "geo_limit": null,
  "property_allowed": null,
  "income_types": null,
  "profile_allowed": null,
  "special_profiles": null,
  "programs": null,
  "foreclosure_charges": null,
  "prepayment_charges": null,
  "guarantor_required": null,
  "notes": null,
  "sales_manager_name": null,
  "sales_manager_mobile": null,
  "sales_manager_designation": "SM",
  "sales_manager_territory": null,
  "sales_manager_program": null,
  "ltv_matrix": {"sorp":null,"socp":null,"soip":null,"rented":null,"industrial_rented":null,"land":null,"special":null},
  "tenure_matrix": {"default_months":null,"land_months":null,"commercial_months":null},
  "searchable_text": null,
  "decision": "new_lender|new_product|policy_update|duplicate",
  "matched_lender_id": null,
  "confidence_score": 0,
  "change_summary": [],
  "human_review_required": false,
  "risk_flags": [],
  "dual_product_note": null,
  "second_product": null,
  "loan_category": "secured",
  "lender_type": "bank",
  "processing_fee_pct": null,
  "processing_fee_flat": null,
  "imd_charges_text": null,
  "max_foir_pct": null,
  "foir_relaxation_available": 0,
  "foir_relaxed_pct": null,
  "cibil_policy": "case_to_case",
  "negative_bureau_allowed": 0,
  "max_dpd_allowed": null,
  "salaried_allowed": 1,
  "senp_allowed": 1,
  "sep_allowed": 1,
  "nip_allowed": 0,
  "cash_income_allowed": 0,
  "co_applicant_income_clubbing": 0,
  "surrogate_banking": 0,
  "surrogate_gst": 0,
  "surrogate_itr": 0,
  "surrogate_rtr": 0,
  "surrogate_gross_profit": 0,
  "surrogate_low_ltv": 0,
  "surrogate_lip": 0,
  "city_coverage": "rajasthan",
  "geo_limit_km": null,
  "ltv_sorp": null,
  "ltv_socp": null,
  "ltv_soip": null,
  "ltv_rented": null,
  "ltv_land": null,
  "ltv_max_overall": null,
  "property_sorp_allowed": 1,
  "property_socp_allowed": 0,
  "property_soip_allowed": 0,
  "property_rented_allowed": 0,
  "property_land_allowed": 0,
  "property_under_construction": 0,
  "title_jda": 0,
  "title_rhb": 0,
  "title_society_patta": 0,
  "title_society_patta_year": null,
  "title_gram_panchayat": 0,
  "title_nagar_nigam": 0,
  "title_riico": 0,
  "title_agriculture": 0,
  "title_freehold": 0,
  "rental_income_considered": 0,
  "min_business_vintage_years": null,
  "gst_registration_required": null,
  "min_annual_turnover_lakh": null,
  "min_avg_bank_balance": null,
  "min_bank_statement_months": null,
  "unsecured_max_foir": null,
  "unsecured_bureau_min_cibil": null,
  "overdraft_available": 0,
  "working_capital_available": 0,
  "typical_tat_days": null,
  "suggested_special_conditions": []
}

If TWO products exist, second_product should be:
{
  "product": "Home Loan",
  "roi_min": 10,
  "roi_max": 11
}
Otherwise second_product = null

═══════════════════════════════════════════════
NORMALIZATION DICTIONARY
═══════════════════════════════════════════════

PROPERTY: JDA=Jaipur Development Authority, RHB=Rajasthan Housing Board,
NN/Nagar Nigam=Municipal Corporation, RIICO=Rajasthan State Industrial Development,
Ricco/Rico/RICCO → always write RIICO, 90B/90-B/90A/B=Section 90B plots,
SORP=Self Occupied Residential, SOCP=Self Occupied Commercial, SOIP=Self Occupied Industrial,
LRD=Lease Rental Discounting, RTR=Rent To Return, DM=District Magistrate, GP=Gram Panchayat

BORROWER: SENP=Self Employed Non Professional (traders/manufacturers/contractors),
SEP=Self Employed Professional (CA/Doctor/Architect/Lawyer)

PROGRAMS: ABB=Average Bank Balance, GST=GST-based assessment, LIP=Loan Insurance Program,
CGTMSE=Credit Guarantee Fund Trust (unsecured), CC=Cash Credit, OD=Overdraft

AMOUNTS: X Lakh=X, X Cr/Crore=X*100, "10L to 7.5Cr" → min=10, max=750

═══════════════════════════════════════════════
LTV EXTRACTION — CRITICAL RULES
═══════════════════════════════════════════════

TRICKY FORMAT — READ LINE BY LINE:
"LTV: Up to 80%     ← SORP value (first line = first property type)
SORP
Up to 75% SOCP     ← SOCP value
Up to 70% Industrial ← SOIP value"
→ sorp=80, socp=75, soip=70 (NOT sorp=75, socp=70)

STANDARD FORMAT:
"SORP 75%, SOCP 65%, Industrial 40%" → sorp=75, socp=65, soip=40

SOIP vs Industrial Rented:
- "Industrial X%" alone → soip=X, industrial_rented=null
- "Industrial rented X%" → industrial_rented=X, soip=null
- "Rented/Vacant X%" → rented=X

Special properties (Hotels/PG/Hospital/Plot):
- "Approved plot/Hotels/Hospital 55%" → special=55, add to notes with detail
- max_ltv = highest value across all property types

IF LTV NOT MENTIONED → max_ltv=null, all ltv_matrix=null

═══════════════════════════════════════════════
PROPERTY TYPE vs BORROWER TYPE — CRITICAL
═══════════════════════════════════════════════

property_allowed = physical property types:
JDA, RHB, RIICO, NN, 90B, Society Patta (with year if mentioned),
Panchayat Patta, Gram Panchayat, Freehold, Agricultural, Industrial,
Commercial, Hotel, Hospital, Resort, School, College, Factory, DM Converted

special_profiles = borrower types normally rejected but accepted here:
- "Builder Profile funding" → "Builder profile SORP/SOCP funding available"
- "NRI" → "NRI funding available"
- "Old DPD accepted" → "Old DPD in CIBIL considered, latest DPD not"
- "Case to Case Structure" → "Flexible underwriting on case to case basis"
- "Actual built up area" → "Actual built-up area eligibility case to case"
- "Pensioner" → "Pensioner eligible"

NEVER put builder/NRI/pensioner in property_allowed.
ALWAYS include year conditions: "Society Patta till 1998" not just "Society Patta"

═══════════════════════════════════════════════
GEOGRAPHY — ONLY FROM MESSAGE
═══════════════════════════════════════════════

Extract ONLY cities explicitly named. Rajasthan spokes:
- Kotputli, Sikar, Niwai, Churu, Dudu = Jaipur region
- Kishangarh, Beawar = Ajmer region
- Balotra, Pali, Gotan, Sojat = Jodhpur region
List ALL spoke cities in city_allowed.
"PAN India" → city_allowed="PAN India"
NOT mentioned → city_allowed=null, geo_limit=null

═══════════════════════════════════════════════
TWO-PRODUCT RULE
═══════════════════════════════════════════════

Message has "HL & LAP" or two products:
- Extract the LOWER ROI product as primary (usually LAP)
- Set dual_product_note = "Also offers [Product 2] at [ROI]. Create separate record."
- human_review_required = true

═══════════════════════════════════════════════
FIELD ROUTING
═══════════════════════════════════════════════

programs: All surrogate/income programs
(Banking, EMI Surrogate, GST, Cash Profit, ITR, RTR, LRD, Low LTV, Rental, PAT, Assessment Income)

notes: Fees (login/processing), PF sharing, special property LTV conditions,
date-based conditions (Society Patta till 2023), minimum vintage, ecological zones

special_profiles: Flexible underwriting, builder profile, NRI, old DPD policy,
actual built-up area, case to case speciality

═══════════════════════════════════════════════
LENDER MATCHING
═══════════════════════════════════════════════

Match lender_name against existing lenders list (provided above).
Exact or >80% similar → matched_lender_id = id, decision = "policy_update"
No match → matched_lender_id = null, decision = "new_lender"
Same lender different product → decision = "new_product"
human_review_required = true when: match confidence < 80, two products, inconsistent values

═══════════════════════════════════════════════
SEARCHABLE TEXT — 100-150 WORDS
═══════════════════════════════════════════════

Plain English paragraph covering:
lender + loan type, amount range, programs, property types, geography,
borrower segments, key USPs, CIBIL if mentioned.
Factual only. No marketing. English only.

═══════════════════════════════════════════════
NEW STRUCTURED FIELDS — EXTRACTION RULES
═══════════════════════════════════════════════
loan_category:
- "secured" → LAP, Home Loan, Mortgage, Car Loan, Property Loan, Machinery Loan
- "unsecured" → Business Loan Unsecured, Personal Loan, CGTMSE, Working Capital CC/OD
- "both" → if policy explicitly covers both secured and unsecured

lender_type (detect from name or context):
- "bank" → SBI, HDFC Bank, ICICI Bank, Axis Bank, Kotak, Yes Bank, PNB etc.
- "hfc" → HDFC Ltd, LIC HFC, Indiabulls Housing, PNB Housing, REPCO, Truhome, Can Fin etc.
- "sfb" → Jana SFB, AU Small Finance, Ujjivan, ESAF, Suryoday etc.
- "nbfc" → everything else (Bajaj, L&T, Poonawalla, Shriram, Nido, Jio Credit etc.)

processing fees:
- "PF 1%" → processing_fee_pct=1.0
- "PF Rs.5000" or flat fee → processing_fee_flat=5000
- "Login fee / IMD 1180" → imd_charges_text="1180"

FOIR:
- "FOIR upto 65%" → max_foir_pct=65
- "Higher FOIR program upto 80%" → foir_relaxation_available=1, foir_relaxed_pct=80

CIBIL policy:
- "Min CIBIL 700" → min_cibil=700, cibil_policy="strict"
- "No minimum CIBIL" or "No bureau" → cibil_policy="no_bureau", min_cibil=0
- "Case to case" or "Flexible CIBIL" → cibil_policy="case_to_case", min_cibil=0
- "Old DPD accepted" → negative_bureau_allowed=1

Income type flags (set 1 if accepted, 0 if not):
- salaried_allowed: 1 if "Salaried" or "Bank Salaried" mentioned
- senp_allowed: 1 if "SENP", "Self Employed", "Trader", "Vyapari" mentioned
- sep_allowed: 1 if "SEP", "Professional", "Doctor", "CA", "Architect" mentioned
- nip_allowed: 1 if "No Income Proof", "NIP", "Assessment Based" mentioned
- cash_income_allowed: 1 if "Cash Income", "Cash Salaried", "Cash Business" mentioned
- co_applicant_income_clubbing: 1 if "joint income", "co-applicant income clubbing" mentioned
- Default: salaried_allowed=1, senp_allowed=1, sep_allowed=1 unless explicitly restricted

Surrogate flags (set 1 if available, 0 if not):
- surrogate_banking: "Banking Surrogate", "Bank Statement", "ABB program"
- surrogate_gst: "GST Surrogate", "GST Program", "GST turnover"
- surrogate_itr: "ITR Program", "ITR based", "1 year ITR"
- surrogate_rtr: "RTR", "Rent to Return", "RTR Surrogate"
- surrogate_gross_profit: "Gross Profit", "GP Program", "Cash Profit"
- surrogate_low_ltv: "Low LTV Program", "LTV Surrogate"
- surrogate_lip: "LIP", "Low Income Program", "LIP x3"

city_coverage enum:
- "jaipur_only" → only Jaipur mentioned
- "rajasthan" → Jaipur + other Rajasthan cities, or "All Rajasthan locations"
- "pan_india" → "PAN India", "All India", "All locations"
- "specific_cities" → specific non-Rajasthan cities listed

Property type flags (1=allowed, 0=not):
- property_sorp_allowed: residential, flat, house, villa, SORP
- property_socp_allowed: commercial, shop, office, SOCP
- property_soip_allowed: industrial, factory, SOIP, karkhana
- property_rented_allowed: rented, rental, vacant, LRD
- property_land_allowed: plot, land, open land, agricultural plot

Title flags (1=accepted):
- title_jda: JDA, Jaipur Development Authority
- title_rhb: RHB, Housing Board, HB
- title_society_patta: Society Patta (also set title_society_patta_year if year mentioned)
- title_gram_panchayat: GP, Gram Panchayat, Panchayat Patta, village property
- title_nagar_nigam: Nagar Nigam, Nagar Palika, Municipal
- title_riico: RIICO, Rico, industrial estate
- title_agriculture: Agriculture, agricultural land, farm
- title_freehold: Freehold, Free Hold, registry

rental_income_considered: 1 if "rental income considered", "LRD", "kiraya income allowed"

Unsecured-only fields (fill only if loan_category=unsecured or both):
- min_business_vintage_years: "minimum 3 years business" → 3
- gst_registration_required: 1 if GST mandatory, 0 if not required, null if not mentioned
- min_annual_turnover_lakh: "minimum turnover 50L" → 50
- min_avg_bank_balance: "ABB minimum 50000" → 50000
- min_bank_statement_months: "6 months bank statement" → 6
- unsecured_max_foir: FOIR cap specific to unsecured
- unsecured_bureau_min_cibil: CIBIL minimum specific to unsecured product
- overdraft_available: 1 if OD/overdraft facility mentioned
- working_capital_available: 1 if working capital loan mentioned

ltv_sorp / ltv_socp / ltv_soip / ltv_rented / ltv_land:
These MUST match the ltv_matrix values exactly.
ltv_sorp = ltv_matrix.sorp, ltv_socp = ltv_matrix.socp, etc.
ltv_max_overall = highest non-null value across all LTV fields.


═══════════════════════════════════════════════
RESIDUAL EXTRACTION — SUGGESTED SPECIAL CONDITIONS
═══════════════════════════════════════════════
After filling ALL structured fields above, re-read the original policy text.
Find anything that:
  - Was NOT captured in any structured field above
  - Is relevant to loan eligibility, property, income, credit, or borrower profile
  - Represents a lender flexibility, exception, or USP unique to this lender

DO NOT include: PF sharing, payout, DSA commission, contact details, greetings.
DO NOT repeat anything already in programs or property_allowed.
IMPORTANT: Items already placed in special_profiles MUST also appear here as structured suggestions.
special_profiles is unstructured text — suggested_special_conditions is the structured version of the same data.
Always convert special_profiles items into properly categorised suggestions.

For each leftover item return:
{
  "category": one of: property_flexibility | income_flexibility |
              credit_flexibility | structural_flexibility | profile_flexibility,
  "flag_name": "snake_case_identifier",
  "condition_text": "qualifier or threshold if mentioned, else null",
  "verbatim": "exact original text word for word"
}

These are SUGGESTIONS only — the human will review and confirm each one.
If nothing leftover return: "suggested_special_conditions": []

RETURN ONLY VALID JSON. NO EXPLANATION. NO MARKDOWN.
PROMPT;
    return $prompt;
}


function callOpenAIStructured(string $sys, string $user): ?array {
    $apiKey = getenv('WM_CLAUDE_KEY');
    if (!$apiKey) return null;
    $model = 'claude-sonnet-4-6';
    $payload = [
        'model'      => $model,
        'max_tokens' => 4000,
        'system'     => $sys . "\n\nIMPORTANT: Return ONLY valid JSON. No markdown, no explanation, no code fences.",
        'messages'   => [['role'=>'user','content'=>"Parse:\n\n".$user]],
    ];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>120,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey,'anthropic-version: 2023-06-01']]);
    $t0=microtime(true); $raw=curl_exec($ch); $ms=(int)round((microtime(true)-$t0)*1000);
    $curlErr=curl_error($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    $data=json_decode($raw??'',true)??[];
    $usage=$data['usage']??[];
    $pTok=(int)($usage['input_tokens']??0); $cTok=(int)($usage['output_tokens']??0);
    // Claude Sonnet 4.6 pricing: $3/1M input, $15/1M output
    $costUsd=round(($pTok*0.000003)+($cTok*0.000015),8);
    $status=($code===200&&$raw&&!$curlErr)?'success':($curlErr?'timeout':'error');
    try { getDB()->prepare("INSERT INTO llm_call_logs (call_source,model,prompt_tokens,completion_tokens,total_tokens,latency_ms,status,cost_usd,prompt_preview) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute(['policy_parser',$model,$pTok?:null,$cTok?:null,($pTok+$cTok)?:null,$ms,$status,$costUsd?:null,substr($user,0,300)]); } catch(\Throwable $e){}
    if($code!==200) return null;
    $text = $data['content'][0]['text'] ?? '{}';
    $text = preg_replace('/^```(?:json)?\s*/i','',$text);
    $text = preg_replace('/\s*```$/i','',$text);
    $text = trim($text);
    $decoded = json_decode($text, true);
    return $decoded;
}


function buildLenderChunks(array $l, array $specialConditions = []): array {
    $name    = $l['lender_name'] ?? 'Unknown';
    $product = $l['product'] ?? $l['product_type'] ?? '';
    $chunks  = [];
    $roiStr = ($l['roi_min'] ?? '') ? ($l['roi_min'].'%'.($l['roi_max'] ? ' to '.$l['roi_max'].'%' : '+')) : '';
    $amtStr = ($l['loan_min_lakh'] ?? '') ? ('Rs.'.($l['loan_min_lakh']).' to Rs.'.($l['loan_max_lakh'] ?? '').' lakh') : '';
    $chunks[] = ['type'=>'core','text'=>implode(' | ', array_filter([
        "{$name} {$product} loan",
        ($l['lender_type']??'')       ? "Type: {$l['lender_type']}"             : '',
        ($l['loan_category']??'')     ? "Category: {$l['loan_category']}"       : '',
        $roiStr                       ? "ROI: {$roiStr}"                         : '',
        $amtStr                       ? "Loan amount: {$amtStr}"                 : '',
        ($l['max_tenure_months']??'') ? "Max tenure: {$l['max_tenure_months']} months" : '',
        ($l['foreclosure_charges']??'') ? "Foreclosure: {$l['foreclosure_charges']}" : '',
        ($l['programs']??'')          ? "Programs: {$l['programs']}"            : '',
    ]))];
    $surrogates = array_filter([
        intval($l['surrogate_banking']??0)      ? 'Banking surrogate'      : '',
        intval($l['surrogate_gst']??0)          ? 'GST surrogate'          : '',
        intval($l['surrogate_itr']??0)          ? 'ITR surrogate'          : '',
        intval($l['surrogate_rtr']??0)          ? 'RTR surrogate'          : '',
        intval($l['surrogate_gross_profit']??0) ? 'Gross profit surrogate' : '',
        intval($l['surrogate_low_ltv']??0)      ? 'Low LTV surrogate'      : '',
        intval($l['surrogate_lip']??0)          ? 'LIP surrogate'          : '',
    ]);
    $incomeTypes = array_filter([
        intval($l['salaried_allowed']??1)    ? 'Salaried'                    : '',
        intval($l['senp_allowed']??1)        ? 'SENP trader manufacturer'    : '',
        intval($l['sep_allowed']??1)         ? 'SEP CA Doctor Architect'     : '',
        intval($l['nip_allowed']??0)         ? 'NIP no income proof'         : '',
        intval($l['cash_income_allowed']??0) ? 'Cash income informal income' : '',
    ]);
    $chunks[] = ['type'=>'eligibility','text'=>implode(' | ', array_filter([
        "{$name} {$product} eligibility criteria bureau CIBIL FOIR",
        ($l['min_cibil']??'')    ? "Min CIBIL: {$l['min_cibil']}"       : 'No minimum CIBIL',
        ($l['cibil_policy']??'') ? "CIBIL policy: {$l['cibil_policy']}" : '',
        intval($l['negative_bureau_allowed']??0) ? 'Negative bureau old DPD cases accepted' : '',
        (($l['max_dpd_allowed']??'')!=='')   ? "Max DPD: {$l['max_dpd_allowed']}"   : '',
        ($l['max_foir_pct']??'')             ? "Max FOIR: {$l['max_foir_pct']}%"    : '',
        intval($l['foir_relaxation_available']??0) ? "FOIR relaxation up to {$l['foir_relaxed_pct']}%" : '',
        $incomeTypes ? "Income types: ".implode(', ',$incomeTypes) : '',
        intval($l['co_applicant_income_clubbing']??0) ? 'Co-applicant income clubbing allowed' : '',
        $surrogates  ? "Surrogate programs: ".implode(', ',$surrogates) : '',
        ($l['special_profiles']??'') ? "Special profiles: {$l['special_profiles']}" : '',
    ]))];
    $ltvParts = array_filter([
        ($l['ltv_sorp']??'')   ? "SORP: {$l['ltv_sorp']}%"          : '',
        ($l['ltv_socp']??'')   ? "SOCP: {$l['ltv_socp']}%"          : '',
        ($l['ltv_soip']??'')   ? "Industrial SOIP: {$l['ltv_soip']}%" : '',
        ($l['ltv_rented']??'') ? "Rented Vacant: {$l['ltv_rented']}%" : '',
        ($l['ltv_land']??'')   ? "Land Plot: {$l['ltv_land']}%"      : '',
    ]);
    $titleParts = array_filter([
        intval($l['title_jda']??0)            ? 'JDA Jaipur Development Authority'  : '',
        intval($l['title_rhb']??0)            ? 'RHB Rajasthan Housing Board'       : '',
        intval($l['title_society_patta']??0)  ? 'Society Patta'.($l['title_society_patta_year'] ? " upto {$l['title_society_patta_year']}" : '') : '',
        intval($l['title_gram_panchayat']??0) ? 'Gram Panchayat village property'   : '',
        intval($l['title_nagar_nigam']??0)    ? 'Nagar Nigam Municipal Corporation' : '',
        intval($l['title_riico']??0)          ? 'RIICO industrial estate'           : '',
        intval($l['title_agriculture']??0)    ? 'Agriculture land'                  : '',
        intval($l['title_freehold']??0)       ? 'Freehold registry'                 : '',
    ]);
    $propTypes = array_filter([
        intval($l['property_sorp_allowed']??1)       ? 'Residential self-occupied SORP'        : '',
        intval($l['property_socp_allowed']??0)       ? 'Commercial self-occupied SOCP'         : '',
        intval($l['property_soip_allowed']??0)       ? 'Industrial self-occupied SOIP factory' : '',
        intval($l['property_rented_allowed']??0)     ? 'Rented Vacant property LRD'            : '',
        intval($l['property_land_allowed']??0)       ? 'Land Plot open'                        : '',
        intval($l['property_under_construction']??0) ? 'Under construction'                    : '',
    ]);
    $chunks[] = ['type'=>'property','text'=>implode(' | ', array_filter([
        "{$name} {$product} property LTV title policy",
        ($l['max_ltv']??'') ? "Max LTV: {$l['max_ltv']}%"                         : '',
        $ltvParts           ? "LTV by type: ".implode(', ',$ltvParts)              : '',
        $propTypes          ? "Property types: ".implode(', ',$propTypes)          : '',
        $titleParts         ? "Title types: ".implode(', ',$titleParts)            : '',
        ($l['property_allowed']??'') ? "Property notes: {$l['property_allowed']}" : '',
        intval($l['rental_income_considered']??0) ? 'Rental income LRD considered' : '',
    ]))];
    $chunks[] = ['type'=>'geography','text'=>implode(' | ', array_filter([
        "{$name} {$product} geography location coverage city",
        ($l['city_allowed']??$l['city']??'') ? "Cities: ".($l['city_allowed']??$l['city']) : '',
        ($l['city_coverage']??'') ? "Coverage: {$l['city_coverage']}" : '',
        ($l['geo_limit']??'')     ? "Geo limit: {$l['geo_limit']}"    : '',
        ($l['geo_limit_km']??'')  ? "Radius: {$l['geo_limit_km']} KM" : '',
    ]))];
    $chunks[] = ['type'=>'surrogate','text'=>implode(' | ', array_filter([
        "{$name} {$product} surrogate alternate income program",
        intval($l['surrogate_banking']??0)      ? 'Banking surrogate bank statement ABB' : '',
        intval($l['surrogate_gst']??0)          ? 'GST surrogate turnover based income'  : '',
        intval($l['surrogate_itr']??0)          ? 'ITR based income program'             : '',
        intval($l['surrogate_rtr']??0)          ? 'RTR rent to return surrogate'         : '',
        intval($l['surrogate_gross_profit']??0) ? 'Gross profit cash profit surrogate'   : '',
        intval($l['surrogate_low_ltv']??0)      ? 'Low LTV program'                      : '',
        intval($l['surrogate_lip']??0)          ? 'LIP low income program'               : '',
        intval($l['nip_allowed']??0)            ? 'NIP no income proof assessment based' : '',
        intval($l['cash_income_allowed']??0)    ? 'Cash income informal income accepted' : '',
        ($l['programs']??'') ? "All programs: {$l['programs']}" : '',
    ]))];
    $chunks[] = ['type'=>'process','text'=>implode(' | ', array_filter([
        "{$name} {$product} fees TAT processing contact",
        ($l['processing_fee_pct']??'')   ? "Processing fee: {$l['processing_fee_pct']}%"  : '',
        ($l['processing_fee_flat']??'')  ? "Flat fee: Rs.{$l['processing_fee_flat']}"      : '',
        ($l['imd_charges_text']??'')     ? "IMD login fee: {$l['imd_charges_text']}"       : '',
        ($l['foreclosure_charges']??'')  ? "Foreclosure: {$l['foreclosure_charges']}"      : '',
        ($l['prepayment_charges']??'')   ? "Prepayment: {$l['prepayment_charges']}"        : '',
        ($l['typical_tat_days']??'')     ? "TAT: {$l['typical_tat_days']} days"            : '',
        ($l['sales_manager_name']??'')   ? "SM: {$l['sales_manager_name']}"                : '',
        ($l['sales_manager_mobile']??'') ? "SM Mobile: {$l['sales_manager_mobile']}"       : '',
        ($l['notes']??'') ? "Notes: ".mb_substr($l['notes'],0,300) : '',
    ]))];

    // Chunk 7: special_conditions
    if (!empty($specialConditions)) {
        $scParts = [];
        foreach ($specialConditions as $sc) {
            $scParts[] = "[{$sc['category']}] {$sc['flag_name']}: {$sc['verbatim_text']}" .
                         (!empty($sc['condition_text']) ? " ({$sc['condition_text']})" : '');
        }
        $scText = "{$name} {$product} special conditions flexibility exceptions\n" . implode("\n", $scParts);
        $chunks[] = ['type' => 'special_conditions', 'text' => $scText];
    }

    return array_filter($chunks, fn($c) => strlen(trim($c['text'])) > 20);
}

function syncToQdrant(PDO $pdo, int $id): bool {
    if (!ragEnabled() || !openAiEnabled()) return false;
    $stmt = $pdo->prepare("SELECT * FROM lenders_products WHERE id=?");
    $stmt->execute([$id]);
    $l = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$l) return false;
    $scStmt = $pdo->prepare("SELECT category, flag_name, condition_text, verbatim_text FROM lender_special_conditions WHERE lender_product_id = ? AND is_confirmed = 1 ORDER BY category, id");
    $scStmt->execute([$id]);
    $specialConditions = $scStmt->fetchAll(PDO::FETCH_ASSOC);
    $chunks = buildLenderChunks($l, $specialConditions);
    if (empty($chunks)) return false;
    $ch0 = curl_init(rtrim(QDRANT_URL,'/').'/collections/lender_policies/points/'.$id);
    curl_setopt_array($ch0,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>'DELETE',CURLOPT_TIMEOUT=>10,CURLOPT_HTTPHEADER=>['Content-Type: application/json','api-key: '.QDRANT_API_KEY]]);
    curl_exec($ch0); curl_close($ch0);
    $ch1 = curl_init(rtrim(QDRANT_URL,'/').'/collections/lender_policies/points/delete');
    curl_setopt_array($ch1,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>'POST',
        CURLOPT_POSTFIELDS=>json_encode(['filter'=>['must'=>[['key'=>'lender_id','match'=>['value'=>$id]]]]]),
        CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['Content-Type: application/json','api-key: '.QDRANT_API_KEY]]);
    curl_exec($ch1); curl_close($ch1);
    $richPayload = [
        'lender_id'=>$id,'lender_name'=>$l['lender_name']??'',
        'product'=>$l['product']??$l['product_type']??'',
        'lender_type'=>$l['lender_type']??'','loan_category'=>$l['loan_category']??'',
        'roi_min'=>(float)($l['roi_min']??0),'roi_max'=>(float)($l['roi_max']??0),
        'loan_min_lakh'=>(float)($l['loan_min_lakh']??0),'loan_max_lakh'=>(float)($l['loan_max_lakh']??0),
        'max_tenure_months'=>(int)($l['max_tenure_months']??0),
        'max_ltv'=>(float)($l['max_ltv']??0),'ltv_sorp'=>(float)($l['ltv_sorp']??0),
        'ltv_socp'=>(float)($l['ltv_socp']??0),'ltv_soip'=>(float)($l['ltv_soip']??0),
        'ltv_rented'=>(float)($l['ltv_rented']??0),'ltv_land'=>(float)($l['ltv_land']??0),
        'min_cibil'=>(int)($l['min_cibil']??0),'cibil_policy'=>$l['cibil_policy']??'',
        'negative_bureau_allowed'=>(int)($l['negative_bureau_allowed']??0),
        'max_dpd_allowed'=>(($l['max_dpd_allowed']??'')!==''&&$l['max_dpd_allowed']!==null)?(int)$l['max_dpd_allowed']:null,
        'max_foir_pct'=>(int)($l['max_foir_pct']??0),
        'foir_relaxation_available'=>(int)($l['foir_relaxation_available']??0),
        'foir_relaxed_pct'=>(int)($l['foir_relaxed_pct']??0),
        'city_coverage'=>$l['city_coverage']??'','city_allowed'=>$l['city_allowed']??$l['city']??'',
        'geo_limit'=>$l['geo_limit']??'',
        'geo_limit_km'=>(($l['geo_limit_km']??'')!==''&&$l['geo_limit_km']!==null)?(int)$l['geo_limit_km']:null,
        'salaried_allowed'=>(int)($l['salaried_allowed']??1),'senp_allowed'=>(int)($l['senp_allowed']??1),
        'sep_allowed'=>(int)($l['sep_allowed']??1),'nip_allowed'=>(int)($l['nip_allowed']??0),
        'cash_income_allowed'=>(int)($l['cash_income_allowed']??0),
        'surrogate_banking'=>(int)($l['surrogate_banking']??0),'surrogate_gst'=>(int)($l['surrogate_gst']??0),
        'surrogate_itr'=>(int)($l['surrogate_itr']??0),'surrogate_rtr'=>(int)($l['surrogate_rtr']??0),
        'surrogate_gross_profit'=>(int)($l['surrogate_gross_profit']??0),
        'surrogate_low_ltv'=>(int)($l['surrogate_low_ltv']??0),'surrogate_lip'=>(int)($l['surrogate_lip']??0),
        'property_sorp_allowed'=>(int)($l['property_sorp_allowed']??1),
        'property_socp_allowed'=>(int)($l['property_socp_allowed']??0),
        'property_soip_allowed'=>(int)($l['property_soip_allowed']??0),
        'property_rented_allowed'=>(int)($l['property_rented_allowed']??0),
        'property_land_allowed'=>(int)($l['property_land_allowed']??0),
        'property_under_construction'=>(int)($l['property_under_construction']??0),
        'rental_income_considered'=>(int)($l['rental_income_considered']??0),
        'title_jda'=>(int)($l['title_jda']??0),'title_rhb'=>(int)($l['title_rhb']??0),
        'title_society_patta'=>(int)($l['title_society_patta']??0),
        'title_gram_panchayat'=>(int)($l['title_gram_panchayat']??0),
        'title_nagar_nigam'=>(int)($l['title_nagar_nigam']??0),
        'title_riico'=>(int)($l['title_riico']??0),'title_agriculture'=>(int)($l['title_agriculture']??0),
        'title_freehold'=>(int)($l['title_freehold']??0),
        'programs'=>$l['programs']??'','special_profiles'=>$l['special_profiles']??'',
        'foreclosure_charges'=>$l['foreclosure_charges']??'','property_allowed'=>$l['property_allowed']??'',
        'income_types'=>$l['income_types']??'','notes'=>mb_substr($l['notes']??'',0,500),
        'sales_manager_name'=>$l['sales_manager_name']??'','sales_manager_mobile'=>$l['sales_manager_mobile']??'',
        'last_updated'=>date('Y-m-d H:i:s'),
    ];
    $points=[]; $baseId=$id*100; $allOk=true;
    foreach ($chunks as $i => $chunk) {
        $embedding = generateQdrantEmbedding($chunk['text']);
        if (!$embedding) { $allOk=false; continue; }
        $points[] = ['id'=>$baseId+$i,'vector'=>$embedding,
            'payload'=>array_merge($richPayload,['chunk_type'=>$chunk['type'],'text_preview'=>mb_substr($chunk['text'],0,300)])];
    }
    if (empty($points)) return false;
    $ch = curl_init(rtrim(QDRANT_URL,'/').'/collections/lender_policies/points?wait=true');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>'PUT',
        CURLOPT_POSTFIELDS=>json_encode(['points'=>$points]),CURLOPT_TIMEOUT=>120,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json','api-key: '.QDRANT_API_KEY]]);
    $raw=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code!==200) { error_log('[WealthMetre] Qdrant upsert failed: '.$raw); $allOk=false; }
    $pdo->prepare("UPDATE lenders_products SET updated_at=NOW() WHERE id=?")->execute([$id]);
    return $allOk;
}

function generateQdrantEmbedding(string $text): ?array {
    $payload = ['model'=>'text-embedding-3-small','input'=>mb_substr($text,0,8000)];
    $ch = curl_init('https://api.openai.com/v1/embeddings');
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>120,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.OPENAI_API_KEY]
    ]);
    $raw=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if($code!==200) return null;
    $data=json_decode($raw,true);
    return $data['data'][0]['embedding']??null;
}

function makeSlug(string $s): string {
    $s=strtolower(trim($s));
    $s=preg_replace('/[^a-z0-9\s-]/','', $s);
    $s=preg_replace('/[\s-]+/','-',$s);
    return trim($s,'-');
}
if($action === 'pre_parse'){
    $body    = json_decode(file_get_contents('php://input'),true)??[];
    $rawText = trim($body['raw_text']??'');
    if(!$rawText){ echo json_encode(['success'=>false,'error'=>'No text']); exit; }

    $stmt    = $pdo->query("SELECT id,lender_name,product FROM lenders_products WHERE status='active' ORDER BY lender_name");
    $existing= $stmt->fetchAll(PDO::FETCH_ASSOC);
    $lj      = json_encode(array_map(fn($l)=>['id'=>$l['id'],'name'=>$l['lender_name'],'product'=>$l['product']],$existing));

    $prePrompt = <<<PROMPT
You are a loan policy pre-analyzer for WealthMetre, an Indian loan advisory platform.

EXISTING LENDERS IN DB:
{$lj}

Analyze the policy text below. Identify ONLY fields where your confidence is below 85%.
Return confident fields separately — do NOT ask about things you are sure of.

FIELDS TO CHECK FOR UNCERTAINTY:
1. product — Is the loan product (Home Loan/LAP/Business Loan etc.) EXPLICITLY stated in the text? If only implied by context, flag it.
2. roi — Is the ROI a general headline rate OR found only inside a specific program (NIP/surrogate/BT/banking)? If program-specific only, flag it.
3. lender_match — Does the lender name match an existing DB entry with >85% certainty? If ambiguous, flag it.
4. decision — new_lender / new_product / policy_update / duplicate. Flag if unsure.
5. city_coverage — Is coverage clearly rajasthan / specific_cities / pan_india? Flag if ambiguous.
6. income_types — Are salaried/SENP/SEP/NIP explicitly named or just implied?
7. surrogate_programs — Are banking/GST/ITR surrogates explicitly offered as a program or just mentioned in passing?
8. ltv_structure — Is LTV a single flat value or a tiered structure by property type or income slab?

CONFIDENCE RULE: If >85% sure → put in confident_fields. If <85% → put in clarifications.

Return ONLY this JSON (no explanation, no markdown):
{
  "clarifications": [
    {
      "field": "product",
      "label": "Loan Product",
      "question": "Product not explicitly named. What is this policy for?",
      "guess_1_label": "Home Loan",
      "guess_1_value": "Home Loan",
      "guess_1_reason": "Mentions salaried LTV slabs by income, 20yr tenure, house purchase norms",
      "guess_2_label": "LAP",
      "guess_2_value": "LAP",
      "guess_2_reason": "Mentions P+C, top-up, BT — common LAP terms",
      "allow_custom": true,
      "custom_placeholder": "e.g. Business Loan - Secured",
      "confidence": 62
    }
  ],
  "confident_fields": {
    "lender_name": "IDFC First Bank",
    "max_tenure_months": 240
  },
  "summary": "2 fields need your confirmation before parsing."
}

NOTES:
- guess_2_label/value/reason are optional — omit if only one alternative exists
- For roi uncertainty: guess_1 should be null (recommended), guess_2 the program value found
- For lender_match: show matched DB name as guess_1, "New Lender" as guess_2
- confidence is your % certainty for guess_1 being correct
PROMPT;

    $clarifyApiKey = getenv('WM_CLAUDE_KEY');
    $clarifyModel  = 'claude-haiku-4-5-20251001';
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$clarifyApiKey,'anthropic-version: 2023-06-01'],
        CURLOPT_POSTFIELDS=>json_encode([
            'model'      => $clarifyModel,
            'max_tokens' => 1200,
            'system'     => $prePrompt . "\n\nIMPORTANT: Return ONLY valid JSON. No markdown, no code fences.",
            'messages'   => [['role'=>'user','content'=>$rawText]],
        ])
    ]);
    $t0=microtime(true); $resp=curl_exec($ch); $ms2=(int)round((microtime(true)-$t0)*1000);
    $curlErr2=curl_error($ch); $code2=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    $parsed=json_decode($resp??'',true)??[];
    $usage2=$parsed['usage']??[];
    $pTok2=(int)($usage2['input_tokens']??0); $cTok2=(int)($usage2['output_tokens']??0);
    $costUsd2=round(($pTok2*0.0000008)+($cTok2*0.000004),8);
    $status2=($code2===200&&$resp&&!$curlErr2)?'success':($curlErr2?'timeout':'error');
    try { getDB()->prepare("INSERT INTO llm_call_logs (call_source,model,prompt_tokens,completion_tokens,total_tokens,latency_ms,status,cost_usd,prompt_preview) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute(['policy_parser_clarify',$clarifyModel,$pTok2?:null,$cTok2?:null,($pTok2+$cTok2)?:null,$ms2,$status2,$costUsd2?:null,substr($rawText??'',0,300)]); } catch(\Throwable $e){}
    $text=$parsed['content'][0]['text']??'{}';
    $text=preg_replace('/^```(?:json)?\s*/i','',$text);
    $text=preg_replace('/\s*```$/i','',$text);
    $result=json_decode(trim($text),true)??['clarifications'=>[],'confident_fields'=>[],'summary'=>''];
    echo json_encode(['success'=>true,'result'=>$result]); exit;
}


