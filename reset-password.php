<?php
include 'includes/header.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$token || !$user) {
    $error = 'This reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?")
            ->execute([$hashed, $user['id']]);
        $success = 'Password reset successfully! You can now <a href="login.php" style="color:var(--primary)">login</a>.';
    }
}
?>

<div class="form-container">
    <h2>Reset Password</h2>
    <p class="subtitle">Enter your new password below</p>

    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <?php if ($user && !$success): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Min 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat new password" required>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;padding:14px;">Reset Password</button>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>