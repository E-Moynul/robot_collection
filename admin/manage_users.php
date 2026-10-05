<?php
$base = '../';

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$currentId = (int)$_SESSION['user_id'];
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = (int)($_POST['user_id'] ?? 0);


    if ($targetId === $currentId) {
        $errors[] = 'You cannot change or delete your own account from this page.';
    } else {


        if (isset($_POST['change_role'])) {
            $newRole = $_POST['role'] ?? '';
            if (!in_array($newRole, ['admin', 'user'], true)) {
                $errors[] = 'Invalid role.';
            } else {
                $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $targetId]);
                header('Location: manage_users.php?msg=role');
                exit;
            }
        }


        if (isset($_POST['delete_user'])) {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
            header('Location: manage_users.php?msg=deleted');
            exit;
        }
    }
}

$messages = [
    'role'    => 'User role updated successfully.',
    'deleted' => 'User deleted successfully.',
];
$msg = $messages[$_GET['msg'] ?? ''] ?? '';


$users = $pdo->query(
    'SELECT u.id, u.name, u.email, u.role, u.created_at, COUNT(b.id) AS bookmark_count
     FROM users u
     LEFT JOIN bookmarks b ON b.user_id = u.id
     GROUP BY u.id, u.name, u.email, u.role, u.created_at
     ORDER BY u.created_at DESC, u.id DESC'
)->fetchAll();

$pageTitle = 'Manage Users';
include '../includes/header.php';
?>

<p style="margin-bottom:14px;"><a href="index.php">&larr; Back to dashboard</a></p>

<h2 class="section-title" style="margin-top:0;">Manage Users (<?= count($users) ?>)</h2>

<?php if ($msg !== ''): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Bookmarks</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <?php $isSelf = (int)$u['id'] === $currentId; ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= e($u['name']) ?><?= $isSelf ? ' (you)' : '' ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e(ucfirst($u['role'])) ?></td>
                    <td><?= (int)$u['bookmark_count'] ?></td>
                    <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($isSelf): ?>
                            -
                        <?php else: ?>
                            <form method="post" action="manage_users.php" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <select name="role" style="width:auto; padding:5px;">
                                    <option value="user"  <?= $u['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" name="change_role" class="btn-small">Save</button>
                            </form>

                            <form method="post" action="manage_users.php" style="display:inline;"
                                  onsubmit="return confirm('Delete this user and all their bookmarks?');">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" name="delete_user" class="btn-small" style="background:#dc2626;">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
