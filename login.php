<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Already logged in? Send to the right place
if (is_logged_in()) {
    header('Location: ' . (is_admin() ? 'admin/index.php' : 'index.php'));
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);   // security: new session id after login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];

        header('Location: ' . ($user['role'] === 'admin' ? 'admin/index.php' : 'index.php'));
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Login';
include 'includes/header.php';
?>

<div class="form-box">
    <h2>Login</h2>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">Registration successful. Please login.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <p style="margin-top:14px;">New here? <a href="register.php">Create an account</a></p>
</div>

<?php include 'includes/footer.php'; ?>
