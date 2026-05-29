<?php
/**
 * WealthMetre — diva_flow.php  COMPLETE v3
 * Upload to: /public_html/api/diva_flow.php
 *
 * Changes vs original:
 * 1. Name + mobile collected at step 2 (every conversation captured)
 * 2. Hindi/Hinglish language detection + translation via OpenAI
 * 3. ALL original functions included below (nothing missing)
 */

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);

$body    = getJsonBody();
$step    = sanitize($body['step']    ?? 'start');
$answer  = $body['answer'] ?? null;
$profile = is_array($body['profile'] ?? null) ? $body['profile'] : [];

// ── Language detection ────────────────────────────────────
if ($answer !== null) {
    $profile['_lang'] = detectLang((string)$answer, $profile);
}
$lang = $profile['_lang'] ?? 'en';

// ── Save + normalize answer ───────────────────────────────
if ($step !== 'start' && $answer !== null && !in_array($step, ['collect_name','collect_mobile'])) {
    $profile[$step] = $answer;
    normalizeProfile($profile);
}

$loanType = normalizeLoanType($profile['loan_type'] ?? ($step === 'loan_type' ? ($answer ?? '') : ''));
if ($loanType) $profile['loan_type'] = $loanType;


// ═══════════════════════════════════════════════════════════
//  LANGUAGE HELPERS
// ═══════════════════════════════════════════════════════════
function detectLang(string $text, array $p): string {
    if (!empty($p['_lang'])) return $p['_lang'];
    if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) return 'hi';
    $hw = ['mujhe','chahiye','mera','ghar','nahi','kya','hai','hain','kitna','milega',
           'batao','mein','aur','bhi','karo','karna','property','loan','paisa','rupaya'];
    $lc = strtolower($text);
    $hits = 0;
    foreach ($hw as $w) { if (str_contains($lc, $w)) $hits++; }
    return $hits >= 2 ? 'hi' : 'en';
}

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
        // Check if diva_leads table exists
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
        // Always mirror to website_leads
        $chk2 = $pdo->prepare("SELECT id FROM website_leads WHERE phone=? AND DATE(created_at)=CURDATE()");
        $chk2->execute([$mobile]);
        if (!$chk2->fetch()) {
            $pdo->prepare("INSERT INTO website_leads (name,phone,loan_type,city,source,created_at) VALUES (?,?,?,?,'diva_ai',NOW())")
                ->execute([$name, $mobile, $p['loan_type'] ?? '', $p['city'] ?? '']);
        }
        // WhatsApp alert
        if (defined('MSG91_AUTH_KEY') && MSG91_AUTH_KEY) {
            $loan = strtoupper($p['loan_type'] ?? 'Unknown');
            $city = $p['city'] ?? '';
            $lb   = ($p['_lang'] ?? 'en') === 'hi' ? '🇮🇳 Hindi' : '🇬🇧 English';
            $msg  = "🤖 *New Diva Lead*\nName: {$name}\nPhone: {$mobile}\nLoan: {$loan}\n" . ($city ? "City: {$city}\n" : '') . "Language: {$lb}\nStatus: Answering questions…";
            $payload = json_encode(['integrated_number' => MSG91_WHATSAPP_FROM_NUMBER, 'content_type' => 'text', 'payload' => ['to' => '917976218596', 'type' => 'text', 'text' => ['body' => $msg]]]);
            $ch = curl_init('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 8, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'authkey: ' . MSG91_AUTH_KEY]]);
            curl_exec($ch); curl_close($ch);
        }
    } catch (\Throwable $e) { error_log('[diva_flow] saveContact: ' . $e->getMessage()); }
}


// ═══════════════════════════════════════════════════════════
//  STEP: GREETING
// ═══════════════════════════════════════════════════════════
if ($step === 'start') {
    jsonResponse([
        'type'      => 'options',
        'field'     => 'loan_type',
        'question'  => "Namaste! 🙏 Main Diva hoon, WealthMetre ki AI Loan Advisor.\n\nKis type ka loan chahiye?\n(What kind of loan are you looking for?)",
        'options'   => ['Loan Against Property (LAP)', 'Home Loan', 'Car Loan', 'Personal Loan', 'Business Loan', 'Education / Institutional Loan', 'Others'],
        'next_step' => 'loan_type',
        'progress'  => 5,
    ]);
}


