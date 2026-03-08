<?php
/**
 * ASTROLEARN – Teacher: Academic Integrity Flags
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('teacher', 'admin');
$db   = getDB();

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending', 'reviewed', 'dismissed'], true)) {
    $status = 'pending';
}

$stmt = $db->prepare(
    'SELECT pv.*, u.first_name, u.last_name, d.title AS document_title, c.name AS class_name
     FROM policy_violations pv
     JOIN users u ON u.id = pv.user_id
     LEFT JOIN documents d ON d.id = pv.document_id
     JOIN enrollments e ON e.student_id = u.id
     JOIN classes c ON c.id = e.class_id AND c.teacher_id = :tid
     WHERE pv.status = :status
     ORDER BY pv.created_at DESC
     LIMIT 100'
);
$stmt->execute([':tid' => $user['id'], ':status' => $status]);
$violations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrity Flags – AstroLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-4">
        <a href="/teacher/" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">← Teacher Panel</a>
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600">Integrity Flags</span>
    </nav>

    <div class="max-w-5xl mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold mb-6">Academic Integrity Flags</h1>

        <!-- Filter tabs -->
        <div class="flex gap-2 mb-6">
            <?php foreach (['pending', 'reviewed', 'dismissed'] as $tab): ?>
                <a href="?status=<?= $tab ?>"
                   class="px-4 py-2 rounded-lg text-sm font-medium <?= $status === $tab ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:border-indigo-300' ?>">
                    <?= ucfirst($tab) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($violations)): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                No <?= $status ?> flags.
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($violations as $v): ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium"><?= e($v['first_name'] . ' ' . $v['last_name']) ?></p>
                                <p class="text-sm text-gray-400"><?= e($v['class_name']) ?> · <?= $v['document_title'] ? e($v['document_title']) : 'Unknown document' ?></p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block text-xs px-2 py-0.5 rounded <?= $v['severity'] === 'high' ? 'bg-red-100 text-red-700' : ($v['severity'] === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') ?>">
                                    <?= ucfirst($v['severity']) ?>
                                </span>
                                <p class="text-xs text-gray-400 mt-1"><?= date('M j, Y g:ia', strtotime($v['created_at'])) ?></p>
                            </div>
                        </div>
                        <div class="mt-3 bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
                            <strong>Flagged request:</strong> <?= e($v['flagged_text']) ?>
                        </div>
                        <?php if ($v['resolution']): ?>
                            <div class="mt-2 text-sm text-gray-500">
                                <strong>Resolution:</strong> <?= e($v['resolution']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
