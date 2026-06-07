<?php
/**
 * WealthMetre – hybrid_query.php
 * Main AI loan matching engine.
 *
 * FLOW:
 *  1. Parse profile from POST / GET
 *  2. SQL search via lender_search.php (real lenders_products table)
 *  3. Score + rank every lender using customer profile
 *  4. Build personalised "why recommended" text per lender
 *  5. RAG enrichment — only if Qdrant is configured
 *  6. Return top-5 result cards
 *
 * NOTE: getCityRegion / cityToState live in lender_search.php only.
 *       No duplicate function names across files.
 *
 * METHOD: POST { profile } OR GET ?q=...
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lender_search.php';   // provides: searchLenders(), normalizeLenderRow(), cityToState(), loanTypeToKeywords()

if (basename($_SERVER['PHP_SELF'] ?? '') === 'hybrid_query.php') {
// ── Route ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body      = getJsonBody();
    $profile   = is_array($body['profile'] ?? null) ? $body['profile'] : $body;
    $freeQuery = sanitize($body['q'] ?? $body['query'] ?? '');
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $freeQuery = sanitize($_GET['q'] ?? $_GET['query'] ?? '');
    $profile   = buildProfileFromGET($_GET);
} else {
    jsonError('POST or GET required', 405);
}

jsonResponse(runHybrid($profile, $freeQuery));
}


// ═══════════════════════════════════════════════════════════
//  MAIN ORCHESTRATOR
// ═══════════════════════════════════════════════════════════
function runHybrid(array $profile, string $freeQuery = ''): array {

    // Merge free-text parse into profile (profile wins on conflict)
    if ($freeQuery) {
        $parsed  = parseNL($freeQuery);
        $profile = array_merge($parsed, array_filter($profile));
    }

    // ── Normalise loan type ───────────────────────────────
    $loanType = normalizeLoanType($profile['loan_type'] ?? '');
    if (!$loanType) $loanType = normalizeLoanType($freeQuery);
    if (!$loanType) $loanType = 'lap';

    // ── Normalise numeric fields ──────────────────────────
    $cibil  = intSafe($profile['cibil_score']      ?? $profile['cibil']        ?? 0);
    $income = intSafe($profile['monthly_income']   ?? $profile['income']       ?? 0);
    $emi    = intSafe($profile['existing_emis']    ?? $profile['existing_emi'] ?? $profile['emi'] ?? 0);
    $amount = intSafe($profile['loan_amount']      ?? $profile['amount']       ?? 0);
    $val    = intSafe($profile['property_valuation']?? 0);

    // ── Normalise text fields (strip chip UI labels) ──────
    $propRaw   = strtolower(trim($profile['property_title'] ?? $profile['property_type'] ?? ''));
    $propClean = cleanChipLabel($propRaw);   // "JDA (Jaipur...)" → "jda"

    $occRaw    = strtolower(trim($profile['occupation'] ?? ''));
    $occ       = normaliseOccupation($occRaw);  // "Salaried – Government / PSU" → "govt"

    $incomeRaw = strtolower(trim($profile['income_source'] ?? ''));
    $incSrc    = normaliseOccupation($incomeRaw) ?: $occ;

    $propUsage    = strtolower(trim($profile['property_usage'] ?? 'residential'));
    $city         = strtolower(trim($profile['city'] ?? DEFAULT_CITY));
    $loanCategory = strtolower(trim($profile['loan_category'] ?? 'secured'));
    $isUnsecured  = ($loanCategory === 'unsecured');

    // ── Derived metrics ───────────────────────────────────
    $amountLakh = ($amount > 0) ? (int)round($amount / 100000) : 0;
    $ltv        = ($val > 0 && $amount > 0) ? round(($amount / $val) * 100, 1) : 0;
    // FOIR: 0.0 = no EMIs (excellent), -1.0 = income unknown
    if ($income > 0) {
        $foir = ($emi > 0) ? round(($emi / $income) * 100, 1) : 0.0;
    } else {
        $foir = -1.0; // sentinel: income not provided
    }

    // Push normalised values back for lender_search
    $searchProfile = array_merge($profile, [
        'loan_type'          => $loanType,
        'cibil_score'        => $cibil,
        'cibil'              => $cibil,
        'monthly_income'     => $income,
        'existing_emis'      => $emi,
        'amount'             => $amount,
        'loan_amount'        => $amount,
        'city'               => $city,
        'property_type'      => $propClean,
        'property_title'     => $propClean,
        'property_usage'     => $propUsage,
        'property_valuation' => $val,
        'occupation'         => $occ,
        'loan_category'      => $loanCategory,
    ]);

    // ── Step 1: SQL search ────────────────────────────────
    $lenders = searchLenders($searchProfile);

   if (empty($lenders)) {
    $lang = $profile['_lang'] ?? 'en';
    $name = $profile['customer_name'] ?? '';

    // Customer already gave contact at step 2 — reassure them
    if (!empty($profile['customer_mobile'])) {
        $message = $lang === 'hi'
            ? ($name ? "Koi exact match nahi mila, {$name} ji." : "Koi exact match nahi mila.")
              . " Hamara expert personally aapke liye best option dhundega aur jald call karega. 🙏"
            : ($name ? "No exact match found for your profile, {$name}." : "No exact match found for your profile.")
              . " Our expert will personally find the best option and call you shortly. 🙏";

        return [
            'type'    => 'collect_contact',   // frontend handles this case
            'message' => $message,
            'lang'    => $lang,
            'contact_already_saved' => true,  // tells frontend NOT to show form again
        ];
    }

    // Customer did NOT give contact (shouldn't happen with v3 flow, but fallback)
    return [
        'type'     => 'collect_contact',
        'message'  => $lang === 'hi'
            ? "Koi exact match nahi mila. Hamara expert personally aapke liye best option dhundega."
            : "No lenders found matching your exact profile. Our expert will personally find the best option for you.",
        'question' => $lang === 'hi'
            ? "Apna naam aur mobile share karein — 30 minute mein call aayega. 📞"
            : "Please share your name and mobile — our expert will call within 30 minutes. 📞",
        'lang'    => $lang,
        'contact_already_saved' => false,
    ];
}

/*
 * ─────────────────────────────────────────
 * ALSO: In hybrid_query.php, add language detection at the
 * top of runHybrid() function, right after the $freeQuery merge:
 *
 *   // Detect language
 *   $lang = !empty($profile['_lang']) ? $profile['_lang'] : 'en';
 *   $profile['_lang'] = $lang;
 *
 * AND pass $lang to enrichTopThree() and generateAiNote() calls,
 * updating those function signatures to accept a $lang = 'en' parameter.
 *
 * In enrichTopThree(), add this to the GPT prompt for Hindi:
 *   $langInstr = $lang === 'hi'
 *       ? "Reply in Hinglish (Hindi in Roman script + English financial terms)."
 *       : "Reply in English.";
 * ─────────────────────────────────────────
 */


    // ── Step 2: Score every lender (V2) ─────────────────
    $incomeProof    = strtolower(trim($profile['income_proof'] ?? $profile['income_type'] ?? ''));
    $needsSurrogate = in_array($incomeProof, ['none','no_itr','cash','informal','bank_statement','gst'])
                   || str_contains($incomeProof, 'no itr')
                   || str_contains($incomeProof, 'without itr')
                   || str_contains($incomeProof, 'bina itr');
    $priority = strtolower(trim($profile['priority'] ?? 'balanced'));

    foreach ($lenders as &$l) {
        $result              = scoreLenderV2($l, $cibil, $ltv, $foir, $amountLakh, $propClean, $city, $occ, $incSrc, $needsSurrogate, $priority, $isUnsecured, $freeQuery);
        $l['score']          = $result['score'];
        $l['eligible']       = $result['eligible'];
        $l['rejection_risk'] = $result['rejection_risk'];
        $l['match_reasons']  = $result['match_reasons'];
        $l['warn_flags']     = $result['warn_flags'];
        $l['score_breakdown']= $result['score_breakdown'] ?? [];
    }
    unset($l);

    // ── Step 3: Sort by score ─────────────────────────────
    usort($lenders, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

    // ── Step 4: Deduplicate (keep highest-scored per name) ─
    $seen    = [];
    $deduped = [];
    foreach ($lenders as $l) {
        $name = $l['lender_name'];
        if (!isset($seen[$name])) {
            $seen[$name] = true;
            $deduped[]   = $l;
        }
    }
    $lenders = $deduped;

    // ── Step 4b: Filter ineligible lenders ──────────────
    $lenders = array_values(array_filter($lenders, function($l) {
        // Remove hard ineligible (property title rejected, CIBIL too low etc.)
        if (isset($l['eligible']) && $l['eligible'] === false && ($l['score'] ?? 0) < 50) {
            return false;
        }
        return true;
    }));

    // ── Step 5: Rank + personalised "why" text ────────────
    foreach ($lenders as $i => &$l) {
        $l['rank'] = $i + 1;
        $l['why']  = buildWhyText($l, $cibil, $ltv, $propClean, $city, $occ, $i + 1);
    }
    unset($l);

    // Pin special-profile-matched lenders into results
    $pinned = [];
    if (!empty($freeQuery)) {
        $stopWords = ['profile','loan','home','property','bank','finance','case','need','want','good','best','please','lender','jaipur','delhi','mumbai','india'];
        $qWords = array_filter(
            preg_split('/\s+/', strtolower(trim($freeQuery))),
            fn($w) => strlen($w) > 4 && !in_array($w, $stopWords)
        );
        foreach ($lenders as $idx => $pl) {
            if (empty($pl['special_profiles'])) continue;
            $sp = strtolower(($pl['special_profiles'] ?? '') . ' ' . ($pl['profile_allowed'] ?? ''));
            foreach ($qWords as $w) {
                if (strlen($w) > 3 && str_contains($sp, $w)) {
                    $pinned[$pl['lender_name']] = $idx;
                    break;
                }
            }
        }
    }
    $top = array_slice($lenders, 0, MAX_LENDERS_RETURN);
    // Inject pinned lenders missing from top
    foreach ($pinned as $pName => $pIdx) {
        $alreadyIn = false;
        foreach ($top as $t) { if ($t['lender_name'] === $pName) { $alreadyIn = true; break; } }
        if (!$alreadyIn && isset($lenders[$pIdx])) {
            $top[] = $lenders[$pIdx];
        }
    }

    // ── Step 6: Generate rich top-3 AI explanations ───────
    if (openAiEnabled() && count($top) >= 1) {
        $top = enrichTopThree($top, $cibil, $income, $emi, $ltv, $amountLakh, $propClean, $city, $occ, $loanType);
    }

    // ── Step 7: RAG (only if Qdrant configured) ───────────
    $policyNote = null;
    if (ragEnabled() && openAiEnabled()) {
        $q = $freeQuery ?: "{$loanType} {$propClean} {$city} CIBIL {$cibil}";
        $policyNote = ragEnrichment($q, $top);
    }

    // ── Step 7: AI summary (only if OpenAI configured) ────
    $aiSummary = null;
    if (openAiEnabled() && count($top) >= 2) {
        $aiSummary = generateAiNote($top, $cibil, $income, $emi, $city, $propClean, $loanType);
    }

    // ── Build response ────────────────────────────────────
    $resp = [
        'type'          => 'result',
        'message'       => 'Top lender recommendations based on your profile',
        'total_matches' => count($lenders),
        'lenders'       => $top,
        'profile_summary' => [
            'loan_type'    => strtoupper($loanType),
            'amount'       => $amountLakh ? "₹{$amountLakh} Lakh" : 'Not provided',
            'cibil'        => $cibil ?: 'Case-to-case lenders shown',
            'income'       => $income ? '₹' . number_format($income) . '/mo' : 'Not provided',
            'existing_emi' => '₹' . number_format($emi) . '/mo',
            'ltv_requested'=> $ltv ? "{$ltv}%" : 'N/A',
            'foir'         => ($foir === -1.0 ? 'N/A' : ($foir === 0.0 ? '0% (No EMIs)' : "{$foir}%")),
            'city'         => ucfirst($city),
            'property'     => $propClean ? ucwords($propClean) : 'Not specified',
            'occupation'   => ucwords($occ ?: 'Not specified'),
        ],
    ];

    if ($policyNote) $resp['policy_note'] = $policyNote;
    if ($aiSummary)  $resp['ai_summary']  = $aiSummary;

    return $resp;
}


