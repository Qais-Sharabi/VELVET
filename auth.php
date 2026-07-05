<?php
/**
 * auth.php — Centralised authentication helpers
 * Include AFTER dbconnect.php (which starts the session).
 */

/** Redirect to login, remembering where to return */
function requireLogin(string $redirect = ''): void {
    if (!isset($_SESSION['user_id'])) {
        $back = $redirect ?: (isset($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : '');
        header('Location: login.php' . ($back ? '?next=' . $back : ''));
        exit;
    }
}

/** True if current user is an admin */
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/** True if any user is signed in */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/** Current user id (0 for guests) */
function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

/** Populate session from a users row */
function populateSession(array $user): void {
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_name']   = $user['full_name'];
    $_SESSION['user_letter'] = strtoupper(substr($user['full_name'], 0, 1));
    $_SESSION['role']        = $user['role'];
}
