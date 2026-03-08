<?php
/**
 * ASTROLEARN – Flags API
 *
 * View and manage academic integrity flags / policy violations.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('teacher', 'admin');
$db   = getDB();

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── List all flags visible to this teacher ───────────────────────
    case 'list':
    default:
        $stmt = $db->prepare(
            'SELECT pv.*, u.first_name, u.last_name, d.title AS document_title
             FROM policy_violations pv
             JOIN users u ON u.id = pv.user_id
             LEFT JOIN documents d ON d.id = pv.document_id
             JOIN enrollments e ON e.student_id = u.id
             JOIN classes c ON c.id = e.class_id AND c.teacher_id = :tid
             ORDER BY pv.created_at DESC
             LIMIT 200'
        );
        $stmt->execute([':tid' => $user['id']]);
        jsonResponse(['flags' => $stmt->fetchAll()]);
        break;
}