// ═══════════════════════════════════════════════════════════

// ═══════════════════════════════════════════════════════════
//  SCORING ENGINE V2
// ═══════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════
//  SCORING ENGINE V2 — UPGRADED (7 Components, Bifurcated)
// ═══════════════════════════════════════════════════════════
function scoreLenderV2(array $l, int $cibil, float $ltv, float $foir, int $amountLakh, string $propClean, string $city, string $occ, string $incSrc, bool $needsSurrogate, string $priority = 'balanced', bool $isUnsecured = false, string $freeQuery = ''): array {
    $s = 0; $eligible = true; $rejRisk = 'low'; $matchReasons = []; $warnFlags = []; $breakdown = [];
    $minCib = (int)($l['min_cibil'] ?? 0);
    $minL = (float)($l['loan_min_lakh'] ?? 0);
    $maxL = (float)($l['loan_max_lakh'] ?? 0);
    if ($minCib > 0 && $cibil > 0 && $cibil < $minCib) { $eligible = false; $rejRisk = 'high'; $warnFlags[] = "CIBIL {$cibil} below minimum {$minCib}"; }
    if ($amountLakh > 0 && $maxL > 0 && $amountLakh > $maxL) { $eligible = false; $rejRisk = 'high'; $warnFlags[] = "Loan exceeds max Rs.{$maxL}L"; }
    if ($amountLakh > 0 && $minL > 0 && $amountLakh < $minL) { $eligible = false; $rejRisk = 'high'; $warnFlags[] = "Loan below min Rs.{$minL}L"; }
    $roi = (float)($l['roi_min'] ?? 0);
    $roiPts = 0; $roiNote = '';
    if ($roi <= 0)        { $roiPts = 5;  $roiNote = 'ROI not specified'; }
    elseif ($roi <= 7.5)  { $roiPts = 25; $roiNote = "Excellent ROI {$roi}%"; $matchReasons[] = "Excellent ROI {$roi}%"; }
    elseif ($roi <= 8.5)  { $roiPts = 22; $roiNote = "Very competitive ROI {$roi}%"; $matchReasons[] = "Very competitive ROI {$roi}%"; }
    elseif ($roi <= 9.5)  { $roiPts = 18; $roiNote = "Competitive ROI {$roi}%"; $matchReasons[] = "Competitive ROI {$roi}%"; }
    elseif ($roi <= 10.5) { $roiPts = 13; $roiNote = "Moderate ROI {$roi}%"; }
    elseif ($roi <= 12.0) { $roiPts = 8;  $roiNote = "Higher ROI {$roi}%"; $warnFlags[] = "ROI {$roi}% above market average"; }
    elseif ($roi <= 14.0) { $roiPts = 4;  $roiNote = "Premium pricing {$roi}%"; }
    else                  { $roiPts = 1;  $roiNote = "Very high ROI {$roi}%"; $warnFlags[] = "Very high ROI {$roi}%"; }
    $s += $roiPts;
    $breakdown['roi'] = ['label'=>'ROI','points'=>"+{$roiPts}",'max'=>25,'note'=>$roiNote];
    $ltvPts = 0; $ltvNote = '';
    if (!$isUnsecured) {
        $effectiveLtv = getLtvForProperty($l, $propClean);
        if ($effectiveLtv <= 0) { $ltvPts = 4; $ltvNote = 'LTV as per policy'; }
        elseif ($ltv <= 0) {
            if ($effectiveLtv >= 85)     { $ltvPts = 15; $ltvNote = "High LTV {$effectiveLtv}%"; $matchReasons[] = "LTV up to {$effectiveLtv}%"; }
            elseif ($effectiveLtv >= 75) { $ltvPts = 12; $ltvNote = "Good LTV {$effectiveLtv}%"; $matchReasons[] = "LTV up to {$effectiveLtv}%"; }
            elseif ($effectiveLtv >= 65) { $ltvPts = 8;  $ltvNote = "Moderate LTV {$effectiveLtv}%"; }
            elseif ($effectiveLtv >= 50) { $ltvPts = 5;  $ltvNote = "Conservative LTV {$effectiveLtv}%"; }
            else                         { $ltvPts = 2;  $ltvNote = "Low LTV {$effectiveLtv}%"; }
        } else {
            $gap = $effectiveLtv - $ltv;
            if ($gap >= 15)     { $ltvPts = 15; $ltvNote = "LTV {$effectiveLtv}% exceeds requirement"; $matchReasons[] = "LTV comfortably covers your need"; }
            elseif ($gap >= 5)  { $ltvPts = 12; $ltvNote = "LTV {$effectiveLtv}% covers need"; }
            elseif ($gap >= 0)  { $ltvPts = 8;  $ltvNote = "LTV {$effectiveLtv}% meets requirement"; }
            elseif ($gap >= -5) { $ltvPts = 4;  $ltvNote = "LTV slightly below need"; $warnFlags[] = "LTV {$effectiveLtv}% below {$ltv}% needed"; $rejRisk = 'medium'; }
            else                { $ltvPts = 0;  $ltvNote = "LTV insufficient"; $warnFlags[] = "LTV gap — {$effectiveLtv}% vs {$ltv}% needed"; $rejRisk = 'medium'; }
        }
        $breakdown['ltv'] = ['label'=>'LTV','points'=>"+{$ltvPts}",'max'=>15,'note'=>$ltvNote];
    } else {
        $breakdown['ltv'] = ['label'=>'LTV','points'=>'N/A','max'=>0,'note'=>'Not applicable for unsecured'];
    }
    $s += $ltvPts;
    $maxCibilComp = $isUnsecured ? 20 : 15;
    $lenderMinCibil = $isUnsecured ? (int)($l['unsecured_bureau_min_cibil'] ?? $l['min_cibil'] ?? 0) : (int)($l['min_cibil'] ?? 0);
    $cibilPolicy = strtolower($l['cibil_policy'] ?? 'case_to_case');
    $cibilPts = 0; $cibilNote = '';
    if ($lenderMinCibil > 0 && $cibil > 0) {
        $gap = $cibil - $lenderMinCibil;
        if ($gap >= 100)     { $cibilPts = $maxCibilComp; $cibilNote = "Strong buffer +{$gap}"; $matchReasons[] = "CIBIL {$cibil} well above minimum"; }
        elseif ($gap >= 50)  { $cibilPts = (int)($maxCibilComp*0.85); $cibilNote = "Good buffer"; }
        elseif ($gap >= 20)  { $cibilPts = (int)($maxCibilComp*0.70); $cibilNote = "Adequate CIBIL"; }
        elseif ($gap >= 0)   { $cibilPts = (int)($maxCibilComp*0.50); $cibilNote = "Meets minimum"; $warnFlags[] = "CIBIL barely meets lender minimum"; }
        elseif ($gap >= -30) { $cibilPts = (int)($maxCibilComp*0.20); $cibilNote = "Below minimum"; $warnFlags[] = "CIBIL below lender min {$lenderMinCibil}"; }
        else                 { $cibilPts = 0; $cibilNote = "CIBIL too low"; $warnFlags[] = "CIBIL {$cibil} too low"; }
    } elseif ($cibil > 0) {
        if ($cibilPolicy === 'no_bureau') { $cibilPts = $maxCibilComp; $cibilNote = "No bureau check"; $matchReasons[] = "No CIBIL minimum"; }
        elseif ($cibil >= 750) { $cibilPts = (int)($maxCibilComp*0.85); $cibilNote = "Strong CIBIL {$cibil}"; }
        elseif ($cibil >= 700) { $cibilPts = (int)($maxCibilComp*0.70); $cibilNote = "Good CIBIL {$cibil}"; }
        elseif ($cibil >= 650) { $cibilPts = (int)($maxCibilComp*0.50); $cibilNote = "Average CIBIL {$cibil}"; }
        elseif ($cibil >= 600) { $cibilPts = (int)($maxCibilComp*0.30); $cibilNote = "Low CIBIL {$cibil}"; $warnFlags[] = "Low CIBIL — case-to-case"; }
        else                   { $cibilPts = (int)($maxCibilComp*0.15); $cibilNote = "Very low CIBIL"; $warnFlags[] = "CIBIL very low"; }
        if (in_array($cibilPolicy,['case_to_case','flexible'])) { $cibilNote .= ' (flexible)'; $matchReasons[] = "Flexible CIBIL policy"; }
    } else { $cibilPts = (int)($maxCibilComp*0.40); $cibilNote = 'CIBIL not provided'; }
    $s += $cibilPts;
    $breakdown['cibil'] = ['label'=>'CIBIL','points'=>"+{$cibilPts}",'max'=>$maxCibilComp,'note'=>$cibilNote];
    $maxFoirComp = $isUnsecured ? 20 : 15;
    $lenderMaxFoir = $isUnsecured ? (int)($l['unsecured_max_foir'] ?? $l['max_foir_pct'] ?? 0) : (int)($l['max_foir_pct'] ?? 0);
    $foirRelaxed = (int)($l['foir_relaxed_pct'] ?? 0);
    $foirRelaxAvail = (int)($l['foir_relaxation_available'] ?? 0);
    $foirPts = 0; $foirNote = '';
    if ($foir === -1.0) {
        // Income not provided — cannot calculate FOIR
        $foirPts = (int)($maxFoirComp*0.50); $foirNote = 'FOIR not calculated';
    } elseif ($foir === 0.0) {
        // Zero existing EMIs — best possible FOIR
        $foirPts = $maxFoirComp; $foirNote = 'No existing EMIs — excellent FOIR';
        $matchReasons[] = 'No existing EMI obligations';
    } elseif ($lenderMaxFoir > 0) {
        $gap = $lenderMaxFoir - $foir;
        if ($gap >= 20)     { $foirPts = $maxFoirComp; $foirNote = "Comfortable FOIR {$foir}%"; $matchReasons[] = "FOIR well within limit"; }
        elseif ($gap >= 10) { $foirPts = (int)($maxFoirComp*0.80); $foirNote = "Good headroom"; }
        elseif ($gap >= 0)  { $foirPts = (int)($maxFoirComp*0.55); $foirNote = "FOIR near limit"; $warnFlags[] = "FOIR {$foir}% near limit {$lenderMaxFoir}%"; }
        elseif ($foirRelaxAvail && $foirRelaxed >= $foir) { $foirPts = (int)($maxFoirComp*0.35); $foirNote = "Relaxed FOIR program"; $matchReasons[] = "Higher FOIR program available"; }
        else { $foirPts = 0; $foirNote = "FOIR exceeded"; $warnFlags[] = "FOIR {$foir}% exceeds max {$lenderMaxFoir}%"; $rejRisk = 'high'; }
    } else {
        // Lender has no max_foir set — use fallback bands
        if ($foir <= 30)     { $foirPts = $maxFoirComp; $foirNote = "Low FOIR {$foir}%"; }
        elseif ($foir <= 40) { $foirPts = (int)($maxFoirComp*0.80); $foirNote = "Healthy FOIR {$foir}%"; }
        elseif ($foir <= 50) { $foirPts = (int)($maxFoirComp*0.55); $foirNote = "Moderate FOIR {$foir}%"; }
        elseif ($foir <= 60) { $foirPts = (int)($maxFoirComp*0.25); $foirNote = "High FOIR {$foir}%"; $warnFlags[] = "FOIR {$foir}% borderline"; $rejRisk = 'medium'; }
        else { $foirPts = 0; $foirNote = "FOIR too high"; $warnFlags[] = "FOIR {$foir}% too high"; $rejRisk = 'high'; $eligible = false; }
    }
    $s += $foirPts;
    $breakdown['foir'] = ['label'=>'FOIR','points'=>"+{$foirPts}",'max'=>$maxFoirComp,'note'=>$foirNote,'customer_foir'=>($foir===-1.0?'N/A':($foir===0.0?'0% (No EMIs)':"{$foir}%"))];
    $sf = ['banking'=>(int)($l['surrogate_banking']??0),'gst'=>(int)($l['surrogate_gst']??0),'itr'=>(int)($l['surrogate_itr']??0),'rtr'=>(int)($l['surrogate_rtr']??0),'gross_profit'=>(int)($l['surrogate_gross_profit']??0),'low_ltv'=>(int)($l['surrogate_low_ltv']??0),'lip'=>(int)($l['surrogate_lip']??0)];
    $actSur = count(array_filter($sf));
    if ($actSur === 0) {
        $prog = strtolower($l['programs'] ?? '');
        if (str_contains($prog,'banking')||str_contains($prog,'surrogate')) $sf['banking']=1;
        if (str_contains($prog,'gst')) $sf['gst']=1;
        if (str_contains($prog,'rtr')) $sf['rtr']=1;
        if (str_contains($prog,'gross profit')) $sf['gross_profit']=1;
        if (str_contains($prog,'low ltv')) $sf['low_ltv']=1;
        $actSur = count(array_filter($sf));
    }
    $nipAllowed = (int)($l['nip_allowed'] ?? 0);
    $nipNeeded = str_contains($incSrc,'nip')||str_contains($incSrc,'no income');
    $surPts = 0; $surNote = '';
    if ($nipNeeded) {
        if ($nipAllowed) { $surPts = 10; $matchReasons[] = "No Income Proof program available"; $surNote = "NIP available"; }
        else { $surPts = 0; $warnFlags[] = "NIP not supported"; $surNote = "NIP not available"; }
    } elseif ($needsSurrogate) {
        if ($actSur >= 4)     { $surPts = 10; array_unshift($matchReasons,"Surrogate available — no ITR needed"); $surNote = "{$actSur} surrogates"; }
        elseif ($actSur >= 2) { $surPts = 7; $matchReasons[] = "Surrogates available"; $surNote = "{$actSur} surrogates"; }
        elseif ($actSur >= 1) { $surPts = 4; $surNote = "Limited surrogates"; }
        else { $surPts = 0; $warnFlags[] = "No surrogate — ITR required"; $surNote = "No surrogates"; $rejRisk = 'medium'; }
    } else {
        if ($actSur >= 4) { $surPts = 8; $surNote = "Rich surrogate portfolio"; }
        elseif ($actSur >= 2) { $surPts = 5; $surNote = "{$actSur} programs"; }
        elseif ($actSur >= 1) { $surPts = 3; $surNote = "1 program"; }
        else { $surPts = 1; $surNote = "No structured surrogates"; }
    }
    $s += $surPts;
    $breakdown['surrogate'] = ['label'=>'Surrogate Programs','points'=>"+{$surPts}",'max'=>10,'note'=>$surNote];
    $cityCoverage  = strtolower($l["city_coverage"] ?? "");
    $citiesAllowed = strtolower($l["cities_allowed"] ?? $l["city_allowed"] ?? "");
    $cityLower     = strtolower(trim($city));
    $geoKm         = (int)($l["geo_limit_km"] ?? 0);
    $distances     = getRajasthanCityDistances();
    $geoPts = 0; $geoNote = "";
    if ($cityCoverage==="pan_india"||str_contains($citiesAllowed,"pan india")) {
        $geoPts=10; $geoNote="Pan-India coverage"; $matchReasons[]="Pan-India lender";
    } elseif ($cityCoverage==="rajasthan"||str_contains($citiesAllowed,"rajasthan")) {
        $geoPts=($cityLower==="jaipur"||empty($cityLower))?10:8;
        $geoNote="Rajasthan coverage"; $matchReasons[]=ucfirst($cityLower?:"Jaipur")." — Rajasthan lender";
    } elseif ($geoKm > 0) {
        if (empty($cityLower)||$cityLower==="jaipur") {
            $geoPts=10; $geoNote="Base city Jaipur"; $matchReasons[]="Jaipur within lender radius";
        } else {
            $gr = getGeoScore($cityLower, $geoKm, $distances);
            $geoPts=$gr["score"]; $geoNote=$gr["reason"];
            if ($gr["status"]==="within_radius")      $matchReasons[]=$gr["reason"];
            elseif ($gr["status"]==="slightly_outside") $warnFlags[]=$gr["reason"];
            elseif ($gr["status"]==="outside_radius")   $warnFlags[]=$gr["reason"];
            elseif ($gr["status"]==="manual_review")    $warnFlags[]="Verify if ".$cityLower." is within lender range";
        }
    } elseif ($cityCoverage==="jaipur_only") {
        if (empty($cityLower)||$cityLower==="jaipur") {
            $geoPts=10; $geoNote="Jaipur confirmed"; $matchReasons[]="Jaipur home city";
        } else {
            $dist=getDistanceFromJaipur($cityLower,$distances);
            if ($dist!==null&&$dist<=40)  { $geoPts=6;  $geoNote="Near Jaipur ({$dist}km)"; $warnFlags[]="Jaipur-only lender — verify ".$cityLower; }
            elseif ($dist!==null)         { $geoPts=-5; $geoNote="Jaipur-only — ".$dist."km away"; $warnFlags[]="Lender Jaipur-only — ".$cityLower." likely outside"; }
            else                          { $geoPts=0;  $geoNote="Jaipur-only — distance unknown"; $warnFlags[]="Verify ".$cityLower." with lender"; }
        }
    } elseif (!empty($cityLower)&&!empty($citiesAllowed)&&str_contains($citiesAllowed,$cityLower)) {
        $geoPts=10; $geoNote=ucfirst($cityLower)." listed"; $matchReasons[]=ucfirst($cityLower)." in coverage";
    } elseif (!empty($citiesAllowed)) {
        $geoPts=3; $geoNote="City not confirmed"; $warnFlags[]=ucfirst($cityLower?:"City")." not in coverage list";
    } else { $geoPts=5; $geoNote="Coverage not specified"; }
    $s += $geoPts;
    $breakdown["geography"] = ["label"=>"Geography","points"=>($geoPts>=0?"+{$geoPts}":"{$geoPts}"),"max"=>10,"note"=>$geoNote];
    $maxIncComp = $isUnsecured ? 15 : 10;
    $incLower = strtolower($incSrc.' '.$occ);
    $isSalaried=str_contains($incLower,'salaried')||str_contains($incLower,'salary');
    $isSenp=str_contains($incLower,'senp')||str_contains($incLower,'self')||str_contains($incLower,'cash');
    $isSep=str_contains($incLower,'sep')||str_contains($incLower,'professional')||str_contains($incLower,'doctor')||str_contains($incLower,'ca');
    $isNipInc = str_contains($incLower,'nip')||str_contains($incLower,'no income')||str_contains($incLower,'no itr');
    $isAgri   = str_contains($incLower,'agri')||str_contains($incLower,'farm')||str_contains($incLower,'kisan');
    $flagOk = true;
    if ($isAgri) {
        // Agriculture — check cash_income or nip or senp (treated case-to-case by most)
        $flagOk = ((int)($l['cash_income_allowed']??0) || (int)($l['nip_allowed']??0) || (int)($l['senp_allowed']??1)) ? true : false;
    } elseif ($isNipInc) $flagOk=(bool)(int)($l['nip_allowed']??0);
    elseif ($isSenp) $flagOk=((int)($l['senp_allowed']??1)||(int)($l['cash_income_allowed']??0))?true:false;
    elseif ($isSep) $flagOk=(bool)(int)($l['sep_allowed']??1);
    elseif ($isSalaried) $flagOk=(bool)(int)($l['salaried_allowed']??1);
    $incPts=0; $incNote='';
    if (!$flagOk) { $incPts=0; $incNote="Income type not accepted"; $warnFlags[]="Income type mismatch"; }
    elseif (!$isUnsecured) {
        $incPts=10; $incNote="Income type accepted";
        if ($isSenp) $matchReasons[]="SENP/Self-Employed accepted";
        elseif ($isSep) $matchReasons[]="Professional/SEP accepted";
        elseif ($isSalaried) $matchReasons[]="Salaried profile accepted";
    } else {
        $incPts=8; $incNote="Business profile accepted";
        $vMin=(int)($l['min_business_vintage_years']??0);
        if ($vMin>0) { $incPts+=2; $incNote.=" (vintage policy set)"; }
        $gReqd=(int)($l['gst_registration_required']??0);
        if ($gReqd&&str_contains($incLower,'no gst')) { $incPts-=3; $warnFlags[]="GST registration required"; }
        $incPts=min($maxIncComp,max(0,$incPts));
    }
    $s += $incPts;
    $breakdown['income_profile'] = ['label'=>$isUnsecured?'Business Profile':'Income Type','points'=>"+{$incPts}",'max'=>$maxIncComp,'note'=>$incNote?:'Income type accepted'];
    // ── Property Title — title_* columns se hard match ──
    $titleColMap = [
        'jda'        => 'title_jda',
        'society'    => 'title_society_patta',
        'patta'      => 'title_society_patta',
        'flat'       => 'title_society_patta',
        'freehold'   => 'title_freehold',
        'registry'   => 'title_freehold',
        'rhb'        => 'title_rhb',
        'bda'        => 'title_rhb',
        'panchayat'  => 'title_gram_panchayat',
        'gram'       => 'title_gram_panchayat',
        'nagar'      => 'title_nagar_nigam',
        'nigam'      => 'title_nagar_nigam',
        'riico'      => 'title_riico',
        'agriculture'=> 'title_agriculture',
        'agri'       => 'title_agriculture',
    ];
    $propTitlePts = 0; $propTitleNote = ''; $titleColUsed = null;
    foreach ($titleColMap as $keyword => $col) {
        if (str_contains($propClean, $keyword)) { $titleColUsed = $col; break; }
    }
    if ($titleColUsed && array_key_exists($titleColUsed, $l)) {
        $titleVal = $l[$titleColUsed];
        if ($titleVal === null || $titleVal === '') {
            $propTitlePts = 2; $propTitleNote = 'Title policy not specified';
        } elseif ((int)$titleVal === 1) {
            $propTitlePts = 18; $propTitleNote = 'Property title accepted';
            $matchReasons[] = ucfirst(str_replace('_',' ',$propClean)) . ' title accepted';
        } else {
            // Check if property_allowed text overrides the 0 flag
            $propText = strtolower(($l['property_allowed'] ?? '') . ' ' . ($l['notes'] ?? '') . ' ' . ($l['special_profiles'] ?? ''));
            $isAllProperties = str_contains($propText, 'all') || 
                               str_contains($propText, 'deviation') ||
                               str_contains($propText, 'any property') ||
                               str_contains($propText, 'all residential') ||
                               str_contains($propText, 'case to case');
            if ($isAllProperties) {
                $propTitlePts = 5; $propTitleNote = 'Flexible property policy';
                $matchReasons[] = 'Flexible property acceptance';
            } else {
                $propTitlePts = -20; $propTitleNote = 'Title NOT accepted';
                $warnFlags[]  = ucfirst(str_replace('_',' ',$propClean)) . ' title not accepted by lender';
                $eligible = false; $rejRisk = 'high';
            }
        }
    } elseif ($propClean) {
        $pa = strtolower(($l['property_allowed'] ?? '') . ' ' . ($l['notes'] ?? ''));
        if ($pa && str_contains($pa, $propClean)) {
            $propTitlePts = 10; $propTitleNote = 'Found in property notes';
            $matchReasons[] = ucfirst($propClean) . ' title mentioned';
        } else {
            $propTitlePts = 0; $propTitleNote = 'Title not specified';
        }
    }
    $s += $propTitlePts;
    $breakdown['property_title'] = ['label'=>'Property Title','points'=>($propTitlePts>=0?"+{$propTitlePts}":"{$propTitlePts}"),'max'=>18,'note'=>$propTitleNote];

    $lenderType=strtolower($l['lender_type']??'nbfc');
    $cleanProf=($cibil>=700&&($foir===-1.0||$foir<=45)&&!$needsSurrogate);
    $bonusPts=0;
    if ($lenderType==='bank') { $bonusPts=$cleanProf?3:1; if ($cleanProf) $matchReasons[]="Bank — ideal for clean profile"; }
    elseif ($lenderType==='hfc') { $bonusPts=2; }
    elseif ($lenderType==='sfb'&&($needsSurrogate||$cibil<680)) { $bonusPts=2; }
    if ($lenderType==='nbfc'&&$cleanProf&&$roi>13) $warnFlags[]="Better rates available from banks";
    $s += $bonusPts;
    if ($bonusPts>0) $breakdown['lender_type']=['label'=>'Lender Type','points'=>"+{$bonusPts}",'max'=>3,'note'=>strtoupper($lenderType).' preference'];
    // --- Special Profile Boost (+15) ---
    if (!empty($freeQuery) && (!empty($l['special_profiles']) || !empty($l['profile_allowed']))) {
        $spLower = strtolower(($l['special_profiles'] ?? '') . ' ' . ($l['profile_allowed'] ?? ''));
        $stopWords2 = ['profile','loan','home','property','bank','finance','case','need','want','good','best','please','lender'];
        $words   = preg_split('/\s+/', strtolower(trim($freeQuery)));
        foreach ($words as $w) {
            if (strlen($w) > 4 && !in_array($w, $stopWords2) && str_contains($spLower, $w)) {
                $s += 15;
                $matchReasons[] = 'Special profile match';
                break;
            }
        }
    }
    $finalScore=min(100,max(5,$s));
    if (!$eligible) { $rejRisk='high'; $finalScore=min($finalScore,38); }
    elseif ($finalScore<35) { $rejRisk='high'; }
    elseif ($finalScore<55&&$rejRisk==='low') { $rejRisk='medium'; }
    return ['score'=>$finalScore,'eligible'=>$eligible,'rejection_risk'=>$rejRisk,'match_reasons'=>array_slice(array_unique($matchReasons),0,4),'warn_flags'=>array_slice(array_unique($warnFlags),0,3),'score_breakdown'=>$breakdown];
}
function getLtvForProperty(array $l, string $propClean): float {
    $matrix = $l['ltv_matrix'] ?? '';
    if ($matrix) {
        $m = is_array($matrix) ? $matrix : (json_decode($matrix, true) ?? []);
        $get = fn($k) => (float)($m[$k] ?? 0);
        $p = strtolower($propClean);
        if (str_contains($p,'sorp')||str_contains($p,'residential')||str_contains($p,'house')||str_contains($p,'villa')) return $get('sorp');
        if (str_contains($p,'socp')||str_contains($p,'commercial')||str_contains($p,'office')||str_contains($p,'shop'))  return $get('socp');
        if (str_contains($p,'soip')||(str_contains($p,'industrial')&&!str_contains($p,'rent')))                           return $get('soip');
        if (str_contains($p,'rent')) { $v=$get('rented'); return $v>0?$v:$get('socp'); }
        if (str_contains($p,'land')||str_contains($p,'plot')||str_contains($p,'zameen'))                                  return $get('land');
    }
    return (float)($l['max_ltv'] ?? 0);
}

