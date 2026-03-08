<?php
/**
 * ASTROLEARN – AI Client Abstraction Layer
 *
 * Wraps AI provider calls behind a consistent interface so models can
 * be swapped without touching application code.
 */

require_once __DIR__ . '/config.php';

/**
 * Send a chat-completion request to the configured AI provider.
 *
 * @param array  $messages  Array of {role, content} message objects.
 * @param string $model     Model identifier (uses default from config if empty).
 * @param int    $maxTokens Maximum tokens for the response.
 * @return array            Parsed response from the provider.
 */
function aiChat(array $messages, string $model = '', int $maxTokens = 0): array {
    $model     = $model     ?: AI_MODEL;
    $maxTokens = $maxTokens ?: AI_MAX_TOKENS;

    $payload = json_encode([
        'model'      => $model,
        'messages'   => $messages,
        'max_tokens' => $maxTokens,
        'temperature' => 0.7,
    ]);

    $ch = curl_init(AI_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . AI_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
    ]);

    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['error' => 'AI request failed: ' . $err, 'status' => 0];
    }

    $data = json_decode($raw, true);
    if ($code !== 200 || !is_array($data)) {
        return ['error' => $data['error']['message'] ?? 'Unknown AI error', 'status' => $code];
    }

    return [
        'content'     => $data['choices'][0]['message']['content'] ?? '',
        'model'       => $data['model'] ?? $model,
        'tokens_used' => $data['usage']['total_tokens'] ?? 0,
        'status'      => $code,
    ];
}

/**
 * Build system instructions for educational AI mode.
 */
function aiSystemPrompt(string $mode, array $policyContext = []): string {
    $base = "You are AstroLearn, an AI academic coach embedded in a school writing workspace. "
          . "Your role is to help students understand assignments, plan their work, improve their writing, "
          . "and develop critical thinking skills. You must NEVER write completed assignments, essays, or "
          . "submit-ready work for students. Always guide, scaffold, and ask questions instead of giving "
          . "direct answers. If a student asks you to do their work, politely refuse and redirect them "
          . "toward a learning-oriented approach.\n\n";

    $modeInstructions = [
        'interpreter' => "MODE: Assignment Interpreter\n"
            . "Restate the assignment prompt in simpler language. Identify the assignment type. "
            . "Explain what the teacher is likely looking for. Break the task into small steps. "
            . "Suggest what the student should do first. Ask one or two guiding questions.",

        'planner' => "MODE: Planner\n"
            . "Break the assignment into manageable steps. Create a work plan with milestones. "
            . "Suggest a logical order of tasks. Do NOT write any content for the student.",

        'brainstorm' => "MODE: Brainstorm Coach\n"
            . "Ask guided questions to help the student generate ideas. Help them find angles, "
            . "examples, or perspectives. Encourage original thinking. Do NOT provide pre-made ideas.",

        'outline' => "MODE: Outline Builder\n"
            . "Help structure an introduction, body, and conclusion. Suggest paragraph roles. "
            . "Guide claim/evidence/reasoning structure. Do NOT write the actual content.",

        'draft_coach' => "MODE: Draft Coach\n"
            . "Highlight weak areas in the student's writing. Suggest improvements without rewriting. "
            . "Encourage clarity, coherence, and strong argumentation. Provide specific feedback.",

        'reasoning' => "MODE: Reasoning Checker\n"
            . "Identify unsupported claims. Flag weak logic. Suggest types of evidence to look for. "
            . "Help the student strengthen their arguments without providing the arguments yourself.",

        'reflection' => "MODE: Reflection Mode\n"
            . "Ask the student why they made specific choices. Check understanding of their own writing. "
            . "Prompt deeper thinking about their work.",

        'grammar' => "MODE: Grammar & Writing Helper\n"
            . "Provide grammar, clarity, and tone suggestions. Identify repetition, vague language, "
            . "and missing transitions. Do NOT rewrite paragraphs wholesale.",
    ];

    $modePrompt = $modeInstructions[$mode] ?? $modeInstructions['draft_coach'];

    $policyNotes = '';
    if (!empty($policyContext)) {
        $policyNotes = "\n\nPOLICY CONSTRAINTS:\n";
        foreach ($policyContext as $key => $value) {
            $policyNotes .= "- {$key}: {$value}\n";
        }
    }

    return $base . $modePrompt . $policyNotes;
}

/**
 * Supported AI assistant modes.
 */
function aiModes(): array {
    return [
        'interpreter'  => 'Understand this assignment',
        'planner'      => 'Break it into steps',
        'brainstorm'   => 'Help me brainstorm',
        'outline'      => 'Help me outline',
        'draft_coach'  => 'Review my writing',
        'reasoning'    => 'Find weak reasoning',
        'reflection'   => 'Ask me questions instead',
        'grammar'      => 'Grammar & writing help',
    ];
}
