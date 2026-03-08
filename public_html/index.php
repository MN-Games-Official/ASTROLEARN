<?php
/**
 * ASTROLEARN – Landing / Entry Point
 */
require_once __DIR__ . '/includes/auth.php';

initSession();
$user = currentUser();

// Redirect authenticated users to dashboard
if ($user) {
    header('Location: /dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AstroLearn – AI-Supported Academic Writing</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-900">

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-2xl">🚀</span>
            <span class="text-xl font-bold text-indigo-600">AstroLearn</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="/login.php" class="text-gray-600 hover:text-indigo-600 font-medium">Sign In</a>
            <a href="/register.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-medium">Get Started</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="max-w-5xl mx-auto px-6 py-20 text-center">
        <h1 class="text-5xl font-bold mb-6 leading-tight">
            Learn to write.<br>
            <span class="text-indigo-600">Think critically.</span><br>
            Succeed academically.
        </h1>
        <p class="text-xl text-gray-600 mb-10 max-w-2xl mx-auto">
            AstroLearn is an AI-supported academic writing workspace that helps students
            understand assignments, organize thoughts, and improve writing &mdash; without
            doing the work for them.
        </p>
        <div class="flex justify-center gap-4">
            <a href="/register.php" class="bg-indigo-600 text-white px-8 py-3 rounded-lg text-lg font-medium hover:bg-indigo-700">
                Start Writing
            </a>
            <a href="#features" class="border border-gray-300 text-gray-700 px-8 py-3 rounded-lg text-lg font-medium hover:border-indigo-300 hover:text-indigo-600">
                Learn More
            </a>
        </div>
    </main>

    <!-- Features Section -->
    <section id="features" class="max-w-6xl mx-auto px-6 py-16">
        <h2 class="text-3xl font-bold text-center mb-12">How AstroLearn Helps</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="text-3xl mb-4">📝</div>
                <h3 class="text-lg font-semibold mb-2">Understand Assignments</h3>
                <p class="text-gray-600">Paste your assignment prompt and get it explained in simpler language with clear steps to follow.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="text-3xl mb-4">🧠</div>
                <h3 class="text-lg font-semibold mb-2">Scaffold Your Thinking</h3>
                <p class="text-gray-600">Brainstorm ideas, build outlines, and plan your work with guided AI coaching.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="text-3xl mb-4">✨</div>
                <h3 class="text-lg font-semibold mb-2">Improve Your Writing</h3>
                <p class="text-gray-600">Get feedback on grammar, clarity, reasoning, and argument strength to level up your skills.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="text-3xl mb-4">🛡️</div>
                <h3 class="text-lg font-semibold mb-2">School-Safe Design</h3>
                <p class="text-gray-600">Built-in academic integrity controls prevent answer generation and keep learning honest.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="text-3xl mb-4">👩‍🏫</div>
                <h3 class="text-lg font-semibold mb-2">Teacher Oversight</h3>
                <p class="text-gray-600">Teachers can monitor AI usage, set policies, review assistance history, and manage classes.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="text-3xl mb-4">📊</div>
                <h3 class="text-lg font-semibold mb-2">Adaptive Support</h3>
                <p class="text-gray-600">Students who need more help get more scaffolding. Advanced students get deeper challenges.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-gray-200 mt-16 py-8 text-center text-gray-500 text-sm">
        &copy; <?= date('Y') ?> AstroLearn. AI-supported academic writing workspace.
    </footer>

</body>
</html>