//  SCORING ENGINE  (20–99)
// ═══════════════════════════════════════════════════════════
function scoreLender(
    array  $l,
    int    $cibil,
    float  $ltv,
    int    $amountLakh,
    string $propClean,
    string $city,
    string $occ
): int {
    $s      = 40;
    $roi    = (float)($l['roi_min']  ?? 0);
    $maxLtv = (float)($l['max_ltv'] ?? 0);
    $minCib = (int)($l['min_cibil'] ?? 0);

    // ── ROI — lower is better; 0 = undisclosed (neutral) ──
    if ($roi <= 0)         { $s += 5; }
    elseif ($roi <= 8.0)   { $s += 25; }
    elseif ($roi <= 8.75)  { $s += 22; }
    elseif ($roi <= 9.5)   { $s += 18; }
    elseif ($roi <= 10.5)  { $s += 13; }
    elseif ($roi <= 12.0)  { $s += 8;  }
    elseif ($roi <= 15.0)  { $s += 4;  }

    // ── LTV — cap suspiciously high values for LAP ────────
    // LTV > 75% on a LAP product is almost certainly the HL
    // LTV from a combined HL+LAP row — don't reward it.
    $effectiveLtv = $maxLtv;
    if ($maxLtv > 75 && str_contains(strtolower($l['product'] ?? ''), 'home')) {
        $effectiveLtv = 60; // conservative LAP LTV estimate
    }

    if ($effectiveLtv <= 0) {
        $s += 4;
    } elseif ($ltv > 0 && $ltv < 30) {
        // Very low requested LTV — almost any lender can cover it, give flat bonus
        $s += 6;
    } elseif ($ltv > 0) {
        if ($effectiveLtv >= $ltv + 15)    { $s += 14; }
        elseif ($effectiveLtv >= $ltv)     { $s += 10; }
        elseif ($effectiveLtv >= $ltv - 5) { $s += 5;  }
        else                               { $s -= 5;  }
    } else {
        if ($effectiveLtv >= 70)    { $s += 10; }
        elseif ($effectiveLtv >= 60){ $s += 7;  }
        elseif ($effectiveLtv >= 50){ $s += 4;  }
    }

    // ── CIBIL headroom ────────────────────────────────────
    if ($minCib === 0) {
        $s += 6;  // case-to-case — slightly lower bonus (less certainty)
    } elseif ($cibil > 0) {
        $h = $cibil - $minCib;
        if ($h >= 150)    { $s += 12; }
        elseif ($h >= 80) { $s += 8;  }
        elseif ($h >= 30) { $s += 5;  }
        elseif ($h >= 0)  { $s += 2;  }
        else              { $s -= 10; }
    }

    // ── Property type — explicit match is strongly rewarded ─
    // NO match = significant penalty (not just no bonus)
    $propMatched = false;
    if ($propClean) {
        $pa = strtolower(($l['property_allowed'] ?? '') . ' ' . ($l['property_type'] ?? ''));
        if (str_contains($pa, $propClean)) {
            $s += 12;
            $propMatched = true;
        } else {
            $s -= 12; // Penalise if not explicitly listed
        }
    }

    // ── City — explicit city match ────────────────────────
    $cityField = strtolower($l['city_allowed'] ?? '');
    if (!$cityField) {
        $s += 5;  // blank = all cities
    } elseif ($city && str_contains($cityField, $city)) {
        $s += 10; // exact city mention
    } elseif ($city && str_contains($cityField, cityToState($city))) {
        $s += 5;  // state-level match (e.g. Rajasthan)
    } else {
        $s -= 5;  // city not covered
    }

    // ── Amount comfort zone ───────────────────────────────
    $minL = (float)($l['loan_min_lakh'] ?? 0);
    $maxL = (float)($l['loan_max_lakh'] ?? 0);
    if ($amountLakh > 0 && $minL > 0 && $maxL > 0) {
        $mid = ($minL + $maxL) / 2;
        if ($amountLakh >= $minL && $amountLakh <= $mid) { $s += 5; }
        elseif ($amountLakh >= $minL)                    { $s += 2; }
    }

    // ── Nil foreclosure ───────────────────────────────────
    if (strtolower(trim($l['foreclosure_charges'] ?? '')) === 'nil') { $s += 3; }

    // ── Occupation / income type ──────────────────────────
    $incField = strtolower(($l['income_types'] ?? '') . ' ' . ($l['profile_allowed'] ?? ''));
    if ($occ === 'govt'          && (str_contains($incField,'govt') || str_contains($incField,'government'))) { $s += 5; }
    if ($occ === 'salaried'      && str_contains($incField,'salaried'))    { $s += 4; }
    if ($occ === 'self_employed' && (str_contains($incField,'self') || str_contains($incField,'sep') || str_contains($incField,'senp'))) { $s += 4; }
    if ($occ === 'professional'  && str_contains($incField,'professional')){ $s += 4; }

    return min(97, max(20, $s));
}


