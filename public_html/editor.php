<?php
/**
 * ASTROLEARN – Document Editor
 *
 * The core writing workspace with AI sidebar, formatting tools, and autosave.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ai_client.php';

$user = requireAuth();
$db   = getDB();

$docId        = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$assignmentId = isset($_GET['assignment_id']) ? (int) $_GET['assignment_id'] : 0;
$document     = null;
$assignment   = null;

// Load existing document
if ($docId > 0) {
    $stmt = $db->prepare('SELECT * FROM documents WHERE id = :id AND user_id = :uid LIMIT 1');
    $stmt->execute([':id' => $docId, ':uid' => $user['id']]);
    $document = $stmt->fetch();
    if (!$document) {
        redirect('/dashboard.php');
    }
    $assignmentId = $document['assignment_id'] ?? 0;
}

// Load assignment context
if ($assignmentId > 0) {
    $stmt = $db->prepare('SELECT * FROM assignments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $assignmentId]);
    $assignment = $stmt->fetch();
}

$modes = aiModes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $document ? e($document['title']) : 'New Document' ?> – AstroLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        #editor {
            min-height: 60vh;
            outline: none;
            line-height: 1.8;
        }
        #editor:empty::before {
            content: 'Start writing here...';
            color: #9ca3af;
        }
        .ai-msg-user { background-color: #eef2ff; }
        .ai-msg-assistant { background-color: #f0fdf4; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

    <!-- Top Bar -->
    <nav class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-4">
            <a href="/dashboard.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">← Dashboard</a>
            <input type="text" id="doc-title"
                   value="<?= e($document['title'] ?? 'Untitled Document') ?>"
                   class="text-lg font-semibold border-none outline-none bg-transparent focus:bg-white focus:border focus:border-gray-300 focus:rounded px-2 py-1 w-72">
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-500">
            <span id="word-count">0 words</span>
            <span id="save-status" class="text-green-600">Saved</span>
            <span class="capitalize bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded text-xs"><?= e($user['role']) ?></span>
        </div>
    </nav>

    <!-- Formatting Toolbar -->
    <div class="bg-white border-b border-gray-200 px-4 py-1.5 flex items-center gap-1 shrink-0">
        <button onclick="execCmd('bold')"          class="toolbar-btn px-2 py-1 rounded hover:bg-gray-100 font-bold text-sm" title="Bold">B</button>
        <button onclick="execCmd('italic')"        class="toolbar-btn px-2 py-1 rounded hover:bg-gray-100 italic text-sm" title="Italic">I</button>
        <button onclick="execCmd('underline')"     class="toolbar-btn px-2 py-1 rounded hover:bg-gray-100 underline text-sm" title="Underline">U</button>
        <span class="w-px h-5 bg-gray-200 mx-1"></span>
        <button onclick="execCmd('insertUnorderedList')" class="toolbar-btn px-2 py-1 rounded hover:bg-gray-100 text-sm" title="Bullet List">• List</button>
        <button onclick="execCmd('insertOrderedList')"   class="toolbar-btn px-2 py-1 rounded hover:bg-gray-100 text-sm" title="Numbered List">1. List</button>
        <span class="w-px h-5 bg-gray-200 mx-1"></span>
        <button onclick="execCmd('formatBlock', 'h2')"   class="toolbar-btn px-2 py-1 rounded hover:bg-gray-100 text-sm font-semibold" title="Heading">H2</button>
        <button onclick="execCmd('formatBlock', 'h3')"   class="toolbar-btn px-2 py-1 rounded hover:bg-gray-100 text-sm font-semibold" title="Sub-heading">H3</button>
        <button onclick="execCmd('formatBlock', 'blockquote')" class="toolbar-btn px-2 py-1 rounded hover:bg-gray-100 text-sm" title="Quote">" Quote</button>
    </div>

    <div class="flex flex-1 overflow-hidden">
        <!-- Left Sidebar: Documents / Assignments -->
        <aside class="w-56 bg-white border-r border-gray-200 p-4 overflow-y-auto hidden lg:block shrink-0">
            <?php if ($assignment): ?>
                <div class="mb-6">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">Assignment</h3>
                    <p class="text-sm font-medium"><?= e($assignment['title']) ?></p>
                    <p class="text-xs text-gray-500 mt-1 whitespace-pre-line"><?= e($assignment['instructions']) ?></p>
                    <?php if ($assignment['due_date']): ?>
                        <p class="text-xs text-orange-500 mt-2">Due: <?= date('M j, Y', strtotime($assignment['due_date'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">AI Modes</h3>
            <div class="space-y-1">
                <?php foreach ($modes as $key => $label): ?>
                    <button onclick="setAiMode('<?= $key ?>')"
                            class="ai-mode-btn w-full text-left text-sm px-3 py-1.5 rounded hover:bg-indigo-50 hover:text-indigo-600 <?= $key === 'interpreter' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600' ?>"
                            data-mode="<?= $key ?>">
                        <?= e($label) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Main Editor Canvas -->
        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <div id="editor" contenteditable="true"
                     class="prose max-w-none text-gray-800"><?= $document ? $document['content'] : '' ?></div>
            </div>
        </main>

        <!-- Right Sidebar: AI Assistant -->
        <aside class="w-80 bg-white border-l border-gray-200 flex flex-col shrink-0 hidden md:flex">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="font-semibold text-sm">AI Assistant</h2>
                <p class="text-xs text-gray-400" id="ai-mode-label">Mode: Understand this assignment</p>
            </div>
            <div id="ai-messages" class="flex-1 overflow-y-auto p-4 space-y-3">
                <div class="text-center text-sm text-gray-400 py-8">
                    <p>Select text or type a question below.</p>
                    <p class="text-xs mt-1">Choose a mode from the left sidebar.</p>
                </div>
            </div>
            <div class="px-4 py-3 border-t border-gray-200">
                <form id="ai-form" class="flex gap-2" onsubmit="sendAiMessage(event)">
                    <input type="text" id="ai-input"
                           placeholder="Ask for help..."
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                        Send
                    </button>
                </form>
            </div>
        </aside>
    </div>

    <script>
    // ── State ───────────────────────────────────────────────────────────
    const DOC_ID        = <?= $docId ?>;
    const ASSIGNMENT_ID = <?= $assignmentId ?>;
    const CSRF_TOKEN    = '<?= csrfToken() ?>';
    let currentMode     = 'interpreter';
    let saveTimeout     = null;
    let lastContent     = '';

    // ── Editor commands ─────────────────────────────────────────────────
    function execCmd(command, value = null) {
        document.execCommand(command, false, value);
        document.getElementById('editor').focus();
    }

    // ── Word count ──────────────────────────────────────────────────────
    function updateWordCount() {
        const text = document.getElementById('editor').innerText.trim();
        const count = text === '' ? 0 : text.split(/\s+/).length;
        document.getElementById('word-count').textContent = count + ' words';
    }

    // ── Autosave ────────────────────────────────────────────────────────
    function scheduleSave() {
        const content = document.getElementById('editor').innerHTML;
        if (content === lastContent) return;
        lastContent = content;

        document.getElementById('save-status').textContent = 'Saving...';
        document.getElementById('save-status').className = 'text-yellow-600';

        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => saveDocument(content), 2000);
    }

    async function saveDocument(content) {
        const title = document.getElementById('doc-title').value || 'Untitled Document';
        try {
            const res = await fetch('/api/documents.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRF_TOKEN,
                },
                body: JSON.stringify({
                    action: DOC_ID ? 'update' : 'create',
                    id: DOC_ID || undefined,
                    title: title,
                    content: content,
                    assignment_id: ASSIGNMENT_ID || undefined,
                }),
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('save-status').textContent = 'Saved';
                document.getElementById('save-status').className = 'text-green-600';
                // Update URL if new doc was created
                if (!DOC_ID && data.id) {
                    window.history.replaceState(null, '', '/editor.php?id=' + data.id);
                }
            } else {
                document.getElementById('save-status').textContent = 'Save failed';
                document.getElementById('save-status').className = 'text-red-600';
            }
        } catch {
            document.getElementById('save-status').textContent = 'Save failed';
            document.getElementById('save-status').className = 'text-red-600';
        }
    }

    // ── AI Mode ─────────────────────────────────────────────────────────
    const MODE_LABELS = <?= json_encode($modes) ?>;

    function setAiMode(mode) {
        currentMode = mode;
        document.getElementById('ai-mode-label').textContent = 'Mode: ' + (MODE_LABELS[mode] || mode);
        document.querySelectorAll('.ai-mode-btn').forEach(btn => {
            btn.classList.toggle('bg-indigo-50', btn.dataset.mode === mode);
            btn.classList.toggle('text-indigo-600', btn.dataset.mode === mode);
        });
    }

    // ── AI Chat ─────────────────────────────────────────────────────────
    function appendMessage(role, text) {
        const container = document.getElementById('ai-messages');
        // Clear the placeholder if present
        if (container.querySelector('.text-center')) {
            container.innerHTML = '';
        }
        const div = document.createElement('div');
        div.className = 'rounded-lg px-3 py-2 text-sm whitespace-pre-line ' +
                        (role === 'user' ? 'ai-msg-user' : 'ai-msg-assistant');
        div.textContent = text;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    async function sendAiMessage(e) {
        e.preventDefault();
        const input = document.getElementById('ai-input');
        const text = input.value.trim();
        if (!text) return;
        input.value = '';

        appendMessage('user', text);

        // Get selected text from editor
        const selection = window.getSelection();
        let selectedText = '';
        const editor = document.getElementById('editor');
        if (selection.rangeCount > 0 && editor.contains(selection.anchorNode)) {
            selectedText = selection.toString();
        }

        try {
            const res = await fetch('/api/ai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRF_TOKEN,
                },
                body: JSON.stringify({
                    mode: currentMode,
                    message: text,
                    selected_text: selectedText,
                    document_id: DOC_ID || 0,
                    assignment_id: ASSIGNMENT_ID || 0,
                }),
            });
            const data = await res.json();
            if (data.error) {
                appendMessage('assistant', '⚠️ ' + data.error);
            } else {
                appendMessage('assistant', data.content || 'No response.');
            }
        } catch {
            appendMessage('assistant', '⚠️ Could not reach the AI service.');
        }
    }

    // ── Init ────────────────────────────────────────────────────────────
    const editor = document.getElementById('editor');
    editor.addEventListener('input', () => {
        updateWordCount();
        scheduleSave();
    });
    lastContent = editor.innerHTML;
    updateWordCount();
    </script>

</body>
</html>