// ═══════════════════════════════════════════════════════════
//  STEP: LOAN TYPE → ask for NAME next
// ═══════════════════════════════════════════════════════════
if ($step === 'loan_type') {
    $profile['loan_type'] = $loanType;
    $ctxMsgs = [
        'lap'      => "Great! Loan Against Property ke liye best lenders dhundhte hain. 🏢",
        'home'     => "Perfect! Home Loan ke liye best rate milegi. 🏠",
        'car'      => "Excellent! Car Loan options dekhte hain. 🚗",
        'personal' => "Sure! Personal Loan ke baare mein details lete hain. 👤",
        'business' => "Good! Business Loan ke options dhundhte hain. 💼",
        'education'=> "Sure! Education/Institutional loan ke options dekhte hain. 🏫",
    ];
    jsonResponse(L([
        'type'      => 'text',
        'field'     => 'collect_name',
        'message'   => $ctxMsgs[$loanType] ?? "Sure! Main aapke liye best option dhundhungi.",
        'question'  => "Pehle aapka naam bata dijiye 😊\n(What's your name?)",
        'next_step' => 'collect_name',
        'progress'  => 12,
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
        'question'  => "Aur aapka WhatsApp number? (10 digits)\nResults share karte waqt contact bhi kar sakenge.\n(Your WhatsApp / mobile number?)",
        'next_step' => 'collect_mobile',
        'progress'  => 18,
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
            'question'  => "Please enter a valid 10-digit mobile number.",
            'next_step' => 'collect_mobile',
            'progress'  => 18,
            'profile'   => $profile,
        ], $lang));
    }
    $profile['customer_mobile'] = $mobile;
    saveContactToDB($profile);   // 🔑 Save lead to DB immediately

    $name = $profile['customer_name'] ?? 'there';
    $nextField = getNextMissingField($loanType, $profile);
    if ($nextField) {
        $q = getQuestionForField($nextField, $profile);
        $q['message']   = $lang === 'hi'
            ? "Shukriya {$name}! 🙏 Ab loan ke baare mein kuch aur details..."
            : "Thank you {$name}! 🙏 Now let's find the best lenders for you.";
        $q['next_step'] = 'flow';
        $q['profile']   = $profile;
        jsonResponse(L($q, $lang));
    }
}


// ═══════════════════════════════════════════════════════════
//  VALIDATIONS
// ═══════════════════════════════════════════════════════════
if ($step === 'property_valuation' && intSafe($answer) > 0 && intSafe($answer) < 100000) {
    jsonResponse(L(['type'=>'message','message'=>"Amount rupees mein enter karein.\nExample: ₹50 Lakh = 5000000",'field'=>'property_valuation','question'=>"Property ki approximate market value? (₹)",'next_step'=>'flow','progress'=>40,'profile'=>$profile], $lang));
}
if ($step === 'loan_amount' && in_array($loanType, ['lap','home'])) {
    $loan = intSafe($answer); $val = intSafe($profile['property_valuation'] ?? 0);
    if ($val > 0 && $loan > 0 && ($loan / $val * 100) > 90) {
        jsonResponse(L(['type'=>'message','message'=>"⚠️ LTV ".round($loan/$val*100)."% bahut zyada hai. Most lenders 60–80% allow karte hain.",'options'=>['Continue with this amount','I will reconsider'],'next_step'=>'flow','profile'=>$profile,'progress'=>46], $lang));
    }
}
if ($step === 'cibil') {
    $c = intSafe($answer);
    if ($c > 0 && ($c < 300 || $c > 900)) {
        jsonResponse(L(['type'=>'message','message'=>"CIBIL 300 aur 900 ke beech hona chahiye.",'field'=>'cibil','question'=>"CIBIL Score? (300–900, ya 0 agar pata nahi)",'next_step'=>'flow','progress'=>74,'profile'=>$profile], $lang));
    }
    if ($c > 0 && $c < 550) $profile['_low_cibil_warned'] = true;
}
if ($step === 'existing_emi') {
    $inc = intSafe($profile['monthly_income'] ?? 0);
    $emi = intSafe($answer);
    if ($inc > 0 && $emi > 0) $profile['foir_pct'] = round(($emi / $inc) * 100, 1);
}


// ═══════════════════════════════════════════════════════════
//  MAIN ROUTING
// ═══════════════════════════════════════════════════════════
$nextField = getNextMissingField($loanType, $profile);