// ═══════════════════════════════════════════════════════════
//  WHY TEXT BUILDER
// ═══════════════════════════════════════════════════════════
function buildWhyText(array $l, int $cibil, float $ltv, string $propClean, string $city, string $occ, int $rank): string {
    $roi    = (float)($l['roi_min']  ?? 0);
    $maxLtv = (float)($l['max_ltv'] ?? 0);
    $minCib = (int)($l['min_cibil'] ?? 0);
    $notes  = trim($l['notes']      ?? '');
    $product= strtolower($l['product'] ?? '');

    // Adjust LTV if suspiciously high for LAP (likely HL LTV in a combined product)
    $displayLtv = $maxLtv;
    if ($maxLtv > 75 && str_contains($product, 'home')) {
        $displayLtv = 0; // don't show unreliable LTV
    }

    $r = [];

    if ($rank === 1) $r[] = "Best overall match for your profile";

    // ROI
    if ($roi <= 0)        $r[] = "ROI disclosed on application — offers customised pricing";
    elseif ($roi <= 8.5)  $r[] = "excellent ROI starting at just {$roi}%";
    elseif ($roi <= 9.5)  $r[] = "competitive ROI from {$roi}%";
    elseif ($roi <= 11.0) $r[] = "ROI {$roi}% — reasonable for this profile";
    else                  $r[] = "ROI {$roi}% — flexible eligibility lender";

    // LTV — only mention when it's meaningful (requested LTV > 25% and LTV data is reliable)
    if ($displayLtv > 0 && $ltv > 25) {
        if ($displayLtv >= $ltv)      $r[] = "LTV up to {$displayLtv}% covers your requirement";
        elseif ($displayLtv >= 60)    $r[] = "LTV up to {$displayLtv}%";
    } elseif ($displayLtv > 0 && $ltv <= 25) {
        $r[] = "your requested LTV is very conservative — easily fundable";
    }

    // CIBIL
    if ($minCib === 0)                $r[] = "flexible CIBIL policy — case-to-case evaluation";
    elseif ($cibil > 0 && $cibil >= $minCib) {
        $h = $cibil - $minCib;
        if ($h >= 80) $r[] = "CIBIL {$cibil} comfortably exceeds min {$minCib}";
        else          $r[] = "CIBIL {$cibil} meets required {$minCib}";
    }

    // Property — only mention if explicitly found in lender's data
    if ($propClean) {
        $pa = strtolower(($l['property_allowed'] ?? '') . ' ' . ($l['property_type'] ?? ''));
        if (str_contains($pa, $propClean)) {
            $r[] = ucwords($propClean) . " title is listed in policy";
        }
        // If not found, DO NOT claim property is accepted
    }

    // City
    if ($city && str_contains(strtolower($l['city_allowed'] ?? ''), $city)) {
        $r[] = "active branch in " . ucfirst($city);
    }

    // Sales contact
    $mgr = trim($l['sales_manager_name']   ?? '');
    $mob = trim($l['sales_manager_mobile'] ?? '');
    if ($mgr && $mob) $r[] = "Speak to: {$mgr} ({$mob})";

    // Short useful notes only
    if ($notes && strlen($notes) > 5 && strlen($notes) < 90) $r[] = $notes;

    if (empty($r)) $r[] = "Matches your loan type and city — verify property and income with lender";

    return implode('; ', array_slice($r, 0, 4)) . '.';
}


