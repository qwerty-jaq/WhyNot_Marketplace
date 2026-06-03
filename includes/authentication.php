<?php

/*
| includes/authentication.php
| ------------------
| Session + login helpers.  Include this whenever a page needs to
| know who is logged in.
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**Is somebody logged in? */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

//* The current logged-in user's id (or null) */
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/** The current logged-in user's role (buyer/seller/admin) or null */
function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/** The current logged-in user's display name */
function current_user_name(): ?string {
    return $_SESSION['first_name'] ?? null;
}

/** Force the user to be logged in - redirect to login if not */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

/** Force the user to be an admin - 403 if not */
function require_admin(): void {
    require_login();
    if (current_user_role() !== 'admin') {
        http_response_code(403);
        die('Access denied. Admins only.');
    }
}

/** Log a user in (sets all session variables) */
function log_in_user(int $user_id, string $user_role, string $first_name): void {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_role'] = $user_role;
    $_SESSION['first_name'] = $first_name;
}

/** Log the user out (destroys session) */
function log_out_user(): void {
    $_SESSION = [];
    session_destroy();
}

?>