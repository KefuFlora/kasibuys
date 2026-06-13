<?php
require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { header('Location: index.php'); exit; }


include 'includes/header.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { header('Location: index.php'); exit; }

// Listings
$listings_stmt = $pdo->prepare("
    SELECT * FROM listings WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC
");
$listings_stmt->execute([$id]);
$listings = $listings_stmt->fetchAll();

// Reviews
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.profile_photo FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.reviewed_id = ? ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$id]);
$reviews = $reviews_stmt->fetchAll();

// Average rating
$avg_stmt = $pdo->prepare("SELECT AVG(rating) as avg, COUNT(*) as total FROM reviews WHERE reviewed_id = ?");
$avg_stmt->execute([$id]);
$avg_rating = $avg_stmt->fetch();
?>

<div style="max-width:1100px;margin:40px auto;padding:0 20px;">

    <!-- PROFILE HEADER -->
    <div style="background:white;border-radius:12px;padding:30px;box-shadow:var(--shadow);display:flex;gap:30px;align-items:center;margin-bottom:30px;">
        <img src="<?= htmlspecialchars($user['profile_photo'] ?? 'images/default-avatar.png') ?>"
             style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);">
        <div style="flex:1;">
            <h2>
    <?= htmlspecialchars($user['full_name']) ?>
    <?php if ($user['is_verified']): ?>
        <span style="background:#28a745;color:white;font-size:0.75rem;padding:3px 10px;border-radius:10px;vertical-align:middle;margin-left:8px;">
            ✅ Verified
        </span>
    <?php endif; ?>
</h2>
            <p style="color:var(--gray);margin:5px 0;">
                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user['location'] ?? 'Location not set') ?>
                &bull; Member since <?= date('M Y', strtotime($user['created_at'])) ?>
            </p>
            <?php if ($user['bio']): ?>
                <p style="margin-top:10px;"><?= htmlspecialchars($user['bio']) ?></p>
            <?php endif; ?>
            <div style="margin-top:10px;">
                <?php
                $avg = round($avg_rating['avg'] ?? 0, 1);
                echo str_repeat('⭐', (int)$avg);
                echo " <strong>$avg</strong> ({$avg_rating['total']} reviews)";
                ?>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $id): ?>
                <a href="conversation.php?user=<?= $id ?>" class="btn-primary">
                    <i class="fas fa-comment"></i> Message
                </a>
            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id): ?>
                <a href="edit-profile.php" class="btn-secondary">Edit Profile</a>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 350px;gap:30px;">
        <!-- LISTINGS -->
        <div>
            <h3 style="margin-bottom:20px;">Listings by <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?> (<?= count($listings) ?>)</h3>
            <?php if (empty($listings)): ?>
                <p style="color:var(--gray);">No active listings.</p>
            <?php else: ?>
                <div class="listings-grid">
                    <?php foreach ($listings as $listing): ?>
                        <a href="listing-single.php?id=<?= $listing['id'] ?>" class="listing-card">
                            <img src="<?= htmlspecialchars($listing['image'] ?? 'images/no-image.png') ?>" alt="">
                            <div class="listing-card-body">
                                <h3><?= htmlspecialchars(substr($listing['title'], 0, 40)) ?></h3>
                                <div class="price">R <?= number_format($listing['price'], 2) ?></div>
                                <span class="condition"><?= ucfirst($listing['condition_type']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- REVIEWS -->
        <div>
            <h3 style="margin-bottom:20px;">Reviews</h3>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $id): ?>
    <div style="background:white;border-radius:12px;padding:20px;box-shadow:var(--shadow);margin-bottom:20px;">
        <h4 style="margin-bottom:15px;">Rate this Seller</h4>
        <?php
        // Check if user has an order with this seller
        $can_review = $pdo->prepare("
            SELECT o.id FROM orders o
            WHERE o.buyer_id = ? AND o.seller_id = ? AND o.status = 'completed'
            AND o.id NOT IN (SELECT order_id FROM reviews WHERE reviewer_id = ?)
            LIMIT 1
        ");
        $can_review->execute([$_SESSION['user_id'], $id, $_SESSION['user_id']]);
        $eligible_order = $can_review->fetch();
        ?>
        <?php if ($eligible_order): ?>
            <a href="reviews.php?order_id=<?= $eligible_order['id'] ?>" class="btn-primary">
                ⭐ Leave a Review
            </a>
        <?php else: ?>
            <p style="color:var(--gray);font-size:0.9rem;">
                You can leave a review after completing a purchase from this seller.
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>
            <?php if (empty($reviews)): ?>
                <p style="color:var(--gray);">No reviews yet.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div style="background:white;border-radius:12px;padding:20px;box-shadow:var(--shadow);margin-bottom:15px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                            <img src="<?= htmlspecialchars($review['profile_photo'] ?? 'images/default-avatar.png') ?>"
                                 style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                            <div>
                                <div style="font-weight:600;"><?= htmlspecialchars($review['full_name']) ?></div>
                                <div style="color:var(--gray);font-size:0.8rem;"><?= date('d M Y', strtotime($review['created_at'])) ?></div>
                            </div>
                            <div style="margin-left:auto;"><?= str_repeat('⭐', $review['rating']) ?></div>
                        </div>
                        <p style="color:#555;font-size:0.9rem;"><?= htmlspecialchars($review['comment']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>