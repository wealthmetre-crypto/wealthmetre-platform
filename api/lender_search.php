<?php
/**
 * WealthMetre – lender_search.php
 * SQL search engine for the lenders_products table.
 * Called by hybrid_query.php — do NOT add functions
 * that are already defined in config.php or hybrid_query.php.
 *
 * Requires: config.php already loaded (done by hybrid_query.php)
 */

// ── If called directly via browser/POST ──────────────────
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    require_once __DIR__ . '/config.php';
    $body    = getJsonBody();
    $profile = is_array($body['profile'] ?? null) ? $body['profile'] : $body;
    $results = searchLenders($profile);
    jsonResponse(['type' => 'lender_list', 'total' => count($results), 'lenders' => $results]);
    exit;
}

// ═══════════════════════════════════════════════════════════
//  MAIN SEARCH FUNCTION
// ═══════════════════════════════════════════════════════════
function searchLenders(array $p): array {

    // Extract inputs
    $loanType   = normalizeLoanType($p['loan_type'] ?? '');
    $cibil      = intSafe($p['cibil_score']      ?? $p['cibil']        ?? 0);
    $amount     = intSafe($p['loan_amount']      ?? $p['amount']       ?? 0);
    $propRaw    = strtolower(trim($p['property_title'] ?? $p['property_type'] ?? ''));
    $city       = strtolower(trim($p['city']     ?? DEFAULT_CITY));

    $amountLakh = ($amount > 0) ? (int)round($amount / 100000) : 0;

    // Clean property keyword — strip chip label parentheses
    // "JDA (Jaipur Development Authority)" → "jda"
    // "Society / Flat / Apartment" → "society"
    $propWord = '';
    if ($propRaw) {
        $propWord = preg_replace('/[\(\[].*/', '', $propRaw); // remove (...) or [...]
        $propWord = explode('/', $propWord)[0];               // take before /
        $propWord = strtolower(trim($propWord));
        $propWord = explode(' ', $propWord)[0];               // first word only
    }

    // Loan type → SQL search keywords
    $loanKeywords = loanTypeToKeywords($loanType);

    // City → also search for state name (e.g. Jaipur → also search Rajasthan)
    $stateKeyword = cityToState($city);

    try {
        $pdo = getDB();
    } catch (\PDOException $e) {
        if (DEBUG_MODE) error_log('[lender_search] DB: ' . $e->getMessage());
        return [];
    }

    // Try with increasing relaxation until we get enough results
    $rows = queryDB($pdo, $loanKeywords, $cibil, $amountLakh, $city, $stateKeyword, $propWord);

    if (count($rows) < 3) {
        // Relax: remove property filter
        $rows = queryDB($pdo, $loanKeywords, $cibil, $amountLakh, $city, $stateKeyword, '');
    }

    if (count($rows) < 3) {
        // Relax: remove city + property filter
        $rows = queryDB($pdo, $loanKeywords, $cibil, $amountLakh, '', '', '');
    }

    if (count($rows) < 3) {
        // Fully relax: only loan type
        $rows = queryDB($pdo, $loanKeywords, 0, 0, '', '', '');
    }

    return array_map('normalizeLenderRow', $rows);
}


