<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Filters from the URL (?q=...&category=...)
$search   = trim($_GET['q'] ?? '');
$category = (int)($_GET['category'] ?? 0);

// Categories for the dropdown
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Build the query step by step (always with prepared statement parameters)
$sql = 'SELECT r.id, r.name, r.manufacturer, r.country, r.image, c.name AS category_name
        FROM robots r
        JOIN categories c ON c.id = r.category_id
        WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (r.name LIKE ? OR r.manufacturer LIKE ? OR r.description LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}

if ($category > 0) {
    $sql .= ' AND r.category_id = ?';
    $params[] = $category;
}

$sql .= ' ORDER BY r.name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$robots = $stmt->fetchAll();

$placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='400' height='300'><rect width='100%' height='100%' fill='%23e5e7eb'/><text x='50%' y='50%' fill='%236b7280' font-size='22' text-anchor='middle' dy='.3em'>No Image</text></svg>";

$pageTitle = 'Robots';
include 'includes/header.php';
?>

<h2 class="section-title" style="margin-top:0;">All Robots</h2>

<form method="get" action="robots.php" class="filter-bar">
    <input type="text" name="q" placeholder="Search by name, manufacturer or description..." value="<?= e($search) ?>">

    <select name="category">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= $category === (int)$cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn">Search</button>
    <a href="robots.php" class="btn btn-outline">Reset</a>
</form>

<p class="meta"><?= count($robots) ?> robot(s) found</p>

<?php if (empty($robots)): ?>
    <p class="empty">No robots match your search.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($robots as $robot): ?>
            <div class="card">
                <img src="<?= e($robot['image'] ?: $placeholder) ?>"
                     alt="<?= e($robot['name']) ?>"
                     onerror="this.onerror=null;this.src=this.dataset.fallback;"
                     data-fallback="<?= e($placeholder) ?>">
                <div class="card-body">
                    <span class="badge"><?= e($robot['category_name']) ?></span>
                    <h3><?= e($robot['name']) ?></h3>
                    <p><?= e($robot['manufacturer']) ?><?= $robot['country'] ? ' · ' . e($robot['country']) : '' ?></p>
                    <a href="robot.php?id=<?= (int)$robot['id'] ?>" class="btn-small">Details</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
