<?php
/**
 * ASTROLEARN – Exports API
 *
 * Document export (placeholder for PDF / Google Docs export).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireAuth();
$db   = getDB();

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── Export document as HTML ──────────────────────────────────────
    case 'html':
        $docId = (int) ($_GET['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM documents WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $docId, ':uid' => $user['id']]);
        $doc = $stmt->fetch();
        if (!$doc) {
            jsonResponse(['error' => 'Document not found.'], 404);
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $doc['title']) . '.html"');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . e($doc['title']) . '</title></head><body>';
        echo '<h1>' . e($doc['title']) . '</h1>';
        echo $doc['content'];
        echo '</body></html>';
        exit;

    default:
        jsonResponse(['error' => 'Unknown export action.'], 400);
}