// ═══════════════════════════════════════════════════════════
//  ENRICH TOP 3 WITH AI-POWERED DETAILED EXPLANATION
// ═══════════════════════════════════════════════════════════
function enrichTopThree(
    array  $lenders,
    int    $cibil,
    int    $income,
    int    $emi,
    float  $ltv,
    int    $amountLakh,
    string $propClean,
    string $city,
    string $occ,
    string $loanType
): array {
    // Build a compact customer profile for GPT
    $profileDesc = implode(', ', array_filter([
        strtoupper($loanType) . " loan",
        $amountLakh ? "₹{$amountLakh}L loan amount" : null,
        $ltv > 0 ? "LTV {$ltv}%" : null,
        $cibil ? "CIBIL {$cibil}" : null,
        $income ? "income ₹" . number_format($income) . "/mo" : null,
        $emi  ? "existing EMI ₹" . number_format($emi) . "/mo" : null,
        $propClean ? ucwords($propClean) . " property" : null,
        $city ? ucfirst($city) : null,
        $occ ? ucwords(str_replace('_', ' ', $occ)) : null,
    ]));

    foreach ($lenders as $i => &$l) {
        if ($i >= 3) break; // Only enrich top 3

        $rank    = $i + 1;
        $name    = $l['lender_name'];
        $roi     = ($l['roi_min'] > 0) ? "{$l['roi_min']}%–{$l['roi_max']}%" : "on request";
        $ltv_val = $l['max_ltv'] > 0 ? "{$l['max_ltv']}%" : "as per policy";
        $prop    = $l['property_allowed'] ? substr($l['property_allowed'], 0, 120) : '';
        $notes   = $l['notes'] ?? '';
        $special = $l['special_profiles'] ?? '';
        $programs= $l['programs'] ?? '';

        $prompt = "You are Diva, an expert Indian loan advisor for WealthMetre, Jaipur.

Customer: {$profileDesc}

Lender #{$rank}: {$name}
ROI: {$roi}
Max LTV: {$ltv_val}
Property accepted: {$prop}
Notes: {$notes}
Special programs: {$special}
Programs: {$programs}

Write a concise, specific 2–3 sentence explanation of WHY this lender is recommended #$rank for THIS customer's exact profile. 
Be specific — mention actual numbers (ROI, LTV), property type match, CIBIL flexibility, or special programs that match.
Do NOT be generic. Do NOT use bullet points. Plain paragraph only.
Focus on what makes this lender uniquely suitable vs others.";

        $msgs = [
            ['role' => 'system', 'content' => 'You are Diva, expert Indian loan consultant for WealthMetre. Be precise, factual, and helpful. 2-3 sentences max.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $aiExplanation = chatCompletion($msgs, 180);
        if ($aiExplanation) {
            $l['ai_reason'] = trim($aiExplanation);
        }

        // score_breakdown assigned in scoring loop above
    }
    unset($l);

    return $lenders;
}

// ── Score breakdown — shows WHY score is what it is ──────
function buildScoreBreakdown(array $l, int $cibil, float $ltv, int $amountLakh, string $propClean, string $city, string $occ): array {
    $roi    = (float)($l['roi_min'] ?? 0);
    $maxLtv = (float)($l['max_ltv'] ?? 0);
    $minCib = (int)($l['min_cibil'] ?? 0);

    $factors = [];

    // ROI factor
    if ($roi <= 0)        $factors['roi'] = ['label' => 'ROI', 'points' => '+5',  'note' => 'On request'];
    elseif ($roi <= 8.5)  $factors['roi'] = ['label' => 'ROI', 'points' => '+25', 'note' => "Excellent at {$roi}%"];
    elseif ($roi <= 9.5)  $factors['roi'] = ['label' => 'ROI', 'points' => '+18', 'note' => "Competitive at {$roi}%"];
    elseif ($roi <= 10.5) $factors['roi'] = ['label' => 'ROI', 'points' => '+13', 'note' => "Fair at {$roi}%"];
    else                  $factors['roi'] = ['label' => 'ROI', 'points' => '+8',  'note' => "Higher at {$roi}%"];

    // LTV factor
    if ($ltv > 0 && $ltv < 30) {
        $factors['ltv'] = ['label' => 'LTV', 'points' => '+6', 'note' => 'Low LTV — easily covered'];
    } elseif ($ltv > 0 && $maxLtv >= $ltv) {
        $factors['ltv'] = ['label' => 'LTV', 'points' => '+14', 'note' => "Covers your {$ltv}% need"];
    } elseif ($maxLtv >= 65) {
        $factors['ltv'] = ['label' => 'LTV', 'points' => '+7', 'note' => "Good LTV at {$maxLtv}%"];
    } else {
        $factors['ltv'] = ['label' => 'LTV', 'points' => '+4', 'note' => 'LTV as per policy'];
    }

    // CIBIL factor
    if ($minCib === 0) {
        $factors['cibil'] = ['label' => 'CIBIL', 'points' => '+6', 'note' => 'Case-to-case (flexible)'];
    } elseif ($cibil > 0 && $cibil >= $minCib) {
        $h = $cibil - $minCib;
        $pts = $h >= 80 ? '+12' : ($h >= 30 ? '+8' : '+2');
        $factors['cibil'] = ['label' => 'CIBIL', 'points' => $pts, 'note' => "Score {$cibil} ≥ min {$minCib}"];
    } else {
        $factors['cibil'] = ['label' => 'CIBIL', 'points' => '-10', 'note' => "Below min {$minCib}"];
    }

    // Property factor
    if ($propClean) {
        $pa = strtolower(($l['property_allowed'] ?? '') . ' ' . ($l['property_type'] ?? ''));
        if (str_contains($pa, $propClean)) {
            $factors['property'] = ['label' => 'Property', 'points' => '+12', 'note' => ucwords($propClean) . ' accepted'];
        } else {
            $factors['property'] = ['label' => 'Property', 'points' => '-12', 'note' => 'Not explicitly listed'];
        }
    }

    // City factor
    $cityField = strtolower($l['city_allowed'] ?? '');
    if (!$cityField) {
        $factors['city'] = ['label' => 'City', 'points' => '+5', 'note' => 'All cities covered'];
    } elseif ($city && str_contains($cityField, $city)) {
        $factors['city'] = ['label' => 'City', 'points' => '+10', 'note' => ucfirst($city) . ' explicitly listed'];
    } elseif ($city && str_contains($cityField, cityToState($city))) {
        $factors['city'] = ['label' => 'City', 'points' => '+5', 'note' => 'State coverage'];
    } else {
        $factors['city'] = ['label' => 'City', 'points' => '-5', 'note' => 'City not in list'];
    }

    return $factors;
}


// ═══════════════════════════════════════════════════════════
//  RAG + AI (only when configured)
// ═══════════════════════════════════════════════════════════
function ragEnrichment(string $query, array $lenders): ?string {
    $emb = getEmbedding($query);
    if (!$emb) return null;

    // Fetch more hits to ensure diversity across lenders after dedup
    $hits = qdrantSearch($emb, 18);
    if (!$hits) return null;

    $topNames   = array_column($lenders, 'lender_name');
    $topNamesLC = array_map('strtolower', $topNames);

    // Group chunks by lender_id — keep best scoring chunk per lender
    $byLender = [];
    foreach ($hits as $h) {
        $p         = $h['payload'] ?? [];
        $lid       = (int)($p['lender_id'] ?? 0);
        $name      = $p['lender_name'] ?? '';
        $score     = (float)($h['score'] ?? 0);
        $chunkType = $p['chunk_type'] ?? 'general';
        $text      = $p['text_preview'] ?? '';

        if (!$lid || !$name || !$text) continue;

        // Boost chunks from already-shortlisted lenders
        $isShortlisted = in_array(strtolower($name), $topNamesLC);
        $boostedScore  = $isShortlisted ? $score + 0.2 : $score;

        if (!isset($byLender[$lid]) || $boostedScore > $byLender[$lid]['score']) {
            $byLender[$lid] = [
                'name'       => $name,
                'score'      => $boostedScore,
                'chunk_type' => $chunkType,
                'text'       => $text,
                'roi_min'    => $p['roi_min'] ?? 0,
                'max_ltv'    => $p['max_ltv'] ?? 0,
                'product'    => $p['product'] ?? '',
            ];
        }
    }

    if (empty($byLender)) return null;

    // Sort by boosted score, take top 5 unique lenders
    usort($byLender, fn($a, $b) => $b['score'] <=> $a['score']);
    $topHits = array_slice($byLender, 0, 5);

    // Calculate confidence from top hit scores
    $rawScores   = array_column($topHits, 'score');
    $maxScore    = !empty($rawScores) ? max($rawScores) : 0;
    $avgScore    = !empty($rawScores) ? array_sum($rawScores) / count($rawScores) : 0;
    // Remove boost (0.2) to get real Qdrant score
    $confidence  = max(0, min(1, $maxScore - 0.1));

    // Build context
    $ctx = '';
    foreach ($topHits as $h) {
        $roi = $h['roi_min'] > 0 ? $h['roi_min'].'%' : 'on request';
        $ltv = $h['max_ltv'] > 0 ? $h['max_ltv'].'%' : '';
        $ctx .= "=== {$h['name']} ({$h['product']}) [{$h['chunk_type']}] ===\n";
        $ctx .= "ROI: {$roi}" . ($ltv ? ", LTV: {$ltv}" : '') . "\n";
        $ctx .= $h['text'] . "\n\n";
    }

    if (!$ctx) return null;

    $names = implode(', ', array_slice($topNames, 0, 5));
    $msgs  = [
        ['role' => 'system', 'content' => 'You are Diva, WealthMetre loan advisor. Use ONLY the provided policy context. Do not invent information.'],
        ['role' => 'user',   'content' => "Shortlisted lenders: {$names}\n\nPolicy evidence:\n{$ctx}\nCustomer query: {$query}\n\nWrite 2 specific sentences using actual policy details above."],
    ];
    return chatCompletion($msgs, 200);
}

// ── RAG Confidence Checker ──────────────────────────────
function getRagConfidence(string $query): float {
    $emb  = getEmbedding($query);
    if (!$emb) return 0.0;
    $hits = qdrantSearch($emb, 5);
    if (!$hits) return 0.0;
    $scores = array_map(fn($h) => (float)($h['score'] ?? 0), $hits);
    return !empty($scores) ? max($scores) : 0.0;
}

// ── Dify Fallback ───────────────────────────────────────
function callDifyFallback(string $query, string $loanType = 'home'): ?string {
    $keys = [
        'home' => defined('DIFY_HOME_KEY') ? DIFY_HOME_KEY : '',
        'lap'  => defined('DIFY_LAP_KEY')  ? DIFY_LAP_KEY  : '',
    ];
    $apiKey = $keys[$loanType] ?? $keys['home'];
    if (!$apiKey) return null;
    $ch = curl_init('https://api.dify.ai/v1/chat-messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'inputs'          => [],
            'query'           => $query,
            'response_mode'   => 'blocking',
            'conversation_id' => '',
            'user'            => 'wealthmetre_diva'
        ])
    ]);
    $res  = curl_exec($ch);
    curl_close($ch);
    $data = json_decode((string)$res, true);
    return $data['answer'] ?? null;
}

