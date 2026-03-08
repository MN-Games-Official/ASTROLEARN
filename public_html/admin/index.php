<?php
/**
 * ASTROLEARN – Admin Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('admin');
$db   = getDB();

// Stats
$stats = [];
foreach (['users', 'classes', 'documents', 'assignments', 'policy_violations'] as $table) {
    $stmt = $db->query("SELECT COUNT(*) AS cnt FROM `{$table}`");
    $stats[$table] = (int) $stmt->fetch()['cnt'];
}

// Recent violations
$violations = $db->query(
    'SELECT pv.*, u.first_name, u.last_name
     FROM policy_violations pv
     JOIN users u ON u.id = pv.user_id
     ORDER BY pv.created_at DESC LIMIT 10'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – AstroLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="/dashboard.php" class="flex items-center gap-2">
                <span class="text-xl">🚀</span>
                <span class="text-lg font-bold text-indigo-600">AstroLearn</span>
            </a>
            <span class="text-gray-300">|</span>
            <span class="text-sm font-medium text-gray-600">Admin Panel</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <a href="/admin/policies.php" class="text-gray-600 hover:text-indigo-600">Policies</a>
            <a href="/admin/analytics.php" class="text-gray-600 hover:text-indigo-600">Analytics</a>
            <a href="/dashboard.php" class="text-gray-600 hover:text-indigo-600">Dashboard</a>
            <a href="/api/auth.php?action=logout" class="text-red-500 hover:underline">Logout</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold mb-6">Admin Dashboard</h1>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <?php
            $labels = [
                'users'             => ['Users',      '👤'],
                'classes'           => ['Classes',    '📚'],
                'documents'         => ['Documents',  '📄'],
                'assignments'       => ['Assignments','📋'],
                'policy_violations' => ['Flags',      '🚩'],
            ];
            foreach ($labels as $key => [$label, $icon]):
            ?>
                <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                    <p class="text-2xl mb-1"><?= $icon ?></p>
                    <p class="text-2xl font-bold"><?= number_format($stats[$key]) ?></p>
                    <p class="text-sm text-gray-400"><?= $label ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Violations -->
        <h2 class="text-lg font-semibold mb-4">Recent Violations</h2>
        <?php if (empty($violations)): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">No violations recorded.</div>
        <?php else: ?>
            <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                <?php foreach ($violations as $v): ?>
                    <div class="px-5 py-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-sm"><?= e($v['first_name'] . ' ' . $v['last_name']) ?></p>
                            <p class="text-xs text-gray-400 truncate max-w-md"><?= e($v['flagged_text']) ?></p>
                        </div>
                        <span class="text-xs bg-<?= $v['severity'] === 'high' ? 'red' : 'yellow' ?>-100 text-<?= $v['severity'] === 'high' ? 'red' : 'yellow' ?>-700 px-2 py-0.5 rounded">
                            <?= ucfirst($v['severity']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
