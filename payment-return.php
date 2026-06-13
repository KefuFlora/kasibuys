<?php
include 'includes/header.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$status = $_GET['status'] ?? 'failed';
$bank = isset($_GET['bank']) ? htmlspecialchars($_GET['bank']) : 'Unknown';

if (!$order_id) { header('Location: orders.php'); exit; }

// Verify order belongs to this user
$stmt = $pdo->prepare("
    SELECT o.*, l.title, l.image, u.full_name as seller_name, u.email as seller_email
    FROM orders o
    JOIN listings l ON o.listing_id = l.id
    JOIN users u ON o.seller_id = u.id
    WHERE o.id = ? AND o.buyer_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) { header('Location: orders.php'); exit; }

if ($status === 'success') {
    // Update order status
    $pdo->prepare("UPDATE orders SET status = 'paid', payment-gateway.phpment_method = ? WHERE id = ?")
        ->execute([$bank, $order_id]);

    // Mark listing as sold
    $pdo->prepare("UPDATE listings SET status = 'sold' WHERE id = ?")
        ->execute([$order['listing_id']]);

    // Notify seller
    $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")
        ->execute([
            $order['seller_id'],
            $_SESSION['full_name'] . ' paid for: ' . $order['title'],
            'orders.php?tab=sales'
        ]);

    // Send emails
    require_once 'config/mail.php';

    $buyer_body = "
        <p>Hi <strong>{$_SESSION['full_name']}</strong>,</p>
        <p>Your payment-gateway.phpment was successful! 🎉</p>
        <p>
            <strong>Item:</strong> {$order['title']}<br>
            <strong>Amount:</strong> R " . number_format($order['amount'], 2) . "<br>
            <strong>Bank:</strong> $bank<br>
            <strong>Order ID:</strong> #$order_id
        </p>
        <p>The seller will be in touch shortly to arrange delivery.</p>
        <a href='http://kasibuys.atwebpages.comorders.php' class='btn'>View My Orders</a>
        <p>The KasiBuys Team</p>
    ";
    sendEmail($_SESSION['email'], $_SESSION['full_name'], 'payment-gateway.phpment Successful — KasiBuys', $buyer_body);

    $seller_body = "
        <p>Hi <strong>{$order['seller_name']}</strong>,</p>
        <p>Great news! payment-gateway.phpment has been received for your listing.</p>
        <p>
            <strong>Item:</strong> {$order['title']}<br>
            <strong>Amount:</strong> R " . number_format($order['amount'], 2) . "<br>
            <strong>Buyer:</strong> {$_SESSION['full_name']}
        </p>
        <p>Please log in to arrange delivery with the buyer.</p>
        <a href='http://kasibuys.atwebpages.comorders.php?tab=sales' class='btn'>View My Sales</a>
        <p>The KasiBuys Team</p>
    ";
    sendEmail($order['seller_email'], $order['seller_name'], 'payment-gateway.phpment Received — KasiBuys', $seller_body);

} elseif ($status === 'failed') {
    $pdo->prepare("UPDATE orders SET status = 'pending' WHERE id = ?")
        ->execute([$order_id]);
} elseif ($status === 'cancelled') {
    $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")
        ->execute([$order_id]);
}
?>

<div style="max-width:500px;margin:60px auto;padding:0 20px;text-align:center;">
    <div style="background:white;border-radius:12px;padding:50px 30px;box-shadow:var(--shadow);">

        <?php if ($status === 'success'): ?>
            <div style="font-size:5rem;margin-bottom:20px;">✅</div>
            <h2 style="color:#28a745;margin-bottom:10px;">payment-gateway.phpment Successful!</h2>
            <p style="color:var(--gray);margin-bottom:5px;">Your payment-gateway.phpment of <strong>R <?= number_format($order['amount'], 2) ?></strong> was received.</p>
            <p style="color:var(--gray);margin-bottom:30px;">A confirmation email has been sent to you and the seller.</p>
            <a href="orders.php" class="btn-primary" style="display:inline-block;padding:14px 30px;">View My Orders</a>

        <?php elseif ($status === 'failed'): ?>
            <div style="font-size:5rem;margin-bottom:20px;">❌</div>
            <h2 style="color:var(--danger);margin-bottom:10px;">payment-gateway.phpment Failed</h2>
            <p style="color:var(--gray);margin-bottom:30px;">Your payment-gateway.phpment could not be processed. Please try again.</p>
            <a href="pace-order.php.php?listing_id=<?= $order['listing_id'] ?>" class="btn-primary" style="display:inline-block;padding:14px 30px;">Try Again</a>

        <?php else: ?>
            <div style="font-size:5rem;margin-bottom:20px;">🚫</div>
            <h2 style="color:var(--gray);margin-bottom:10px;">payment-gateway.phpment Cancelled</h2>
            <p style="color:var(--gray);margin-bottom:30px;">You cancelled the payment-gateway.phpment. Your order has been cancelled.</p>
            <a href="listings.php" class="btn-primary" style="display:inline-block;padding:14px 30px;">Browse Listings</a>
        <?php endif; ?>

        <div style="margin-top:20px;">
            <a href="index.php" style="color:var(--gray);font-size:0.9rem;">← Back to Homepage</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>