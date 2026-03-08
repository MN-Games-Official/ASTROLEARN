<?php
/**
 * ASTROLEARN – Teacher API
 *
 * Student progress, AI history, and assistance review endpoints.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('teacher', 'admin');
$db   = getDB();

$input  = ($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json' ? jsonInput() : $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Student progress for a class ─────────────────────────────────
    case 'student_progress':
        $classId = (int) ($input['class_id'] ?? $_GET['class_id'] ?? 0);
        $stmt = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email,
                    COUNT(d.id) AS document_count,
                    SUM(d.word_count) AS total_words
             FROM users u
             JOIN enrollments e ON e.student_id = u.id AND e.class_id = :cid
             LEFT JOIN documents d ON d.user_id = u.id
             GROUP BY u.id
             ORDER BY u.last_name, u.first_name'
        );
        $stmt->execute([':cid' => $classId]);
        jsonResponse(['students' => $stmt->fetchAll()]);
        break;

    // ── AI assistance history for a student ──────────────────────────
    case 'ai_history':
        $studentId = (int) ($input['student_id'] ?? $_GET['student_id'] ?? 0);
        $stmt = $db->prepare(
            'SELECT ae.*, d.title AS document_title
             FROM ai_events ae
             LEFT JOIN documents d ON d.id = ae.document_id
             WHERE ae.user_id = :sid
             ORDER BY ae.created_at DESC
             LIMIT 100'
        );
        $stmt->execute([':sid' => $studentId]);
        jsonResponse(['events' => $stmt->fetchAll()]);
        break;

    // ── Policy violations / flags ────────────────────────────────────
    case 'violations':
        $classId = (int) ($input['class_id'] ?? $_GET['class_id'] ?? 0);
        $stmt = $db->prepare(
            'SELECT pv.*, u.first_name, u.last_name, d.title AS document_title
             FROM policy_violations pv
             JOIN users u ON u.id = pv.user_id
             LEFT JOIN documents d ON d.id = pv.document_id
             JOIN enrollments e ON e.student_id = u.id AND e.class_id = :cid
             ORDER BY pv.created_at DESC
             LIMIT 100'
        );
        $stmt->execute([':cid' => $classId]);
        jsonResponse(['violations' => $stmt->fetchAll()]);
        break;

    // ── Resolve a violation ──────────────────────────────────────────
    case 'resolve_violation':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Method not allowed.'], 405);
        }
        $token = $input[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals(csrfToken(), $token)) {
            jsonResponse(['error' => 'Invalid CSRF token.'], 403);
        }

        $violationId = (int) ($input['violation_id'] ?? 0);
        $status      = $input['status'] ?? 'reviewed';
        $resolution  = trim($input['resolution'] ?? '');

        if (!in_array($status, ['reviewed', 'dismissed'], true)) {
            jsonResponse(['error' => 'Invalid status.'], 400);
        }

        $stmt = $db->prepare(
            'UPDATE policy_violations SET status = :status, resolution = :resolution WHERE id = :id'
        );
        $stmt->execute([
            ':status'     => $status,
            ':resolution' => $resolution,
            ':id'         => $violationId,
        ]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
