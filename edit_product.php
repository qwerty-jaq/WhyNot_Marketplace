<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/authentication.php';
require_login();

$me = current_user_id();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// Fetch product and verify ownership.
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND seller_id = ?");
$stmt->execute([$id, $me]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product){
    $_SESSION['flash_message'] = 'Product not found or you are not the seller.';
    header('Location: seller-dashboard.php');
    exit;
}

// Fetch categories for dropdown
$cats = $pdo->query("SELECT * FROM categories ORDER BY cat_name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $category     = (int)($_POST['category'] ?? 0);
    $condition   = $_POST['condition'] ?? 'Good';
    $location    = trim($_POST['location'] ?? '');
    $status      = $_POST['prod_status'] ?? 'active';

    // Validation
    if ($title === '') {
        $error = 'Title is required.';
    } elseif ($category <= 0) {
        $error = 'Please select a category.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0.';
    } else {
        // Keep existing image unless a new one is uploaded
        $image_filename = $product['image_url'];

        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Image must be jpg, png, gif, or webp.';
            } elseif ($_FILES['image']['size'] > 5*1024*1024) {
                $error = 'Image must tbe under 5MB.';
            } else {
                $image_filename = 'prod_' . time() . '_' . $me . '.' . $exit;
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_filename)) {
                    $error = 'Failed to upload new image.';
                }
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare("
                UPDATE products
                SET prod_title = ?, prod_description = ?, price = ?, category_id = ?,
                    condition_status = ?, image_url = ?, location_user = ?, prod_status = ?
                WHERE product_id = ? AND seller_id = ?
            ");
            $stmt->execute([$title, $description, $price, $category, $condition, $image_filename, $location, $status, $id, $me]);

            $_SESSION['flash_message'] = 'Listing updated successfully.';
            header('Location: seller-dashboard.php');
            exit;
        }
    }
}

$page_title = 'Edit Listing - WhyNot?';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="vd-form-card">
                <h2><i class=bi bi-pencil-square"></i> Edit Listing</h2>
                <p class="text-muted mb-4"> Update the details for "<?= htmlspecialchars($product['prod_title']) ?>"</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" maxlength="150" required
                               value="<?= htmlspecialchars($_POST['title'] ?? $product['prod_title']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($_POST['description'] ?? $product['prod_description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (R)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required
                               value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?= (int)$c['category_id'] ?>"
                                    <?= ($_POST['category'] ?? $product['category_id']) == $c['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['cat_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition</label>
                        <select name="condition" class="form-select">
                            <?php foreach (['New', 'Like New', 'Good', 'Fair', 'Poor'] as $cond): ?>
                                <option value="<?= $cond ?>"
                                    <?= ($_POST['condition'] ?? $product['condition_status']) === $cond ? 'selected' : '' ?>>
                                    <?= $cond ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form_control"
                               value="<?= htmlspecialchars($_POST['location'] ?? ($product['location_user'] ?? '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="prod_status" class="form-select">
                            <?php foreach (['active', 'sold', 'removed'] as $s): ?>
                                <option value="<?= $s ?>"
                                    <?= ($_POST['prod_status'] ?? $product['prod_status']) === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Image</label><br>
                        <img src="/whynot/uploads/<?= htmlspecialchars($product['image_url'] ?? 'placeholder.jpg') ?>"
                             alt="Current product image" style="max-width: 200px; border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Replace Image (optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep the current image. Max 5MB. JPG, PNG, GIF or WebP</small>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="seller-dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>