<?php
/**
 * ASTROLEARN – Assignments API
 *
 * CRUD operations for assignments (teacher-only for create/update/delete).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireAuth();
$db   = getDB();

$input  = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ? jsonInput() : $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';

// CSRF for mutations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $input[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        jsonResponse(['error' => 'Invalid CSRF token.'], 403);
    }
}

switch ($action) {

    // ── List assignments for current user ────────────────────────────
    case 'list':
        if ($user['role'] === 'student') {
            $stmt = $db->prepare(
                'SELECT a.*, c.name AS class_name
                 FROM assignments a
                 JOIN classes c ON a.class_id = c.id
                 JOIN enrollments e ON e.class_id = c.id AND e.student_id = :uid
                 ORDER BY a.due_date ASC'
            );
        } else {
            $stmt = $db->prepare(
                'SELECT a.*, c.name AS class_name
                 FROM assignments a
                 JOIN classes c ON a.class_id = c.id
                 WHERE a.teacher_id = :uid
                 ORDER BY a.created_at DESC'
            );
        }
        $stmt->execute([':uid' => $user['id']]);
        jsonResponse(['assignments' => $stmt->fetchAll()]);
        break;

    // ── Get single assignment ────────────────────────────────────────
    case 'get':
        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM assignments WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $asg = $stmt->fetch();
        if (!$asg) {
            jsonResponse(['error' => 'Assignment not found.'], 404);
        }
        jsonResponse(['assignment' => $asg]);
        break;

    // ── Create assignment (teacher only) ─────────────────────────────
    case 'create':
        requireRole('teacher', 'admin');
        $classId      = (int) ($input['class_id'] ?? 0);
        $title        = trim($input['title'] ?? '');
        $instructions = trim($input['instructions'] ?? '');
        $dueDate      = $input['due_date'] ?? null;

        if ($classId <= 0 || $title === '' || $instructions === '') {
            jsonResponse(['error' => 'Class, title, and instructions are required.'], 400);
        }

        $stmt = $db->prepare(
            'INSERT INTO assignments (class_id, teacher_id, title, instructions, due_date)
             VALUES (:cid, :tid, :title, :instructions, :due)'
        );
        $stmt->execute([
            ':cid'          => $classId,
            ':tid'          => $user['id'],
            ':title'        => $title,
            ':instructions' => $instructions,
            ':due'          => $dueDate ?: null,
        ]);
        jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
        break;

    // ── Delete assignment (teacher only) ─────────────────────────────
    case 'delete':
        requireRole('teacher', 'admin');
        $id = (int) ($input['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM assignments WHERE id = :id AND teacher_id = :tid');
        $stmt->execute([':id' => $id, ':tid' => $user['id']]);
        jsonResponse(['success' => $stmt->rowCount() > 0]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
