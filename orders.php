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
$tab = $_GET['tab'] ?? 'purchases';

// Purchases
$purchases = $pdo->prepare("
    SELECT o.*, l.title, l.image, u.full_name as seller_name, u.id as seller_id
    FROM orders o
    JOIN listings l ON o.listing_id = l.id
    JOIN users u ON o.seller_id = u.id
    WHERE o.buyer_id = ?
    ORDER BY o.created_at DESC
");
$purchases->execute([$uid]);
$purchases = $purchases->fetchAll();

// Sales
$sales = $pdo->prepare("
    SELECT o.*, l.title, l.image, u.full_name as buyer_name, u.id as buyer_id
    FROM orders o
    JOIN listings l ON o.listing_id = l.id
    JOIN users u ON o.buyer_id = u.id
    WHERE o.seller_id = ?
    ORDER BY o.created_at DESC
");
$sales->execute([$uid]);
$sales = $sales->fetchAll();

// Handle status update (seller)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    $allowed_statuses = ['shipped', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?")
            ->execute([$new_status, $order_id, $uid]);
    }
    header('Location: orders.php?tab=sales');
    exit;
}
?>

<div style="max-width:1000px;margin:40px auto;padding:0 20px;">
    <h2 style="margin-bottom:25px;">My Orders</h2>

    <!-- TABS -->
    <div style="display:flex;gap:10px;margin-bottom:25px;">
        <a href="orders.php?tab=purchases"
           style="padding:10px 25px;border-radius:25px;text-decoration:none;font-weight:600;
           background:<?= $tab==='purchases' ? 'var(--primary)' : 'white' ?>;
           color:<?= $tab==='purchases' ? 'white' : '#333' ?>;
           box-shadow:var(--shadow);">
           My Purchases
        </a>
        <a href="orders.php?tab=sales"
           style="padding:10px 25px;border-radius:25px;text-decoration:none;font-weight:600;
           background:<?= $tab==='sales' ? 'var(--primary)' : 'white' ?>;
           color:<?= $tab==='sales' ? 'white' : '#333' ?>;
           box-shadow:var(--shadow);">
           My Sales
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">🎉 Order placed successfully! The seller will contact you shortly.</div>
    <?php endif; ?>

    <?php
    $orders = $tab === 'purchases' ? $purchases : $sales;
    if (empty($orders)):
    ?>
        <div style="background:white;border-radius:12px;padding:60px;text-align:center;box-shadow:var(--shadow);">
            <div style="font-size:3rem;">📦</div>
            <h3 style="margin:15px 0 10px;">No <?= $tab ?> yet</h3>
            <p style="color:var(--gray);">
                <?= $tab === 'purchases' ? 'Browse listings and make your first purchase!' : 'Create a listing to start selling!' ?>
            </p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div style="background:white;border-radius:12px;padding:20px;box-shadow:var(--shadow);margin-bottom:15px;display:flex;gap:15px;align-items:center;">
                <img src="<?= htmlspecialchars($order['image'] ?? 'images/no-image.png') ?>"
                     style="width:75px;height:75px;object-fit:cover;border-radius:8px;">
                <div style="flex:1;">
                    <h3 style="font-size:1rem;margin-bottom:5px;"><?= htmlspecialchars($order['title']) ?></h3>
                    <div style="color:var(--gray);font-size:0.85rem;">
                        <?= $tab === 'purchases' ? 'Seller: ' . htmlspecialchars($order['seller_name']) : 'Buyer: ' . htmlspecialchars($order['buyer_name']) ?>
                        &bull; <?= date('d M Y', strtotime($order['created_at'])) ?>
                    </div>
                    <div style="margin-top:8px;">
                        <?php
                        $status_colors = [
                            'pending' => ['#fff3cd','#856404'],
                            'paid' => ['#d1ecf1','#0c5460'],
                            'shipped' => ['#d4edda','#155724'],
                            'completed' => ['#d4edda','#155724'],
                            'cancelled' => ['#f8d7da','#721c24'],
                            'disputed' => ['#f8d7da','#721c24'],
                        ];
                        $colors = $status_colors[$order['status']] ?? ['#e9ecef','#495057'];
                        ?>
                        <span style="padding:4px 12px;border-radius:10px;font-size:0.8rem;background:<?= $colors[0] ?>;color:<?= $colors[1] ?>;">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:1.2rem;font-weight:700;color:var(--primary);margin-bottom:10px;">
                        R <?= number_format($order['amount'], 2) ?>
                    </div>
                    <?php if ($tab === 'sales' && in_array($order['status'], ['paid','shipped'])): ?>
                        <form method="POST" style="display:flex;gap:5px;flex-direction:column;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="new_status" style="padding:6px;border:1px solid var(--border);border-radius:6px;font-size:0.85rem;">
                                <option value="shipped">Mark Shipped</option>
                                <option value="completed">Mark Completed</option>
                                <option value="cancelled">Cancel</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-primary" style="padding:6px 12px;font-size:0.85rem;">Update</button>
                        </form>
                    <?php elseif ($tab === 'purchases' && $order['status'] === 'completed'): ?>
                        <a href="reviews.php?order_id=<?= $order['id'] ?>" class="btn-secondary" style="font-size:0.85rem;padding:6px 12px;">
                            Leave Review
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>