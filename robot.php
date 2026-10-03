<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Convert any YouTube link (watch / youtu.be / embed) into an embed link
function youtube_embed(?string $url): ?string
{
    if (!$url) {
        return null;
    }
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    return null;
}

$id = (int)($_GET['id'] ?? 0);

// Fetch the robot with its category name
$stmt = $pdo->prepare(
    'SELECT r.*, c.name AS category_name
     FROM robots r
     JOIN categories c ON c.id = r.category_id
     WHERE r.id = ?'
);
$stmt->execute([$id]);
$robot = $stmt->fetch();

// Robot not found
if (!$robot) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    include 'includes/header.php';
    echo '<p class="empty">Robot not found. <a href="robots.php">Back to all robots</a></p>';
    include 'includes/footer.php';
    exit;
}

// Bookmark / un-bookmark (form submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_bookmark'])) {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }

    $check = $pdo->prepare('SELECT id FROM bookmarks WHERE user_id = ? AND robot_id = ?');
    $check->execute([$_SESSION['user_id'], $id]);

    if ($check->fetch()) {
        $pdo->prepare('DELETE FROM bookmarks WHERE user_id = ? AND robot_id = ?')
            ->execute([$_SESSION['user_id'], $id]);
    } else {
        $pdo->prepare('INSERT INTO bookmarks (user_id, robot_id) VALUES (?, ?)')
            ->execute([$_SESSION['user_id'], $id]);
    }

    header('Location: robot.php?id=' . $id);
    exit;
}

// Is it already bookmarked by this user?
$isBookmarked = false;
if (is_logged_in()) {
    $check = $pdo->prepare('SELECT id FROM bookmarks WHERE user_id = ? AND robot_id = ?');
    $check->execute([$_SESSION['user_id'], $id]);
    $isBookmarked = (bool)$check->fetch();
}

$embedUrl = youtube_embed($robot['video_url']);

$placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='400' height='300'><rect width='100%' height='100%' fill='%23e5e7eb'/><text x='50%' y='50%' fill='%236b7280' font-size='22' text-anchor='middle' dy='.3em'>No Image</text></svg>";

// Load the 3D viewer script only when this robot has a model
$extraHead = '';
if (!empty($robot['model_url'])) {
    $extraHead = '<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.5.0/model-viewer.min.js"></script>';
}

$pageTitle = $robot['name'];
include 'includes/header.php';
?>

<p style="margin-bottom:14px;"><a href="robots.php">&larr; Back to all robots</a></p>

<section class="detail">
    <div>
        <img class="main-img"
             src="<?= e($robot['image'] ?: $placeholder) ?>"
             alt="<?= e($robot['name']) ?>"
             onerror="this.onerror=null;this.src=this.dataset.fallback;"
             data-fallback="<?= e($placeholder) ?>">
    </div>

    <div>
        <span class="badge"><?= e($robot['category_name']) ?></span>
        <h1><?= e($robot['name']) ?></h1>
        <p class="meta">
            <?= e($robot['manufacturer']) ?>
            <?= $robot['country'] ? ' · ' . e($robot['country']) : '' ?>
            <?= $robot['year_introduced'] ? ' · ' . e($robot['year_introduced']) : '' ?>
        </p>

        <h3>Description</h3>
        <p style="margin-bottom:14px;"><?= nl2br(e($robot['description'])) ?></p>

        <h3>Specifications</h3>
        <p style="margin-bottom:18px;"><?= $robot['specs'] ? nl2br(e($robot['specs'])) : 'Not available.' ?></p>

        <?php if (is_logged_in()): ?>
            <form method="post" action="robot.php?id=<?= (int)$robot['id'] ?>">
                <button type="submit" name="toggle_bookmark" class="btn <?= $isBookmarked ? 'btn-outline' : '' ?>">
                    <?= $isBookmarked ? '★ Remove Bookmark' : '☆ Add Bookmark' ?>
                </button>
            </form>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline">Login to bookmark</a>
        <?php endif; ?>
    </div>
</section>

<h2 class="section-title">Video</h2>
<?php if ($embedUrl): ?>
    <div class="video-wrap">
        <iframe src="<?= e($embedUrl) ?>" title="<?= e($robot['name']) ?> video"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
    </div>
<?php else: ?>
    <p class="empty">No video available for this robot.</p>
<?php endif; ?>

<h2 class="section-title">3D Model</h2>
<div class="model-box">
    <?php if (!empty($robot['model_url'])): ?>
        <model-viewer src="<?= e($robot['model_url']) ?>"
                      alt="3D model of <?= e($robot['name']) ?>"
                      camera-controls auto-rotate shadow-intensity="1"></model-viewer>
    <?php else: ?>
        <div class="unavailable">3D model unavailable</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
