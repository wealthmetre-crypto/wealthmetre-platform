<?php
/**
 * WealthMetre — diva_flow.php  v4
 * Changes vs v3:
 * 1. Language selection as Step 0 (explicit, not auto-detected)
 * 2. "Other" loan type → describe → AI classify → correct path
 * 3. existing_emi → options with Skip (not raw number input)
 * 4. other_secured / other_unsecured added to getRequiredFields
 */

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);

$body    = getJsonBody();
$step    = sanitize($body['step']    ?? 'start');
$answer  = $body['answer'] ?? null;
$profile = is_array($body['profile'] ?? null) ? $body['profile'] : [];

// ── Language from profile (set explicitly at lang_select step) ────
$lang = $profile['_lang'] ?? 'en';

// ── Save + normalize answer ───────────────────────────────
if ($step !== 'start' && $step !== 'lang_select' && $answer !== null
    && !in_array($step, ['collect_name','collect_mobile','loan_type_describe'])) {
    $profile[$step] = $answer;
    normalizeProfile($profile);
}

$loanType = normalizeLoanType($profile['loan_type'] ?? ($step === 'loan_type' ? ($answer ?? '') : ''));
if ($loanType) $profile['loan_type'] = $loanType;


// ═══════════════════════════════════════════════════════════
//  LANGUAGE HELPERS
// ═══════════════════════════════════════════════════════════
function xlate(string $q, string $lang): string {
    if ($lang !== 'hi' || !openAiEnabled()) return $q;
    static $cache = [];
    $key = md5($q);
    if (isset($cache[$key])) return $cache[$key];
    $r = chatCompletion([
        ['role' => 'system', 'content' => 'Translate this loan advisory question to Hinglish (Hindi in Roman script + English financial terms). Keep CIBIL, ROI, LTV, EMI, LAP in English. Return ONLY the translated text.'],
        ['role' => 'user',   'content' => $q],
    ], 100);
    if ($r) $cache[$key] = trim($r);
    return $cache[$key] ?? $q;
}

function L(array $r, string $lang): array {
    if ($lang !== 'hi') return $r;
    foreach (['question','message','note'] as $k) {
        if (!empty($r[$k]) && strlen($r[$k]) < 400) $r[$k] = xlate($r[$k], $lang);
    }
    return $r;
}

