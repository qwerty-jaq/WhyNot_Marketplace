<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ .'/includes/authentication.php';
require_login();

$cats = $pdo->query("SELECT * FROM categories ORDER BY CASE WHEN cat_name = 'Other' THEN 1 ELSE 0 END, cat_name")->fetchAll(PDO::FETCH_ASSOC);
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = (int)($_POST['category'] ?? 0);
    $condition = $_POST['condition'] ?? 'Good';
    $location = trim($_POST['location'] ?? '');

    // Validation
    if ($title === '') {
        $error = 'Title is required.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0.';
    } elseif ($category <= 0) {
        $error = 'Please select a category.';
    } else {
        // Handle image upload
        $image_filename = 'placeholder.jpg';
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Image must be jpg, png, gif, or webp.';
            } elseif ($_FILES['image']['size'] > 5*1024*1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $image_filename = 'prod_' . time() . '_' . current_user_id() . '.' . $ext;
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_filename)) {
                    $error = 'Failed to upload image.';
                }
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare("INSERT INTO products (seller_id, category_id, prod_title, prod_description, price, condition_status, image_url, location_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([current_user_id(), $category, $title, $description, $price, $condition, $image_filename, $location]);
            $success = true;
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Listing created!'];
            header('Location: product.php?id=' . $pdo->lastInsertId());
            exit;
        }
    }
}

$page_title = 'Sell an Item - WhyNot?';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="vd-form-card">
                <h2>List an item for sale</h2>
                <p class="text-muted mb-4">Fill in the details below to create your listing</p>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (R)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="">Select a category</option>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?= (int)$c['category_id'] ?>">
                                    <?= htmlspecialchars($c['cat_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition</label>
                        <select name="condition" class="form-select">
                            <?php foreach (['New', 'Like New', 'Good', 'Fair', 'Poor'] as $cond): ?>
                                <option value="<?= $cond ?>"><?= $cond ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                </div>
                    
                <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <img id="imagePreview" src="" style="display: none;max-width: 300px;margin-top: 10px;border-radius: 8px;">
                        <small class="text-muted d-block">Max 5MB. JPG, PNG, GIF or WebP</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Listing</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>