// ── Smart RAG Router ────────────────────────────────────
function smartRagQuery(string $query, array $lenders, string $loanType = 'home'): array {
    $confidence = getRagConfidence($query);
    if ($confidence >= 0.75) {
        $answer = ragEnrichment($query, $lenders);
        return ['answer' => $answer, 'source' => 'php_rag', 'confidence' => $confidence];
    }
    if ($confidence >= 0.50) {
        $answer = ragEnrichment($query, $lenders);
        return ['answer' => $answer, 'source' => 'php_rag_low', 'confidence' => $confidence];
    }
    $difyAnswer = callDifyFallback($query, $loanType);
    if ($difyAnswer) {
        return ['answer' => $difyAnswer, 'source' => 'dify_fallback', 'confidence' => 0.85];
    }
    return ['answer' => ragEnrichment($query, $lenders), 'source' => 'php_rag_fallback', 'confidence' => $confidence];
}

function generateAiNote(array $lenders, int $cibil, int $income, int $emi, string $city, string $prop, string $loanType): ?string {
    $list = '';
    foreach (array_slice($lenders, 0, 3) as $l) {
        $roi  = ($l['roi_min'] > 0) ? $l['roi_min'] . '%' : 'on request';
        $list .= "• {$l['lender_name']}: ROI {$roi}, LTV {$l['max_ltv']}%\n";
    }
    $msgs = [
        ['role' => 'system', 'content' => 'You are Diva, expert Indian loan advisor for WealthMetre. Be concise — 2 sentences.'],
        ['role' => 'user',   'content' => "Customer: {$loanType} in " . ucfirst($city) . ", " . ucwords($prop) . " property. CIBIL {$cibil}, income ₹{$income}, EMI ₹{$emi}.\nTop matches:\n{$list}\nAdvise briefly."],
    ];
    return chatCompletion($msgs, 180);
}