if ($nextField) {
    $q = getQuestionForField($nextField, $profile);
    if (empty($q)) {
        $profile[$nextField] = '_skipped';
        $nextField = getNextMissingField($loanType, $profile);
        $q = $nextField ? getQuestionForField($nextField, $profile) : [];
    }
    if (!empty($q)) {
        $q['next_step'] = 'flow';
        $q['profile']   = $profile;
        if (!empty($profile['_low_cibil_warned']) && $nextField === 'occupation') {
            $q['note'] = $lang === 'hi'
                ? "⚠️ CIBIL 550 se kam — NBFC options dhundhenge."
                : "⚠️ With CIBIL below 550 — I'll look for NBFCs and flexible lenders.";
        }
        jsonResponse(L($q, $lang));
    }
}

// All fields done → trigger lender search
$triggerMsg = $lang === 'hi'
    ? "✨ Shukriya! Saari details mil gayi.\n\n140+ lenders mein aapke profile ke liye best match dhundh raha hoon…"
    : "✨ Thank you! I have all the information I need.\n\nSearching through 140+ lenders for the best matches for your profile…";

jsonResponse(['type' => 'trigger_agent', 'message' => $triggerMsg, 'next_step' => 'results', 'progress' => 97, 'profile' => $profile]);


// ═══════════════════════════════════════════════════════════
//  NORMALISATION — collapse aliases into canonical fields
// ═══════════════════════════════════════════════════════════
function normalizeProfile(array &$p): void {
    foreach (['profession', 'professional_profile', 'employment_type', 'job_profile'] as $alias) {
        if (!empty($p[$alias]) && empty($p['occupation'])) { $p['occupation'] = $p[$alias]; }
        unset($p[$alias]);
    }
    foreach (['income_type', 'primary_income', 'employment'] as $alias) {
        if (!empty($p[$alias]) && empty($p['income_source'])) { $p['income_source'] = $p[$alias]; }
        unset($p[$alias]);
    }
    if (!empty($p['income_source'])) {
        $src = strtolower($p['income_source']);
        if (str_contains($src, 'salary') || str_contains($src, 'salaried') || str_contains($src, 'job')) { $p['income_source'] = 'salaried'; }
        elseif (str_contains($src, 'business') || str_contains($src, 'self') || str_contains($src, 'proprietor')) { $p['income_source'] = 'self_employed'; }
        elseif (str_contains($src, 'govt') || str_contains($src, 'government') || str_contains($src, 'psu')) { $p['income_source'] = 'govt'; }
        elseif (str_contains($src, 'professional') || str_contains($src, 'doctor') || str_contains($src, 'ca')) { $p['income_source'] = 'professional'; }
        elseif (str_contains($src, 'rental') || str_contains($src, 'rent')) { $p['income_source'] = 'rental'; }
    }
    if (!empty($p['property_title'])) {
        $pt = strtolower($p['property_title']);
        if (str_contains($pt, 'society') || str_contains($pt, 'flat') || str_contains($pt, 'apartment')) $p['property_title'] = 'society';
        elseif (str_contains($pt, 'jda'))          $p['property_title'] = 'jda';
        elseif (str_contains($pt, 'rhb'))          $p['property_title'] = 'rhb';
        elseif (str_contains($pt, 'nagar nigam'))  $p['property_title'] = 'nagar_nigam';
        elseif (str_contains($pt, 'nagar palika')) $p['property_title'] = 'nagar_palika';
        elseif (str_contains($pt, 'panchayat') || str_contains($pt, 'village')) $p['property_title'] = 'panchayati';
        elseif (str_contains($pt, 'riico'))        $p['property_title'] = 'riico';
        elseif (str_contains($pt, 'freehold') || str_contains($pt, 'registry')) $p['property_title'] = 'freehold';
        elseif (str_contains($pt, 'agriculture'))  $p['property_title'] = 'agriculture';
    }
    if (!empty($p['income_source']) && empty($p['occupation'])) {
        $src = strtolower(trim($p['income_source']));
        if (str_contains($src, 'government') || str_contains($src, 'govt') || str_contains($src, 'psu')) { $p['occupation'] = 'Salaried – Government / PSU'; }
        elseif (str_contains($src, 'private') && (str_contains($src, 'salary') || str_contains($src, 'salaried'))) { $p['occupation'] = 'Salaried – Private Sector'; }
        elseif ($src === 'self_employed' || str_contains($src, 'self-employ') || str_contains($src, 'proprietor')) { $p['occupation'] = 'Self-Employed / Proprietor'; }
        elseif (str_contains($src, 'professional') || str_contains($src, 'doctor') || str_contains($src, 'lawyer')) { $p['occupation'] = 'Professional (CA / Doctor / Architect)'; }
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
        default:
            return ['city','loan_amount','income_source','monthly_income','existing_emi','cibil','occupation'];
    }
}


