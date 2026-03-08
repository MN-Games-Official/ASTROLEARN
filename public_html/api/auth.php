<?php
/**
 * ASTROLEARN – Auth API
 *
 * Handles login, registration, logout, and session management.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'logout':
        logoutUser();
        redirect('/login.php');
        break;

    case 'me':
        $user = currentUser();
        if ($user) {
            unset($user['password_hash']);
            jsonResponse(['user' => $user]);
        } else {
            jsonResponse(['user' => null], 401);
        }
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 400);
}
