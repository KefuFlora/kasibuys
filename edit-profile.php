<?php
require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF($_POST['csrf_token'] ?? '');

    $full_name = trim($_POST['full_name']);
    $location = trim($_POST['location']);
    $bio = trim($_POST['bio']);
    $photo = $user['profile_photo'];

    if (!empty($_FILES['profile_photo']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['profile_photo']['size'] <= 5000000) {
            $filename = 'user_' . $_SESSION['user_id'] . '.' . $ext;
            move_uploaded_file($_FILES['profile_photo']['tmp_name'], 'images/uploads/' . $filename);
            $photo = 'images/uploads/' . $filename;
        }
    }

    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $_SESSION['user_id']]);
        }
    }

    if (!$error) {
        $pdo->prepare("UPDATE users SET full_name=?, location=?, bio=?, profile_photo=? WHERE id=?")
            ->execute([$full_name, $location, $bio, $photo, $_SESSION['user_id']]);
        $_SESSION['full_name'] = $full_name;
        $_SESSION['profile_photo'] = $photo;
        $success = 'Profile updated successfully!';
        $user = array_merge($user, ['full_name'=>$full_name,'location'=>$location,'bio'=>$bio,'profile_photo'=>$photo]);
    }
}

include 'includes/header.php';
?>

<div class="form-container" style="max-width:600px;">
    <h2>Edit Profile</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

        <div style="text-align:center;margin-bottom:25px;">
            <img src="/<?= htmlspecialchars($user['profile_photo'] ?? 'images/default-avatar.png') ?>"
                 style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);margin-bottom:10px;">
            <div>
                <input type="file" name="profile_photo" accept="image/*">
            </div>
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Bio</label>
            <textarea name="bio" placeholder="Tell buyers and sellers about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
        </div>

        <hr style="margin:25px 0;border:none;border-top:1px solid var(--border);">
        <h3 style="margin-bottom:20px;">Change Password</h3>
        <div class="form-row">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Leave blank to keep current">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat new password">
            </div>
        </div>

        <button type="submit" class="btn-primary" style="width:100%;padding:14px;">Save Profile</button>
    </form>

    <div style="margin-top:40px;padding-top:25px;border-top:1px solid var(--border);text-align:center;">
        <p style="color:var(--gray);font-size:0.9rem;margin-bottom:10px;">Want to leave KasiBuys?</p>
        <a href="delete-account.php" style="color:var(--danger);font-size:0.9rem;font-weight:600;">
            <i class="fas fa-trash"></i> Delete My Account
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>