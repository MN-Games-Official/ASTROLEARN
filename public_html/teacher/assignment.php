<?php
/**
 * ASTROLEARN – Teacher: Create / View Assignment
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('teacher', 'admin');
$db   = getDB();

$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

// Fetch teacher's classes for the dropdown
$stmt = $db->prepare('SELECT id, name FROM classes WHERE teacher_id = :tid ORDER BY name');
$stmt->execute([':tid' => $user['id']]);
$classes = $stmt->fetchAll();

$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();

    $postClassId    = (int) ($_POST['class_id'] ?? 0);
    $title          = trim($_POST['title'] ?? '');
    $instructions   = trim($_POST['instructions'] ?? '');
    $dueDate        = $_POST['due_date'] ?? null;

    if ($postClassId <= 0 || $title === '' || $instructions === '') {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $db->prepare(
            'INSERT INTO assignments (class_id, teacher_id, title, instructions, due_date)
             VALUES (:cid, :tid, :title, :instr, :due)'
        );
        $stmt->execute([
            ':cid'   => $postClassId,
            ':tid'   => $user['id'],
            ':title' => $title,
            ':instr' => $instructions,
            ':due'   => $dueDate ?: null,
        ]);
        $success = 'Assignment created successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Assignment – AstroLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-4">
        <a href="/teacher/" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">← Teacher Panel</a>
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600">New Assignment</span>
    </nav>

    <div class="max-w-2xl mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold mb-6">Create Assignment</h1>

        <form method="POST" class="bg-white rounded-xl border border-gray-200 p-8">
            <?= csrfField() ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">Select a class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $c['id'] == $classId ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" name="title" required class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                <textarea name="instructions" required rows="8"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder="Paste the full assignment prompt here..."></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date (optional)</label>
                <input type="datetime-local" name="due_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-indigo-700">
                Create Assignment
            </button>
        </form>
    </div>

</body>
</html>
