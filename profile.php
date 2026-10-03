<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

require_login();

$userId  = (int)$_SESSION['user_id'];
$message = '';
$error   = '';

// Load the current user
$stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

// User no longer exists in the database -> log out
if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update name
    if (isset($_POST['update_name'])) {
        $newName = trim($_POST['name'] ?? '');
        if ($newName === '') {
            $error = 'Name cannot be empty.';
        } else {
            $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$newName, $userId]);
            $_SESSION['name'] = $newName;
            $user['name'] = $newName;
            $message = 'Name updated successfully.';
        }
    }

    // Remove a bookmark
    if (isset($_POST['remove_bookmark'])) {
        $robotId = (int)($_POST['robot_id'] ?? 0);
        $pdo->prepare('DELETE FROM bookmarks WHERE user_id = ? AND robot_id = ?')->execute([$userId, $robotId]);
        header('Location: profile.php');
        exit;
    }
}

// Bookmarked robots
$stmt = $pdo->prepare(
    'SELECT r.id, r.name, r.manufacturer, r.image, c.name AS category_name
     FROM bookmarks b
     JOIN robots r ON r.id = b.robot_id
     JOIN categories c ON c.id = r.category_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC'
);
$stmt->execute([$userId]);
$bookmarks = $stmt->fetchAll();

$placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='400' height='300'><rect width='100%' height='100%' fill='%23e5e7eb'/><text x='50%' y='50%' fill='%236b7280' font-size='22' text-anchor='middle' dy='.3em'>No Image</text></svg>";

$pageTitle = 'My Profile';
include 'includes/header.php';
?>

<div class="form-box wide" style="margin-top:0;">
    <h2>My Profile</h2>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <p><strong>Email:</strong> <?= e($user['email']) ?></p>
    <p><strong>Role:</strong> <?= e(ucfirst($user['role'])) ?></p>
    <p style="margin-bottom:18px;"><strong>Member since:</strong> <?= e(date('d M Y', strtotime($user['created_at']))) ?></p>

    <form method="post" action="profile.php">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required>
        </div>
        <button type="submit" name="update_name" class="btn">Update Name</button>
    </form>
</div>

<h2 class="section-title">My Bookmarked Robots</h2>

<?php if (empty($bookmarks)): ?>
    <p class="empty">You have not bookmarked any robots yet. <a href="robots.php">Browse robots</a></p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($bookmarks as $robot): ?>
            <div class="card">
                <img src="<?= e($robot['image'] ?: $placeholder) ?>"
                     alt="<?= e($robot['name']) ?>"
                     onerror="this.onerror=null;this.src=this.dataset.fallback;"
                     data-fallback="<?= e($placeholder) ?>">
                <div class="card-body">
                    <span class="badge"><?= e($robot['category_name']) ?></span>
                    <h3><?= e($robot['name']) ?></h3>
                    <p><?= e($robot['manufacturer']) ?></p>
                    <a href="robot.php?id=<?= (int)$robot['id'] ?>" class="btn-small">Details</a>

                    <form method="post" action="profile.php" style="display:inline;">
                        <input type="hidden" name="robot_id" value="<?= (int)$robot['id'] ?>">
                        <button type="submit" name="remove_bookmark" class="btn-small" style="background:#dc2626;">Remove</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