// ═══════════════════════════════════════════════════════════
//  QUESTION DEFINITIONS
// ═══════════════════════════════════════════════════════════
function getQuestionForField(string $field, array $profile): array {
    $loanType = $profile['loan_type'] ?? 'lap';

    switch ($field) {
        case 'city':
            $ctx = in_array($loanType, ['lap','home']) ? 'property' : 'your';
            return ['type'=>'text','field'=>'city','question'=>"In which city is {$ctx} located?\n(e.g., Jaipur, Kota, Udaipur, Jodhpur)",'input_type'=>'text','progress'=>20];

        case 'property_stage':
            return ['type'=>'options','field'=>'property_stage','question'=>"What is the current stage of the property?",'options'=>['Ready / Completed','Resale Property','Under Construction','Plot + Self Construction'],'input_type'=>'chips','progress'=>22];

        case 'property_title':
            return ['type'=>'options','field'=>'property_title','question'=>"What is the property title / approval authority?",'options'=>['JDA (Jaipur Development Authority)','Society / Flat / Apartment','RHB (Rajasthan Housing Board)','Nagar Nigam','Nagar Palika','Panchayati / Village Patta','RIICO','Freehold / Registry','Agriculture Registry','Others'],'input_type'=>'chips','progress'=>28];

        case 'property_type':
            return ['type'=>'options','field'=>'property_type','question'=>"What is the current usage / type of the property?",'options'=>['Residential (Self-Occupied)','Residential (Rented)','Commercial','Mixed Use (Residential + Commercial)','Industrial','Vacant Plot / Land','Others'],'input_type'=>'chips','progress'=>34];

        case 'property_valuation':
            return ['type'=>'number','field'=>'property_valuation','question'=>"What is the approximate current market value of the property? (₹)\n\n(e.g., type 5000000 for ₹50 Lakhs)",'input_type'=>'number','progress'=>40];

        case 'loan_amount':
            $hint = '';
            $val  = intSafe($profile['property_valuation'] ?? 0);
            if ($val > 0 && in_array($loanType, ['lap','home'])) {
                $suggested = number_format(round($val * 0.6 / 100000) * 100000);
                $hint = "\n\nTypically lenders offer 50–70% of property value. For your property that would be around ₹{$suggested}.";
            }
            return ['type'=>'number','field'=>'loan_amount','question'=>"How much loan amount are you looking for? (₹){$hint}",'input_type'=>'number','progress'=>46];

        case 'car_condition':
            return ['type'=>'options','field'=>'car_condition','question'=>"Is the car new or used?",'options'=>['New Car','Used / Second-hand Car'],'input_type'=>'chips','progress'=>18];

        case 'car_brand':
            return ['type'=>'options','field'=>'car_brand','question'=>"Which car brand are you looking at?",'options'=>['Maruti / Suzuki','Hyundai','Tata','Mahindra','Honda','Toyota','Kia','Skoda / Volkswagen','MG','Ford','Other'],'input_type'=>'chips','progress'=>24];

        case 'car_model':
            return ['type'=>'text','field'=>'car_model','question'=>"Which car model / variant? (e.g., Swift ZXI, Creta SX, Nexon XZ+)",'input_type'=>'text','progress'=>30];

        case 'car_price':
            return ['type'=>'number','field'=>'car_price','question'=>"What is the on-road price of the car? (₹)\n\n(Include all charges — ex-showroom + tax + insurance)",'input_type'=>'number','progress'=>36];

        case 'down_payment':
            return ['type'=>'number','field'=>'down_payment','question'=>"How much down payment can you arrange? (₹)\n\n(Most lenders fund 85–90% of on-road price)",'input_type'=>'number','progress'=>42];

        case 'income_source':
            if ($loanType === 'personal') {
                return ['type'=>'options','field'=>'income_source','question'=>"Are you salaried or self-employed?",'options'=>['Salaried (Private / Govt / PSU)','Self-Employed / Business Owner','Professional (CA / Doctor / Lawyer)'],'input_type'=>'chips','progress'=>22];
            }
            return ['type'=>'options','field'=>'income_source','question'=>"What is your primary source of income?",'options'=>['Salary – Private Company','Salary – Government / PSU','Business / Self-Employed','Professional (CA / Doctor / Lawyer)','Rental Income','Mixed Income','NRI'],'input_type'=>'chips','progress'=>52];

        case 'monthly_income':
            $ctx = ($loanType === 'personal') ? "net monthly salary (in-hand)" : "gross monthly income";
            return ['type'=>'number','field'=>'monthly_income','question'=>"What is your total {$ctx}? (₹)\n\n(Include all income sources)",'input_type'=>'number','progress'=>58];

        case 'existing_emi':
            $income = intSafe($profile['monthly_income'] ?? 0);
            $hint   = ($income > 0) ? "\n\n(Your income is ₹".number_format($income)."/mo — this helps calculate your loan eligibility)" : "";
            return ['type'=>'number','field'=>'existing_emi','question'=>"What is your total existing EMI per month? (₹){$hint}\n\nInclude all running loans. Enter 0 if none.",'input_type'=>'number','progress'=>65];

        case 'cibil':
            $income = intSafe($profile['monthly_income'] ?? 0);
            $emi    = intSafe($profile['existing_emi']   ?? 0);
            $foir_hint = '';
            if ($income > 0 && $emi > 0) {
                $foir = round(($emi / $income) * 100, 1);
                $foir_hint = $foir > 50
                    ? "\n\n⚠️ Your current FOIR is {$foir}% — some lenders may require it under 50%. CIBIL score becomes critical."
                    : "\n\n✅ Your FOIR is {$foir}% — well within range.";
            }
            return ['type'=>'number','field'=>'cibil','question'=>"What is your CIBIL / Credit Score?{$foir_hint}\n\n(Enter 300–900. Type 0 if unknown.)",'input_type'=>'number','progress'=>74];

        case 'occupation':
            $options = ($loanType === 'car' || $loanType === 'personal')
                ? ['Salaried – Private Sector','Salaried – Government / PSU','Business Owner / Director','Professional (CA / Doctor)','Self-Employed / Freelancer','Student / Fresher','Others']
                : ['Salaried – Private Sector','Salaried – Government / PSU','Self-Employed / Proprietor','Business Owner / Partner / Director','Professional (CA / Doctor / Architect)','Trader / Manufacturer','NRI','Pensioner','Others'];
            return ['type'=>'options','field'=>'occupation','question'=>"What is your occupation / professional profile?",'options'=>$options,'input_type'=>'chips','progress'=>88];

        case 'company_type':
            return ['type'=>'options','field'=>'company_type','question'=>"What type of company do you work for?",'options'=>['MNC / Large Corporate','Private Ltd / Listed Company','Government / PSU','SME / Small Business','Startup','Proprietorship / Self-owned','Other'],'input_type'=>'chips','progress'=>50];

        case 'work_experience':
            return ['type'=>'options','field'=>'work_experience','question'=>"How many years of total work experience do you have?",'options'=>['Less than 1 year','1–2 years','2–5 years','5–10 years','More than 10 years'],'input_type'=>'chips','progress'=>56];

        case 'business_type':
            return ['type'=>'options','field'=>'business_type','question'=>"What is your business type?",'options'=>['Proprietorship','Partnership','Private Limited (Pvt Ltd)','LLP','Trader','Manufacturer','Service Provider','Professional (CA / Doctor)'],'input_type'=>'chips','progress'=>26];

        case 'business_vintage':
            return ['type'=>'options','field'=>'business_vintage','question'=>"How long has your business been operating?",'options'=>['Less than 1 year','1–2 years','2–3 years','3–5 years','More than 5 years'],'input_type'=>'chips','progress'=>34];

        case 'annual_turnover':
            return ['type'=>'number','field'=>'annual_turnover','question'=>"What is your approximate annual turnover? (₹)\n\n(As per last ITR / GST return)",'input_type'=>'number','progress'=>42];

        case 'gst_available':
            return ['type'=>'options','field'=>'gst_available','question'=>"Do you have GST registration and returns available?",'options'=>['Yes – GST registered & returns filed','GST registered but returns irregular','No GST registration'],'input_type'=>'chips','progress'=>70];

        case 'itr_available':
            return ['type'=>'options','field'=>'itr_available','question'=>"Are Income Tax Returns (ITR) available?",'options'=>['Yes – last 2 years','Yes – last 1 year only','Not filed / Not available'],'input_type'=>'chips','progress'=>76];

        case 'education_purpose':
            return ['type'=>'options','field'=>'education_purpose','question'=>"What is the loan purpose?",'options'=>['School / College Fees','Study Abroad','Hospital / Nursing Home','Hotel / Restaurant','KG / Pre-Primary School','Coaching Institute','Hostel / PG','Other Institution'],'input_type'=>'chips','progress'=>22];

        case 'collateral_available':
            return ['type'=>'options','field'=>'collateral_available','question'=>"Do you have any property as collateral / security for this loan?",'options'=>['Yes – property available as collateral','No – looking for unsecured loan'],'input_type'=>'chips','progress'=>52];

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