<?php
$base = '../';   // this page is inside admin/ folder

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

// Totals for the dashboard
$stats = $pdo->query(
    'SELECT
        (SELECT COUNT(*) FROM robots)     AS robots,
        (SELECT COUNT(*) FROM categories) AS categories,
        (SELECT COUNT(*) FROM users)      AS users,
        (SELECT COUNT(*) FROM bookmarks)  AS bookmarks'
)->fetch();

// Latest 5 registered users
$latestUsers = $pdo->query(
    'SELECT name, email, role, created_at FROM users ORDER BY created_at DESC, id DESC LIMIT 5'
)->fetchAll();

$pageTitle = 'Admin Dashboard';
include '../includes/header.php';
?>

<h2 class="section-title" style="margin-top:0;">Admin Dashboard</h2>

<div class="grid">
    <div class="card"><div class="card-body">
        <h3 style="font-size:2rem;"><?= (int)$stats['robots'] ?></h3>
        <p>Robots</p>
        <a href="manage_robots.php" class="btn-small">Manage Robots</a>
    </div></div>

    <div class="card"><div class="card-body">
        <h3 style="font-size:2rem;"><?= (int)$stats['categories'] ?></h3>
        <p>Categories</p>
        <a href="manage_categories.php" class="btn-small">Manage Categories</a>
    </div></div>

    <div class="card"><div class="card-body">
        <h3 style="font-size:2rem;"><?= (int)$stats['users'] ?></h3>
        <p>Users</p>
        <a href="manage_users.php" class="btn-small">Manage Users</a>
    </div></div>

    <div class="card"><div class="card-body">
        <h3 style="font-size:2rem;"><?= (int)$stats['bookmarks'] ?></h3>
        <p>Total Bookmarks</p>
    </div></div>
</div>

<h2 class="section-title">Quick Actions</h2>
<p>
    <a href="robot_form.php" class="btn">+ Add New Robot</a>
    <a href="manage_categories.php" class="btn btn-outline">+ Add Category</a>
</p>

<h2 class="section-title">Latest Registered Users</h2>
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
        </thead>
        <tbody>
            <?php foreach ($latestUsers as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e(ucfirst($u['role'])) ?></td>
                    <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
