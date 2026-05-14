<?php
/**
 * ═══════════════════════════════════════════════════════════
 *  WealthMetre – Diva AI Loan Advisor
 *  FILE: /public_html/api/diva_parser.php
 *  PURPOSE: Parse free-text / natural language inputs into
 *           structured loan profile fields.
 *  METHOD:  POST JSON { text }
 *  RETURNS: JSON { loan_type, amount, monthly_income, ... }
 * ═══════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('POST required', 405);
}

$body = getJsonBody();
$text = sanitize($body['text'] ?? '');

if (!$text) {
    jsonError('text is required');
}

// ─── Try AI parsing first ────────────────────────────────
$aiResult = parseWithAI($text);
if ($aiResult) {
    jsonResponse(['parsed' => $aiResult, 'method' => 'ai']);
}

// ─── Fallback: regex parsing ─────────────────────────────
$parsed = parseWithRegex($text);
jsonResponse(['parsed' => $parsed, 'method' => 'regex']);


// ═══════════════════════════════════════════════════════════
//  AI PARSING (OpenAI)
// ═══════════════════════════════════════════════════════════
function parseWithAI(string $text): ?array {
    $systemPrompt = <<<PROMPT
You are a loan profile extractor for an Indian financial advisory service.
Extract loan profile data from the user's message and return ONLY valid JSON.
Return only the fields you can identify; omit unknown fields.
Currency is Indian Rupees (INR). Convert lakh/lac/cr to raw numbers.

JSON fields to extract:
{
  "loan_type": "lap|home|business|car|personal|education",
  "amount": <number in rupees or null>,
  "monthly_income": <number in rupees or null>,
  "existing_emis": <number in rupees or null>,
  "cibil": <number 300-900 or null>,
  "occupation": "salaried|self_employed|govt|professional|nri|other",
  "property_type": "jda|society|rhb|nagar_nigam|panchayati|riico|freehold|other",
  "property_usage": "residential|commercial|mixed|industrial|plot|other",
  "city": "<city string or null>",
  "property_valuation": <number in rupees or null>
}

Examples:
"LAP 50 lakh society jaipur salary 80k emi 20k cibil 720"
→ {"loan_type":"lap","amount":null,"monthly_income":80000,"existing_emis":20000,"cibil":720,"occupation":"salaried","property_type":"society","city":"jaipur"}

"home loan 30 lacs, self employed, jda property, kota"
→ {"loan_type":"home","amount":3000000,"occupation":"self_employed","property_type":"jda","city":"kota"}

Return ONLY the JSON object, nothing else.
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user',   'content' => $text],
    ];

    $reply = chatCompletion($messages, 300);
    if (!$reply) return null;

    // Clean and parse
    $reply = preg_replace('/```json|```/i', '', $reply);
    $reply = trim($reply);
    $data  = json_decode($reply, true);
    return is_array($data) ? $data : null;
}

// ═══════════════════════════════════════════════════════════
//  REGEX PARSING (fallback)
// ═══════════════════════════════════════════════════════════
function parseWithRegex(string $text): array {
    $t = strtolower(trim($text));
    $parsed = [];

    // Loan type
    $loanMap = [
        'lap'                   => 'lap',
        'loan against property' => 'lap',
        'home loan'             => 'home',
        'housing loan'          => 'home',
        'business loan'         => 'business',
        'working capital'       => 'business',
        'msme'                  => 'business',
        'car loan'              => 'car',
        'personal loan'         => 'personal',
        'education loan'        => 'education',
    ];
    foreach ($loanMap as $k => $v) {
        if (str_contains($t, $k)) { $parsed['loan_type'] = $v; break; }
    }

    // Amount in rupees
    if (preg_match('/(\d+(\.\d+)?)\s*(cr|crore)/i', $t, $m)) {
        $parsed['amount'] = (int)round((float)$m[1] * 10000000);
    } elseif (preg_match('/(\d+(\.\d+)?)\s*(lakh|lac|lacs|l)\b/i', $t, $m)) {
        $parsed['amount'] = (int)round((float)$m[1] * 100000);
    }

    // Monthly income
    if (preg_match('/(?:income|salary|sal)\s*[:\-]?\s*(\d{2,3})\s*k/i', $t, $m)) {
        $parsed['monthly_income'] = (int)$m[1] * 1000;
    } elseif (preg_match('/(?:income|salary)\s*[:\-]?\s*(\d{4,7})/i', $t, $m)) {
        $parsed['monthly_income'] = (int)$m[1];
    }

    // Existing EMI
    if (preg_match('/emi\s*[:\-]?\s*(\d{2,3})\s*k/i', $t, $m)) {
        $parsed['existing_emis'] = (int)$m[1] * 1000;
    } elseif (preg_match('/(\d{2,3})\s*k\s*(?:ki\s*)?emi/i', $t, $m)) {
        $parsed['existing_emis'] = (int)$m[1] * 1000;
    } elseif (str_contains($t, 'no emi') || str_contains($t, 'emi 0')) {
        $parsed['existing_emis'] = 0;
    }

    // CIBIL
    if (preg_match('/(?:cibil|credit score)\s*[:\-]?\s*(\d{3})\b/i', $t, $m)) {
        $parsed['cibil'] = (int)$m[1];
    } elseif (preg_match('/\b(\d{3})\+?\b/', $t, $m) && (int)$m[1] >= 300 && (int)$m[1] <= 900) {
        $parsed['cibil'] = (int)$m[1];
    }

    // Property type
    $propMap = [
        'jda'        => 'jda',
        'society'    => 'society',
        'flat'       => 'society',
        'apartment'  => 'society',
        'rhb'        => 'rhb',
        'freehold'   => 'freehold',
        'registry'   => 'freehold',
        'panchayati' => 'panchayati',
        'nagar nigam'=> 'nagar_nigam',
        'riico'      => 'riico',
    ];
    foreach ($propMap as $k => $v) {
        if (str_contains($t, $k)) { $parsed['property_type'] = $v; break; }
    }

    // Occupation
    $occMap = [
        'govt'         => 'govt',
        'government'   => 'govt',
        'sarkari'      => 'govt',
        'salaried'     => 'salaried',
        'job'          => 'salaried',
        'self employed'=> 'self_employed',
        'proprietor'   => 'self_employed',
        'businessman'  => 'self_employed',
        'doctor'       => 'professional',
        'ca '          => 'professional',
        'lawyer'       => 'professional',
    ];
    foreach ($occMap as $k => $v) {
        if (str_contains($t, $k)) { $parsed['occupation'] = $v; break; }
    }

    // City
    $cities = ['jaipur','kota','udaipur','jodhpur','ajmer','bikaner','alwar','bhilwara','sikar','delhi','mumbai','bangalore','pune','chennai','hyderabad'];
    foreach ($cities as $c) {
        if (str_contains($t, $c)) { $parsed['city'] = $c; break; }
    }

    return $parsed;
}
