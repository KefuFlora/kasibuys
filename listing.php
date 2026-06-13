<?php
require_once '../config/db.php';
require_once '../config/security.php';
require_once '../config/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

include '../includes/header.php';
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['listing_id'])) {
    $pdo->prepare("UPDATE listings SET status = 'deleted' WHERE id = ?")
        ->execute([(int)$_POST['listing_id']]);
    header('Location: listings.php');
    exit;
}

$listings = $pdo->query("
    SELECT l.*, u.full_name, c.name as category_name
    FROM listings l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN categories c ON l.category_id = c.id
    WHERE l.status != 'deleted'
    ORDER BY l.created_at DESC
")->fetchAll();
?>

<div style="max-width:1200px;margin:40px auto;padding:0 20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
        <h2>Manage Listings (<?= count($listings) ?>)</h2>
        <a href="dashboard.php" class="btn-secondary">← Back to Admin</a>
    </div>

    <div style="background:white;border-radius:12px;box-shadow:var(--shadow);overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead style="background:var(--secondary);color:white;">
                <tr>
                    <th style="padding:15px;text-align:left;">Item</th>
                    <th style="padding:15px;text-align:left;">Seller</th>
                    <th style="padding:15px;text-align:left;">Category</th>
                    <th style="padding:15px;text-align:left;">Price</th>
                    <th style="padding:15px;text-align:left;">Status</th>
                    <th style="padding:15px;text-align:left;">Date</th>
                    <th style="padding:15px;text-align:left;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $l): ?>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:15px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="../<?= htmlspecialchars($l['image'] ?? 'images/no-image.png') ?>"
                                     style="width:45px;height:45px;object-fit:cover;border-radius:6px;">
                                <a href="../listing-single.php?id=<?= $l['id'] ?>" style="color:inherit;text-decoration:none;">
                                    <?= htmlspecialchars(substr($l['title'],0,35)) ?>...
                                </a>
                            </div>
                        </td>
                        <td style="padding:15px;"><?= htmlspecialchars($l['full_name']) ?></td>
                        <td style="padding:15px;color:var(--gray);"><?= htmlspecialchars($l['category_name'] ?? 'N/A') ?></td>
                        <td style="padding:15px;font-weight:700;color:var(--primary);">R <?= number_format($l['price'],2) ?></td>
                        <td style="padding:15px;">
                            <span style="padding:3px 10px;border-radius:10px;font-size:0.8rem;
                                background:<?= $l['status']==='active' ? '#d4edda' : '#f8d7da' ?>;
                                color:<?= $l['status']==='active' ? '#155724' : '#721c24' ?>;">
                                <?= ucfirst($l['status']) ?>
                            </span>
                        </td>
                        <td style="padding:15px;color:var(--gray);"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
                        <td style="padding:15px;">
                            <form method="POST" onsubmit="return confirm('Delete this listing?')">
                                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                                <button type="submit" class="btn-danger" style="padding:5px 12px;font-size:0.8rem;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>