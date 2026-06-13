<?php
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: listings.php'); exit; }

$stmt = $pdo->prepare("
    SELECT l.*, u.full_name, u.profile_photo, u.location as user_location,
    u.id as seller_id, u.created_at as member_since, c.name as category_name
    FROM listings l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN categories c ON l.category_id = c.id
    WHERE l.id = ? AND l.status != 'deleted'
");
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) { header('Location: listings.php'); exit; }

// Seller rating
$rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg, COUNT(*) as total FROM reviews WHERE reviewed_id = ?");
$rating_stmt->execute([$listing['seller_id']]);
$rating = $rating_stmt->fetch();

// Seller's other listings
$other_stmt = $pdo->prepare("
    SELECT * FROM listings WHERE user_id = ? AND id != ? AND status = 'active' LIMIT 4
");
$other_stmt->execute([$listing['seller_id'], $id]);
$other_listings = $other_stmt->fetchAll();

// Check if already in cart
$in_cart = false;
if (isset($_SESSION['user_id'])) {
    $cart_check = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND listing_id = ?");
    $cart_check->execute([$_SESSION['user_id'], $id]);
    $in_cart = $cart_check->fetch() ? true : false;
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    if (!$in_cart) {
        $cart_stmt = $pdo->prepare("INSERT INTO cart (user_id, listing_id) VALUES (?, ?)");
        $cart_stmt->execute([$_SESSION['user_id'], $id]);
        $in_cart = true;
    }
}
?>

<div class="listing-detail">
    <!-- LEFT: IMAGE & DESCRIPTION -->
    <div>
        <div class="listing-images">
            <img src="<?= htmlspecialchars($listing['image'] ?? 'images/no-image.png') ?>"
                 alt="<?= htmlspecialchars($listing['title']) ?>">
        </div>

        <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--shadow);margin-top:20px;">
            <h3 style="margin-bottom:15px;">Description</h3>
            <p style="color:#555;line-height:1.8;"><?= nl2br(htmlspecialchars($listing['description'])) ?></p>

            <div style="margin-top:20px;display:flex;flex-wrap:wrap;gap:15px;">
                <div style="background:var(--light);padding:10px 20px;border-radius:8px;">
                    <small style="color:var(--gray);">Condition</small>
                    <div style="font-weight:600;"><?= ucfirst($listing['condition_type']) ?></div>
                </div>
                <div style="background:var(--light);padding:10px 20px;border-radius:8px;">
                    <small style="color:var(--gray);">Category</small>
                    <div style="font-weight:600;"><?= htmlspecialchars($listing['category_name'] ?? 'N/A') ?></div>
                </div>
                <div style="background:var(--light);padding:10px 20px;border-radius:8px;">
                    <small style="color:var(--gray);">Location</small>
                    <div style="font-weight:600;"><?= htmlspecialchars($listing['location'] ?? $listing['user_location']) ?></div>
                </div>
                <div style="background:var(--light);padding:10px 20px;border-radius:8px;">
                    <small style="color:var(--gray);">Posted</small>
                    <div style="font-weight:600;"><?= date('d M Y', strtotime($listing['created_at'])) ?></div>
                </div>
            </div>
        </div>

        <!-- OTHER LISTINGS BY SELLER -->
        <?php if (!empty($other_listings)): ?>
        <div style="margin-top:25px;">
            <h3 style="margin-bottom:15px;">More from this seller</h3>
            <div class="listings-grid">
                <?php foreach ($other_listings as $other): ?>
                    <a href="listing-single.php?id=<?= $other['id'] ?>" class="listing-card">
                        <img src="<?= htmlspecialchars($other['image'] ?? 'images/no-image.png') ?>" alt="">
                        <div class="listing-card-body">
                            <h3><?= htmlspecialchars(substr($other['title'], 0, 40)) ?></h3>
                            <div class="price">R <?= number_format($other['price'], 2) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: LISTING INFO & ACTIONS -->
    <div>
        <div class="listing-info">
            <h1><?= htmlspecialchars($listing['title']) ?></h1>
            <div class="price">R <?= number_format($listing['price'], 2) ?></div>

            <?php if ($listing['status'] === 'sold'): ?>
                <div class="alert alert-danger">This item has been sold.</div>
            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $listing['seller_id']): ?>
                <a href="edit-listing.php?id=<?= $listing['id'] ?>" class="btn-secondary" style="display:block;text-align:center;margin-bottom:10px;">Edit Listing</a>
                <form method="POST" action="delete-listing.php" class="delete-form">
                    <input type="hidden" name="id" value="<?= $listing['id'] ?>">
                    <button type="submit" class="btn-danger" style="width:100%;">Delete Listing</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <button type="submit" name="add_to_cart" class="btn-primary" style="width:100%;padding:14px;margin-bottom:10px;" <?= $in_cart ? 'disabled' : '' ?>>
                        <i class="fas fa-shopping-cart"></i>
                        <?= $in_cart ? 'Added to Cart' : 'Add to Cart' ?>
                    </button>
                </form>
                <a href="pace-order.php.php?listing_id=<?= $listing['id'] ?>" class="btn-secondary" style="display:block;text-align:center;padding:14px;margin-bottom:10px;">
                    Buy Now
                </a>
                <a href="conversation.php?user=<?= $listing['seller_id'] ?>&listing=<?= $listing['id'] ?>"
                   class="btn-secondary" style="display:block;text-align:center;padding:14px;">
                    <i class="fas fa-comment"></i> Message Seller
                </a>
            <?php endif; ?>
        </div>

        <!-- SELLER INFO -->
        <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--shadow);margin-top:20px;">
            <h3 style="margin-bottom:15px;">Seller Info</h3>
            <a href="profile.php?id=<?= $listing['seller_id'] ?>" style="display:flex;align-items:center;gap:15px;text-decoration:none;color:inherit;">
                <img src="<?= htmlspecialchars($listing['profile_photo'] ?? 'images/default-avatar.png') ?>"
                     style="width:55px;height:55px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);">
                <div>
                    <div style="font-weight:700;">
    <?= htmlspecialchars($listing['full_name']) ?>
    <?php if ($listing['is_verified'] ?? false): ?>
        <span style="background:#28a745;color:white;font-size:0.7rem;padding:2px 8px;border-radius:8px;margin-left:5px;">✅ Verified</span>
    <?php endif; ?>
</div>
                    <div style="color:var(--gray);font-size:0.85rem;">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($listing['user_location']) ?>
                    </div>
                    <div style="color:var(--gray);font-size:0.85rem;">
                        Member since <?= date('M Y', strtotime($listing['member_since'])) ?>
                    </div>
                </div>
            </a>
            <div style="margin-top:15px;padding-top:15px;border-top:1px solid var(--border);">
                <?php
                $avg = round($rating['avg'] ?? 0, 1);
                $stars = str_repeat('⭐', (int)$avg) . ($avg - (int)$avg >= 0.5 ? '⭐' : '');
                ?>
                <div style="font-size:1.1rem;"><?= $stars ?: '☆ No ratings yet' ?></div>
                <div style="color:var(--gray);font-size:0.85rem;"><?= $rating['total'] ?> review(s)</div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>