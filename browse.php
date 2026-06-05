<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ .'/includes/authentication.php';

//Filters from URL: ?q=search&cat=2&condition=Good&min=100&max=5000
$q         = trim($_GET['q'] ?? '');
$cat       = isset($_GET['cat']) ? (int)$_GET['cat'] :0;
$condition = $_GET['condition'] ?? '';
$min       = isset($_GET['min']) ? (float)$_GET['min'] :0;
$max       = isset($_GET['max']) ? (float)$_GET['max'] :0;

// Build WHERE clause dynamically
$where = ["prod_status = 'active'"];
$params = [];
if ($q !== '') {
    $where[] = "(prod_title LIKE ? OR prod_description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($cat > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $cat;
}
if ($condition !== '') {
    $where[] = "condition_status = ?";
    $params[] = $condition;
}
if ($min > 0) {
    $where[] = "price >= ?";
    $params[] = $min;
}
if ($max > 0) {
    $where[] = "price <= ?";
    $params[] = $max;
}

$sql = "SELECT p.*, c.cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = $pdo->query("SELECT * FROM categories ORDER BY CASE WHEN cat_name = 'Other' THEN 1 ELSE 0 END, cat_name")->fetchAll(PDO::FETCH_ASSOC);  

$page_title = 'Browse - WhyNot?';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4">Browse Listings</h2>

    <div class="row">
        <!-- FILTERS SIDEBAR -->
        <aside class="col-lg-3 mb-4">
            <div class="vd-form-card">
                <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter</h5>
                <form method="GET">
                    <div class="mb-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Keyword...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="cat" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?= (int)$c['category_id'] ?>" <?= $cat == $c['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['cat_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition</label>
                        <select name="condition" class="form-select">
                            <option value="">Any Condition</option>
                            <?php foreach (['New', 'Like New', 'Good', 'Fair', 'Poor'] as $cond): ?>
                                <option value="<?= $cond ?>" <?= $condition == $cond ? 'selected' : '' ?>><?= $cond ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="mb-3">
                            <label class="form-label">Min R:</label>
                            <input type="number" name="min" class="form-control" value="<?= $min > 0 ? (int)$min : '' ?>" placeholder="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max R:</label>
                            <input type="number" name="max" class="form-control" value="<?= $max > 0 ? (int)$max : '' ?>" placeholder="0">
                        </div>  
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </form>
            </div>
        </aside>

        <!-- PRODUCTS GRID -->
        <div class="col-lg-9">
            <p class="text-muted"><?= count($products) ?> listings found.</p>
            <div class="row">
                <?php if (empty($products)): ?>
                    <div class="col-12"><div class="alert alert-warning">No products match your filters. <a href="browse.php" class="alert-link">Clear filters</a></div></div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <?php include __DIR__ . '/includes/product_card.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>