<?php
/**
 * ASTROLEARN – Teacher: Class Management
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('teacher', 'admin');
$db   = getDB();

$classId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$class   = null;
$students    = [];
$assignments = [];

if ($classId > 0) {
    $stmt = $db->prepare('SELECT * FROM classes WHERE id = :id AND teacher_id = :tid LIMIT 1');
    $stmt->execute([':id' => $classId, ':tid' => $user['id']]);
    $class = $stmt->fetch();

    if ($class) {
        // Students
        $stmt = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email,
                    COUNT(d.id) AS doc_count, COALESCE(SUM(d.word_count),0) AS total_words
             FROM users u
             JOIN enrollments e ON e.student_id = u.id AND e.class_id = :cid
             LEFT JOIN documents d ON d.user_id = u.id
             GROUP BY u.id ORDER BY u.last_name'
        );
        $stmt->execute([':cid' => $classId]);
        $students = $stmt->fetchAll();

        // Assignments
        $stmt = $db->prepare('SELECT * FROM assignments WHERE class_id = :cid ORDER BY due_date DESC');
        $stmt->execute([':cid' => $classId]);
        $assignments = $stmt->fetchAll();
    }
}

// Handle form POST for creating a new class
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$classId) {
    csrfValidate();
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Class name is required.';
    } else {
        $stmt = $db->prepare(
            'INSERT INTO classes (school_id, teacher_id, name, description) VALUES (:sid, :tid, :name, :desc)'
        );
        $stmt->execute([
            ':sid'  => $user['school_id'] ?? null,
            ':tid'  => $user['id'],
            ':name' => $name,
            ':desc' => $desc,
        ]);
        redirect('/teacher/class.php?id=' . $db->lastInsertId());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $class ? e($class['name']) : 'New Class' ?> – AstroLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-4">
        <a href="/teacher/" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">← Teacher Panel</a>
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600"><?= $class ? e($class['name']) : 'New Class' ?></span>
    </nav>

    <div class="max-w-5xl mx-auto px-6 py-8">
        <?php if (!$class): ?>
            <!-- Create new class form -->
            <h1 class="text-2xl font-bold mb-6">Create a New Class</h1>
            <form method="POST" class="bg-white rounded-xl border border-gray-200 p-8 max-w-lg">
                <?= csrfField() ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm"><?= e($error) ?></div>
                <?php endif; ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-indigo-700">Create Class</button>
            </form>
        <?php else: ?>
            <!-- Class detail view -->
            <h1 class="text-2xl font-bold mb-2"><?= e($class['name']) ?></h1>
            <?php if ($class['description']): ?>
                <p class="text-gray-500 mb-6"><?= e($class['description']) ?></p>
            <?php endif; ?>

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Students -->
                <div>
                    <h2 class="text-lg font-semibold mb-4">Students (<?= count($students) ?>)</h2>
                    <?php if (empty($students)): ?>
                        <div class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-400 text-sm">
                            No students enrolled yet.
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                            <?php foreach ($students as $s): ?>
                                <div class="px-4 py-3 flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-sm"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></p>
                                        <p class="text-xs text-gray-400"><?= e($s['email']) ?></p>
                                    </div>
                                    <div class="text-right text-xs text-gray-400">
                                        <p><?= (int)$s['doc_count'] ?> docs</p>
                                        <p><?= number_format((int)$s['total_words']) ?> words</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Assignments -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">Assignments (<?= count($assignments) ?>)</h2>
                        <a href="/teacher/assignment.php?class_id=<?= $classId ?>" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-indigo-700">+ New</a>
                    </div>
                    <?php if (empty($assignments)): ?>
                        <div class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-400 text-sm">
                            No assignments yet.
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($assignments as $asg): ?>
                                <div class="bg-white rounded-xl border border-gray-200 p-4">
                                    <h3 class="font-medium text-sm"><?= e($asg['title']) ?></h3>
                                    <?php if ($asg['due_date']): ?>
                                        <p class="text-xs text-orange-500 mt-1">Due: <?= date('M j, Y', strtotime($asg['due_date'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
