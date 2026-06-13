<?php
require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';

$uid = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF($_POST['csrf_token'] ?? '');
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    if (!password_verify($password, $user['password'])) {
        $error = 'Incorrect password. Account not deleted.';
    } else {
        // Temporarily disable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM reviews WHERE reviewer_id = ? OR reviewed_id = ?")->execute([$uid, $uid]);
        $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$uid, $uid]);
        $pdo->prepare("DELETE FROM disputes WHERE raised_by = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM orders WHERE buyer_id = ? OR seller_id = ?")->execute([$uid, $uid]);
        $pdo->prepare("UPDATE listings SET status = 'deleted' WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);

        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        session_destroy();
        header('Location: index.php');
        exit;
    }
    
}
?>

<div class="form-container">
    <h2 style="color:var(--danger);">Delete Account</h2>
    <p class="subtitle">This action is permanent and cannot be undone.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="background:#f8d7da;border-radius:8px;padding:15px;margin-bottom:25px;font-size:0.9rem;color:#721c24;">
        <strong>⚠️ Warning:</strong> Deleting your account will permanently remove:
        <ul style="margin-top:8px;padding-left:20px;">
            <li>Your profile and all personal data</li>
            <li>All your listings</li>
            <li>Your cart and notifications</li>
        </ul>
        Your order history will be kept for record purposes.
    </div>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="form-group">
            <label>Enter your password to confirm</label>
            <input type="password" name="password" placeholder="Your current password" required>
        </div>
        <button type="submit" class="btn-danger" style="width:100%;padding:14px;"
                onclick="return confirm('Are you absolutely sure? This cannot be undone!')">
            Permanently Delete My Account
        </button>
        <a href="edit-profile.php" style="display:block;text-align:center;margin-top:15px;color:var(--gray);font-size:0.9rem;">
            Cancel — Keep My Account
        </a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>