<?php
/**
 * ═══════════════════════════════════════════════════════════
 *  WealthMetre – Diva AI Loan Advisor
 *  FILE: /public_html/api/rag_query.php
 *  PURPOSE: RAG / policy intelligence layer.
 *           Searches lender policy documents in Qdrant,
 *           then uses GPT to synthesize a policy-aware answer.
 *  METHOD:  POST JSON { query, profile, lenders }
 * ═══════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('POST required', 405);
}

$body    = getJsonBody();
$query   = sanitize($body['query']   ?? '');
$profile = is_array($body['profile'] ?? null) ? $body['profile'] : [];
$lenders = is_array($body['lenders'] ?? null) ? $body['lenders'] : [];

if (!$query) {
    // Auto-build query from profile
    $query = buildQueryFromProfile($profile);
}

$result = runRagQuery($query, $profile, $lenders);
jsonResponse($result);


// ═══════════════════════════════════════════════════════════
//  MAIN RAG FUNCTION
// ═══════════════════════════════════════════════════════════
function runRagQuery(string $query, array $profile, array $lenders): array {

    // Step 1: Get embedding for the query
    $embedding = getEmbedding($query);

    // Step 2: Search Qdrant for relevant policy chunks
    $policyChunks = [];
    if ($embedding) {
        $qdrantFilter = buildQdrantFilter($profile, $lenders);
        $hits = qdrantSearch($embedding, 8, $qdrantFilter);

        foreach ($hits as $hit) {
            $payload = $hit['payload'] ?? [];
            $policyChunks[] = [
                'lender'  => $payload['lender_name'] ?? 'Unknown Lender',
                'section' => $payload['section']     ?? 'Policy',
                'text'    => $payload['text_preview'] ?? $payload['text'] ?? '',
                'score'   => round($hit['score'] ?? 0, 3),
            ];
        }
    }

    // Step 3: Build GPT context from chunks
    if (empty($policyChunks)) {
        // Qdrant not set up or no results — use AI knowledge directly
        return runDirectAIQuery($query, $profile, $lenders);
    }

    $contextText = buildContextText($policyChunks);

    // Step 4: GPT synthesis
    $answer = synthesizeWithGPT($query, $contextText, $profile);

    return [
        'type'          => 'rag_result',
        'answer'        => $answer,
        'policy_chunks' => count($policyChunks),
        'sources'       => array_unique(array_column($policyChunks, 'lender')),
    ];
}

// ─── Build search query from profile ────────────────────
function buildQueryFromProfile(array $p): string {
    $parts = [];
    if (!empty($p['loan_type']))         $parts[] = $p['loan_type'] . ' loan';
    if (!empty($p['property_title']))    $parts[] = $p['property_title'] . ' property';
    if (!empty($p['property_type']))     $parts[] = $p['property_type'];
    if (!empty($p['city']))              $parts[] = $p['city'];
    if (!empty($p['occupation']))        $parts[] = $p['occupation'];
    if (!empty($p['cibil_score']))       $parts[] = 'CIBIL ' . $p['cibil_score'];
    $parts[] = 'eligibility policy interest rate processing fee';
    return implode(' ', $parts);
}

// ─── Build Qdrant filter based on profile ───────────────
function buildQdrantFilter(array $profile, array $lenders): array {
    $filter = ['should' => []];

    // Filter by lender names if we have SQL results
    if (!empty($lenders)) {
        $lenderNames = array_column($lenders, 'lender_name');
        foreach ($lenderNames as $name) {
            $filter['should'][] = [
                'key' => 'lender_name',
                'match' => ['value' => $name],
            ];
        }
    }

    // Filter by loan type
    if (!empty($profile['loan_type'])) {
        $filter['should'][] = [
            'key' => 'loan_type',
            'match' => ['value' => $profile['loan_type']],
        ];
    }

    return empty($filter['should']) ? [] : $filter;
}

// ─── Build context text from Qdrant hits ────────────────
function buildContextText(array $chunks): string {
    $text = '';
    foreach ($chunks as $i => $chunk) {
        if (!$chunk['text']) continue;
        $text .= sprintf(
            "--- LENDER: %s | SECTION: %s | Relevance: %.2f ---\n%s\n\n",
            $chunk['lender'],
            $chunk['section'],
            $chunk['score'],
            $chunk['text']
        );
        if ($i >= 5) break; // limit tokens
    }
    return trim($text);
}

// ─── GPT synthesis ───────────────────────────────────────
function synthesizeWithGPT(string $query, string $context, array $profile): string {
    $profileSummary = json_encode(array_filter($profile), JSON_UNESCAPED_UNICODE);

    $systemPrompt = <<<PROMPT
You are Diva, an expert AI loan advisor for WealthMetre (India).

STRICT RULES — YOU MUST FOLLOW THESE:
1. ONLY use lenders from the Policy Context provided. Do NOT use general training knowledge.
2. NEVER mention SBI, HDFC, ICICI, Kotak or any lender NOT in the Policy Context.
3. Quote ACTUAL rates, LTV, and conditions from the context. No guessing.
4. If context is insufficient say: "Best match ke liye +91 7976218596 par call karein."
5. Do NOT invent interest rates, fees, LTV or policies.
6. Use Indian financial terms: CIBIL, FOIR, LTV, EMI, LAP, NTC.
7. Be concise and professional. Use bullet points for comparisons.
8. Respond in same language as query (Hindi/English/Hinglish).
9. End every response: "Final confirmation ke liye +91 7976218596 par call karein."
PROMPT;

    $userMessage = <<<MSG
Customer Profile:
{$profileSummary}

Policy Context from our database:
{$context}

Customer Question / Query:
{$query}

Please provide a clear, helpful response based on the policy context above.
MSG;

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user',   'content' => $userMessage],
    ];

    $reply = chatCompletion($messages, 500);
    return $reply ?? "I have found relevant policy information but couldn't process it right now. Please call +91 7976218596 for expert guidance.";
}

// ─── Direct AI query (no Qdrant) ────────────────────────
function runDirectAIQuery(string $query, array $profile, array $lenders): array {
    $lenderList = '';
    if (!empty($lenders)) {
        foreach (array_slice($lenders, 0, 5) as $l) {
            $lenderList .= "- {$l['lender_name']}: ROI {$l['interest_rate_start']}%, LTV {$l['max_ltv']}%\n";
        }
    }

    $profileSummary = json_encode(array_filter($profile), JSON_UNESCAPED_UNICODE);

    $systemPrompt = "You are Diva, an expert Indian loan advisor for WealthMetre. Be helpful, concise, and professional. Recommend calling +91 7976218596 for final confirmation.";

    $userMessage = "Customer Profile: {$profileSummary}\n\nShortlisted Lenders:\n{$lenderList}\n\nQuery: {$query}\n\nProvide helpful guidance.";

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user',   'content' => $userMessage],
    ];

    $reply = chatCompletion($messages, 500);

    return [
        'type'   => 'rag_result',
        'answer' => $reply ?? 'Please call +91 7976218596 for expert guidance on this query.',
        'policy_chunks' => 0,
        'sources' => [],
    ];
}