// ═══════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════


function getGeoScore(string $city, int $maxKm, array $distances): array {
    $distance = getDistanceFromJaipur($city, $distances);
    if ($distance === null) {
        try { getDB()->prepare("INSERT INTO geo_unknown_cities (city_name,seen_count,last_seen) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE seen_count=seen_count+1,last_seen=NOW()")->execute([$city]); } catch(\Throwable $e){}
        return ["score"=>0,"status"=>"manual_review","reason"=>"City distance unknown — verify geo with lender","distance_km"=>null];
    }
    if ($distance <= $maxKm)
        return ["score"=>8,"status"=>"within_radius","reason"=>"Within radius: {$distance} km of {$maxKm} km","distance_km"=>$distance];
    if ($distance <= ($maxKm + 25))
        return ["score"=>-3,"status"=>"slightly_outside","reason"=>"Slightly outside: {$distance} km vs {$maxKm} km — verify with lender","distance_km"=>$distance];
    return ["score"=>-10,"status"=>"outside_radius","reason"=>"Outside radius: {$distance} km vs {$maxKm} km","distance_km"=>$distance];
}
// Strip chip UI labels e.g. "JDA (Jaipur Development Authority)" → "jda"
function cleanChipLabel(string $raw): string {
    if (!$raw) return '';
    $clean = preg_replace('/[\(\[].*/', '', $raw);   // remove (...)
    $clean = explode('/', $clean)[0];                 // take before /
    $clean = strtolower(trim($clean));
    return explode(' ', $clean)[0];                   // first word
}

