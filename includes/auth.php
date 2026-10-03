<?php
// Session + authentication helpers
// Usage: require_once 'includes/auth.php';  (in admin/ pages: '../includes/auth.php')

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Is any user logged in?
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

// Is the logged-in user an admin?
function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

// Use at the top of pages only for logged-in users (root folder pages)
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// Use at the top of every page inside admin/ folder
function require_admin(): void
{
    if (!is_admin()) {
        header('Location: ../login.php');
        exit;
    }
}

// Safely print user-provided text in HTML (prevents XSS)
// Usage: <?= e($robot['name']) ?>
function e($text): string
{
    return htmlspecialchars((string)($text ?? ''), ENT_QUOTES, 'UTF-8');
}
