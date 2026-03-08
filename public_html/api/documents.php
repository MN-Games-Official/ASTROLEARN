<?php
/**
 * ASTROLEARN – Documents API
 *
 * CRUD operations for student documents with autosave support.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireAuth();
$db   = getDB();

// Accept both form POST and JSON body
$input = $_SERVER['CONTENT_TYPE'] === 'application/json'
    ? jsonInput()
    : $_POST;

$action = $input['action'] ?? $_GET['action'] ?? '';

// Validate CSRF for mutating requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $input[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        jsonResponse(['error' => 'Invalid CSRF token.'], 403);
    }
}

switch ($action) {

    // ── List documents ──────────────────────────────────────────────────
    case 'list':
        $stmt = $db->prepare(
            'SELECT id, title, word_count, status, created_at, updated_at
             FROM documents WHERE user_id = :uid ORDER BY updated_at DESC'
        );
        $stmt->execute([':uid' => $user['id']]);
        jsonResponse(['documents' => $stmt->fetchAll()]);
        break;

    // ── Get single document ─────────────────────────────────────────────
    case 'get':
        $id   = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM documents WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $user['id']]);
        $doc  = $stmt->fetch();
        if (!$doc) {
            jsonResponse(['error' => 'Document not found.'], 404);
        }
        jsonResponse(['document' => $doc]);
        break;

    // ── Create document ─────────────────────────────────────────────────
    case 'create':
        $title        = trim($input['title'] ?? 'Untitled Document');
        $content      = $input['content'] ?? '';
        $assignmentId = !empty($input['assignment_id']) ? (int) $input['assignment_id'] : null;

        $stmt = $db->prepare(
            'INSERT INTO documents (user_id, assignment_id, title, content, word_count)
             VALUES (:uid, :aid, :title, :content, :wc)'
        );
        $stmt->execute([
            ':uid'     => $user['id'],
            ':aid'     => $assignmentId,
            ':title'   => $title,
            ':content' => $content,
            ':wc'      => wordCount($content),
        ]);
        $newId = (int) $db->lastInsertId();
        jsonResponse(['success' => true, 'id' => $newId]);
        break;

    // ── Update document (autosave) ──────────────────────────────────────
    case 'update':
        $id      = (int) ($input['id'] ?? 0);
        $title   = trim($input['title'] ?? '');
        $content = $input['content'] ?? '';

        if ($id <= 0) {
            jsonResponse(['error' => 'Missing document id.'], 400);
        }

        // Verify ownership
        $stmt = $db->prepare('SELECT id FROM documents WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $id, ':uid' => $user['id']]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Document not found.'], 404);
        }

        $updates = [];
        $params  = [':id' => $id];

        if ($title !== '') {
            $updates[]        = 'title = :title';
            $params[':title'] = $title;
        }
        if ($content !== '') {
            $updates[]          = 'content = :content';
            $params[':content'] = $content;
            $updates[]          = 'word_count = :wc';
            $params[':wc']      = wordCount($content);
        }

        if (!empty($updates)) {
            $sql = 'UPDATE documents SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $db->prepare($sql)->execute($params);
        }

        jsonResponse(['success' => true]);
        break;

    // ── Delete document ─────────────────────────────────────────────────
    case 'delete':
        $id = (int) ($input['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM documents WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $user['id']]);
        jsonResponse(['success' => $stmt->rowCount() > 0]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
