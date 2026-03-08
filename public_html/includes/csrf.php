<?php
/**
 * ASTROLEARN – CSRF Protection
 */

require_once __DIR__ . '/config.php';

/**
 * Generate or retrieve the current CSRF token.
 */
function csrfToken(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Return an HTML hidden input containing the CSRF token.
 */
function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate the CSRF token from a POST request.
 * Terminates with 403 on failure.
 */
function csrfValidate(): void {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token.']);
        exit;
    }
}
