<?php
require_once __DIR__ . '/config.php';
$partner = requireAuth();
$pid = (int)$partner['id'];
$body = getBody();
$act = $_GET['action'] ?? ($body['action'] ?? '');

if ($act === 'create') {
    $db = getDB();
    $name = $body['customer_name'] ?? '';
    $mob  = $body['customer_mobile'] ?? '';
    $city = $body['city'] ?? '';
    $lt   = $body['loan_type'] ?? '';
    $amt  = (int)($body['amount'] ?? 0);
    $inc  = (int)($body['monthly_income'] ?? 0);
    $cib  = (int)($body['cibil'] ?? 0);
    $src  = $body['source'] ?? 'portal';
    $pri  = $body['priority'] ?? 'medium';
    $prop_title    = $body['property_title'] ?? '';
    $prop_usage    = $body['property_usage'] ?? '';
    $prop_location = $body['property_location'] ?? '';
    $valuation     = (int)($body['valuation'] ?? 0);
    $income_type   = $body['income_type'] ?? '';
    $occupation    = $body['occupation'] ?? '';
    $existing_emi  = (int)($body['existing_emi'] ?? 0);
    $remarks       = $body['remarks'] ?? '';
    $stmt = $db->prepare("INSERT INTO leads (partner_id,customer_name,mobile,customer_mobile,city,loan_type,loan_amount,amount,income,monthly_income,cibil,source,priority,status,property_title,property_usage,property_location,valuation,income_type,occupation,existing_emi,remarks,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'new',?,?,?,?,?,?,?,?,NOW())");
    $stmt->bind_param('isssssiiiiisssssisiis', $pid,$name,$mob,$mob,$city,$lt,$amt,$amt,$inc,$inc,$cib,$src,$pri,$prop_title,$prop_usage,$prop_location,$valuation,$income_type,$occupation,$existing_emi,$remarks);
    $stmt->execute();
    $lid = $db->insert_id;
    jsonOk(['lead_id'=>$lid,'message'=>'Lead saved']);
}

if ($act === 'list') {
    $db = getDB();
    $s  = $db->prepare("SELECT id AS lead_id, id, partner_id, customer_name, COALESCE(customer_mobile,mobile) AS customer_mobile, city, loan_type, COALESCE(amount,loan_amount) AS amount, COALESCE(monthly_income,income) AS monthly_income, cibil, source, priority, status, next_action, next_action_date, created_at, updated_at FROM leads WHERE partner_id=? ORDER BY created_at DESC LIMIT 100");
    $s->bind_param('i',$pid);
    $s->execute();
    $leads = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    jsonOk(['leads'=>$leads,'total'=>count($leads)]);
}

if ($act === 'get') {
    $lid = (int)($_GET['lead_id'] ?? $body['lead_id'] ?? 0);
    $db  = getDB();
    $s   = $db->prepare("SELECT id AS lead_id, id, partner_id, customer_name, COALESCE(customer_mobile,mobile) AS customer_mobile, city, loan_type, COALESCE(amount,loan_amount) AS amount, COALESCE(monthly_income,income) AS monthly_income, cibil, source, priority, status, next_action, next_action_date, created_at, updated_at, property_title, property_type, property_usage, property_location, valuation, income_type, occupation, existing_emi, remarks, notes FROM leads WHERE id=? AND partner_id=? LIMIT 1");
    $s->bind_param('ii',$lid,$pid);
    $s->execute();
    $lead = $s->get_result()->fetch_assoc();
    if (!$lead) jsonError('Lead not found',404);
    jsonOk(['lead'=>$lead,'lender_results'=>[]]);
}

if ($act === 'save_results') {
    $lid     = (int)($body['lead_id'] ?? 0);
    $lenders = $body['lenders'] ?? [];
    if (!$lid) jsonError('lead_id required');
    jsonOk(['message'=>'Results saved','count'=>count($lenders)]);
}

if ($act === 'update') {
    $lid = (int)($body['lead_id'] ?? 0);
    if (!$lid) jsonError('lead_id required');
    $db = getDB();
    $fields = ['property_title','property_usage','property_location','income_type','occupation','remarks'];
    $intFields = ['valuation','existing_emi','monthly_income','cibil','amount'];
    $sets=[]; $vals=[]; $types='';
    foreach($fields as $f2) {
        if (isset($body[$f2])) { $sets[]="$f2=?"; $vals[]=$body[$f2]; $types.='s'; }
    }
    foreach($intFields as $f2) {
        if (isset($body[$f2])) { $sets[]="$f2=?"; $vals[]=(int)$body[$f2]; $types.='i'; }
    }
    if (!$sets) jsonError('No fields to update');
    $sets[]='updated_at=NOW()';
    $vals[]=$lid; $vals[]=$pid; $types.='ii';
    $s=$db->prepare("UPDATE leads SET ".implode(',',$sets)." WHERE id=? AND partner_id=?");
    $s->bind_param($types,...$vals);
    $s->execute();
    jsonOk(['message'=>'Lead updated']);
}

if ($act === 'update_status') {
    $lid = (int)($body['lead_id'] ?? 0);
    $st  = $body['status'] ?? '';
    $db  = getDB();
    $s   = $db->prepare("UPDATE leads SET status=?,updated_at=NOW() WHERE id=? AND partner_id=?");
    $s->bind_param('sii',$st,$lid,$pid);
    $s->execute();
    jsonOk(['message'=>'Status updated']);
}

if ($act === 'update_next_action') {
    $lid = (int)($body['lead_id'] ?? 0);
    $na  = $body['next_action'] ?? '';
    $nd  = $body['next_action_date'] ?? '';
    $db  = getDB();
    $s   = $db->prepare("UPDATE leads SET next_action=?,next_action_date=?,updated_at=NOW() WHERE id=? AND partner_id=?");
    $s->bind_param('ssii',$na,$nd,$lid,$pid);
    $s->execute();
    jsonOk(['message'=>'Saved']);
}

if ($act === 'save_results') { jsonOk(); }
jsonError('Invalid action');