function saveContactToDB(array $p): void {
    $name   = sanitize($p['customer_name']   ?? '');
    $mobile = preg_replace('/\D/', '', $p['customer_mobile'] ?? '');
    $sid    = sanitize($p['_session_id']     ?? 'diva_' . uniqid());
    if (!$name || strlen($mobile) < 10) return;
    try {
        $pdo = getDB();
        $tables = $pdo->query("SHOW TABLES LIKE 'diva_leads'")->fetchAll();
        if (!empty($tables)) {
            $chk = $pdo->prepare("SELECT id FROM diva_leads WHERE session_id = ?");
            $chk->execute([$sid]);
            if ($chk->fetch()) {
                $pdo->prepare("UPDATE diva_leads SET customer_name=?, customer_mobile=?, loan_type=COALESCE(NULLIF(?,\"\"),loan_type), city=COALESCE(NULLIF(?,\"\"),city), lang=?, status='contact_saved', steps_completed=GREATEST(steps_completed,2), updated_at=NOW() WHERE session_id=?")
                    ->execute([$name, $mobile, $p['loan_type'] ?? '', $p['city'] ?? '', $p['_lang'] ?? 'en', $sid]);
            } else {
                $pdo->prepare("INSERT INTO diva_leads (session_id,customer_name,customer_mobile,loan_type,city,lang,status,steps_completed) VALUES (?,?,?,?,?,?,'contact_saved',2)")
                    ->execute([$sid, $name, $mobile, $p['loan_type'] ?? '', $p['city'] ?? '', $p['_lang'] ?? 'en']);
            }
        }
        $chk2 = $pdo->prepare("SELECT id FROM website_leads WHERE phone=? AND DATE(created_at)=CURDATE()");
        $chk2->execute([$mobile]);
        if (!$chk2->fetch()) {
            $pdo->prepare("INSERT INTO website_leads (name,phone,loan_type,city,source,created_at) VALUES (?,?,?,?,'diva_ai',NOW())")
                ->execute([$name, $mobile, $p['loan_type'] ?? '', $p['city'] ?? '']);
        }
        if (defined('MSG91_AUTH_KEY') && MSG91_AUTH_KEY) {
            $loan = strtoupper($p['loan_type'] ?? 'Unknown');
            $city = $p['city'] ?? '';
            $lb   = ($p['_lang'] ?? 'en') === 'hi' ? 'Hindi' : 'English';
            $emiNote = !empty($p['_emi_skipped']) ? 'EMI: Not captured (verify on call)' : 'EMI: ' . ($p['existing_emi'] ?? 'Not asked yet');
            $msg  = "*New Diva Lead*\nName: {$name}\nPhone: {$mobile}\nLoan: {$loan}\n"
                  . ($city ? "City: {$city}\n" : '')
                  . "Language: {$lb}\n{$emiNote}\nStatus: Answering questions...";
            $payload = json_encode(['integrated_number' => MSG91_WHATSAPP_FROM_NUMBER, 'content_type' => 'text', 'payload' => ['to' => '917976218596', 'type' => 'text', 'text' => ['body' => $msg]]]);
            $ch = curl_init('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 8, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'authkey: ' . MSG91_AUTH_KEY]]);
            curl_exec($ch); curl_close($ch);
        }
    } catch (\Throwable $e) { error_log('[diva_flow] saveContact: ' . $e->getMessage()); }
}


// ═══════════════════════════════════════════════════════════
//  STEP 0: LANGUAGE SELECTION — always first
// ═══════════════════════════════════════════════════════════
if ($step === 'start') {
    jsonResponse([
        'type'      => 'options',
        'field'     => '_lang',
        'question'  => "Namaste! Main Diva hoon — WealthMetre ki AI Loan Advisor.\nHello! I'm Diva — WealthMetre's AI Loan Advisor.\n\nAap kis bhasha mein baat karna chahenge?\nWhich language would you prefer?",
        'options'   => ['Hindi', 'English'],
        'next_step' => 'lang_select',
        'progress'  => 3,
    ]);
}


// ═══════════════════════════════════════════════════════════
//  STEP 1: LANGUAGE CHOSEN → show loan type options
// ═══════════════════════════════════════════════════════════
if ($step === 'lang_select') {
    $chosen = strtolower(trim($answer ?? ''));
    $profile['_lang'] = (str_contains($chosen, 'hindi') || $chosen === 'hi') ? 'hi' : 'en';
    $lang = $profile['_lang'];

    $q = $lang === 'hi'
        ? "Bahut achha! Main aapko 2 minute mein best lenders shortlist kar doongi — bilkul free.\n\nAapko kaunsa loan chahiye?"
        : "Great! I'll shortlist the best lenders for you in 2 minutes — completely free.\n\nWhat type of loan are you looking for?";

    $opts = $lang === 'hi'
        ? ['Home Loan', 'Loan Against Property (LAP)', 'Business Loan', 'Personal Loan', 'Car Loan', 'Working Capital / OD', 'Education / Institutional Loan', 'Doosra — describe karein']
        : ['Home Loan', 'Loan Against Property (LAP)', 'Business Loan', 'Personal Loan', 'Car Loan', 'Working Capital / OD', 'Education / Institutional Loan', 'Other — describe your need'];

    jsonResponse([
        'type'      => 'options',
        'field'     => 'loan_type',
        'message'   => $lang === 'hi'
            ? "Aapki details sirf lender matching ke liye use hongi. Koi credit check nahi."
            : "Your details will only be used for lender matching. No credit check will be done.",
        'question'  => $q,
        'options'   => $opts,
        'next_step' => 'loan_type',
        'progress'  => 8,
        'profile'   => $profile,
    ]);
}


// ═══════════════════════════════════════════════════════════
//  STEP 2: LOAN TYPE → if Other, ask describe; else ask name
// ═══════════════════════════════════════════════════════════
if ($step === 'loan_type') {
    $profile['loan_type'] = $loanType;

    // ── "Other / Doosra" → ask for description ──────────
    if (in_array($loanType, ['other', 'others', 'doosra'])) {
        jsonResponse(L([
            'type'      => 'text',
            'field'     => 'loan_type_desc',
            'message'   => $lang === 'hi'
                ? "Bilkul! Main aapki help karungi."
                : "No problem! I can help with that.",
            'question'  => $lang === 'hi'
                ? "Aap kis cheez ke liye loan chahiye? Thoda describe karein.\n\nExample: Machinery kharidni hai, Hospital setup, School building, Gold loan, etc."
                : "What do you need the loan for? Please describe briefly.\n\nExample: Machinery purchase, Hospital setup, School construction, Gold loan, etc.",
            'next_step' => 'loan_type_describe',
            'progress'  => 10,
            'profile'   => $profile,
        ], $lang));
    }

    // ── Known loan types → ask name (existing flow) ─────
    $ctxMsgs = [
        'lap'         => $lang === 'hi' ? "Loan Against Property ke liye best lenders dhundhte hain." : "Let's find the best lenders for your Loan Against Property.",
        'home'        => $lang === 'hi' ? "Home Loan ke liye best rate milegi." : "Let's find the best Home Loan rates for you.",
        'car'         => $lang === 'hi' ? "Car Loan options dekhte hain." : "Let's explore Car Loan options for you.",
        'personal'    => $lang === 'hi' ? "Personal Loan ke liye details lete hain." : "Let's get details for your Personal Loan.",
        'business'    => $lang === 'hi' ? "Business Loan ke options dhundhte hain." : "Let's find the best Business Loan options.",
        'education'   => $lang === 'hi' ? "Education / Institutional loan ke options dekhte hain." : "Let's explore Education / Institutional loan options.",
        'workingcap'  => $lang === 'hi' ? "Working Capital ke options dekhte hain." : "Let's find Working Capital options for you.",
    ];
    $ctxMsg = $ctxMsgs[$loanType] ?? ($lang === 'hi' ? "Main aapke liye best option dhundhungi." : "I'll find the best options for you.");

    jsonResponse(L([
        'type'      => 'text',
        'field'     => 'collect_name',
        'message'   => $ctxMsg,
        'question'  => $lang === 'hi'
            ? "Pehle aapka naam bata dijiye."
            : "First, what's your name?",
        'next_step' => 'collect_name',
        'progress'  => 14,
        'profile'   => $profile,
    ], $lang));
}


// ═══════════════════════════════════════════════════════════
//  STEP: OTHER LOAN — classify description → correct path
// ═══════════════════════════════════════════════════════════
if ($step === 'loan_type_describe') {
    $profile['loan_type_desc'] = sanitize($answer ?? '');
    $desc = strtolower($profile['loan_type_desc']);

    // Classify based on keywords
    $securedKw   = ['machinery','machine','equipment','plant','hospital','clinic','nursing','school','college','hotel','resort','construction','factory','warehouse','building','property','land','plot','infrastructure','office','shop'];
    $unsecuredKw = ['gold','education','study','abroad','personal','medical','emergency','wedding','travel','consumer'];

    $securedScore = 0;
    $unsecuredScore = 0;
    foreach ($securedKw   as $kw) { if (str_contains($desc, $kw)) $securedScore++;   }
    foreach ($unsecuredKw as $kw) { if (str_contains($desc, $kw)) $unsecuredScore++; }

    // Default to secured if unclear (more fields = better lender match)
    $profile['loan_type'] = ($unsecuredScore > $securedScore) ? 'other_unsecured' : 'other_secured';
    $loanType = $profile['loan_type'];

    $typeLabel = $lang === 'hi'
        ? ($loanType === 'other_secured' ? 'secured loan (property/asset ke against)' : 'unsecured loan')
        : ($loanType === 'other_secured' ? 'secured loan (against property/asset)' : 'unsecured loan');

    jsonResponse(L([
        'type'      => 'text',
        'field'     => 'collect_name',
        'message'   => $lang === 'hi'
            ? "Samajh gaya! Aapke {$profile['loan_type_desc']} ke liye main {$typeLabel} options dhundhungi."
            : "Got it! For your {$profile['loan_type_desc']}, I'll find {$typeLabel} options.",
        'question'  => $lang === 'hi'
            ? "Pehle aapka naam bata dijiye."
            : "First, what's your name?",
        'next_step' => 'collect_name',
        'progress'  => 14,
        'profile'   => $profile,
    ], $lang));
}


// ═══════════════════════════════════════════════════════════
//  STEP: COLLECT NAME → ask for MOBILE
// ═══════════════════════════════════════════════════════════
if ($step === 'collect_name') {
    $profile['customer_name'] = sanitize($answer ?? '');
    jsonResponse(L([
        'type'      => 'text',
        'field'     => 'collect_mobile',
        'question'  => $lang === 'hi'
            ? "Aur aapka WhatsApp number? (10 digits)\nResults share karte waqt contact bhi kar sakenge."
            : "And your WhatsApp number? (10 digits)\nWe'll share results and connect you with an advisor.",
        'note'      => $lang === 'hi'
            ? "Aapka number sirf aapke WealthMetre advisor ke paas jayega. Koi spam nahi."
            : "Your number goes only to your WealthMetre advisor. No spam calls.",
        'next_step' => 'collect_mobile',
        'progress'  => 20,
        'profile'   => $profile,
    ], $lang));
}


// ═══════════════════════════════════════════════════════════
//  STEP: COLLECT MOBILE → save lead + continue
// ═══════════════════════════════════════════════════════════
if ($step === 'collect_mobile') {
    $mobile = preg_replace('/\D/', '', $answer ?? '');
    if (strlen($mobile) !== 10) {
        jsonResponse(L([
            'type'      => 'text',
            'field'     => 'collect_mobile',
            'question'  => $lang === 'hi'
                ? "Valid 10-digit number enter karein please."
                : "Please enter a valid 10-digit mobile number.",
            'next_step' => 'collect_mobile',
            'progress'  => 20,
            'profile'   => $profile,
        ], $lang));
    }
    $profile['customer_mobile'] = $mobile;
    saveContactToDB($profile);

    $name = $profile['customer_name'] ?? ($lang === 'hi' ? 'aap' : 'there');
    $nextField = getNextMissingField($loanType, $profile);
    if ($nextField) {
        $q = getQuestionForField($nextField, $profile, $lang);
        $q['message'] = $lang === 'hi'
            ? "Shukriya {$name}! Ab kuch aur details..."
            : "Thank you {$name}! Now let's find the best lenders for you.";
        $q['next_step'] = 'flow';
        $q['profile']   = $profile;
        jsonResponse(L($q, $lang));
    }
}


// ═══════════════════════════════════════════════════════════
//  VALIDATIONS
// ═══════════════════════════════════════════════════════════

// Property valuation sanity check
if ($step === 'property_valuation' && intSafe($answer) > 0 && intSafe($answer) < 100000) {
    jsonResponse(L([
        'type'      => 'message',
        'message'   => $lang === 'hi'
            ? "Amount rupees mein enter karein. Example: 50 Lakh = 5000000"
            : "Please enter amount in Rupees. Example: ₹50 Lakh = 5000000",
        'field'     => 'property_valuation',
        'question'  => $lang === 'hi'
            ? "Property ki approximate market value? (Rs mein)"
            : "Property's approximate market value? (in ₹)",
        'next_step' => 'flow',
        'progress'  => 40,
        'profile'   => $profile,
    ], $lang));
}

// LTV warning
if ($step === 'loan_amount' && in_array($loanType, ['lap','home','other_secured'])) {
    $loan = intSafe($answer);
    $val  = intSafe($profile['property_valuation'] ?? 0);
    if ($val > 0 && $loan > 0 && ($loan / $val * 100) > 90) {
        jsonResponse(L([
            'type'    => 'message',
            'message' => $lang === 'hi'
                ? "LTV " . round($loan/$val*100) . "% bahut zyada hai. Most lenders 60-80% allow karte hain."
                : "LTV of " . round($loan/$val*100) . "% is very high. Most lenders allow 60-80%.",
            'options'   => $lang === 'hi'
                ? ['Is amount ke saath continue karein', 'Amount reconsider karunga']
                : ['Continue with this amount', 'I will reconsider the amount'],
            'next_step' => 'flow',
            'profile'   => $profile,
            'progress'  => 46,
        ], $lang));
    }
}

// CIBIL range check
if ($step === 'cibil') {
    $c = intSafe($answer);
    if ($c > 0 && ($c < 300 || $c > 900)) {
        jsonResponse(L([
            'type'      => 'message',
            'message'   => $lang === 'hi'
                ? "CIBIL 300 aur 900 ke beech hona chahiye."
                : "CIBIL score must be between 300 and 900.",
            'field'     => 'cibil',
            'question'  => $lang === 'hi'
                ? "CIBIL Score? (300-900, ya 0 agar pata nahi)"
                : "CIBIL Score? (300-900, or type 0 if unknown)",
            'next_step' => 'flow',
            'progress'  => 74,
            'profile'   => $profile,
        ], $lang));
    }
    if ($c > 0 && $c < 550) $profile['_low_cibil_warned'] = true;
}

// Existing EMI — handle options + skip
if ($step === 'existing_emi') {
    $ans = strtolower(trim($answer ?? ''));
    if (str_contains($ans, 'skip') || str_contains($ans, 'baad') || str_contains($ans, 'later')) {
        $profile['existing_emi']  = 0;
        $profile['_emi_skipped']  = true;
    } elseif (str_contains($ans, 'koi emi nahi') || str_contains($ans, 'no emi') || str_contains($ans, '₹0') || $ans === '0') {
        $profile['existing_emi'] = 0;
    } elseif (str_contains($ans, 'below') || str_contains($ans, '10,000') && !str_contains($ans, '25,000')) {
        $profile['existing_emi'] = 5000;
    } elseif (str_contains($ans, '10') && str_contains($ans, '25')) {
        $profile['existing_emi'] = 17500;
    } elseif (str_contains($ans, '25') && str_contains($ans, '50')) {
        $profile['existing_emi'] = 37500;
    } elseif (str_contains($ans, '50') && str_contains($ans, 'zyada') || str_contains($ans, 'above')) {
        $profile['existing_emi'] = 60000;
    } else {
        $profile['existing_emi'] = intSafe($answer);
    }

    // Calculate FOIR if income known
    $inc = intSafe($profile['monthly_income'] ?? 0);
    $emi = intSafe($profile['existing_emi']);
    if ($inc > 0 && $emi > 0) {
        $profile['foir_pct'] = round(($emi / $inc) * 100, 1);
    }
}


// ═══════════════════════════════════════════════════════════
//  MAIN FLOW ROUTING
// ═══════════════════════════════════════════════════════════
$nextField = getNextMissingField($loanType, $profile);

if ($nextField) {
    $q = getQuestionForField($nextField, $profile, $lang);
    if (empty($q)) {
        $profile[$nextField] = '_skipped';
        $nextField = getNextMissingField($loanType, $profile);
        $q = $nextField ? getQuestionForField($nextField, $profile, $lang) : [];
    }
    if (!empty($q)) {
        $q['next_step'] = 'flow';
        $q['profile']   = $profile;
        if (!empty($profile['_low_cibil_warned']) && $nextField === 'occupation') {
            $q['note'] = $lang === 'hi'
                ? "CIBIL 550 se kam — NBFC options dhundhenge."
                : "CIBIL below 550 — I'll look for NBFCs and flexible lenders.";
        }
        jsonResponse(L($q, $lang));
    }
}

// All fields done → trigger lender search
$name = $profile['customer_name'] ?? '';
$emiNote = !empty($profile['_emi_skipped'])
    ? ($lang === 'hi' ? "\n(EMI details call pe confirm kar lena.)" : "\n(EMI details to be confirmed on call.)")
    : '';

jsonResponse([
    'type'      => 'trigger_agent',
    'message'   => $lang === 'hi'
        ? "Shukriya{$name}! Saari details mil gayi.{$emiNote}\n\n140+ lenders mein aapke profile ke liye best match dhundh raha hoon..."
        : "Thank you{$name}! I have all the information I need.{$emiNote}\n\nSearching through 140+ lenders for the best matches...",
    'next_step' => 'results',
    'progress'  => 97,
    'profile'   => $profile,
]);


// ═══════════════════════════════════════════════════════════
//  NORMALISATION
// ═══════════════════════════════════════════════════════════
function normalizeProfile(array &$p): void {
    foreach (['profession','professional_profile','employment_type','job_profile'] as $alias) {
        if (!empty($p[$alias]) && empty($p['occupation'])) { $p['occupation'] = $p[$alias]; }
        unset($p[$alias]);
    }
    foreach (['income_type','primary_income','employment'] as $alias) {
        if (!empty($p[$alias]) && empty($p['income_source'])) { $p['income_source'] = $p[$alias]; }
        unset($p[$alias]);
    }
    if (!empty($p['income_source'])) {
        $src = strtolower($p['income_source']);
        if (str_contains($src,'salary')||str_contains($src,'salaried')||str_contains($src,'job'))            { $p['income_source'] = 'salaried'; }
        elseif (str_contains($src,'business')||str_contains($src,'self')||str_contains($src,'proprietor'))   { $p['income_source'] = 'self_employed'; }
        elseif (str_contains($src,'govt')||str_contains($src,'government')||str_contains($src,'psu'))        { $p['income_source'] = 'govt'; }
        elseif (str_contains($src,'professional')||str_contains($src,'doctor')||str_contains($src,'ca'))     { $p['income_source'] = 'professional'; }
        elseif (str_contains($src,'rental')||str_contains($src,'rent'))                                      { $p['income_source'] = 'rental'; }
    }
    if (!empty($p['property_title'])) {
        $pt = strtolower($p['property_title']);
        if (str_contains($pt,'society')||str_contains($pt,'flat')||str_contains($pt,'apartment')) $p['property_title']='society';
        elseif (str_contains($pt,'jda'))          $p['property_title']='jda';
        elseif (str_contains($pt,'rhb'))          $p['property_title']='rhb';
        elseif (str_contains($pt,'nagar nigam'))  $p['property_title']='nagar_nigam';
        elseif (str_contains($pt,'nagar palika')) $p['property_title']='nagar_palika';
        elseif (str_contains($pt,'panchayat')||str_contains($pt,'village')) $p['property_title']='panchayati';
        elseif (str_contains($pt,'riico'))        $p['property_title']='riico';
        elseif (str_contains($pt,'freehold')||str_contains($pt,'registry')) $p['property_title']='freehold';
        elseif (str_contains($pt,'agriculture'))  $p['property_title']='agriculture';
    }
    if (!empty($p['income_source']) && empty($p['occupation'])) {
        $src = strtolower(trim($p['income_source']));
        if (str_contains($src,'government')||str_contains($src,'govt')||str_contains($src,'psu'))                                              { $p['occupation']='Salaried - Government / PSU'; }
        elseif (str_contains($src,'private')&&(str_contains($src,'salary')||str_contains($src,'salaried')))                                    { $p['occupation']='Salaried - Private Sector'; }
        elseif ($src==='self_employed'||str_contains($src,'self-employ')||str_contains($src,'proprietor'))                                     { $p['occupation']='Self-Employed / Proprietor'; }
        elseif (str_contains($src,'professional')||str_contains($src,'doctor')||str_contains($src,'lawyer'))                                   { $p['occupation']='Professional (CA / Doctor / Architect)'; }
    }
}


// ═══════════════════════════════════════════════════════════
//  REQUIRED FIELDS per loan type
// ═══════════════════════════════════════════════════════════
function getRequiredFields(string $loanType, array $profile): array {
    switch ($loanType) {
        case 'lap':
            return ['city','property_title','property_type','property_valuation','loan_amount','income_source','monthly_income','existing_emi','cibil','occupation'];
        case 'home':
            return ['city','property_stage','property_title','property_type','property_valuation','loan_amount','income_source','monthly_income','existing_emi','cibil','occupation'];
        case 'car':
            return ['car_condition','car_brand','car_model','car_price','loan_amount','down_payment','income_source','monthly_income','existing_emi','cibil','city'];
        case 'personal':
            $src = $profile['income_source'] ?? '';
            if ($src === 'self_employed') {
                return ['city','income_source','business_type','monthly_income','existing_emi','cibil','itr_available','occupation'];
            }
            return ['city','income_source','monthly_income','company_type','work_experience','existing_emi','cibil','occupation'];
        case 'business':
            return ['city','business_type','business_vintage','annual_turnover','monthly_income','loan_amount','existing_emi','gst_available','itr_available','cibil','occupation'];
        case 'education':
        case 'institutional':
            return ['city','education_purpose','loan_amount','collateral_available','monthly_income','cibil','occupation'];
        case 'workingcap':
            return ['city','business_type','loan_amount','monthly_income','existing_emi','gst_available','cibil','occupation'];
        // ── NEW: Other loan type paths ───────────────────────
        case 'other_secured':
            return ['city','property_title','property_type','property_valuation','loan_amount','income_source','monthly_income','existing_emi','cibil','occupation'];
        case 'other_unsecured':
            return ['city','loan_amount','income_source','monthly_income','existing_emi','cibil','occupation'];
        default:
            return ['city','loan_amount','income_source','monthly_income','existing_emi','cibil','occupation'];
    }
}


// ═══════════════════════════════════════════════════════════
//  QUESTION DEFINITIONS
// ═══════════════════════════════════════════════════════════
function getQuestionForField(string $field, array $profile, string $lang = 'en'): array {
    $loanType = $profile['loan_type'] ?? 'lap';
    $hi = ($lang === 'hi');

    switch ($field) {
        case 'city':
            $ctx = in_array($loanType, ['lap','home','other_secured']) ? ($hi?'property':'property') : ($hi?'aap':'you');
            return ['type'=>'text','field'=>'city',
                'question'=> $hi
                    ? "Aap / property kis city mein hai?\n(Jaipur, Kota, Udaipur, Jodhpur, Delhi, etc.)"
                    : "Which city is the property / applicant located in?\n(e.g., Jaipur, Kota, Udaipur, Jodhpur)",
                'input_type'=>'text','progress'=>26];

        case 'property_stage':
            return ['type'=>'options','field'=>'property_stage',
                'question'=> $hi ? "Property ki current stage kya hai?" : "What is the current stage of the property?",
                'options'=>$hi
                    ? ['Ready / Completed','Resale Property','Under Construction','Plot + Self Construction']
                    : ['Ready / Completed','Resale Property','Under Construction','Plot + Self Construction'],
                'input_type'=>'chips','progress'=>30];

        case 'property_title':
            return ['type'=>'options','field'=>'property_title',
                'question'=> $hi
                    ? "Property ka title / approval authority kya hai?\n\nYeh sabse important question hai — alag title alag banks accept karte hain."
                    : "What is the property title / approval authority?\n\nThis is the most important question — different title types are accepted by different banks.",
                'options'=>['JDA (Jaipur Development Authority)','Society / Flat / Apartment','RHB (Rajasthan Housing Board)','Nagar Nigam','Nagar Palika','Panchayati / Village Patta','RIICO','Freehold / Registry','Agriculture Registry','Others / Not sure'],
                'input_type'=>'chips','progress'=>36];

        case 'property_type':
            return ['type'=>'options','field'=>'property_type',
                'question'=> $hi ? "Property ka use / type kya hai?" : "What is the current usage / type of the property?",
                'options'=>$hi
                    ? ['Residential (Self-Occupied)','Residential (Rented out)','Commercial','Mixed Use (Residential + Commercial)','Industrial','Vacant Plot / Land','Other']
                    : ['Residential (Self-Occupied)','Residential (Rented out)','Commercial','Mixed Use (Residential + Commercial)','Industrial','Vacant Plot / Land','Other'],
                'input_type'=>'chips','progress'=>42];

        case 'property_valuation':
            return ['type'=>'number','field'=>'property_valuation',
                'question'=> $hi
                    ? "Property ki approximate current market value kitni hai? (Rs mein)\n\nExample: 50 Lakh ke liye type karein 5000000"
                    : "What is the approximate current market value of the property? (₹)\n\nExample: type 5000000 for ₹50 Lakhs",
                'input_type'=>'number','progress'=>48];

        case 'loan_amount':
            $hint = '';
            $val  = intSafe($profile['property_valuation'] ?? 0);
            if ($val > 0 && in_array($loanType, ['lap','home','other_secured'])) {
                $suggested = number_format(round($val * 0.6 / 100000) * 100000);
                $hint = $hi
                    ? "\n\nAapki property ke liye typically ₹{$suggested} tak loan mil sakta hai (60% LTV)."
                    : "\n\nBased on your property value, you may get up to ₹{$suggested} (60% LTV).";
            }
            return ['type'=>'number','field'=>'loan_amount',
                'question'=> ($hi ? "Aapko kitna loan chahiye? (Rs mein){$hint}" : "How much loan are you looking for? (₹){$hint}"),
                'input_type'=>'number','progress'=>54];

        case 'car_condition':
            return ['type'=>'options','field'=>'car_condition',
                'question'=> $hi ? "Car new hai ya used?" : "Is the car new or used?",
                'options'=>$hi ? ['New Car','Used / Second-hand Car'] : ['New Car','Used / Second-hand Car'],
                'input_type'=>'chips','progress'=>22];

        case 'car_brand':
            return ['type'=>'options','field'=>'car_brand',
                'question'=> $hi ? "Kaunsa car brand?" : "Which car brand are you looking at?",
                'options'=>['Maruti / Suzuki','Hyundai','Tata','Mahindra','Honda','Toyota','Kia','Skoda / Volkswagen','MG','Ford','Other'],
                'input_type'=>'chips','progress'=>30];

        case 'car_model':
            return ['type'=>'text','field'=>'car_model',
                'question'=> $hi ? "Kaunsa model / variant? (e.g., Swift ZXI, Creta SX)" : "Which car model / variant? (e.g., Swift ZXI, Creta SX, Nexon XZ+)",
                'input_type'=>'text','progress'=>36];

        case 'car_price':
            return ['type'=>'number','field'=>'car_price',
                'question'=> $hi ? "Car ki on-road price kitni hai? (Rs mein, insurance + tax sab include)" : "What is the on-road price of the car? (₹ — include all charges)",
                'input_type'=>'number','progress'=>42];

        case 'down_payment':
            return ['type'=>'number','field'=>'down_payment',
                'question'=> $hi ? "Aap kitna down payment de sakte hain? (Rs mein)" : "How much down payment can you arrange? (₹)",
                'input_type'=>'number','progress'=>48];

        case 'income_source':
            if ($loanType === 'personal') {
                return ['type'=>'options','field'=>'income_source',
                    'question'=> $hi ? "Aap salaried hain ya self-employed?" : "Are you salaried or self-employed?",
                    'options'=>$hi
                        ? ['Salaried (Private / Govt / PSU)','Self-Employed / Business Owner','Professional (CA / Doctor / Lawyer)']
                        : ['Salaried (Private / Govt / PSU)','Self-Employed / Business Owner','Professional (CA / Doctor / Lawyer)'],
                    'input_type'=>'chips','progress'=>30];
            }
            return ['type'=>'options','field'=>'income_source',
                'question'=> $hi ? "Income ka primary source kya hai?" : "What is your primary source of income?",
                'options'=>$hi
                    ? ['Salary - Private Company','Salary - Government / PSU','Business / Self-Employed','Professional (CA / Doctor / Lawyer)','Rental Income','Mixed Income','NRI']
                    : ['Salary - Private Company','Salary - Government / PSU','Business / Self-Employed','Professional (CA / Doctor / Lawyer)','Rental Income','Mixed Income','NRI'],
                'input_type'=>'chips','progress'=>60];

        case 'monthly_income':
            $ctx = ($loanType === 'personal')
                ? ($hi ? "net monthly salary (in-hand)" : "net monthly salary (in-hand)")
                : ($hi ? "total gross monthly income" : "total gross monthly income");
            return ['type'=>'number','field'=>'monthly_income',
                'question'=> $hi
                    ? "Aapki {$ctx} kitni hai? (Rs mein — sab sources include karein)"
                    : "What is your {$ctx}? (₹ — include all income sources)",
                'input_type'=>'number','progress'=>66];

        // ── EXISTING EMI — options with Skip ────────────────
        case 'existing_emi':
            $income = intSafe($profile['monthly_income'] ?? 0);
            $incNote = ($income > 0)
                ? ($hi ? "\n\n(Aapki income: Rs ".number_format($income)."/month)" : "\n\n(Your income: ₹".number_format($income)."/month)")
                : "";
            return ['type'=>'options','field'=>'existing_emi',
                'question'=> $hi
                    ? "Aapki koi existing EMI chal rahi hai? (Optional — skip kar sakte hain){$incNote}"
                    : "Do you have any existing EMIs running? (Optional — you can skip){$incNote}",
                'note'=> $hi
                    ? "Yeh sirf loan eligibility calculate karne ke liye hai. Aapka jawab optional hai."
                    : "This helps calculate your loan eligibility accurately. Your answer is optional.",
                'options'=> $hi
                    ? ['Koi EMI nahi (Rs 0)','Below Rs 10,000/month','Rs 10,000 - Rs 25,000/month','Rs 25,000 - Rs 50,000/month','Rs 50,000 se zyada/month','Skip — baad mein bataunga']
                    : ['No existing EMI (₹0)','Below ₹10,000/month','₹10,000 - ₹25,000/month','₹25,000 - ₹50,000/month','Above ₹50,000/month','Skip — I\'ll tell later'],
                'input_type'=>'chips','progress'=>72];

        case 'cibil':
            $income = intSafe($profile['monthly_income'] ?? 0);
            $emi    = intSafe($profile['existing_emi']   ?? 0);
            $foirHint = '';
            if ($income > 0 && $emi > 0) {
                $foir = round(($emi / $income) * 100, 1);
                $foirHint = $foir > 50
                    ? ($hi ? "\n\nAapka current FOIR {$foir}% hai — kuch lenders 50% se neeche chahte hain." : "\n\nYour current FOIR is {$foir}% — some lenders require it under 50%.")
                    : ($hi ? "\n\nAapka FOIR {$foir}% hai — bilkul theek range mein." : "\n\nYour FOIR is {$foir}% — well within range.");
            }
            return ['type'=>'options','field'=>'cibil',
                'question'=> $hi
                    ? "Last question — aapka CIBIL score approximately kya hai?{$foirHint}\n\nSach bataiye — kam score pe bhi options hote hain. Main wahi lenders dikhaungi jo aapke score ke liye sahi hain."
                    : "Last question — what is your approximate CIBIL score?{$foirHint}\n\nPlease answer honestly — there are options even for lower scores. I'll only show lenders that match your score range.",
                'note'=> $hi
                    ? "Main aapka CIBIL pull NAHI karta. Koi hard inquiry nahi hogi. Aapka score affect nahi hoga."
                    : "I will NOT pull your CIBIL report. No hard inquiry. Your score will not be affected at all.",
                'options'=> $hi
                    ? ['750+ (Excellent)','700 - 750 (Good)','650 - 700 (Average)','600 - 650 (Low)','600 se neeche','Mujhe pata nahi']
                    : ['750+ (Excellent)','700 - 750 (Good)','650 - 700 (Average)','600 - 650 (Low)','Below 600','I don\'t know my score'],
                'input_type'=>'chips','progress'=>82];

        case 'occupation':
            $options = ($loanType === 'car' || $loanType === 'personal')
                ? ['Salaried - Private Sector','Salaried - Government / PSU','Business Owner / Director','Professional (CA / Doctor)','Self-Employed / Freelancer','Student / Fresher','Others']
                : ['Salaried - Private Sector','Salaried - Government / PSU','Self-Employed / Proprietor','Business Owner / Partner / Director','Professional (CA / Doctor / Architect)','Trader / Manufacturer','NRI','Pensioner','Others'];
            return ['type'=>'options','field'=>'occupation',
                'question'=> $hi ? "Aapka occupation / professional profile kya hai?" : "What is your occupation / professional profile?",
                'options'=> $options,'input_type'=>'chips','progress'=>90];

        case 'company_type':
            return ['type'=>'options','field'=>'company_type',
                'question'=> $hi ? "Aap kis type ki company mein kaam karte hain?" : "What type of company do you work for?",
                'options'=>['MNC / Large Corporate','Private Ltd / Listed Company','Government / PSU','SME / Small Business','Startup','Proprietorship / Self-owned','Other'],
                'input_type'=>'chips','progress'=>56];

        case 'work_experience':
            return ['type'=>'options','field'=>'work_experience',
                'question'=> $hi ? "Aapka total work experience kitna hai?" : "How many years of total work experience do you have?",
                'options'=>$hi
                    ? ['1 saal se kam','1-2 saal','2-5 saal','5-10 saal','10 saal se zyada']
                    : ['Less than 1 year','1-2 years','2-5 years','5-10 years','More than 10 years'],
                'input_type'=>'chips','progress'=>62];

        case 'business_type':
            return ['type'=>'options','field'=>'business_type',
                'question'=> $hi ? "Aapka business type kya hai?" : "What is your business type?",
                'options'=>['Proprietorship','Partnership','Private Limited (Pvt Ltd)','LLP','Trader','Manufacturer','Service Provider','Professional (CA / Doctor)'],
                'input_type'=>'chips','progress'=>32];

        case 'business_vintage':
            return ['type'=>'options','field'=>'business_vintage',
                'question'=> $hi ? "Aapka business kitne saal se chal raha hai?" : "How long has your business been operating?",
                'options'=>$hi
                    ? ['1 saal se kam','1-2 saal','2-3 saal','3-5 saal','5 saal se zyada']
                    : ['Less than 1 year','1-2 years','2-3 years','3-5 years','More than 5 years'],
                'input_type'=>'chips','progress'=>40];

        case 'annual_turnover':
            return ['type'=>'number','field'=>'annual_turnover',
                'question'=> $hi
                    ? "Aapka approximate annual turnover kya hai? (Rs mein — last ITR / GST return ke hisaab se)"
                    : "What is your approximate annual turnover? (₹ — as per last ITR / GST return)",
                'input_type'=>'number','progress'=>48];

        case 'gst_available':
            return ['type'=>'options','field'=>'gst_available',
                'question'=> $hi ? "GST registration aur returns available hain?" : "Do you have GST registration and returns available?",
                'options'=>$hi
                    ? ['Haan — GST registered aur returns filed','GST registered hai par returns irregular','GST registration nahi hai']
                    : ['Yes — GST registered & returns filed','GST registered but returns irregular','No GST registration'],
                'input_type'=>'chips','progress'=>76];

        case 'itr_available':
            return ['type'=>'options','field'=>'itr_available',
                'question'=> $hi ? "Income Tax Returns (ITR) available hain?" : "Are Income Tax Returns (ITR) available?",
                'options'=>$hi
                    ? ['Haan — last 2 saal','Haan — sirf last 1 saal','File nahi kiya / Available nahi']
                    : ['Yes — last 2 years','Yes — last 1 year only','Not filed / Not available'],
                'input_type'=>'chips','progress'=>80];

        case 'education_purpose':
            return ['type'=>'options','field'=>'education_purpose',
                'question'=> $hi ? "Loan kis purpose ke liye chahiye?" : "What is the loan purpose?",
                'options'=>$hi
                    ? ['School / College Fees','Videsh mein padhna (Study Abroad)','Hospital / Nursing Home','Hotel / Restaurant','KG / Pre-Primary School','Coaching Institute','Hostel / PG','Doosra Institution']
                    : ['School / College Fees','Study Abroad','Hospital / Nursing Home','Hotel / Restaurant','KG / Pre-Primary School','Coaching Institute','Hostel / PG','Other Institution'],
                'input_type'=>'chips','progress'=>28];

        case 'collateral_available':
            return ['type'=>'options','field'=>'collateral_available',
                'question'=> $hi
                    ? "Koi property hai jo collateral / security ke roop mein de sakte hain?"
                    : "Do you have any property available as collateral / security for this loan?",
                'options'=>$hi
                    ? ['Haan — property available hai collateral ke liye','Nahi — unsecured loan chahiye']
                    : ['Yes — property available as collateral','No — looking for unsecured loan'],
                'input_type'=>'chips','progress'=>56];

        default:
            return [];
    }
}


// ═══════════════════════════════════════════════════════════
//  HELPER: Find first missing required field
// ═══════════════════════════════════════════════════════════
function getNextMissingField(string $loanType, array $profile): ?string {
    $required = getRequiredFields($loanType, $profile);
    foreach ($required as $field) {
        $val = $profile[$field] ?? null;
        if ($val === null || $val === '' || $val === '_skipped') {
            return $field;
        }
    }
    return null;
}
