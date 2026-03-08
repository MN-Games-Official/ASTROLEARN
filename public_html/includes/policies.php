<?php
/**
 * ASTROLEARN – Policy Engine
 *
 * Evaluates AI requests against the rule hierarchy:
 *   1. Global school rules
 *   2. Teacher class rules
 *   3. Assignment-specific rules
 *   4. Student-specific accommodations
 */

require_once __DIR__ . '/db.php';

// ── Cheating-detection patterns ──────────────────────────────────────

/**
 * Patterns that indicate direct-answer / cheating requests.
 * Each entry is [regex, severity].
 */
function cheatingPatterns(): array {
    return [
        ['/\b(write|do|complete|finish)\s+(my|the|this)\s+(essay|paper|assignment|homework|exam|test|report)\b/i', 'high'],
        ['/\bgive\s+me\s+the\s+answer/i',           'high'],
        ['/\bdo\s+(this|it)\s+for\s+me\b/i',        'high'],
        ['/\banswer\s+these\s+questions\b/i',        'high'],
        ['/\bcomplete\s+my\s+exam\b/i',              'high'],
        ['/\bwrite\s+the\s+entire\s+paper\b/i',      'high'],
        ['/\bmake\s+(this|it)\s+sound\s+human\b/i',  'high'],
        ['/\bundetectable\b/i',                       'medium'],
        ['/\bpretend\s+you\s+are\s+me\b/i',          'high'],
        ['/\bgenerate\s+final\s+version\b/i',        'medium'],
        ['/\bgive\s+me\s+a\s+model\s+response\b/i',  'medium'],
    ];
}

/**
 * Check a user request against cheating patterns.
 *
 * @return array|null  Null if clean, or ['pattern' => ..., 'severity' => ...].
 */
function detectCheating(string $text): ?array {
    foreach (cheatingPatterns() as [$regex, $severity]) {
        if (preg_match($regex, $text)) {
            return ['pattern' => $regex, 'severity' => $severity];
        }
    }
    return null;
}

// ── Policy rule resolution ───────────────────────────────────────────

/**
 * Resolve the effective policy for a request, following the hierarchy:
 * global -> school -> class -> assignment.
 *
 * @param int|null $schoolId
 * @param int|null $classId
 * @param int|null $assignmentId
 * @return array   Key-value map of effective rules.
 */
function resolvePolicy(?int $schoolId = null, ?int $classId = null, ?int $assignmentId = null): array {
    $db = getDB();

    // Collect scopes in hierarchy order
    $scopes = [['global', null]];
    if ($schoolId)     $scopes[] = ['school',     $schoolId];
    if ($classId)      $scopes[] = ['class',      $classId];
    if ($assignmentId) $scopes[] = ['assignment',  $assignmentId];

    $rules = [];
    foreach ($scopes as [$scope, $scopeId]) {
        if ($scopeId === null) {
            $stmt = $db->prepare('SELECT rule_key, rule_value FROM policy_rules WHERE scope = :scope AND scope_id IS NULL');
            $stmt->execute([':scope' => $scope]);
        } else {
            $stmt = $db->prepare('SELECT rule_key, rule_value FROM policy_rules WHERE scope = :scope AND scope_id = :scope_id');
            $stmt->execute([':scope' => $scope, ':scope_id' => $scopeId]);
        }
        foreach ($stmt->fetchAll() as $row) {
            $rules[$row['rule_key']] = $row['rule_value'];
        }
    }
    return $rules;
}

/**
 * Evaluate whether an AI request is allowed under the resolved policy.
 *
 * @param string $mode       The requested AI mode.
 * @param string $userText   The student's request text.
 * @param array  $policy     Resolved policy rules.
 * @return array             ['allowed' => bool, 'reason' => string|null, 'violation' => array|null]
 */
function evaluateRequest(string $mode, string $userText, array $policy): array {
    // 1. Check cheating patterns
    $cheat = detectCheating($userText);
    if ($cheat) {
        return [
            'allowed'   => false,
            'reason'    => "I can't do that for you, but I can help you understand the prompt, build an outline, and improve your own draft.",
            'violation' => $cheat,
        ];
    }

    // 2. Check mode-specific policy
    $modeRuleMap = [
        'outline'    => 'allow_outline_help',
        'brainstorm' => 'allow_brainstorming',
        'grammar'    => 'allow_grammar_help',
    ];

    if (isset($modeRuleMap[$mode]) && isset($policy[$modeRuleMap[$mode]]) && $policy[$modeRuleMap[$mode]] === '0') {
        return [
            'allowed'   => false,
            'reason'    => 'This type of AI assistance is not available for this assignment.',
            'violation' => ['pattern' => 'policy_block', 'severity' => 'low'],
        ];
    }

    // 3. Check test-mode blocking
    if (($policy['block_during_tests'] ?? '0') === '1' && ($policy['is_test'] ?? '0') === '1') {
        return [
            'allowed'   => false,
            'reason'    => 'AI assistance is not available during tests.',
            'violation' => ['pattern' => 'test_block', 'severity' => 'low'],
        ];
    }

    return ['allowed' => true, 'reason' => null, 'violation' => null];
}

/**
 * Log a policy violation to the database.
 */
function logViolation(int $userId, ?int $documentId, ?int $aiEventId, string $severity, string $flaggedText): int {
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO policy_violations (user_id, document_id, ai_event_id, severity, flagged_text)
         VALUES (:user_id, :document_id, :ai_event_id, :severity, :flagged_text)'
    );
    $stmt->execute([
        ':user_id'     => $userId,
        ':document_id' => $documentId,
        ':ai_event_id' => $aiEventId,
        ':severity'    => $severity,
        ':flagged_text' => $flaggedText,
    ]);
    return (int) $db->lastInsertId();
}

/**
 * Generate a safe educational redirect message when a request is blocked.
 */
function safeRedirectMessage(string $mode): string {
    $redirects = [
        'interpreter' => "Let me help you understand what this assignment is asking instead.",
        'planner'     => "Let's break this assignment into smaller steps you can tackle one at a time.",
        'brainstorm'  => "What ideas do you already have? Let's build on those.",
        'outline'     => "Let's create a structure together. What's your main point?",
        'draft_coach' => "Show me what you've written so far, and I'll help you improve it.",
        'reasoning'   => "Let's look at the reasoning in your writing and strengthen it together.",
        'reflection'  => "Tell me about the choices you've made in your writing so far.",
        'grammar'     => "I can help you check your grammar and clarity once you have a draft.",
    ];
    return $redirects[$mode] ?? "I can help you work through this step by step. What have you tried so far?";
}
