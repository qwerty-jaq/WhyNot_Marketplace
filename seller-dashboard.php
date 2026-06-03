<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ .'/includes/authentication.php';
require_login();

// Handle delete
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ? AND seller_id = ?");
    $stmt->execute([$_POST['delete_id'], current_user_id()]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Listing deleted.'];
    header('Location: seller-dashboard.php');
    exit;
}

$me = current_user_id();

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM products WHERE seller_id = ? AND prod_status = 'active'");
$stmt->execute([$me]);
$active_count = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM orders WHERE seller_id = ? AND order_status != 'cancelled'");
$stmt->execute([$me]);
$sold_count = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COALESCE(SUM(price),0) AS total_sales FROM orders WHERE seller_id = ? AND order_status = 'completed'");
$stmt->execute([$me]);
$revenue = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COALESCE(SUM(views),0) AS total_views FROM products WHERE seller_id = ?");
$stmt->execute([$me]);
$views = $stmt->fetch(PDO::FETCH_ASSOC);

// My listings 
$stmt = $pdo->prepare("SELECT p.*, c.cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.seller_id = ? ORDER BY p.created_at DESC");
$stmt->execute([$me]);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Seller Dashboard - VerkoopDit';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-speedometer2"></i> Seller Dashboard</h2>
        <a href="sell.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Listing</a>
    </div>

    <!-- STATS -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3"><div class="vd-stat"><div class="vd-stat-num"><?= $active_count['total'] ?></div><div class="vd-stat-label">Active Listings</div></div></div>
        <div class="col-md-3 mb-3"><div class="vd-stat"><div class="vd-stat-num"><?= $sold_count['total'] ?></div><div class="vd-stat-label">Orders Received</div></div></div>
        <div class="col-md-3 mb-3"><div class="vd-stat"><div class="vd-stat-num">R<?= number_format((float)$revenue['total_sales'],2) ?></div><div class="vd-stat-label">Revenue</div></div></div>
        <div class="col-md-3 mb-3"><div class="vd-stat"><div class="vd-stat-num"><?= $views['total_views'] ?></div><div class="vd-stat-label">Total Views</div></div></div>
    </div>

    <!-- MY LISTINGS TABLE -->
    <h4>My Listings</h4>
    <?php if (empty($listings)): ?>
        <div class="alert alert-info">You don't have any listings yet. <a href="sell.php">Create one</a>.</div>
    <?php else: ?>
        <div class="table-responsive vd-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Condition</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Actions</th>
                    <th></th>
                </tr>
            </thead>
                <tbody>
                <?php foreach ($listings as $p): ?>
                    <tr>
                        <td><a href="product.php?id=<?= $p['product_id'] ?>"><?= htmlspecialchars($p['prod_title']) ?></a></td>
                        <td><?= htmlspecialchars($p['cat_name'] ?? '-') ?></td>
                        <td>R<?= number_format((float)$p['price'],2) ?></td>
                        <td><?= htmlspecialchars($p['condition_status']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($p['prod_status'])) ?></td>
                        <td><?= (int)$p['views'] ?></td>
                        <td><a href="edit_product.php?id=<?= $p['product_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this listing?');">
                                <input type="hidden" name="delete_id" value="<?= $p['product_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>