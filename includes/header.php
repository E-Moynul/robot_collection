<?php
// Common header + navbar.
// Before including, a page may set:
//   $base       = '';      (root pages)  or  '../'  (pages inside admin/)
//   $pageTitle  = 'Page name';
//   $extraHead  = '<script ...></script>';   (optional, e.g. 3D viewer)

require_once __DIR__ . '/auth.php';

$base      = $base ?? '';
$pageTitle = $pageTitle ?? 'Robot Collection';
$extraHead = $extraHead ?? '';
$current   = basename($_SERVER['PHP_SELF']);

function nav_active(string $file, string $current): string
{
    return $file === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Robot Collection</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <?= $extraHead ?>
</head>
<body>

<header class="site-header">
    <nav class="navbar">
        <a href="<?= $base ?>index.php" class="brand">🤖 Robot Collection</a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">☰</button>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?= $base ?>index.php" class="<?= nav_active('index.php', $current) ?>">Home</a></li>
            <li><a href="<?= $base ?>robots.php" class="<?= nav_active('robots.php', $current) ?>">Robots</a></li>

            <?php if (is_logged_in()): ?>
                <li><a href="<?= $base ?>profile.php" class="<?= nav_active('profile.php', $current) ?>">My Profile</a></li>
                <?php if (is_admin()): ?>
                    <li><a href="<?= $base ?>admin/index.php">Admin Panel</a></li>
                <?php endif; ?>
                <li class="nav-user">Hi, <?= e($_SESSION['name'] ?? 'User') ?></li>
                <li><a href="<?= $base ?>logout.php" class="btn-small">Logout</a></li>
            <?php else: ?>
                <li><a href="<?= $base ?>login.php" class="<?= nav_active('login.php', $current) ?>">Login</a></li>
                <li><a href="<?= $base ?>register.php" class="btn-small">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main class="container">
