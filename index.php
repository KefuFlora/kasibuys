<?php include 'includes/header.php'; ?>
<?php
if (!defined('LANGUAGES_LOADED')) {
    require_once 'config/languages.php';
}
?>

<?php
// Fetch categories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Fetch latest listings
$listings = $pdo->query("
    SELECT l.*, u.full_name, u.location as user_location, c.name as category_name
    FROM listings l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN categories c ON l.category_id = c.id
    WHERE l.status = 'active'
    ORDER BY l.created_at DESC
    LIMIT 12
")->fetchAll();
?>

<!-- HERO -->
<section class="hero">
 <h1>
    <?php
    echo str_replace(
        'KasiBuys',
        '<span style="color:white;">Kasi</span><span style="color:var(--primary);">Buys</span>',
        $t['welcome']
    );
    ?>
</h1>
    <p><?= $t['tagline'] ?></p>
    <div class="hero-buttons">
        <a href="listings.php" class="btn-primary"><?= $t['browse'] ?></a>
        <a href="create-listing.php" class="btn-secondary" style="color:white;border-color:white;"><?= $t['start_selling'] ?></a>
    </div>
</section>

<!-- CATEGORIES -->
<section class="categories-section">
   <h2 class="section-title"><?= $t['browse_category'] ?></h2>
    <div class="categories-grid">
        <?php foreach ($categories as $cat): ?>
            <a href="listings.php?category=<?= $cat['id'] ?>" class="category-card">
                <div class="icon"><?= htmlspecialchars_decode(htmlspecialchars($cat['icon'], ENT_SUBSTITUTE, 'UTF-8')) ?></div>
                <span><?= htmlspecialchars($cat['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- LATEST LISTINGS -->
<section class="listings-section">
    <h2 class="section-title"><?= $t['latest_listings'] ?></h2>
    <?php if (empty($listings)): ?>
        <p style="color:var(--gray)">No listings yet. <a href="create-listing.php" style="color:var(--primary)">Be the first to sell!</a></p>
    <?php else: ?>
        <div class="listings-grid">
            <?php foreach ($listings as $listing): ?>
                <a href="listing-single.php?id=<?= $listing['id'] ?>" class="listing-card">
                    <img src="<?= $listing['image'] ? htmlspecialchars($listing['image']) : 'images/no-image.png' ?>" alt="<?= htmlspecialchars($listing['title']) ?>">
                    <div class="listing-card-body">
                        <h3><?= htmlspecialchars($listing['title']) ?></h3>
                        <div class="price">R <?= number_format($listing['price'], 2) ?></div>
                        <span class="condition"><?= ucfirst($listing['condition_type']) ?></span>
                        <div class="meta">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($listing['location'] ?? $listing['user_location']) ?>
                            &bull; <?= $listing['category_name'] ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- WHY KasiBuys -->
<section style="background:white;padding:60px 20px;margin-top:40px;">
    <div style="max-width:1200px;margin:0 auto;text-align:center;">
        <h2 class="section-title" style="text-align:center;"><?= $t['why_title'] ?></h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:30px;margin-top:30px;">
            <div style="padding:30px;border-radius:12px;background:var(--light);">
                <div style="font-size:2.5rem;">🛡️</div>
                <h3 style="margin:15px 0 10px;"><?= $t['safe'] ?></h3>
<p style="color:var(--gray);font-size:0.9rem;"><?= $t['safe_desc'] ?></p>
            </div>
            <div style="padding:30px;border-radius:12px;background:var(--light);">
                <div style="font-size:2.5rem;">💸</div>
            <h3 style="margin:15px 0 10px;"><?= $t['free'] ?></h3>
<p style="color:var(--gray);font-size:0.9rem;"><?= $t['free_desc'] ?></p>
            </div>
            <div style="padding:30px;border-radius:12px;background:var(--light);">
                <div style="font-size:2.5rem;">📍</div>
 <h3 style="margin:15px 0 10px;"><?= $t['local'] ?></h3>
<p style="color:var(--gray);font-size:0.9rem;"><?= $t['local_desc'] ?></p>
             </div>
            <div style="padding:30px;border-radius:12px;background:var(--light);">
                <div style="font-size:2.5rem;">⚡</div>
           <h3 style="margin:15px 0 10px;"><?= $t['fast'] ?></h3>
<p style="color:var(--gray);font-size:0.9rem;"><?= $t['fast_desc'] ?></p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>