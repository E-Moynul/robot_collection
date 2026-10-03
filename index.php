<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// All categories with number of robots in each
$categories = $pdo->query(
    'SELECT c.id, c.name, c.description, COUNT(r.id) AS robot_count
     FROM categories c
     LEFT JOIN robots r ON r.category_id = c.id
     GROUP BY c.id, c.name, c.description
     ORDER BY c.name'
)->fetchAll();

// Latest 6 robots
$latest = $pdo->query(
    'SELECT r.id, r.name, r.manufacturer, r.image, c.name AS category_name
     FROM robots r
     JOIN categories c ON c.id = r.category_id
     ORDER BY r.created_at DESC, r.id DESC
     LIMIT 6'
)->fetchAll();

// Simple grey placeholder if a robot image is missing
$placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='400' height='300'><rect width='100%' height='100%' fill='%23e5e7eb'/><text x='50%' y='50%' fill='%236b7280' font-size='22' text-anchor='middle' dy='.3em'>No Image</text></svg>";

$pageTitle = 'Home';
include 'includes/header.php';
?>

<section class="hero">
    <h1>Explore the World of Robots</h1>
    <p>Browse popular robots by category, watch videos, read their specifications and view 3D models.</p>
    <a href="robots.php" class="btn">Browse All Robots</a>
</section>

<h2 class="section-title">Categories</h2>
<?php if (empty($categories)): ?>
    <p class="empty">No categories added yet.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($categories as $cat): ?>
            <div class="card">
                <div class="card-body">
                    <span class="badge"><?= (int)$cat['robot_count'] ?> robots</span>
                    <h3><?= e($cat['name']) ?></h3>
                    <p><?= e($cat['description']) ?></p>
                    <a href="robots.php?category=<?= (int)$cat['id'] ?>" class="btn-small">View</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2 class="section-title">Latest Robots</h2>
<?php if (empty($latest)): ?>
    <p class="empty">No robots added yet.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($latest as $robot): ?>
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
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
