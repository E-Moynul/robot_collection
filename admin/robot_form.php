<?php
$base = '../';   // this page is inside admin/ folder

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$id     = (int)($_GET['id'] ?? 0);   // 0 = add new robot, >0 = edit
$isEdit = $id > 0;
$errors = [];

$fields = ['category_id', 'name', 'manufacturer', 'country', 'year_introduced',
           'description', 'specs', 'image', 'video_url', 'model_url'];
$data = array_fill_keys($fields, '');

// Edit mode: load the existing robot
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM robots WHERE id = ?');
    $stmt->execute([$id]);
    $robot = $stmt->fetch();

    if (!$robot) {
        header('Location: manage_robots.php');
        exit;
    }
    $data = array_merge($data, array_intersect_key($robot, $data));
}

// Categories for the dropdown
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }

    // ----- Validation -----
    if ($data['name'] === '') {
        $errors[] = 'Robot name is required.';
    }

    $validCategory = false;
    foreach ($categories as $c) {
        if ((int)$c['id'] === (int)$data['category_id']) {
            $validCategory = true;
        }
    }
    if (!$validCategory) {
        $errors[] = 'Please select a category.';
    }

    if ($data['year_introduced'] !== '') {
        $year = (int)$data['year_introduced'];
        if ($year < 1901 || $year > (int)date('Y') + 1) {
            $errors[] = 'Year must be between 1901 and ' . ((int)date('Y') + 1) . '.';
        }
    }

    if ($data['video_url'] !== ''
        && !preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)[A-Za-z0-9_-]{11}~', $data['video_url'])) {
        $errors[] = 'Video link must be a valid YouTube link.';
    }

    // ----- Image upload (optional; overrides the image path/link field) -----
    if (empty($errors)
        && isset($_FILES['image_file'])
        && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {

        $file = $_FILES['image_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed. Please try again.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image must be smaller than 2 MB.';
        } elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $errors[] = 'Image must be JPG, PNG, WEBP or GIF.';
        } elseif (@getimagesize($file['tmp_name']) === false) {
            $errors[] = 'The uploaded file is not a valid image.';
        } else {
            $dir = __DIR__ . '/../assets/images/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $newName = uniqid('robot_') . '.' . $ext;

            if (move_uploaded_file($file['tmp_name'], $dir . $newName)) {
                $data['image'] = 'assets/images/' . $newName;
            } else {
                $errors[] = 'Could not save the uploaded image.';
            }
        }
    }

    // ----- 3D model upload (optional; overrides the model path/link field) -----
    if (empty($errors)
        && isset($_FILES['model_file'])
        && $_FILES['model_file']['error'] !== UPLOAD_ERR_NO_FILE) {

        $file = $_FILES['model_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = '3D model upload failed. The file may be too large for the server.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = '3D model must be smaller than 10 MB.';
        } elseif ($ext !== 'glb') {
            $errors[] = '3D model must be a .glb file.';
        } else {
            // A real .glb file always starts with the 4 bytes "glTF"
            $fh    = fopen($file['tmp_name'], 'rb');
            $magic = fread($fh, 4);
            fclose($fh);

            if ($magic !== 'glTF') {
                $errors[] = 'The uploaded file is not a valid .glb model.';
            } else {
                $dir = __DIR__ . '/../assets/models/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $newName = uniqid('model_') . '.glb';

                if (move_uploaded_file($file['tmp_name'], $dir . $newName)) {
                    $data['model_url'] = 'assets/models/' . $newName;
                } else {
                    $errors[] = 'Could not save the uploaded 3D model.';
                }
            }
        }
    }

    // ----- Save -----
    if (empty($errors)) {
        $year = $data['year_introduced'] === '' ? null : (int)$data['year_introduced'];

        $values = [
            (int)$data['category_id'],
            $data['name'],
            $data['manufacturer'] ?: null,
            $data['country'] ?: null,
            $year,
            $data['description'] ?: null,
            $data['specs'] ?: null,
            $data['image'] ?: null,
            $data['video_url'] ?: null,
            $data['model_url'] ?: null,
        ];

        if ($isEdit) {
            $sql = 'UPDATE robots SET category_id=?, name=?, manufacturer=?, country=?, year_introduced=?,
                    description=?, specs=?, image=?, video_url=?, model_url=? WHERE id=?';
            $values[] = $id;
            $pdo->prepare($sql)->execute($values);
            header('Location: manage_robots.php?msg=updated');
        } else {
            $sql = 'INSERT INTO robots (category_id, name, manufacturer, country, year_introduced,
                    description, specs, image, video_url, model_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $pdo->prepare($sql)->execute($values);
            header('Location: manage_robots.php?msg=added');
        }
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Robot' : 'Add Robot';
include '../includes/header.php';
?>

<p style="margin-bottom:14px;"><a href="manage_robots.php">&larr; Back to robots</a></p>

<div class="form-box wide" style="margin-top:0;">
    <h2><?= $isEdit ? 'Edit Robot' : 'Add New Robot' ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($categories)): ?>
        <div class="alert alert-error">
            No categories found. <a href="manage_categories.php">Add a category first.</a>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data"
          action="robot_form.php<?= $isEdit ? '?id=' . $id : '' ?>">

        <div class="form-group">
            <label for="name">Robot Name *</label>
            <input type="text" id="name" name="name" value="<?= e($data['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="category_id">Category *</label>
            <select id="category_id" name="category_id" required>
                <option value="">-- Select category --</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                        <?= (int)$data['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="manufacturer">Manufacturer</label>
            <input type="text" id="manufacturer" name="manufacturer" value="<?= e($data['manufacturer']) ?>">
        </div>

        <div class="form-group">
            <label for="country">Country</label>
            <input type="text" id="country" name="country" value="<?= e($data['country']) ?>">
        </div>

        <div class="form-group">
            <label for="year_introduced">Year Introduced</label>
            <input type="number" id="year_introduced" name="year_introduced"
                   min="1901" max="<?= (int)date('Y') + 1 ?>" value="<?= e($data['year_introduced']) ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e($data['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="specs">Specifications</label>
            <textarea id="specs" name="specs"><?= e($data['specs']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="image_file">Upload Image (JPG, PNG, WEBP, GIF, max 2 MB)</label>
            <input type="file" id="image_file" name="image_file" accept="image/*">
        </div>

        <div class="form-group">
            <label for="image">Or Image Path / Link</label>
            <input type="text" id="image" name="image" value="<?= e($data['image']) ?>"
                   placeholder="assets/images/atlas.jpg or https://...">
            <small>If you upload a file above, it will replace this value.</small>
        </div>

        <div class="form-group">
            <label for="video_url">YouTube Video Link</label>
            <input type="text" id="video_url" name="video_url" value="<?= e($data['video_url']) ?>"
                   placeholder="https://www.youtube.com/watch?v=...">
        </div>

        <div class="form-group">
            <label for="model_file">Upload 3D Model (.glb, max 10 MB)</label>
            <input type="file" id="model_file" name="model_file" accept=".glb">
        </div>

        <div class="form-group">
            <label for="model_url">Or 3D Model Path / Link (.glb)</label>
            <input type="text" id="model_url" name="model_url" value="<?= e($data['model_url']) ?>"
                   placeholder="assets/models/atlas.glb">
            <small>Leave empty if no 3D model is available. The site will show "3D model unavailable".</small>
        </div>

        <button type="submit" class="btn"><?= $isEdit ? 'Update Robot' : 'Add Robot' ?></button>
        <a href="manage_robots.php" class="btn btn-outline">Cancel</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