// ═══════════════════════════════════════════════════════════
//  DB QUERY BUILDER
//  NOTE: Property title is NOT used as a hard SQL filter.
//  Reason: many lenders don't populate property_allowed field,
//  so filtering on it would exclude HSBC, IDFC etc. even though
//  they accept JDA. Property match is handled in scoring (+12/-12).
// ═══════════════════════════════════════════════════════════
function queryDB(
    PDO    $pdo,
    array  $loanKeywords,
    int    $cibil,
    int    $amountLakh,
    string $city,
    string $state,
    string $propWord,     // kept for signature compat — not used in WHERE
    int    $limit = 60
): array {
    $where  = ["status = 'active'"];
    $params = [];
    $n      = 0;

    // Loan type — match product OR products_supported
    $loanClauses = [];
    foreach ($loanKeywords as $kw) {
        $kp = ':kwp' . $n;
        $ks = ':kws' . $n;
        $n++;
        $loanClauses[] = "(product LIKE $kp OR products_supported LIKE $ks)";
        $params[$kp]   = '%' . $kw . '%';
        $params[$ks]   = '%' . $kw . '%';
    }
    if ($loanClauses) {
        $where[] = '(' . implode(' OR ', $loanClauses) . ')';
    }

    // CIBIL — 0 in DB means "case-to-case" → always include
    if ($cibil > 0) {
        $where[]          = '(CAST(min_cibil AS UNSIGNED) = 0 OR min_cibil IS NULL OR CAST(min_cibil AS UNSIGNED) <= :cibil)';
        $params[':cibil'] = $cibil;
    }

    // Loan amount — 0 in DB means no restriction → always include
    if ($amountLakh > 0) {
        $where[]         = '(loan_min_lakh = 0 OR loan_min_lakh IS NULL OR loan_min_lakh <= :amin)';
        $params[':amin'] = (float)$amountLakh;
        $where[]         = '(loan_max_lakh = 0 OR loan_max_lakh IS NULL OR loan_max_lakh >= :amax)';
        $params[':amax'] = (float)$amountLakh;
    }

    // City — empty city_allowed means all cities → include
    if ($city) {
        $cityClause       = '(city_allowed IS NULL OR city_allowed = \'\' OR city_allowed LIKE :city';
        $params[':city']  = '%' . $city . '%';
        if ($state) {
            $cityClause      .= ' OR city_allowed LIKE :state';
            $params[':state'] = '%' . $state . '%';
        }
        $cityClause .= ')';
        $where[] = $cityClause;
    }

    // ── Property title: NOT filtered in SQL ──────────────────
    // Reason: property_allowed is inconsistently populated in DB.
    // Many premium lenders (HSBC, IDFC etc.) leave it blank or use
    // different terminology. Hard-filtering would exclude them.
    // Instead, scoreLender() applies +12 (match) or -12 (no match).
    // ─────────────────────────────────────────────────────────

    $sql = 'SELECT * FROM `' . DB_TABLE . '` WHERE ' . implode(' AND ', $where)
         . ' ORDER BY CASE WHEN CAST(roi_min AS DECIMAL(6,2)) > 0 THEN CAST(roi_min AS DECIMAL(6,2)) ELSE 15 END ASC'
         . ', lender_name ASC'
         . ' LIMIT ' . (int)$limit;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        if (DEBUG_MODE) error_log('[queryDB] ' . $e->getMessage() . ' | SQL: ' . $sql);
        return [];
    }
}


// ═══════════════════════════════════════════════════════════
//  LOAN TYPE → SQL KEYWORDS
// ═══════════════════════════════════════════════════════════
function loanTypeToKeywords(string $type): array {
    $map = [
        'lap'      => ['LAP', 'Loan Against Property'],
        'home'     => ['Home Loan', 'HL +', 'Housing Loan', 'HL,'],
        'business' => ['Business', 'MSME', 'Working Capital', 'SME', 'MBL'],
        'car'      => ['Car Loan', 'Vehicle', 'Auto Loan'],
        'personal' => ['Personal Loan'],
        'education'=> ['Education'],
        'equipment'=> ['Equipment', 'Machinery'],
    ];
    // For home loan also match "HL + LAP" entries
    if ($type === 'home') return $map['home'];
    // For LAP also match "HL + LAP" entries
    if ($type === 'lap') return array_merge($map['lap'], ['HL + LAP', 'Home Loan + LAP', 'HL,LAP']);
    return $map[$type] ?? [$type];
}


// ═══════════════════════════════════════════════════════════
//  CITY → STATE KEYWORD
// ═══════════════════════════════════════════════════════════
function cityToState(string $city): string {
    $rajasthan = ['jaipur','kota','udaipur','jodhpur','ajmer','bikaner','alwar','sikar',
                  'churu','jhunjhunu','bhilwara','tonk','dausa','bhiwadi','kishangarh',
                  'barmer','pali','nagaur','bundi','sawai madhopur'];
    if (in_array($city, $rajasthan)) return 'rajasthan';

    $maharashtra = ['mumbai','pune','nashik','nagpur','aurangabad','thane'];
    if (in_array($city, $maharashtra)) return 'maharashtra';

    $karnataka = ['bangalore','bengaluru','mysore','hubli','dharwad'];
    if (in_array($city, $karnataka)) return 'karnataka';

    $gujarat = ['ahmedabad','surat','vadodara','rajkot'];
    if (in_array($city, $gujarat)) return 'gujarat';

    return '';
}


