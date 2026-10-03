<?php
$base = '../';   // this page is inside admin/ folder

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

// Delete a robot (bookmarks of this robot are removed automatically by the database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $pdo->prepare('DELETE FROM robots WHERE id = ?')->execute([$deleteId]);
    header('Location: manage_robots.php?msg=deleted');
    exit;
}

// Success messages coming from this page or robot_form.php
$messages = [
    'deleted' => 'Robot deleted successfully.',
    'added'   => 'Robot added successfully.',
    'updated' => 'Robot updated successfully.',
];
$msg = $messages[$_GET['msg'] ?? ''] ?? '';

// All robots with category name
$robots = $pdo->query(
    'SELECT r.id, r.name, r.manufacturer, r.image, r.video_url, r.model_url, c.name AS category_name
     FROM robots r
     JOIN categories c ON c.id = r.category_id
     ORDER BY r.id DESC'
)->fetchAll();

// Image path: full links stay as they are, local paths need "../" because we are in admin/
function admin_img(?string $path): string
{
    if (!$path) {
        return '';
    }
    return preg_match('~^https?://~', $path) ? $path : '../' . $path;
}

$pageTitle = 'Manage Robots';
include '../includes/header.php';
?>

<p style="margin-bottom:14px;"><a href="index.php">&larr; Back to dashboard</a></p>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <h2 class="section-title" style="margin:0;">Manage Robots (<?= count($robots) ?>)</h2>
    <a href="robot_form.php" class="btn">+ Add New Robot</a>
</div>

<?php if ($msg !== ''): ?>
    <div class="alert alert-success" style="margin-top:14px;"><?= e($msg) ?></div>
<?php endif; ?>

<?php if (empty($robots)): ?>
    <p class="empty">No robots yet. Add your first one.</p>
<?php else: ?>
    <div class="table-wrap" style="margin-top:14px;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Manufacturer</th>
                    <th>Video</th>
                    <th>3D</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($robots as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td>
                            <?php if ($r['image']): ?>
                                <img src="<?= e(admin_img($r['image'])) ?>" alt=""
                                     style="width:60px;height:45px;object-fit:cover;border-radius:4px;">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e($r['category_name']) ?></td>
                        <td><?= e($r['manufacturer']) ?></td>
                        <td><?= $r['video_url'] ? 'Yes' : 'No' ?></td>
                        <td><?= $r['model_url'] ? 'Yes' : 'No' ?></td>
                        <td style="white-space:nowrap;">
                            <a href="../robot.php?id=<?= (int)$r['id'] ?>" class="btn-small" target="_blank">View</a>
                            <a href="robot_form.php?id=<?= (int)$r['id'] ?>" class="btn-small">Edit</a>
                            <form method="post" action="manage_robots.php" style="display:inline;"
                                  onsubmit="return confirm('Delete this robot? This cannot be undone.');">
                                <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn-small" style="background:#dc2626;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
