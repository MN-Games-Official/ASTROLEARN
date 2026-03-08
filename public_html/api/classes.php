<?php
/**
 * ASTROLEARN – Classes API
 *
 * Manages classes and enrollments.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireAuth();
$db   = getDB();

$input  = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ? jsonInput() : $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $input[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        jsonResponse(['error' => 'Invalid CSRF token.'], 403);
    }
}

switch ($action) {

    // ── List classes ─────────────────────────────────────────────────
    case 'list':
        if ($user['role'] === 'student') {
            $stmt = $db->prepare(
                'SELECT c.*, u.first_name AS teacher_first, u.last_name AS teacher_last
                 FROM classes c
                 JOIN enrollments e ON e.class_id = c.id AND e.student_id = :uid
                 JOIN users u ON u.id = c.teacher_id
                 ORDER BY c.name'
            );
        } else {
            $stmt = $db->prepare(
                'SELECT c.* FROM classes c WHERE c.teacher_id = :uid ORDER BY c.name'
            );
        }
        $stmt->execute([':uid' => $user['id']]);
        jsonResponse(['classes' => $stmt->fetchAll()]);
        break;

    // ── Create class (teacher/admin) ─────────────────────────────────
    case 'create':
        requireRole('teacher', 'admin');
        $name        = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $schoolId    = $user['school_id'] ?? null;

        if ($name === '') {
            jsonResponse(['error' => 'Class name is required.'], 400);
        }

        $stmt = $db->prepare(
            'INSERT INTO classes (school_id, teacher_id, name, description)
             VALUES (:sid, :tid, :name, :desc)'
        );
        $stmt->execute([
            ':sid'  => $schoolId,
            ':tid'  => $user['id'],
            ':name' => $name,
            ':desc' => $description,
        ]);
        jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
        break;

    // ── Enroll student ───────────────────────────────────────────────
    case 'enroll':
        requireRole('teacher', 'admin');
        $classId   = (int) ($input['class_id']  ?? 0);
        $studentId = (int) ($input['student_id'] ?? 0);

        if ($classId <= 0 || $studentId <= 0) {
            jsonResponse(['error' => 'Class and student IDs are required.'], 400);
        }

        $stmt = $db->prepare(
            'INSERT IGNORE INTO enrollments (class_id, student_id) VALUES (:cid, :sid)'
        );
        $stmt->execute([':cid' => $classId, ':sid' => $studentId]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
