<?php
$base = '../';   // this page is inside admin/ folder

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$errors = [];

// Form values (used for both "add" and "edit")
$form = ['id' => 0, 'name' => '', 'description' => ''];

// Edit mode: ?edit=ID loads that category into the form
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, description FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $found = $stmt->fetch();
    if ($found) {
        $form = $found;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ----- Add or update -----
    if (isset($_POST['save_category'])) {
        $form['id']          = (int)($_POST['id'] ?? 0);
        $form['name']        = trim($_POST['name'] ?? '');
        $form['description'] = trim($_POST['description'] ?? '');

        if ($form['name'] === '') {
            $errors[] = 'Category name is required.';
        } else {
            // Name must be unique (ignore the category being edited)
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ? AND id <> ?');
            $stmt->execute([$form['name'], $form['id']]);
            if ($stmt->fetch()) {
                $errors[] = 'A category with this name already exists.';
            }
        }

        if (empty($errors)) {
            if ($form['id'] > 0) {
                $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?')
                    ->execute([$form['name'], $form['description'] ?: null, $form['id']]);
                header('Location: manage_categories.php?msg=updated');
            } else {
                $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)')
                    ->execute([$form['name'], $form['description'] ?: null]);
                header('Location: manage_categories.php?msg=added');
            }
            exit;
        }
    }

    // ----- Delete -----
    if (isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];

        // A category that still has robots cannot be deleted
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM robots WHERE category_id = ?');
        $stmt->execute([$deleteId]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) {
            $errors[] = "Cannot delete: $count robot(s) still use this category. Move or delete them first.";
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$deleteId]);
            header('Location: manage_categories.php?msg=deleted');
            exit;
        }
    }
}

$messages = [
    'added'   => 'Category added successfully.',
    'updated' => 'Category updated successfully.',
    'deleted' => 'Category deleted successfully.',
];
$msg = $messages[$_GET['msg'] ?? ''] ?? '';

// All categories with robot counts
$categories = $pdo->query(
    'SELECT c.id, c.name, c.description, COUNT(r.id) AS robot_count
     FROM categories c
     LEFT JOIN robots r ON r.category_id = c.id
     GROUP BY c.id, c.name, c.description
     ORDER BY c.name'
)->fetchAll();

$isEdit    = (int)$form['id'] > 0;
$pageTitle = 'Manage Categories';
include '../includes/header.php';
?>

<p style="margin-bottom:14px;"><a href="index.php">&larr; Back to dashboard</a></p>

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

<div class="form-box wide" style="margin-top:0;">
    <h2><?= $isEdit ? 'Edit Category' : 'Add New Category' ?></h2>

    <form method="post" action="manage_categories.php">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

        <div class="form-group">
            <label for="name">Category Name *</label>
            <input type="text" id="name" name="name" value="<?= e($form['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e($form['description']) ?></textarea>
        </div>

        <button type="submit" name="save_category" class="btn">
            <?= $isEdit ? 'Update Category' : 'Add Category' ?>
        </button>
        <?php if ($isEdit): ?>
            <a href="manage_categories.php" class="btn btn-outline">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<h2 class="section-title">All Categories (<?= count($categories) ?>)</h2>

<?php if (empty($categories)): ?>
    <p class="empty">No categories yet. Add your first one above.</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Description</th><th>Robots</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?= (int)$c['id'] ?></td>
                        <td><?= e($c['name']) ?></td>
                        <td><?= e($c['description']) ?></td>
                        <td><?= (int)$c['robot_count'] ?></td>
                        <td style="white-space:nowrap;">
                            <a href="manage_categories.php?edit=<?= (int)$c['id'] ?>" class="btn-small">Edit</a>
                            <form method="post" action="manage_categories.php" style="display:inline;"
                                  onsubmit="return confirm('Delete this category?');">
                                <input type="hidden" name="delete_id" value="<?= (int)$c['id'] ?>">
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
