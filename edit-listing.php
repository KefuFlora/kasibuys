<?php
require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$listing = $stmt->fetch();

if (!$listing) { header('Location: dashboard.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF($_POST['csrf_token'] ?? '');
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $condition_type = $_POST['condition_type'];
    $location = trim($_POST['location']);
    $status = $_POST['status'];
    $image_path = $listing['image'];

    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 5000000) {
            $filename = uniqid('listing_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], 'images/uploads/' . $filename);
            $image_path = 'images/uploads/' . $filename;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE listings SET title=?, description=?, price=?, category_id=?,
        condition_type=?, location=?, image=?, status=? WHERE id=? AND user_id=?
    ");
    $stmt->execute([$title, $description, $price, $category_id, $condition_type, $location, $image_path, $status, $id, $_SESSION['user_id']]);
    $success = 'Listing updated successfully!';
    $listing = array_merge($listing, compact('title','description','price','category_id','condition_type','location','status'));
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="form-container" style="max-width:700px;">
    <h2>Edit Listing</h2>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($listing['title']) ?>" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" required><?= htmlspecialchars($listing['description']) ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Price (R)</label>
                <input type="number" name="price" value="<?= $listing['price'] ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Condition</label>
                <select name="condition_type">
                    <option value="used" <?= $listing['condition_type']==='used'?'selected':'' ?>>Used</option>
                    <option value="new" <?= $listing['condition_type']==='new'?'selected':'' ?>>New</option>
                    <option value="refurbished" <?= $listing['condition_type']==='refurbished'?'selected':'' ?>>Refurbished</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $listing['category_id']==$cat['id']?'selected':'' ?>>
                            <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="<?= htmlspecialchars($listing['location']) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active" <?= $listing['status']==='active'?'selected':'' ?>>Active</option>
                <option value="sold" <?= $listing['status']==='sold'?'selected':'' ?>>Mark as Sold</option>
            </select>
        </div>
        <div class="form-group">
            <label>Update Photo</label>
            <?php if ($listing['image']): ?>
                <img src="<?= htmlspecialchars($listing['image']) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:8px;margin-bottom:10px;display:block;">
            <?php endif; ?>
            <input type="file" name="image" accept="image/*">
        </div>
        <button type="submit" class="btn-primary" style="width:100%;padding:14px;">Save Changes</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>