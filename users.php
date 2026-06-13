<?php
require_once '../config/db.php';
require_once '../config/security.php';
require_once '../config/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

include '../includes/header.php';
// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$user_id]);
    } elseif ($action === 'make_admin') {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$user_id]);
    } elseif ($action === 'make_buyer') {
        $pdo->prepare("UPDATE users SET role = 'buyer' WHERE id = ?")->execute([$user_id]);
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query("
    SELECT u.*, COUNT(l.id) as listing_count
    FROM users u
    LEFT JOIN listings l ON u.id = l.user_id AND l.status != 'deleted'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div style="max-width:1200px;margin:40px auto;padding:0 20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
        <h2>Manage Users (<?= count($users) ?>)</h2>
        <a href="dashboard.php" class="btn-secondary">← Back to Admin</a>
    </div>

    <div style="background:white;border-radius:12px;box-shadow:var(--shadow);overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead style="background:var(--secondary);color:white;">
                <tr>
                    <th style="padding:15px;text-align:left;">User</th>
                    <th style="padding:15px;text-align:left;">Email</th>
                    <th style="padding:15px;text-align:left;">Role</th>
                    <th style="padding:15px;text-align:left;">Listings</th>
                    <th style="padding:15px;text-align:left;">Joined</th>
                    <th style="padding:15px;text-align:left;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:15px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="../<?= htmlspecialchars($u['profile_photo'] ?? 'images/default-avatar.png') ?>"
                                     style="width:38px;height:38px;border-radius:50%;object-fit:cover;">
                                <?= htmlspecialchars($u['full_name']) ?>
                            </div>
                        </td>
                        <td style="padding:15px;color:var(--gray);"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding:15px;">
                            <span style="padding:3px 10px;border-radius:10px;font-size:0.8rem;
                                background:<?= $u['role']==='admin' ? '#d4edda' : 'var(--light)' ?>;
                                color:<?= $u['role']==='admin' ? '#155724' : '#333' ?>;">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td style="padding:15px;"><?= $u['listing_count'] ?></td>
                        <td style="padding:15px;color:var(--gray);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td style="padding:15px;">
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <?php if ($u['role'] !== 'admin'): ?>
                                        <button name="action" value="make_admin" class="btn-primary" style="padding:5px 10px;font-size:0.8rem;margin-right:5px;">
                                            Make Admin
                                        </button>
                                        <button name="action" value="delete" class="btn-danger" style="padding:5px 10px;font-size:0.8rem;"
                                                onclick="return confirm('Delete this user?')">
                                            Delete
                                        </button>
                                    <?php else: ?>
                                        <button name="action" value="make_buyer" class="btn-secondary" style="padding:5px 10px;font-size:0.8rem;">
                                            Remove Admin
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--gray);font-size:0.85rem;">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>