<?php
/**
 * ASTROLEARN – Registration Page
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

initSession();

if (currentUser()) {
    redirect('/dashboard.php');
}

$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $password  = $_POST['password']        ?? '';
    $confirm   = $_POST['password_confirm'] ?? '';
    $role      = $_POST['role']            ?? 'student';

    // Validation
    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['student', 'teacher'], true)) {
        $error = 'Invalid role selected.';
    } else {
        $result = registerUser($firstName, $lastName, $email, $password, $role);
        if (is_int($result)) {
            // Auto-login
            loginUser($email, $password);
            redirect('/dashboard.php');
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – AstroLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md mx-auto p-6">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="/" class="inline-flex items-center gap-2">
                <span class="text-3xl">🚀</span>
                <span class="text-2xl font-bold text-indigo-600">AstroLearn</span>
            </a>
            <p class="text-gray-500 mt-2">Create your AstroLearn account</p>
        </div>

        <!-- Form -->
        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <?= csrfField() ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" id="first_name" name="first_name" required
                           value="<?= e($_POST['first_name'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required
                           value="<?= e($_POST['last_name'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= e($_POST['email'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>

            <div class="mb-4">
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">I am a&hellip;</label>
                <select id="role" name="role"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                    <option value="student" <?= ($_POST['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="teacher" <?= ($_POST['role'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required minlength="8"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                <p class="text-xs text-gray-400 mt-1">At least 8 characters</p>
            </div>

            <div class="mb-6">
                <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-medium hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Create Account
            </button>
        </form>

        <p class="text-center mt-6 text-sm text-gray-500">
            Already have an account?
            <a href="/login.php" class="text-indigo-600 font-medium hover:underline">Sign in</a>
        </p>
    </div>

</body>
</html>
