<?php

require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';
require_once 'config/mail.php';

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF($_POST['csrf_token'] ?? '');

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $role = $_POST['role'];
    $location = trim($_POST['location']);

    if (empty($full_name) || empty($email) || empty($password) || empty($location)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, location, verification_token) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $hashed, $role, $location, $token]);

            $email_body = "
                <p>Hi <strong>$full_name</strong>,</p>
                <p>Welcome to KasiBuys! Your account has been created successfully.</p>
                <p>You can now log in and start buying and selling on South Africa's local marketplace.</p>
                <a href='http://kasibuys.atwebpages.com/login.php' class='btn'>Login to KasiBuys</a>
                <p>Happy trading!<br>The KasiBuys Team</p>
            ";
            sendEmail($email, $full_name, 'Welcome to KasiBuys!', $email_body);
            $success = 'Account created successfully! A welcome email has been sent.';
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Join <span style="color:var(--primary)">KasiBuys</span></h2>
    <p class="subtitle">Create your free account today</p>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="Your full name" required>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="your@email.com" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat password" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>I want to</label>
                <select name="role">
                    <option value="both">Buy & Sell</option>
                    <option value="buyer">Buy Only</option>
                    <option value="seller">Sell Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Location (City)</label>
                <input type="text" name="location" placeholder="e.g. Johannesburg" required>
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;padding:14px;">Create Account</button>
    </form>
    <p style="text-align:center;margin-top:20px;font-size:0.9rem;">Already have an account? <a href="login.php" style="color:var(--primary)">Login</a></p>
</div>

<?php include 'includes/footer.php'; ?>