<?php
/**
 * ASTROLEARN – Admin: Global Policy Management
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('admin');
$db   = getDB();

// Handle updates
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();

    $rules = $_POST['rules'] ?? [];
    foreach ($rules as $id => $value) {
        $stmt = $db->prepare('UPDATE policy_rules SET rule_value = :val WHERE id = :id AND scope = "global"');
        $stmt->execute([':val' => $value, ':id' => (int) $id]);
    }
    $success = 'Policies updated successfully.';
}

// Fetch global rules
$stmt = $db->query('SELECT * FROM policy_rules WHERE scope = "global" ORDER BY id');
$globalRules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policies – AstroLearn Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-4">
        <a href="/admin/" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">← Admin Panel</a>
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600">Global Policies</span>
    </nav>

    <div class="max-w-3xl mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold mb-6">Global AI Policies</h1>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="bg-white rounded-xl border border-gray-200 p-8">
            <?= csrfField() ?>

            <p class="text-sm text-gray-500 mb-6">
                These rules apply globally across the platform. Schools, classes, and assignments can
                override specific rules at their own level.
            </p>

            <div class="space-y-5">
                <?php foreach ($globalRules as $rule): ?>
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4">
                        <div>
                            <p class="font-medium text-sm"><?= e($rule['rule_key']) ?></p>
                            <?php if ($rule['description']): ?>
                                <p class="text-xs text-gray-400 mt-0.5"><?= e($rule['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($rule['rule_key'] === 'enforcement_level'): ?>
                                <select name="rules[<?= (int)$rule['id'] ?>]"
                                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white">
                                    <?php foreach (['strict', 'balanced', 'supportive'] as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $rule['rule_value'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <select name="rules[<?= (int)$rule['id'] ?>]"
                                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white">
                                    <option value="1" <?= $rule['rule_value'] === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= $rule['rule_value'] === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="mt-6 bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-indigo-700">
                Save Policies
            </button>
        </form>
    </div>

</body>
</html>
