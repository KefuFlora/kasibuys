<?php
require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['user_id'];
$listing_id = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;

if (!$listing_id) { header('Location: cart.php'); exit; }

$stmt = $pdo->prepare("
    SELECT l.*, u.full_name as seller_name, u.id as seller_id, u.location as seller_location, u.email as seller_email
    FROM listings l JOIN users u ON l.user_id = u.id
    WHERE l.id = ? AND l.status = 'active'
");
$stmt->execute([$listing_id]);
$listing = $stmt->fetch();

if (!$listing || $listing['seller_id'] == $uid) { header('Location: listings.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF($_POST['csrf_token'] ?? '');
    $payment_method = $_POST['payment_method'];

    $stmt = $pdo->prepare("
        INSERT INTO orders (buyer_id, seller_id, listing_id, amount, status, payment_method)
        VALUES (?, ?, ?, ?, 'pending', ?)
    ");
    $stmt->execute([$uid, $listing['seller_id'], $listing_id, $listing['price'], $payment_method]);
    $order_id = $pdo->lastInsertId();

    $item = urlencode($listing['title']);
    header("Location: payment-gateway.php?order_id=$order_id&amount={$listing['price']}&item=$item");
    exit;
}

include 'includes/header.php';
?>

<div style="max-width:800px;margin:40px auto;padding:0 20px;">
    <h2 style="margin-bottom:25px;">Checkout</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:25px;">
        <!-- PAYMENT FORM -->
        <div style="background:white;border-radius:12px;padding:30px;box-shadow:var(--shadow);">
            <h3 style="margin-bottom:20px;">Payment Details</h3>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="payfast">PayFast</option>
                        <option value="snapscan">SnapScan</option>
                        <option value="ozow">Ozow</option>
                    </select>
                </div>

                <div style="background:var(--light);border-radius:8px;padding:15px;margin-bottom:20px;font-size:0.9rem;color:var(--gray);">
                    <i class="fas fa-info-circle"></i>
                    After placing your order, the seller will contact you with payment instructions via messages.
                </div>

                <div class="form-group">
                    <label>Delivery Address</label>
                    <textarea name="address" placeholder="Enter your delivery address or write 'Collection' if collecting in person..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Additional Notes (Optional)</label>
                    <textarea name="notes" placeholder="Any special instructions for the seller..."></textarea>
                </div>

                <button type="submit" class="btn-primary" style="width:100%;padding:14px;font-size:1rem;">
                    <i class="fas fa-lock"></i> Place Order — R <?= number_format($listing['price'], 2) ?>
                </button>
            </form>
        </div>

        <!-- ORDER SUMMARY -->
        <div>
            <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--shadow);">
                <h3 style="margin-bottom:15px;">Order Summary</h3>
                <img src="<?= htmlspecialchars($listing['image'] ?? 'images/no-image.png') ?>"
                     style="width:100%;height:180px;object-fit:cover;border-radius:8px;margin-bottom:15px;">
                <h4><?= htmlspecialchars($listing['title']) ?></h4>
                <p style="color:var(--gray);font-size:0.85rem;margin:5px 0;">
                    Sold by: <?= htmlspecialchars($listing['seller_name']) ?>
                </p>
                <p style="color:var(--gray);font-size:0.85rem;">
                    <i class="fas fa-map-marker-alt"></i>
                    <?= htmlspecialchars($listing['seller_location']) ?>
                </p>
                <hr style="margin:15px 0;border:none;border-top:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.2rem;">
                    <span>Total</span>
                    <span style="color:var(--primary);">R <?= number_format($listing['price'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>