<?php
/**
 * ASTROLEARN – Student / Teacher Dashboard
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

$user = requireAuth();

// Fetch recent documents
$db = getDB();
$stmt = $db->prepare(
    'SELECT d.*, a.title AS assignment_title
     FROM documents d
     LEFT JOIN assignments a ON d.assignment_id = a.id
     WHERE d.user_id = :uid
     ORDER BY d.updated_at DESC
     LIMIT 20'
);
$stmt->execute([':uid' => $user['id']]);
$documents = $stmt->fetchAll();

// Fetch assignments (for students: via enrollments; for teachers: own)
if ($user['role'] === 'student') {
    $stmt = $db->prepare(
        'SELECT a.*, c.name AS class_name
         FROM assignments a
         JOIN classes c ON a.class_id = c.id
         JOIN enrollments e ON e.class_id = c.id AND e.student_id = :uid
         ORDER BY a.due_date ASC
         LIMIT 20'
    );
    $stmt->execute([':uid' => $user['id']]);
} else {
    $stmt = $db->prepare(
        'SELECT a.*, c.name AS class_name
         FROM assignments a
         JOIN classes c ON a.class_id = c.id
         WHERE a.teacher_id = :uid
         ORDER BY a.due_date DESC
         LIMIT 20'
    );
    $stmt->execute([':uid' => $user['id']]);
}
$assignments = $stmt->fetchAll();

// Fetch notifications
$stmt = $db->prepare(
    'SELECT * FROM notifications WHERE user_id = :uid AND is_read = 0 ORDER BY created_at DESC LIMIT 10'
);
$stmt->execute([':uid' => $user['id']]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – AstroLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-xl">🚀</span>
            <span class="text-lg font-bold text-indigo-600">AstroLearn</span>
        </div>
        <div class="flex items-center gap-4">
            <?php if ($user['role'] === 'teacher'): ?>
                <a href="/teacher/" class="text-sm text-gray-600 hover:text-indigo-600">Teacher Panel</a>
            <?php endif; ?>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="/admin/" class="text-sm text-gray-600 hover:text-indigo-600">Admin Panel</a>
            <?php endif; ?>
            <span class="text-sm text-gray-500"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span>
            <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full capitalize"><?= e($user['role']) ?></span>
            <a href="/api/auth.php?action=logout" class="text-sm text-red-500 hover:underline">Logout</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Welcome -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold">Welcome back, <?= e($user['first_name']) ?>!</h1>
            <p class="text-gray-500 mt-1">
                <?php if ($user['role'] === 'student'): ?>
                    Continue working on your documents or start a new assignment.
                <?php else: ?>
                    Manage your classes, assignments, and student progress.
                <?php endif; ?>
            </p>
        </div>

        <!-- Quick Actions -->
        <div class="flex gap-4 mb-8">
            <a href="/editor.php" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-indigo-700 inline-flex items-center gap-2">
                <span>+</span> New Document
            </a>
            <?php if ($user['role'] === 'teacher'): ?>
                <a href="/teacher/assignment.php" class="border border-gray-300 text-gray-700 px-5 py-2.5 rounded-lg font-medium hover:border-indigo-300 hover:text-indigo-600 inline-flex items-center gap-2">
                    <span>📋</span> New Assignment
                </a>
            <?php endif; ?>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Recent Documents -->
            <div class="lg:col-span-2">
                <h2 class="text-lg font-semibold mb-4">Recent Documents</h2>
                <?php if (empty($documents)): ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                        <p class="text-lg mb-2">No documents yet</p>
                        <p class="text-sm">Click "New Document" to start writing.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($documents as $doc): ?>
                            <a href="/editor.php?id=<?= (int)$doc['id'] ?>"
                               class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-medium"><?= e($doc['title']) ?></h3>
                                        <?php if ($doc['assignment_title']): ?>
                                            <p class="text-sm text-gray-400 mt-0.5">📋 <?= e($doc['assignment_title']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right text-sm text-gray-400">
                                        <p><?= e($doc['word_count']) ?> words</p>
                                        <p><?= date('M j', strtotime($doc['updated_at'])) ?></p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Assignments -->
                <h2 class="text-lg font-semibold mb-4">
                    <?= $user['role'] === 'student' ? 'Current Assignments' : 'Your Assignments' ?>
                </h2>
                <?php if (empty($assignments)): ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-400 text-sm mb-8">
                        No assignments yet.
                    </div>
                <?php else: ?>
                    <div class="space-y-3 mb-8">
                        <?php foreach ($assignments as $asg): ?>
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <h3 class="font-medium text-sm"><?= e($asg['title']) ?></h3>
                                <p class="text-xs text-gray-400 mt-1"><?= e($asg['class_name']) ?></p>
                                <?php if ($asg['due_date']): ?>
                                    <p class="text-xs text-orange-500 mt-1">Due: <?= date('M j, Y', strtotime($asg['due_date'])) ?></p>
                                <?php endif; ?>
                                <?php if ($user['role'] === 'student'): ?>
                                    <a href="/editor.php?assignment_id=<?= (int)$asg['id'] ?>"
                                       class="text-xs text-indigo-600 hover:underline mt-2 inline-block">Start working →</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Notifications -->
                <?php if (!empty($notifications)): ?>
                    <h2 class="text-lg font-semibold mb-4">Notifications</h2>
                    <div class="space-y-2">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="bg-white rounded-lg border border-gray-200 p-3 text-sm">
                                <p class="font-medium"><?= e($notif['title']) ?></p>
                                <?php if ($notif['body']): ?>
                                    <p class="text-gray-500 text-xs mt-1"><?= e($notif['body']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
