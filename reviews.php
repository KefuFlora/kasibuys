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
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Verify this order belongs to the user and is completed
$stmt = $pdo->prepare("
    SELECT o.*, l.title, u.full_name as seller_name, u.id as seller_id
    FROM orders o
    JOIN listings l ON o.listing_id = l.id
    JOIN users u ON o.seller_id = u.id
    WHERE o.id = ? AND o.buyer_id = ? AND o.status = 'completed'
");
$stmt->execute([$order_id, $uid]);
$order = $stmt->fetch();

if (!$order) { header('Location: orders.php'); exit; }

// Check already reviewed
$already = $pdo->prepare("SELECT id FROM reviews WHERE reviewer_id = ? AND order_id = ?");
$already->execute([$uid, $order_id]);
if ($already->fetch()) {
    header('Location: orders.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating.';
    } else {
        $pdo->prepare("INSERT INTO reviews (reviewer_id, reviewed_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)")
            ->execute([$uid, $order['seller_id'], $order_id, $rating, $comment]);

        // Notify seller
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")
            ->execute([
                $order['seller_id'],
                $_SESSION['full_name'] . ' left you a ' . $rating . '-star review!',
                'profile.php?id=' . $order['seller_id']
            ]);

        header('Location: orders.php?tab=purchases');
        exit;
    }
}
?>

<div class="form-container">
    <h2>Leave a Review</h2>
    <p class="subtitle">How was your experience with <?= htmlspecialchars($order['seller_name']) ?>?</p>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div style="background:var(--light);border-radius:8px;padding:15px;margin-bottom:20px;font-size:0.9rem;">
        <strong>Order:</strong> <?= htmlspecialchars($order['title']) ?>
    </div>

    <form method="POST">
        <div class="form-group">
            <label>Rating</label>
            <div style="display:flex;gap:10px;font-size:2rem;margin-bottom:10px;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <label style="cursor:pointer;">
                        <input type="radio" name="rating" value="<?= $i ?>" required style="display:none;">
                        <span class="star" data-value="<?= $i ?>">☆</span>
                    </label>
                <?php endfor; ?>
            </div>
        </div>
        <div class="form-group">
            <label>Comment</label>
            <textarea name="comment" placeholder="Share your experience — was the item as described? How was the seller to deal with?" required></textarea>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;padding:14px;">Submit Review</button>
    </form>
</div>

<script>
const stars = document.querySelectorAll('.star');
stars.forEach(star => {
    star.addEventListener('click', function() {
        const val = this.dataset.value;
        stars.forEach((s, i) => s.textContent = i < val ? '⭐' : '☆');
    });
    star.addEventListener('mouseover', function() {
        const val = this.dataset.value;
        stars.forEach((s, i) => s.textContent = i < val ? '⭐' : '☆');
    });
});
</script>

<?php include 'includes/footer.php'; ?>