<?php
/**
 * WealthMetre — diva_v3.php
 *
 * PHASE 1: PHP deterministic guided flow — collects all fields, no OpenAI cost
 * PHASE 2: OpenAI conversation — after lead captured + lender results shown
 *
 * modes:
 *   welcome_mode  → initial loan type chips
 *   flow_mode     → PHP step-by-step collection
 *   result_mode   → lender results just shown (transitions to ai_mode)
 *   ai_mode       → OpenAI Hinglish conversation about results
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') jsonError('POST required', 405);

$body      = getJsonBodySafe();
$userMsg   = sanitizeText((string)($body['message'] ?? ''), 2000);
$history   = is_array($body['history'] ?? null) ? $body['history'] : [];
$profile   = is_array($body['profile'] ?? null) ? $body['profile'] : [];
$mode      = (string)($body['mode'] ?? 'welcome_mode');
$sessionId = buildSessionId((string)($body['session_id'] ?? ''));
$ipAddress = getClientIp();

$profile = normalizeProfileDiva($profile);

if (!empty($profile['customer_mobile'])) {
    $mob = normalizeIndianMobile((string)$profile['customer_mobile']);
    $profile['customer_mobile'] = $mob ?? '';
}

enforceRateLimit($sessionId, $ipAddress);


// ══════════════════════════════════════════════════════════
// WELCOME
// ══════════════════════════════════════════════════════════
// Language chip from frontend
if ($mode === 'welcome_mode' && in_array($userMsg, ['Hindi', 'English'], true)) {
    $profile['_lang'] = ($userMsg === 'Hindi') ? 'hi' : 'en';
    $hi = ($profile['_lang'] === 'hi');
    jsonResponse([
        'success'    => true,
        'mode'       => 'flow_mode',
        'message'    => $hi
            ? "Bahut achha! Main Diva hoon 🙏
140+ banks mein se best match dhundh dungi — bilkul free.

Kaunsa loan chahiye?"
            : "Great! I'm Diva 🙏
I'll find the best match from 140+ banks — completely free.

What type of loan are you looking for?",
        'chips'      => $hi
            ? ['🏠 Home Loan', '🏢 Loan Against Property', '🚗 Car Loan', '💼 Business Loan', '👤 Personal Loan']
            : ['🏠 Home Loan', '🏢 Loan Against Property', '🚗 Car Loan', '💼 Business Loan', '👤 Personal Loan'],
        'input_type' => 'chips',
        'profile'    => ['_lang' => $profile['_lang'], '_expecting' => 'product_type'],
    ]);
}

if ($userMsg === '' && $mode === 'welcome_mode') {
    jsonResponse([
        'success'    => true,
        'mode'       => 'flow_mode',
        'message'    => "Namaste! Main Diva hoon 🙏\nWealthMetre ki AI Loan Advisor.\n\n140+ banks & NBFCs mein se best match dhundh ke dungi — bilkul free.\n\nKaunsa loan chahiye?",
        'chips'      => ['🏠 Home Loan', '🏢 Loan Against Property', '💼 Business Loan', '👤 Personal Loan', '🚗 Car Loan'],
        'input_type' => 'chips',
        'profile'    => ['_expecting' => 'product_type'],
    ]);
}

if ($mode === 'welcome_mode' && $userMsg !== '') {
    $mode = 'flow_mode';
    if (empty($profile['_expecting'])) {
        $profile['_expecting'] = 'product_type';
    }
}

// ══════════════════════════════════════════════════════════
// EXPERT INTERCEPT — catch before AI touches it
// ══════════════════════════════════════════════════════════
$expertTriggers = [
    'talk to expert','talk to an expert','📞 talk to an expert',
    'expert se baat','📞 expert se baat','call expert','📞 call expert',
    'call now','📞 call now','connect expert','talk to advisor',
    'expert','consult'
];
$userMsgClean = strtolower(trim(preg_replace('/[^\w\s📞]/u','',$userMsg)));
$isExpertRequest = in_array(strtolower(trim($userMsg)), $expertTriggers, true)
    || in_array($userMsgClean, $expertTriggers, true)
    || stripos($userMsg, 'talk to expert') !== false
    || stripos($userMsg, 'expert se baat') !== false
    || stripos($userMsg, 'call expert') !== false;

if ($isExpertRequest) {
    $hi   = ($profile['_lang'] ?? 'en') === 'hi';
    $name = trim($profile['customer_name'] ?? '');

    if ($hi) {
        $msg = $name
            ? "Bilkul {$name}! 🙏 Hamara loan expert aapko personally guide karega.

Aapka profile already save ho gaya hai — seedha call karein aur apna loan process shuru karein.

📞 *+91 7976218596*
💬 WhatsApp pe bhi available hain"
            : "Bilkul! 🙏 Hamara loan expert aapko personally guide karega.

Call karein — seedha apna loan process shuru karein.

📞 *+91 7976218596*
💬 WhatsApp pe bhi available hain";
        $chips = ['📞 Abhi Call Karein', '💬 WhatsApp Karein', '🔄 Naya Search'];
    } else {
        $msg = $name
            ? "Absolutely {$name}! 🙏 Our loan expert will personally guide you through the entire process.

Your profile is already saved — just call us and we'll take it from here.

📞 *+91 7976218596*
💬 Also available on WhatsApp"
            : "Absolutely! 🙏 Our loan expert will personally guide you through the entire process.

Just call us and we'll take it from here.

📞 *+91 7976218596*
💬 Also available on WhatsApp";
        $chips = ['📞 Call Now', '💬 WhatsApp Us', '🔄 New Search'];
    }

    jsonResponse([
        'success'    => true,
        'mode'       => 'ai_mode',
        'message'    => $msg,
        'chips'      => $chips,
        'input_type' => 'chips',
        'profile'    => $profile,
    ]);
}


// ══════════════════════════════════════════════════════════
// ✏️ EXACT INPUT INTERCEPTOR — show text prompt for field
// ══════════════════════════════════════════════════════════
if ($mode === 'flow_mode' && str_starts_with(trim($userMsg), '✏️')) {
    $expecting = $profile['_expecting'] ?? '';
    $hi        = ($profile['_lang'] ?? 'en') === 'hi';
    $prompts   = [
        'city'            => ['en' => 'Please type your city name:',
                              'hi' => 'Apna sheher ka naam type karein: 📍'],
        'occupation_type' => ['en' => 'Please describe your occupation or income source:',
                              'hi' => 'Apna occupation ya income source describe karein:'],
        'property_type'   => ['en' => 'Please describe your property title type:',
                              'hi' => 'Property title ka type describe karein:'],
        'loan_amount'     => ['en' => 'Type loan amount in lakhs (e.g. 35 lakh, 55 lakh, 1.5 crore):',
                              'hi' => 'Exact loan amount type karein (e.g. 35 lakh, 1.5 crore):'],
        'property_value'  => ['en' => 'Type property value in lakhs (e.g. 80 lakh, 1.5 crore, 2 crore):',
                              'hi' => 'Property ki market value type karein:'],
        'monthly_income'  => ['en' => 'Type monthly income in rupees (e.g. 45000, 75000, 1.2 lakh):',
                              'hi' => 'Exact monthly income type karein:'],
        'existing_emi'    => ['en' => 'Type total monthly EMI in rupees (e.g. 14000, 25000) or 0 if none:',
                              'hi' => 'Total monthly EMI type karein (0 agar koi EMI nahi):'],
        'cibil_score'     => ['en' => 'Type your CIBIL score (between 300–900):',
                              'hi' => 'Apna CIBIL score type karein (300–900 ke beech):'],
    ];
    $prompt = $prompts[$expecting][$hi ? 'hi' : 'en']
           ?? ($hi ? 'Value type karein:' : 'Please type the value:');
    jsonResponse([
        'success'    => true,
        'mode'       => 'flow_mode',
        'message'    => $prompt,
        'chips'      => [],
        'input_type' => 'text',
        'profile'    => $profile,
    ]);
}

// ══════════════════════════════════════════════════════════
// FREE-TEXT DETECTION → temporarily answer, then resume flow
// ══════════════════════════════════════════════════════════
if ($mode === 'flow_mode' && $userMsg !== '' && !empty($profile['_expecting'])) {
    $expecting = $profile['_expecting'];

    // ── Pre-parse amount/EMI fields before going to AI ──────────────────────
    $amountFields = ['existing_emi', 'loan_amount', 'monthly_income', 'property_value'];
    if (in_array($expecting, $amountFields, true)) {
        $wordNums = ['one'=>1,'two'=>2,'three'=>3,'four'=>4,'five'=>5,'six'=>6,
                     'seven'=>7,'eight'=>8,'nine'=>9,'ten'=>10,'fifteen'=>15,
                     'twenty'=>20,'fifty'=>50,'hundred'=>100];
        $tryMsg = strtolower($userMsg);
        foreach ($wordNums as $w => $n) { $tryMsg = str_replace($w, (string)$n, $tryMsg); }
        $triedProfile = extractFlowAnswer($expecting, $tryMsg, $profile);
        $fieldSet = false;
        if ($expecting === 'existing_emi' && !empty($triedProfile['_emi_answered'])) $fieldSet = true;
        elseif ($expecting !== 'existing_emi' && ($triedProfile[$expecting] ?? 0) > 0) $fieldSet = true;
        if ($fieldSet) {
            $profile   = normalizeProfileDiva($triedProfile);
            $loanType  = $profile['product_type'] ?? '';
            $nextField = getNextRequiredField($loanType, $profile);
            if (!$nextField) {
                $searchProfile = buildSearchProfile($profile);
                try { $lenderResult = callDivaHybrid($searchProfile); }
                catch (\Throwable $e) { $lenderResult = ['type'=>'error','lenders'=>[],'total_matches'=>0]; }
                if (!isset($lenderResult['lenders'])) $lenderResult['lenders'] = [];
                $topLenders = array_slice($lenderResult['lenders'], 0, 5);
                $profile['_matched_lenders_json'] = json_encode(array_map(function($l) {
                    return ['name'=>$l['lender_name']??'','roi_min'=>$l['roi_min']??'','roi_max'=>$l['roi_max']??'','max_ltv'=>$l['max_ltv']??'','tenure'=>$l['max_tenure_months']??''];
                }, $topLenders), JSON_UNESCAPED_UNICODE);
                $hi   = ($profile['_lang'] ?? 'en') === 'hi';
                $name = trim($profile['customer_name'] ?? '');
                $msg  = $hi ? ($name ? "Shukriya {$name}! Best lenders dhundh rahe hain..." : "Best match dhundh rahe hain...") : ($name ? "Thank you {$name}! Finding best lenders..." : "Searching 140+ lenders...");
                $chips = $hi ? ['🎯 Best rate kaun dega?','📋 Documents kya chahiye?','💬 EMI calculate karein','📞 Expert se baat'] : ['🎯 Best rate?','📋 Documents needed?','💬 Calculate EMI','📞 Talk to expert'];
                safeJsonResponse(['success'=>true,'mode'=>'result_mode','message'=>$msg,'chips'=>$chips,'input_type'=>'text','profile'=>$profile,'lender_result'=>$lenderResult]);
            }
            $question = buildQuestion($nextField, $profile);
            $profile['_expecting'] = $nextField;
            $question['profile'] = $profile; $question['success'] = true; $question['mode'] = 'flow_mode';
            jsonResponse($question);
        }
    }

    if (!isChipAnswer($expecting, $userMsg)) {
        $profile['_ai_triggered_from'] = $expecting;

        $sysPrompt = buildAIPrompt($profile);
        $msgs = [['role' => 'system', 'content' => $sysPrompt]];

        foreach (array_slice($history, -16) as $t) {
            $r = in_array($t['role'] ?? '', ['user', 'assistant'], true) ? $t['role'] : 'user';
            $c = sanitizeText((string)($t['content'] ?? ''), 1000);
            if ($c !== '') {
                $msgs[] = ['role' => $r, 'content' => $c];
            }
        }

        $msgs[] = ['role' => 'user', 'content' => $userMsg];

        // ── RAG INJECTION (flow_mode) ──
        $ragCtx = fetchRagContextForDiva($userMsg, $profile);
        if ($ragCtx) {
            array_splice($msgs, 1, 0, [[
                'role'    => 'system',
                'content' => 'LENDER POLICY DATA:\n'.$ragCtx.'\n\nUse ONLY this data. No hallucination.'
            ]]);
        }

        try {
            $aiText = chatCompletion($msgs, 400);
        } catch (\Throwable $e) {
            $aiText = null;
        }

        if (!$aiText) {
            jsonResponse([
                'success'    => true,
                'mode'       => 'flow_mode',
                'message'    => "Samajh gaya! 😊 Chalte hain aage — " . getResumeQuestion($expecting, $profile),
                'chips'      => getResumeChips($expecting),
                'input_type' => 'chips',
                'profile'    => $profile,
            ]);
        }

        $chips = [];
        if (preg_match('/\[([^\]]+)\]/', $aiText, $m)) {
            $chips  = array_map('trim', explode('|', $m[1]));
            $aiText = trim(str_replace($m[0], '', $aiText));
        }

        $nudge = getFlowNudge($expecting, $profile);
        if ($nudge) {
            $aiText .= "\n\n" . $nudge;
        }

        if (!$chips) {
            $chips = getResumeChips($expecting);
        }

        jsonResponse([
            'success'    => true,
            'mode'       => 'flow_mode',
            'message'    => sanitizeText($aiText, 2000),
            'chips'      => sanitizeChips($chips),
            'input_type' => $chips ? 'chips' : 'text',
            'profile'    => $profile,
        ]);
    }
}


// ══════════════════════════════════════════════════════════
// PHASE 2: AI CONVERSATION (after lead captured)
// ══════════════════════════════════════════════════════════
if (in_array($mode, ['ai_mode', 'result_mode'], true) && $userMsg !== '') {
    saveLeadDiva($profile, $sessionId, $userMsg, $ipAddress);

    $quoteStep = $profile['_quote_step'] ?? '';
    $lowerMsg  = strtolower(trim($userMsg));
    $isQuoteTrigger = str_contains($lowerMsg, 'exact quote')
                   || str_contains($lowerMsg, 'quote chahiye')
                   || str_contains($lowerMsg, 'exact rate')
                   || str_contains($lowerMsg, '🎯 exact');

    // Re-search after customer adds extra notes
    if ($quoteStep === 'final_search') {
        $profile['_quote_step'] = '';
        $searchProfile = buildSearchProfile($profile);
        $extraNotes = trim($profile['extra_notes'] ?? $profile['notes'] ?? '');
        $searchProfile['extra_notes'] = $extraNotes;
        // Direct call — no HTTP loopback
        if (!function_exists('runHybrid')) { require_once __DIR__ . '/hybrid_query.php'; }
        $lenderResult = runHybrid($searchProfile, $extraNotes);
        $custName = trim($profile['customer_name'] ?? $profile['name'] ?? '');
        $msg = $custName
            ? "Updated results for {$custName} based on full profile:"
            : "Updated results based on your full profile:";
        jsonResponse([
            'type'          => 'result',
            'message'       => $msg,
            'lender_result' => $lenderResult,
            'profile'       => $profile,
            'mode'          => $mode,
        ]);
    }
    if ($quoteStep !== '' || $isQuoteTrigger) {
        handleExactQuoteFlow($userMsg, $profile, $sessionId, $ipAddress);
    }

    $sysPrompt = buildAIPrompt($profile);
    $msgs = [['role' => 'system', 'content' => $sysPrompt]];

    foreach (array_slice($history, -16) as $t) {
        $r = in_array($t['role'] ?? '', ['user', 'assistant'], true) ? $t['role'] : 'user';
        $c = sanitizeText((string)($t['content'] ?? ''), 1000);
        if ($c) {
            $msgs[] = ['role' => $r, 'content' => $c];
        }
    }

    $msgs[] = ['role' => 'user', 'content' => $userMsg];

    // ── RAG INJECTION: lender policy context ──
    $ragContext = fetchRagContextForDiva($userMsg, $profile);
    if ($ragContext) {
        array_splice($msgs, 1, 0, [[
            'role'    => 'system',
            'content' => "LENDER POLICY DATA FROM DATABASE:\n" . $ragContext . "\n\nUse ONLY this data. Do not hallucinate lender names or rates."
        ]]);
    }

    try {
        $aiText = chatCompletion($msgs, 400);
    } catch (\Throwable $e) {
        $aiText = null;
    }

    if (!$aiText) {
        $hi      = ($profile['_lang'] ?? 'en') === 'hi';
        $defChips = $hi
            ? ['🎯 Best rate kaun dega?', '📋 Documents kya chahiye?', '💬 EMI calculate karein', '📞 Expert se baat']
            : ['🎯 Which lender has best rate?', '📋 What documents needed?', '💬 Calculate my EMI', '📞 Talk to an expert'];
        jsonResponse([
            'success'    => true,
            'mode'       => 'ai_mode',
            'message'    => $hi
                ? "Ek second! 😊 Sawaal phir se poochh sakte hain, ya neeche se koi option choose karein."
                : "One moment! 😊 Could you rephrase that, or choose an option below.",
            'chips'      => $defChips,
            'input_type' => 'chips',
            'profile'    => $profile,
        ]);
    }

    $chips = [];
    if (preg_match('/\[([^\]]+)\]/', $aiText, $m)) {
        $chips  = array_map('trim', explode('|', $m[1]));
        $aiText = trim(str_replace($m[0], '', $aiText));
    }

    jsonResponse([
        'success'    => true,
        'mode'       => 'ai_mode',
        'message'    => sanitizeText($aiText, 2000),
        'chips'      => sanitizeChips($chips),
        'input_type' => 'text',
        'profile'    => $profile,
    ]);
}


// ══════════════════════════════════════════════════════════
// PHASE 1: PHP GUIDED FLOW
// ══════════════════════════════════════════════════════════

$expecting = $profile['_expecting'] ?? null;

if ($expecting && $userMsg !== '') {
    $profile = extractFlowAnswer($expecting, $userMsg, $profile);
    $profile = normalizeProfileDiva($profile);

    if (!empty($profile['customer_mobile'])) {
        $mob = normalizeIndianMobile((string)$profile['customer_mobile']);
        $profile['customer_mobile'] = $mob ?? '';
    }
}

if ($expecting === 'customer_mobile' && $userMsg !== '') {
    $digits = preg_replace('/\D/', '', $userMsg);
    if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') $digits = substr($digits, 2);
    if (strlen($digits) === 11 && substr($digits, 0, 1) === '0')  $digits = substr($digits, 1);

    if (strlen($digits) !== 10 || !preg_match('/^[5-9]/', $digits)) {
        $profile['customer_mobile'] = '';
        jsonResponse([
            'success'    => true,
            'mode'       => 'flow_mode',
            'message'    => "Please enter exactly 10-digit mobile number (starting with 5-9) 🙂",
            'chips'      => [],
            'input_type' => 'text',
            'profile'    => array_merge($profile, ['_expecting' => 'customer_mobile']),
        ]);
    }
}

if ($expecting === 'city' && strtolower(trim($userMsg)) === '✏️ other city') {
    jsonResponse([
        'success'    => true,
        'mode'       => 'flow_mode',
        'message'    => "City ka naam type karein: 📍",
        'chips'      => [],
        'input_type' => 'text',
        'profile'    => array_merge($profile, ['_expecting' => 'city_text']),
    ]);
}

if ($expecting === 'property_type' && (str_contains($userMsg, '✏️') || strtolower(trim($userMsg)) === 'other')) {
    jsonResponse([
        'success'    => true,
        'mode'       => 'flow_mode',
        'message'    => "Property ka title type karein (e.g. DUDA, Nagar Parishad, Agricultural):",
        'chips'      => [],
        'input_type' => 'text',
        'profile'    => array_merge($profile, ['_expecting' => 'property_type_text']),
    ]);
}

// Save progress to DB
saveLeadDiva($profile, $sessionId, $userMsg, $ipAddress);

// ── MOBILE CAPTURED → save lead + alert RM, then continue collecting ──
$mobile = (string)($profile['customer_mobile'] ?? '');
if (strlen($mobile) >= 10 && empty($profile['_lead_saved'])) {
    sendWhatsAppAlert($profile, $sessionId);
    $profile['_lead_saved'] = '1';
    // Do NOT search yet — continue collecting remaining fields
}

$pendingText = $profile['_pending_text_input'] ?? '';
if ($pendingText) {
    unset($profile['_pending_text_input']);

    $labels = [
        'loan_amount'    => "Loan amount type karein — lakhs mein (e.g. 35 lakh, 55 lakh, 1.5 cr):",
        'property_value' => "Property value type karein — lakhs mein (e.g. 80 lakh, 1.5 cr, 2 cr):",
        'monthly_income' => "Monthly income type karein — rupees mein (e.g. 45000, 75000, 1.2 lakh):",
    ];

    $profile['_expecting'] = $pendingText;

    jsonResponse([
        'success'    => true,
        'mode'       => 'flow_mode',
        'message'    => $labels[$pendingText] ?? "Amount type karein:",
        'chips'      => [],
        'input_type' => 'text',
        'profile'    => $profile,
    ]);
}

if (($profile['_expecting'] ?? '') === 'occupation_text' && empty($profile['occupation_type'])) {
    jsonResponse([
        'success'    => true,
        'mode'       => 'flow_mode',
        'message'    => "Income source / occupation type karein:",
        'chips'      => [],
        'input_type' => 'text',
        'profile'    => $profile,
    ]);
}

$loanType  = $profile['product_type'] ?? '';
$nextField = getNextRequiredField($loanType, $profile);
if (!$nextField) {
    $hi   = ($profile['_lang'] ?? 'en') === 'hi';
    $name = trim($profile['customer_name'] ?? '');
    $msg  = $hi
        ? ($name ? "Shukriya {$name}! Aapke profile ke liye best lenders dhundh rahe hain..." : "140+ lenders mein se best match dhundh rahe hain...")
        : ($name ? "Thank you {$name}! Finding the best lenders for your profile..." : "Searching through 140+ lenders for the best match...");
    $searchProfile = buildSearchProfile($profile);
    $lenderResult  = null;
    try {
        $lenderResult = callDivaHybrid($searchProfile);
    } catch (\Throwable $e) {
        error_log('[diva_v3] Hybrid query: ' . $e->getMessage());
        $lenderResult = ['type' => 'error', 'lenders' => [], 'total_matches' => 0];
    }
    if (is_array($lenderResult)) {
        $encoded = json_encode($lenderResult, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
        $lenderResult = json_decode($encoded ?: '{}', true)
            ?? ['type' => 'error', 'lenders' => [], 'total_matches' => 0];
    }
    if (!isset($lenderResult['lenders'])) $lenderResult['lenders'] = [];
    // Save matched lenders into profile for AI context
    $topLenders = array_slice($lenderResult['lenders'], 0, 5);
    $profile['_matched_lenders_json'] = json_encode(array_map(function($l) {
        return ['name'=>$l['lender_name']??'','roi_min'=>$l['roi_min']??'','roi_max'=>$l['roi_max']??'','max_ltv'=>$l['max_ltv']??'','tenure'=>$l['max_tenure_months']??'','notes'=>substr($l['notes']??'',0,100)];
    }, $topLenders), JSON_UNESCAPED_UNICODE);
    $chips = $hi
        ? ['🎯 Best rate kaun dega?', '📋 Documents kya chahiye?', '💬 EMI calculate karein', '📞 Expert se baat']
        : ['🎯 Which lender has best rate?', '📋 What documents needed?', '💬 Calculate my EMI', '📞 Talk to an expert'];
    safeJsonResponse([
        'success'       => true,
        'mode'          => 'result_mode',
        'message'       => $msg,
        'chips'         => $chips,
        'input_type'    => 'text',
        'profile'       => $profile,
        'lender_result' => $lenderResult,
    ]);
}

$extraMsg = '';
if ($nextField === 'cibil_score') {
    $inc = (int)($profile['monthly_income'] ?? 0);
    $emi = (int)($profile['existing_emi'] ?? 0);
    $emi = ($emi === 1) ? 0 : $emi;

    if ($inc > 0 && $emi > 0) {
        $foir = round(($emi / $inc) * 100, 1);

        if ($foir > 55) {
            $extraMsg = "\n\n⚠️ Aapka FOIR {$foir}% hai — kuch lenders 50% se kam chahte hain. Koi na koi option milega.";
        } else {
            $extraMsg = "\n\n✅ FOIR {$foir}% — eligibility ke liye achha hai!";
        }
    }
}

$question              = buildQuestion($nextField, $profile);
$profile['_expecting'] = $nextField;
$question['profile']   = $profile;
$question['success']   = true;
$question['mode']      = 'flow_mode';

if ($extraMsg) {
    $question['message'] = ($question['message'] ?? '') . $extraMsg;
}

jsonResponse($question);


// ══════════════════════════════════════════════════════════
// STEP SEQUENCER
// ══════════════════════════════════════════════════════════
function getNextRequiredField(string $lt, array $p): ?string
{
    $secured = in_array($lt, ['home', 'lap', ''], true);
    $fields  = [
        'product_type',
        'city',
        'consent',
        'customer_name',
        'customer_mobile',
        'occupation_type',
        'loan_amount',
        'monthly_income',
        'existing_emi',
    ];
    if ($lt === 'home') {
        $fields[] = 'loan_purpose';
    }
    if ($secured) {
        $fields[] = 'property_type';
        $fields[] = 'property_usage';
        $fields[] = 'property_value';
    }
    $fields[] = 'cibil_score';
    $noChk = ['consent','customer_name','customer_mobile','city','occupation_type','property_type','property_usage'];
    foreach ($fields as $field) {
        $v = $p[$field] ?? null;
        if ($field === 'existing_emi') {
            if (!empty($p['_emi_answered'])) continue;
            return $field;
        }
        if ($v === null || $v === '') return $field;
        if (!in_array($field, $noChk, true) && is_numeric($v) && (float)$v === 0.0) return $field;
    }
    return null;
}


// ══════════════════════════════════════════════════════════
// ANSWER EXTRACTOR
// ══════════════════════════════════════════════════════════
function extractFlowAnswer(string $field, string $msg, array $p): array
{
    $msg = trim($msg);

    switch ($field) {
        case 'product_type':
            $p['product_type'] = mapProductChip($msg);
            break;

        case 'city':
        case 'city_text':
            $city = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $msg) ?? $msg;
            $p['city'] = strtolower(trim($city));
            break;

        case 'occupation_type':
            if (str_contains($msg, '✏️') || strtolower($msg) === 'other') {
                $p['_expecting'] = 'occupation_text';
            } else {
                $p['occupation_type'] = mapOccupationChip($msg);
            }
            break;

        case 'occupation_text':
            $p['occupation_type'] = sanitizeText($msg, 50);
            break;

        case 'loan_purpose':
            $p['loan_purpose'] = sanitizeText($msg, 80);
            break;
        case 'property_type':
            if (str_contains($msg, '✏️') || strtolower(trim($msg)) === 'other') break;
            $p['property_type'] = mapPropertyTitleChip($msg);
            break;

        case 'property_type_text':
            $p['property_type'] = strtolower(sanitizeText($msg, 50));
            break;

        case 'property_usage':
            $p['property_usage'] = mapPropertyUsageChip($msg);
            break;

        case 'loan_amount':
        case 'property_value':
        case 'monthly_income':
            // Track if value came from range chip (approx) vs exact entry
            $isRangeChip = preg_match('/^\s*[₹\d].*[-–].*[LlCr\d]/u', $msg)
                        && !str_contains($msg, '✏️');
            if ($isRangeChip) {
                $p['_approx_' . $field] = true;
            } else {
                unset($p['_approx_' . $field]);
            }
            if (str_contains($msg, '✏️') || strtolower(trim($msg)) === 'other') {
                $p['_pending_text_input'] = $field;
            } else {
                $p[$field] = parseRupeeAmt($msg);
            }
            break;


        case 'existing_emi':
            $lower = strtolower(trim($msg));
            if (str_contains($lower, 'skip') || str_contains($lower, 'baad') || str_contains($lower, 'later')) {
                $p['existing_emi']  = 0;
                $p['_emi_answered'] = '1';
                $p['_emi_skipped']  = '1';
            } elseif (str_contains($lower, 'no emi') || str_contains($lower, 'no existing') || str_contains($lower, 'koi nahi') || str_contains($lower, 'koi emi') || str_contains($lower, '❌') || str_contains($lower, 'nahi')) {
                $p['existing_emi']  = 0;
                $p['_emi_answered'] = '1';
            } else {
                $p['existing_emi']  = parseRupeeAmt($msg);
                $p['_emi_answered'] = '1';
            }
            break;
        case 'cibil_score':
            $p['cibil_score'] = parseCibilChip($msg);
            break;

        case 'consent':
            // Yes chips contain checkmark, No chips contain cross
            $p['consent'] = (strpos($msg, '✅') !== false || stripos($msg, 'yes') !== false || stripos($msg, 'agree') !== false || stripos($msg, 'haan') !== false);
            if (!$p['consent']) {
                // User declined — skip name/mobile collection
                $p['customer_name']   = 'Guest';
                $p['customer_mobile'] = '';
            }
            break;
        case 'customer_name':
            $p['customer_name'] = sanitizeText($msg, 80);
            break;

        case 'customer_mobile':
            $digits = preg_replace('/\D/', '', $msg);
            if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') $digits = substr($digits, 2);
            if (strlen($digits) >= 10) $p['customer_mobile'] = substr($digits, -10);
            break;
    }

    return $p;
}


// ══════════════════════════════════════════════════════════
// QUESTION BUILDER
// ══════════════════════════════════════════════════════════
function buildQuestion(string $field, array $p): array
{
    $lt  = $p['product_type'] ?? '';
    $hi  = ($p['_lang'] ?? 'en') === 'hi';

    switch ($field) {
        case 'city':
            return [
                'chips'      => ['✏️ Enter city name', 'Jaipur', 'Jodhpur', 'Udaipur', 'Kota', 'Ajmer', 'Alwar', 'Sikar', 'Bhilwara', 'Bikaner', 'Delhi NCR', 'Mumbai', 'Bangalore'],
                'input_type' => 'chips',
                'message'    => $hi
                    ? "Aap / property kaunse city mein hai? 📍"
                    : "Which city is the property / applicant located in? 📍"
            ];
        case 'occupation_type':
            return [
                'chips'      => ['✏️ Describe occupation', '💼 Salaried - Private', '🏛️ Salaried - Govt/PSU', '🏪 Self Employed', '🏭 Business Owner', '👨‍⚕️ Professional (CA/Doctor/Lawyer)', '🌾 Agriculture Income'],
                'input_type' => 'chips',
                'message'    => $hi
                    ? "Aapki income ka source kya hai?"
                    : "What is your primary source of income?"
            ];
        case 'loan_purpose':
            return [
                'chips'      => [
                    '🏠 New Purchase — Ready Property',
                    '🏗️ New Purchase — Under Construction',
                    '🔨 Self Construction on Plot',
                    '🔧 Renovation / Home Improvement',
                    '💳 Balance Transfer',
                    '💰 Top-Up on Existing Loan',
                ],
                'input_type' => 'chips',
                'message'    => $hi
                    ? "Home loan ka purpose kya hai?"
                    : "What is the purpose of your home loan?"
            ];
        case 'property_type':
            return [
                'chips'      => ['✏️ Other title type', 'JDA Approved', 'Society Patta / Flat', 'Freehold / Registry', 'RHB / BDA', 'Nagar Nigam / Palika', 'Panchayat / Gram Panchayat', 'RIICO'],
                'input_type' => 'chips',
                'message'    => $hi
                    ? "Property ka title / approval kya hai?\n\nYeh sabse important question hai — alag title alag banks accept karte hain."
                    : "What is the property title / approval authority?\n\nThis is the most important question — different banks accept different title types."
            ];
        case 'property_usage':
            return [
                'chips'      => ['Residential - Self Occupied', 'Residential - Rented', 'Commercial', 'Industrial / Warehouse', 'Mixed Use', 'Vacant Plot / Land'],
                'input_type' => 'chips',
                'message'    => $hi
                    ? "Property ka current use kya hai?"
                    : "What is the current usage of the property?"
            ];
        case 'loan_amount':
            $hint = '';
            $val  = (int)($p['property_value'] ?? 0);
            if ($val > 0 && in_array($lt, ['lap', 'home'], true)) {
                $suggested = '₹' . number_format((int)round($val * 0.6 / 100000) * 100000);
                $hint = $hi
                    ? "\n(Property value ka 50-70% milta hai — approx {$suggested} eligible ho sakta hai)"
                    : "\n(Lenders typically offer 50-70% of property value — approx {$suggested} eligible)";
            }
            return [
                'chips'      => ['✏️ Type exact amount', '₹10-25 Lakh', '₹25-50 Lakh', '₹50L-1 Cr', '₹1-2 Cr', '₹2-5 Cr', '₹5 Cr+'],
                'input_type' => 'text',
                'message'    => $hi
                    ? "Kitna loan chahiye? (ya amount type karein){$hint}"
                    : "How much loan do you need? (or type the exact amount){$hint}"
            ];
        case 'property_value':
            return [
                'chips'      => ['✏️ Type exact value', '₹25-50 Lakh', '₹50L-1 Cr', '₹1-2 Cr', '₹2-5 Cr', '₹5 Cr+'],
                'input_type' => 'text',
                'message'    => $hi
                    ? "Property ki approximate current market value kya hai?"
                    : "What is the approximate current market value of the property?"
            ];
        case 'monthly_income':
            $ctxHi = in_array($p['occupation_type'] ?? '', ['salaried', 'govt'], true)
                ? "net monthly salary (in-hand)"
                : "monthly average income / drawing";
            $ctxEn = in_array($p['occupation_type'] ?? '', ['salaried', 'govt'], true)
                ? "net monthly take-home salary"
                : "average monthly income from all sources";
            return [
                'chips'      => ['✏️ Type exact income', '₹15,000-25,000', '₹25,000-50,000', '₹50,000-1 Lakh', '₹1-2 Lakh', '₹2 Lakh+'],
                'input_type' => 'text',
                'message'    => $hi
                    ? "Aapki {$ctxHi} kitni hai?"
                    : "What is your {$ctxEn}?"
            ];
        case 'existing_emi':
            $inc  = (int)($p['monthly_income'] ?? 0);
            $hint = $inc > 0
                ? ($hi
                    ? "\n(Income ₹" . number_format($inc) . "/mo — EMI se FOIR calculate hogi)"
                    : "\n(Your income ₹" . number_format($inc) . "/mo — helps calculate FOIR eligibility)")
                : '';
            return [
                'chips'      => $hi
                    ? ['✏️ Type exact EMI', '❌ Koi EMI nahi', '₹5,000-10,000/mo', '₹10,000-25,000/mo', '₹25,000-50,000/mo', '₹50,000+/mo', 'Skip — baad mein bataunga']
                    : ['✏️ Type exact EMI', 'No existing EMI', '₹5,000-10,000/mo', '₹10,000-25,000/mo', '₹25,000-50,000/mo', '₹50,000+/mo', "Skip — I'll tell later"],
                'input_type' => 'chips',
                'message'    => $hi
                    ? "Koi existing loan ki EMI chal rahi hai? (Optional — skip kar sakte hain){$hint}"
                    : "Do you have any existing EMIs running? (Optional — you can skip){$hint}"
            ];
        case 'cibil_score':
            return [
                'chips'      => $hi
                    ? ['✏️ Type exact score', '750+ (Excellent 🟢)', '700-750 (Achha 🟡)', '650-700 (Average 🟠)', '650 se kam 🔴', 'Mujhe pata nahi']
                    : ['✏️ Type exact score', '750+ (Excellent 🟢)', '700-750 (Good 🟡)', '650-700 (Average 🟠)', 'Below 650 🔴', "I don't know my score"],
                'input_type' => 'chips',
                'message'    => $hi
                    ? "Last question — aapka CIBIL score approximately kitna hai?\n\nMain aapka CIBIL pull NAHI karta — sirf lender filter ke liye hai."
                    : "Last question — what is your approximate CIBIL score?\n\nI will NOT pull your credit report — this only helps filter matching lenders."
            ];
        case 'consent':
            return [
                'chips'      => $hi
                    ? ['✅ Haan, main agree karta/karti hoon', '❌ Nahi chahiye personalized results']
                    : ['✅ Yes, I agree', '❌ No, skip personalization'],
                'input_type' => 'chips',
                'message'    => $hi
                    ? "Results share karne ke liye, mujhe aapka naam aur WhatsApp number chahiye.\n\n🔒 *Data Privacy:* Aapki details sirf aapke WealthMetre advisor ke paas jayegi. Kisi third party ko share nahi hogi. Aap kabhi bhi deletion request kar sakte hain — privacy@wealthmetre.com\n\nKya aap agree karte hain?"
                    : "To share your personalised lender results, I need your name and WhatsApp number.\n\n🔒 *Data Privacy:* Your details will only be shared with your WealthMetre advisor — never sold or shared with third parties. You can request deletion anytime at privacy@wealthmetre.com\n\nDo you agree to share your details?"
            ];
        case 'customer_name':
            return [
                'chips'      => [],
                'input_type' => 'text',
                'message'    => $hi
                    ? "Bilkul! Results share karne ke liye aapka naam chahiye.\n\nAapka naam kya hai?"
                    : "Great! To share your results personally, I just need a couple of quick details.\n\nWhat is your name?"
            ];
        case 'customer_mobile':
            return [
                'chips'      => [],
                'input_type' => 'text',
                'note'       => $hi
                    ? "Aapka number sirf aapke WealthMetre advisor ke paas jayega. Koi spam nahi."
                    : "Your number goes only to your WealthMetre advisor. No spam calls.",
                'message'    => $hi
                    ? "Aur aapka WhatsApp number? (10 digits)\n\nResults aur lender comparison seedha bhej denge."
                    : "And your WhatsApp number? (10 digits)\n\nWe'll send your lender results and comparison directly to you."
            ];
        default:
            return [
                'chips'      => [],
                'input_type' => 'text',
                'message'    => $hi ? "Kuch aur batayein?" : "Anything else to add?"
            ];
    }
}

function fetchRagContextForDiva(string $userMsg, array $profile): string {
    $lower = strtolower($userMsg);
    $kws = ['rate','roi','interest','patta','panchayati','jda','society','rera',
        'lap','working capital','surrogate','foir','ltv','eligible','eligibility',
        'processing fee','document','chahiye','konsa','konse','karega','milega',
        'milegi','lender','bank','nbfc','hdfc','icici','sbi','bajaj','sammaan',
        'cholamandalam','aadhar','truhome','program','scheme','title','approval',
        'balance transfer','overdraft','axis','nido'];
    $isQ = false;
    foreach ($kws as $kw) { if (str_contains($lower, $kw)) { $isQ = true; break; } }
    if (!$isQ) return '';
    $q = $userMsg;
    if (!empty($profile['product_type'])) $q .= ' '.$profile['product_type'];
    if (!empty($profile['city']))         $q .= ' '.$profile['city'];
    if (!empty($profile['property_type'])) $q .= ' '.$profile['property_type'];
    $payload = json_encode(['query'=>$q,'profile'=>$profile]);
    $ch = curl_init('http://127.0.0.1/api/rag_query.php');
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>$payload,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_TIMEOUT=>8,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    if (!$resp) return '';
    $d = json_decode($resp,true);
    if (empty($d['answer'])) return '';
    $src = implode(', ',array_values($d['sources']??[]));
    return 'Matched Lenders: '.$src.'\n\nPolicy Answer: '.$d['answer'];
}

function buildAIPrompt(array $p): string
{
    $lt     = strtoupper($p['product_type'] ?? 'loan');
    $city   = ucfirst($p['city'] ?? '');
    $amt    = ($p['loan_amount'] ?? 0) > 0 ? '₹' . number_format((int)$p['loan_amount']) : 'not specified';
    $inc    = ($p['monthly_income'] ?? 0) > 0 ? '₹' . number_format((int)$p['monthly_income']) : 'not specified';
    $cibil  = ($p['cibil_score'] ?? 0) > 0 ? (string)$p['cibil_score'] : 'not provided';
    $prop   = $p['property_type'] ?? '';
    $occ    = $p['occupation_type'] ?? '';
    $name   = $p['customer_name'] ?? 'Customer';
    $emi    = (int)($p['existing_emi'] ?? 0);
    $emiStr = ($emi <= 1) ? 'None' : '₹' . number_format($emi) . '/mo';

    $langRule = ($p['_lang'] ?? 'en') === 'hi'
        ? 'Answer in Hinglish (Hindi + English mixed). Use Hindi naturally with English financial terms.'
        : 'Answer STRICTLY in English only. No Hindi words at all. Keep financial terms like CIBIL, FOIR, LTV, ROI, EMI in English. Never mix Hindi.';

    $matchedLendersRaw = json_decode($p['_matched_lenders_json'] ?? '[]', true) ?: [];
    if (!empty($matchedLendersRaw)) {
        $matchedLendersStr = implode("\n", array_map(function($l) {
            $roi = $l['roi_min'] . '%' . (!empty($l['roi_max']) ? '-' . $l['roi_max'] . '%' : '+');
            $ltv = !empty($l['max_ltv']) ? $l['max_ltv'] . '% LTV' : 'LTV on request';
            $ten = !empty($l['tenure']) ? round($l['tenure']/12) . ' yr tenure' : '';
            return "- {$l['name']}: ROI {$roi}, {$ltv}" . ($ten ? ", {$ten}" : '');
        }, $matchedLendersRaw));
    } else {
        $matchedLendersStr = 'Results not yet available';
    }

    return <<<PROMPT
You are Diva — WealthMetre ki warm, expert AI Loan Advisor. You are talking to a retail customer.

CUSTOMER PROFILE:
- Name: {$name}
- Loan Type: {$lt}
- City: {$city}
- Loan Required: {$amt}
- Property Type: {$prop}
- Monthly Income: {$inc}
- Occupation: {$occ}
- Existing EMI: {$emiStr}
- CIBIL Score: {$cibil}

MATCHED LENDERS (from WealthMetre database — these are the ONLY lenders for this customer):
{$matchedLendersStr}

STRICT RULES — READ CAREFULLY:
- Language: {$langRule}
- You MUST ONLY refer to the matched lenders listed above. NEVER mention any other bank or NBFC.
- If asked about a lender NOT in the list above, say it did not match this profile and redirect to the matched lenders.
- Answer ROI, LTV, tenure questions ONLY using data from the matched lenders above.
- Do NOT use your general training knowledge to suggest or name any lender.
- CIBIL, FOIR, LTV, ROI, LAP, EMI — use these terms correctly.
- Keep answers concise: 3-5 lines max.
- Only suggest calling expert for very complex or document-specific situations.

PROFILE EXTRACTION FROM FREE-FORM INPUT:
If the user's message contains profile information, extract it:
- "society patta/patte" → property_type = Society Patta
- "contractor/construction" → occupation = contractor (SENP)
- "cash income/nakit" → income_type = cash (many lenders accept this via surrogate)
- City names → city field
- Amount in lakhs/crores → loan_amount
If critical fields missing (CIBIL, city, loan amount), ask ONLY for those.
NEVER say "no match" if CIBIL is not yet collected — ask for CIBIL first.
Cash income customers CAN get loans via surrogate programs — never say cash income not supported.

RESPONSE FORMAT:
- Natural conversational text (3-5 lines max)
- End with 2-3 meaningful action chips: [Chip One | Chip Two | Chip Three]
- CRITICAL: NEVER use "action 1", "action 2", "action 3" as chip text — ALWAYS use real options
- Good chip examples: [Best rate kaun dega? | Documents kya chahiye? | Expert se baat]
- Never use lender names as chip options
PROMPT;
}


// ══════════════════════════════════════════════════════════
// CHIP MAPPERS
// ══════════════════════════════════════════════════════════
function mapProductChip(string $s): string
{
    $s = strtolower($s);
    if (str_contains($s, 'home'))                                return 'home';
    if (str_contains($s, 'lap') || str_contains($s, 'against')) return 'lap';
    if (str_contains($s, 'business'))                            return 'business';
    if (str_contains($s, 'personal'))                            return 'personal';
    if (str_contains($s, 'car') || str_contains($s, 'vehicle')) return 'car';
    return strtolower(trim($s));
}

function mapOccupationChip(string $s): string
{
    $s = strtolower($s);
    if (str_contains($s, 'govt') || str_contains($s, 'psu') || str_contains($s, 'government')) return 'govt';
    if (str_contains($s, 'professional') || str_contains($s, 'doctor') || str_contains($s, 'ca') || str_contains($s, 'lawyer')) return 'professional';
    if (str_contains($s, 'self') || str_contains($s, 'business owner') || str_contains($s, 'owner')) return 'self_employed';
    if (str_contains($s, 'salaried') || str_contains($s, 'private')) return 'salaried';
    if (str_contains($s, 'business')) return 'self_employed';
    return strtolower(trim($s));
}

function mapPropertyTitleChip(string $s): string
{
    $s = strtolower($s);
    if (str_contains($s, 'jda'))                                        return 'jda';
    if (str_contains($s, 'society') || str_contains($s, 'flat'))        return 'society';
    if (str_contains($s, 'freehold') || str_contains($s, 'registry'))   return 'freehold';
    if (str_contains($s, 'rhb') || str_contains($s, 'bda'))             return 'rhb';
    if (str_contains($s, 'nagar nigam') || str_contains($s, 'palika'))  return 'nagar_nigam';
    if (str_contains($s, 'panchayat') || str_contains($s, 'gram'))      return 'panchayat';
    if (str_contains($s, 'riico'))                                      return 'riico';
    return strtolower(trim(preg_replace('/[^\w\s]/u', '', $s) ?? $s));
}

function mapPropertyUsageChip(string $s): string
{
    $s = strtolower($s);
    if (str_contains($s, 'rented') || str_contains($s, 'rent'))                return 'residential_rented';
    if (str_contains($s, 'residential') || str_contains($s, 'self occupied'))  return 'residential_sorp';
    if (str_contains($s, 'commercial'))                                         return 'commercial';
    if (str_contains($s, 'industrial') || str_contains($s, 'warehouse'))       return 'industrial';
    if (str_contains($s, 'mixed'))                                              return 'mixed';
    if (str_contains($s, 'plot') || str_contains($s, 'land'))                  return 'plot';
    return 'residential_sorp';
}

function parseCibilChip(string $s): int
{
    $s = strtolower($s);
    if (str_contains($s, '750') || str_contains($s, 'excellent')) return 780;
    if (str_contains($s, '700'))                                   return 725;
    if (str_contains($s, '650'))                                   return 675;
    if (str_contains($s, 'below') || str_contains($s, 'low'))      return 620;
    if (str_contains($s, 'pata nahi') || str_contains($s, 'unknown')) return 600;

    if (preg_match('/\d{3}/', $s, $m)) {
        $n = (int)$m[0];
        if ($n >= 300 && $n <= 900) return $n;
    }

    return 600;
}

function parseRupeeAmt(string $s): int
{
    $s = strtolower(trim($s));

    $chipMap = [
        '₹10-25 lakh' => 1750000, '10-25 lakh' => 1750000,
        '₹25-50 lakh' => 3750000, '25-50 lakh' => 3750000,
        '₹50l-1 cr'   => 7500000, '50l-1 cr'   => 7500000,
        '₹1-2 cr'     => 15000000, '1-2 cr'    => 15000000,
        '₹2-5 cr'     => 35000000, '2-5 cr'    => 35000000,
        '₹5 cr+'      => 60000000, '5 cr+'     => 60000000,
        '₹15,000-25,000' => 20000, '₹25,000-50,000' => 37500,
        '₹50,000-1 lakh' => 75000, '₹1-2 lakh' => 150000, '₹2 lakh+' => 250000,
        '₹5,000-10,000/mo' => 7500, '₹10,000-25,000/mo' => 17500,
        '₹25,000-50,000/mo' => 37500, '₹50,000+/mo' => 60000,
    ];

    foreach ($chipMap as $k => $v) {
        if (str_contains($s, $k)) return $v;
    }

    $s = str_replace(',', '', $s);

    if (preg_match('/([\d.]+)\s*(cr|crore)/i', $s, $m))     return (int)round((float)$m[1] * 10000000);
    if (preg_match('/([\d.]+)s*(lakhs?|lacs?|l)b/i', $s, $m)) return (int)round((float)$m[1] * 100000);
    if (preg_match('/([\d.]+)\s*k\b/i', $s, $m))            return (int)round((float)$m[1] * 1000);
    if (preg_match('/^[\d.]+$/', $s))                       return (int)round((float)$s);

    return 0;
}


// ══════════════════════════════════════════════════════════
// BUILD SEARCH PROFILE for hybrid_query
// ══════════════════════════════════════════════════════════

function detectSpecialFlag(string $notes, string $flagType): bool {
    $notes = strtolower(trim($notes));
    $patterns = [
        'negative_bureau' => ['negative bureau', 'low cibil', 'bad credit', 'cibil issue', 'dpd', 'written off', 'settled'],
        'covid_dpd'       => ['covid dpd', 'covid', 'pandemic dpd', 'moratorium', 'covid default', 'covid delay'],
        'surrogate'       => ['surrogate', 'no itr', 'bank statement program', 'banking surrogate', 'without itr'],
    ];
    if (!isset($patterns[$flagType])) return false;
    foreach ($patterns[$flagType] as $keyword) {
        if (str_contains($notes, $keyword)) return true;
    }
    return false;
}

function buildSearchProfile(array $p): array
{
    $emi = (int)($p['existing_emi'] ?? 0);
    if ($emi === 1) $emi = 0;
    $occ = mapOccupationChip($p['occupation_type'] ?? '');

    return [
        'loan_type'          => mapProductChip($p['product_type'] ?? ''),
        'city'               => strtolower(trim($p['city'] ?? 'jaipur')),
        'cibil_score'        => (int)($p['cibil_score'] ?? 0) ?: 600,
        'cibil'              => (int)($p['cibil_score'] ?? 0) ?: 600,
        'loan_amount'        => (int)($p['loan_amount'] ?? 0),
        'amount'             => (int)($p['loan_amount'] ?? 0),
        'property_value'     => (int)($p['property_value'] ?? 0),
        'property_valuation' => (int)($p['property_value'] ?? 0),
        'monthly_income'     => (int)($p['monthly_income'] ?? 0),
        'income'             => (int)($p['monthly_income'] ?? 0),
        'existing_emis'      => $emi,
        'existing_emi'       => $emi,
        'property_title'     => $p['property_type'] ?? '',
        'property_type'      => $p['property_type'] ?? '',
        'property_usage'     => $p['property_usage'] ?? 'residential',
        'occupation'         => $occ,
        'income_source'      => $occ,
        'consent'            => (bool)($p['consent'] ?? false),
        'customer_name'      => $p['customer_name'] ?? '',
        'customer_mobile'    => $p['customer_mobile'] ?? '',
        'loan_purpose'         => $p['loan_purpose'] ?? '',
        'covid_dpd_hint'       => isset($p['covid_dpd_hint']) && $p['covid_dpd_hint'] ? true : false,
        'negative_bureau_hint' => isset($p['negative_bureau_hint']) && $p['negative_bureau_hint'] ? true : false,
        'surrogate_hint'       => isset($p['surrogate_hint']) && $p['surrogate_hint'] ? true : false,
        'extra_notes'          => trim((string)($p['_extra_notes'] ?? $p['notes'] ?? '')),
    ];
}


// ══════════════════════════════════════════════════════════
// WHATSAPP ALERT
// ══════════════════════════════════════════════════════════
function sendWhatsAppAlert(array $p, string $sid): void
{
    if (!defined('MSG91_AUTH_KEY') || !MSG91_AUTH_KEY) return;

    $name   = $p['customer_name'] ?? 'Unknown';
    $mobile = $p['customer_mobile'] ?? '';
    $lt     = strtoupper($p['product_type'] ?? '');
    $city   = ucfirst($p['city'] ?? '');
    $amt    = ($p['loan_amount'] ?? 0) > 0 ? '₹' . number_format((int)$p['loan_amount']) : '-';
    $cibil  = ($p['cibil_score'] ?? 0) > 0 ? (string)$p['cibil_score'] : '-';
    $score  = calcLeadScore($p);

    $inc    = ($p['monthly_income'] ?? 0) > 0 ? '₹' . number_format((int)$p['monthly_income']) . '/mo' : '-';
    $emi    = ($p['existing_emi'] ?? 0) > 1  ? '₹' . number_format((int)$p['existing_emi']) . '/mo' : 'None';
    $occ    = $p['occupation_type'] ?? '-';
    $lang   = ($p['_lang'] ?? 'en') === 'hi' ? 'Hindi' : 'English';
    $msg = "🤖 *New Diva Lead* — Score {$score}/100\n"
         . "Name: {$name} | Lang: {$lang}\n"
         . "Mobile: {$mobile}\n"
         . "Loan: {$lt} | City: {$city}\n"
         . "Amount: {$amt} | CIBIL: {$cibil}\n"
         . "Income: {$inc} | EMI: {$emi}\n"
         . "Occupation: {$occ}\n"
         . "Session: {$sid}";

    $payload = json_encode([
        'integrated_number' => MSG91_WHATSAPP_FROM_NUMBER,
        'content_type'      => 'text',
        'payload'           => [
            'to'   => '917976218596',
            'type' => 'text',
            'text' => ['body' => $msg]
        ],
    ]);

    $ch = curl_init('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'authkey: ' . MSG91_AUTH_KEY
        ]
    ]);
    curl_exec($ch);
    curl_close($ch);
}


// ══════════════════════════════════════════════════════════
// ASYNC EMAIL + SHEET ALERT via trigger_alert.php
// No require_once — zero dependency — Diva never crashes
// ══════════════════════════════════════════════════════════
function fireLeadAlert(array $p, string $sid): void
{
    try {
        $mobile = (string)($p['customer_mobile'] ?? '');
        if (strlen($mobile) < 10) return;

        $payload = json_encode([
            'secret'          => 'wm_alert_2024',
            'source'          => 'Diva AI',
            'id'              => 0,
            'session_id'      => $sid,
            'consent_given'   => isset($p['consent']) ? ($p['consent'] ? 1 : 0) : 0,
            'name'            => (string)($p['customer_name'] ?? ''),
            'mobile'          => $mobile,
            'city'            => (string)($p['city'] ?? ''),
            'loan_type'       => (string)($p['product_type'] ?? ''),
            'loan_amount'     => (int)($p['loan_amount'] ?? 0),
            'property_value'  => (int)($p['property_value'] ?? 0),
            'monthly_income'  => (int)($p['monthly_income'] ?? 0),
            'cibil_score'     => (int)($p['cibil_score'] ?? 0),
            'employment_type' => (string)($p['occupation_type'] ?? ''),
            'property_type'   => (string)($p['property_type'] ?? ''),
            'alert_time'      => date('d M Y, h:i A'),
        ]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'wealthmetre.com';
        $url    = $scheme . '://' . $host . '/api/trigger_alert.php';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 1,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);
    } catch (\Throwable $e) {
        error_log('[diva_v3] fireLeadAlert failed: ' . $e->getMessage());
    }
}


// ══════════════════════════════════════════════════════════
// DIRECT HTTP CALL TO HYBRID QUERY
// ══════════════════════════════════════════════════════════
function callDivaHybrid(array $profile): array
{
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'wealthmetre.com';
    $url     = $scheme . '://' . $host . '/api/hybrid_query.php';
    $payload = json_encode(['profile' => $profile, 'q' => $profile['extra_notes'] ?? ''], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) error_log('[diva_v3] curl error: ' . $err);
    if (!$raw) {
        return ['type' => 'error', 'lenders' => [], 'total_matches' => 0, 'message' => 'Lender search unavailable'];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['type' => 'error', 'lenders' => [], 'total_matches' => 0, 'message' => 'Parse error'];
    }

    return $decoded;
}


// ══════════════════════════════════════════════════════════
// EXACT QUOTE FLOW
// ══════════════════════════════════════════════════════════
function handleExactQuoteFlow(string $msg, array $profile, string $sid, string $ip): void
{
    $step  = $profile['_quote_step'] ?? 'start';
    $lower = strtolower(trim($msg));

    switch ($step) {

        case 'start':
        case '':
            $summary = buildProfileSummary($profile);
            $profile['_quote_step'] = 'ask_extra';
            safeJsonResponse([
                'success'    => true,
                'mode'       => 'ai_mode',
                'message'    => "Would you like to add anything else?",
                'chips'      => ["\u270f\ufe0f Yes, add detail", "\u2705 This is final \u2014 find lenders"],
                'input_type' => 'chips',
                'profile'    => $profile
            ]);

        case 'ask_extra':
        case 'more_or_final':
            if (str_contains($lower, 'add') || str_contains($lower, 'yes') || str_contains($lower, 'haan') || str_contains($lower, "\u270f")) {
                $profile['_quote_step'] = 'collecting_extra';
                safeJsonResponse([
                    'success'    => true,
                    'mode'       => 'ai_mode',
                    'message'    => "Please share additional details:\n(e.g. high tension line, co-applicant income, NRI, irregular income, property dispute, women applicant, rental income etc.)",
                    'chips'      => [],
                    'input_type' => 'text',
                    'profile'    => $profile
                ]);
            }
            $profile['_quote_step'] = 'consent';

        case 'collecting_extra':
            if ($step === 'collecting_extra') {
                $existing = trim($profile['_extra_notes'] ?? $profile['notes'] ?? '');
                $newNote  = sanitizeText(trim($msg), 500);
                $profile['_extra_notes'] = $existing ? $existing . '; ' . $newNote : $newNote;
                $profile['notes']        = $profile['_extra_notes'];
                $profile['covid_dpd_hint']      = detectSpecialFlag($profile['_extra_notes'], 'covid_dpd')       ? '1' : '';
                $profile['negative_bureau_hint'] = detectSpecialFlag($profile['_extra_notes'], 'negative_bureau') ? '1' : '';
                $profile['surrogate_hint']       = detectSpecialFlag($profile['_extra_notes'], 'surrogate')       ? '1' : '';
                $summary   = buildProfileSummary($profile);
                $notesLine = "\n\n\ud83d\udcdd Additional details captured:\n" . $profile['_extra_notes'];
                $profile['_quote_step'] = 'more_or_final';
                safeJsonResponse([
                    'success'    => true,
                    'mode'       => 'ai_mode',
                    'message'    => "\u2705 Added!\n\n{$summary}{$notesLine}\n\nWhat would you like to do next?",
                    'chips'      => ["\u270f\ufe0f Add more detail", "\ud83d\udd0d Find best lenders now"],
                    'input_type' => 'chips',
                    'profile'    => $profile
                ]);
            }
            $profile['_quote_step'] = 'consent';

        case 'final_search':
            if ($step === 'final_search' || str_contains($lower, 'find') || str_contains($lower, "\ud83d\udd0d")) {
                $searchProfile = buildSearchProfile($profile);
                file_put_contents('/var/www/wealthmetre/public_html/diva_fs_debug.txt', date('H:i:s').' notes='.($searchProfile['extra_notes']??'EMPTY').' q_will_be='.($searchProfile['extra_notes']??'')."\n", FILE_APPEND);
                try { $lenderResult = callDivaHybrid($searchProfile); }
                catch (\Throwable $e) { $lenderResult = ['type'=>'error','lenders'=>[],'total_matches'=>0]; }
                if (!isset($lenderResult['lenders'])) $lenderResult['lenders'] = [];
                $topLenders = array_slice($lenderResult['lenders'], 0, 5);
                $profile['_matched_lenders_json'] = json_encode(array_map(function($l) {
                    return ['name'=>$l['lender_name']??'','roi_min'=>$l['roi_min']??'','roi_max'=>$l['roi_max']??'','max_ltv'=>$l['max_ltv']??'','tenure'=>$l['max_tenure_months']??''];
                }, $topLenders), JSON_UNESCAPED_UNICODE);
                $profile['_quote_step'] = '';
                saveLeadDiva($profile, $sid, 'extra_notes_search', $ip);
                $name = trim($profile['customer_name'] ?? '');
                safeJsonResponse([
                    'success'       => true,
                    'mode'          => 'result_mode',
                    'message'       => $name ? "Updated results for {$name} based on full profile:" : "Updated results based on your full profile:",
                    'chips'         => ["\ud83c\udfaf Best rate?", "\ud83d\udccb Documents needed?", "\ud83d\udcac Calculate EMI", "\ud83d\udcde Talk to expert"],
                    'input_type'    => 'text',
                    'profile'       => $profile,
                    'lender_result' => $lenderResult,
                ]);
            }
            $profile['_quote_step'] = 'consent';

        case 'consent':
            $name    = trim($profile['customer_name'] ?? 'aap');
            $profile['_quote_step'] = 'awaiting_consent';
            $notes   = trim($profile['_extra_notes'] ?? $profile['notes'] ?? '');
            $notesLine = $notes ? "\n\ud83d\udcdd Additional info: {$notes}\n" : '';
            $summary = buildProfileSummary($profile);
            safeJsonResponse([
                'success'    => true,
                'mode'       => 'ai_mode',
                'message'    => "\ud83d\udccb Case ready hai {$name}!\n\n{$summary}{$notesLine}\n\ud83d\udd12 Your number will not be shared without your approval.\n\nShall I send this to shortlisted lenders?",
                'chips'      => ["\u2705 Yes, send to lenders", "\ud83d\udd50 Not now"],
                'input_type' => 'chips',
                'profile'    => $profile
            ]);

        case 'awaiting_consent':
            if (str_contains($lower, 'yes') || str_contains($lower, 'send') || str_contains($lower, 'haan') || str_contains($lower, "\u2705")) {
                $profile['_quote_step']    = 'done';
                $profile['_consent_given'] = '1';
                sendQuoteRequestAlert($profile, $sid);
                saveLeadDiva($profile, $sid, 'consent_given', $ip);
                safeJsonResponse([
                    'success'    => true,
                    'mode'       => 'ai_mode',
                    'message'    => "\ud83c\udf89 Thank you " . trim($profile['customer_name'] ?? '') . "!\n\nExact ROI, PF and quotes on WhatsApp within 1 hour.\n\n\ud83d\udcde Call: +91 7976218596\n\ud83d\udcac WhatsApp: wa.me/917976218596",
                    'chips'      => ["\ud83d\udcde Call Expert Now", "\ud83d\udcac WhatsApp Now"],
                    'input_type' => 'chips',
                    'profile'    => $profile
                ]);
            }
            $profile['_quote_step'] = '';
            safeJsonResponse([
                'success'    => true,
                'mode'       => 'ai_mode',
                'message'    => "No problem! Whenever ready, press '\ud83c\udfaf Exact quote chahiye'. \ud83d\ude0a",
                'chips'      => ["\ud83c\udfaf Exact quote chahiye", "\ud83d\udcac EMI calculate karein", "\ud83d\udcde Expert se baat"],
                'input_type' => 'chips',
                'profile'    => $profile
            ]);
    }
}


function buildProfileSummary(array $p): string
{
    $emi   = (int)($p['existing_emi'] ?? 0);
    $lines = [];

    if (!empty($p['product_type']))    $lines[] = "• Loan Type   : " . strtoupper($p['product_type']);
    if (!empty($p['city']))            $lines[] = "• City        : " . ucfirst($p['city']);
    if (!empty($p['property_type']))   $lines[] = "• Prop. Title : " . ucfirst($p['property_type']);
    if (!empty($p['property_usage']))  $lines[] = "• Prop. Use   : " . $p['property_usage'];
    if (!empty($p['loan_amount']))     $lines[] = "• Loan Amount : ₹" . number_format((int)$p['loan_amount']);
    if (!empty($p['property_value']))  $lines[] = "• Prop. Value : ₹" . number_format((int)$p['property_value']);
    if (!empty($p['monthly_income']))  $lines[] = "• Income/mo   : ₹" . number_format((int)$p['monthly_income']);
    $lines[]                           = "• Existing EMI: " . ($emi <= 1 ? "None" : "₹" . number_format($emi));
    if (!empty($p['cibil_score']))     $lines[] = "• CIBIL Score : " . $p['cibil_score'];
    if (!empty($p['occupation_type'])) $lines[] = "• Occupation  : " . $p['occupation_type'];

    return implode("\n", $lines);
}

function sendQuoteRequestAlert(array $p, string $sid): void
{
    if (!defined('MSG91_AUTH_KEY') || !MSG91_AUTH_KEY) return;

    $name   = $p['customer_name'] ?? 'Unknown';
    $mobile = $p['customer_mobile'] ?? '';
    $notes  = $p['notes'] ?? '';

    $msg = "🎯 *EXACT QUOTE REQUEST*\nName: {$name}\nMobile: {$mobile}\n"
         . "Loan: " . strtoupper($p['product_type'] ?? '') . " | City: " . ucfirst($p['city'] ?? '') . "\n"
         . "Amount: ₹" . number_format((int)($p['loan_amount'] ?? 0)) . "\n"
         . "CIBIL: " . ($p['cibil_score'] ?? '-') . " | Income: ₹" . number_format((int)($p['monthly_income'] ?? 0)) . "/mo\n"
         . ($notes ? "Notes: {$notes}\n" : '')
         . "Session: {$sid}";

    $payload = json_encode([
        'integrated_number' => MSG91_WHATSAPP_FROM_NUMBER,
        'content_type'      => 'text',
        'payload'           => [
            'to'   => '917976218596',
            'type' => 'text',
            'text' => ['body' => $msg]
        ]
    ]);

    $ch = curl_init('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'authkey: ' . MSG91_AUTH_KEY
        ]
    ]);
    curl_exec($ch);
    curl_close($ch);
}


// ══════════════════════════════════════════════════════════
// UTF-8 SAFE JSON RESPONSE
// ══════════════════════════════════════════════════════════
function sanitizeForJson(array $data): array
{
    array_walk_recursive($data, function (&$v) {
        if (is_string($v)) {
            $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
            $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? $v;
        }
    });

    return $data;
}

function safeJsonResponse(array $data, int $status = 200): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    http_response_code($status);
    $data    = sanitizeForJson($data);
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

    echo $encoded === false
        ? '{"success":false,"mode":"error","message":"Response error — please try again."}'
        : $encoded;

    exit;
}


// ══════════════════════════════════════════════════════════
// LEAD SAVE
// ══════════════════════════════════════════════════════════
function saveLeadDiva(array $p, string $sid, string $msg, string $ip): void
{
    try {
        $pdo    = getDB();
        $score  = calcLeadScore($p);
        $status = !empty($p['customer_mobile']) ? 'qualified' : (!empty($p['loan_amount']) ? 'partial' : 'new');

        $sql = "INSERT INTO diva_leads_v2
            (session_id,name,mobile,city,product_type,loan_amount,property_value,
             monthly_income,business_turnover,existing_emi,cibil_score,property_type,
             lead_score,lead_status,last_message,ip_address,created_at,updated_at)
            VALUES (:sid,:name,:mobile,:city,:pt,:la,:pv,:mi,:bt,:emi,:cibil,:prop,
                    :score,:status,:msg,:ip,NOW(),NOW())
            ON DUPLICATE KEY UPDATE
                name           = COALESCE(NULLIF(VALUES(name),''), name),
                mobile         = COALESCE(NULLIF(VALUES(mobile),''), mobile),
                city           = COALESCE(NULLIF(VALUES(city),''), city),
                product_type   = COALESCE(NULLIF(VALUES(product_type),''), product_type),
                loan_amount    = GREATEST(loan_amount, VALUES(loan_amount)),
                property_value = GREATEST(property_value, VALUES(property_value)),
                monthly_income = GREATEST(monthly_income, VALUES(monthly_income)),
                existing_emi   = GREATEST(existing_emi, VALUES(existing_emi)),
                cibil_score    = GREATEST(cibil_score, VALUES(cibil_score)),
                property_type  = COALESCE(NULLIF(VALUES(property_type),''), property_type),
                lead_score     = VALUES(lead_score),
                lead_status    = VALUES(lead_status),
                last_message   = VALUES(last_message),
                updated_at     = NOW()";

        $emiSave = (int)($p['existing_emi'] ?? 0);
        if ($emiSave === 1) $emiSave = 0;

        $pdo->prepare($sql)->execute([
            ':sid'    => $sid,
            ':name'   => (string)($p['customer_name'] ?? ''),
            ':mobile' => (string)($p['customer_mobile'] ?? ''),
            ':city'   => (string)($p['city'] ?? ''),
            ':pt'     => (string)($p['product_type'] ?? ''),
            ':la'     => (int)($p['loan_amount'] ?? 0),
            ':pv'     => (int)($p['property_value'] ?? 0),
            ':mi'     => (int)($p['monthly_income'] ?? 0),
            ':bt'     => (int)($p['business_turnover'] ?? 0),
            ':emi'    => $emiSave,
            ':cibil'  => (int)($p['cibil_score'] ?? 0),
            ':prop'   => (string)($p['property_type'] ?? ''),
            ':score'  => $score,
            ':status' => $status,
            ':msg'    => sanitizeText($msg, 500),
            ':ip'     => $ip,
        ]);

        $mobile = (string)($p['customer_mobile'] ?? '');
        if (strlen($mobile) >= 10) {
            fireLeadAlert($p, $sid);
        }

        $name = (string)($p['customer_name'] ?? '');
        if ($name && strlen($mobile) >= 10) {
            $chk = $pdo->prepare("SELECT id FROM website_leads WHERE phone=? AND DATE(created_at)=CURDATE()");
            $chk->execute([$mobile]);

            if (!$chk->fetch()) {
                $pdo->prepare("INSERT INTO website_leads (name,phone,loan_type,city,source,created_at) VALUES (?,?,?,?,'diva_ai_v3',NOW())")
                    ->execute([$name, $mobile, $p['product_type'] ?? '', $p['city'] ?? '']);
            }
        }
    } catch (\Throwable $e) {
        error_log('[diva_v3] saveLeadDiva: ' . $e->getMessage());
    }
}


// ══════════════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ══════════════════════════════════════════════════════════
function calcLeadScore(array $p): int
{
    $s = 0;
    if (!empty($p['product_type']))    $s += 10;
    if (!empty($p['city']))            $s += 10;
    if (!empty($p['loan_amount']))     $s += 15;
    if (!empty($p['property_value']))  $s += 10;
    if (!empty($p['monthly_income']))  $s += 15;
    if (!empty($p['cibil_score']))     $s += 10;
    if (!empty($p['property_type']))   $s += 5;
    if (!empty($p['occupation_type'])) $s += 5;
    if (!empty($p['existing_emi']))    $s += 5;
    if (!empty($p['customer_name']))   $s += 10;
    if (!empty($p['customer_mobile'])) $s += 40;
    return min($s, 100);
}

function normalizeProfileDiva(array $p): array
{
    return [
        'customer_name'       => sanitizeText((string)($p['customer_name'] ?? ''), 80),
        'customer_mobile'     => sanitizeText((string)($p['customer_mobile'] ?? ''), 20),
        'city'                => strtolower(sanitizeText((string)($p['city'] ?? ''), 80)),
        'product_type'        => sanitizeText((string)($p['product_type'] ?? $p['loan_type'] ?? ''), 50),
        'loan_amount'         => normalizeNum($p['loan_amount'] ?? null),
        'property_value'      => normalizeNum($p['property_value'] ?? $p['property_valuation'] ?? null),
        'monthly_income'      => normalizeNum($p['monthly_income'] ?? null),
        'business_turnover'   => normalizeNum($p['business_turnover'] ?? null),
        'existing_emi'        => normalizeNum($p['existing_emi'] ?? null),
        'cibil_score'         => normalizeRange($p['cibil_score'] ?? $p['cibil'] ?? null, 300, 900),
        'property_type'       => sanitizeText((string)($p['property_type'] ?? $p['property_title'] ?? ''), 80),
        'property_usage'      => sanitizeText((string)($p['property_usage'] ?? ''), 80),
        'occupation_type'     => sanitizeText((string)($p['occupation_type'] ?? $p['income_source'] ?? $p['occupation'] ?? ''), 50),
        'notes'               => sanitizeText((string)($p['notes'] ?? ''), 500),
        '_expecting'          => sanitizeText((string)($p['_expecting'] ?? ''), 50),
        '_pending_text_input' => sanitizeText((string)($p['_pending_text_input'] ?? ''), 50),
        '_quote_step'         => sanitizeText((string)($p['_quote_step'] ?? ''), 30),
        '_consent_given'      => sanitizeText((string)($p['_consent_given'] ?? ''), 5),
        'consent'             => (bool)($p['consent'] ?? false),
        '_ai_triggered_from'  => sanitizeText((string)($p['_ai_triggered_from'] ?? ''), 50),
        '_lead_saved'         => !empty($p['_lead_saved']) ? '1' : '',
        '_emi_answered'       => !empty($p['_emi_answered']) ? '1' : '',
        'loan_purpose'         => sanitizeText((string)($p['loan_purpose'] ?? ''), 80),
        '_matched_lenders_json' => is_string($p['_matched_lenders_json'] ?? null) ? ($p['_matched_lenders_json'] ?? '') : '',
        '_lang'               => in_array($p['_lang'] ?? 'en', ['hi', 'en'], true) ? ($p['_lang'] ?? 'en') : 'en',
        'extra_notes'          => $p['_extra_notes'] ?? $p['notes'] ?? $p['extra_notes'] ?? '',
        'negative_bureau_hint' => detectSpecialFlag($p['_extra_notes'] ?? '', 'negative_bureau'),
        'covid_dpd_hint'       => detectSpecialFlag($p['_extra_notes'] ?? '', 'covid_dpd'),
        'surrogate_hint'       => detectSpecialFlag($p['_extra_notes'] ?? '', 'surrogate'),
    ];
}

function normalizeNum($v): int
{
    if ($v === null || $v === '') return 0;
    $n = is_numeric($v) ? (float)$v : (float)(preg_replace('/[^\d.]/', '', (string)$v) ?? 0);
    return (int)round(max(0, min($n, 9999999999)));
}

function normalizeRange($v, int $mn, int $mx): int
{
    $n = normalizeNum($v);
    if ($n === 0) return 0;
    return max($mn, min($mx, $n));
}

function getJsonBodySafe(): array
{
    try {
        if (function_exists('getJsonBody')) {
            $r = getJsonBody();
            return is_array($r) ? $r : [];
        }

        $raw = file_get_contents('php://input');
        $d   = json_decode((string)$raw, true);
        return is_array($d) ? $d : [];
    } catch (\Throwable $e) {
        return [];
    }
}

function sanitizeText(string $t, int $max = 1000): string
{
    $t = trim($t);
    $t = preg_replace('/[[:cntrl:]]+/u', ' ', $t) ?? '';
    $t = preg_replace('/\s+/u', ' ', $t) ?? '';

    if (mb_strlen($t) > $max) {
        $t = mb_substr($t, 0, $max);
    }

    return trim($t);
}

function sanitizeChips($chips): array
{
    if (!is_array($chips)) return [];

    $out = [];
    foreach ($chips as $c) {
        $c = sanitizeText((string)$c, 60);
        if ($c !== '') $out[] = $c;
        if (count($out) >= 6) break;
    }

    return array_values(array_unique($out));
}

function buildSessionId(string $s): string
{
    $s = trim($s);
    if ($s === '') return 'diva_' . bin2hex(random_bytes(8));

    $s = preg_replace('/[^a-zA-Z0-9_\-]/', '', $s) ?? '';
    return $s !== '' ? substr($s, 0, 80) : 'diva_' . bin2hex(random_bytes(8));
}

function getClientIp(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $v = trim(explode(',', (string)$_SERVER[$k])[0]);
            if (filter_var($v, FILTER_VALIDATE_IP)) return $v;
        }
    }

    return '0.0.0.0';
}

function normalizeIndianMobile(string $m): ?string
{
    $d = preg_replace('/\D+/', '', $m) ?? '';
    if (strlen($d) === 12 && substr($d, 0, 2) === '91') $d = substr($d, 2);
    return preg_match('/^[5-9][0-9]{9}$/', $d) ? $d : null;
}

function enforceRateLimit(string $sid, string $ip): void
{
    $dir = __DIR__ . '/rate_limit_logs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    checkRateBucket($dir, 'sid_' . md5($sid), 60, 300);
    checkRateBucket($dir, 'ip_'  . md5($ip), 120, 300);
}

function checkRateBucket(string $dir, string $key, int $max, int $win): void
{
    $file = $dir . '/' . $key . '.json';
    $now  = time();
    $reqs = [];

    if (is_file($file)) {
        $d = json_decode((string)@file_get_contents($file), true);
        if (is_array($d)) $reqs = $d;
    }

    $reqs = array_values(array_filter($reqs, fn($t) => is_numeric($t) && (int)$t > $now - $win));

    if (count($reqs) >= $max) {
        jsonError('Too many requests. Please wait.', 429);
    }

    $reqs[] = $now;
    @file_put_contents($file, json_encode($reqs), LOCK_EX);
}


// ══════════════════════════════════════════════════════════
// FREE-TEXT FLOW HELPERS
// ══════════════════════════════════════════════════════════
function isChipAnswer(string $field, string $msg): bool
{
    $msg   = trim($msg);
    $lower = strtolower($msg);

    $greetings = [
        'hi', 'hello', 'hey', 'hii', 'helo', 'namaste', 'haan', 'ok', 'okay',
        'sure', 'yes', 'no', 'nahi', 'thanks', 'thank', 'help', 'kya', 'kaise',
        'main', 'mujhe', 'mera', 'mere', 'aap'
    ];

    if (in_array($lower, $greetings, true)) return false;
    if (str_ends_with($msg, '?')) return false;
    $tooShort = (strlen($msg) < 4 && is_numeric($msg) === false);
    $nameOrMobile = ($field === 'customer_name' || $field === 'customer_mobile');
    if ($tooShort && $nameOrMobile === false) return false;

    switch ($field) {
        case 'product_type':
            return preg_match('/home|lap|business|personal|car|vehicle|property|loan/i', $msg) === 1;

        case 'city':
        case 'city_text':
            return strlen($msg) >= 3 && !preg_match('/\?|kaise|kya|mujhe|chahiye/i', $msg);

        case 'occupation_type':
            return strlen(trim($msg)) >= 2 && preg_match('/\?|kaise|kya|mujhe|chahiye|loan|help/i', $msg) === 0;

        case 'loan_purpose':
            return preg_match('/purchase|construction|renovation|balance|transfer|top.?up|improvement|ready|plot/i', $msg) === 1;

        case 'property_type':
        case 'property_type_text':
            return preg_match('/jda|society|freehold|rhb|bda|nagar|panchayat|riico|registry|flat|plot/i', $msg) === 1;

        case 'property_usage':
            return preg_match('/residential|commercial|industrial|warehouse|mixed|vacant|plot|rented|occupied/i', $msg) === 1;

        case 'loan_amount':
        case 'property_value':
        case 'monthly_income':
            return preg_match('/[\d]|lakh|lac|crore|cr\b|₹|rs\.?|income|salary/i', $msg) === 1;

        case 'existing_emi':
            return preg_match('/[\d]|nahi|no.emi|no existing|koi.nahi|zero|skip|baad|later|❌/i', $msg) === 1;

        case 'cibil_score':
            return preg_match('/\d{3}|750|700|650|excellent|good|average|low|below|pata|know|score/i', $msg) === 1;

        case 'customer_name':
            return strlen($msg) >= 2
                && strlen($msg) <= 60
                && !preg_match('/\?|[\d]{5,}|http|@/i', $msg)
                && !in_array($lower, ['hi', 'hello', 'hey', 'ok', 'okay', 'haan', 'no', 'nahi'], true);

        case 'customer_mobile':
            return preg_match('/^[\d\s\+\-]{8,15}$/', $msg) === 1;
        case 'consent':
            return strpos($msg, 'â') !== false
                || stripos($msg, 'agree') !== false
                || stripos($msg, 'haan') !== false
                || stripos($msg, 'yes') !== false;
    }

    return true;
}

function getFlowNudge(string $expecting, array $profile): string
{
    $nudges = [
        'product_type'    => "Ab batayein — kaunsa loan chahiye? 👆",
        'city'            => "Achha! Ab apna city batayein 👆",
        'occupation_type' => "Chalte hain! Apni income source select karein 👆",
        'loan_purpose'    => "Home loan ka purpose select karein 👆",
        'property_type'   => "Property ka title select karein 👆",
        'property_usage'  => "Property ka use batayein 👆",
        'loan_amount'     => "Loan amount select karein ya type karein 👆",
        'property_value'  => "Property ki value batayein 👆",
        'monthly_income'  => "Apni monthly income batayein 👆",
        'existing_emi'    => "Koi existing EMI chal rahi hai? 👆",
        'cibil_score'     => "Apna CIBIL score select karein 👆",
        'customer_name'   => "Aapka naam type karein 👆",
        'customer_mobile' => "WhatsApp number share karein — results turant milenge! 👆",
    ];

    return $nudges[$expecting] ?? '';
}

function getResumeQuestion(string $expecting, array $profile): string
{
    $q = buildQuestion($expecting, $profile);
    return $q['message'] ?? 'aage ki details batayein';
}

function getResumeChips(string $expecting): array
{
    $chipMap = [
        'product_type'    => ['🏠 Home Loan', '🏢 Loan Against Property', '💼 Business Loan', '👤 Personal Loan', '🚗 Car Loan'],
        'city'            => ['✏️ Enter city name', 'Jaipur', 'Jodhpur', 'Udaipur', 'Kota', 'Dilli NCR', 'Mumbai'],
        'occupation_type' => ['✏️ Describe occupation', '💼 Salaried - Private', '🏛 Salaried - Govt/PSU', '🏪 Self Employed', '🏭 Business Owner', '👨‍⚕️ Professional'],
        'loan_purpose'    => ['🏠 New Purchase — Ready Property', '🏗️ New Purchase — Under Construction', '🔨 Self Construction on Plot', '🔧 Renovation / Home Improvement', '💳 Balance Transfer', '💰 Top-Up on Existing Loan'],
        'property_type'   => ['✏️ Other title type', 'JDA Approved', 'Society Patta / Flat', 'Freehold / Registry', 'RHB / BDA', 'Nagar Nigam / Palika'],
        'property_usage'  => ['Residential - Self Occupied', 'Residential - Rented', 'Commercial', 'Industrial / Warehouse'],
        'loan_amount'     => ['✏️ Type exact amount', '₹10-25 Lakh', '₹25-50 Lakh', '₹50L-1 Cr', '₹1-2 Cr', '₹2-5 Cr'],
        'property_value'  => ['✏️ Type exact value', '₹25-50 Lakh', '₹50L-1 Cr', '₹1-2 Cr', '₹2-5 Cr', '₹5 Cr+'],
        'monthly_income'  => ['✏️ Type exact income', '₹15,000-25,000', '₹25,000-50,000', '₹50,000-1 Lakh', '₹1-2 Lakh', '₹2 Lakh+'],
        'existing_emi'    => ['✏️ Type exact EMI', 'No existing EMI', '₹5,000-10,000/mo', '₹10,000-25,000/mo', '₹25,000-50,000/mo'],
        'cibil_score'     => ['✏️ Type exact score', '750+ (Excellent 🟢)', '700-750 (Good 🟡)', '650-700 (Average 🟠)', 'Below 650 (Low 🔴)', "I don't know"],
    ];

    return $chipMap[$expecting] ?? [];
}