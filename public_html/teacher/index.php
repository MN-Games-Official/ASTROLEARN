<?php
/**
 * ASTROLEARN – Teacher Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('teacher', 'admin');
$db   = getDB();

// Fetch teacher's classes
$stmt = $db->prepare('SELECT * FROM classes WHERE teacher_id = :tid ORDER BY name');
$stmt->execute([':tid' => $user['id']]);
$classes = $stmt->fetchAll();

// Fetch recent violations across teacher's classes
$stmt = $db->prepare(
    'SELECT pv.*, u.first_name, u.last_name, c.name AS class_name
     FROM policy_violations pv
     JOIN users u ON u.id = pv.user_id
     JOIN enrollments e ON e.student_id = u.id
     JOIN classes c ON c.id = e.class_id AND c.teacher_id = :tid
     WHERE pv.status = "pending"
     ORDER BY pv.created_at DESC
     LIMIT 20'
);
$stmt->execute([':tid' => $user['id']]);
$violations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard – AstroLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Nav -->
    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="/dashboard.php" class="flex items-center gap-2">
                <span class="text-xl">🚀</span>
                <span class="text-lg font-bold text-indigo-600">AstroLearn</span>
            </a>
            <span class="text-gray-300">|</span>
            <span class="text-sm font-medium text-gray-600">Teacher Panel</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <a href="/dashboard.php" class="text-gray-600 hover:text-indigo-600">Dashboard</a>
            <a href="/teacher/flags.php" class="text-gray-600 hover:text-indigo-600">Flags</a>
            <a href="/api/auth.php?action=logout" class="text-red-500 hover:underline">Logout</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold mb-6">Teacher Dashboard</h1>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Classes -->
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">Your Classes</h2>
                    <a href="/teacher/class.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">+ New Class</a>
                </div>
                <?php if (empty($classes)): ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                        <p>No classes yet. Create one to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($classes as $cls): ?>
                            <a href="/teacher/class.php?id=<?= (int)$cls['id'] ?>"
                               class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition">
                                <h3 class="font-medium"><?= e($cls['name']) ?></h3>
                                <?php if ($cls['description']): ?>
                                    <p class="text-sm text-gray-500 mt-1"><?= e($cls['description']) ?></p>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Flags -->
            <div>
                <h2 class="text-lg font-semibold mb-4">Pending Flags</h2>
                <?php if (empty($violations)): ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-400 text-sm">
                        No pending flags. 🎉
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($violations as $v): ?>
                            <div class="bg-white rounded-xl border border-red-100 p-4">
                                <p class="font-medium text-sm">
                                    <?= e($v['first_name'] . ' ' . $v['last_name']) ?>
                                </p>
                                <p class="text-xs text-gray-400"><?= e($v['class_name']) ?></p>
                                <p class="text-xs text-red-500 mt-1 truncate"><?= e($v['flagged_text']) ?></p>
                                <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded <?= $v['severity'] === 'high' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                    <?= e(ucfirst($v['severity'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="/teacher/flags.php" class="block text-center text-sm text-indigo-600 hover:underline mt-4">View all flags →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
