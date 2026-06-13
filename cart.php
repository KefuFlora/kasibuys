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

// Remove item
if (isset($_GET['remove'])) {
    $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND listing_id = ?")
        ->execute([$uid, (int)$_GET['remove']]);
    header('Location: cart.php');
    exit;
}

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, l.title, l.price, l.image, l.status, l.user_id as seller_id,
    u.full_name as seller_name
    FROM cart c
    JOIN listings l ON c.listing_id = l.id
    JOIN users u ON l.user_id = u.id
    WHERE c.user_id = ?
");
$stmt->execute([$uid]);
$cart_items = $stmt->fetchAll();

$total = array_sum(array_column($cart_items, 'price'));
?>

<div style="max-width:900px;margin:40px auto;padding:0 20px;">
    <h2 style="margin-bottom:25px;">🛒 My Cart</h2>

    <?php if (empty($cart_items)): ?>
        <div style="background:white;border-radius:12px;padding:60px;text-align:center;box-shadow:var(--shadow);">
            <div style="font-size:3rem;">🛒</div>
            <h3 style="margin:15px 0 10px;">Your cart is empty</h3>
            <p style="color:var(--gray);margin-bottom:20px;">Browse listings and add items you like!</p>
            <a href="listings.php" class="btn-primary">Browse Listings</a>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:1fr 300px;gap:25px;">
            <!-- CART ITEMS -->
            <div>
                <?php foreach ($cart_items as $item): ?>
                    <div style="background:white;border-radius:12px;padding:20px;box-shadow:var(--shadow);margin-bottom:15px;display:flex;gap:15px;align-items:center;">
                        <img src="<?= htmlspecialchars($item['image'] ?? 'images/no-image.png') ?>"
                             style="width:80px;height:80px;object-fit:cover;border-radius:8px;">
                        <div style="flex:1;">
                            <h3 style="font-size:1rem;margin-bottom:5px;">
                                <a href="listing-single.php?id=<?= $item['listing_id'] ?>" style="color:inherit;text-decoration:none;">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </h3>
                            <div style="color:var(--gray);font-size:0.85rem;">
                                Seller: <?= htmlspecialchars($item['seller_name']) ?>
                            </div>
                            <?php if ($item['status'] === 'sold'): ?>
                                <span style="color:var(--danger);font-size:0.85rem;">⚠️ This item has been sold</span>
                            <?php endif; ?>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:1.2rem;font-weight:700;color:var(--primary);">
                                R <?= number_format($item['price'], 2) ?>
                            </div>
                            <a href="cart.php?remove=<?= $item['listing_id'] ?>"
                               style="color:var(--danger);font-size:0.85rem;text-decoration:none;">
                                <i class="fas fa-trash"></i> Remove
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ORDER SUMMARY -->
            <div>
                <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--shadow);position:sticky;top:80px;">
                    <h3 style="margin-bottom:20px;">Order Summary</h3>
                    <?php foreach ($cart_items as $item): ?>
                        <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:0.9rem;">
                            <span><?= htmlspecialchars(substr($item['title'], 0, 25)) ?>...</span>
                            <span>R <?= number_format($item['price'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <hr style="margin:15px 0;border:none;border-top:1px solid var(--border);">
                    <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;margin-bottom:20px;">
                        <span>Total</span>
                        <span style="color:var(--primary);">R <?= number_format($total, 2) ?></span>
                    </div>
                    <?php
                    $available = array_filter($cart_items, fn($i) => $i['status'] === 'active');
                    if (count($available) > 0):
                        $first = reset($available);
                    ?>
                        <a href="pace-order.php.php?listing_id=<?= $first['listing_id'] ?>"
                           class="btn-primary" style="display:block;text-align:center;padding:14px;">
                            Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <p style="color:var(--danger);font-size:0.9rem;text-align:center;">
                            All items in your cart have been sold.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>