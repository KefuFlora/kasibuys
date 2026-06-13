<?php
require_once '../config/db.php';
require_once '../config/security.php';
require_once '../config/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

include '../includes/header.php';

// Stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_listings = $pdo->query("SELECT COUNT(*) FROM listings WHERE status != 'deleted'")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE status = 'completed'")->fetchColumn();
$open_disputes = $pdo->query("SELECT COUNT(*) FROM disputes WHERE status = 'open'")->fetchColumn();

// Recent users
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent orders
$recent_orders = $pdo->query("
    SELECT o.*, l.title, b.full_name as buyer_name, s.full_name as seller_name
    FROM orders o
    JOIN listings l ON o.listing_id = l.id
    JOIN users b ON o.buyer_id = b.id
    JOIN users s ON o.seller_id = s.id
    ORDER BY o.created_at DESC LIMIT 5
")->fetchAll();
?>

<div style="max-width:1200px;margin:40px auto;padding:0 20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
        <h2>⚙️ Admin Panel</h2>
        <div style="display:flex;gap:10px;">
            <a href="/admin/users.php" class="btn-secondary">Manage Users</a>
<a href="/admin/listings.php" class="btn-secondary">Manage Listings</a>
<a href="/admin/disputes.php" class="btn-secondary">Disputes <?= $open_disputes > 0 ? "($open_disputes)" : '' ?></a>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:30px;">
        <div class="stat-card">
            <div class="number"><?= $total_users ?></div>
            <div class="label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $total_listings ?></div>
            <div class="label">Active Listings</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $total_orders ?></div>
            <div class="label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="number">R <?= number_format($total_revenue ?? 0, 0) ?></div>
            <div class="label">Revenue</div>
        </div>
        <div class="stat-card">
            <div class="number" style="color:<?= $open_disputes > 0 ? 'var(--danger)' : 'var(--primary)' ?>">
                <?= $open_disputes ?>
            </div>
            <div class="label">Open Disputes</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;">
        <!-- RECENT USERS -->
        <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--shadow);">
            <div style="display:flex;justify-content:space-between;margin-bottom:20px;">
                <h3>Recent Users</h3>
                <a href="users.php" style="color:var(--primary);font-size:0.9rem;">View All</a>
            </div>
            <?php foreach ($recent_users as $u): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);">
                    <img src="../<?= htmlspecialchars($u['profile_photo'] ?? 'images/default-avatar.png') ?>"
                         style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($u['full_name']) ?></div>
                        <div style="color:var(--gray);font-size:0.8rem;"><?= htmlspecialchars($u['email']) ?></div>
                    </div>
                    <span style="font-size:0.8rem;color:var(--gray);"><?= date('d M', strtotime($u['created_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- RECENT ORDERS -->
        <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--shadow);">
            <div style="display:flex;justify-content:space-between;margin-bottom:20px;">
                <h3>Recent Orders</h3>
            </div>
            <?php foreach ($recent_orders as $o): ?>
                <div style="padding:10px 0;border-bottom:1px solid var(--border);">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="font-size:0.9rem;font-weight:600;"><?= htmlspecialchars(substr($o['title'],0,30)) ?>...</span>
                        <span style="font-weight:700;color:var(--primary);">R <?= number_format($o['amount'],2) ?></span>
                    </div>
                    <div style="color:var(--gray);font-size:0.8rem;margin-top:3px;">
                        <?= htmlspecialchars($o['buyer_name']) ?> → <?= htmlspecialchars($o['seller_name']) ?>
                        &bull; <?= ucfirst($o['status']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>