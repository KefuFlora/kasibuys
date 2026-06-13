<?php include 'includes/header.php'; ?>

<?php
$where = ["l.status = 'active'"];
$params = [];

// Search
if (!empty($_GET['q'])) {
    $where[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $params[] = '%' . $_GET['q'] . '%';
    $params[] = '%' . $_GET['q'] . '%';
}

// Category filter
if (!empty($_GET['category'])) {
    $where[] = "l.category_id = ?";
    $params[] = $_GET['category'];
}

// Price filter
if (!empty($_GET['min_price'])) {
    $where[] = "l.price >= ?";
    $params[] = $_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $where[] = "l.price <= ?";
    $params[] = $_GET['max_price'];
}

// Condition filter
if (!empty($_GET['condition'])) {
    $where[] = "l.condition_type = ?";
    $params[] = $_GET['condition'];
}

$whereSQL = implode(' AND ', $where);

// Sort
$sort = $_GET['sort'] ?? 'newest';
$orderBy = match($sort) {
    'price_asc' => 'l.price ASC',
    'price_desc' => 'l.price DESC',
    'oldest' => 'l.created_at ASC',
    default => 'l.created_at DESC'
};

$stmt = $pdo->prepare("
    SELECT l.*, u.full_name, u.location as user_location, c.name as category_name
    FROM listings l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN categories c ON l.category_id = c.id
    WHERE $whereSQL
    ORDER BY $orderBy
");
$stmt->execute($params);
$listings = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div style="max-width:1200px;margin:30px auto;padding:0 20px;">
    <div style="display:grid;grid-template-columns:250px 1fr;gap:30px;">

        <!-- FILTERS SIDEBAR -->
        <button onclick="toggleFilters()" class="btn-secondary" id="filter-toggle"
    style="display:none;width:100%;margin-bottom:15px;">
    <i class="fas fa-filter"></i> Show / Hide Filters
</button>
<aside id="filters-sidebar">
            <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--shadow);">
                <h3 style="margin-bottom:20px;">🔍 Filter Listings</h3>
                <form method="GET" action="listings.php">
                    <?php if (!empty($_GET['q'])): ?>
                        <input type="hidden" name="q" value="<?= htmlspecialchars($_GET['q']) ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($_GET['category'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Condition</label>
                        <select name="condition">
                            <option value="">Any Condition</option>
                            <option value="new" <?= ($_GET['condition'] ?? '') === 'new' ? 'selected' : '' ?>>New</option>
                            <option value="used" <?= ($_GET['condition'] ?? '') === 'used' ? 'selected' : '' ?>>Used</option>
                            <option value="refurbished" <?= ($_GET['condition'] ?? '') === 'refurbished' ? 'selected' : '' ?>>Refurbished</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Min Price (R)</label>
                        <input type="number" name="min_price" placeholder="0" value="<?= $_GET['min_price'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Max Price (R)</label>
                        <input type="number" name="max_price" placeholder="Any" value="<?= $_GET['max_price'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Sort By</label>
                        <select name="sort">
                            <option value="newest" <?= ($sort === 'newest') ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= ($sort === 'oldest') ? 'selected' : '' ?>>Oldest First</option>
                            <option value="price_asc" <?= ($sort === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_desc" <?= ($sort === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;">Apply Filters</button>
                    <a href="listings.php" style="display:block;text-align:center;margin-top:10px;color:var(--gray);font-size:0.85rem;">Clear Filters</a>
                </form>
            </div>
        </aside>
        <script>
function toggleFilters() {
    const sidebar = document.getElementById('filters-sidebar');
    sidebar.style.display = sidebar.style.display === 'none' ? 'block' : 'none';
}

// Show filter button on mobile
if (window.innerWidth <= 768) {
    document.getElementById('filter-toggle').style.display = 'block';
    document.getElementById('filters-sidebar').style.display = 'none';
}
</script>

        <!-- LISTINGS -->
        <main>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 style="font-size:1.2rem;"><?= count($listings) ?> listing(s) found</h2>
            </div>

            <?php if (empty($listings)): ?>
                <div style="background:white;border-radius:12px;padding:60px;text-align:center;box-shadow:var(--shadow);">
                    <div style="font-size:3rem;">😕</div>
                    <h3 style="margin:15px 0 10px;">No listings found</h3>
                    <p style="color:var(--gray)">Try adjusting your filters or <a href="listings.php" style="color:var(--primary)">browse all listings</a>.</p>
                </div>
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
                                    &bull; <?= htmlspecialchars($listing['category_name'] ?? '') ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>