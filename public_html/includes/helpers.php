<?php
/**
 * ASTROLEARN – General Helper Functions
 */

/**
 * Escape a string for safe HTML output.
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Return a JSON response and exit.
 */
function jsonResponse(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Read JSON body from the request.
 */
function jsonInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Simple redirect helper.
 */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/**
 * Flash a message into the session for the next page load.
 */
function flash(string $key, string $message): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['_flash'][$key] = $message;
}

/**
 * Retrieve and clear a flash message.
 */
function getFlash(string $key): ?string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

/**
 * Count words in a string of HTML content.
 */
function wordCount(string $html): int {
    $text = strip_tags($html);
    $text = preg_replace('/\s+/', ' ', trim($text));
    return $text === '' ? 0 : count(explode(' ', $text));
}
