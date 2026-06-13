<?php
require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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

    if (empty($title) || empty($description) || empty($price) || empty($location)) {
        $error = 'Please fill in all required fields.';
    } else {
        $image_path = 'images/no-image.png';

        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Only JPG, PNG, and WEBP images are allowed.';
            } elseif ($_FILES['image']['size'] > 5000000) {
                $error = 'Image must be under 5MB.';
            } else {
                $filename = uniqid('listing_') . '.' . $ext;
                $upload_dir = 'images/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename);
                $image_path = $upload_dir . $filename;
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("
                INSERT INTO listings (user_id, category_id, title, description, price, condition_type, location, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'], $category_id, $title, $description,
                $price, $condition_type, $location, $image_path
            ]);
            $new_id = $pdo->lastInsertId();
            header("Location: listing-single.php?id=$new_id");
            exit;
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

include 'includes/header.php';
?>

<div class="form-container" style="max-width:700px;">
    <h2>Create a Listing</h2>
    <p class="subtitle">Fill in the details below to post your item</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" placeholder="e.g. iPhone 12 Pro Max 256GB" required>
        </div>

        <div class="form-group">
            <label>Description *</label>
            <textarea name="description" placeholder="Describe your item — condition, features, reason for selling..." required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Price (R) *</label>
                <input type="number" name="price" placeholder="0.00" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Condition *</label>
                <select name="condition_type" required>
                    <option value="used">Used</option>
                    <option value="new">New</option>
                    <option value="refurbished">Refurbished</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Location *</label>
                <input type="text" name="location" placeholder="e.g. Soweto, Johannesburg" required>
            </div>
        </div>

        <div class="form-group">
            <label>Item Photo</label>
            <input type="file" name="image" id="listing-image" accept="image/*">
            <div id="image-preview" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:8px;"></div>
            <small style="color:var(--gray);">Max 5MB. JPG, PNG or WEBP.</small>
        </div>

        <button type="submit" class="btn-primary" style="width:100%;padding:14px;">
            <i class="fas fa-plus"></i> Post Listing
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>