// ═══════════════════════════════════════════════════════════
//  ROW NORMALIZER
// ═══════════════════════════════════════════════════════════
function normalizeLenderRow(array $row): array {
    $roiMin    = (float)($row['roi_min']             ?? 0);
    $roiMax    = (float)($row['roi_max']             ?? 0);
    $rateStart = (float)($row['interest_rate_start'] ?? 0);

    $displayRoi = ($roiMin > 0) ? $roiMin : ($rateStart > 0 ? $rateStart : 0);

    // LTV might be text like "Highest (policy text)" or "100%" or a number
    $ltvRaw  = trim((string)($row['max_ltv'] ?? ''));
    $ltvNum  = 0;
    if (is_numeric($ltvRaw)) {
        $ltvNum = (float)$ltvRaw;
    } elseif (preg_match('/(\d+)/', $ltvRaw, $m)) {
        $ltvNum = (float)$m[1];
    }

    return [
        'lender_name'         => trim($row['lender_name']           ?? ''),
        'lender_key'          => trim($row['lender_key']            ?? ''),
        'product'             => trim($row['product']               ?? ''),
        'products_supported'  => trim($row['products_supported']    ?? ''),
        'interest_rate_start' => $displayRoi,
        'roi_min'             => $roiMin,
        'roi_max'             => $roiMax,
        'max_ltv'             => $ltvNum,
        'max_ltv_text'        => $ltvRaw,
        'min_cibil'           => (int)($row['min_cibil']            ?? 0),
        'loan_min_lakh'       => (float)($row['loan_min_lakh']      ?? 0),
        'loan_max_lakh'       => (float)($row['loan_max_lakh']      ?? 0),
        'max_tenure_months'   => (int)($row['max_tenure_months']    ?? 0),
        'income_types'        => trim($row['income_types']          ?? ''),
        'profile_allowed'     => trim($row['profile_allowed']       ?? ''),
        'special_profiles'    => trim($row['special_profiles']      ?? ''),
        'property_allowed'    => trim($row['property_allowed']      ?? ''),
        'property_type'       => trim($row['property_type']         ?? ''),
        'city_allowed'        => trim($row['city_allowed']          ?? ''),
        'geo_limit'           => trim($row['geo_limit']             ?? ''),
        'prepayment_charges'  => trim($row['prepayment_charges']    ?? 'Nil'),
        'foreclosure_charges' => trim($row['foreclosure_charges']   ?? 'Nil'),
        'guarantor_required'  => trim($row['guarantor_required']    ?? 'Case to Case'),
        'notes'               => trim($row['notes']                 ?? ''),
        'programs'            => trim($row['programs']              ?? ''),
        'sales_manager_name'  => trim($row['sales_manager_name']    ?? ''),
        'sales_manager_mobile'=> trim($row['sales_manager_mobile']  ?? ''),
        'score'               => 50,
        'why'                 => '',
        'rank'                => 0,
        // V2 scoring fields
        'ltv_matrix'          => trim($row['ltv_matrix']       ?? ''),
        'tenure_matrix'       => trim($row['tenure_matrix']    ?? ''),
        'searchable_text'     => trim($row['searchable_text']  ?? ''),
        'dsa_payout'          => trim($row['dsa_payout']       ?? ''),
        'lender_type'         => trim($row['lender_type'] ?? 'nbfc'),
        'eligible'            => true,
        'rejection_risk'      => 'low',
        'match_reasons'       => [],
        'warn_flags'          => [],
        // CIBIL policy
        'cibil_policy'             => trim($row['cibil_policy']              ?? ''),
        // FOIR scoring
        'max_foir_pct'             => (int)($row['max_foir_pct']             ?? 0),
        'unsecured_max_foir'       => (int)($row['unsecured_max_foir']       ?? 0),
        'foir_relaxed_pct'         => (int)($row['foir_relaxed_pct']         ?? 0),
        'foir_relaxation_available'=> (int)($row['foir_relaxation_available']?? 0),
        // Geography
        'city_coverage'            => trim($row['city_coverage']             ?? ''),
        'cities_allowed'           => trim($row['cities_allowed']            ?? ''),
        // Surrogate programs
        'surrogate_banking'        => (int)($row['surrogate_banking']        ?? 0),
        'surrogate_gst'            => (int)($row['surrogate_gst']            ?? 0),
        'surrogate_itr'            => (int)($row['surrogate_itr']            ?? 0),
        'surrogate_rtr'            => (int)($row['surrogate_rtr']            ?? 0),
        'surrogate_gross_profit'   => (int)($row['surrogate_gross_profit']   ?? 0),
        'surrogate_low_ltv'        => (int)($row['surrogate_low_ltv']        ?? 0),
        'surrogate_lip'            => (int)($row['surrogate_lip']            ?? 0),
        'needs_itr'                => (int)($row['needs_itr']                ?? 0),
    ];
}