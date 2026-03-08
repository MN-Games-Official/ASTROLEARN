<?php
/**
 * ASTROLEARN – AI API
 *
 * Routes AI requests through the policy engine then to the AI provider.
 *
 * Request flow:
 *   1. Authenticate user
 *   2. Classify request type / mode
 *   3. Policy engine checks whether the request is allowed
 *   4. Build targeted prompt
 *   5. Send to AI provider
 *   6. Post-process and log
 *   7. Return response
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/ai_client.php';
require_once __DIR__ . '/../includes/policies.php';

$user = requireAuth();
$db   = getDB();

// Only accept POST with JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

// CSRF check
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals(csrfToken(), $token)) {
    jsonResponse(['error' => 'Invalid CSRF token.'], 403);
}

$input = jsonInput();

$mode         = $input['mode']          ?? 'draft_coach';
$message      = trim($input['message']  ?? '');
$selectedText = trim($input['selected_text'] ?? '');
$documentId   = (int) ($input['document_id']   ?? 0);
$assignmentId = (int) ($input['assignment_id'] ?? 0);

if ($message === '') {
    jsonResponse(['error' => 'Please enter a message.'], 400);
}

// Validate mode
$validModes = array_keys(aiModes());
if (!in_array($mode, $validModes, true)) {
    $mode = 'draft_coach';
}

// ── Resolve policy context ──────────────────────────────────────────
$schoolId = $user['school_id'] ?? null;
$classId  = null;

// If we have an assignment, find the class
if ($assignmentId > 0) {
    $stmt = $db->prepare('SELECT class_id FROM assignments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $assignmentId]);
    $asg = $stmt->fetch();
    if ($asg) {
        $classId = (int) $asg['class_id'];
    }
}

$policy = resolvePolicy($schoolId, $classId, $assignmentId ?: null);

// ── Evaluate request against policy ─────────────────────────────────
$eval = evaluateRequest($mode, $message, $policy);

if (!$eval['allowed']) {
    // Log violation
    $violationId = logViolation(
        $user['id'],
        $documentId ?: null,
        null,
        $eval['violation']['severity'] ?? 'medium',
        $message
    );

    // Log AI event as refusal
    $stmt = $db->prepare(
        'INSERT INTO ai_events (document_id, user_id, event_type, mode, request_text, response_text)
         VALUES (:doc_id, :uid, :etype, :mode, :req, :res)'
    );
    $stmt->execute([
        ':doc_id' => $documentId ?: 0,
        ':uid'    => $user['id'],
        ':etype'  => 'refusal',
        ':mode'   => $mode,
        ':req'    => $message,
        ':res'    => $eval['reason'],
    ]);

    jsonResponse([
        'content' => $eval['reason'] . "\n\n" . safeRedirectMessage($mode),
        'blocked' => true,
    ]);
}

// ── Build prompt & call AI ──────────────────────────────────────────
$systemPrompt = aiSystemPrompt($mode, $policy);

$userContent = $message;
if ($selectedText !== '') {
    $userContent .= "\n\n--- Selected Text ---\n" . $selectedText;
}

$messages = [
    ['role' => 'system',  'content' => $systemPrompt],
    ['role' => 'user',    'content' => $userContent],
];

$result = aiChat($messages);

if (!empty($result['error'])) {
    jsonResponse(['error' => $result['error']], 502);
}

// ── Log AI event ────────────────────────────────────────────────────
$stmt = $db->prepare(
    'INSERT INTO ai_events (document_id, user_id, event_type, mode, request_text, response_text, model_used, tokens_used)
     VALUES (:doc_id, :uid, :etype, :mode, :req, :res, :model, :tokens)'
);
$stmt->execute([
    ':doc_id'  => $documentId ?: 0,
    ':uid'     => $user['id'],
    ':etype'   => 'response',
    ':mode'    => $mode,
    ':req'     => $message,
    ':res'     => $result['content'],
    ':model'   => $result['model'] ?? '',
    ':tokens'  => $result['tokens_used'] ?? 0,
]);

jsonResponse([
    'content' => $result['content'],
    'mode'    => $mode,
    'blocked' => false,
]);
