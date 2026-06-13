<?php

require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF($_POST['csrf_token'] ?? '');

    $ip = $_SERVER['REMOTE_ADDR'];
    if (!rateLimit('login_' . $ip, 5, 300)) {
        $error = 'Too many login attempts. Please wait 5 minutes and try again.';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_photo'] = $user['profile_photo'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Welcome Back</h2>
    <p class="subtitle">Login to your KasiBuys account</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="your@email.com" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Your password" required>
        </div>
        <div style="text-align:right;margin-bottom:15px;">
            <a href="forgot-password.php" style="color:var(--primary);font-size:0.9rem;">Forgot password?</a>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;padding:14px;">Login</button>
    </form>
    <p style="text-align:center;margin-top:20px;font-size:0.9rem;">Don't have an account? <a href="register.php" style="color:var(--primary)">Register</a></p>
</div>

<?php include 'includes/footer.php'; ?>