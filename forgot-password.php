<?php
require_once 'config/mail.php';
session_start();
require_once 'config/db.php';
include 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?")
            ->execute([$token, $expiry, $user['id']]);

        $reset_link = "http://kasibuys.atwebpages.comreset-password.php?token=$token";
        $email_body = "
        <p>Hi <strong>{$user['full_name']}</strong>,</p>
        <p>We received a request to reset your KasiBuys password.</p>
        <p>Click the button below to reset it. This link expires in <strong>1 hour</strong>.</p>
        <a href='$reset_link' class='btn'>Reset My Password</a>
        <p>If you didn't request this, you can safely ignore this email.</p>
        <p>The KasiBuys Team</p>
        ";
         sendEmail($user['email'], $user['full_name'], 'Reset Your KasiBuys Password', $email_body);
         $success = "Password reset link sent! Check your email.";
    } else {
        // Don't reveal if email exists or not
        $success = "If an account with that email exists, a reset link has been sent.";
    }
}
?>

<div class="form-container">
    <h2>Forgot Password</h2>
    <p class="subtitle">Enter your email and we'll send you a reset link</p>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="your@email.com" required>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;padding:14px;">Send Reset Link</button>
    </form>
    <p style="text-align:center;margin-top:20px;font-size:0.9rem;">
        <a href="login.php" style="color:var(--primary);">Back to Login</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>