// Map occupation chip labels to clean keys
function normaliseOccupation(string $raw): string {
    $raw = strtolower($raw);
    if (str_contains($raw,'government') || str_contains($raw,'psu') || str_contains($raw,'sarkari')) return 'govt';
    if (str_contains($raw,'salaried')   || str_contains($raw,'salary'))                              return 'salaried';
    if (str_contains($raw,'self')       || str_contains($raw,'proprietor') || str_contains($raw,'sep') || str_contains($raw,'senp')) return 'self_employed';
    if (str_contains($raw,'professional')|| str_contains($raw,'doctor') || str_contains($raw,'ca ') || str_contains($raw,'lawyer')) return 'professional';
    if (str_contains($raw,'business')   || str_contains($raw,'owner') || str_contains($raw,'director'))                            return 'self_employed';
    if (str_contains($raw,'nri'))        return 'nri';
    return '';
}

function buildProfileFromGET(array $g): array {
    return [
        'loan_type'          => sanitize($g['product']    ?? $g['loan_type']  ?? ''),
        'city'               => sanitize($g['city']       ?? DEFAULT_CITY),
        'property_type'      => sanitize($g['property']   ?? ''),
        'property_title'     => sanitize($g['property']   ?? ''),
        'cibil_score'        => intSafe($g['cibil']       ?? 0),
        'cibil'              => intSafe($g['cibil']       ?? 0),
        'amount'             => intSafe($g['amount_lakh'] ?? 0) * 100000,
        'loan_amount'        => intSafe($g['amount_lakh'] ?? 0) * 100000,
        'monthly_income'     => intSafe($g['income']      ?? 0),
        'existing_emis'      => intSafe($g['emi']         ?? 0),
        'property_valuation' => intSafe($g['valuation']   ?? 0),
        'occupation'         => sanitize($g['occupation'] ?? ''),
        'property_usage'     => sanitize($g['usage']      ?? 'residential'),
    ];
}

function parseNL(string $text): array {
    $t = strtolower(trim($text));
    $p = [];
    $tm = ['lap'=>'lap','loan against property'=>'lap','home loan'=>'home','housing'=>'home',
           'business loan'=>'business','working capital'=>'business','msme'=>'business',
           'car loan'=>'car','personal loan'=>'personal','education loan'=>'education'];
    foreach ($tm as $k => $v) { if (str_contains($t, $k)) { $p['loan_type'] = $v; break; } }
    if (preg_match('/(\d+(\.\d+)?)\s*(cr|crore)/i',        $t, $m)) $p['amount'] = (int)round((float)$m[1]*10000000);
    elseif (preg_match('/(\d+(\.\d+)?)\s*(lakh|lac|l)\b/i', $t, $m)) $p['amount'] = (int)round((float)$m[1]*100000);
    if (preg_match('/(?:income|salary)\s*[:\-]?\s*(\d{2,3})\s*k/i', $t, $m)) $p['monthly_income'] = (int)$m[1]*1000;
    if (preg_match('/emi\s*[:\-]?\s*(\d{2,3})\s*k/i',               $t, $m)) $p['existing_emis']  = (int)$m[1]*1000;
    if (str_contains($t,'no emi')) $p['existing_emis'] = 0;
    if (preg_match('/(?:cibil|score)\s*[:\-]?\s*(\d{3})\b/i', $t, $m)) $p['cibil_score'] = (int)$m[1];
    elseif (preg_match('/\b(\d{3})\b/',$t,$m) && (int)$m[1]>=300 && (int)$m[1]<=900) $p['cibil_score']=(int)$m[1];
    foreach (['jda','society','rhb','freehold','panchayati','panchayat','riico','nagar nigam','nagar palika','sorp','socp'] as $pt) {
        if (str_contains($t, $pt)) { $p['property_type'] = $pt; break; }
    }
    foreach (['jaipur','kota','udaipur','jodhpur','ajmer','bikaner','alwar','sikar','churu','bhilwara','kishangarh','bhiwadi','tonk'] as $c) {
        if (str_contains($t, $c)) { $p['city'] = $c; break; }
    }
    return $p;
}