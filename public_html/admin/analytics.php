<?php
/**
 * ASTROLEARN – Admin: Usage Analytics
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('admin');
$db   = getDB();

// User counts by role
$roleCounts = $db->query(
    "SELECT role, COUNT(*) AS cnt FROM users GROUP BY role"
)->fetchAll();

// AI events by mode (last 30 days)
$modeStats = $db->query(
    "SELECT mode, event_type, COUNT(*) AS cnt
     FROM ai_events
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY mode, event_type
     ORDER BY cnt DESC"
)->fetchAll();

// Violations by severity (last 30 days)
$violationStats = $db->query(
    "SELECT severity, COUNT(*) AS cnt
     FROM policy_violations
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY severity"
)->fetchAll();

// Documents created (last 30 days)
$docStats = $db->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
     FROM documents
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY day ORDER BY day"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics – AstroLearn Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-4">
        <a href="/admin/" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">← Admin Panel</a>
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600">Analytics</span>
    </nav>

    <div class="max-w-6xl mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold mb-6">Usage Analytics (Last 30 Days)</h1>

        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <!-- Users by Role -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-sm mb-3">Users by Role</h2>
                <?php foreach ($roleCounts as $rc): ?>
                    <div class="flex justify-between text-sm py-1">
                        <span class="capitalize"><?= e($rc['role']) ?></span>
                        <span class="font-medium"><?= number_format($rc['cnt']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- AI Usage by Mode -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-sm mb-3">AI Events by Mode</h2>
                <?php if (empty($modeStats)): ?>
                    <p class="text-sm text-gray-400">No AI events yet.</p>
                <?php else: ?>
                    <?php foreach ($modeStats as $ms): ?>
                        <div class="flex justify-between text-sm py-1">
                            <span><?= e($ms['mode'] ?? 'unknown') ?> <span class="text-gray-400">(<?= e($ms['event_type']) ?>)</span></span>
                            <span class="font-medium"><?= number_format($ms['cnt']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Violations by Severity -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-sm mb-3">Violations by Severity</h2>
                <?php if (empty($violationStats)): ?>
                    <p class="text-sm text-gray-400">No violations recorded.</p>
                <?php else: ?>
                    <?php foreach ($violationStats as $vs): ?>
                        <div class="flex justify-between text-sm py-1">
                            <span class="capitalize"><?= e($vs['severity']) ?></span>
                            <span class="font-medium"><?= number_format($vs['cnt']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Document creation over time -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-sm mb-3">Documents Created Per Day</h2>
            <?php if (empty($docStats)): ?>
                <p class="text-sm text-gray-400">No documents created yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-gray-200">
                                <th class="py-2 pr-4 font-medium text-gray-500">Date</th>
                                <th class="py-2 font-medium text-gray-500">Documents</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($docStats as $ds): ?>
                                <tr class="border-b border-gray-50">
                                    <td class="py-2 pr-4"><?= e($ds['day']) ?></td>
                                    <td class="py-2"><?= number_format($ds['cnt']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
